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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mini_erp' => [
        'url' => env('MINI_ERP_API_URL'),
        'credentials_key' => env('CATALOG_CREDENTIAL_KEY', env('APP_KEY')),
        'timeout' => (int) env('MINI_ERP_API_TIMEOUT', 10),
        'connect_timeout' => (int) env('MINI_ERP_API_CONNECT_TIMEOUT', 3),
        'product_photo_urls' => [
            'public' => env('MINI_ERP_PUBLIC_STORAGE_URL'),
            'r2_products' => env('MINI_ERP_PRODUCTS_PUBLIC_URL'),
        ],
    ],

];
