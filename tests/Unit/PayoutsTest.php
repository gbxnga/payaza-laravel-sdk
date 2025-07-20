<?php

declare(strict_types=1);

use PayazaSdk\Payaza;
use PayazaSdk\Data\{PayoutBeneficiary, TransactionStatus};
use PayazaSdk\Enums\{Currency, TransactionState};
use Illuminate\Support\Facades\Http;


test('sends payout successfully', function () {
    // Mock account info first
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
                ]
            ]
        ], 200),
        'https://api.payaza.africa/test/payout-receptor/payout' => Http::response([
            "response_code" => 200,
            "response_message" => "Request successfully submitted",
            "response_content" => [
                "transaction_status" => "PROCESSING",
                "narration" => "Test payout",
                "transaction_time" => "2023-10-19T14:37:35.517809",
                "amount" => 100.0,
                "response_status" => "TRANSACTION_INITIATED",
                "response_description" => "Transaction has been successfully submitted for processing"
            ],
            "resp_code" => "09"
        ], 200)
    ]);

    $beneficiary = new PayoutBeneficiary(
        accountName: 'John Doe',
        accountNumber: '1234567890',
        bankCode: '044',
        amount: 100.0,
        currency: Currency::NGN,
        narration: 'Test payout'
    );

    $status = Payaza::payouts()->send($beneficiary, 'PAYOUT123');

    expect($status)
        ->toBeInstanceOf(TransactionStatus::class)
        ->and($status->state)->toBe(TransactionState::PROCESSING)
        ->and($status->transactionId)->toBe('PAYOUT123');
});

test('fetches payout status', function () {
    $sessionId = 'ABC123DEF456789';
    
    Http::fake([
        'https://api.payaza.africa/test/payaza-account/api/v1/mainaccounts/merchant/transaction/PAYOUT123' => Http::response([
            "status" => true,
            "message" => "Transaction fetched",
            "data" => [
                "transactionStatus" => "NIP_SUCCESS",
                "responseCode" => "00",
                "sessionId" => $sessionId
            ]
        ], 200),
    ]);

    $status = Payaza::payouts()->status('PAYOUT123');

    expect($status)
        ->toBeInstanceOf(TransactionStatus::class)
        ->and($status->state)->toBe(TransactionState::SUCCESSFUL)
        ->and($status->transactionId)->toBe('PAYOUT123');
});

test('fetches banks list', function () {
    Http::fake([
        'https://api.payaza.africa/payout/banks/NG' => Http::response([
            'data' => [
                ['code' => '044', 'name' => 'Access Bank'],
                ['code' => '011', 'name' => 'First Bank'],
            ],
        ], 200),
    ]);

    $banks = Payaza::payouts()->getBanks('NG');

    expect($banks)
        ->toBeArray()
        ->toHaveCount(2)
        ->and($banks[0]['code'])->toBe('044');
});

test('sends GHS bank transfer successfully', function () {
    // Mock account info for GHS
    Http::fake([
        'https://api.payaza.africa/test/payaza-account/api/v1/mainaccounts/merchant/enquiry/main' => Http::response([
            "message" => "Account enquiry response", 
            "status" => true,
            "data" => [
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
        'https://api.payaza.africa/test/payout-receptor/payout' => Http::response([
            "response_code" => 200,
            "response_message" => "Request successfully submitted",
            "response_content" => [
                "transaction_status" => "PROCESSING",
                "narration" => "GHS Bank Transfer",
                "transaction_time" => "2023-10-19T14:37:35.517809",
                "amount" => 50.0,
                "response_status" => "TRANSACTION_INITIATED",
                "response_description" => "Transaction has been successfully submitted for processing"
            ],
            "resp_code" => "09"
        ], 200)
    ]);

    $status = Payaza::payouts()->sendGHSBankTransfer(
        amount: 50.0,
        accountNumber: '1234567890',
        accountName: 'Jane Doe',
        bankCode: 'GCB',
        transactionRef: 'GHS-BANK-123',
        narration: 'GHS Bank Transfer'
    );

    expect($status)
        ->toBeInstanceOf(TransactionStatus::class)
        ->and($status->state)->toBe(TransactionState::PROCESSING)
        ->and($status->transactionId)->toBe('GHS-BANK-123');
});

test('sends KES mobile money successfully', function () {
    // Mock account info for KES
    Http::fake([
        'https://api.payaza.africa/test/payaza-account/api/v1/mainaccounts/merchant/enquiry/main' => Http::response([
            "message" => "Account enquiry response", 
            "status" => true,
            "data" => [
                [
                    "name" => "Test Merchant",
                    "payazaAccountReference" => "4010000000",
                    "status" => "ACTIVE",
                    "accountBalance" => 10000.0,
                    "currency" => "KES",
                    "country" => "KEN",
                ]
            ]
        ], 200),
        'https://api.payaza.africa/test/payout-receptor/payout' => Http::response([
            "response_code" => 200,
            "response_message" => "Request successfully submitted",
            "response_content" => [
                "transaction_status" => "PROCESSING",
                "narration" => "Mobile Money Payout",
                "transaction_time" => "2023-10-19T14:37:35.517809",
                "amount" => 1000.0,
                "response_status" => "TRANSACTION_INITIATED",
                "response_description" => "Transaction has been successfully submitted for processing"
            ],
            "resp_code" => "09"
        ], 200)
    ]);

    $status = Payaza::payouts()->sendMobileMoney(
        currency: Currency::KES,
        amount: 1000.0,
        phoneNumber: '254700123456',
        accountName: 'John Doe',
        bankCode: 'MPESA',
        transactionRef: 'KES-MOMO-123'
    );

    expect($status)
        ->toBeInstanceOf(TransactionStatus::class)
        ->and($status->state)->toBe(TransactionState::PROCESSING)
        ->and($status->transactionId)->toBe('KES-MOMO-123');
});

test('sends XOF mobile money with country successfully', function () {
    // Mock account info for XOF
    Http::fake([
        'https://api.payaza.africa/test/payaza-account/api/v1/mainaccounts/merchant/enquiry/main' => Http::response([
            "message" => "Account enquiry response", 
            "status" => true,
            "data" => [
                [
                    "name" => "Test Merchant",
                    "payazaAccountReference" => "6010000000",
                    "status" => "ACTIVE",
                    "accountBalance" => 500000.0,
                    "currency" => "XOF",
                    "country" => "SEN",
                ]
            ]
        ], 200),
        'https://api.payaza.africa/test/payout-receptor/payout' => Http::response([
            "response_code" => 200,
            "response_message" => "Request successfully submitted",
            "response_content" => [
                "transaction_status" => "PROCESSING",
                "narration" => "Mobile Money Payout",
                "transaction_time" => "2023-10-19T14:37:35.517809",
                "amount" => 10000.0,
                "response_status" => "TRANSACTION_INITIATED",
                "response_description" => "Transaction has been successfully submitted for processing"
            ],
            "resp_code" => "09"
        ], 200)
    ]);

    $status = Payaza::payouts()->sendMobileMoney(
        currency: Currency::XOF,
        amount: 10000.0,
        phoneNumber: '221701234567',
        accountName: 'Marie Diallo',
        bankCode: 'ORANGE',
        transactionRef: 'XOF-MOMO-123',
        country: 'SEN'
    );

    expect($status)
        ->toBeInstanceOf(TransactionStatus::class)
        ->and($status->state)->toBe(TransactionState::PROCESSING)
        ->and($status->transactionId)->toBe('XOF-MOMO-123');
});