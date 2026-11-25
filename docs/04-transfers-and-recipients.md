# Transfers & Transfer Recipients

Covers `Paystack::transferRecipients()` and `Paystack::transfers()`.

## 1. End-to-End Payout Workflow

Before sending money, resolve the bank account (optional but recommended), create a transfer recipient, and initiate the transfer.

```php
<?php

namespace App\Jobs;

use App\Models\Payout;
use Edydeyemi\Paystack\Facades\Paystack;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessVendorPayout implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Payout $payout,
    ) {}

    public function handle(): void
    {
        $vendor = $this->payout->vendor;

        // 1. Create a transfer recipient if the vendor doesn't have one yet
        if (! $vendor->paystack_recipient_code) {
            $resolved = Paystack::verification()->resolveAccount(
                $vendor->account_number,
                $vendor->bank_code,
            );

            $recipient = Paystack::transferRecipients()->create([
                'type' => 'nuban',
                'name' => $resolved->data('account_name'),
                'account_number' => $vendor->account_number,
                'bank_code' => $vendor->bank_code,
                'currency' => 'NGN',
            ]);

            $vendor->update([
                'paystack_recipient_code' => $recipient->data('recipient_code'),
            ]);
        }

        // 2. Initiate the transfer with an idempotent reference
        $reference = $this->payout->reference ?? Paystack::generateReference('trf_');
        $this->payout->update(['reference' => $reference]);

        $transfer = Paystack::transfers()->initiate([
            'source' => 'balance',
            'amount' => $this->payout->amount_kobo,
            'recipient' => $vendor->paystack_recipient_code,
            'reference' => $reference,
            'reason' => "Vendor payout #{$this->payout->id}",
        ]);

        $this->payout->update([
            'transfer_code' => $transfer->data('transfer_code'),
            'status' => $transfer->data('status'), // e.g. "pending", "otp", or "success"
        ]);
    }
}
```

## 2. Managing Transfer Recipients

```php
// Bulk create recipients
$batch = Paystack::transferRecipients()->bulkCreate([
    [
        'type' => 'nuban',
        'name' => 'Vendor One',
        'account_number' => '0123456789',
        'bank_code' => '058',
        'currency' => 'NGN',
    ],
    [
        'type' => 'nuban',
        'name' => 'Vendor Two',
        'account_number' => '9876543210',
        'bank_code' => '011',
        'currency' => 'NGN',
    ],
]);

// List, fetch, update, delete
$recipients = Paystack::transferRecipients()->list(['perPage' => 20]);
$recipient = Paystack::transferRecipients()->fetch('RCP_gd9vgag7n5lr5ix');

Paystack::transferRecipients()->update('RCP_gd9vgag7n5lr5ix', [
    'name' => 'Vendor One Ltd',
    'email' => 'payouts@vendorone.com',
]);

Paystack::transferRecipients()->delete('RCP_gd9vgag7n5lr5ix');
```

## 3. Finalizing OTP Transfers, Bulk Transfers & Verification

```php
// If your Paystack account requires OTP for transfers:
$finalized = Paystack::transfers()->finalize(
    transferCode: 'TRF_1ptvuv321ahaa7q',
    otp: '928783',
);

// Bulk transfers
$bulk = Paystack::transfers()->bulk([
    'source' => 'balance',
    'currency' => 'NGN',
    'transfers' => [
        [
            'amount' => 50000,
            'recipient' => 'RCP_gd9vgag7n5lr5ix',
            'reference' => Paystack::generateReference('trf_'),
            'reason' => 'April commission',
        ],
        [
            'amount' => 75000,
            'recipient' => 'RCP_2xey9w205720',
            'reference' => Paystack::generateReference('trf_'),
            'reason' => 'April commission',
        ],
    ],
]);

// List, fetch, and verify transfers
$transfers = Paystack::transfers()->list(['perPage' => 25]);
$transfer = Paystack::transfers()->fetch('TRF_1ptvuv321ahaa7q');
$verified = Paystack::transfers()->verify('trf_01J...');
```
