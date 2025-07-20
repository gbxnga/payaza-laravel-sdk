<?php

declare(strict_types=1);

namespace PayazaSdk\Contracts;

interface PayazaClientContract
{
    public function cards(): Resources\CardsContract;
    public function payouts(): Resources\PayoutsContract;
    public function accounts(): Resources\AccountsContract;
}