<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Http\Controllers;

use Edydeyemi\Paystack\Events\ChargeSucceeded;
use Edydeyemi\Paystack\Events\CustomerIdentificationFailed;
use Edydeyemi\Paystack\Events\CustomerIdentificationSucceeded;
use Edydeyemi\Paystack\Events\DedicatedAccountAssigned;
use Edydeyemi\Paystack\Events\InvoiceCreated;
use Edydeyemi\Paystack\Events\InvoicePaymentFailed;
use Edydeyemi\Paystack\Events\PaystackWebhookEvent;
use Edydeyemi\Paystack\Events\PaystackWebhookReceived;
use Edydeyemi\Paystack\Events\PreauthorizationCaptureFailed;
use Edydeyemi\Paystack\Events\PreauthorizationCaptureSucceeded;
use Edydeyemi\Paystack\Events\PreauthorizationReleaseSucceeded;
use Edydeyemi\Paystack\Events\RefundProcessed;
use Edydeyemi\Paystack\Events\SubscriptionCreated;
use Edydeyemi\Paystack\Events\SubscriptionDisabled;
use Edydeyemi\Paystack\Events\SubscriptionNotRenewing;
use Edydeyemi\Paystack\Events\TransferFailed;
use Edydeyemi\Paystack\Events\TransferReversed;
use Edydeyemi\Paystack\Events\TransferSucceeded;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Invokable controller that handles verified Paystack webhook notifications.
 *
 * Dispatches {@see PaystackWebhookReceived} for every webhook and, when the
 * Paystack `event` key matches a known event, dispatches its dedicated typed event.
 */
class WebhookController
{
    /**
     * Map of Paystack webhook event names to typed Laravel event classes.
     *
     * @var array<string, class-string<PaystackWebhookEvent>>
     */
    protected const array EVENT_MAP = [
        'charge.success' => ChargeSucceeded::class,
        'transfer.success' => TransferSucceeded::class,
        'transfer.failed' => TransferFailed::class,
        'transfer.reversed' => TransferReversed::class,
        'subscription.create' => SubscriptionCreated::class,
        'subscription.disable' => SubscriptionDisabled::class,
        'subscription.not_renew' => SubscriptionNotRenewing::class,
        'invoice.create' => InvoiceCreated::class,
        'invoice.payment_failed' => InvoicePaymentFailed::class,
        'refund.processed' => RefundProcessed::class,
        'customeridentification.success' => CustomerIdentificationSucceeded::class,
        'customeridentification.failed' => CustomerIdentificationFailed::class,
        'dedicatedaccount.assign.success' => DedicatedAccountAssigned::class,
        'preauthorization.capture.success' => PreauthorizationCaptureSucceeded::class,
        'preauthorization.capture.failed' => PreauthorizationCaptureFailed::class,
        'preauthorization.release.success' => PreauthorizationReleaseSucceeded::class,
    ];

    /**
     * Process an incoming verified Paystack webhook request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all();
        $event = is_string($payload['event'] ?? null) ? $payload['event'] : '';

        // Always dispatch the catch-all event so listeners can audit or handle any Paystack event type.
        PaystackWebhookReceived::dispatch($event, $payload);

        // Dispatch the strongly-typed event when the event name is mapped.
        if ($event !== '' && isset(self::EVENT_MAP[$event])) {
            $eventClass = self::EVENT_MAP[$event];
            $eventClass::dispatch($event, $payload);
        }

        // Event dispatch happens before the acknowledgement; listeners doing I/O or business work should be queued.
        return response()->json(['status' => 'ok']);
    }
}
