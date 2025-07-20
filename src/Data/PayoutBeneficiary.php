<?php

declare(strict_types=1);

namespace PayazaSdk\Data;

use PayazaSdk\Enums\Currency;

final readonly class PayoutBeneficiary
{
    public function __construct(
        public string   $accountName,
        public string   $accountNumber,
        public string   $bankCode,
        public float    $amount,
        public Currency $currency,
        public ?string  $narration = null,
    ) {}
}