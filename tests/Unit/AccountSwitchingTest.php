<?php

declare(strict_types=1);

use PayazaSdk\{Payaza, PayazaClient};
use Illuminate\Support\Facades\Http;



test('can switch between accounts', function () {
    $primaryClient = Payaza::account('primary');
    $premiumClient = Payaza::account('premium');

    expect($primaryClient)->toBeInstanceOf(PayazaClient::class);
    expect($premiumClient)->toBeInstanceOf(PayazaClient::class);
});

test('throws exception for invalid account', function () {
    expect(fn() => Payaza::account('invalid'))
        ->toThrow(InvalidArgumentException::class, "Account 'invalid' not found in configuration");
});

test('throws exception for account with empty key', function () {
    config(['payaza.accounts.empty' => ['key' => '']]);
    
    expect(fn() => Payaza::account('empty'))
        ->toThrow(InvalidArgumentException::class, "API key for account 'empty' is not configured");
});

test('can use different accounts for operations', function () {
    Http::fake([
        'https://api.payaza.africa/test/payaza-account/api/v1/mainaccounts/merchant/enquiry/main' => Http::response([
            "message" => "Account enquiry response",
            "status" => true,
            "data" => [
                [
                    "name" => "Primary Account",
                    "payazaAccountReference" => "1010000000",
                    "status" => "ACTIVE",
                    "accountBalance" => 990.13,
                    "currency" => "NGN",
                    "country" => "NGA",
                ]
            ]
        ], 200),
    ]);

    $primaryBalance = Payaza::account('primary')->accounts()->balance();
    $premiumBalance = Payaza::account('premium')->accounts()->balance();

    expect($primaryBalance)->toBeArray()->toHaveCount(1);
    expect($premiumBalance)->toBeArray()->toHaveCount(1);
});

test('can perform different operations with different accounts', function () {
    Http::fake([
        // Mock account info
        'https://api.payaza.africa/test/payaza-account/api/v1/mainaccounts/merchant/enquiry/main' => Http::response([
            "message" => "Account enquiry response",
            "status" => true,
            "data" => [
                [
                    "payazaAccountReference" => "1010000000",
                    "currency" => "NGN",
                    "accountBalance" => 1000.00
                ]
            ]
        ], 200),
        // Mock payout
        'https://api.payaza.africa/test/payout-receptor/payout' => Http::response([
            "response_code" => 200,
            "response_content" => [
                "response_status" => "TRANSACTION_INITIATED"
            ]
        ], 200),
        // Mock card charge
        'https://cards-live.78financials.com/card_charge/' => Http::response([
            'do3dsAuth' => false,
            'transaction' => ['transaction_status' => 'successful']
        ], 200)
    ]);

    // Use primary account for payout
    $beneficiary = new \PayazaSdk\Data\PayoutBeneficiary(
        accountName: 'John Doe',
        accountNumber: '1234567890', 
        bankCode: '044',
        amount: 100.0,
        currency: \PayazaSdk\Enums\Currency::NGN
    );
    
    $payoutStatus = Payaza::account('primary')->payouts()->send($beneficiary, 'PAYOUT-123');
    
    // Use premium account for card charge
    $card = new \PayazaSdk\Data\Card('4242424242424242', 12, 2027, '123');
    $chargeStatus = Payaza::account('premium')->cards()->charge(
        amount: 50.0,
        card: $card,
        transactionRef: 'CHARGE-123',
        currency: \PayazaSdk\Enums\Currency::USD
    );

    expect($payoutStatus)->toBeInstanceOf(\PayazaSdk\Data\TransactionStatus::class);
    expect($chargeStatus)->toBeInstanceOf(\PayazaSdk\Data\TransactionStatus::class);
});

test('default facade uses configured default account', function () {
    Http::fake([
        'https://api.payaza.africa/test/payaza-account/api/v1/mainaccounts/merchant/enquiry/main' => Http::response([
            "message" => "Account enquiry response",
            "status" => true,
            "data" => [
                [
                    "payazaAccountReference" => "1010000000",
                    "accountBalance" => 500.00,
                    "currency" => "NGN"
                ]
            ]
        ], 200),
    ]);

    // This should use the primary account as configured in default_account
    $balance = Payaza::accounts()->balance();

    expect($balance)->toBeArray()->toHaveCount(1)
        ->and($balance[0]['accountBalance'])->toBe(500);
});

test('can chain operations with account switching', function () {
    Http::fake([
        'https://api.payaza.africa/test/payaza-account/api/v1/mainaccounts/merchant/enquiry/main' => Http::response([
            "message" => "Account enquiry response",
            "status" => true,
            "data" => [
                [
                    "payazaAccountReference" => "1010000000",
                    "accountBalance" => 1500.00,
                    "currency" => "NGN"
                ]
            ]
        ], 200),
    ]);

    // Chain currency filtering with account switching
    $ngnBalance = Payaza::account('premium')->accounts()->currency(\PayazaSdk\Enums\Currency::NGN)->balance();

    expect($ngnBalance)
        ->toBeArray()
        ->and($ngnBalance['available_balance'])->toBe(1500)
        ->and($ngnBalance['currency'])->toBe('NGN');
});