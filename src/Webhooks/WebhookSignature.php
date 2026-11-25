<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Webhooks;

use Edydeyemi\Paystack\Exceptions\InvalidWebhookSignatureException;
use Edydeyemi\Paystack\Exceptions\MissingConfigurationException;

/**
 * Computes and verifies Paystack HMAC-SHA512 webhook signatures.
 *
 * Paystack signs every webhook request body with your secret key using HMAC-SHA512
 * and sends the hex digest in the `x-paystack-signature` HTTP header.
 */
final class WebhookSignature
{
    /**
     * Compute the expected HMAC-SHA512 signature for a raw webhook request body.
     *
     * @param  string  $rawPayload  Exact raw JSON body received from Paystack.
     * @param  string  $secretKey  Paystack secret key used as the HMAC key.
     */
    public static function compute(string $rawPayload, string $secretKey): string
    {
        return hash_hmac('sha512', $rawPayload, $secretKey);
    }

    /**
     * Verify that the incoming `x-paystack-signature` matches the raw request body.
     *
     * @param  string  $rawPayload  Exact raw JSON body received from Paystack.
     * @param  string|null  $signature  Value of the `x-paystack-signature` header.
     * @param  string  $secretKey  Configured Paystack secret key.
     *
     * @throws MissingConfigurationException When the secret key is empty.
     * @throws InvalidWebhookSignatureException When the signature is missing or does not match.
     */
    public static function verify(string $rawPayload, ?string $signature, string $secretKey): void
    {
        if ($secretKey === '') {
            throw MissingConfigurationException::secretKey();
        }

        if ($signature === null || $signature === '') {
            throw InvalidWebhookSignatureException::missingSignature();
        }

        $expected = self::compute($rawPayload, $secretKey);

        // Use constant-time comparison to prevent timing side-channel attacks.
        if (! hash_equals($expected, $signature)) {
            throw InvalidWebhookSignatureException::invalidSignature();
        }
    }
}
