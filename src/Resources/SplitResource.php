<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Resources;

use Edydeyemi\Paystack\Http\PaystackResponse;

/**
 * Paystack Transaction Splits API resource for multi-party payment splitting.
 *
 * @see https://paystack.com/docs/api/split/
 */
class SplitResource extends Resource
{
    /**
     * Create a transaction split configuration on your integration.
     *
     * @param  array<string, mixed>  $payload  Required: `name`, `type` ("percentage" or "flat"), `currency`,
     *                                         `subaccounts` (list of `['subaccount' => ..., 'share' => ...]`).
     *                                         Optional: `bearer_type`, `bearer_subaccount`.
     */
    public function create(array $payload): PaystackResponse
    {
        return $this->client->post('split', $payload);
    }

    /**
     * List transaction splits available on your integration.
     *
     * @param  array<string, mixed>  $query  Optional filters: `name`, `active`, `sort_by`, `perPage`, `page`.
     */
    public function list(array $query = []): PaystackResponse
    {
        return $this->client->get('split', $query);
    }

    /**
     * Get details of a transaction split by ID.
     *
     * @param  int|string  $id  Paystack split ID.
     */
    public function fetch(int|string $id): PaystackResponse
    {
        return $this->client->get("split/{$id}");
    }

    /**
     * Update a transaction split's name, active state, or bearer settings.
     *
     * @param  int|string  $id  Paystack split ID.
     * @param  array<string, mixed>  $payload  Fields to update: `name`, `active`, `bearer_type`, `bearer_subaccount`.
     */
    public function update(int|string $id, array $payload): PaystackResponse
    {
        return $this->client->put("split/{$id}", $payload);
    }

    /**
     * Add a subaccount to a split or update its share if already present.
     *
     * @param  int|string  $id  Paystack split ID.
     * @param  string  $subaccount  Subaccount code (`ACCT_...`).
     * @param  int|float  $share  Percentage share or flat amount in minor units depending on split type.
     */
    public function addOrUpdateSubaccount(int|string $id, string $subaccount, int|float $share): PaystackResponse
    {
        return $this->client->post("split/{$id}/subaccount/add", [
            'subaccount' => $subaccount,
            'share' => $share,
        ]);
    }

    /**
     * Remove a subaccount from a transaction split.
     *
     * @param  int|string  $id  Paystack split ID.
     * @param  string  $subaccount  Subaccount code (`ACCT_...`) to remove.
     */
    public function removeSubaccount(int|string $id, string $subaccount): PaystackResponse
    {
        return $this->client->post("split/{$id}/subaccount/remove", [
            'subaccount' => $subaccount,
        ]);
    }
}
