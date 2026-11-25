<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack;

use Edydeyemi\Paystack\Http\PaystackClient;
use Edydeyemi\Paystack\Resources\CustomerResource;
use Edydeyemi\Paystack\Resources\DedicatedAccountResource;
use Edydeyemi\Paystack\Resources\MiscellaneousResource;
use Edydeyemi\Paystack\Resources\PlanResource;
use Edydeyemi\Paystack\Resources\PreauthorizationResource;
use Edydeyemi\Paystack\Resources\RefundResource;
use Edydeyemi\Paystack\Resources\SplitResource;
use Edydeyemi\Paystack\Resources\SubaccountResource;
use Edydeyemi\Paystack\Resources\SubscriptionResource;
use Edydeyemi\Paystack\Resources\TransactionResource;
use Edydeyemi\Paystack\Resources\TransferRecipientResource;
use Edydeyemi\Paystack\Resources\TransferResource;
use Edydeyemi\Paystack\Resources\VerificationResource;
use Illuminate\Support\Str;

/**
 * Main entry point and resource factory for the Paystack SDK.
 *
 * Resolve this class via dependency injection or use the
 * {@see Facades\Paystack} facade to access
 * fluent Paystack API resource groups.
 */
class Paystack
{
    /**
     * Create a new Paystack SDK manager instance.
     *
     * @param  PaystackClient  $client  Configured HTTP client used by all API resources.
     */
    public function __construct(
        protected readonly PaystackClient $client,
    ) {}

    /**
     * Retrieve the underlying low-level Paystack HTTP client.
     */
    public function client(): PaystackClient
    {
        return $this->client;
    }

    /**
     * Access the Paystack Transactions API resource.
     */
    public function transactions(): TransactionResource
    {
        return new TransactionResource($this->client);
    }

    /**
     * Access the Paystack Refunds API resource.
     */
    public function refunds(): RefundResource
    {
        return new RefundResource($this->client);
    }

    /**
     * Access the Paystack Customers API resource.
     */
    public function customers(): CustomerResource
    {
        return new CustomerResource($this->client);
    }

    /**
     * Access the Paystack Plans API resource.
     */
    public function plans(): PlanResource
    {
        return new PlanResource($this->client);
    }

    /**
     * Access the Paystack Subscriptions API resource.
     */
    public function subscriptions(): SubscriptionResource
    {
        return new SubscriptionResource($this->client);
    }

    /**
     * Access the Paystack Transfers API resource.
     */
    public function transfers(): TransferResource
    {
        return new TransferResource($this->client);
    }

    /**
     * Access the Paystack Transfer Recipients API resource.
     */
    public function transferRecipients(): TransferRecipientResource
    {
        return new TransferRecipientResource($this->client);
    }

    /**
     * Access the Paystack Subaccounts API resource.
     */
    public function subaccounts(): SubaccountResource
    {
        return new SubaccountResource($this->client);
    }

    /**
     * Access the Paystack Transaction Splits API resource.
     */
    public function splits(): SplitResource
    {
        return new SplitResource($this->client);
    }

    /**
     * Access the Paystack Dedicated Virtual Accounts (DVA) API resource.
     */
    public function dedicatedAccounts(): DedicatedAccountResource
    {
        return new DedicatedAccountResource($this->client);
    }

    /**
     * Access the Paystack Verification API resource.
     */
    public function verification(): VerificationResource
    {
        return new VerificationResource($this->client);
    }

    /**
     * Access the Paystack Miscellaneous API resource (banks, countries, states).
     */
    public function miscellaneous(): MiscellaneousResource
    {
        return new MiscellaneousResource($this->client);
    }

    /**
     * Access the Paystack Card Preauthorization API resource (holds, captures, releases).
     */
    public function preauthorizations(): PreauthorizationResource
    {
        return new PreauthorizationResource($this->client);
    }

    /**
     * Generate a lexicographically sortable, unique transaction or transfer reference.
     *
     * @param  string  $prefix  Optional prefix prepended to the generated ULID (e.g. "ord_", "trf_").
     */
    public function generateReference(string $prefix = 'psk_'): string
    {
        // Base32 ULIDs are URL-safe, uppercase alphanumeric, and monotonically sortable.
        return $prefix.Str::ulid()->toBase32();
    }
}
