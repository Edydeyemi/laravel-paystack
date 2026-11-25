# Laravel Paystack — Complete User Guide

A step-by-step guide to integrating **`edydeyemi/laravel-paystack`** into a **Laravel 13** application.

---

## Table of Contents

1. [Installation & Setup](#1-installation--setup)
2. [Core Concepts: Amounts, Responses & Exceptions](#2-core-concepts-amounts-responses--exceptions)
3. [Accepting Payments (Transactions)](#3-accepting-payments-transactions)
4. [Card Preauthorizations (Hold, Capture, Release)](#4-card-preauthorizations-hold-capture-release)
5. [Refunds](#5-refunds)
6. [Customers, Plans & Subscriptions](#6-customers-plans--subscriptions)
7. [Bank Verification & Listing Banks](#7-bank-verification--listing-banks)
8. [Transfers & Transfer Recipients (Payouts)](#8-transfers--transfer-recipients-payouts)
9. [Subaccounts & Transaction Splits (Marketplaces)](#9-subaccounts--transaction-splits-marketplaces)
10. [Dedicated Virtual Accounts (DVA)](#10-dedicated-virtual-accounts-dva)
11. [Webhooks & Event-Driven Fulfillment](#11-webhooks--event-driven-fulfillment)
12. [Testing Your Integration](#12-testing-your-integration)

---

## 1. Installation & Setup

Install the package via Composer:

```bash
composer require edydeyemi/laravel-paystack
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="paystack-config"
```

Add your Paystack keys and preferences to `.env`:

```env
PAYSTACK_SECRET_KEY=sk_test_your_secret_key
PAYSTACK_PUBLIC_KEY=pk_test_your_public_key
PAYSTACK_CURRENCY=NGN
PAYSTACK_ALLOW_CUSTOM_BASE_URL=false

# Optional webhook customization
PAYSTACK_WEBHOOK_ENABLED=true
PAYSTACK_WEBHOOK_PATH=paystack/webhook
PAYSTACK_WEBHOOK_ALLOWED_IPS=52.31.139.75,52.49.173.169,52.214.14.220
```

### Exclude the Webhook Route from CSRF Protection

In Laravel 13 (`bootstrap/app.php`), exclude the Paystack webhook path so Paystack's `POST` requests are not blocked by CSRF verification:

```php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'paystack/webhook', // Must match PAYSTACK_WEBHOOK_PATH if customized.
        ]);
    })
    ->create();
```

---

## 2. Core Concepts: Amounts, Responses & Exceptions

### Working with Minor Units (`Amount` Helper)

Paystack expects all monetary amounts in the **minor unit** of the currency (e.g., kobo for `NGN`, pesewas for `GHS`, cents for `ZAR`/`KES`). Use `Edydeyemi\Paystack\Support\Amount` to convert. Prefer decimal strings for exact input; float inputs may already have lost precision before conversion. The helper rejects values outside the PHP integer range and supports precision from 0 to 18:

```php
use Edydeyemi\Paystack\Support\Amount;

$kobo = Amount::toMinor('12500.50');     // 1250050
$naira = Amount::toMajor(1250050);       // 12500.5
$label = Amount::format(1250050, 'NGN'); // "NGN 12,500.50"
```

### Working with `PaystackResponse`

Every API call returns an immutable `Edydeyemi\Paystack\Http\PaystackResponse`:

```php
$response = Paystack::transactions()->verify($reference);

$response->ok();                         // bool (true when HTTP 2xx and status === true)
$response->message();                    // "Verification successful"
$response->data('amount');               // 1250050 (supports dot notation)
$response->data('customer.email');       // "ada@example.com"
$response->data('metadata.order_id', 0); // default fallback if missing
$response->meta('total');                // pagination metadata on list endpoints
$response->toArray();                    // full decoded JSON payload
```

### Handling Errors (`PaystackRequestException`)

If Paystack returns an HTTP 4xx/5xx status, a connection failure occurs, or Paystack responds with `"status": false`, the client throws `Edydeyemi\Paystack\Exceptions\PaystackRequestException`:

```php
use Edydeyemi\Paystack\Exceptions\PaystackRequestException;
use Edydeyemi\Paystack\Facades\Paystack;

try {
    $result = Paystack::verification()->resolveAccount('0123456789', '058');
} catch (PaystackRequestException $e) {
    logger()->warning('Paystack verification failed', [
        'status' => $e->statusCode,
        'message' => $e->getMessage(),
        'errors' => $e->errors(),
    ]);
}
```

> You can use either the `Paystack` facade or inject `Edydeyemi\Paystack\Paystack` via constructor dependency injection in controllers, services, and jobs.

---

## 3. Accepting Payments (Transactions)

### Standard Checkout Flow (Initialize -> Redirect -> Verify)

```php
use App\Models\Order;
use Edydeyemi\Paystack\Facades\Paystack;
use Edydeyemi\Paystack\Support\Amount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController
{
    public function store(Request $request, Order $order): RedirectResponse
    {
        $reference = Paystack::generateReference('ord_');

        $order->update(['payment_reference' => $reference]);

        $response = Paystack::transactions()->initialize([
            'email' => $request->user()->email,
            'amount' => Amount::toMinor($order->total_amount),
            'currency' => 'NGN',
            'reference' => $reference,
            'callback_url' => route('checkout.callback'),
            'metadata' => [
                'order_id' => $order->id,
                'custom_fields' => [
                    ['display_name' => 'Order Number', 'variable_name' => 'order_number', 'value' => $order->number],
                ],
            ],
        ]);

        return redirect()->away((string) $response->data('authorization_url'));
    }

    public function callback(Request $request): RedirectResponse
    {
        $reference = (string) $request->query('reference');
        $verification = Paystack::transactions()->verify($reference);

        $order = Order::where('payment_reference', $reference)->firstOrFail();

        if ($verification->data('status') === 'success'
            && (int) $verification->data('amount') === Amount::toMinor($order->total_amount)) {
            $order->markAsPaid($verification->data('authorization.authorization_code'));

            return redirect()->route('orders.show', $order)->with('status', 'Payment confirmed!');
        }

        return redirect()->route('orders.show', $order)->withErrors('Payment could not be verified.');
    }
}
```

### Charging a Saved Card (`chargeAuthorization`)

```php
$charge = Paystack::transactions()->chargeAuthorization([
    'authorization_code' => $user->paystack_auth_code,
    'email' => $user->email,
    'amount' => Amount::toMinor(4500),
    'reference' => Paystack::generateReference('rebill_'),
]);
```

### Other Transaction Endpoints

```php
Paystack::transactions()->list(['perPage' => 25, 'status' => 'success']);
Paystack::transactions()->fetch(123456789);
Paystack::transactions()->timeline($reference);
Paystack::transactions()->totals(['from' => '2026-01-01', 'to' => '2026-01-31']);
Paystack::transactions()->export(['status' => 'success']);
Paystack::transactions()->partialDebit([
    'authorization_code' => 'AUTH_72btv547',
    'currency' => 'NGN',
    'amount' => Amount::toMinor(1000),
    'email' => 'customer@example.com',
]);
```

---

## 4. Card Preauthorizations (Hold, Capture, Release)

Card preauthorization (available on Paystack for **South Africa / `ZAR`**) lets you place a temporary hold on a customer's card and settle the final amount later—ideal for hotels, car rentals, or on-demand services.

### Step 1: Initialize a Hold

```php
use Edydeyemi\Paystack\Facades\Paystack;
use Edydeyemi\Paystack\Support\Amount;

$reference = Paystack::generateReference('preauth_');

$hold = Paystack::preauthorizations()->initialize([
    'email' => 'guest@example.com',
    'amount' => Amount::toMinor(2500), // R2,500.00 in ZAR cents
    'currency' => 'ZAR',
    'reference' => $reference,
    'expire_after_days' => 5,          // Optional: 1-7 days (default 5)
    'expire_action' => 'release',      // Optional: 'release' (default) or 'capture'
]);

return redirect()->away((string) $hold->data('authorization_url'));
```

### Step 2a: Capture the Final Amount

Capture up to the originally held amount. If you capture less, Paystack automatically releases the remainder:

```php
$capture = Paystack::preauthorizations()->capture([
    'reference' => $reference,
    'amount' => Amount::toMinor(1850), // Final bill: R1,850.00
    'currency' => 'ZAR',
]);
```

### Step 2b: Or Release the Hold

If the booking is canceled before fulfillment:

```php
$release = Paystack::preauthorizations()->release($reference);
```

Because captures and releases complete asynchronously, listen for `PreauthorizationCaptureSucceeded`, `PreauthorizationCaptureFailed`, and `PreauthorizationReleaseSucceeded` (see [Section 11](#11-webhooks--event-driven-fulfillment)).

---

## 5. Refunds

```php
// Full refund
Paystack::refunds()->create([
    'transaction' => $reference,
]);

// Partial refund with merchant note
Paystack::refunds()->create([
    'transaction' => $reference,
    'amount' => Amount::toMinor(1500),
    'merchant_note' => 'Partial refund for missing item',
]);

// List, fetch, or retry a failed refund
Paystack::refunds()->list(['perPage' => 20]);
Paystack::refunds()->fetch(10452);
Paystack::refunds()->retry(10452, [
    'refund_account_details' => [
        'currency' => 'NGN',
        'account_number' => '0123456789',
        'bank_id' => '9',
    ],
]);
```

---

## 6. Customers, Plans & Subscriptions

### Managing Customers

```php
// Create & update
$customer = Paystack::customers()->create([
    'email' => 'ada@example.com',
    'first_name' => 'Ada',
    'last_name' => 'Lovelace',
    'phone' => '+2348012345678',
]);

$customerCode = (string) $customer->data('customer_code');

Paystack::customers()->update($customerCode, ['first_name' => 'Augusta']);
Paystack::customers()->fetch($customerCode);
Paystack::customers()->list(['perPage' => 50]);

// Risk action: 'allow', 'deny', or 'default'
Paystack::customers()->setRiskAction($customerCode, 'allow');
```

### Plans & Subscriptions

```php
// 1. Create a recurring plan
$plan = Paystack::plans()->create([
    'name' => 'Pro Monthly',
    'interval' => 'monthly',
    'amount' => Amount::toMinor(15000),
]);

// 2. Subscribe a customer
$subscription = Paystack::subscriptions()->create([
    'customer' => $customerCode,
    'plan' => $plan->data('plan_code'),
]);

$subCode = (string) $subscription->data('subscription_code');
$emailToken = (string) $subscription->data('email_token');

// 3. Manage subscription lifecycle
Paystack::subscriptions()->disable($subCode, $emailToken);
Paystack::subscriptions()->enable($subCode, $emailToken);

// 4. Generate or email a self-service card update link
$link = Paystack::subscriptions()->generateUpdateLink($subCode)->data('link');
Paystack::subscriptions()->sendUpdateLink($subCode);
```

---

## 7. Bank Verification & Listing Banks

### Listing Supported Banks

Use `Paystack::miscellaneous()->banks()` to populate bank dropdowns in your UI and obtain `bank_code` values:

```php
// Nigerian banks with NIP sort codes
$banks = Paystack::miscellaneous()->banks([
    'country' => 'nigeria',
    'include_nip_sort_code' => true,
    'perPage' => 100,
])->data();

// Ghana mobile money channels
$momoProviders = Paystack::miscellaneous()->banks([
    'country' => 'ghana',
    'type' => 'mobile_money',
])->data();

// South African banks enabled for account verification
$zaBanks = Paystack::miscellaneous()->banks([
    'country' => 'south africa',
    'enabled_for_verification' => true,
])->data();
```

### Resolving & Validating Bank Accounts

Always resolve a customer's account number before creating a transfer recipient or saving payout details:

```php
$resolved = Paystack::verification()->resolveAccount('0123456789', '058');

$accountName = $resolved->data('account_name');     // "DOE JANE LOREN"
$accountNumber = $resolved->data('account_number'); // "0123456789"
$bankId = $resolved->data('bank_id');               // 9
```

You can also validate an account with identity details or inspect a card's BIN:

```php
Paystack::verification()->validateAccount([
    'account_name' => 'Ann Perkinson',
    'account_number' => '0123456789',
    'account_type' => 'personal',
    'bank_code' => '632005',
    'country_code' => 'ZA',
    'document_type' => 'identityNumber',
    'document_number' => '1234567890123',
]);

$binInfo = Paystack::verification()->resolveCardBin('539983');
```

---

## 8. Transfers & Transfer Recipients (Payouts)

```php
// 1. Resolve bank account first, then create a transfer recipient
$account = Paystack::verification()->resolveAccount('0123456789', '058');

$recipient = Paystack::transferRecipients()->create([
    'type' => 'nuban',
    'name' => $account->data('account_name'),
    'account_number' => '0123456789',
    'bank_code' => '058',
    'currency' => 'NGN',
]);

$recipientCode = (string) $recipient->data('recipient_code');

// 2. Initiate a transfer
$reference = Paystack::generateReference('payout_');

$transfer = Paystack::transfers()->initiate([
    'source' => 'balance',
    'amount' => Amount::toMinor(25000),
    'recipient' => $recipientCode,
    'reference' => $reference,
    'reason' => 'Vendor settlement #402',
]);

// 3. If OTP is enabled on your Paystack account, finalize with OTP:
// Paystack::transfers()->finalize((string) $transfer->data('transfer_code'), '123456');

// 4. Verify transfer status
$status = Paystack::transfers()->verify($reference)->data('status');
```

For batch payouts, use `bulkCreate()` and `bulk()`:

```php
Paystack::transferRecipients()->bulkCreate([
    ['type' => 'nuban', 'name' => 'Vendor A', 'account_number' => '0123456789', 'bank_code' => '058'],
    ['type' => 'nuban', 'name' => 'Vendor B', 'account_number' => '9876543210', 'bank_code' => '044'],
]);

Paystack::transfers()->bulk([
    'currency' => 'NGN',
    'source' => 'balance',
    'transfers' => [
        ['amount' => Amount::toMinor(5000), 'recipient' => 'RCP_a', 'reference' => Paystack::generateReference('blk_')],
        ['amount' => Amount::toMinor(7500), 'recipient' => 'RCP_b', 'reference' => Paystack::generateReference('blk_')],
    ],
]);
```

---

## 9. Subaccounts & Transaction Splits (Marketplaces)

```php
// Create a vendor subaccount (platform takes 15%, vendor receives 85%)
$subaccount = Paystack::subaccounts()->create([
    'business_name' => 'Kemi Crafts',
    'settlement_bank' => '058',
    'account_number' => '0123456789',
    'percentage_charge' => 15,
]);

// Create a multi-party split group
$split = Paystack::splits()->create([
    'name' => 'Marketplace Order Split',
    'type' => 'percentage',
    'currency' => 'NGN',
    'subaccounts' => [
        ['subaccount' => $subaccount->data('subaccount_code'), 'share' => 80],
    ],
    'bearer_type' => 'all-proportional',
]);

// Use the split_code when initializing a transaction
Paystack::transactions()->initialize([
    'email' => 'buyer@example.com',
    'amount' => Amount::toMinor(10000),
    'split_code' => $split->data('split_code'),
]);
```

---

## 10. Dedicated Virtual Accounts (DVA)

```php
// Assign a customer + DVA in a single step
$dva = Paystack::dedicatedAccounts()->assign([
    'email' => 'ada@example.com',
    'first_name' => 'Ada',
    'last_name' => 'Lovelace',
    'phone' => '+2348012345678',
    'preferred_bank' => 'wema-bank',
    'country' => 'NG',
]);

// Check available bank providers, list, requery, or deactivate
Paystack::dedicatedAccounts()->providers();
Paystack::dedicatedAccounts()->list(['active' => true]);
Paystack::dedicatedAccounts()->requery(['account_number' => '1234567890', 'provider_slug' => 'wema-bank']);
Paystack::dedicatedAccounts()->deactivate(54321);
```

---

## 11. Webhooks & Event-Driven Fulfillment

The package registers `POST /paystack/webhook` (route name `paystack.webhook`) protected by `VerifyPaystackWebhookSignature`.
If `PAYSTACK_WEBHOOK_PATH` is customized, use that exact path in the application's CSRF exception.
Events are dispatched before the HTTP acknowledgement. Keep synchronous listeners lightweight, and configure an asynchronous queue driver for queued listeners/jobs. Webhook deliveries may repeat, so use an atomic state transition or database uniqueness constraint keyed by the transaction reference before fulfillment.

### Listening to Typed Events

Register listeners in `AppServiceProvider::boot()` (or via Laravel 13's `#[ListensTo]` / auto-discovered listeners):

```php
use Edydeyemi\Paystack\Events\ChargeSucceeded;
use Edydeyemi\Paystack\Events\PreauthorizationCaptureSucceeded;
use Edydeyemi\Paystack\Events\TransferFailed;
use Illuminate\Support\Facades\Event;

Event::listen(ChargeSucceeded::class, function (ChargeSucceeded $event): void {
    $reference = (string) $event->data('reference');
    $amount = (int) $event->data('amount');

    // Dispatch a queued job to fulfill the order idempotently
    FulfillOrderJob::dispatch($reference, $amount);
});

Event::listen(PreauthorizationCaptureSucceeded::class, function (PreauthorizationCaptureSucceeded $event): void {
    // Mark booking as settled using $event->data('reference')
});

Event::listen(TransferFailed::class, function (TransferFailed $event): void {
    // Alert ops or reverse payout status
});
```

### Catch-All Audit Listener

`PaystackWebhookReceived` fires for **every** verified webhook (including custom or unmapped events):

```php
use Edydeyemi\Paystack\Events\PaystackWebhookReceived;

Event::listen(PaystackWebhookReceived::class, function (PaystackWebhookReceived $event): void {
    logger()->info('Paystack webhook received', [
        'event' => $event->event,
        'reference' => $event->data('reference'),
    ]);
});
```

### Available Typed Event Classes

| Paystack Event | Laravel Event Class |
|---|---|
| `charge.success` | `ChargeSucceeded` |
| `preauthorization.capture.success` | `PreauthorizationCaptureSucceeded` |
| `preauthorization.capture.failed` | `PreauthorizationCaptureFailed` |
| `preauthorization.release.success` | `PreauthorizationReleaseSucceeded` |
| `transfer.success` | `TransferSucceeded` |
| `transfer.failed` | `TransferFailed` |
| `transfer.reversed` | `TransferReversed` |
| `subscription.create` | `SubscriptionCreated` |
| `subscription.disable` | `SubscriptionDisabled` |
| `subscription.not_renew` | `SubscriptionNotRenewing` |
| `invoice.create` | `InvoiceCreated` |
| `invoice.payment_failed` | `InvoicePaymentFailed` |
| `refund.processed` | `RefundProcessed` |
| `customeridentification.success` | `CustomerIdentificationSucceeded` |
| `customeridentification.failed` | `CustomerIdentificationFailed` |
| `dedicatedaccount.assign.success` | `DedicatedAccountAssigned` |

---

## 12. Testing Your Integration

### Faking Paystack API Calls

Because `PaystackClient` uses Laravel's HTTP Client factory, `Http::fake()` works out of the box in Pest/PHPUnit:

```php
use Edydeyemi\Paystack\Facades\Paystack;
use Illuminate\Support\Facades\Http;

Http::fake([
    'https://api.paystack.co/transaction/verify/*' => Http::response([
        'status' => true,
        'message' => 'Verification successful',
        'data' => ['status' => 'success', 'amount' => 500000],
    ]),
]);

$res = Paystack::transactions()->verify('ord_123');
expect($res->data('status'))->toBe('success');
```

### Testing Webhooks with Valid Signatures

Use `WebhookSignature::compute()` in your feature tests to sign payloads exactly as Paystack does:

```php
use Edydeyemi\Paystack\Events\ChargeSucceeded;
use Edydeyemi\Paystack\Webhooks\WebhookSignature;
use Illuminate\Support\Facades\Event;

Event::fake();

$payload = ['event' => 'charge.success', 'data' => ['reference' => 'ord_123', 'amount' => 500000]];
$signature = WebhookSignature::compute(json_encode($payload, JSON_THROW_ON_ERROR), config('paystack.secret_key'));

$this->postJson(route('paystack.webhook'), $payload, [
    'x-paystack-signature' => $signature,
])->assertOk();

Event::assertDispatched(ChargeSucceeded::class);
```
