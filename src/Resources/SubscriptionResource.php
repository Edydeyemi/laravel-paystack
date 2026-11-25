<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Resources;

use Edydeyemi\Paystack\Http\PaystackResponse;

/**
 * Paystack Subscriptions API resource.
 *
 * @see https://paystack.com/docs/api/subscription/
 */
class SubscriptionResource extends Resource
{
    /**
     * Subscribe a customer to a recurring plan.
     *
     * @param  array<string, mixed>  $payload  Required: `customer` (email or code), `plan` (plan code).
     *                                         Optional: `authorization`, `start_date` (ISO 8601).
     */
    public function create(array $payload): PaystackResponse
    {
        return $this->client->post('subscription', $payload);
    }

    /**
     * List subscriptions available on your integration.
     *
     * @param  array<string, mixed>  $query  Optional filters: `perPage`, `page`, `customer`, `plan`.
     */
    public function list(array $query = []): PaystackResponse
    {
        return $this->client->get('subscription', $query);
    }

    /**
     * Get details of a subscription by ID or subscription code (`SUB_...`).
     *
     * @param  int|string  $idOrCode  Subscription ID or code.
     */
    public function fetch(int|string $idOrCode): PaystackResponse
    {
        return $this->client->get('subscription/'.rawurlencode((string) $idOrCode));
    }

    /**
     * Enable a previously disabled subscription.
     *
     * @param  string  $code  Subscription code (`SUB_...`).
     * @param  string  $token  Email token associated with the subscription.
     */
    public function enable(string $code, string $token): PaystackResponse
    {
        return $this->client->post('subscription/enable', [
            'code' => $code,
            'token' => $token,
        ]);
    }

    /**
     * Disable (cancel) an active subscription.
     *
     * @param  string  $code  Subscription code (`SUB_...`).
     * @param  string  $token  Email token associated with the subscription.
     */
    public function disable(string $code, string $token): PaystackResponse
    {
        return $this->client->post('subscription/disable', [
            'code' => $code,
            'token' => $token,
        ]);
    }

    /**
     * Generate a hosted link for a customer to update the card on their subscription.
     *
     * @param  string  $code  Subscription code (`SUB_...`).
     */
    public function generateUpdateLink(string $code): PaystackResponse
    {
        return $this->client->get('subscription/'.rawurlencode($code).'/manage/link');
    }

    /**
     * Email a card-update management link directly to the subscribed customer.
     *
     * @param  string  $code  Subscription code (`SUB_...`).
     */
    public function sendUpdateLink(string $code): PaystackResponse
    {
        return $this->client->post('subscription/'.rawurlencode($code).'/manage/email');
    }
}
