<?php

namespace App\Services\Payments;

use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CinetPayGateway
{
    public function initiate(SubscriptionPayment $payment): array
    {
        $siteId = config('services.cinetpay.site_id');
        $apiKey = config('services.cinetpay.api_key');
        $currency = config('services.cinetpay.currency', 'XOF');
        $returnUrl = config('services.cinetpay.return_url') ?: route('payment.cinetpay.return');
        $notifyUrl = config('services.cinetpay.notify_url') ?: route('payment.cinetpay.notify');

        if (! $siteId || ! $apiKey || ! $returnUrl || ! $notifyUrl) {
            return [
                'success' => false,
                'message' => 'La configuration CinetPay est incomplète.',
            ];
        }

        $transactionId = $payment->reference ?: (string) Str::uuid();
        $payment->forceFill([
            'provider' => 'cinetpay',
            'reference' => $transactionId,
            'metadata' => array_merge($payment->metadata ?? [], [
                'site_id' => $siteId,
                'currency' => $currency,
            ]),
            'status' => 'pending',
        ])->save();

        $payload = [
            'site_id' => $siteId,
            'apikey' => $apiKey,
            'transaction_id' => $transactionId,
            'amount' => (int) round((float) $payment->amount),
            'currency' => $currency,
            'description' => 'Renouvellement de forfait',
            'notify_url' => $notifyUrl,
            'return_url' => $returnUrl,
            'channels' => 'ALL',
            'metadata' => json_encode([
                'subscription_payment_id' => $payment->id,
                'tenant_id' => $payment->tenant_id,
            ], JSON_THROW_ON_ERROR),
        ];

        $response = Http::asForm()->post(config('services.cinetpay.init_url'), $payload);

        if (! $response->successful()) {
            return [
                'success' => false,
                'message' => 'Impossible de contacter le prestataire de paiement.',
            ];
        }

        $data = $response->json();

        if (($data['code'] ?? null) !== '201') {
            return [
                'success' => false,
                'message' => $data['message'] ?? 'Le prestataire a refusé l’initiation du paiement.',
                'raw' => $data,
            ];
        }

        $payment->forceFill([
            'provider' => 'cinetpay',
            'reference' => $transactionId,
            'metadata' => array_merge($payment->metadata ?? [], [
                'initiation_response' => $data,
            ]),
        ])->save();

        return [
            'success' => true,
            'payment_url' => $data['data']['payment_url'] ?? null,
            'transaction_id' => $transactionId,
            'raw' => $data,
        ];
    }

    public function verify(SubscriptionPayment $payment): array
    {
        $siteId = config('services.cinetpay.site_id');
        $apiKey = config('services.cinetpay.api_key');

        if (! $siteId || ! $apiKey || ! $payment->reference) {
            return [
                'success' => false,
                'message' => 'La configuration de vérification est incomplète.',
            ];
        }

        $response = Http::asForm()->post(config('services.cinetpay.check_url'), [
            'site_id' => $siteId,
            'apikey' => $apiKey,
            'transaction_id' => $payment->reference,
        ]);

        if (! $response->successful()) {
            return [
                'success' => false,
                'message' => 'Impossible de vérifier le paiement.',
            ];
        }

        $data = $response->json();
        $status = data_get($data, 'data.payment_status') ?? data_get($data, 'data.status');

        return [
            'success' => true,
            'status' => $status,
            'raw' => $data,
        ];
    }
}
