# Subaccounts & Transaction Splits

Covers `Paystack::subaccounts()` and `Paystack::splits()` for marketplaces, multi-vendor checkouts, and partner revenue sharing.

## 1. Subaccounts (Single Vendor + Platform Split)

### Create, List, Fetch & Update Subaccounts

```php
use Edydeyemi\Paystack\Facades\Paystack;

$subaccount = Paystack::subaccounts()->create([
    'business_name' => 'Lagos Coffee Roasters',
    'settlement_bank' => '058', // GTBank
    'account_number' => '0123456789',
    'percentage_charge' => 15.0, // Platform keeps 15%, vendor gets 85%
    'description' => 'Marketplace vendor settlement account',
    'primary_contact_email' => 'owner@lagoscoffee.ng',
]);

$subaccountCode = $subaccount->data('subaccount_code'); // ACCT_...

// List, fetch, and update
$subaccounts = Paystack::subaccounts()->list(['perPage' => 50]);
$details = Paystack::subaccounts()->fetch($subaccountCode);

Paystack::subaccounts()->update($subaccountCode, [
    'percentage_charge' => 12.5,
    'active' => true,
]);
```

### Initialize a Transaction with a Subaccount

```php
use Edydeyemi\Paystack\Support\Amount;

$checkout = Paystack::transactions()->initialize([
    'email' => 'buyer@example.com',
    'amount' => Amount::toMinor(10000),
    'subaccount' => $subaccountCode,
    'bearer' => 'subaccount', // 'account' (default) or 'subaccount' bears Paystack fees
    // Optional flat platform fee override in minor units:
    // 'transaction_charge' => Amount::toMinor(1000),
]);
```

## 2. Transaction Splits (Multi-Party Splits)

When a single payment needs to be split across multiple vendors or partners, create a split group:

```php
$split = Paystack::splits()->create([
    'name' => 'Marketplace Multi-Vendor Split',
    'type' => 'percentage', // or 'flat'
    'currency' => 'NGN',
    'subaccounts' => [
        [
            'subaccount' => 'ACCT_vendor_a',
            'share' => 60, // 60%
        ],
        [
            'subaccount' => 'ACCT_logistics',
            'share' => 20, // 20% (remaining 20% stays in main account)
        ],
    ],
    'bearer_type' => 'all-proportional', // 'subaccount', 'account', 'all-proportional', 'all'
]);

$splitId = $split->data('id');
$splitCode = $split->data('split_code'); // SPL_...
```

### Use the Split Code at Checkout

```php
$checkout = Paystack::transactions()->initialize([
    'email' => 'buyer@example.com',
    'amount' => Amount::toMinor(25000),
    'split_code' => $splitCode,
]);
```

### Manage Subaccounts Inside an Existing Split

```php
// List, fetch, and update split metadata
$splits = Paystack::splits()->list(['active' => true]);
$fetched = Paystack::splits()->fetch($splitId);

Paystack::splits()->update($splitId, [
    'name' => 'Updated Multi-Vendor Split',
    'active' => true,
]);

// Add a new subaccount or update an existing subaccount's share
Paystack::splits()->addOrUpdateSubaccount($splitId, 'ACCT_vendor_b', 10);

// Remove a subaccount from the split
Paystack::splits()->removeSubaccount($splitId, 'ACCT_vendor_b');
```
