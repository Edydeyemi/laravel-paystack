<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Resources;

use Edydeyemi\Paystack\Http\PaystackResponse;

/**
 * Paystack Plans API resource for recurring billing.
 *
 * @see https://paystack.com/docs/api/plan/
 */
class PlanResource extends Resource
{
    /**
     * Create a recurring billing plan on your integration.
     *
     * @param  array<string, mixed>  $payload  Required: `name`, `amount` (minor units), `interval`
     *                                         (`hourly`, `daily`, `weekly`, `monthly`, `quarterly`, `biannually`, `annually`).
     *                                         Optional: `description`, `currency`, `invoice_limit`, `send_invoices`, `send_sms`.
     */
    public function create(array $payload): PaystackResponse
    {
        return $this->client->post('plan', $payload);
    }

    /**
     * List plans available on your integration.
     *
     * @param  array<string, mixed>  $query  Optional filters: `perPage`, `page`, `status`, `interval`, `amount`.
     */
    public function list(array $query = []): PaystackResponse
    {
        return $this->client->get('plan', $query);
    }

    /**
     * Get details of a plan by ID or plan code (`PLN_...`).
     *
     * @param  int|string  $idOrCode  Plan ID or plan code.
     */
    public function fetch(int|string $idOrCode): PaystackResponse
    {
        return $this->client->get('plan/'.rawurlencode((string) $idOrCode));
    }

    /**
     * Update a plan's details on your integration.
     *
     * @param  int|string  $idOrCode  Plan ID or plan code (`PLN_...`).
     * @param  array<string, mixed>  $payload  Fields to update: `name`, `amount`, `interval`, `description`, etc.
     */
    public function update(int|string $idOrCode, array $payload): PaystackResponse
    {
        return $this->client->put('plan/'.rawurlencode((string) $idOrCode), $payload);
    }
}
