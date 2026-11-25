# Webhooks & Laravel Events

The package automatically registers a `POST` webhook route (`paystack.webhook` at `/paystack/webhook` by default) protected by `VerifyPaystackWebhookSignature`.

## 1. Security & Verification

Every incoming request passes through `Edydeyemi\Paystack\Http\Middleware\VerifyPaystackWebhookSignature`:

1. **Optional IP Allowlist**: If `PAYSTACK_WEBHOOK_ALLOWED_IPS` is non-empty, requests from any other IP are rejected with `403 Forbidden`.
2. **HMAC-SHA512 Signature**: Computes `hash_hmac('sha512', $rawBody, config('paystack.secret_key'))` and compares it against the `x-paystack-signature` header using `hash_equals()`. Invalid or missing signatures receive `403 Forbidden`.

> Make sure `paystack/webhook` is excluded from CSRF verification in `bootstrap/app.php` (see [Installation & Configuration](01-installation-and-configuration.md)).
> If `PAYSTACK_WEBHOOK_PATH` is customized, use that exact path in the CSRF exception.

## 2. Dispatched Events

For every verified webhook, `WebhookController` dispatches:
1. `Edydeyemi\Paystack\Events\PaystackWebhookReceived` (always dispatched for any Paystack event).
2. A **typed event class** if the Paystack `event` name matches one of the built-in mappings:

| Paystack Event | Laravel Event Class |
|---|---|
| `charge.success` | `Edydeyemi\Paystack\Events\ChargeSucceeded` |
| `transfer.success` | `Edydeyemi\Paystack\Events\TransferSucceeded` |
| `transfer.failed` | `Edydeyemi\Paystack\Events\TransferFailed` |
| `transfer.reversed` | `Edydeyemi\Paystack\Events\TransferReversed` |
| `subscription.create` | `Edydeyemi\Paystack\Events\SubscriptionCreated` |
| `subscription.disable` | `Edydeyemi\Paystack\Events\SubscriptionDisabled` |
| `subscription.not_renew` | `Edydeyemi\Paystack\Events\SubscriptionNotRenewing` |
| `invoice.create` | `Edydeyemi\Paystack\Events\InvoiceCreated` |
| `invoice.payment_failed` | `Edydeyemi\Paystack\Events\InvoicePaymentFailed` |
| `refund.processed` | `Edydeyemi\Paystack\Events\RefundProcessed` |
| `customeridentification.success` | `Edydeyemi\Paystack\Events\CustomerIdentificationSucceeded` |
| `customeridentification.failed` | `Edydeyemi\Paystack\Events\CustomerIdentificationFailed` |
| `dedicatedaccount.assign.success` | `Edydeyemi\Paystack\Events\DedicatedAccountAssigned` |
| `preauthorization.capture.success` | `Edydeyemi\Paystack\Events\PreauthorizationCaptureSucceeded` |
| `preauthorization.capture.failed` | `Edydeyemi\Paystack\Events\PreauthorizationCaptureFailed` |
| `preauthorization.release.success` | `Edydeyemi\Paystack\Events\PreauthorizationReleaseSucceeded` |

## 3. Listening to Webhook Events (Recommended: Queued Listeners)

The controller dispatches the generic event and any matching typed event before returning `200 OK`. Laravel listeners run synchronously unless queued. Implement `ShouldQueue` for work that performs database updates, external I/O, emails, or fulfillment, and use an asynchronous queue driver in production; the `sync` queue driver still runs jobs inline.

Webhook deliveries may be retried or duplicated. Treat them as at-least-once and make payment state changes idempotent. Use a stable transaction reference and an atomic database transition or uniqueness constraint before triggering fulfillment.

```php
<?php

namespace App\Listeners;

use App\Models\Order;
use App\Jobs\FulfillOrderJob;
use Edydeyemi\Paystack\Events\ChargeSucceeded;
use Illuminate\Contracts\Queue\ShouldQueue;

class FulfillPaidOrder implements ShouldQueue
{
    public function handle(ChargeSucceeded $event): void
    {
        $reference = (string) $event->data('reference');
        $amount = (int) $event->data('amount');

        $transitioned = Order::query()
            ->where('reference', $reference)
            ->where('status', 'pending')
            ->where('total_kobo', $amount)
            ->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

        if ($transitioned === 1) {
            FulfillOrderJob::dispatch($reference);
        }
    }
}
```

### Handling Unmapped Events via `PaystackWebhookReceived`

```php
<?php

namespace App\Listeners;

use Edydeyemi\Paystack\Events\PaystackWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogPaystackWebhook implements ShouldQueue
{
    public function handle(PaystackWebhookReceived $event): void
    {
        logger()->info('Paystack webhook received', [
            'event' => $event->event,
            'reference' => $event->data('reference'),
        ]);
    }
}
```

## 4. Testing Webhooks in Pest

Use `WebhookSignature::compute()` to sign test payloads in your application's Pest tests:

```php
use Edydeyemi\Paystack\Events\ChargeSucceeded;
use Edydeyemi\Paystack\Webhooks\WebhookSignature;
use Illuminate\Support\Facades\Event;

it('fulfills an order on charge.success webhook', function () {
    Event::fake([ChargeSucceeded::class]);

    $payload = [
        'event' => 'charge.success',
        'data' => [
            'reference' => 'ord_01J...',
            'amount' => 500000,
        ],
    ];

    $signature = WebhookSignature::compute(
        json_encode($payload, JSON_THROW_ON_ERROR),
        config('paystack.secret_key'),
    );

    $this->postJson(route('paystack.webhook'), $payload, [
        'x-paystack-signature' => $signature,
    ])->assertOk();

    Event::assertDispatched(ChargeSucceeded::class);
});
```
