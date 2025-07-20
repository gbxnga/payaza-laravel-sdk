<?php

declare(strict_types=1);

namespace PayazaSdk;

use Illuminate\Support\Facades\Facade;

/**
 * @method static Contracts\Resources\CardsContract    cards()
 * @method static Contracts\Resources\PayoutsContract  payouts()
 * @method static Contracts\Resources\AccountsContract accounts()
 */
final class Payaza extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PayazaClient::class;
    }
}