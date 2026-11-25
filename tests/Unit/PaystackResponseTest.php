<?php

declare(strict_types=1);

use Edydeyemi\Paystack\Http\PaystackResponse;

it('wraps paystack json payload and provides fluent accessors', function () {
    $response = new PaystackResponse([
        'status' => true,
        'message' => 'Verification successful',
        'data' => [
            'reference' => 'ref_123',
            'amount' => 50000,
            'customer' => [
                'email' => 'ada@example.com',
            ],
        ],
        'meta' => [
            'total' => 1,
        ],
    ], 200);

    expect($response->status())->toBeTrue()
        ->and($response->ok())->toBeTrue()
        ->and($response->message())->toBe('Verification successful')
        ->and($response->data('reference'))->toBe('ref_123')
        ->and($response->data('customer.email'))->toBe('ada@example.com')
        ->and($response->data('missing', 'fallback'))->toBe('fallback')
        ->and($response->meta('total'))->toBe(1)
        ->and($response->httpStatus())->toBe(200)
        ->and($response['status'])->toBeTrue()
        ->and(count($response))->toBe(3);
});

it('is immutable via array access', function () {
    $response = new PaystackResponse(['status' => true]);

    expect(fn () => $response['status'] = false)->toThrow(LogicException::class);
});
