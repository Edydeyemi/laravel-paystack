<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Exceptions;

/**
 * Thrown when required Paystack configuration (such as the secret key) is missing.
 */
class MissingConfigurationException extends PaystackException
{
    public static function secretKey(): self
    {
        return new self(
            'Paystack secret key is not configured. Please set PAYSTACK_SECRET_KEY in your .env file or config/paystack.php.'
        );
    }
}
