<?php

declare(strict_types=1);

namespace PayazaSdk\Contracts\Resources;

use PayazaSdk\Data\Card;
use PayazaSdk\Enums\Currency;
use PayazaSdk\Data\TransactionStatus;

interface CardsContract
{
    public function charge(
        float       $amount,
        Card        $card,
        string      $transactionRef,
        Currency    $currency = Currency::USD,
        ?string     $accountName = null,
        string      $authType = '3DS',
        ?string     $callbackUrl = null
    ): TransactionStatus;

    public function status(string $transactionRef): TransactionStatus;

    public function refund(string $transactionRef, float $amount): bool;

    public function refundStatus(string $refundTransactionRef): TransactionStatus;
}