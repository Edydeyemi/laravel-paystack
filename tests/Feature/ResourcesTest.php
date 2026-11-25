<?php

declare(strict_types=1);

use Edydeyemi\Paystack\Facades\Paystack;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'https://api.paystack.co/*' => Http::response([
            'status' => true,
            'message' => 'Success',
            'data' => ['ok' => true],
        ]),
    ]);
});

it('calls transaction endpoints', function () {
    Paystack::transactions()->verify('ref/123');
    Paystack::transactions()->list(['perPage' => 10]);
    Paystack::transactions()->fetch(42);
    Paystack::transactions()->chargeAuthorization(['authorization_code' => 'AUTH_123', 'email' => 'a@b.com', 'amount' => 1000]);
    Paystack::transactions()->totals();

    Http::assertSent(fn (Request $r) => $r->method() === 'GET' && str_contains($r->url(), 'transaction/verify/ref%2F123'));
    Http::assertSent(fn (Request $r) => $r->method() === 'GET' && str_contains($r->url(), 'transaction?perPage=10'));
    Http::assertSent(fn (Request $r) => $r->method() === 'GET' && $r->url() === 'https://api.paystack.co/transaction/42');
    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r->url() === 'https://api.paystack.co/transaction/charge_authorization');
    Http::assertSent(fn (Request $r) => $r->method() === 'GET' && $r->url() === 'https://api.paystack.co/transaction/totals');
});

it('calls refund, customer, plan, and subscription endpoints', function () {
    Paystack::refunds()->create(['transaction' => 'ref_1']);
    Paystack::customers()->create(['email' => 'ada@example.com']);
    Paystack::customers()->update('CUS_123', ['first_name' => 'Ada']);
    Paystack::plans()->create(['name' => 'Pro', 'interval' => 'monthly', 'amount' => 500000]);
    Paystack::subscriptions()->enable('SUB_123', 'tok_123');
    Paystack::subscriptions()->disable('SUB_123', 'tok_123');

    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r->url() === 'https://api.paystack.co/refund');
    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r->url() === 'https://api.paystack.co/customer');
    Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && $r->url() === 'https://api.paystack.co/customer/CUS_123');
    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r->url() === 'https://api.paystack.co/plan');
    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r->url() === 'https://api.paystack.co/subscription/enable');
    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r->url() === 'https://api.paystack.co/subscription/disable');
});

it('calls transfer, recipient, subaccount, split, dva, verification, and misc endpoints', function () {
    Paystack::transferRecipients()->create(['type' => 'nuban', 'name' => 'Ada', 'account_number' => '0123456789', 'bank_code' => '058']);
    Paystack::transfers()->initiate(['source' => 'balance', 'amount' => 50000, 'recipient' => 'RCP_123']);
    Paystack::subaccounts()->create(['business_name' => 'Acme', 'settlement_bank' => '058', 'account_number' => '0123456789', 'percentage_charge' => 10]);
    Paystack::splits()->addOrUpdateSubaccount(12, 'ACCT_123', 50);
    Paystack::dedicatedAccounts()->providers();
    Paystack::verification()->resolveAccount('0123456789', '058');
    Paystack::miscellaneous()->banks(['country' => 'nigeria', 'include_nip_sort_code' => true]);

    Paystack::preauthorizations()->initialize(['email' => 'guest@example.com', 'amount' => 250000, 'currency' => 'ZAR']);
    Paystack::preauthorizations()->capture(['reference' => 'preauth_1', 'amount' => 185000, 'currency' => 'ZAR']);
    Paystack::preauthorizations()->release('preauth_1');

    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r->url() === 'https://api.paystack.co/transferrecipient');
    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r->url() === 'https://api.paystack.co/transfer');
    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r->url() === 'https://api.paystack.co/subaccount');
    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r->url() === 'https://api.paystack.co/split/12/subaccount/add');
    Http::assertSent(fn (Request $r) => $r->method() === 'GET' && $r->url() === 'https://api.paystack.co/dedicated_account/available_providers');
    Http::assertSent(fn (Request $r) => $r->method() === 'GET' && str_contains($r->url(), 'bank/resolve'));
    Http::assertSent(fn (Request $r) => $r->method() === 'GET' && str_contains($r->url(), 'bank?country=nigeria'));
    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r->url() === 'https://api.paystack.co/preauthorization/initialize');
    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r->url() === 'https://api.paystack.co/preauthorization/capture');
    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r->url() === 'https://api.paystack.co/preauthorization/release' && $r['reference'] === 'preauth_1');
});
