<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Resources;

use Edydeyemi\Paystack\Http\PaystackResponse;

/**
 * Paystack Verification API resource for bank accounts and card BINs.
 *
 * @see https://paystack.com/docs/api/verification/
 */
class VerificationResource extends Resource
{
    /**
     * Resolve a bank account number to confirm the account name and bank details.
     *
     * @param  string  $accountNumber  Customer's bank account number.
     * @param  string  $bankCode  Paystack bank code (from {@see MiscellaneousResource::banks()}).
     */
    public function resolveAccount(string $accountNumber, string $bankCode): PaystackResponse
    {
        return $this->client->get('bank/resolve', [
            'account_number' => $accountNumber,
            'bank_code' => $bankCode,
        ]);
    }

    /**
     * Confirm the authenticity of a customer's bank account before initiating transfers.
     *
     * @param  array<string, mixed>  $payload  Required: `account_name`, `account_number`, `account_type`
     *                                         ("personal" or "business"), `bank_code`, `country_code`, `document_type`.
     *                                         Optional: `document_number`.
     */
    public function validateAccount(array $payload): PaystackResponse
    {
        return $this->client->post('bank/validate', $payload);
    }

    /**
     * Retrieve card brand, type, bank, and country metadata from the first 6 digits (BIN).
     *
     * @param  string  $bin  First 6 digits of the payment card.
     */
    public function resolveCardBin(string $bin): PaystackResponse
    {
        return $this->client->get('decision/bin/'.rawurlencode($bin));
    }
}
