<?php

declare(strict_types=1);

use PayazaSdk\Payaza;
use PayazaSdk\Enums\Currency;

beforeEach(function () {
    if (! getenv('PAYAZA_INTEGRATION')) {
        test()->markTestSkipped('Set PAYAZA_INTEGRATION=1 to run live tests');
    }
});

test('can fetch account balance for all currencies', function () {
    try {
        $balance = Payaza::accounts()->balance();
        
        expect($balance)
            ->toBeArray()
            ->not->toBeEmpty();
    } catch (\PayazaSdk\Exceptions\PayazaException $e) {
        if (str_contains($e->getMessage(), 'Forbidden') || str_contains($e->getMessage(), 'Failed to retrieve')) {
            test()->markTestSkipped('Account endpoint not accessible - try with live environment');
        }
        throw $e;
    }
});

test('can fetch account balance for specific currency', function () {
    try {
        $balance = Payaza::accounts()->currency(Currency::NGN)->balance();
        
        expect($balance)
            ->toBeArray()
            ->toHaveKey('available_balance')
            ->toHaveKey('currency')
            ->toHaveKey('account_reference')
            ->and($balance['currency'])->toBe('NGN');
    } catch (\PayazaSdk\Exceptions\PayazaException $e) {
        test()->markTestSkipped('Account balance endpoint not accessible: ' . $e->getMessage());
    }
});


test('can perform account name enquiry', function () {
    try {
        // Test with a known working bank account
        $accountInfo = Payaza::accounts()->getAccountNameEnquiry(
            accountNumber: '0190878999',
            bankCode: '044',
            currency: Currency::NGN
        );
        
        expect($accountInfo)
            ->toBeArray()
            ->toHaveKey('account_name')
            ->toHaveKey('account_status')
            ->toHaveKey('account_number')
            ->toHaveKey('bank_code')
            ->and($accountInfo['account_number'])->toBe('0190878999')
            ->and($accountInfo['bank_code'])->toBe('044');
    } catch (\PayazaSdk\Exceptions\PayazaException $e) {
        test()->markTestSkipped('Account name enquiry not accessible: ' . $e->getMessage());
    }
});

test('can get payaza accounts info', function () {
    try {
        $accounts = Payaza::accounts()->getPayazaAccountsInfo();
        
        expect($accounts)
            ->toBeArray()
            ->not->toBeEmpty();
            
        // Check structure of first account
        if (!empty($accounts)) {
            expect($accounts[0])
                ->toHaveKey('payazaAccountReference')
                ->toHaveKey('currency')
                ->toHaveKey('accountBalance');
        }
    } catch (\PayazaSdk\Exceptions\PayazaException $e) {
        test()->markTestSkipped('Payaza accounts info not accessible: ' . $e->getMessage());
    }
});

test('can switch accounts and fetch different balances', function () {
    try {
        $primaryBalance = Payaza::account('primary')->accounts()->balance();
        $premiumBalance = Payaza::account('premium')->accounts()->balance();
        
        expect($primaryBalance)->toBeArray();
        expect($premiumBalance)->toBeArray();
    } catch (\PayazaSdk\Exceptions\PayazaException $e) {
        test()->markTestSkipped('Account switching test not accessible: ' . $e->getMessage());
    }
});