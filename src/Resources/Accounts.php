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


    public function getAccountNameEnquiry(string $accountNumber, string $bankCode, Currency $currency = Currency::NGN): array
    {
        try {
            $response = $this->http->post(
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

        $responseData = $response->json();
        $responseCode = $responseData['response_code'] ?? null;
        $responseMessage = $responseData['response_message'] ?? 'Unknown error';
        
        // Handle invalid account gracefully - return as inactive rather than throwing exception
        if ($responseCode == 500 && str_contains(strtolower($responseMessage), 'invalid account')) {
            return [
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
                'account_name' => null,
                'account_status' => 'INVALID',
                'transaction_reference' => null,
                'error_message' => $responseMessage
            ];
        }
        
        // Only throw exception for unexpected errors (not invalid accounts)
        if (!$response->successful() || $responseCode != 200) {
            throw new PayazaException($responseMessage, $response->status(), null, $responseData);
        }

        $responseContent = $responseData['response_content'] ?? [];

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
            $response = $this->http->get(
                $this->resolveUrl('account_info')
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new PayazaException('Connection timeout - Payaza accounts info service not responding');
        }

        if (!$response->successful()) {
            throw new PayazaException('Failed to retrieve Payaza accounts info', $response->status(), null, $response->json());
        }

        return $response->json('data', []);
    }

    private function baseUrl(): string
    {
        return 'https://api.payaza.africa' . ($this->env === Environment::LIVE ? '/live' : '');
    }
}