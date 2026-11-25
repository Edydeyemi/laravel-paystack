<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Http\Middleware;

use Closure;
use Edydeyemi\Paystack\Exceptions\InvalidWebhookSignatureException;
use Edydeyemi\Paystack\Webhooks\WebhookSignature;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP middleware that protects the Paystack webhook endpoint.
 *
 * Enforces:
 * 1. Optional client IP allowlisting via `paystack.webhook.allowed_ips`.
 * 2. Mandatory HMAC-SHA512 signature verification via `x-paystack-signature`.
 */
class VerifyPaystackWebhookSignature
{
    /**
     * Handle an incoming Paystack webhook request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var array<int, string> $allowedIps */
        $allowedIps = config('paystack.webhook.allowed_ips', []);

        // Check IP allowlist first so unauthorized origins are rejected before hashing the body.
        if ($allowedIps !== []) {
            $clientIp = (string) $request->ip();

            if (! in_array($clientIp, $allowedIps, true)) {
                abort(403, InvalidWebhookSignatureException::disallowedIp($clientIp)->getMessage());
            }
        }

        $secretKey = (string) config('paystack.secret_key', '');
        $signature = $request->header('x-paystack-signature');

        try {
            // Verify against the exact raw body bytes (not re-encoded JSON) to preserve signature fidelity.
            WebhookSignature::verify(
                rawPayload: $request->getContent(),
                signature: is_string($signature) ? $signature : null,
                secretKey: $secretKey,
            );
        } catch (InvalidWebhookSignatureException $e) {
            // Return 403 Forbidden instead of 500 so unauthenticated probes do not trigger Paystack retries.
            abort(403, $e->getMessage());
        }

        return $next($request);
    }
}
