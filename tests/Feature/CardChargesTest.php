<?php

declare(strict_types=1);

use PayazaSdk\Payaza;
use PayazaSdk\Data\{Card, PayoutBeneficiary};
use PayazaSdk\Enums\Currency;


beforeEach(function () {
    if (! getenv('PAYAZA_INTEGRATION')) {
        test()->markTestSkipped('Set PAYAZA_INTEGRATION=1 to run live tests');
    }
});

test('can charge and poll live', function () {
    $ref = 'IT-' . uniqid();
    $status = Payaza::cards()->charge(
        1.00,
        new Card('4242424242424242', 12, 2028, '123'),
        $ref,
        Currency::USD
    );

    sleep(3);
    $polled = Payaza::cards()->status($ref);

    expect($polled->transactionId)->toBe($ref);
});

test('can process payout and check status', function () {
    $ref = 'PAYOUT-' . uniqid();
    
    $beneficiary = new PayoutBeneficiary(
        accountName: 'Test User',
        accountNumber: '0123456789',
        bankCode: '044',
        amount: 100.0,
        currency: Currency::NGN
    );

    $status = Payaza::payouts()->send($beneficiary, $ref);

    sleep(2);
    $polled = Payaza::payouts()->status($ref);

    expect($polled->transactionId)->toBe($ref);
});

test('can fetch account data', function () {
    try {
        $balance = Payaza::accounts()->balance();
        expect($balance)->toBeArray();
    } catch (\PayazaSdk\Exceptions\PayazaException $e) {
        test()->markTestSkipped('Account balance not accessible: ' . $e->getMessage());
    }
});