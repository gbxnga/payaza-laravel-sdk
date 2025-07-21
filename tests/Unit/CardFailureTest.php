<?php

declare(strict_types=1);

use PayazaSdk\Payaza;
use PayazaSdk\Data\Card;
use PayazaSdk\Enums\{Currency, TransactionState};
use PayazaSdk\Exceptions\PayazaException;
use Illuminate\Support\Facades\Http;

test('throws exception for invalid credentials (API error)', function () {
    Http::fake([
        '*' => Http::response([
            "statusOk" => false,
            "message" => "Transaction Failed",
            "debugMessage" => "Invalid credentials.",
            "waitForNotification" => false,
            "do3dsAuth" => false,
            "paymentCompleted" => false,
            "amountPaid" => 0,
            "valueAmount" => 0
        ], 400)
    ]);

    expect(fn() => Payaza::cards()->charge(
        100.00,
        new Card('4242424242424242', '12', '25', '123'),
        'FAIL-TEST-123',
        Currency::USD
    ))->toThrow(PayazaException::class, 'Transaction Failed');
});

test('returns FAILED status for transaction failures (insufficient funds)', function () {
    Http::fake([
        '*' => Http::response([
            "statusOk" => false,
            "message" => "Transaction Failed",
            "debugMessage" => "Insufficient funds",
            "waitForNotification" => false,
            "do3dsAuth" => false,
            "paymentCompleted" => false,
            "amountPaid" => 0,
            "valueAmount" => 0
        ], 400)
    ]);

    $result = Payaza::cards()->charge(
        100.00,
        new Card('4242424242424242', '12', '25', '123'),
        'INSUFFICIENT-123',
        Currency::USD
    );

    expect($result->state)->toBe(TransactionState::FAILED);
    expect($result->raw['debugMessage'])->toBe('Insufficient funds');
});

test('returns FAILED status for card declined', function () {
    Http::fake([
        '*' => Http::response([
            "statusOk" => false,
            "message" => "Transaction Failed",
            "debugMessage" => "Card declined by issuer",
            "waitForNotification" => false,
            "do3dsAuth" => false,
            "paymentCompleted" => false,
            "amountPaid" => 0,
            "valueAmount" => 0
        ], 400)
    ]);

    $result = Payaza::cards()->charge(
        100.00,
        new Card('4242424242424242', '12', '25', '123'),
        'DECLINED-123',
        Currency::USD
    );

    expect($result->state)->toBe(TransactionState::FAILED);
    expect($result->raw['debugMessage'])->toBe('Card declined by issuer');
});

test('still throws exception for 500 server errors', function () {
    Http::fake([
        '*' => Http::response([
            "error" => "Internal server error",
            "message" => "Database connection failed"
        ], 500)
    ]);

    expect(fn() => Payaza::cards()->charge(
        100.00,
        new Card('4242424242424242', '12', '25', '123'),
        'ERROR-TEST-123',
        Currency::USD
    ))->toThrow(PayazaException::class, 'Database connection failed');
});

test('still throws exception for 401 authentication errors', function () {
    Http::fake([
        '*' => Http::response([
            "error" => "Unauthorized",
            "message" => "API key not found"
        ], 401)
    ]);

    expect(fn() => Payaza::cards()->charge(
        100.00,
        new Card('4242424242424242', '12', '25', '123'),
        'AUTH-ERROR-123',
        Currency::USD
    ))->toThrow(PayazaException::class, 'API key not found');
});