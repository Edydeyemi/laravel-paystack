<?php

declare(strict_types=1);

use Edydeyemi\Paystack\Exceptions\MissingConfigurationException;
use Edydeyemi\Paystack\Exceptions\PaystackRequestException;
use Edydeyemi\Paystack\Facades\Paystack;
use Edydeyemi\Paystack\Http\PaystackClient;
use Edydeyemi\Paystack\Paystack as PaystackManager;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('sends bearer token and json headers to paystack', function () {
    Http::fake([
        'https://api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'message' => 'Authorization URL created',
            'data' => [
                'authorization_url' => 'https://checkout.paystack.com/abc',
                'access_code' => 'abc',
                'reference' => 'ref_123',
            ],
        ]),
    ]);

    $response = Paystack::transactions()->initialize([
        'email' => 'ada@example.com',
        'amount' => 50000,
    ]);

    expect($response->ok())->toBeTrue()
        ->and($response->data('reference'))->toBe('ref_123');

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.paystack.co/transaction/initialize'
            && $request->hasHeader('Authorization', 'Bearer sk_test_1234567890abcdef')
            && $request['email'] === 'ada@example.com'
            && $request['amount'] === 50000;
    });
});

it('throws PaystackRequestException on failed http response', function () {
    Http::fake([
        'https://api.paystack.co/transaction/verify/*' => Http::response([
            'status' => false,
            'message' => 'Transaction reference not found',
        ], 404),
    ]);

    expect(fn () => Paystack::transactions()->verify('missing_ref'))
        ->toThrow(PaystackRequestException::class, 'Transaction reference not found');
});

it('throws PaystackRequestException when paystack returns status false with 200', function () {
    Http::fake([
        'https://api.paystack.co/bank/resolve*' => Http::response([
            'status' => false,
            'message' => 'Could not resolve account name.',
        ], 200),
    ]);

    expect(fn () => Paystack::verification()->resolveAccount('0000000000', '058'))
        ->toThrow(PaystackRequestException::class, 'Could not resolve account name.');
});

it('retries safe GET requests but does not retry POST requests', function () {
    config()->set('paystack.retry.times', 2);
    config()->set('paystack.retry.sleep_ms', 0);
    app()->forgetInstance(PaystackClient::class);
    app()->forgetInstance(PaystackManager::class);
    Paystack::clearResolvedInstances();

    Http::fakeSequence('https://api.paystack.co/transaction/verify/*')
        ->push(['status' => false, 'message' => 'Temporary failure'], 503)
        ->push(['status' => true, 'message' => 'Success', 'data' => ['status' => 'success']], 200);

    expect(Paystack::transactions()->verify('ref_123')->ok())->toBeTrue();
    Http::assertSentCount(2);
});

it('does not retry a write request when retries are configured', function () {
    config()->set('paystack.retry.times', 2);
    config()->set('paystack.retry.sleep_ms', 0);
    app()->forgetInstance(PaystackClient::class);
    app()->forgetInstance(PaystackManager::class);
    Paystack::clearResolvedInstances();

    Http::fakeSequence('https://api.paystack.co/transaction/initialize')
        ->push(['status' => false, 'message' => 'Temporary failure'], 503)
        ->push(['status' => true, 'message' => 'Success'], 200);

    expect(fn () => Paystack::transactions()->initialize([
        'email' => 'ada@example.com',
        'amount' => 50000,
    ]))->toThrow(PaystackRequestException::class, 'Temporary failure');

    Http::assertSentCount(1);
});

it('requires explicit trust for custom API hosts and HTTPS', function () {
    config()->set('paystack.base_url', 'https://gateway.example');
    app()->forgetInstance(PaystackClient::class);
    app()->forgetInstance(PaystackManager::class);
    Paystack::clearResolvedInstances();

    expect(fn () => Paystack::miscellaneous()->countries())
        ->toThrow(InvalidArgumentException::class, 'PAYSTACK_ALLOW_CUSTOM_BASE_URL=true');

    config()->set('paystack.allow_custom_base_url', true);
    app()->forgetInstance(PaystackClient::class);
    app()->forgetInstance(PaystackManager::class);
    Paystack::clearResolvedInstances();
    Http::fake([
        'https://gateway.example/country' => Http::response([
            'status' => true,
            'message' => 'Success',
            'data' => [],
        ]),
    ]);

    Paystack::miscellaneous()->countries();
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://gateway.example/country'
        && $request->hasHeader('Authorization', 'Bearer sk_test_1234567890abcdef'));

    config()->set('paystack.base_url', 'http://gateway.example');
    app()->forgetInstance(PaystackClient::class);
    app()->forgetInstance(PaystackManager::class);
    Paystack::clearResolvedInstances();

    expect(fn () => Paystack::miscellaneous()->countries())
        ->toThrow(InvalidArgumentException::class, 'must use HTTPS');

    foreach ([
        'https://user:pass@api.paystack.co',
        'https://api.paystack.co?token=value',
        'https://api.paystack.co:8443',
    ] as $invalidBaseUrl) {
        config()->set('paystack.base_url', $invalidBaseUrl);
        app()->forgetInstance(PaystackClient::class);
        app()->forgetInstance(PaystackManager::class);
        Paystack::clearResolvedInstances();

        expect(fn () => Paystack::miscellaneous()->countries())
            ->toThrow(InvalidArgumentException::class);
    }
});

it('throws MissingConfigurationException when secret key is empty', function () {
    config()->set('paystack.secret_key', '');
    app()->forgetInstance(PaystackClient::class);
    app()->forgetInstance(PaystackManager::class);
    Paystack::clearResolvedInstances();

    expect(fn () => Paystack::miscellaneous()->countries())
        ->toThrow(MissingConfigurationException::class);
});

it('generates unique references with prefix', function () {
    $ref1 = Paystack::generateReference('ord_');
    $ref2 = Paystack::generateReference('ord_');

    expect($ref1)->toStartWith('ord_')
        ->and($ref2)->toStartWith('ord_')
        ->and($ref1)->not->toBe($ref2);
});
