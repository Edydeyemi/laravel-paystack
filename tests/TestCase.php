<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Tests;

use Edydeyemi\Paystack\PaystackServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            PaystackServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('paystack.secret_key', 'sk_test_1234567890abcdef');
        $app['config']->set('paystack.public_key', 'pk_test_1234567890abcdef');
        $app['config']->set('paystack.base_url', 'https://api.paystack.co');
    }
}
