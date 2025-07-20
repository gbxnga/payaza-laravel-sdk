<?php

declare(strict_types=1);

use PayazaSdk\{PayazaServiceProvider, Payaza};
use PayazaSdk\Data\{Card, PayoutBeneficiary};
use PayazaSdk\Enums\Currency;
use PayazaSdk\Exceptions\PayazaException;
use Illuminate\Support\Facades\Http;

uses(\Orchestra\Testbench\TestCase::class);

it('provides package providers', function () {
    return [PayazaServiceProvider::class];
})->provides('getPackageProviders');

test('handles payout transaction status failure', function () {
    Http::fake([
        'https://api.payaza.africa/live/payaza-account/api/v1/mainaccounts/merchant/transaction/FAILED123' => Http::response([
            "status" => false,
            "message" => "Transaction not found"
        ], 404)
    ]);

    expect(fn() => Payaza::payouts()->status('FAILED123'))
        ->toThrow(PayazaException::class, 'Transaction not found');
});

test('handles payout send failure with detailed error message', function () {
    Http::fake([
        // Mock account info first
        'https://api.payaza.africa/live/payaza-account/api/v1/mainaccounts/merchant/enquiry/main' => Http::response([
            "status" => true,
            "data" => [["payazaAccountReference" => "1010000000", "currency" => "NGN"]]
        ], 200),
        // Mock failed payout
        'https://api.payaza.africa/live/payout-receptor/payout' => Http::response([
            "response_code" => 400,
            "response_message" => "Insufficient balance",
            "response_content" => []
        ], 400)
    ]);

    $beneficiary = new PayoutBeneficiary(
        accountName: 'John Doe',
        accountNumber: '1234567890',
        bankCode: '044',
        amount: 10000.0, // Large amount to trigger insufficient balance
        currency: Currency::NGN
    );

    expect(fn() => Payaza::payouts()->send($beneficiary, 'FAIL-PAYOUT-123'))
        ->toThrow(PayazaException::class, 'Insufficient balance');
});

test('handles card charge connection timeout', function () {
    Http::fake([
        'https://cards-live.78financials.com/card_charge/' => Http::response([], 408) // Timeout
    ]);

    $card = new Card('4242424242424242', 12, 2027, '123');

    expect(fn() => Payaza::cards()->charge(
        amount: 100.0,
        card: $card,
        transactionRef: 'TIMEOUT-123',
        currency: Currency::USD
    ))->toThrow(PayazaException::class);
});

test('handles card status API error with message', function () {
    Http::fake([
        'https://api.payaza.africa/live/card/card_charge/transaction_status' => Http::response([
            "message" => "Invalid transaction reference",
            "error_code" => "INVALID_REF"
        ], 400)
    ]);

    expect(fn() => Payaza::cards()->status('INVALID-REF-123'))
        ->toThrow(PayazaException::class, 'Invalid transaction reference');
});

test('handles account enquiry service unavailable', function () {
    Http::fake([
        'https://api.payaza.africa/live/payaza-account/api/v1/mainaccounts/merchant/provider/enquiry' => Http::response([
            "message" => "Service temporarily unavailable"
        ], 503)
    ]);

    expect(fn() => Payaza::accounts()->getAccountNameEnquiry('1234567890', '044'))
        ->toThrow(PayazaException::class, 'Service temporarily unavailable');
});

test('handles missing account reference for currency', function () {
    Http::fake([
        'https://api.payaza.africa/live/payaza-account/api/v1/mainaccounts/merchant/enquiry/main' => Http::response([
            "status" => true,
            "data" => [
                [
                    "currency" => "NGN",
                    "accountBalance" => 100.0
                    // Missing payazaAccountReference
                ]
            ]
        ], 200)
    ]);

    $beneficiary = new PayoutBeneficiary(
        accountName: 'John Doe',
        accountNumber: '1234567890',
        bankCode: '044',
        amount: 100.0,
        currency: Currency::GHS // Different currency than available
    );

    expect(fn() => Payaza::payouts()->send($beneficiary, 'NO-ACCOUNT-123'))
        ->toThrow(PayazaException::class, 'No account found for currency GHS');
});

test('handles malformed response from payaza API', function () {
    Http::fake([
        'https://api.payaza.africa/live/payaza-account/api/v1/mainaccounts/merchant/enquiry/main' => Http::response(
            "Invalid JSON response", // Malformed response
            200,
            ['Content-Type' => 'text/plain']
        )
    ]);

    expect(fn() => Payaza::accounts()->getPayazaAccountsInfo())
        ->toThrow(PayazaException::class);
});

test('handles card refund failure with debug message', function () {
    Http::fake([
        'https://cards-live.78financials.com/card_charge/refund' => Http::response([
            "message" => "Refund failed",
            "debugMessage" => "Transaction cannot be refunded after 90 days"
        ], 400)
    ]);

    expect(fn() => Payaza::cards()->refund('OLD-TXN-123', 50.0))
        ->toThrow(PayazaException::class, 'Refund failed');
});

test('handles network timeout gracefully', function () {
    // Simulate network timeout by not providing any HTTP fake
    // This would cause a connection timeout in real scenarios
    
    $card = new Card('4242424242424242', 12, 2027, '123');

    // The HTTP client should timeout and throw a connection exception
    // which should be caught and converted to PayazaException
    expect(fn() => Payaza::cards()->charge(
        amount: 100.0,
        card: $card,
        transactionRef: 'NETWORK-TIMEOUT-123',
        currency: Currency::USD
    ))->toThrow(PayazaException::class);
});

test('handles invalid currency filter', function () {
    Http::fake([
        'https://api.payaza.africa/live/payaza-account/api/v1/mainaccounts/merchant/enquiry/main' => Http::response([
            "status" => true,
            "data" => [
                ["currency" => "NGN", "accountBalance" => 100.0]
            ]
        ], 200)
    ]);

    // Try to get balance for a currency that doesn't exist in the account
    expect(fn() => Payaza::accounts()->currency(Currency::USD)->balance())
        ->toThrow(PayazaException::class);
});