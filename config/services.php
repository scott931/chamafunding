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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'paypal' => [
        'mode' => env('PAYPAL_MODE', 'sandbox'), // 'sandbox' or 'live'
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
        'webhook_signature' => env('PAYPAL_WEBHOOK_SIGNATURE'), // Webhook signature for verification
        'sandbox' => [
            'base_url' => 'https://api-m.sandbox.paypal.com',
            'js_sdk_url' => 'https://www.paypal.com/sdk/js',
            'webhook_signature' => env('PAYPAL_SANDBOX_WEBHOOK_SIGNATURE', 'AOZqNS2Qc1XJH9gWDESc6SEPYQz6A1Z1xSiHPqvw-PAGbnuONj1ATD4R'),
        ],
        'live' => [
            'base_url' => 'https://api-m.paypal.com',
            'js_sdk_url' => 'https://www.paypal.com/sdk/js',
            'webhook_signature' => env('PAYPAL_LIVE_WEBHOOK_SIGNATURE'),
        ],
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'mpesa' => [
        'consumer_key' => env('MPESA_CONSUMER_KEY'),
        'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
        'shortcode' => env('MPESA_SHORTCODE'),
        'passkey' => env('MPESA_PASSKEY'),
        'environment' => env('MPESA_ENVIRONMENT', 'sandbox'), // 'sandbox' or 'live'
        'callback_url' => env('MPESA_CALLBACK_URL'),
    ],

    'flutterwave' => [
        'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
        'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
        'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY'),
        'environment' => env('FLUTTERWAVE_ENVIRONMENT', 'sandbox'), // 'sandbox' or 'live'
    ],

];
