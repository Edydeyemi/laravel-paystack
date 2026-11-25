<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Events;

use Edydeyemi\Paystack\Http\Controllers\WebhookController;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

/**
 * Base class for all Paystack webhook events dispatched by {@see WebhookController}.
 */
abstract class PaystackWebhookEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new webhook event instance.
     *
     * @param  string  $event  Raw Paystack event name (e.g. "charge.success").
     * @param  array<string, mixed>  $payload  Full decoded JSON webhook payload.
     */
    public function __construct(
        public readonly string $event,
        public readonly array $payload,
    ) {}

    /**
     * Retrieve the webhook `data` object or a nested value using dot notation.
     *
     * @param  string|null  $key  Dot-notated key inside `data` (e.g. "customer.email"), or null for all data.
     * @param  mixed  $default  Fallback value when the key is missing.
     */
    public function data(?string $key = null, mixed $default = null): mixed
    {
        $data = $this->payload['data'] ?? null;

        if ($key === null) {
            return $data ?? $default;
        }

        if (! is_array($data)) {
            return $default;
        }

        return Arr::get($data, $key, $default);
    }
}
