<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Resources;

use Edydeyemi\Paystack\Http\PaystackResponse;

/**
 * Paystack Transactions API resource.
 *
 * Handles payment initialization, verification, reusable card authorization charges,
 * transaction timelines, totals, exports, and partial debits.
 *
 * @see https://paystack.com/docs/api/transaction/
 */
class TransactionResource extends Resource
{
    /**
     * Initialize a transaction from your backend and obtain an authorization URL.
     *
     * @param  array<string, mixed>  $payload  Required: `email` (string), `amount` (int in minor units).
     *                                         Optional: `reference`, `callback_url`, `currency`, `plan`,
     *                                         `channels`, `subaccount`, `split_code`, `metadata`.
     */
    public function initialize(array $payload): PaystackResponse
    {
        return $this->client->post('transaction/initialize', $payload);
    }

    /**
     * Confirm the status and details of a transaction by its reference.
     *
     * @param  string  $reference  Unique transaction reference.
     */
    public function verify(string $reference): PaystackResponse
    {
        // URL-encode the reference in case a caller uses slashes or special characters.
        return $this->client->get('transaction/verify/'.rawurlencode($reference));
    }

    /**
     * List transactions carried out on your integration.
     *
     * @param  array<string, mixed>  $query  Optional filters: `perPage`, `page`, `customer`, `status`,
     *                                       `from`, `to`, `amount`.
     */
    public function list(array $query = []): PaystackResponse
    {
        return $this->client->get('transaction', $query);
    }

    /**
     * Fetch full details of a single transaction by its numeric ID.
     *
     * @param  int|string  $id  Paystack transaction ID.
     */
    public function fetch(int|string $id): PaystackResponse
    {
        return $this->client->get("transaction/{$id}");
    }

    /**
     * Charge a customer's reusable card authorization code without user interaction.
     *
     * @param  array<string, mixed>  $payload  Required: `authorization_code`, `email`, `amount` (minor units).
     *                                         Optional: `reference`, `currency`, `metadata`, `queue`.
     */
    public function chargeAuthorization(array $payload): PaystackResponse
    {
        return $this->client->post('transaction/charge_authorization', $payload);
    }

    /**
     * Retrieve the event timeline of a transaction by ID or reference.
     *
     * @param  int|string  $idOrReference  Transaction ID or reference string.
     */
    public function timeline(int|string $idOrReference): PaystackResponse
    {
        return $this->client->get('transaction/timeline/'.rawurlencode((string) $idOrReference));
    }

    /**
     * Retrieve total transaction volume and amount received on your account.
     *
     * @param  array<string, mixed>  $query  Optional filters: `perPage`, `page`, `from`, `to`.
     */
    public function totals(array $query = []): PaystackResponse
    {
        return $this->client->get('transaction/totals', $query);
    }

    /**
     * Export a downloadable CSV link for transactions matching the given filters.
     *
     * @param  array<string, mixed>  $query  Optional filters: `perPage`, `page`, `from`, `to`, `status`, `settled`.
     */
    public function export(array $query = []): PaystackResponse
    {
        return $this->client->get('transaction/export', $query);
    }

    /**
     * Retrieve part of a payment from a customer using a reusable authorization.
     *
     * @param  array<string, mixed>  $payload  Required: `authorization_code`, `currency`, `amount`, `email`.
     *                                         Optional: `reference`, `at_least`.
     */
    public function partialDebit(array $payload): PaystackResponse
    {
        return $this->client->post('transaction/partial_debit', $payload);
    }
}
