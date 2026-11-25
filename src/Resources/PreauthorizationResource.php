<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Resources;

use Edydeyemi\Paystack\Http\PaystackResponse;

/**
 * Paystack Card Preauthorization API (primarily for ZAR / South Africa).
 *
 * Allows placing a temporary hold on a customer's card and later capturing
 * the final amount or releasing the hold.
 *
 * @see https://paystack.com/docs/payments/card-preauthorization/
 */
class PreauthorizationResource extends Resource
{
    /**
     * Initialize a card preauthorization to place a temporary hold on funds.
     *
     * Expected payload keys:
     * - `email` (string, required): Customer email address.
     * - `amount` (int|string, required): Amount in minor units (e.g. ZAR cents, minimum 100).
     * - `currency` (string, required): Currency code (e.g. `ZAR`).
     * - `reference` (string, optional): Unique reference for this preauthorization.
     * - `expire_after_days` (int, optional): Days until expiry (default 5, max 7).
     * - `expire_action` (string, optional): `release` (default) or `capture`.
     *
     * @param  array<string, mixed>  $payload
     */
    public function initialize(array $payload): PaystackResponse
    {
        return $this->client->post('preauthorization/initialize', $payload);
    }

    /**
     * Capture funds from an existing preauthorization hold.
     *
     * Expected payload keys:
     * - `reference` (string, required): Reference of the initialized preauthorization.
     * - `amount` (int|string, required): Final amount to capture in minor units (<= held amount).
     * - `currency` (string, required): Currency code (e.g. `ZAR`).
     *
     * @param  array<string, mixed>  $payload
     */
    public function capture(array $payload): PaystackResponse
    {
        return $this->client->post('preauthorization/capture', $payload);
    }

    /**
     * Release an existing preauthorization hold, freeing the reserved funds.
     */
    public function release(string $reference): PaystackResponse
    {
        return $this->client->post('preauthorization/release', [
            'reference' => $reference,
        ]);
    }
}
