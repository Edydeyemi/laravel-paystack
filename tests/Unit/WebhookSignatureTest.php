<?php

declare(strict_types=1);

use Edydeyemi\Paystack\Exceptions\InvalidWebhookSignatureException;
use Edydeyemi\Paystack\Exceptions\MissingConfigurationException;
use Edydeyemi\Paystack\Webhooks\WebhookSignature;

it('verifies a valid hmac sha512 signature', function () {
    $payload = '{"event":"charge.success","data":{"reference":"ref_123"}}';
    $secret = 'sk_test_secret';
    $signature = WebhookSignature::compute($payload, $secret);

    WebhookSignature::verify($payload, $signature, $secret);

    expect(true)->toBeTrue();
});

it('throws when signature is missing or invalid', function () {
    $payload = '{"event":"charge.success"}';

    expect(fn () => WebhookSignature::verify($payload, null, 'sk_test_secret'))
        ->toThrow(InvalidWebhookSignatureException::class)
        ->and(fn () => WebhookSignature::verify($payload, 'bad_sig', 'sk_test_secret'))
        ->toThrow(InvalidWebhookSignatureException::class)
        ->and(fn () => WebhookSignature::verify($payload, 'sig', ''))
        ->toThrow(MissingConfigurationException::class);
});
