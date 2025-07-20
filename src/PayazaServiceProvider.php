<?php

declare(strict_types=1);

namespace PayazaSdk;

use Illuminate\Support\ServiceProvider;
use PayazaSdk\Enums\Environment;

final class PayazaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/payaza.php', 'payaza');

        $this->app->bind(PayazaClient::class, function ($app) {
            
            $defaultAccount = $app['config']->get('payaza.default_account', 'primary');
            $accounts = $app['config']->get('payaza.accounts', []);
            
            if (empty($accounts) || !isset($accounts[$defaultAccount])) {
                throw new \InvalidArgumentException("No account configured for '{$defaultAccount}' - available: " . implode(',', array_keys($accounts)));
            }
            
            $key = $accounts[$defaultAccount]['key'] ?? '';
            if (empty($key)) {
                throw new \InvalidArgumentException("API key for account '{$defaultAccount}' is not configured");
            }
            
            $env = $app['config']->get('payaza.environment') === 'live'
                ? Environment::LIVE : Environment::TEST;

            return new PayazaClient(base64_encode($key), $env);
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/payaza.php' => config_path('payaza.php'),
        ], 'payaza-config');
    }
}