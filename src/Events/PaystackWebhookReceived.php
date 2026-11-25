<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Events;

/**
 * Dispatched for every verified Paystack webhook regardless of event type.
 */
class PaystackWebhookReceived extends PaystackWebhookEvent {}
