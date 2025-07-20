<?php

declare(strict_types=1);

namespace PayazaSdk\Data;

final readonly class Card
{
    public readonly string $expiryMonth;
    public readonly string $expiryYear;
    
    public function __construct(
        public string $number,
        string|int $expiryMonth,
        string|int $expiryYear,
        public string $cvc
    ) {
        // Ensure MM format for month
        $this->expiryMonth = str_pad((string)$expiryMonth, 2, '0', STR_PAD_LEFT);
        
        // Ensure YY format for year  
        $this->expiryYear = str_pad((string)$expiryYear, 2, '0', STR_PAD_LEFT);
    }
}