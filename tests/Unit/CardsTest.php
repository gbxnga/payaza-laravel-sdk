<?php

declare(strict_types=1);

use PayazaSdk\{PayazaServiceProvider, Payaza};
use PayazaSdk\Data\{Card, TransactionStatus};
use PayazaSdk\Enums\{Currency, TransactionState};
use Illuminate\Support\Facades\Http;

uses(\Orchestra\Testbench\TestCase::class);

it('provides package providers', function () {
    return [PayazaServiceProvider::class];
})->provides('getPackageProviders');

test('charges a card successfully', function () {
    fakeSuccessfulChargeResponse();

    $status = Payaza::cards()->charge(
        amount:           100,
        card:             new Card('4242424242424242', 12, 2027, '123'),
        transactionRef:   'TEST123',
        currency:         Currency::USD
    );

    expect($status)
        ->toBeInstanceOf(TransactionStatus::class)
        ->and($status->state)->toBe(TransactionState::PENDING)
        ->and($status->transactionId)->toBe('TEST123');
});

test('fetches transaction status', function () {
    Http::fake([
        '*' => Http::response([
            'response_content' => ['transaction_status' => 'successful'],
        ], 200),
    ]);

    $status = Payaza::cards()->status('TEST123');

    expect($status)
        ->toBeInstanceOf(TransactionStatus::class)
        ->and($status->state)->toBe(TransactionState::SUCCESSFUL);
});

test('processes refunds', function () {
    Http::fake([
        '*' => Http::response(['status' => 'success'], 200),
    ]);

    $result = Payaza::cards()->refund('TEST123', 50.0);

    expect($result)->toBeTrue();
});

function fakeSuccessfulChargeResponse(): void
{
    Http::fake([
        '*' => Http::response([
            'do3dsAuth'  => true,
            'transaction'=> ['transaction_status' => 'pending'],
        ], 200),
    ]);
}