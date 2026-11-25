<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Exceptions;

/**
 * Thrown when an incoming Paystack webhook fails signature or IP verification.
 */
class InvalidWebhookSignatureException extends PaystackException
{
    public static function missingSignature(): self
    {
        return new self('Missing Paystack webhook signature header (x-paystack-signature).');
    }

    public static function invalidSignature(): self
    {
        return new self('Invalid Paystack webhook signature.');
    }

    public static function disallowedIp(string $ip): self
    {
        return new self("Webhook request from disallowed IP address [{$ip}].");
    }
}
