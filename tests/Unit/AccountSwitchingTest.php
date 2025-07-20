<?php

declare(strict_types=1);

use PayazaSdk\{PayazaServiceProvider, Payaza, PayazaClient};
use Illuminate\Support\Facades\Http;

uses(\Orchestra\Testbench\TestCase::class);

it('provides package providers', function () {
    return [PayazaServiceProvider::class];
})->provides('getPackageProviders');

beforeEach(function () {
    config([
        'payaza.accounts' => [
            'primary' => ['key' => 'primary-key-123'],
            'premium' => ['key' => 'premium-key-456'],
        ],
        'payaza.default_account' => 'primary',
        'payaza.environment' => 'test',
    ]);
});

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
        '*' => Http::response([
            'data' => ['available_balance' => 1000.00, 'currency' => 'USD'],
        ], 200),
    ]);

    $primaryBalance = Payaza::account('primary')->accounts()->balance();
    $premiumBalance = Payaza::account('premium')->accounts()->balance();

    expect($primaryBalance)->toBeArray();
    expect($premiumBalance)->toBeArray();
});

test('default facade uses configured default account', function () {
    Http::fake([
        '*' => Http::response([
            'data' => ['available_balance' => 500.00, 'currency' => 'NGN'],
        ], 200),
    ]);

    // This should use the primary account as configured in default_account
    $balance = Payaza::accounts()->balance();

    expect($balance)->toBeArray()
        ->and($balance['available_balance'])->toBe(500.00);
});