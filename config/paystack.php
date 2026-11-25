<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Paystack API Keys
    |--------------------------------------------------------------------------
    |
    | Your Paystack secret and public keys from your Paystack Dashboard.
    | The secret key is used for server-side API requests and webhook
    | signature verification.
    |
    */
    'secret_key' => env('PAYSTACK_SECRET_KEY', ''),
    'public_key' => env('PAYSTACK_PUBLIC_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Paystack Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for all Paystack API requests.
    |
    */
    'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
    'allow_custom_base_url' => (bool) env('PAYSTACK_ALLOW_CUSTOM_BASE_URL', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    |
    | Timeout (in seconds) and retry configuration for Paystack API requests.
    |
    */
    'timeout' => (int) env('PAYSTACK_TIMEOUT', 30),
    'connect_timeout' => (int) env('PAYSTACK_CONNECT_TIMEOUT', 10),
    'retry' => [
        'times' => (int) env('PAYSTACK_RETRY_TIMES', 0),
        'sleep_ms' => (int) env('PAYSTACK_RETRY_SLEEP_MS', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | Default currency used by helper utilities when none is specified.
    |
    */
    'currency' => env('PAYSTACK_CURRENCY', 'NGN'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the auto-registered webhook route, middleware, and IP
    | allowlist (optional). Signature verification is always enforced via
    | the VerifyPaystackWebhookSignature middleware.
    |
    */
    'webhook' => [
        'enabled' => (bool) env('PAYSTACK_WEBHOOK_ENABLED', true),
        'path' => env('PAYSTACK_WEBHOOK_PATH', 'paystack/webhook'),
        'name' => env('PAYSTACK_WEBHOOK_ROUTE_NAME', 'paystack.webhook'),
        'middleware' => [],
        'allowed_ips' => array_filter(
            array_map('trim', explode(',', (string) env('PAYSTACK_WEBHOOK_ALLOWED_IPS', '')))
        ),
    ],
];
