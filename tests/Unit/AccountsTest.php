<?php

declare(strict_types=1);

use PayazaSdk\Payaza;
use Illuminate\Support\Facades\Http;
use PayazaSdk\Enums\Currency;


test('fetches account balance for all currencies', function () {
    Http::fake([
        'https://api.payaza.africa/test/payaza-account/api/v1/mainaccounts/merchant/enquiry/main' => Http::response([
            "message" => "Account enquiry response",
            "status" => true,
            "data" => [
                [
                    "name" => "Test Merchant",
                    "payazaAccountReference" => "1010000000",
                    "status" => "ACTIVE",
                    "accountBalance" => 990.13,
                    "currency" => "NGN",
                    "country" => "NGA",
                ],
                [
                    "name" => "Test Merchant", 
                    "payazaAccountReference" => "3010000000",
                    "status" => "ACTIVE",
                    "accountBalance" => 673.26,
                    "currency" => "GHS",
                    "country" => "GHA",
                ]
            ]
        ], 200),
    ]);

    $balances = Payaza::accounts()->balance();

    expect($balances)
        ->toBeArray()
        ->toHaveCount(2)
        ->and($balances[0]['currency'])->toBe('NGN')
        ->and($balances[0]['accountBalance'])->toBe(990.13);
});

test('fetches account balance for specific currency', function () {
    Http::fake([
        'https://api.payaza.africa/test/payaza-account/api/v1/mainaccounts/merchant/enquiry/main' => Http::response([
            "message" => "Account enquiry response",
            "status" => true,
            "data" => [
                [
                    "name" => "Test Merchant",
                    "payazaAccountReference" => "1010000000", 
                    "status" => "ACTIVE",
                    "accountBalance" => 990.13,
                    "currency" => "NGN",
                    "country" => "NGA",
                    "accountName" => "Test Merchant NGN Account"
                ]
            ]
        ], 200),
    ]);

    $balance = Payaza::accounts()->currency(Currency::NGN)->balance();

    expect($balance)
        ->toBeArray()
        ->and($balance['available_balance'])->toBe(990.13)
        ->and($balance['currency'])->toBe('NGN')
        ->and($balance['account_reference'])->toBe('1010000000');
});


test('performs account name enquiry successfully', function () {
    Http::fake([
        'https://api.payaza.africa/test/payaza-account/api/v1/mainaccounts/merchant/provider/enquiry' => Http::response([
            "response_code" => 200,
            "response_message" => "Approved or completely successful",
            "response_content" => [
                "account_number" => "0190878999",
                "bank_code" => "044",
                "account_name" => "JOHN DOE",
                "account_status" => "ACTIVE",
                "transaction_reference" => 9
            ]
        ], 200)
    ]);

    $accountInfo = Payaza::accounts()->getAccountNameEnquiry(
        accountNumber: '0190878999',
        bankCode: '044',
        currency: Currency::NGN
    );

    expect($accountInfo)
        ->toBeArray()
        ->and($accountInfo['account_name'])->toBe('JOHN DOE')
        ->and($accountInfo['account_status'])->toBe('ACTIVE')
        ->and($accountInfo['account_number'])->toBe('0190878999')
        ->and($accountInfo['bank_code'])->toBe('044');
});

test('handles account name enquiry failure', function () {
    Http::fake([
        'https://api.payaza.africa/test/payaza-account/api/v1/mainaccounts/merchant/provider/enquiry' => Http::response([
            "response_code" => 400,
            "response_message" => "Account not found"
        ], 400)
    ]);

    expect(fn() => Payaza::accounts()->getAccountNameEnquiry('1234567890', '044'))
        ->toThrow(\PayazaSdk\Exceptions\PayazaException::class, 'Account not found');
});

test('gets payaza account info successfully', function () {
    Http::fake([
        'https://api.payaza.africa/test/payaza-account/api/v1/mainaccounts/merchant/enquiry/main' => Http::response([
            "message" => "Account enquiry response",
            "status" => true,
            "data" => [
                [
                    "name" => "Test Merchant",
                    "payazaAccountReference" => "1010000000",
                    "status" => "ACTIVE",
                    "accountBalance" => 990.13,
                    "currency" => "NGN",
                    "country" => "NGA",
                    "hasVirtualAccounts" => true,
                    "virtualAccounts" => [
                        [
                            "accountNumber" => "99926838326",
                            "accountName" => "PAYAZA(Test Merchant)",
                            "bankCode" => "000023",
                            "bankId" => 306
                        ]
                    ]
                ]
            ]
        ], 200)
    ]);

    $accounts = Payaza::accounts()->getPayazaAccountsInfo();

    expect($accounts)
        ->toBeArray()
        ->toHaveCount(1)
        ->and($accounts[0]['payazaAccountReference'])->toBe('1010000000')
        ->and($accounts[0]['currency'])->toBe('NGN')
        ->and($accounts[0]['hasVirtualAccounts'])->toBeTrue();
});