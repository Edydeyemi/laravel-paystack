# Transactions & Refunds

Covers `Paystack::transactions()` and `Paystack::refunds()`.

## 1. Standard Checkout Flow (Initialize + Callback Verification)

A typical Laravel 13 checkout controller initializes a transaction, saves the reference on the order, redirects the customer to Paystack's hosted checkout, and verifies the transaction on callback.

```php
<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Edydeyemi\Paystack\Exceptions\PaystackRequestException;
use Edydeyemi\Paystack\Facades\Paystack;
use Edydeyemi\Paystack\Support\Amount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $order = Order::create([
            'user_id' => $request->user()->id,
            'total_kobo' => Amount::toMinor('12500.00'), // NGN 12,500.00
            'reference' => Paystack::generateReference('ord_'),
            'status' => 'pending',
        ]);

        $response = Paystack::transactions()->initialize([
            'email' => $request->user()->email,
            'amount' => $order->total_kobo,
            'reference' => $order->reference,
            'currency' => 'NGN',
            'callback_url' => route('checkout.callback'),
            'channels' => ['card', 'bank_transfer', 'ussd'],
            'metadata' => [
                'order_id' => $order->id,
                'custom_fields' => [
                    [
                        'display_name' => 'Order Number',
                        'variable_name' => 'order_id',
                        'value' => (string) $order->id,
                    ],
                ],
            ],
        ]);

        return redirect()->away((string) $response->data('authorization_url'));
    }

    public function callback(Request $request): RedirectResponse
    {
        $reference = (string) $request->query('reference');

        try {
            $verification = Paystack::transactions()->verify($reference);
        } catch (PaystackRequestException $e) {
            return redirect()->route('orders.index')
                ->withErrors(['payment' => $e->getMessage()]);
        }

        $order = Order::where('reference', $reference)->firstOrFail();

        if (
            $verification->data('status') === 'success'
            && (int) $verification->data('amount') === $order->total_kobo
        ) {
            $order->update([
                'status' => 'paid',
                'authorization_code' => $verification->data('authorization.authorization_code'),
            ]);

            return redirect()->route('orders.show', $order)
                ->with('status', 'Payment confirmed!');
        }

        return redirect()->route('orders.show', $order)
            ->withErrors(['payment' => 'Payment was not successful.']);
    }
}
```

> **Tip:** Always combine callback verification with the `charge.success` webhook ([Webhooks & Events](07-webhooks-and-events.md)) so orders are still fulfilled if the user closes their browser before the redirect completes.

## 2. Charging a Reusable Authorization (Saved Card)

When a customer completes a card payment, Paystack returns `data.authorization.authorization_code` (when `reusable === true`). You can charge it later without user interaction:

```php
$charge = Paystack::transactions()->chargeAuthorization([
    'authorization_code' => $user->paystack_auth_code,
    'email' => $user->email,
    'amount' => Amount::toMinor(3500),
    'reference' => Paystack::generateReference('ren_'),
    'metadata' => ['reason' => 'wallet_auto_topup'],
]);

if ($charge->data('status') === 'success') {
    // Credit user wallet
}
```

## 3. Partial Debit

Collect a partial amount when the full amount cannot be debited at once:

```php
$partial = Paystack::transactions()->partialDebit([
    'authorization_code' => 'AUTH_72btv547',
    'currency' => 'NGN',
    'amount' => Amount::toMinor(2000),
    'email' => 'customer@example.com',
    'at_least' => Amount::toMinor(500),
]);
```

## 4. Listing, Fetching, Timelines, Totals & Export

```php
// Paginated transaction list
$list = Paystack::transactions()->list([
    'perPage' => 25,
    'page' => 1,
    'status' => 'success',
    'from' => now()->startOfMonth()->toIso8601String(),
    'to' => now()->toIso8601String(),
]);

foreach ($list->data() as $tx) {
    // $tx['reference'], $tx['amount'], $tx['status']
}

// Fetch single transaction by ID
$tx = Paystack::transactions()->fetch(4099260516);

// View transaction timeline by ID or reference
$timeline = Paystack::transactions()->timeline('ord_01J...');

// Account totals
$totals = Paystack::transactions()->totals([
    'from' => now()->subDays(30)->toDateString(),
]);

// Generate CSV export link
$export = Paystack::transactions()->export([
    'status' => 'success',
    'settled' => true,
]);
$csvUrl = $export->data('path');
```

## 5. Refunds

### Full or Partial Refund

```php
// Full refund
$refund = Paystack::refunds()->create([
    'transaction' => 'ord_01J...',
    'customer_note' => 'Refund for cancelled order #1042',
    'merchant_note' => 'Customer requested cancellation before dispatch',
]);

// Partial refund (NGN 2,000 of a larger payment)
$partialRefund = Paystack::refunds()->create([
    'transaction' => 'ord_01J...',
    'amount' => Amount::toMinor(2000),
]);
```

### List, Fetch & Retry Failed Refunds

```php
$refunds = Paystack::refunds()->list(['perPage' => 20]);
$single = Paystack::refunds()->fetch(123456);

// Retry a failed refund with customer bank details
$retried = Paystack::refunds()->retry(123456, [
    'refund_account_details' => [
        'currency' => 'NGN',
        'account_number' => '0123456789',
        'bank_id' => '9',
    ],
]);
```

## 6. Card Preauthorizations (Hold, Capture & Release)

For South African (`ZAR`) integrations or workflows requiring a temporary card hold (e.g. hotel deposits, car rentals):

```php
// 1. Place a temporary hold
$hold = Paystack::preauthorizations()->initialize([
    'email' => 'guest@example.com',
    'amount' => Amount::toMinor(2500), // ZAR 2,500.00
    'currency' => 'ZAR',
    'reference' => Paystack::generateReference('preauth_'),
    'expire_after_days' => 5,
    'expire_action' => 'release',
]);

// 2. Capture the final amount (<= held amount)
Paystack::preauthorizations()->capture([
    'reference' => 'preauth_01J...',
    'amount' => Amount::toMinor(1850),
    'currency' => 'ZAR',
]);

// 3. Or release the hold without charging
Paystack::preauthorizations()->release('preauth_01J...');
```
