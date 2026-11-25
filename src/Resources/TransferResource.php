<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Resources;

use Edydeyemi\Paystack\Http\PaystackResponse;

/**
 * Paystack Transfers API resource for payouts.
 *
 * @see https://paystack.com/docs/api/transfer/
 */
class TransferResource extends Resource
{
    /**
     * Initiate a single transfer to a registered transfer recipient.
     *
     * @param  array<string, mixed>  $payload  Required: `source` (usually "balance"), `amount` (minor units),
     *                                         `recipient` (`RCP_...`). Optional: `reason`, `currency`, `reference`.
     */
    public function initiate(array $payload): PaystackResponse
    {
        return $this->client->post('transfer', $payload);
    }

    /**
     * Finalize an initiated transfer that requires OTP confirmation.
     *
     * @param  string  $transferCode  Transfer code (`TRF_...`) returned by {@see initiate()}.
     * @param  string  $otp  One-time password sent to the business owner.
     */
    public function finalize(string $transferCode, string $otp): PaystackResponse
    {
        return $this->client->post('transfer/finalize_transfer', [
            'transfer_code' => $transferCode,
            'otp' => $otp,
        ]);
    }

    /**
     * Initiate multiple transfers in a single batch request.
     *
     * @param  array<string, mixed>  $payload  Required: `source` ("balance"), `transfers` (list of transfer items).
     *                                         Optional: `currency`.
     */
    public function bulk(array $payload): PaystackResponse
    {
        return $this->client->post('transfer/bulk', $payload);
    }

    /**
     * List transfers made on your integration.
     *
     * @param  array<string, mixed>  $query  Optional filters: `perPage`, `page`, `recipient`, `from`, `to`.
     */
    public function list(array $query = []): PaystackResponse
    {
        return $this->client->get('transfer', $query);
    }

    /**
     * Get details of a transfer by ID or transfer code (`TRF_...`).
     *
     * @param  int|string  $idOrCode  Transfer ID or transfer code.
     */
    public function fetch(int|string $idOrCode): PaystackResponse
    {
        return $this->client->get('transfer/'.rawurlencode((string) $idOrCode));
    }

    /**
     * Verify the status of a transfer by its unique reference.
     *
     * @param  string  $reference  Transfer reference supplied during initiation.
     */
    public function verify(string $reference): PaystackResponse
    {
        return $this->client->get('transfer/verify/'.rawurlencode($reference));
    }
}
