<?php

namespace App\Services\Payments;

use App\Models\Setting;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaveCheckoutGateway
{
    public function initiate(SubscriptionPayment $payment): array
    {
        $config = $this->config();

        if (! $config['api_key']) {
            return [
                'success' => false,
                'message' => 'La configuration Wave est incomplète.',
            ];
        }

        $reference = $payment->reference ?: (string) $payment->id . '-' . now()->timestamp;

        $payload = [
            'amount' => (string) round((float) $payment->amount),
            'currency' => $payment->currency ?: 'XOF',
            'error_url' => route('payment.wave.return', ['status' => 'error', 'payment' => $payment->id]),
            'success_url' => route('payment.wave.return', ['status' => 'success', 'payment' => $payment->id]),
            'client_reference' => $reference,
        ];

        if ($config['aggregated_merchant_id']) {
            $payload['aggregated_merchant_id'] = $config['aggregated_merchant_id'];
        }

        $response = Http::acceptJson()
            ->withToken($config['api_key'])
            ->timeout($this->requestTimeoutSeconds())
            ->connectTimeout($this->connectTimeoutSeconds())
            ->post(rtrim($config['base_url'], '/') . '/v1/checkout/sessions', $payload);

        if (! $response->successful()) {
            Log::warning('wave.checkout.initiate_failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => 'La création de la session de paiement Wave a échoué.',
                'raw' => $response->json(),
            ];
        }

        $data = $response->json();
        $sessionId = $data['id'] ?? null;
        $launchUrl = $data['wave_launch_url'] ?? null;

        if (! $sessionId || ! $launchUrl) {
            return [
                'success' => false,
                'message' => 'Réponse Wave incomplète (session ou URL de paiement manquante).',
                'raw' => $data,
            ];
        }

        $payment->forceFill([
            'provider' => 'wave',
            'reference' => $sessionId,
            'status' => 'pending',
            'metadata' => array_merge($payment->metadata ?? [], [
                'wave' => [
                    'client_reference' => $reference,
                    'session_id' => $sessionId,
                    'checkout_session_response' => $data,
                ],
            ]),
        ])->save();

        return [
            'success' => true,
            'payment_url' => $launchUrl,
            'transaction_id' => $sessionId,
            'raw' => $data,
        ];
    }

    public function verify(SubscriptionPayment $payment): array
    {
        $config = $this->config();

        if (! $config['api_key'] || ! $payment->reference) {
            return [
                'success' => false,
                'message' => 'Configuration Wave incomplète pour vérification.',
            ];
        }

        $response = Http::acceptJson()
            ->withToken($config['api_key'])
            ->timeout($this->requestTimeoutSeconds())
            ->connectTimeout($this->connectTimeoutSeconds())
            ->get(rtrim($config['base_url'], '/') . '/v1/checkout/sessions/' . $payment->reference);

        if (! $response->successful()) {
            Log::warning('wave.checkout.verify_failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => 'Impossible de vérifier le paiement Wave.',
                'raw' => $response->json(),
            ];
        }

        $data = $response->json();
        $status = $this->mapStatus(
            (string) ($data['checkout_status'] ?? ''),
            (string) ($data['payment_status'] ?? '')
        );

        return [
            'success' => true,
            'status' => $status,
            'raw' => $data,
        ];
    }

    /**
     * Verify the "Wave-Signature: t=<timestamp>,v1=<signature>" header against the raw
     * request body, per Wave's HMAC-SHA256 webhook verification scheme.
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool
    {
        $webhookSecret = $this->config()['webhook_secret'];

        if (! $webhookSecret || ! $signatureHeader) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $segment) {
            [$key, $value] = array_pad(explode('=', trim($segment), 2), 2, null);
            if ($key !== null && $value !== null) {
                $parts[$key][] = $value;
            }
        }

        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];

        if (! $timestamp || $signatures === []) {
            return false;
        }

        if (abs(now()->timestamp - (int) $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . $rawBody, $webhookSecret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    private function mapStatus(string $checkoutStatus, string $paymentStatus): string
    {
        if ($paymentStatus === 'succeeded' && $checkoutStatus === 'complete') {
            return 'SUCCESSFUL';
        }

        if ($paymentStatus === 'cancelled' || $checkoutStatus === 'expired') {
            return 'FAILED';
        }

        return 'PENDING';
    }

    private function config(): array
    {
        return [
            'api_key' => Setting::get('wave_api_key', env('WAVE_API_KEY')),
            'webhook_secret' => Setting::get('wave_webhook_secret', env('WAVE_WEBHOOK_SECRET')),
            'aggregated_merchant_id' => Setting::get('wave_business_id', env('WAVE_AGGREGATED_MERCHANT_ID')),
            'base_url' => rtrim((string) Setting::get('wave_base_url', env('WAVE_BASE_URL', 'https://api.wave.com')), '/'),
        ];
    }

    private function requestTimeoutSeconds(): int
    {
        return max(5, min(60, (int) env('WAVE_TIMEOUT_SECONDS', 20)));
    }

    private function connectTimeoutSeconds(): int
    {
        return max(2, min(30, (int) env('WAVE_CONNECT_TIMEOUT_SECONDS', 10)));
    }
}
