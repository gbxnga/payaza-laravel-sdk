<?php

declare(strict_types=1);

namespace PayazaSdk\Resources;

use Illuminate\Http\Client\Factory as Http;
use PayazaSdk\Contracts\Resources\PayoutsContract;
use PayazaSdk\Data\{PayoutBeneficiary, TransactionStatus};
use PayazaSdk\Enums\{Environment, TransactionState};
use PayazaSdk\Exceptions\PayazaException;
use Illuminate\Support\Str;

final class Payouts implements PayoutsContract
{
    public function __construct(
        private readonly Http        $http,
        private readonly Environment $env
    ) {}

    public function send(PayoutBeneficiary $beneficiary, string $transactionRef): TransactionStatus
    {
        $payload = [
            'service_payload' => [
                'account_name'          => $beneficiary->accountName,
                'account_number'        => $beneficiary->accountNumber,
                'bank_code'             => $beneficiary->bankCode,
                'amount'                => $beneficiary->amount,
                'currency'              => $beneficiary->currency->value,
                'transaction_reference' => $transactionRef,
                'narration'             => $beneficiary->narration ?? 'Payout via Payaza SDK',
            ],
        ];

        $response = $this->http->post(
            $this->baseUrl() . '/payout/transfer',
            $payload
        );

        if (! $response->successful()) {
            throw new PayazaException(
                message: $response->json('message', 'Payout failed'),
                code: $response->status()
            );
        }

        return new TransactionStatus(
            transactionId: $transactionRef,
            state:         $this->mapStatus($response->json('response_content.transaction_status') ?? 'PENDING'),
            raw:           $response->json()
        );
    }

    public function status(string $transactionRef): TransactionStatus
    {
        $response = $this->http->post(
            $this->baseUrl() . '/payout/transaction_status',
            ['service_payload' => ['transaction_reference' => $transactionRef]]
        );

        if (! $response->successful()) {
            throw new PayazaException('Unable to fetch payout status', $response->status());
        }

        return new TransactionStatus(
            transactionId: $transactionRef,
            state:         $this->mapStatus($response->json('response_content.transaction_status')),
            raw:           $response->json()
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
        return config('payaza.base_url') . ($this->env === Environment::LIVE ? '/live' : '');
    }

    private function mapStatus(string|null $status): TransactionState
    {
        return match (Str::lower($status ?? 'pending')) {
            'completed', 'successful' => TransactionState::SUCCESSFUL,
            'processing', 'initialized' => TransactionState::PROCESSING,
            'failed' => TransactionState::FAILED,
            default => TransactionState::PENDING
        };
    }
}