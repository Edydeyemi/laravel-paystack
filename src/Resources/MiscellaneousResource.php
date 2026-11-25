<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Resources;

use Edydeyemi\Paystack\Http\PaystackResponse;

/**
 * Paystack Miscellaneous API resource for banks, countries, and states.
 *
 * @see https://paystack.com/docs/api/miscellaneous/
 */
class MiscellaneousResource extends Resource
{
    /**
     * List all supported banks and their properties.
     *
     * @param  array<string, mixed>  $query  Optional filters: `country` (e.g. "nigeria", "ghana"),
     *                                       `use_cursor`, `perPage`, `pay_with_bank_transfer`, `pay_with_bank`, `currency`.
     */
    public function banks(array $query = []): PaystackResponse
    {
        return $this->client->get('bank', $query);
    }

    /**
     * List all countries currently supported by Paystack.
     */
    public function countries(): PaystackResponse
    {
        return $this->client->get('country');
    }

    /**
     * List states/provinces for a supported country (used for address verification).
     *
     * @param  string  $country  Two-letter ISO country code (e.g. "NG", "US", "CA").
     */
    public function states(string $country): PaystackResponse
    {
        return $this->client->get('address_verification/states', [
            'country' => $country,
        ]);
    }
}
