<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | API Keys (base64‑encoded in Payaza dashboard)
    |--------------------------------------------------------------------------
    */
    'primary_public_key'  => env('PAYAZA_PUBLIC_KEY'),
    'premium_public_key'  => env('PAYAZA_PREMIUM_PUBLIC_KEY'),

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