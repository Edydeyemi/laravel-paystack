<?php

declare(strict_types=1);

use Edydeyemi\Paystack\Events\ChargeSucceeded;
use Edydeyemi\Paystack\Events\PaystackWebhookReceived;
use Edydeyemi\Paystack\Events\PreauthorizationCaptureSucceeded;
use Edydeyemi\Paystack\Events\PreauthorizationReleaseSucceeded;
use Edydeyemi\Paystack\Events\TransferFailed;
use Edydeyemi\Paystack\Webhooks\WebhookSignature;
use Illuminate\Support\Facades\Event;

it('dispatches generic and typed events for valid webhook', function () {
    Event::fake();

    $payload = [
        'event' => 'charge.success',
        'data' => [
            'reference' => 'ref_abc',
            'amount' => 250000,
        ],
    ];

    $raw = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = WebhookSignature::compute($raw, 'sk_test_1234567890abcdef');

    $this->postJson(route('paystack.webhook'), $payload, [
        'x-paystack-signature' => $signature,
    ])->assertOk()->assertJson(['status' => 'ok']);

    Event::assertDispatched(PaystackWebhookReceived::class, function (PaystackWebhookReceived $event): bool {
        return $event->event === 'charge.success'
            && $event->data('reference') === 'ref_abc';
    });

    Event::assertDispatched(ChargeSucceeded::class, function (ChargeSucceeded $event): bool {
        return $event->data('amount') === 250000;
    });

    Event::assertNotDispatched(TransferFailed::class);
});

it('rejects webhooks with missing or invalid signature', function () {
    Event::fake();

    $payload = ['event' => 'charge.success', 'data' => []];

    $this->postJson(route('paystack.webhook'), $payload)
        ->assertForbidden();

    $this->postJson(route('paystack.webhook'), $payload, [
        'x-paystack-signature' => 'invalid_signature',
    ])->assertForbidden();

    Event::assertNotDispatched(PaystackWebhookReceived::class);
});

it('enforces allowed ips when configured', function () {
    Event::fake();
    config()->set('paystack.webhook.allowed_ips', ['52.31.139.75']);

    $payload = ['event' => 'transfer.failed', 'data' => ['reference' => 'trf_1']];
    $raw = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = WebhookSignature::compute($raw, 'sk_test_1234567890abcdef');

    $this->postJson(route('paystack.webhook'), $payload, [
        'x-paystack-signature' => $signature,
        'REMOTE_ADDR' => '10.0.0.1',
    ])->assertForbidden();

    $this->postJson(route('paystack.webhook'), $payload, [
        'x-paystack-signature' => $signature,
        'REMOTE_ADDR' => '52.31.139.75',
    ])->assertOk();

    Event::assertDispatched(TransferFailed::class);
});

it('dispatches preauthorization capture and release events', function () {
    Event::fake();

    $capturePayload = ['event' => 'preauthorization.capture.success', 'data' => ['reference' => 'preauth_1']];
    $captureSig = WebhookSignature::compute(json_encode($capturePayload, JSON_THROW_ON_ERROR), 'sk_test_1234567890abcdef');

    $this->postJson(route('paystack.webhook'), $capturePayload, [
        'x-paystack-signature' => $captureSig,
    ])->assertOk();

    $releasePayload = ['event' => 'preauthorization.release.success', 'data' => ['reference' => 'preauth_2']];
    $releaseSig = WebhookSignature::compute(json_encode($releasePayload, JSON_THROW_ON_ERROR), 'sk_test_1234567890abcdef');

    $this->postJson(route('paystack.webhook'), $releasePayload, [
        'x-paystack-signature' => $releaseSig,
    ])->assertOk();

    Event::assertDispatched(PreauthorizationCaptureSucceeded::class, fn (PreauthorizationCaptureSucceeded $e) => $e->data('reference') === 'preauth_1');
    Event::assertDispatched(PreauthorizationReleaseSucceeded::class, fn (PreauthorizationReleaseSucceeded $e) => $e->data('reference') === 'preauth_2');
});
