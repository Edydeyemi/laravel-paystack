# Installation & Configuration

## Requirements

- **PHP**: `8.3` or higher
- **Laravel**: `13.0` or higher

## 1. Install via Composer

```bash
composer require edydeyemi/laravel-paystack
```

Laravel's package auto-discovery automatically registers `Edydeyemi\Paystack\PaystackServiceProvider` and the `Paystack` facade alias.

## 2. Publish the Configuration File

```bash
php artisan vendor:publish --tag="paystack-config"
```

This creates `config/paystack.php` in your application.

## 3. Environment Variables

Add your Paystack keys from the Paystack Dashboard to your `.env` file:

```env
PAYSTACK_SECRET_KEY=sk_test_your_secret_key
PAYSTACK_PUBLIC_KEY=pk_test_your_public_key
PAYSTACK_BASE_URL=https://api.paystack.co
PAYSTACK_ALLOW_CUSTOM_BASE_URL=false
PAYSTACK_CURRENCY=NGN

# HTTP Client Tuning
PAYSTACK_TIMEOUT=30
PAYSTACK_CONNECT_TIMEOUT=10
# Retries safe GET requests only; write requests are not retried automatically.
PAYSTACK_RETRY_TIMES=2
PAYSTACK_RETRY_SLEEP_MS=200

# Webhooks
PAYSTACK_WEBHOOK_ENABLED=true
PAYSTACK_WEBHOOK_PATH=paystack/webhook
PAYSTACK_WEBHOOK_ROUTE_NAME=paystack.webhook
PAYSTACK_WEBHOOK_ALLOWED_IPS=52.31.139.75,52.49.173.169,52.214.14.220
```

The client sends your secret key as a bearer token. Keep `PAYSTACK_BASE_URL` in trusted deployment configuration. Custom hosts must use HTTPS and require `PAYSTACK_ALLOW_CUSTOM_BASE_URL=true`; do not derive the URL from request data.

## 4. Exclude the Webhook Route from CSRF (Laravel 13)

In `bootstrap/app.php`, exempt your webhook path from CSRF token validation:

```php
<?php

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

## 5. Dependency Injection vs. Facade

You can use either the `Paystack` facade or inject `Edydeyemi\Paystack\Paystack` into controllers, jobs, or services:

```php
use Edydeyemi\Paystack\Facades\Paystack;
use Edydeyemi\Paystack\Paystack as PaystackManager;

// Via Facade
$banks = Paystack::miscellaneous()->banks(['country' => 'nigeria']);

// Via Dependency Injection
class BankDirectoryService
{
    public function __construct(
        private readonly PaystackManager $paystack,
    ) {}

    public function listNigerianBanks(): array
    {
        return $this->paystack->miscellaneous()
            ->banks(['country' => 'nigeria'])
            ->data();
    }
}
```

## 6. Working with Monetary Amounts (`Amount` Helper)

Paystack expects all monetary amounts in the **lowest denomination (minor units)** — e.g. kobo for NGN, pesewas for GHS, cents for ZAR/USD.

Use `Edydeyemi\Paystack\Support\Amount` to convert explicitly and safely:

For exact decimal input, prefer a string such as `'1500.50'`. Float inputs are accepted, but a float may already have lost decimal precision before conversion. Precision must be between 0 and 18; values outside the PHP integer range are rejected.

```php
use Edydeyemi\Paystack\Support\Amount;

// Major -> Minor (integer)
$kobo = Amount::toMinor('1500.50');    // 150050
$kobo = Amount::toMinor('2500');       // 250000

// Minor -> Major (float)
$naira = Amount::toMajor(150050);      // 1500.5

// Human-readable formatting
echo Amount::format(150050);           // "NGN 1,500.50"
echo Amount::format(150050, 'GHS');    // "GHS 1,500.50"
```

## 7. Working with `PaystackResponse` & Exceptions

Every API call returns an immutable `Edydeyemi\Paystack\Http\PaystackResponse`:

```php
$response = Paystack::transactions()->verify('ord_01J...');

$response->ok();                       // true if HTTP 2xx and status === true
$response->message();                  // "Verification successful"
$response->data('amount');             // 500000 (supports dot notation)
$response->data('customer.email');     // "ada@example.com"
$response->meta('total');              // pagination metadata
$response->toArray();                  // full envelope array
```

Failed HTTP responses (4xx/5xx), connection failures, or `status: false` payloads throw `Edydeyemi\Paystack\Exceptions\PaystackRequestException`:

```php
use Edydeyemi\Paystack\Exceptions\PaystackRequestException;

try {
    $response = Paystack::transactions()->verify($reference);
} catch (PaystackRequestException $e) {
    logger()->error('Paystack error', [
        'message' => $e->getMessage(),
        'http_status' => $e->statusCode,
        'errors' => $e->errors(),
    ]);
}
```
