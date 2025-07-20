<?php

declare(strict_types=1);

namespace PayazaSdk\Resources;

use Illuminate\Http\Client\Factory as Http;
use PayazaSdk\Contracts\Resources\PayoutsContract;
use PayazaSdk\Data\{PayoutBeneficiary, TransactionStatus};
use PayazaSdk\Enums\{Environment, TransactionState, Currency};
use PayazaSdk\Exceptions\PayazaException;
use PayazaSdk\Traits\ResolvesUrls;
use Illuminate\Support\Str;

final class Payouts implements PayoutsContract
{
    use ResolvesUrls;
    
    public function __construct(
        private readonly Http        $http,
        private readonly Environment $env
    ) {}

    public function send(PayoutBeneficiary $beneficiary, string $transactionRef): TransactionStatus
    {
        $transactionType = $this->getTransactionType($beneficiary->currency);
        $accountReference = $this->getAccountReference($beneficiary->currency);
        
        $payload = [
            'transaction_type' => $transactionType,
            'service_payload' => [
                'payout_amount' => $beneficiary->amount,
                'transaction_pin' => config('payaza.transaction_pin'),
                'account_reference' => $accountReference,
                'currency' => $beneficiary->currency->value,
                'payout_beneficiaries' => [
                    [
                        'credit_amount' => $beneficiary->amount,
                        'account_number' => $beneficiary->accountNumber,
                        'account_name' => $beneficiary->accountName,
                        'bank_code' => $beneficiary->bankCode,
                        'narration' => $beneficiary->narration ?? 'Payout via Payaza SDK',
                        'transaction_reference' => $transactionRef,
                        'sender' => [
                            'sender_name' => 'Payaza SDK User',
                            'sender_id' => '',
                            'sender_phone_number' => '01234595',
                            'sender_address' => '123, SDK Street'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->http->post(
            $this->resolveUrl('payout_send'),
            $payload
        );

        if (!$response->successful() || !isset($response->json()['response_code']) || $response->json()['response_code'] != 200) {
            $message = $response->json('response_message', $response->json('message', 'Payout failed'));
            throw new PayazaException($message, $response->status());
        }

        $responseContent = $response->json('response_content', []);
        
        return new TransactionStatus(
            transactionId: $transactionRef,
            state: $this->mapStatus($responseContent['transaction_status'] ?? $responseContent['response_status'] ?? 'PENDING'),
            raw: $response->json()
        );
    }

    public function status(string $transactionRef): TransactionStatus
    {
        $response = $this->http->get(
            $this->resolveUrl('payout_status') . "/{$transactionRef}"
        );

        if (!$response->successful() || !isset($response->json()['status']) || !$response->json()['status']) {
            $message = $response->json('message', 'Failed to get transaction status');
            throw new PayazaException($message, $response->status());
        }

        $transactionData = $response->json('data', []);
        $status = $transactionData['transactionStatus'] ?? 'PENDING';
        
        return new TransactionStatus(
            transactionId: $transactionRef,
            state: $this->mapPayoutStatus($status),
            raw: $response->json()
        );
    }

    public function getBanks(string $countryCode): array
    {
        $response = $this->http->get(
            $this->baseUrl() . "/payout/banks/{$countryCode}"
        );

        if (! $response->successful()) {
            throw new PayazaException('Unable to fetch banks', $response->status());
        }

        return $response->json('data', []);
    }

    private function baseUrl(): string
    {
        return 'https://api.payaza.africa' . ($this->env === Environment::LIVE ? '/live' : '');
    }

    private function mapStatus(string|null $status): TransactionState
    {
        return match (Str::lower($status ?? 'pending')) {
            'completed', 'successful' => TransactionState::SUCCESSFUL,
            'processing', 'initialized', 'transaction_initiated' => TransactionState::PROCESSING,
            'failed' => TransactionState::FAILED,
            default => TransactionState::PENDING
        };
    }
    
    private function mapPayoutStatus(string|null $status): TransactionState
    {
        return match ($status) {
            'NIP_SUCCESS' => TransactionState::SUCCESSFUL,
            'NIP_PENDING', 'TRANSACTION_INITIATED' => TransactionState::PROCESSING,
            'NIP_FAILURE' => TransactionState::FAILED,
            default => TransactionState::PENDING
        };
    }
    
    private function getTransactionType(Currency $currency): string
    {
        return match ($currency) {
            Currency::NGN => 'nuban',
            Currency::GHS => 'ghipps',
            Currency::KES, Currency::UGX, Currency::TZS, Currency::XOF => 'mobile_money',
            default => 'nuban'
        };
    }
    
    private function getAccountReference(Currency $currency): ?string
    {
        try {
            $response = $this->http->get(
                $this->resolveUrl('account_info')
            );
            
            if (!$response->successful()) {
                throw new PayazaException('Failed to retrieve account reference');
            }
            
            $accounts = $response->json('data', []);
            $account = collect($accounts)->firstWhere('currency', $currency->value);
            
            if (!$account) {
                throw new PayazaException("No account found for currency {$currency->value}");
            }
            
            return $account['payazaAccountReference'] ?? null;
        } catch (\Exception $e) {
            throw new PayazaException("Failed to get account reference: {$e->getMessage()}");
        }
    }

    public function sendMobileMoney(
        Currency $currency,
        float $amount,
        string $phoneNumber,
        string $accountName,
        string $bankCode,
        string $transactionRef,
        ?string $narration = null,
        ?string $country = null
    ): TransactionStatus {
        $accountReference = $this->getAccountReference($currency);
        
        $payload = [
            'transaction_type' => 'mobile_money',
            'service_payload' => [
                'payout_amount' => $amount,
                'transaction_pin' => config('payaza.transaction_pin'),
                'account_reference' => $accountReference,
                'currency' => $currency->value,
                'payout_beneficiaries' => [
                    [
                        'credit_amount' => $amount,
                        'account_number' => $phoneNumber,
                        'account_name' => $accountName,
                        'bank_code' => $bankCode,
                        'narration' => $narration ?? 'Mobile Money Payout',
                        'transaction_reference' => $transactionRef,
                        'sender' => [
                            'sender_name' => 'Payaza SDK User',
                            'sender_id' => '',
                            'sender_phone_number' => '01234595',
                            'sender_address' => '123, SDK Street'
                        ]
                    ]
                ]
            ]
        ];

        // Add country for XOF currency
        if ($currency === Currency::XOF && $country) {
            $payload['service_payload']['country'] = $country;
        }

        $response = $this->http->post(
            $this->resolveUrl('payout_send'),
            $payload
        );

        if (!$response->successful() || !isset($response->json()['response_code']) || $response->json()['response_code'] != 200) {
            $message = $response->json('response_message', $response->json('message', 'Mobile money payout failed'));
            throw new PayazaException($message, $response->status());
        }

        $responseContent = $response->json('response_content', []);
        
        return new TransactionStatus(
            transactionId: $transactionRef,
            state: $this->mapStatus($responseContent['transaction_status'] ?? $responseContent['response_status'] ?? 'PENDING'),
            raw: $response->json()
        );
    }

    public function sendGHSBankTransfer(
        float $amount,
        string $accountNumber,
        string $accountName,
        string $bankCode,
        string $transactionRef,
        ?string $narration = null
    ): TransactionStatus {
        $accountReference = $this->getAccountReference(Currency::GHS);
        
        $payload = [
            'transaction_type' => 'ghipps',
            'service_payload' => [
                'payout_amount' => $amount,
                'transaction_pin' => config('payaza.transaction_pin'),
                'account_reference' => $accountReference,
                'currency' => 'GHS',
                'payout_beneficiaries' => [
                    [
                        'credit_amount' => $amount,
                        'account_number' => $accountNumber,
                        'account_name' => $accountName,
                        'bank_code' => $bankCode,
                        'narration' => $narration ?? 'GHS Bank Transfer',
                        'transaction_reference' => $transactionRef,
                        'sender' => [
                            'sender_name' => 'Payaza SDK User',
                            'sender_id' => '',
                            'sender_phone_number' => '01234595',
                            'sender_address' => '123, SDK Street'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->http->post(
            $this->resolveUrl('payout_send'),
            $payload
        );

        if (!$response->successful() || !isset($response->json()['response_code']) || $response->json()['response_code'] != 200) {
            $message = $response->json('response_message', $response->json('message', 'GHS bank transfer failed'));
            throw new PayazaException($message, $response->status());
        }

        $responseContent = $response->json('response_content', []);
        
        return new TransactionStatus(
            transactionId: $transactionRef,
            state: $this->mapStatus($responseContent['transaction_status'] ?? $responseContent['response_status'] ?? 'PENDING'),
            raw: $response->json()
        );
    }
}