<?php

declare(strict_types=1);

namespace PayazaSdk\Resources;

use Illuminate\Http\Client\Factory as Http;
use PayazaSdk\Contracts\Resources\CardsContract;
use PayazaSdk\Data\{Card, TransactionStatus};
use PayazaSdk\Enums\{Currency, Environment, TransactionState};
use PayazaSdk\Exceptions\PayazaException;
use Illuminate\Support\Str;

final class Cards implements CardsContract
{
    public function __construct(
        private readonly Http      $http,
        private readonly Environment $env
    ) {}

    public function charge(
        float    $amount,
        Card     $card,
        string   $transactionRef,
        Currency $currency = Currency::USD,
        ?string  $accountName = null,
        string   $authType = '3DS'
    ): TransactionStatus {

        $payload = [
            'service_payload' => [
                'amount'               => $amount,
                'currency'             => $currency->value,
                'transaction_reference'=> $transactionRef,
                'card' => [
                    'expiryMonth'  => $card->expiryMonth,
                    'expiryYear'   => $card->expiryYear,
                    'securityCode' => $card->cvc,
                    'cardNumber'   => $card->number,
                ],
            ],
        ];

        if ($accountName) {
            [$first, $last] = array_pad(explode(' ', $accountName, 2), 2, '');
            $payload['service_payload']['first_name'] = $first;
            $payload['service_payload']['last_name']  = $last;
        }

        $endpoint = $authType === '2DS'
            ? '/cards/mpgs/v1/2ds/card_charge'
            : '/card_charge/';

        $response = $this->http->post(
            "{$this->baseUrl()}{$endpoint}",
            $payload
        );

        if (! $response->successful()) {
            throw new PayazaException(
                message: $response->json('message', 'Charge failed'),
                code: $response->status()
            );
        }

        return new TransactionStatus(
            transactionId: $transactionRef,
            state:         $this->mapStatus($response->json('transaction.transaction_status') ?? 'PENDING'),
            raw:           $response->json()
        );
    }

    public function status(string $transactionRef): TransactionStatus
    {
        $response = $this->http->post(
            $this->baseUrl() . '/card/card_charge/transaction_status',
            ['service_payload' => ['transaction_reference' => $transactionRef]]
        );

        if (! $response->successful()) {
            throw new PayazaException('Unable to fetch transaction status', $response->status());
        }

        return new TransactionStatus(
            transactionId: $transactionRef,
            state:         $this->mapStatus($response->json('response_content.transaction_status')),
            raw:           $response->json()
        );
    }

    public function refund(string $transactionRef, float $amount): bool
    {
        $response = $this->http->post(
            $this->baseUrl() . '/card/refund',
            [
                'service_payload' => [
                    'transaction_reference' => $transactionRef,
                    'amount' => $amount,
                ]
            ]
        );

        if (! $response->successful()) {
            throw new PayazaException('Refund failed', $response->status());
        }

        return $response->json('status') === 'success';
    }

    public function refundStatus(string $refundTransactionRef): TransactionStatus
    {
        $response = $this->http->post(
            $this->baseUrl() . '/card/refund/status',
            ['service_payload' => ['transaction_reference' => $refundTransactionRef]]
        );

        if (! $response->successful()) {
            throw new PayazaException('Unable to fetch refund status', $response->status());
        }

        return new TransactionStatus(
            transactionId: $refundTransactionRef,
            state:         $this->mapStatus($response->json('response_content.transaction_status')),
            raw:           $response->json()
        );
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