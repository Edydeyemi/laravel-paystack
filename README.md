# Laravel Paystack (`edydeyemi/laravel-paystack`)

A modern, fluent **PHP 8.3+ / Laravel 13** package for integrating Paystack payments, refunds, customers, plans, subscriptions, transfers, subaccounts, splits, dedicated virtual accounts (DVA), verification, and webhooks.

## Features

- **Fluent Resource API**: `Paystack::transactions()`, `customers()`, `plans()`, `subscriptions()`, `transfers()`, `transferRecipients()`, `subaccounts()`, `splits()`, `dedicatedAccounts()`, `refunds()`, `verification()`, and `miscellaneous()`.
- **Laravel HTTP Client Native**: Built on `Illuminate\Http\Client\Factory`, so `Http::fake()` works seamlessly in tests.
- **Immutable Response Wrapper**: `PaystackResponse` with `ok()`, `status()`, `message()`, dot-notated `data()` and `meta()`, `ArrayAccess`, and `JsonSerializable`.
- **Verified Webhooks & Typed Events**: Auto-registered webhook route with HMAC-SHA512 signature verification, optional IP allowlist, `PaystackWebhookReceived`, and 13 typed Paystack events.
- **Explicit Amount Helpers**: `Amount::toMinor()`, `Amount::toMajor()`, and `Amount::format()` to prevent unit-conversion bugs.
- **Strict Typing**: PHPStan/Larastan Level 8 clean, Laravel Pint formatted, and Pest tested.

## Quick Start

```bash
composer require edydeyemi/laravel-paystack
php artisan vendor:publish --tag="paystack-config"
```

Add your keys to `.env`:

```env
PAYSTACK_SECRET_KEY=sk_test_...
PAYSTACK_PUBLIC_KEY=pk_test_...
PAYSTACK_CURRENCY=NGN
```

Initialize a payment:

```php
use Edydeyemi\Paystack\Facades\Paystack;
use Edydeyemi\Paystack\Support\Amount;

$response = Paystack::transactions()->initialize([
    'email' => 'customer@example.com',
    'amount' => Amount::toMinor(5000), // 500000 kobo
    'reference' => Paystack::generateReference('ord_'),
    'callback_url' => route('payment.callback'),
]);

return redirect()->away((string) $response->data('authorization_url'));
```

## Documentation

Detailed guides and realistic Laravel 13 examples for every feature:

1. [Installation, Configuration & Core Concepts](docs/01-installation-and-configuration.md)
2. [Transactions & Refunds](docs/02-transactions-and-refunds.md)
3. [Customers, Plans & Subscriptions](docs/03-customers-plans-and-subscriptions.md)
4. [Transfers & Transfer Recipients](docs/04-transfers-and-recipients.md)
5. [Subaccounts & Transaction Splits](docs/05-subaccounts-and-splits.md)
6. [Dedicated Virtual Accounts (DVA), Verification & Miscellaneous](docs/06-dedicated-virtual-accounts-and-verification.md)
7. [Webhooks & Laravel Events](docs/07-webhooks-and-events.md)

## Testing & Quality

```bash
composer test
composer analyse
composer format
```
