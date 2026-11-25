<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Http;

use ArrayAccess;
use Countable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use JsonSerializable;
use LogicException;

/**
 * Immutable, fluent wrapper around a Paystack API JSON response.
 *
 * Paystack responses follow the envelope `{status: bool, message: string, data: mixed, meta?: array}`.
 * This class provides dot-notation accessors for `data` and `meta`, plus read-only array access.
 *
 * @implements ArrayAccess<string, mixed>
 */
class PaystackResponse implements ArrayAccess, Countable, JsonSerializable
{
    /**
     * Decoded JSON response payload.
     *
     * @var array<string, mixed>
     */
    protected array $payload;

    /**
     * Create a new PaystackResponse instance.
     *
     * @param  array<string, mixed>  $payload  Decoded JSON response body.
     * @param  int  $httpStatus  HTTP status code returned by Paystack.
     * @param  array<string, array<int, string>>  $headers  Response headers.
     */
    public function __construct(
        array $payload,
        protected int $httpStatus = 200,
        protected array $headers = [],
    ) {
        $this->payload = $payload;
    }

    /**
     * Construct a PaystackResponse from a Laravel HTTP client Response.
     */
    public static function fromHttpResponse(Response $response): self
    {
        $decoded = $response->json();

        return new self(
            payload: is_array($decoded) ? $decoded : [],
            httpStatus: $response->status(),
            headers: $response->headers(),
        );
    }

    /**
     * Determine whether Paystack reported `status: true` in the JSON body.
     */
    public function status(): bool
    {
        return (bool) ($this->payload['status'] ?? false);
    }

    /**
     * Determine whether both the JSON `status` flag is true and the HTTP status is 2xx.
     */
    public function ok(): bool
    {
        return $this->status() && $this->httpStatus >= 200 && $this->httpStatus < 300;
    }

    /**
     * Retrieve the human-readable `message` returned by Paystack.
     */
    public function message(): string
    {
        $message = $this->payload['message'] ?? '';

        return is_string($message) ? $message : '';
    }

    /**
     * Retrieve the response `data` payload or a nested value using dot notation.
     *
     * @param  string|null  $key  Dot-notated key inside `data` (e.g. "customer.email"), or null for all data.
     * @param  mixed  $default  Fallback value when the key does not exist.
     */
    public function data(?string $key = null, mixed $default = null): mixed
    {
        $data = $this->payload['data'] ?? null;

        if ($key === null) {
            return $data ?? $default;
        }

        if (! is_array($data)) {
            return $default;
        }

        return Arr::get($data, $key, $default);
    }

    /**
     * Retrieve the response `meta` pagination/metadata payload or a nested value using dot notation.
     *
     * @param  string|null  $key  Dot-notated key inside `meta` (e.g. "total"), or null for all metadata.
     * @param  mixed  $default  Fallback value when the key does not exist.
     */
    public function meta(?string $key = null, mixed $default = null): mixed
    {
        $meta = $this->payload['meta'] ?? null;

        if ($key === null) {
            return $meta ?? $default;
        }

        if (! is_array($meta)) {
            return $default;
        }

        return Arr::get($meta, $key, $default);
    }

    /**
     * Retrieve the HTTP status code of the underlying response.
     */
    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    /**
     * Retrieve the HTTP response headers.
     *
     * @return array<string, array<int, string>>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Convert the entire Paystack response envelope to an associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->payload;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->payload;
    }

    /**
     * Count the number of items in `data` when `data` is an array.
     */
    public function count(): int
    {
        $data = $this->data();

        return is_array($data) ? count($data) : 0;
    }

    /**
     * Determine whether a top-level payload key exists.
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists((string) $offset, $this->payload);
    }

    /**
     * Retrieve a top-level payload key.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->payload[(string) $offset] ?? null;
    }

    /**
     * Prevent mutating the response payload via ArrayAccess.
     *
     * @throws LogicException Always thrown because PaystackResponse is immutable.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('PaystackResponse is immutable.');
    }

    /**
     * Prevent unsetting response keys via ArrayAccess.
     *
     * @throws LogicException Always thrown because PaystackResponse is immutable.
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('PaystackResponse is immutable.');
    }
}
