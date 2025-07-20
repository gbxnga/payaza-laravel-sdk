<?php

declare(strict_types=1);

use PayazaSdk\{PayazaServiceProvider, Payaza};
use Illuminate\Support\Facades\Http;

uses(\Orchestra\Testbench\TestCase::class);

it('provides package providers', function () {
    return [PayazaServiceProvider::class];
})->provides('getPackageProviders');

test('fetches account balance', function () {
    Http::fake([
        '*' => Http::response([
            'data' => [
                'available_balance' => 1500.00,
                'currency' => 'NGN',
            ],
        ], 200),
    ]);

    $balance = Payaza::accounts()->balance();

    expect($balance)
        ->toBeArray()
        ->and($balance['available_balance'])->toBe(1500.00)
        ->and($balance['currency'])->toBe('NGN');
});

test('fetches transactions list', function () {
    Http::fake([
        '*' => Http::response([
            'data' => [
                'transactions' => [
                    ['id' => '1', 'amount' => 100.00],
                    ['id' => '2', 'amount' => 200.00],
                ],
            ],
        ], 200),
    ]);

    $transactions = Payaza::accounts()->transactions(1, 10);

    expect($transactions)
        ->toBeArray()
        ->toHaveKey('transactions')
        ->and($transactions['transactions'])->toHaveCount(2);
});

test('fetches single transaction', function () {
    Http::fake([
        '*' => Http::response([
            'data' => [
                'id' => 'TXN123',
                'amount' => 500.00,
                'status' => 'successful',
            ],
        ], 200),
    ]);

    $transaction = Payaza::accounts()->transaction('TXN123');

    expect($transaction)
        ->toBeArray()
        ->and($transaction['id'])->toBe('TXN123')
        ->and($transaction['amount'])->toBe(500.00);
});