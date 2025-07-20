<?php

declare(strict_types=1);

namespace PayazaSdk\Contracts\Resources;

use PayazaSdk\Data\PayoutBeneficiary;
use PayazaSdk\Data\TransactionStatus;

interface PayoutsContract
{
    public function send(PayoutBeneficiary $beneficiary, string $transactionRef): TransactionStatus;

    public function status(string $transactionRef): TransactionStatus;

    public function getBanks(string $countryCode): array;
}