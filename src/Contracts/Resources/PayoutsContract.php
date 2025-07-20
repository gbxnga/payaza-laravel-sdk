<?php

declare(strict_types=1);

namespace PayazaSdk\Contracts\Resources;

use PayazaSdk\Data\PayoutBeneficiary;
use PayazaSdk\Data\TransactionStatus;
use PayazaSdk\Enums\Currency;

interface PayoutsContract
{
    public function send(PayoutBeneficiary $beneficiary, string $transactionRef): TransactionStatus;

    public function status(string $transactionRef): TransactionStatus;

    
    public function sendMobileMoney(
        Currency $currency,
        float $amount,
        string $phoneNumber,
        string $accountName,
        string $bankCode,
        string $transactionRef,
        ?string $narration = null,
        ?string $country = null
    ): TransactionStatus;
    
    public function sendGHSBankTransfer(
        float $amount,
        string $accountNumber,
        string $accountName,
        string $bankCode,
        string $transactionRef,
        ?string $narration = null
    ): TransactionStatus;
}