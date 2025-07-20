<?php

declare(strict_types=1);

use Orchestra\Testbench\TestCase;
use PayazaSdk\PayazaServiceProvider;

uses(TestCase::class)
    ->beforeEach(function () {
        // Force configuration values to be available for tests
        $this->app['config']->set('payaza.accounts', [
            'primary' => ['key' => 'primary-test-key'],
            'premium' => ['key' => 'premium-test-key'],
        ]);
        $this->app['config']->set('payaza.default_account', 'primary');
        $this->app['config']->set('payaza.environment', 'test');
        $this->app['config']->set('payaza.transaction_pin', '1234');
        $this->app['config']->set('payaza.base_url', 'https://api.payaza.africa');
        $this->app['config']->set('payaza.timeout', 24);
        $this->app['config']->set('payaza.urls', [
            'card_charge_3ds' => 'https://cards-live.78financials.com/card_charge/',
            'card_charge_2ds' => 'https://cards-live.78financials.com/cards/mpgs/v1/2ds/card_charge',
            'card_status' => 'https://api.payaza.africa/{tenant}/card/card_charge/transaction_status',
            'card_refund' => 'https://cards-live.78financials.com/card_charge/refund',
            'card_refund_status' => 'https://cards-live.78financials.com/card_charge/refund_status',
            'payout_send' => 'https://api.payaza.africa/{tenant}/payout-receptor/payout',
            'payout_status' => 'https://api.payaza.africa/{tenant}/payaza-account/api/v1/mainaccounts/merchant/transaction',
            'payout_banks' => 'https://api.payaza.africa/{tenant}/payout-receptor/banks',
            'account_enquiry' => 'https://api.payaza.africa/{tenant}/payaza-account/api/v1/mainaccounts/merchant/provider/enquiry',
            'account_info' => 'https://api.payaza.africa/{tenant}/payaza-account/api/v1/mainaccounts/merchant/enquiry/main',
            'account_transactions' => 'https://api.payaza.africa/{tenant}/payaza-account/api/v1/mainaccounts/merchant/transactions',
            'account_transaction' => 'https://api.payaza.africa/{tenant}/payaza-account/api/v1/mainaccounts/merchant/transaction',
        ]);
        
        // Manually register the service provider since getPackageProviders isn't working
        $provider = new PayazaServiceProvider($this->app);
        $provider->register();
        $this->app->register(PayazaServiceProvider::class);
    })
    ->in('Feature', 'Unit');

function getPackageProviders($app): array
{
    return [PayazaServiceProvider::class];
}