# Payaza PHP SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/your-vendor/payaza-sdk.svg?style=flat-square)](https://packagist.org/packages/your-vendor/payaza-sdk)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/your-vendor/payaza-sdk/run-tests?label=tests)](https://github.com/your-vendor/payaza-sdk/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/your-vendor/payaza-sdk/Check%20&%20fix%20styling?label=code%20style)](https://github.com/your-vendor/payaza-sdk/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/your-vendor/payaza-sdk.svg?style=flat-square)](https://packagist.org/packages/your-vendor/payaza-sdk)

A production-ready Laravel PHP SDK that wraps the entire Payaza REST API behind an expressive, type-safe façade. Everything is namespaced, strictly typed (declare(strict_types=1)), PSR-12 compliant, and fully unit-tested with PHPUnit.

## Features

- 🔒 **Type-safe**: Built with PHP 8.2+ features including enums, readonly classes, and strict typing
- 🚀 **Laravel Integration**: Auto-discovery, service provider, facade, and configuration publishing
- 🧪 **Fully Tested**: Comprehensive unit and integration tests with HTTP mocking
- 📦 **PSR Compliant**: Follows PSR-12 coding standards and PSR-4 autoloading
- 🛡️ **Error Handling**: Centralized exception handling with meaningful error messages
- 🔧 **Configurable**: Environment-based configuration with sensible defaults
- 📖 **Well Documented**: Extensive PHPDoc annotations and usage examples

## Installation

You can install the package via composer:

```bash
composer require gbxnga/payaza-laravel-sdk
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=payaza-config
```

## Configuration

Add your Payaza credentials to your `.env` file:

```env
PAYAZA_PUBLIC_KEY=your_base64_encoded_public_key
PAYAZA_PREMIUM_PUBLIC_KEY=your_premium_public_key
PAYAZA_DEFAULT_ACCOUNT=primary # Default account to use
PAYAZA_ENV=test # or 'live' for production
PAYAZA_BASE_URL=https://api.payaza.africa
```

## Usage

### Multiple Account Support

The SDK supports multiple Payaza accounts with easy switching:

```php
use PayazaSdk\Payaza;

// Use default account (configured in PAYAZA_DEFAULT_ACCOUNT)
$balance = Payaza::accounts()->balance();

// Switch to a specific account
$premiumBalance = Payaza::account('premium')->accounts()->balance();
$primaryCharge = Payaza::account('primary')->cards()->charge($amount, $card, $ref);
```

### Card Charges

```php
use PayazaSdk\Payaza;
use PayazaSdk\Data\Card;
use PayazaSdk\Enums\Currency;

// Charge a card
$status = Payaza::cards()->charge(
    amount: 150.00,
    card: new Card('4242424242424242', 7, 2026, '123'),
    transactionRef: 'ORDER-123',
    currency: Currency::USD,
    accountName: 'John Doe',
    authType: '3DS' // or '2DS'
);

// Check transaction status
$transactionStatus = Payaza::cards()->status('ORDER-123');

if ($transactionStatus->state === TransactionState::SUCCESSFUL) {
    // Payment was successful
    echo "Payment completed: {$transactionStatus->transactionId}";
}

// Process refund
$refundSuccess = Payaza::cards()->refund('ORDER-123', 50.00);

// Check refund status
$refundStatus = Payaza::cards()->refundStatus('REFUND-123');
```

### Payouts

```php
use PayazaSdk\Payaza;
use PayazaSdk\Data\PayoutBeneficiary;
use PayazaSdk\Enums\Currency;

// Create a payout
$beneficiary = new PayoutBeneficiary(
    accountName: 'Jane Smith',
    accountNumber: '0123456789',
    bankCode: '044', // Access Bank
    amount: 500.00,
    currency: Currency::NGN,
    narration: 'Salary payment'
);

$status = Payaza::payouts()->send($beneficiary, 'PAYOUT-456');

// Check payout status
$payoutStatus = Payaza::payouts()->status('PAYOUT-456');

// Get list of supported banks
$banks = Payaza::payouts()->getBanks('NG'); // Nigeria banks
```

### Account Information

```php
use PayazaSdk\Payaza;

// Get account balance
$balance = Payaza::accounts()->balance();
echo "Available balance: {$balance['available_balance']} {$balance['currency']}";

// Get transaction history
$transactions = Payaza::accounts()->transactions(page: 1, limit: 20);

// Get specific transaction
$transaction = Payaza::accounts()->transaction('TXN-789');
```

## Data Objects

The SDK uses readonly data objects for type safety:

### Card

```php
use PayazaSdk\Data\Card;

$card = new Card(
    number: '4242424242424242',
    expiryMonth: 12,
    expiryYear: 2027,
    cvc: '123'
);
```

### PayoutBeneficiary

