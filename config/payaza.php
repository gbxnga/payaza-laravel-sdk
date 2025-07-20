<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Multiple Accounts Configuration
    |--------------------------------------------------------------------------
    */
    'accounts' => [
        'primary' => [
            'key' => env('PAYAZA_PUBLIC_KEY'),
        ],
        'premium' => [
            'key' => env('PAYAZA_PREMIUM_PUBLIC_KEY'),
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Default Account
    |--------------------------------------------------------------------------
    */
    'default_account' => env('PAYAZA_DEFAULT_ACCOUNT', 'primary'), 
    
    /*
    |--------------------------------------------------------------------------
    | Transaction PIN
    |--------------------------------------------------------------------------
    */
    'transaction_pin' => env('PAYAZA_TRANSACTION_PIN'),

    /*
    |--------------------------------------------------------------------------
    | API URLs - Configurable endpoints with tenant support
    |--------------------------------------------------------------------------
    | URLs can be overridden per environment. Use {tenant} placeholder for 
    | dynamic tenant injection (live/test).
    */
    'urls' => [
        // Card endpoints - these don't typically use tenant paths
        'card_charge_3ds' => env('PAYAZA_CARD_3DS_URL', 'https://cards-live.78financials.com/card_charge/'),
        'card_charge_2ds' => env('PAYAZA_CARD_2DS_URL', 'https://cards-live.78financials.com/cards/mpgs/v1/2ds/card_charge'),
        'card_status' => env('PAYAZA_CARD_STATUS_URL', 'https://api.payaza.africa/{tenant}/card/card_charge/transaction_status'),
        'card_refund' => env('PAYAZA_CARD_REFUND_URL', 'https://cards-live.78financials.com/card_charge/refund'),
        'card_refund_status' => env('PAYAZA_CARD_REFUND_STATUS_URL', 'https://cards-live.78financials.com/card_charge/refund_status'),
        
        // Payout endpoints - use tenant paths
        'payout_send' => env('PAYAZA_PAYOUT_URL', 'https://api.payaza.africa/{tenant}/payout-receptor/payout'),
        'payout_status' => env('PAYAZA_PAYOUT_STATUS_URL', 'https://api.payaza.africa/{tenant}/payaza-account/api/v1/mainaccounts/merchant/transaction'),
        
        // Account endpoints - use tenant paths
        'account_enquiry' => env('PAYAZA_ACCOUNT_ENQUIRY_URL', 'https://api.payaza.africa/{tenant}/payaza-account/api/v1/mainaccounts/merchant/provider/enquiry'),
        'account_info' => env('PAYAZA_ACCOUNT_INFO_URL', 'https://api.payaza.africa/{tenant}/payaza-account/api/v1/mainaccounts/merchant/enquiry/main'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant + Base URL
    |--------------------------------------------------------------------------
    */
    'environment' => env('PAYAZA_ENV', 'test'),   // test|live
    'base_url'    => env('PAYAZA_BASE_URL', 'https://api.payaza.africa'),

    /*
    |--------------------------------------------------------------------------
    | Misc.
    |--------------------------------------------------------------------------
    */
    'timeout' => 24,
];