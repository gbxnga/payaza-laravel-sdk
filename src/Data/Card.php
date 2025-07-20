<?php

declare(strict_types=1);

namespace PayazaSdk\Data;

final readonly class Card
{
    public function __construct(
        public string $number,
        public int    $expiryMonth,
        public int    $expiryYear,
        public string $cvc
    ) {}
}