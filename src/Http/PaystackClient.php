<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Http;

use Edydeyemi\Paystack\Exceptions\MissingConfigurationException;
use Edydeyemi\Paystack\Exceptions\PaystackRequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * Low-level HTTP client for communicating with the Paystack REST API.
 *
 * Wraps Laravel's {@see HttpFactory} so standard test helpers such as
 * {@see Http::fake()} work out of the box.
 */
class PaystackClient
{
    /**
     * Create a new Paystack HTTP client instance.
     *
     * @param  HttpFactory  $http  Laravel HTTP client factory.
     * @param  string  $secretKey  Paystack secret key (sk_test_... or sk_live_...).
     * @param  string  $baseUrl  Paystack API base URL.
     * @param  int  $timeout  Total request timeout in seconds.
     * @param  int  $connectTimeout  Connection establishment timeout in seconds.
     * @param  int  $retryTimes  Number of retry attempts for transient failures (0 disables retries).
     * @param  int  $retrySleepMs  Delay in milliseconds between retry attempts.
     * @param  bool  $allowCustomBaseUrl  Whether non-Paystack HTTPS hosts are explicitly trusted.
     */
    public function __construct(
        protected HttpFactory $http,
        protected string $secretKey,
        protected string $baseUrl = 'https://api.paystack.co',
        protected int $timeout = 30,
        protected int $connectTimeout = 10,
        protected int $retryTimes = 0,
        protected int $retrySleepMs = 100,
        protected bool $allowCustomBaseUrl = false,
    ) {}

    /**
     * Send an HTTP GET request to a Paystack endpoint.
     *
     * @param  string  $uri  Relative endpoint path (e.g. "transaction/verify/ref_123").
     * @param  array<string, mixed>  $query  Optional query string parameters.
     *
     * @throws MissingConfigurationException When the secret key is not configured.
     * @throws PaystackRequestException When the network call fails or Paystack returns an error.
     */
    public function get(string $uri, array $query = []): PaystackResponse
    {
        return $this->send('GET', $uri, ['query' => $query]);
    }

    /**
     * Send an HTTP POST request with a JSON payload to a Paystack endpoint.
     *
     * @param  string  $uri  Relative endpoint path.
     * @param  array<string, mixed>  $data  JSON request body.
     *
     * @throws MissingConfigurationException When the secret key is not configured.
     * @throws PaystackRequestException When the network call fails or Paystack returns an error.
     */
    public function post(string $uri, array $data = []): PaystackResponse
    {
        return $this->send('POST', $uri, ['json' => $data]);
    }

    /**
     * Send an HTTP PUT request with a JSON payload to a Paystack endpoint.
     *
     * @param  string  $uri  Relative endpoint path.
     * @param  array<string, mixed>  $data  JSON request body.
     *
     * @throws MissingConfigurationException When the secret key is not configured.
     * @throws PaystackRequestException When the network call fails or Paystack returns an error.
     */
    public function put(string $uri, array $data = []): PaystackResponse
    {
        return $this->send('PUT', $uri, ['json' => $data]);
    }

    /**
     * Send an HTTP DELETE request with an optional JSON payload to a Paystack endpoint.
     *
     * @param  string  $uri  Relative endpoint path.
     * @param  array<string, mixed>  $data  Optional JSON request body.
     *
     * @throws MissingConfigurationException When the secret key is not configured.
     * @throws PaystackRequestException When the network call fails or Paystack returns an error.
     */
    public function delete(string $uri, array $data = []): PaystackResponse
    {
        return $this->send('DELETE', $uri, ['json' => $data]);
    }

    /**
     * Execute an HTTP request against the Paystack API and wrap the response.
     *
     * @param  string  $method  HTTP verb (GET, POST, PUT, DELETE).
     * @param  string  $uri  Relative endpoint path.
     * @param  array<string, mixed>  $options  Guzzle/PendingRequest options (query, json, etc.).
     *
     * @throws MissingConfigurationException When the secret key is empty.
     * @throws PaystackRequestException When a connection error, 4xx/5xx status, or status=false occurs.
     */
    public function send(string $method, string $uri, array $options = []): PaystackResponse
    {
        // Fail fast before attempting any network I/O when credentials are missing.
        if ($this->secretKey === '') {
            throw MissingConfigurationException::secretKey();
        }

        try {
            $pending = $this->request();

            if ($this->retryTimes > 0 && strtoupper($method) === 'GET') {
                // Retry only reads; replaying writes can duplicate payment operations.
                // Let the normal response handling below report exhausted retries.
                $pending->retry($this->retryTimes, $this->retrySleepMs, throw: false);
            }

            /** @var Response $httpResponse */
            $httpResponse = $pending->send($method, ltrim($uri, '/'), $options);
        } catch (ConnectionException $e) {
            // Normalize Laravel's ConnectionException into PaystackRequestException for uniform catching.
            throw new PaystackRequestException(
                message: "Unable to connect to Paystack API: {$e->getMessage()}",
                statusCode: 0,
                response: null,
                previous: $e,
            );
        }

        $paystackResponse = PaystackResponse::fromHttpResponse($httpResponse);

        // Paystack occasionally returns HTTP 200 with `status: false` on business/validation errors.
        if ($httpResponse->failed() || ! $paystackResponse->status()) {
            throw PaystackRequestException::fromResponse($paystackResponse, $httpResponse->status());
        }

        return $paystackResponse;
    }

    /**
     * Build a pre-configured PendingRequest with auth, JSON headers, and timeouts.
     */
    protected function request(): PendingRequest
    {
        $this->assertTrustedBaseUrl();

        return $this->http
            ->baseUrl(rtrim($this->baseUrl, '/'))
            ->withToken($this->secretKey)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout);
    }

    /**
     * Ensure the secret bearer token is sent only to a trusted HTTPS endpoint.
     */
    protected function assertTrustedBaseUrl(): void
    {
        $parts = parse_url($this->baseUrl);

        if (! is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https') {
            throw new InvalidArgumentException('Paystack base URL must use HTTPS.');
        }

        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            throw new InvalidArgumentException('Paystack base URL must not contain credentials, a query, or a fragment.');
        }

        if (isset($parts['port']) && $parts['port'] !== 443) {
            throw new InvalidArgumentException('Paystack base URL must use the standard HTTPS port.');
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '' || ($host !== 'api.paystack.co' && ! $this->allowCustomBaseUrl)) {
            throw new InvalidArgumentException(
                'Custom Paystack API hosts require PAYSTACK_ALLOW_CUSTOM_BASE_URL=true.'
            );
        }
    }
}
