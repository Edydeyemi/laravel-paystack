<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack;

use Edydeyemi\Paystack\Http\PaystackClient;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;

/**
 * Laravel service provider for registering Paystack services, configuration, and webhook routes.
 */
class PaystackServiceProvider extends ServiceProvider
{
    /**
     * Register package configuration and singleton bindings in the container.
     */
    public function register(): void
    {
        // Merge package defaults so applications only need to publish keys they want to override.
        $this->mergeConfigFrom(__DIR__.'/../config/paystack.php', 'paystack');

        $this->app->singleton(PaystackClient::class, function (Application $app): PaystackClient {
            /** @var array<string, mixed> $config */
            $config = $app->make('config')->get('paystack', []);

            /** @var array<string, mixed> $retry */
            $retry = is_array($config['retry'] ?? null) ? $config['retry'] : [];

            return new PaystackClient(
                http: $app->make(HttpFactory::class),
                secretKey: (string) ($config['secret_key'] ?? ''),
                baseUrl: (string) ($config['base_url'] ?? 'https://api.paystack.co'),
                timeout: (int) ($config['timeout'] ?? 30),
                connectTimeout: (int) ($config['connect_timeout'] ?? 10),
                retryTimes: (int) ($retry['times'] ?? 0),
                retrySleepMs: (int) ($retry['sleep_ms'] ?? 100),
                allowCustomBaseUrl: (bool) ($config['allow_custom_base_url'] ?? false),
            );
        });

        $this->app->singleton(Paystack::class, function (Application $app): Paystack {
            return new Paystack($app->make(PaystackClient::class));
        });

        // Provide a short container alias for convenience when resolving via app('paystack').
        $this->app->alias(Paystack::class, 'paystack');
    }

    /**
     * Bootstrap publishable assets and auto-registered webhook routes.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/paystack.php' => config_path('paystack.php'),
            ], 'paystack-config');
        }

        // Route registration respects `paystack.webhook.enabled` inside routes/webhooks.php.
        $this->loadRoutesFrom(__DIR__.'/../routes/webhooks.php');
    }
}
