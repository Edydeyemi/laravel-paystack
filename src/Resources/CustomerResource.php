<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Resources;

use Edydeyemi\Paystack\Http\PaystackResponse;

/**
 * Paystack Customers API resource.
 *
 * @see https://paystack.com/docs/api/customer/
 */
class CustomerResource extends Resource
{
    /**
     * Create a customer on your integration.
     *
     * @param  array<string, mixed>  $payload  Required: `email`. Optional: `first_name`, `last_name`, `phone`, `metadata`.
     */
    public function create(array $payload): PaystackResponse
    {
        return $this->client->post('customer', $payload);
    }

    /**
     * List customers available on your integration.
     *
     * @param  array<string, mixed>  $query  Optional filters: `perPage`, `page`, `from`, `to`.
     */
    public function list(array $query = []): PaystackResponse
    {
        return $this->client->get('customer', $query);
    }

    /**
     * Get details of a customer by email address or customer code (`CUS_...`).
     *
     * @param  string  $emailOrCode  Customer email or Paystack customer code.
     */
    public function fetch(string $emailOrCode): PaystackResponse
    {
        return $this->client->get('customer/'.rawurlencode($emailOrCode));
    }

    /**
     * Update a customer's details on your integration.
     *
     * @param  string  $code  Customer code (`CUS_...`).
     * @param  array<string, mixed>  $payload  Fields to update: `first_name`, `last_name`, `phone`, `metadata`.
     */
    public function update(string $code, array $payload): PaystackResponse
    {
        return $this->client->put('customer/'.rawurlencode($code), $payload);
    }

    /**
     * Validate a customer's identity (e.g. BVN / bank account verification).
     *
     * @param  string  $code  Customer code (`CUS_...`).
     * @param  array<string, mixed>  $payload  Required: `country`, `type`, `value`, `first_name`, `last_name`, etc.
     */
    public function validate(string $code, array $payload): PaystackResponse
    {
        return $this->client->post('customer/'.rawurlencode($code).'/identification', $payload);
    }

    /**
     * Whitelist or blacklist a customer on your integration.
     *
     * @param  string  $customer  Customer email or customer code (`CUS_...`).
     * @param  string  $riskAction  One of `default`, `allow`, or `deny`.
     */
    public function setRiskAction(string $customer, string $riskAction = 'default'): PaystackResponse
    {
        return $this->client->post('customer/set_risk_action', [
            'customer' => $customer,
            'risk_action' => $riskAction,
        ]);
    }
}
