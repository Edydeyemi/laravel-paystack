<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Resources;

use Edydeyemi\Paystack\Http\PaystackResponse;

/**
 * Paystack Dedicated Virtual Accounts (DVA) API resource.
 *
 * @see https://paystack.com/docs/api/dedicated-virtual-account/
 */
class DedicatedAccountResource extends Resource
{
    /**
     * Create a dedicated virtual account for an existing, validated customer.
     *
     * @param  array<string, mixed>  $payload  Required: `customer` (ID or `CUS_...` code).
     *                                         Optional: `preferred_bank`, `subaccount`, `split_code`, `first_name`, `last_name`, `phone`.
     */
    public function create(array $payload): PaystackResponse
    {
        return $this->client->post('dedicated_account', $payload);
    }

    /**
     * Create a customer, validate identity, and assign a dedicated virtual account in one asynchronous step.
     *
     * @param  array<string, mixed>  $payload  Required: `email`, `first_name`, `last_name`, `phone`, `preferred_bank`, `country`.
     *                                         Optional: `account_number`, `bvn`, `bank_code`, `subaccount`, `split_code`.
     */
    public function assign(array $payload): PaystackResponse
    {
        return $this->client->post('dedicated_account/assign', $payload);
    }

    /**
     * List dedicated virtual accounts available on your integration.
     *
     * @param  array<string, mixed>  $query  Optional filters: `active` (bool), `currency`, `provider_slug`, `bank_id`, `customer`.
     */
    public function list(array $query = []): PaystackResponse
    {
        return $this->client->get('dedicated_account', $query);
    }

    /**
     * Get details of a dedicated virtual account by ID.
     *
     * @param  int|string  $id  Dedicated account ID.
     */
    public function fetch(int|string $id): PaystackResponse
    {
        return $this->client->get("dedicated_account/{$id}");
    }

    /**
     * Requery a dedicated virtual account for missed or pending bank transfer notifications.
     *
     * @param  array<string, mixed>  $query  Required: `account_number`, `provider_slug`. Optional: `date` (YYYY-MM-DD).
     */
    public function requery(array $query): PaystackResponse
    {
        return $this->client->get('dedicated_account/requery', $query);
    }

    /**
     * Deactivate a dedicated virtual account.
     *
     * @param  int|string  $id  Dedicated account ID.
     */
    public function deactivate(int|string $id): PaystackResponse
    {
        return $this->client->delete("dedicated_account/{$id}");
    }

    /**
     * Split payments received on a dedicated virtual account with one or more subaccounts.
     *
     * @param  array<string, mixed>  $payload  Required: `customer`, and either `subaccount` or `split_code`.
     *                                         Optional: `preferred_bank`.
     */
    public function addSplit(array $payload): PaystackResponse
    {
        return $this->client->post('dedicated_account/split', $payload);
    }

    /**
     * Remove a split configuration from a dedicated virtual account.
     *
     * @param  string  $accountNumber  10-digit NUBAN virtual account number.
     */
    public function removeSplit(string $accountNumber): PaystackResponse
    {
        return $this->client->delete('dedicated_account/split', [
            'account_number' => $accountNumber,
        ]);
    }

    /**
     * Fetch available bank providers for dedicated virtual accounts.
     */
    public function providers(): PaystackResponse
    {
        return $this->client->get('dedicated_account/available_providers');
    }
}
