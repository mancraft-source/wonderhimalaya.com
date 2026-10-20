<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'wordpress' => [
        'url' => env('WORDPRESS_URL', 'https://wonderhimalaya.com'),
        'timeout' => (int) env('WORDPRESS_TIMEOUT', 30),
        // Pause between uncached requests so we don't hammer the old site.
        'delay_ms' => (int) env('WORDPRESS_DELAY_MS', 300),
        'timeout_backoff' => (int) env('WORDPRESS_TIMEOUT_BACKOFF', 15),
        // Raw API/HTML responses are cached so re-runs are fast and work offline.
        'cache' => (bool) env('WORDPRESS_CACHE', true),
        'cache_path' => storage_path('app/private/wp-cache'),
        // Downloaded originals, so a re-import never fetches the same file twice.
        'media_cache_path' => storage_path('app/private/wp-media-cache'),
        'media_map_path' => storage_path('app/private/wp-media-map.json'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Cloudflare Turnstile (bot protection for public forms + API enquiries). Disabled while secret is empty.
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret' => env('TURNSTILE_SECRET'),
    ],

    // PayPal REST v2 (Orders API). mode: sandbox|live
    'paypal' => [
        'mode' => env('PAYPAL_MODE', 'sandbox'),
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],

    // Nepali bank card gateway via Cybersource Secure Acceptance (Hosted Checkout).
    // Merchant credentials + bank-specific docs are pending from the client's bank.
    'nepal_bank' => [
        'label' => env('NEPAL_BANK_LABEL', 'Credit / debit card'),
        'access_key' => env('NEPAL_BANK_ACCESS_KEY'),
        'profile_id' => env('NEPAL_BANK_PROFILE_ID'),
        'secret_key' => env('NEPAL_BANK_SECRET_KEY'),
        'endpoint' => env('NEPAL_BANK_ENDPOINT', 'https://testsecureacceptance.cybersource.com/pay'),
        'currency' => env('NEPAL_BANK_CURRENCY', 'USD'),
        'locale' => env('NEPAL_BANK_LOCALE', 'en'),
    ],

];
