<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Resources;

use Edydeyemi\Paystack\Http\PaystackClient;

/**
 * Base class for all Paystack API resource groups.
 */
abstract class Resource
{
    public function __construct(
        protected readonly PaystackClient $client,
    ) {}
}
