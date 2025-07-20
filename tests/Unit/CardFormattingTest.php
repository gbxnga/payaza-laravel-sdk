<?php

declare(strict_types=1);

use PayazaSdk\Data\Card;

test('formats expiry month with leading zero', function () {
    $card = new Card('4242424242424242', 7, 30, '123');
    
    expect($card->expiryMonth)->toBe('07');
    expect($card->expiryYear)->toBe('30');
});

test('preserves two-digit expiry month', function () {
    $card = new Card('4242424242424242', 12, 25, '123');
    
    expect($card->expiryMonth)->toBe('12');
    expect($card->expiryYear)->toBe('25');
});

test('formats expiry year with leading zero', function () {
    $card = new Card('4242424242424242', 7, 5, '123');
    
    expect($card->expiryMonth)->toBe('07');
    expect($card->expiryYear)->toBe('05');
});

test('accepts string inputs and formats correctly', function () {
    $card = new Card('4242424242424242', '7', '30', '123');
    
    expect($card->expiryMonth)->toBe('07');
    expect($card->expiryYear)->toBe('30');
});

test('handles mixed string and int inputs', function () {
    $card1 = new Card('4242424242424242', '07', 30, '123');
    $card2 = new Card('4242424242424242', 7, '30', '123');
    
    expect($card1->expiryMonth)->toBe('07');
    expect($card1->expiryYear)->toBe('30');
    expect($card2->expiryMonth)->toBe('07');
    expect($card2->expiryYear)->toBe('30');
});