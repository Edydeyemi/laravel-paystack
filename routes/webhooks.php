<?php

declare(strict_types=1);

use Edydeyemi\Paystack\Http\Controllers\WebhookController;
use Edydeyemi\Paystack\Http\Middleware\VerifyPaystackWebhookSignature;
use Illuminate\Support\Facades\Route;

// Only register the webhook endpoint when enabled in configuration.
if (config('paystack.webhook.enabled', true)) {
    /** @var array<int, string> $customMiddleware */
    $customMiddleware = config('paystack.webhook.middleware', []);

    // Always run signature verification before any user-configured middleware.
    Route::post(
        (string) config('paystack.webhook.path', 'paystack/webhook'),
        WebhookController::class,
    )
        ->name((string) config('paystack.webhook.name', 'paystack.webhook'))
        ->middleware(array_merge([VerifyPaystackWebhookSignature::class], $customMiddleware));
}
