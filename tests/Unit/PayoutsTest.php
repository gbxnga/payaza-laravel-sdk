<?php

declare(strict_types=1);

use PayazaSdk\{PayazaServiceProvider, Payaza};
use PayazaSdk\Data\{PayoutBeneficiary, TransactionStatus};
use PayazaSdk\Enums\{Currency, TransactionState};
use Illuminate\Support\Facades\Http;

uses(\Orchestra\Testbench\TestCase::class);

it('provides package providers', function () {
    return [PayazaServiceProvider::class];
})->provides('getPackageProviders');

test('sends payout successfully', function () {
    Http::fake([
        '*' => Http::response([
            'response_content' => ['transaction_status' => 'processing'],
        ], 200),
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
    Http::fake([
        '*' => Http::response([
            'response_content' => ['transaction_status' => 'completed'],
        ], 200),
    ]);

    $status = Payaza::payouts()->status('PAYOUT123');

    expect($status)
        ->toBeInstanceOf(TransactionStatus::class)
        ->and($status->state)->toBe(TransactionState::SUCCESSFUL);
});

test('fetches banks list', function () {
    Http::fake([
        '*' => Http::response([
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