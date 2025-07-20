<?php

declare(strict_types=1);

namespace PayazaSdk\Resources;

use Illuminate\Http\Client\Factory as Http;
use PayazaSdk\Contracts\Resources\AccountsContract;
use PayazaSdk\Enums\Environment;
use PayazaSdk\Exceptions\PayazaException;

final class Accounts implements AccountsContract
{
    public function __construct(
        private readonly Http        $http,
        private readonly Environment $env
    ) {}

    public function balance(): array
    {
        $response = $this->http->get(
            $this->baseUrl() . '/account/balance'
        );

        if (! $response->successful()) {
            throw new PayazaException('Unable to fetch account balance', $response->status());
        }

        return $response->json('data', []);
    }

    public function transactions(int $page = 1, int $limit = 50): array
    {
        $response = $this->http->get(
            $this->baseUrl() . '/account/transactions',
            [
                'page' => $page,
                'limit' => $limit,
            ]
        );

        if (! $response->successful()) {
            throw new PayazaException('Unable to fetch transactions', $response->status());
        }

        return $response->json('data', []);
    }

    public function transaction(string $transactionId): array
    {
        $response = $this->http->get(
            $this->baseUrl() . "/account/transaction/{$transactionId}"
        );

        if (! $response->successful()) {
            throw new PayazaException('Unable to fetch transaction', $response->status());
        }

        return $response->json('data', []);
    }

    private function baseUrl(): string
    {
        return config('payaza.base_url') . ($this->env === Environment::LIVE ? '/live' : '');
    }
}