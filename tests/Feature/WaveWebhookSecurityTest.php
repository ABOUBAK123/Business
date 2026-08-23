<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaveWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;


    public function test_wave_webhook_is_blocked_when_ip_not_allowlisted(): void
    {
        config()->set('services.wave.webhook.ip_filter_enabled', true);
        config()->set('services.wave.webhook.allowed_ips', '203.0.113.10');
        config()->set('services.wave.webhook.trusted_proxies', '');

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->post('/payment/wave/notify', []);

        $response->assertStatus(403);
    }

    public function test_wave_webhook_reaches_signature_check_when_ip_is_allowlisted(): void
    {
        config()->set('services.wave.webhook.ip_filter_enabled', true);
        config()->set('services.wave.webhook.allowed_ips', '198.51.100.20');
        config()->set('services.wave.webhook.trusted_proxies', '');

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->post('/payment/wave/notify', []);

        // Passes the IP gate, then rejected for missing/invalid Wave-Signature.
        $response->assertStatus(401);
    }

    public function test_wave_webhook_uses_x_forwarded_for_only_for_trusted_proxy(): void
    {
        config()->set('services.wave.webhook.ip_filter_enabled', true);
        config()->set('services.wave.webhook.allowed_ips', '203.0.113.45');
        config()->set('services.wave.webhook.trusted_proxies', '10.10.10.10');

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '10.10.10.10'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.45, 10.10.10.10'])
            ->post('/payment/wave/notify', []);

        $response->assertStatus(401);
    }

    public function test_wave_webhook_signature_is_verified_correctly(): void
    {
        config()->set('services.wave.webhook.ip_filter_enabled', false);

        \App\Models\Setting::set('wave_webhook_secret', 'unit-test-secret', 'mobile_money', true);

        $gateway = app(\App\Services\Payments\WaveCheckoutGateway::class);
        $body = json_encode(['id' => 'AE_test', 'type' => 'checkout.session.completed', 'data' => ['id' => 'cos-doesnotexist']]);
        $timestamp = now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp . $body, 'unit-test-secret');

        $this->assertTrue($gateway->verifyWebhookSignature($body, "t={$timestamp},v1={$signature}"));
        $this->assertFalse($gateway->verifyWebhookSignature($body, "t={$timestamp},v1=deadbeef"));
        $this->assertFalse($gateway->verifyWebhookSignature($body, null));
    }
}
