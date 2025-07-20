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

        $this->app->singleton(PayazaClient::class, function () {
            $key = config('payaza.primary_public_key');
            $env = config('payaza.environment') === 'live'
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