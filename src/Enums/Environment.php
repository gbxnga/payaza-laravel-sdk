<?php

declare(strict_types=1);

namespace PayazaSdk\Enums;

enum Environment: string
{
    case TEST = 'test';
    case LIVE = 'live';
}