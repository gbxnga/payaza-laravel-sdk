<?php

declare(strict_types=1);

namespace PayazaSdk\Resources;

use Illuminate\Http\Client\Factory as Http;
use PayazaSdk\Contracts\Resources\AccountsContract;
use PayazaSdk\Enums\{Environment, Currency};
use PayazaSdk\Exceptions\PayazaException;
use PayazaSdk\Traits\ResolvesUrls;

final class Accounts implements AccountsContract
{
    use ResolvesUrls;
    
    private ?Currency $filterCurrency = null;
    
    public function __construct(
        private readonly Http        $http,
        private readonly Environment $env
    ) {}

    public function currency(Currency $currency): self
    {
        $this->filterCurrency = $currency;
        return $this;
    }
    
    public function balance(): array
    {
        $accounts = $this->getPayazaAccountsInfo();
        
        if ($this->filterCurrency) {
            $currencyToFind = $this->filterCurrency->value;
            $account = collect($accounts)->firstWhere('currency', $currencyToFind);
            $this->filterCurrency = null; // Reset filter after use
            
            if (!$account) {
                throw new PayazaException("No account found for currency {$currencyToFind}");
            }
            
            return [
                'available_balance' => $account['accountBalance'] ?? 0,
                'currency' => $account['currency'],
                'account_reference' => $account['payazaAccountReference'] ?? null,
                'account_name' => $account['accountName'] ?? null,
            ];
        }
        
        // Return all accounts if no currency filter
        return $accounts;
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

    public function getAccountNameEnquiry(string $accountNumber, string $bankCode, Currency $currency = Currency::NGN): array
    {
        try {
            $response = $this->http->withHeaders([
                'x-TenantID' => $this->getTenantId()
            ])->timeout(110)->post(
                $this->resolveUrl('account_enquiry'),
                [
                    'service_payload' => [
                        'currency' => $currency->value,
                        'bank_code' => $bankCode,
                        'account_number' => $accountNumber,
                    ],
                ]
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new PayazaException('Connection timeout - account name enquiry service not responding');
        }

        if (!$response->successful() || !isset($response->json()['response_code']) || $response->json()['response_code'] != 200) {
            $message = $response->json('response_message', $response->json('message', 'Failed to get account name'));
            throw new PayazaException($message, $response->status());
        }

        $responseContent = $response->json('response_content', []);

        return [
            'account_number' => $responseContent['account_number'] ?? $accountNumber,
            'bank_code' => $responseContent['bank_code'] ?? $bankCode,
            'account_name' => $responseContent['account_name'] ?? null,
            'account_status' => $responseContent['account_status'] ?? 'UNKNOWN',
            'transaction_reference' => $responseContent['transaction_reference'] ?? null,
        ];
    }

    public function getPayazaAccountsInfo(): array
    {
        try {
            $response = $this->http->withHeaders([
                'x-TenantID' => $this->getTenantId()
            ])->get(
                $this->resolveUrl('account_info')
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new PayazaException('Connection timeout - Payaza accounts info service not responding');
        }

        if (!$response->successful()) {
            throw new PayazaException('Failed to retrieve Payaza accounts info', $response->status());
        }

        return $response->json('data', []);
    }

    private function baseUrl(): string
    {
        return 'https://api.payaza.africa' . ($this->env === Environment::LIVE ? '/live' : '');
    }
}