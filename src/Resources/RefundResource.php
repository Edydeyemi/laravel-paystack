<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Resources;

use Edydeyemi\Paystack\Http\PaystackResponse;

/**
 * Paystack Refunds API resource.
 *
 * @see https://paystack.com/docs/api/refund/
 */
class RefundResource extends Resource
{
    /**
     * Initiate a full or partial refund on a successful transaction.
     *
     * @param  array<string, mixed>  $payload  Required: `transaction` (reference or ID).
     *                                         Optional: `amount` (minor units for partial refund),
     *                                         `currency`, `customer_note`, `merchant_note`.
     */
    public function create(array $payload): PaystackResponse
    {
        return $this->client->post('refund', $payload);
    }

    /**
     * List refunds available on your integration.
     *
     * @param  array<string, mixed>  $query  Optional filters: `transaction`, `currency`, `from`, `to`, `perPage`, `page`.
     */
    public function list(array $query = []): PaystackResponse
    {
        return $this->client->get('refund', $query);
    }

    /**
     * Fetch details of a single refund by its ID.
     *
     * @param  int|string  $id  Paystack refund ID.
     */
    public function fetch(int|string $id): PaystackResponse
    {
        return $this->client->get("refund/{$id}");
    }

    /**
     * Retry a failed refund by providing the customer's bank account details.
     *
     * @param  int|string  $id  Paystack refund ID.
     * @param  array<string, mixed>  $payload  Required: `refund_account_details` (`currency`, `account_number`, `bank_id`).
     */
    public function retry(int|string $id, array $payload): PaystackResponse
    {
        return $this->client->post("refund/retry_with_customer_details/{$id}", $payload);
    }
}
