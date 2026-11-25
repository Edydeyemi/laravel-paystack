# Customers, Plans & Subscriptions

Covers `Paystack::customers()`, `Paystack::plans()`, and `Paystack::subscriptions()`.

## 1. Managing Customers

### Create, Fetch & Update

```php
use Edydeyemi\Paystack\Facades\Paystack;

$customer = Paystack::customers()->create([
    'email' => 'ada@example.com',
    'first_name' => 'Ada',
    'last_name' => 'Lovelace',
    'phone' => '+2348012345678',
    'metadata' => ['user_id' => 42],
]);

$customerCode = $customer->data('customer_code'); // e.g. CUS_xnxdt6s1zg1f4nx

// Fetch by email or customer code
$fetched = Paystack::customers()->fetch($customerCode);

// Update customer profile
Paystack::customers()->update($customerCode, [
    'first_name' => 'Augusta Ada',
]);

// List customers
$customers = Paystack::customers()->list(['perPage' => 50, 'page' => 1]);
```

### Customer Identity Validation & Risk Actions

```php
// Validate customer identity (e.g. bank_account + BVN)
Paystack::customers()->validate($customerCode, [
    'country' => 'NG',
    'type' => 'bank_account',
    'account_number' => '0123456789',
    'bvn' => '20012345678',
    'bank_code' => '058',
    'first_name' => 'Ada',
    'last_name' => 'Lovelace',
]);

// Whitelist ('allow'), blacklist ('deny'), or reset ('default')
Paystack::customers()->setRiskAction($customerCode, 'allow');
```

## 2. Managing Plans

Create recurring billing plans for SaaS tiers or memberships:

```php
use Edydeyemi\Paystack\Support\Amount;

$plan = Paystack::plans()->create([
    'name' => 'Pro Monthly',
    'description' => 'Full access to Pro workspace features',
    'amount' => Amount::toMinor(15000), // NGN 15,000 / month
    'interval' => 'monthly',            // hourly, daily, weekly, monthly, quarterly, biannually, annually
    'currency' => 'NGN',
    'send_invoices' => true,
    'send_sms' => false,
]);

$planCode = $plan->data('plan_code'); // e.g. PLN_gx2wn530m0i3w3m

// List, fetch, and update plans
$plans = Paystack::plans()->list(['interval' => 'monthly']);
$fetchedPlan = Paystack::plans()->fetch($planCode);

Paystack::plans()->update($planCode, [
    'name' => 'Pro Monthly (2026)',
    'amount' => Amount::toMinor(18000),
]);
```

## 3. Managing Subscriptions

### Option A: Subscribe via Transaction Initialization

Pass `plan` when initializing a transaction so Paystack automatically creates the subscription upon first payment:

```php
$checkout = Paystack::transactions()->initialize([
    'email' => $user->email,
    'amount' => Amount::toMinor(15000),
    'plan' => $planCode,
    'callback_url' => route('billing.callback'),
]);
```

### Option B: Subscribe an Existing Customer Directly

```php
$subscription = Paystack::subscriptions()->create([
    'customer' => $customerCode,
    'plan' => $planCode,
    'authorization' => $user->paystack_auth_code, // optional if customer has an active auth
    'start_date' => now()->addDays(7)->toIso8601String(),
]);

$subCode = $subscription->data('subscription_code'); // SUB_...
$emailToken = $subscription->data('email_token');
```

### Enable, Disable & Card Management Links

```php
// Fetch or list subscriptions
$subs = Paystack::subscriptions()->list(['customer' => $customerId]);
$sub = Paystack::subscriptions()->fetch($subCode);

// Disable (cancel) a subscription
Paystack::subscriptions()->disable($subCode, $emailToken);

// Re-enable a subscription
Paystack::subscriptions()->enable($subCode, $emailToken);

// Generate a self-service card update link for your UI
$link = Paystack::subscriptions()->generateUpdateLink($subCode)->data('link');

// Or have Paystack email the card update link directly to the customer
Paystack::subscriptions()->sendUpdateLink($subCode);
```
