<?php

declare(strict_types=1);

use PayazaSdk\Payaza;
use PayazaSdk\Enums\Currency;
use Illuminate\Support\Facades\Http;

test('handles invalid account gracefully', function () {
    Http::fake([
        'https://api.payaza.africa/test/payaza-account/api/v1/mainaccounts/merchant/provider/enquiry' => Http::response([
            "response_code" => 500,
            "response_message" => "Invalid Account"
        ], 500)
    ]);

    $accountInfo = Payaza::accounts()->getAccountNameEnquiry(
        accountNumber: '9999999999',
        bankCode: '044',
        currency: Currency::NGN
    );

    expect($accountInfo)
        ->toBeArray()
        ->and($accountInfo['account_status'])->toBe('INVALID')
        ->and($accountInfo['account_name'])->toBeNull()
        ->and($accountInfo['account_number'])->toBe('9999999999')
        ->and($accountInfo['bank_code'])->toBe('044')
        ->and($accountInfo['error_message'])->toBe('Invalid Account');
});

test('still throws exception for other server errors', function () {
    Http::fake([
        'https://api.payaza.africa/test/payaza-account/api/v1/mainaccounts/merchant/provider/enquiry' => Http::response([
            "response_code" => 500,
            "response_message" => "Database connection failed"
        ], 500)
    ]);

    expect(fn() => Payaza::accounts()->getAccountNameEnquiry('1234567890', '044'))
        ->toThrow(\PayazaSdk\Exceptions\PayazaException::class, 'Database connection failed');
});

test('handles various invalid account messages', function () {
    Http::fake([
        'https://api.payaza.africa/test/payaza-account/api/v1/mainaccounts/merchant/provider/enquiry' => Http::response([
            "response_code" => 500,
            "response_message" => "INVALID ACCOUNT NUMBER"
        ], 500)
    ]);

    $accountInfo = Payaza::accounts()->getAccountNameEnquiry('0000000000', '044');

    expect($accountInfo['account_status'])->toBe('INVALID');
});