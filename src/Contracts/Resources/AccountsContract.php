<?php

declare(strict_types=1);

namespace PayazaSdk\Contracts\Resources;

interface AccountsContract
{
    public function balance(): array;

    public function transactions(int $page = 1, int $limit = 50): array;

    public function transaction(string $transactionId): array;
}