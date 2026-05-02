<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    | "sandbox" or "production". The package switches all Daraja base URLs
    | automatically based on this value.
    */

    'environment' => env('MPESA_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Credentials
    |--------------------------------------------------------------------------
    | Create an app on https://developer.safaricom.co.ke to get these.
    | In sandbox, Safaricom ships default test credentials with every account.
    */

    'consumer_key'    => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | STK Push (Lipa Na M-Pesa Online)
    |--------------------------------------------------------------------------
    | business_short_code — your Paybill or Till number
    | passkey             — the Lipa Na M-Pesa Online passkey (from Daraja portal)
    | party_b             — usually equals business_short_code; for till numbers
    |                       under an aggregated paybill, this is the HO paybill
    | callback_url        — absolute URL Safaricom POSTs the final result to
    */

    'stk' => [
        'business_short_code' => env('MPESA_STK_SHORT_CODE'),
        'passkey'             => env('MPESA_STK_PASSKEY'),
        'party_b'             => env('MPESA_STK_PARTY_B', env('MPESA_STK_SHORT_CODE')),
        'callback_url'        => env('MPESA_STK_CALLBACK_URL'),
        'transaction_type'    => env('MPESA_STK_TRANSACTION_TYPE', 'CustomerPayBillOnline'),
        'default_reference'   => env('MPESA_STK_DEFAULT_REFERENCE', 'Payment'),
        'default_description' => env('MPESA_STK_DEFAULT_DESCRIPTION', 'Payment'),
    ],

    /*
    |--------------------------------------------------------------------------
    | C2B (Customer-to-Business) — Paybill / Till confirmation + validation
    |--------------------------------------------------------------------------
    */

    'c2b' => [
        'short_code'       => env('MPESA_C2B_SHORT_CODE'),
        'confirmation_url' => env('MPESA_C2B_CONFIRMATION_URL'),
        'validation_url'   => env('MPESA_C2B_VALIDATION_URL'),
        'response_type'    => env('MPESA_C2B_RESPONSE_TYPE', 'Completed'), // Completed | Cancelled
    ],

    /*
    |--------------------------------------------------------------------------
    | B2C (Business-to-Customer) — disbursements / payouts
    |--------------------------------------------------------------------------
    | initiator_name, security_credential, and short_code come from a B2C
    | organisation account on Daraja (separate from C2B credentials).
    */

    'b2c' => [
        'initiator_name'       => env('MPESA_B2C_INITIATOR_NAME'),
        'security_credential'  => env('MPESA_B2C_SECURITY_CREDENTIAL'),
        'short_code'           => env('MPESA_B2C_SHORT_CODE'),
        'queue_timeout_url'    => env('MPESA_B2C_TIMEOUT_URL'),
        'result_url'           => env('MPESA_B2C_RESULT_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP client
    |--------------------------------------------------------------------------
    */

    'http' => [
        'timeout'         => (int) env('MPESA_HTTP_TIMEOUT', 30),
        'connect_timeout' => (int) env('MPESA_HTTP_CONNECT_TIMEOUT', 10),
        'retries'         => (int) env('MPESA_HTTP_RETRIES', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token cache
    |--------------------------------------------------------------------------
    | Daraja OAuth tokens are valid for 3600 seconds. The package caches them
    | in your default Laravel cache store and refreshes automatically.
    */

    'cache' => [
        'store' => env('MPESA_CACHE_STORE', null),          // null = default store
        'ttl'   => (int) env('MPESA_CACHE_TTL', 3500),      // seconds, leave 100s headroom
        'key'   => env('MPESA_CACHE_KEY', 'mpesa:access_token'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Callback security
    |--------------------------------------------------------------------------
    | Safaricom's production callback IPs (as of 2024).
    | Leave empty to disable IP allow-listing (NOT recommended for production).
    */

    'callback' => [
        'allowed_ips' => array_filter(explode(',', (string) env(
            'MPESA_CALLBACK_ALLOWED_IPS',
            '196.201.214.200,196.201.214.206,196.201.213.114,196.201.214.207,196.201.214.208,196.201.213.44,196.201.212.127,196.201.212.138,196.201.212.129,196.201.212.136,196.201.212.74,196.201.212.69'
        ))),
        'secret_query_key'   => env('MPESA_CALLBACK_SECRET_KEY'),   // optional ?key= shared secret
        'secret_query_param' => env('MPESA_CALLBACK_SECRET_PARAM', 'key'),
        'route_prefix'       => env('MPESA_CALLBACK_ROUTE_PREFIX', 'mpesa'),
        'register_routes'    => (bool) env('MPESA_REGISTER_ROUTES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Transactions table
    |--------------------------------------------------------------------------
    */

    'database' => [
        'log_transactions' => (bool) env('MPESA_LOG_TRANSACTIONS', true),
        'table'            => env('MPESA_TRANSACTIONS_TABLE', 'mpesa_transactions'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Daraja endpoints
    |--------------------------------------------------------------------------
    | You normally don't touch these — they're switched automatically based on
    | `environment` above. Override only if Safaricom introduces a new host.
    */

    'endpoints' => [
        'sandbox' => [
            'base'          => 'https://sandbox.safaricom.co.ke',
            'oauth'         => '/oauth/v1/generate?grant_type=client_credentials',
            'stk_push'      => '/mpesa/stkpush/v1/processrequest',
            'stk_query'     => '/mpesa/stkpushquery/v1/query',
            'c2b_register'  => '/mpesa/c2b/v1/registerurl',
            'c2b_simulate'  => '/mpesa/c2b/v1/simulate',
            'b2c_payment'   => '/mpesa/b2c/v1/paymentrequest',
            'balance'       => '/mpesa/accountbalance/v1/query',
            'status'        => '/mpesa/transactionstatus/v1/query',
            'reversal'      => '/mpesa/reversal/v1/request',
        ],
        'production' => [
            'base'          => 'https://api.safaricom.co.ke',
            'oauth'         => '/oauth/v1/generate?grant_type=client_credentials',
            'stk_push'      => '/mpesa/stkpush/v1/processrequest',
            'stk_query'     => '/mpesa/stkpushquery/v1/query',
            'c2b_register'  => '/mpesa/c2b/v1/registerurl',
            'c2b_simulate'  => '/mpesa/c2b/v1/simulate',
            'b2c_payment'   => '/mpesa/b2c/v1/paymentrequest',
            'balance'       => '/mpesa/accountbalance/v1/query',
            'status'        => '/mpesa/transactionstatus/v1/query',
            'reversal'      => '/mpesa/reversal/v1/request',
        ],
    ],
];
