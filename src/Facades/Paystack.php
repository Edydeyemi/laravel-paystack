<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Facades;

use Edydeyemi\Paystack\Http\PaystackClient;
use Edydeyemi\Paystack\Paystack as PaystackManager;
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
use Illuminate\Support\Facades\Facade;

/**
 * @method static PaystackClient client()
 * @method static TransactionResource transactions()
 * @method static PreauthorizationResource preauthorizations()
 * @method static RefundResource refunds()
 * @method static CustomerResource customers()
 * @method static PlanResource plans()
 * @method static SubscriptionResource subscriptions()
 * @method static TransferResource transfers()
 * @method static TransferRecipientResource transferRecipients()
 * @method static SubaccountResource subaccounts()
 * @method static SplitResource splits()
 * @method static DedicatedAccountResource dedicatedAccounts()
 * @method static VerificationResource verification()
 * @method static MiscellaneousResource miscellaneous()
 * @method static PreauthorizationResource preauthorizations()
 * @method static string generateReference(string $prefix = 'psk_')
 *
 * @see PaystackManager
 */
class Paystack extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PaystackManager::class;
    }
}
