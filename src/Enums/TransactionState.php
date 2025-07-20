<?php

declare(strict_types=1);

namespace PayazaSdk\Enums;

enum TransactionState: string
{
    case PENDING     = 'PENDING';
    case PROCESSING  = 'PROCESSING';
    case SUCCESSFUL  = 'SUCCESSFUL';
    case FAILED      = 'FAILED';
}