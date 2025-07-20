<?php

declare(strict_types=1);

namespace PayazaSdk\Resources;

use Illuminate\Http\Client\Factory as Http;
use PayazaSdk\Contracts\Resources\CardsContract;
use PayazaSdk\Data\{Card, TransactionStatus};
use PayazaSdk\Enums\{Currency, Environment, TransactionState};
use PayazaSdk\Exceptions\PayazaException;
use PayazaSdk\Traits\ResolvesUrls;
use Illuminate\Support\Str;

final class Cards implements CardsContract
{
    use ResolvesUrls;
    
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
        string   $authType = '3DS',
        ?string  $callbackUrl = null
    ): TransactionStatus {

        $payload = [
            'service_type' => 'Account',
            'service_payload' => [
                'request_application' => 'Payaza',
                'application_module' => 'USER_MODULE',
                'application_version' => '1.0.0',
                'request_class' => 'UsdCardChargeRequest',
                'phone_number' => '08012345678',
                'amount' => $amount,
                'transaction_reference' => $transactionRef,
                'currency' => $currency->value,
                'description' => 'Payment via Payaza SDK',
                'card' => [
                    'expiryMonth' => $card->expiryMonth,
                    'expiryYear' => $card->expiryYear,
                    'securityCode' => $card->cvc,
                    'cardNumber' => $card->number,
                ],
                'callback_url' => $callbackUrl ?? (config('app.url') . "/api/transaction/{$transactionRef}/webhooks/payaza")
            ],
        ];

        if ($accountName) {
            [$first, $last] = array_pad(explode(' ', $accountName, 2), 2, '');
            $payload['service_payload']['first_name'] = $first;
            $payload['service_payload']['last_name'] = $last;
        }

        $endpoint = $authType === '2DS'
            ? $this->resolveUrl('card_charge_2ds')
            : $this->resolveUrl('card_charge_3ds');

        try {
            $response = $this->http->timeout(24)->post($endpoint, $payload);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new PayazaException('Connection timeout - card issuer not responding');
        }

        if (!$response->successful()) {
            $message = $response->json('message', $response->json('debugMessage', 'Charge failed'));
            throw new PayazaException($message, $response->status());
        }

        $responseData = $response->json();
        
        // Handle 2DS vs 3DS response format
        if ($authType === '2DS') {
            $responseData = $responseData['response_content'] ?? $responseData;
        }

        $transactionStatus = 'PENDING';
        if (!($responseData['do3dsAuth'] ?? true) && isset($responseData['transaction'])) {
            $transactionStatus = $responseData['transaction']['transaction_status'] ?? 'PENDING';
            if (!in_array($transactionStatus, ['SUCCESSFUL', 'FAILED'])) {
                $transactionStatus = 'PENDING';
            }
        }

        return new TransactionStatus(
            transactionId: $transactionRef,
            state: $this->mapStatus($transactionStatus),
            raw: $response->json()
        );
    }

    public function status(string $transactionRef): TransactionStatus
    {
        try {
            $response = $this->http->withHeaders([
                'x-api-key' => 'P5ooumv6U29K55guZfGqB1fYw904ZUz8gAg0TI36',
                'x-TenantID' => $this->getTenantId()
            ])->timeout(24)->post(
                $this->resolveUrl('card_status'),
                ['service_payload' => ['transaction_reference' => $transactionRef]]
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new PayazaException('Connection timeout');
        }

        if (!$response->successful() && !isset($response->json()['response_content']['transaction_status'])) {
            $message = $response->json('message', 'Failed to get transaction status');
            throw new PayazaException($message, $response->status());
        }

        $responseContent = $response->json('response_content', []);
        $status = $responseContent['transaction_status'] ?? 'pending';
        
        return new TransactionStatus(
            transactionId: $transactionRef,
            state: $this->mapStatus($status),
            raw: $response->json()
        );
    }

    public function refund(string $transactionRef, float $amount): bool
    {
        try {
            $response = $this->http->timeout(24)->post(
                $this->resolveUrl('card_refund'),
                [
                    'service_payload' => [
                        'transaction_reference' => $transactionRef,
                        'refund_amount' => $amount
                    ]
                ]
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new PayazaException('Connection timeout - refund service not responding');
        }

        if (!$response->successful()) {
            $message = $response->json('message', 'Refund failed');
            throw new PayazaException($message, $response->status());
        }

        return true; // Refund initiated successfully
    }

    public function refundStatus(string $refundTransactionRef): TransactionStatus
    {
        try {
            $response = $this->http->timeout(24)->post(
                $this->resolveUrl('card_refund_status'),
                ['service_payload' => ['refund_transaction_reference' => $refundTransactionRef]]
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new PayazaException('Connection timeout - refund status service not responding');
        }

        if (!$response->successful()) {
            $message = $response->json('message', 'Failed to fetch refund status');
            throw new PayazaException($message, $response->status());
        }

        return new TransactionStatus(
            transactionId: $refundTransactionRef,
            state: $this->mapStatus($response->json('response_content.transaction_status', 'pending')),
            raw: $response->json()
        );
    }

    // Note: Card endpoints use different base URLs than other services
    private function baseUrl(): string
    {
        return $this->env === Environment::LIVE 
            ? 'https://cards-live.78financials.com'
            : 'https://cards-live.78financials.com'; // Use live for both for now
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