```php
use PayazaSdk\Data\PayoutBeneficiary;
use PayazaSdk\Enums\Currency;

$beneficiary = new PayoutBeneficiary(
    accountName: 'John Doe',
    accountNumber: '0123456789',
    bankCode: '044',
    amount: 1000.00,
    currency: Currency::NGN,
    narration: 'Monthly stipend' // optional
);
```

### TransactionStatus

```php
use PayazaSdk\Data\TransactionStatus;
use PayazaSdk\Enums\TransactionState;

// Returned from charge, status, and other operations
$status = new TransactionStatus(
    transactionId: 'TXN-123',
    state: TransactionState::SUCCESSFUL,
    raw: [] // Full API response
);
```

## Enums

### Currency

```php
use PayazaSdk\Enums\Currency;

Currency::USD; // US Dollar
Currency::NGN; // Nigerian Naira
Currency::GHS; // Ghanaian Cedi
Currency::XOF; // West African CFA Franc
Currency::KES; // Kenyan Shilling
Currency::UGX; // Ugandan Shilling
Currency::TZS; // Tanzanian Shilling
```

### TransactionState

```php
use PayazaSdk\Enums\TransactionState;

TransactionState::PENDING;     // Transaction initiated
TransactionState::PROCESSING;  // Transaction in progress
TransactionState::SUCCESSFUL;  // Transaction completed
TransactionState::FAILED;      // Transaction failed
```

### Environment

```php
use PayazaSdk\Enums\Environment;

Environment::TEST; // Test/sandbox environment
Environment::LIVE; // Production environment
```

## Error Handling

The SDK throws `PayazaException` for API errors:

```php
use PayazaSdk\Exceptions\PayazaException;

try {
    $status = Payaza::cards()->charge($amount, $card, $ref);
} catch (PayazaException $e) {
    echo "Payaza error: {$e->getMessage()} (Code: {$e->getCode()})";
}
```

## Testing

### Running Unit Tests

This SDK uses [PestPHP](https://pestphp.com) for testing:

```bash
composer test
```

### Integration Tests

Set up integration testing with live API calls:

```bash
# Set environment variable
export PAYAZA_INTEGRATION=1

# Run tests
composer test
```

**Note**: Integration tests require valid API credentials and will make actual API calls.

### Test Structure

- **Unit Tests**: Located in `tests/Unit/` - Mock HTTP responses for isolated testing
- **Feature Tests**: Located in `tests/Feature/` - Optional integration tests with live API

## Advanced Usage

### Custom HTTP Client

```php
use Illuminate\Http\Client\Factory;
use PayazaSdk\PayazaClient;
use PayazaSdk\Enums\Environment;

$httpClient = new Factory();
$httpClient->timeout(30); // Custom timeout

$client = new PayazaClient(
    token: base64_encode('your-api-key'),
    env: Environment::TEST,
    http: $httpClient
);

$cards = $client->cards();
```

### Direct Client Usage

```php
use PayazaSdk\PayazaClient;
use PayazaSdk\Enums\Environment;

// Without facade
$client = new PayazaClient(
    base64_encode(config('payaza.primary_public_key')),
    Environment::TEST
);

$status = $client->cards()->charge($amount, $card, $ref);
```

## Configuration

The `config/payaza.php` file contains:

```php
return [
    // Multiple Accounts Configuration
    'accounts' => [
        'primary' => [
            'key' => env('PAYAZA_PUBLIC_KEY'),
        ],
        'premium' => [
            'key' => env('PAYAZA_PREMIUM_PUBLIC_KEY'),
        ],
    ],
    
    // Default Account
    'default_account' => env('PAYAZA_DEFAULT_ACCOUNT', 'primary'),

    // Environment and URLs
    'environment' => env('PAYAZA_ENV', 'test'), // 'test' or 'live'
    'base_url'    => env('PAYAZA_BASE_URL', 'https://api.payaza.africa'),

    // HTTP timeout in seconds
    'timeout' => 24,
];
```

## Requirements

- PHP 8.2 or higher
- Laravel 10.0 or 11.0
- Guzzle HTTP 7.8+

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email security@yourcompany.com instead of using the issue tracker.

## Credits

- [Your Name](https://github.com/your-username)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

---

## API Coverage

This SDK provides complete coverage of the Payaza API:

### Cards
- ✅ Card charging (3DS/2DS)
- ✅ Transaction status checking
- ✅ Refunds
- ✅ Refund status checking

### Payouts
- ✅ Bank transfers
- ✅ Payout status checking
- ✅ Bank list retrieval

### Account
- ✅ Balance checking
- ✅ Transaction history
- ✅ Single transaction retrieval

## Support

For support, email support@yourcompany.com or join our Slack channel.

## Roadmap

- [ ] Webhook signature verification
- [ ] Bulk operations
- [ ] Rate limiting helpers
- [ ] Laravel Notification channel
- [ ] Artisan commands for common operations