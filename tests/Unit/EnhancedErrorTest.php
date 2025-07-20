<?php

declare(strict_types=1);

use PayazaSdk\Payaza;
use PayazaSdk\Data\Card;
use PayazaSdk\Enums\Currency;
use PayazaSdk\Exceptions\PayazaException;
use Illuminate\Support\Facades\Http;

test('includes API response data in exception message', function () {
    Http::fake([
        '*' => Http::response([
            'statusOk' => false,
            'message' => 'Authentication Failed',
            'debugMessage' => 'Invalid API key provided',
            'errorCode' => 'AUTH_001',
            'timestamp' => '2024-01-01T00:00:00Z'
        ], 403)
    ]);

    try {
        Payaza::cards()->charge(
            100.00,
            new Card('4242424242424242', '12', '25', '123'),
            'TEST-' . uniqid(),
            Currency::USD
        );
        
        expect(false)->toBeTrue('Exception should have been thrown');
    } catch (PayazaException $e) {
        // Check that the exception message includes the API response
        expect($e->getMessage())->toContain('Authentication Failed');
        expect($e->getMessage())->toContain('API Response:');
        expect($e->getMessage())->toContain('"debugMessage": "Invalid API key provided"');
        expect($e->getMessage())->toContain('"errorCode": "AUTH_001"');
        
        // Check that response data is accessible via property
        expect($e->responseData)->toBeArray();
        expect($e->responseData['message'])->toBe('Authentication Failed');
        expect($e->responseData['errorCode'])->toBe('AUTH_001');
    }
});

test('works without response data for connection errors', function () {
    $exception = new PayazaException('Connection timeout');
    
    expect($exception->getMessage())->toBe('Connection timeout');
    expect($exception->responseData)->toBeNull();
});

test('properly formats JSON in exception message', function () {
    $responseData = [
        'error' => 'VALIDATION_ERROR',
        'details' => [
            'field' => 'amount',
            'message' => 'Amount must be greater than 0'
        ]
    ];
    
    $exception = new PayazaException('Validation failed', 400, null, $responseData);
    
    expect($exception->getMessage())->toContain('Validation failed');
    expect($exception->getMessage())->toContain('API Response:');
    expect($exception->getMessage())->toContain('"error": "VALIDATION_ERROR"');
    expect($exception->getMessage())->toContain('"field": "amount"');
    expect($exception->responseData)->toBe($responseData);
});