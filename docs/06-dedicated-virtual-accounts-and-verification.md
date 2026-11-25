# Dedicated Virtual Accounts (DVA), Verification & Miscellaneous

Covers `Paystack::dedicatedAccounts()`, `Paystack::verification()`, and `Paystack::miscellaneous()`.

## 1. Dedicated Virtual Accounts (DVA)

Dedicated Virtual Accounts let your users fund their wallets via direct bank transfer to a permanent NUBAN account number assigned to them.

### Discover Available Bank Providers

```php
use Edydeyemi\Paystack\Facades\Paystack;

$providers = Paystack::dedicatedAccounts()->providers();
// e.g. wema-bank, titan-paystack
```

### Single-Step Customer Creation, Validation & DVA Assignment

```php
$assignment = Paystack::dedicatedAccounts()->assign([
    'email' => 'ada@example.com',
    'first_name' => 'Ada',
    'last_name' => 'Lovelace',
    'phone' => '+2348012345678',
    'preferred_bank' => 'wema-bank',
    'country' => 'NG',
    // Optional KYC fields when required:
    // 'account_number' => '0123456789',
    // 'bvn' => '20012345678',
    // 'bank_code' => '058',
]);
```

> Paystack processes `assign()` asynchronously and sends `dedicatedaccount.assign.success` (or `dedicatedaccount.assign.failed`) to your webhook. See [Webhooks & Events](07-webhooks-and-events.md).

### Create a DVA for an Already-Validated Customer

```php
$dva = Paystack::dedicatedAccounts()->create([
    'customer' => 'CUS_xnxdt6s1zg1f4nx',
    'preferred_bank' => 'titan-paystack',
]);

$accountNumber = $dva->data('account_number');
$bankName = $dva->data('bank.name');
```

### List, Fetch, Requery, Split & Deactivate

```php
// List & fetch
$accounts = Paystack::dedicatedAccounts()->list(['active' => true, 'currency' => 'NGN']);
$account = Paystack::dedicatedAccounts()->fetch(59);

// Requery for missed bank transfer notifications
Paystack::dedicatedAccounts()->requery([
    'account_number' => '9988776655',
    'provider_slug' => 'wema-bank',
    'date' => now()->toDateString(),
]);

// Split incoming DVA deposits with a subaccount or split code
Paystack::dedicatedAccounts()->addSplit([
    'customer' => 'CUS_xnxdt6s1zg1f4nx',
    'subaccount' => 'ACCT_vendor_a',
]);

// Remove split from a DVA
Paystack::dedicatedAccounts()->removeSplit('9988776655');

// Deactivate a DVA
Paystack::dedicatedAccounts()->deactivate(59);
```

## 2. Verification API

### Resolve a Bank Account Number

Use this in a FormRequest or Livewire/API endpoint to display the account holder's name before saving bank details:

```php
$resolved = Paystack::verification()->resolveAccount(
    accountNumber: '0123456789',
    bankCode: '058',
);

$accountName = $resolved->data('account_name'); // "ADA LOVELACE"
```

### Validate a Bank Account (KYC)

```php
$validation = Paystack::verification()->validateAccount([
    'account_name' => 'Ada Lovelace',
    'account_number' => '0123456789',
    'account_type' => 'personal', // 'personal' or 'business'
    'bank_code' => '058',
    'country_code' => 'NG',
    'document_type' => 'identityNumber',
    'document_number' => '12345678901',
]);
```

### Resolve Card BIN (First 6 Digits)

```php
$bin = Paystack::verification()->resolveCardBin('539983');

$brand = $bin->data('brand'); // "Mastercard"
$bank = $bin->data('bank');   // "Guaranty Trust Bank"
```

## 3. Miscellaneous API (Banks, Countries & States)

```php
// Cache bank list for 24 hours in Laravel
$banks = cache()->remember('paystack.banks.ng', now()->addDay(), function (): array {
    return Paystack::miscellaneous()
        ->banks(['country' => 'nigeria', 'perPage' => 100])
        ->data();
});

// Supported countries
$countries = Paystack::miscellaneous()->countries()->data();

// States for address verification
$states = Paystack::miscellaneous()->states('NG')->data();
```
