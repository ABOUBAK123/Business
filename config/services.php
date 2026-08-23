<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'cinetpay' => [
        'site_id' => env('CINETPAY_SITE_ID'),
        'api_key' => env('CINETPAY_API_KEY'),
        'currency' => env('CINETPAY_CURRENCY', 'XOF'),
        'init_url' => env('CINETPAY_INIT_URL', 'https://api-checkout.cinetpay.com/v2/payment'),
        'check_url' => env('CINETPAY_CHECK_URL', 'https://api-checkout.cinetpay.com/v2/payment/check'),
        'return_url' => env('CINETPAY_RETURN_URL'),
        'notify_url' => env('CINETPAY_NOTIFY_URL'),
    ],

    'mtn_momo' => [
        'api_user' => env('MTN_MOMO_API_USER_ID'),
        'api_key' => env('MTN_MOMO_API_KEY'),
        'subscription_key' => env('MTN_MOMO_SUBSCRIPTION_KEY'),
        'target_environment' => env('MTN_MOMO_TARGET_ENVIRONMENT', 'sandbox'),
        'base_url' => env('MTN_MOMO_BASE_URL', 'https://sandbox.momodeveloper.mtn.com'),
        'country_code' => env('MTN_MOMO_COUNTRY_CODE', '225'),
        'callback' => [
            'ip_filter_enabled' => env('MTN_CALLBACK_IP_FILTER_ENABLED', false),
            'allowed_ips' => env('MTN_CALLBACK_ALLOWED_IPS', ''),
            'trusted_proxies' => env('MTN_CALLBACK_TRUSTED_PROXIES', ''),
            'audit_log_channel' => env('MTN_CALLBACK_AUDIT_LOG_CHANNEL', 'mtn_audit'),
        ],
    ],

    'wave' => [
        'api_key' => env('WAVE_API_KEY'),
        'webhook_secret' => env('WAVE_WEBHOOK_SECRET'),
        'aggregated_merchant_id' => env('WAVE_AGGREGATED_MERCHANT_ID'),
        'base_url' => env('WAVE_BASE_URL', 'https://api.wave.com'),
        'webhook' => [
            'ip_filter_enabled' => env('WAVE_WEBHOOK_IP_FILTER_ENABLED', false),
            // Official Wave webhook source IPs, per https://docs.wave.com/webhook
            'allowed_ips' => env('WAVE_WEBHOOK_ALLOWED_IPS', implode(',', [
                '104.155.43.220/32', '34.140.23.175/32', '34.22.138.147/32',
                '34.76.157.22/32', '34.78.253.137/32', '34.79.119.200/32',
                '35.189.207.30/32', '35.195.255.192/32', '35.205.122.113/32',
                '35.205.190.121/32', '35.233.61.130/32', '35.240.61.196/32',
                '35.240.75.65/32', '35.241.190.127/32', '35.241.219.1/32',
            ])),
            'trusted_proxies' => env('WAVE_WEBHOOK_TRUSTED_PROXIES', ''),
            'audit_log_channel' => env('WAVE_WEBHOOK_AUDIT_LOG_CHANNEL', 'wave_audit'),
        ],
    ],

];
