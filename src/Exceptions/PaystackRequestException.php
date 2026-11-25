<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Exceptions;

use Edydeyemi\Paystack\Http\PaystackResponse;
use Throwable;

/**
 * Thrown when a Paystack API request fails (HTTP error or status=false response).
 */
class PaystackRequestException extends PaystackException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        public readonly ?PaystackResponse $response = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public static function fromResponse(PaystackResponse $response, int $statusCode): self
    {
        $message = $response->message() ?: "Paystack API request failed with HTTP status {$statusCode}.";

        return new self(
            message: $message,
            statusCode: $statusCode,
            response: $response,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function errors(): array
    {
        $data = $this->response?->toArray() ?? [];

        /** @var array<string, mixed> */
        return is_array($data['errors'] ?? null) ? $data['errors'] : [];
    }
}
