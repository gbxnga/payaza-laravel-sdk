<?php

declare(strict_types=1);

namespace PayazaSdk\Data;

use PayazaSdk\Enums\TransactionState;

final readonly class TransactionStatus
{
    public function __construct(
        public string            $transactionId,
        public TransactionState  $state,
        public array             $raw
    ) {}
}