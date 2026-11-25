<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Resources;

use Edydeyemi\Paystack\Http\PaystackResponse;

/**
 * Paystack Subaccounts API resource for marketplace and partner settlements.
 *
 * @see https://paystack.com/docs/api/subaccount/
 */
class SubaccountResource extends Resource
{
    /**
     * Create a subaccount on your integration.
     *
     * @param  array<string, mixed>  $payload  Required: `business_name`, `settlement_bank` (bank code),
     *                                         `account_number`, `percentage_charge` (float).
     *                                         Optional: `description`, `primary_contact_email`, `metadata`.
     */
    public function create(array $payload): PaystackResponse
    {
        return $this->client->post('subaccount', $payload);
    }

    /**
     * List subaccounts available on your integration.
     *
     * @param  array<string, mixed>  $query  Optional filters: `perPage`, `page`, `from`, `to`.
     */
    public function list(array $query = []): PaystackResponse
    {
        return $this->client->get('subaccount', $query);
    }

    /**
     * Get details of a subaccount by ID or subaccount code (`ACCT_...`).
     *
     * @param  int|string  $idOrCode  Subaccount ID or code.
     */
    public function fetch(int|string $idOrCode): PaystackResponse
    {
        return $this->client->get('subaccount/'.rawurlencode((string) $idOrCode));
    }

    /**
     * Update a subaccount's settlement or contact details.
     *
     * @param  int|string  $idOrCode  Subaccount ID or code (`ACCT_...`).
     * @param  array<string, mixed>  $payload  Fields to update: `business_name`, `settlement_bank`,
     *                                         `account_number`, `active`, `percentage_charge`, etc.
     */
    public function update(int|string $idOrCode, array $payload): PaystackResponse
    {
        return $this->client->put('subaccount/'.rawurlencode((string) $idOrCode), $payload);
    }
}
