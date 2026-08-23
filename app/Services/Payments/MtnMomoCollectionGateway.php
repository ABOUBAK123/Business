<?php

namespace App\Services\Payments;

use App\Models\Setting;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MtnMomoCollectionGateway
{
    public function initiate(SubscriptionPayment $payment, string $payerPhone): array
    {
        $config = $this->config();

        if (! $config['api_user'] || ! $config['api_key'] || ! $config['subscription_key']) {
            return [
                'success' => false,
                'message' => 'La configuration MTN MoMo est incomplète.',
            ];
        }

        $normalizedPhone = $this->normalizeMsisdn($payerPhone, $config['country_code']);
        if (! $normalizedPhone) {
            return [
                'success' => false,
                'message' => 'Le numero de telephone MTN est invalide.',
            ];
        }

        $accessToken = $this->getAccessToken($config);
        if (! $accessToken) {
            return [
                'success' => false,
                'message' => 'Impossible d obtenir le token MTN MoMo.',
            ];
        }

        $reference = $payment->reference ?: (string) Str::uuid();
        $externalId = 'SUBPAY-' . $payment->id;

        $payment->forceFill([
            'provider' => 'mtn_momo',
            'reference' => $reference,
            'status' => 'pending',
            'metadata' => array_merge($payment->metadata ?? [], [
                'mtn' => [
                    'payer_msisdn' => $normalizedPhone,
                    'environment' => $config['target_environment'],
                    'base_url' => $config['base_url'],
                    'external_id' => $externalId,
                ],
            ]),
        ])->save();

        $requestResponse = $this->sendWithRetry(function () use ($accessToken, $config, $reference, $payment, $externalId, $normalizedPhone) {
            return $this->newHttpClient()
                ->withToken($accessToken)
                ->withHeaders([
                    'X-Reference-Id' => $reference,
                    'X-Target-Environment' => $config['target_environment'],
                    'Ocp-Apim-Subscription-Key' => $config['subscription_key'],
                    'X-Callback-Url' => route('payment.mtn.notify'),
                    'Content-Type' => 'application/json',
                ])
                ->post(rtrim($config['base_url'], '/') . '/collection/v1_0/requesttopay', [
                    'amount' => (string) round((float) $payment->amount),
                    'currency' => $payment->currency ?: 'XOF',
                    'externalId' => $externalId,
                    'payer' => [
                        'partyIdType' => 'MSISDN',
                        'partyId' => $normalizedPhone,
                    ],
                    'payerMessage' => 'Renouvellement abonnement boutique',
                    'payeeNote' => 'Subscription payment ' . $payment->id,
                ]);
        });

        if (! $requestResponse || $requestResponse->status() !== 202) {
            return [
                'success' => false,
                'message' => 'La demande de paiement MTN a ete refusee.',
                'raw' => $requestResponse?->json(),
            ];
        }

        $payment->forceFill([
            'metadata' => array_merge($payment->metadata ?? [], [
                'mtn' => array_merge(data_get($payment->metadata, 'mtn', []), [
                    'request_to_pay_response' => $requestResponse->json(),
                ]),
            ]),
        ])->save();

        return [
            'success' => true,
            'reference' => $reference,
            'status' => 'PENDING',
            'raw' => $requestResponse->json(),
        ];
    }

    public function verify(SubscriptionPayment $payment): array
    {
        $config = $this->config();

        if (! $config['api_user'] || ! $config['api_key'] || ! $config['subscription_key'] || ! $payment->reference) {
            return [
                'success' => false,
                'message' => 'Configuration MTN MoMo incomplète pour verification.',
            ];
        }

        $accessToken = $this->getAccessToken($config);
        if (! $accessToken) {
            return [
                'success' => false,
                'message' => 'Token MTN indisponible pour verification.',
            ];
        }

        $response = $this->sendWithRetry(function () use ($accessToken, $config, $payment) {
            return $this->newHttpClient()
                ->withToken($accessToken)
                ->withHeaders([
                    'X-Target-Environment' => $config['target_environment'],
                    'Ocp-Apim-Subscription-Key' => $config['subscription_key'],
                ])
                ->get(rtrim($config['base_url'], '/') . '/collection/v1_0/requesttopay/' . $payment->reference);
        });

        if (! $response || ! $response->successful()) {
            return [
                'success' => false,
                'message' => 'Impossible de verifier le paiement MTN.',
                'raw' => $response?->json(),
            ];
        }

        $status = strtoupper((string) ($response->json('status') ?? ''));

        return [
            'success' => true,
            'status' => $status,
            'raw' => $response->json(),
        ];
    }

    private function getAccessToken(array $config): ?string
    {
        $cacheKey = 'mtn_momo_token_' . md5($config['target_environment'] . '|' . $config['api_user'] . '|' . $config['subscription_key']);

        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $tokenResponse = $this->sendWithRetry(function () use ($config) {
            return $this->newHttpClient()
                ->withBasicAuth($config['api_user'], $config['api_key'])
                ->withHeaders([
                    'Ocp-Apim-Subscription-Key' => $config['subscription_key'],
                ])
                ->withOptions(['body' => ''])
                ->post(rtrim($config['base_url'], '/') . '/collection/token/');
        });

        if (! $tokenResponse || ! $tokenResponse->successful()) {
            Log::warning('mtn_momo.token.failed', [
                'status' => $tokenResponse?->status(),
                'body' => $tokenResponse?->json(),
            ]);
            return null;
        }

        $accessToken = $tokenResponse->json('access_token');
        if (! $accessToken) {
            Log::warning('mtn_momo.token.missing_in_response', ['body' => $tokenResponse->json()]);
            return null;
        }

        $expiresIn = (int) ($tokenResponse->json('expires_in') ?? 3600);
        $ttlSeconds = max(60, $expiresIn - 60);
        Cache::put($cacheKey, $accessToken, $ttlSeconds);

        return $accessToken;
    }

    private function config(): array
    {
        return [
            'api_user' => Setting::get('mtn_momo_api_key', env('MTN_MOMO_API_USER_ID')),
            'api_key' => Setting::get('mtn_momo_api_secret', env('MTN_MOMO_API_KEY')),
            'subscription_key' => Setting::get('mtn_momo_subscription_key', env('MTN_MOMO_SUBSCRIPTION_KEY')),
            'target_environment' => Setting::get('mtn_momo_target_environment', env('MTN_MOMO_TARGET_ENVIRONMENT', 'sandbox')),
            'base_url' => rtrim((string) Setting::get('mtn_momo_base_url', env('MTN_MOMO_BASE_URL', 'https://sandbox.momodeveloper.mtn.com')), '/'),
            'country_code' => (string) Setting::get('mtn_momo_country_code', env('MTN_MOMO_COUNTRY_CODE', '225')),
        ];
    }

    private function normalizeMsisdn(string $rawPhone, string $countryCode): ?string
    {
        $digits = preg_replace('/\D+/', '', $rawPhone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0')) {
            $digits = ltrim($digits, '0');
            $digits = $countryCode . $digits;
        } elseif (! str_starts_with($digits, $countryCode) && strlen($digits) <= 10) {
            $digits = $countryCode . $digits;
        }

        if (strlen($digits) < 8 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }

    private function newHttpClient(): PendingRequest
    {
        return Http::acceptJson()
            ->timeout($this->requestTimeoutSeconds())
            ->connectTimeout($this->connectTimeoutSeconds());
    }

    private function sendWithRetry(callable $operation): ?Response
    {
        $maxAttempts = $this->maxRetryAttempts();

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                /** @var Response $response */
                $response = $operation();

                if ($response->serverError() || $response->status() === 429) {
                    if ($attempt < $maxAttempts) {
                        $this->sleepWithBackoff($attempt);
                        continue;
                    }

                    Log::error('mtn_momo.request.failed_after_retries', [
                        'attempts' => $attempt,
                        'status' => $response->status(),
                        'body' => $response->json(),
                    ]);
                }

                return $response;
            } catch (\Throwable $e) {
                if ($attempt >= $maxAttempts) {
                    Log::error('mtn_momo.request.exception_after_retries', [
                        'attempts' => $attempt,
                        'error' => $e->getMessage(),
                    ]);
                    return null;
                }

                $this->sleepWithBackoff($attempt);
            }
        }

        return null;
    }

    private function sleepWithBackoff(int $attempt): void
    {
        $baseDelayMs = $this->retryBaseDelayMs();
        $maxDelayMs = 5000;
        $jitterMs = random_int(0, 150);
        $delayMs = min($maxDelayMs, ($baseDelayMs * (2 ** ($attempt - 1))) + $jitterMs);

        usleep($delayMs * 1000);
    }

    private function maxRetryAttempts(): int
    {
        $value = (int) Setting::get('mtn_momo_max_retries', env('MTN_MOMO_MAX_RETRIES', 3));
        return max(1, min(5, $value));
    }

    private function retryBaseDelayMs(): int
    {
        $value = (int) Setting::get('mtn_momo_retry_base_delay_ms', env('MTN_MOMO_RETRY_BASE_DELAY_MS', 300));
        return max(100, min(3000, $value));
    }

    private function requestTimeoutSeconds(): int
    {
        $value = (int) Setting::get('mtn_momo_timeout_seconds', env('MTN_MOMO_TIMEOUT_SECONDS', 20));
        return max(5, min(60, $value));
    }

    private function connectTimeoutSeconds(): int
    {
        $value = (int) Setting::get('mtn_momo_connect_timeout_seconds', env('MTN_MOMO_CONNECT_TIMEOUT_SECONDS', 10));
        return max(2, min(30, $value));
    }
}
