<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Resources;

use Edydeyemi\Paystack\Http\PaystackResponse;

/**
 * Paystack Transfer Recipients API resource.
 *
 * @see https://paystack.com/docs/api/transfer-recipient/
 */
class TransferRecipientResource extends Resource
{
    /**
     * Create a new transfer recipient (bank account, mobile money, or authorization).
     *
     * @param  array<string, mixed>  $payload  Required: `type` (e.g. "nuban", "mobile_money", "basa"), `name`,
     *                                         `account_number`, `bank_code`. Optional: `currency`, `description`, `metadata`.
     */
    public function create(array $payload): PaystackResponse
    {
        return $this->client->post('transferrecipient', $payload);
    }

    /**
     * Create multiple transfer recipients in a single batch.
     *
     * @param  array<int, array<string, mixed>>  $batch  List of recipient payloads matching {@see create()}.
     */
    public function bulkCreate(array $batch): PaystackResponse
    {
        return $this->client->post('transferrecipient/bulk', ['batch' => $batch]);
    }

    /**
     * List transfer recipients available on your integration.
     *
     * @param  array<string, mixed>  $query  Optional filters: `perPage`, `page`, `from`, `to`.
     */
    public function list(array $query = []): PaystackResponse
    {
        return $this->client->get('transferrecipient', $query);
    }

    /**
     * Get details of a transfer recipient by ID or recipient code (`RCP_...`).
     *
     * @param  int|string  $idOrCode  Recipient ID or recipient code.
     */
    public function fetch(int|string $idOrCode): PaystackResponse
    {
        return $this->client->get('transferrecipient/'.rawurlencode((string) $idOrCode));
    }

    /**
     * Update a transfer recipient's name or email.
     *
     * @param  int|string  $idOrCode  Recipient ID or recipient code (`RCP_...`).
     * @param  array<string, mixed>  $payload  Fields to update: `name`, `email`.
     */
    public function update(int|string $idOrCode, array $payload): PaystackResponse
    {
        return $this->client->put('transferrecipient/'.rawurlencode((string) $idOrCode), $payload);
    }

    /**
     * Delete (deactivate) a transfer recipient.
     *
     * @param  int|string  $idOrCode  Recipient ID or recipient code (`RCP_...`).
     */
    public function delete(int|string $idOrCode): PaystackResponse
    {
        return $this->client->delete('transferrecipient/'.rawurlencode((string) $idOrCode));
    }
}
