# Payaza PHP SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gbxnga/payaza-laravel-sdk.svg?style=flat-square)](https://packagist.org/packages/gbxnga/payaza-laravel-sdk)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/gbxnga/payaza-laravel-sdk/ci-cd.yml?branch=main&label=tests&style=flat-square)](https://github.com/gbxnga/payaza-laravel-sdk/actions?query=workflow%3Aci-cd+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/gbxnga/payaza-laravel-sdk.svg?style=flat-square)](https://packagist.org/packages/gbxnga/payaza-laravel-sdk)
[![PHP Version Require](https://img.shields.io/packagist/dependency-v/gbxnga/payaza-laravel-sdk/php?style=flat-square)](https://packagist.org/packages/gbxnga/payaza-laravel-sdk)

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

### Environment-Based URL Resolution

The SDK automatically handles test/live environment switching with configurable URLs. URLs can contain a `{tenant}` placeholder that gets replaced with `test` or `live` based on your `PAYAZA_ENV` setting.

### Setup

Add your Payaza credentials to your `.env` file:

```env
PAYAZA_PUBLIC_KEY=your_base64_encoded_public_key
PAYAZA_PREMIUM_PUBLIC_KEY=your_premium_public_key
PAYAZA_DEFAULT_ACCOUNT=primary # Default account to use
PAYAZA_TRANSACTION_PIN=your_transaction_pin # Required for payouts
PAYAZA_ENV=test # or 'live' for production
PAYAZA_BASE_URL=https://api.payaza.africa

# Optional: Override specific API URLs if Payaza changes them
# Use {tenant} placeholder for automatic live/test path injection
PAYAZA_CARD_STATUS_URL=https://api.payaza.africa/{tenant}/card/card_charge/transaction_status
PAYAZA_PAYOUT_URL=https://api.payaza.africa/{tenant}/payout-receptor/payout
PAYAZA_ACCOUNT_ENQUIRY_URL=https://api.payaza.africa/{tenant}/payaza-account/api/v1/mainaccounts/merchant/provider/enquiry
# ... other URLs as needed
```

### How Tenant Resolution Works

When you set `PAYAZA_ENV=live`, URLs like:
- `https://api.payaza.africa/{tenant}/payout-receptor/payout` 
- Become: `https://api.payaza.africa/live/payout-receptor/payout`

When you set `PAYAZA_ENV=test`, they become:
- `https://api.payaza.africa/test/payout-receptor/payout`

This ensures proper environment isolation while maintaining URL flexibility.

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

// Charge a card with custom callback URL
$status = Payaza::cards()->charge(
    amount: 150.00,
    card: new Card('4242424242424242', 7, 2026, '123'),
    transactionRef: 'ORDER-123',
    currency: Currency::USD,
    accountName: 'John Doe',
    authType: '3DS', // or '2DS'
    callbackUrl: 'https://yoursite.com/webhooks/payaza' // Custom callback
);

// Charge with default callback URL
$status = Payaza::cards()->charge(
    amount: 150.00,
    card: new Card('4242424242424242', 7, 2026, '123'),
    transactionRef: 'ORDER-123',
    currency: Currency::USD,
    accountName: 'John Doe'
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

#### NGN Bank Transfer

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

#### GHS Bank Transfer

```php
// GHS bank transfer
$status = Payaza::payouts()->sendGHSBankTransfer(
    amount: 100.00,
    accountNumber: '1234567890',
    accountName: 'John Doe',
    bankCode: 'GCB',
    transactionRef: 'GHS-PAYOUT-123',
    narration: 'GHS Payment'
);
```

#### Mobile Money Payouts

Support for mobile money across African currencies:

```php
use PayazaSdk\Enums\Currency;

// KES Mobile Money (Kenya)
$status = Payaza::payouts()->sendMobileMoney(
    currency: Currency::KES,
    amount: 1000.00,
    phoneNumber: '254700123456',
    accountName: 'John Doe',
    bankCode: 'MPESA',
    transactionRef: 'KES-MOMO-123'
);

// GHS Mobile Money (Ghana)
$status = Payaza::payouts()->sendMobileMoney(
    currency: Currency::GHS,
    amount: 50.00,
    phoneNumber: '233241234567',
    accountName: 'Jane Doe',
    bankCode: 'MTN',
    transactionRef: 'GHS-MOMO-123'
);

// UGX Mobile Money (Uganda)
$status = Payaza::payouts()->sendMobileMoney(
    currency: Currency::UGX,
    amount: 50000.00,
    phoneNumber: '256701234567',
    accountName: 'Bob Smith',
    bankCode: 'AIRTEL',
    transactionRef: 'UGX-MOMO-123'
);

// TZS Mobile Money (Tanzania)
$status = Payaza::payouts()->sendMobileMoney(
    currency: Currency::TZS,
    amount: 25000.00,
    phoneNumber: '255621234567',
    accountName: 'Alice Johnson',
    bankCode: 'VODACOM',
    transactionRef: 'TZS-MOMO-123'
);

// XOF Mobile Money (West Africa - requires country)
$status = Payaza::payouts()->sendMobileMoney(
    currency: Currency::XOF,
    amount: 10000.00,
    phoneNumber: '221701234567',
    accountName: 'Marie Diallo',
    bankCode: 'ORANGE',
    transactionRef: 'XOF-MOMO-123',
    country: 'SEN' // Senegal country code
);
```

### Account Information

```php
use PayazaSdk\Payaza;
use PayazaSdk\Enums\Currency;

// Get balance for a specific currency (NEW!)
$ngnBalance = Payaza::accounts()->currency(Currency::NGN)->balance();
echo "NGN Balance: {$ngnBalance['available_balance']} {$ngnBalance['currency']}";

$ghsBalance = Payaza::accounts()->currency(Currency::GHS)->balance();
echo "GHS Balance: {$ghsBalance['available_balance']} {$ghsBalance['currency']}";

// Get all account balances
$allBalances = Payaza::accounts()->balance();
foreach ($allBalances as $account) {
    echo "Currency: {$account['currency']}, Balance: {$account['balance']}";
}

// Get transaction history
$transactions = Payaza::accounts()->transactions(page: 1, limit: 20);

// Get specific transaction
$transaction = Payaza::accounts()->transaction('TXN-789');

// Verify account name before payout (Account Name Enquiry)
$accountInfo = Payaza::accounts()->getAccountNameEnquiry(
    accountNumber: '0123456789',
    bankCode: '044',
    currency: Currency::NGN
);

echo "Account Name: {$accountInfo['account_name']}";
echo "Account Status: {$accountInfo['account_status']}";

// Get Payaza account information
$payazaAccounts = Payaza::accounts()->getPayazaAccountsInfo();
foreach ($payazaAccounts as $account) {
    echo "Currency: {$account['currency']}, Balance: {$account['balance']}";
}
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

### Complete Payout Workflow with Account Verification

```php
use PayazaSdk\Payaza;
use PayazaSdk\Data\PayoutBeneficiary;
use PayazaSdk\Enums\Currency;
use PayazaSdk\Exceptions\PayazaException;

try {
    // Step 1: Verify recipient account details
    $accountInfo = Payaza::accounts()->getAccountNameEnquiry(
        accountNumber: '0123456789',
        bankCode: '044',
        currency: Currency::NGN
    );
    
    if ($accountInfo['account_status'] !== 'ACTIVE') {
        throw new Exception('Recipient account is not active');
    }
    
    // Step 2: Create and send payout
    $beneficiary = new PayoutBeneficiary(
        accountName: $accountInfo['account_name'], // Use verified name
        accountNumber: $accountInfo['account_number'],
        bankCode: '044',
        amount: 1000.00,
        currency: Currency::NGN,
        narration: 'Salary payment'
    );
    
    $status = Payaza::payouts()->send($beneficiary, 'PAYOUT-' . uniqid());
    
    // Step 3: Monitor payout status
    do {
        sleep(5);
        $payoutStatus = Payaza::payouts()->status($status->transactionId);
        echo "Status: {$payoutStatus->state->value}\n";
    } while ($payoutStatus->state === TransactionState::PROCESSING);
    
    if ($payoutStatus->state === TransactionState::SUCCESSFUL) {
        echo "Payout completed successfully!";
    }
    
} catch (PayazaException $e) {
    echo "Payout failed: {$e->getMessage()}";
}
```

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
    
    // Transaction PIN
    'transaction_pin' => env('PAYAZA_TRANSACTION_PIN'),
    
    // Configurable API URLs with tenant support
    // Use {tenant} placeholder for automatic live/test path injection
    'urls' => [
        'card_charge_3ds' => env('PAYAZA_CARD_3DS_URL', 'https://cards-live.78financials.com/card_charge/'),
        'card_charge_2ds' => env('PAYAZA_CARD_2DS_URL', 'https://cards-live.78financials.com/cards/mpgs/v1/2ds/card_charge'),
        'card_status' => env('PAYAZA_CARD_STATUS_URL', 'https://api.payaza.africa/{tenant}/card/card_charge/transaction_status'),
        'payout_send' => env('PAYAZA_PAYOUT_URL', 'https://api.payaza.africa/{tenant}/payout-receptor/payout'),
        'account_enquiry' => env('PAYAZA_ACCOUNT_ENQUIRY_URL', 'https://api.payaza.africa/{tenant}/payaza-account/api/v1/mainaccounts/merchant/provider/enquiry'),
        // ... other endpoints
    ],

    // Environment and URLs
    'environment' => env('PAYAZA_ENV', 'test'), // 'test' or 'live'
    'base_url'    => env('PAYAZA_BASE_URL', 'https://api.payaza.africa'),

    // HTTP timeout in seconds
    'timeout' => 24,
];
```

## Requirements

- PHP 8.2 or higher
- Laravel 10.0, 11.0, or 12.0
- Guzzle HTTP 7.8+

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email security@yourcompany.com instead of using the issue tracker.

## Credits

- [Gbenga Oni](https://github.com/gbxnga)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

---

## API Coverage

This SDK provides complete coverage of the Payaza API:

### Cards
- ✅ Card charging (3DS/2DS authentication)
- ✅ Transaction status checking
- ✅ Refunds processing
- ✅ Refund status tracking
- ✅ Multiple currency support (USD, NGN, GHS, KES, UGX, TZS, XOF)

### Payouts
- ✅ NGN bank transfers (NUBAN)
- ✅ GHS bank transfers (GHIPPS)
- ✅ Mobile money payouts (GHS, KES, UGX, TZS, XOF)
- ✅ Multi-country support (XOF with country parameter)
- ✅ Payout status checking
- ✅ Bank list retrieval
- ✅ Automatic account reference resolution

### Account Management
- ✅ Account balance checking
- ✅ Transaction history retrieval
- ✅ Single transaction lookup
- ✅ Account name enquiry/verification
- ✅ Payaza account information
- ✅ Multi-account support with easy switching

### Additional Features
- ✅ Multiple Payaza account management
- ✅ Environment-based configuration (test/live)
- ✅ Type-safe enums and data objects
- ✅ Comprehensive error handling
- ✅ Laravel service provider integration
- ✅ Facade support with account switching

## Support

For support, email support@yourcompany.com or join our Slack channel.

## Roadmap

- [ ] Webhook signature verification
- [ ] Bulk operations
- [ ] Rate limiting helpers
- [ ] Laravel Notification channel
- [ ] Artisan commands for common operations