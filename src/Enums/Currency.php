<?php

declare(strict_types=1);

namespace PayazaSdk\Enums;

enum Currency: string
{
    case USD = 'USD';
    case NGN = 'NGN';
    case GHS = 'GHS';
    case XOF = 'XOF';
    case KES = 'KES';
    case UGX = 'UGX';
    case TZS = 'TZS';
}