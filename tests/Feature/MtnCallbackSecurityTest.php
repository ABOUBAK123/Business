<?php

namespace Tests\Feature;

use Tests\TestCase;

class MtnCallbackSecurityTest extends TestCase
{
    public function test_mtn_callback_is_blocked_when_ip_not_allowlisted(): void
    {
        config()->set('services.mtn_momo.callback.ip_filter_enabled', true);
        config()->set('services.mtn_momo.callback.allowed_ips', '203.0.113.10');
        config()->set('services.mtn_momo.callback.trusted_proxies', '');

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->post('/payment/mtn/notify', []);

        $response->assertStatus(403);
    }

    public function test_mtn_callback_is_allowed_when_source_ip_is_allowlisted(): void
    {
        config()->set('services.mtn_momo.callback.ip_filter_enabled', true);
        config()->set('services.mtn_momo.callback.allowed_ips', '198.51.100.20');
        config()->set('services.mtn_momo.callback.trusted_proxies', '');

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->post('/payment/mtn/notify', []);

        $response->assertStatus(422);
    }

    public function test_mtn_callback_uses_x_forwarded_for_only_for_trusted_proxy(): void
    {
        config()->set('services.mtn_momo.callback.ip_filter_enabled', true);
        config()->set('services.mtn_momo.callback.allowed_ips', '203.0.113.45');
        config()->set('services.mtn_momo.callback.trusted_proxies', '10.10.10.10');

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '10.10.10.10'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.45, 10.10.10.10'])
            ->post('/payment/mtn/notify', []);

        $response->assertStatus(422);
    }

    public function test_mtn_callback_accepts_put_method_when_ip_is_allowlisted(): void
    {
        config()->set('services.mtn_momo.callback.ip_filter_enabled', true);
        config()->set('services.mtn_momo.callback.allowed_ips', '198.51.100.77');
        config()->set('services.mtn_momo.callback.trusted_proxies', '');

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.77'])
            ->put('/payment/mtn/notify', []);

        $response->assertStatus(422);
    }
}
