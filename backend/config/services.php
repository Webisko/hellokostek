<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'przelewy24' => [
        'enabled' => env('PRZELEWY24_ENABLED', true),
        'merchant_id' => env('PRZELEWY24_MERCHANT_ID'),
        'pos_id' => env('PRZELEWY24_POS_ID'),
        'crc' => env('PRZELEWY24_CRC'),
        'api_key' => env('PRZELEWY24_API_KEY'),
        'api_base_url' => env('PRZELEWY24_API_BASE_URL'),
        'redirect_base_url' => env('PRZELEWY24_REDIRECT_BASE_URL'),
        'callback_token' => env('PRZELEWY24_CALLBACK_TOKEN'),
    ],

    'stripe' => [
        'enabled' => env('STRIPE_ENABLED', true),
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'storefront' => [
        'url' => env('STOREFRONT_URL', env('APP_URL', 'http://localhost')),
    ],

	'mail_safety' => [
		'redirect_all_to' => env('MAIL_REDIRECT_TO'),
	],

    'analytics' => [
        'enabled' => env('ANALYTICS_ENABLED', false),
        'accepted_environments' => array_values(array_filter(array_map(
            static fn (string $environment): string => trim($environment),
            explode(',', (string) env('ANALYTICS_ACCEPTED_ENVIRONMENTS', 'production')),
        ))),
    ],

    'google_places' => [
        'api_key' => env('GOOGLE_PLACES_API_KEY'),
        'place_id' => env('GOOGLE_PLACES_PLACE_ID'),
        'business_name' => env('GOOGLE_PLACES_BUSINESS_NAME', 'Generic Shop'),
        'cache_ttl_minutes' => env('GOOGLE_PLACES_CACHE_TTL_MINUTES', 180),
        'language' => env('GOOGLE_PLACES_LANGUAGE', 'pl'),
    ],

    'inpost' => [
        'organization_id' => env('INPOST_ORGANIZATION_ID'),
        'token' => env('INPOST_TOKEN'),
        'sandbox' => env('INPOST_SANDBOX', true),
        // Default sender details if store settings aren't fully configured
        'sender_email' => env('INPOST_SENDER_EMAIL'),
        'sender_phone' => env('INPOST_SENDER_PHONE'),
        'sender_name' => env('INPOST_SENDER_NAME'),
        'sender_company' => env('INPOST_SENDER_COMPANY'),
        'sender_street' => env('INPOST_SENDER_STREET'),
        'sender_building' => env('INPOST_SENDER_BUILDING'),
        'sender_city' => env('INPOST_SENDER_CITY'),
        'sender_postcode' => env('INPOST_SENDER_POSTCODE'),
    ],

    'orlen_paczka' => [
        'partner_id' => env('ORLEN_PACZKA_PARTNER_ID'),
        'partner_key' => env('ORLEN_PACZKA_PARTNER_KEY'),
        'sandbox' => env('ORLEN_PACZKA_SANDBOX', true),
        // Default sender details if store settings aren't fully configured
        'sender_email' => env('ORLEN_PACZKA_SENDER_EMAIL'),
        'sender_phone' => env('ORLEN_PACZKA_SENDER_PHONE'),
        'sender_name' => env('ORLEN_PACZKA_SENDER_NAME'),
        'sender_company' => env('ORLEN_PACZKA_SENDER_COMPANY'),
        'sender_street' => env('ORLEN_PACZKA_SENDER_STREET'),
        'sender_building' => env('ORLEN_PACZKA_SENDER_BUILDING'),
        'sender_city' => env('ORLEN_PACZKA_SENDER_CITY'),
        'sender_postcode' => env('ORLEN_PACZKA_SENDER_POSTCODE'),
    ],

];
