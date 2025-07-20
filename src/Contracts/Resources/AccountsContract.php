<?php

declare(strict_types=1);

namespace PayazaSdk\Contracts\Resources;

use PayazaSdk\Enums\Currency;

interface AccountsContract
{
    public function currency(Currency $currency): self;
    
    public function balance(): array;

    
    public function getAccountNameEnquiry(string $accountNumber, string $bankCode, Currency $currency = Currency::NGN): array;
    
    public function getPayazaAccountsInfo(): array;
}