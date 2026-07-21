<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Polish Accounting Platforms Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure credentials and status for the primary accounting
    | systems in Poland. Active integrations will automatically receive
    | orders upon payment.
    |
    */

    'drivers' => [

        'built_in' => [
            'enabled' => env('ACCOUNTING_BUILT_IN_ENABLED', false),
        ],

        'fakturownia' => [
            'enabled' => env('ACCOUNTING_FAKTUROWNIA_ENABLED', false),
            'api_token' => env('ACCOUNTING_FAKTUROWNIA_API_TOKEN'),
            'domain' => env('ACCOUNTING_FAKTUROWNIA_DOMAIN'), // e.g., 'yourcompany' for yourcompany.fakturownia.pl
        ],

        'ifirma' => [
            'enabled' => env('ACCOUNTING_IFIRMA_ENABLED', false),
            'api_key' => env('ACCOUNTING_IFIRMA_API_KEY'),
            'username' => env('ACCOUNTING_IFIRMA_USERNAME'), // typically owner's email address
        ],

        'infakt' => [
            'enabled' => env('ACCOUNTING_INFAKT_ENABLED', false),
            'api_key' => env('ACCOUNTING_INFAKT_API_KEY'),
        ],

        'wfirma' => [
            'enabled' => env('ACCOUNTING_WFIRMA_ENABLED', false),
            'api_key' => env('ACCOUNTING_WFIRMA_API_KEY'), // API secret/password
            'access_key' => env('ACCOUNTING_WFIRMA_ACCESS_KEY'), // API user access key
        ],

    ],

];
