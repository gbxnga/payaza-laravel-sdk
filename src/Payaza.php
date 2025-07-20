<?php

declare(strict_types=1);

namespace PayazaSdk;

use Illuminate\Support\Facades\Facade;
use InvalidArgumentException;

/**
 * @method static PayazaClient account(string $name)
 * @method static Contracts\Resources\CardsContract    cards()
 * @method static Contracts\Resources\PayoutsContract  payouts()
 * @method static Contracts\Resources\AccountsContract accounts()
 */
final class Payaza extends Facade
{
    public static function account(string $name): PayazaClient
    {
        $accounts = config('payaza.accounts');
        
        if (!isset($accounts[$name])) {
            throw new InvalidArgumentException("Account '{$name}' not found in configuration");
        }
        
        if (empty($accounts[$name]['key'])) {
            throw new InvalidArgumentException("API key for account '{$name}' is not configured");
        }
        
        return new PayazaClient(
            token: base64_encode($accounts[$name]['key']),
            env: Enums\Environment::from(config('payaza.environment'))
        );
    }

    protected static function getFacadeAccessor(): string
    {
        return PayazaClient::class;
    }
}