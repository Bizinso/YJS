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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'razorpay' => [
        'mode' => env('RAZORPAY_MODE', 'test'),
        'test_key_id' => env('RAZORPAY_TEST_KEY_ID'),
        'test_key_secret' => env('RAZORPAY_TEST_KEY_SECRET'),
        'test_webhook_secret' => env('RAZORPAY_TEST_WEBHOOK_SECRET'),
        'live_key_id' => env('RAZORPAY_LIVE_KEY_ID'),
        'live_key_secret' => env('RAZORPAY_LIVE_KEY_SECRET'),
        'live_webhook_secret' => env('RAZORPAY_LIVE_WEBHOOK_SECRET'),
        'theme_color' => env('RAZORPAY_THEME_COLOR', '#B8860B'),
    ],

    'shiprocket' => [
        'email' => env('SHIPROCKET_EMAIL'),
        'password' => env('SHIPROCKET_PASSWORD'),
        'channel_id' => env('SHIPROCKET_CHANNEL_ID'),
        'pickup_location' => env('SHIPROCKET_PICKUP_LOCATION', 'Primary'),
        'pickup_pincode' => env('SHIPROCKET_PICKUP_PINCODE'),
        'company_name' => env('SHIPROCKET_COMPANY_NAME', 'YJS Jewellers'),
        'return_address' => env('SHIPROCKET_RETURN_ADDRESS'),
        'return_city' => env('SHIPROCKET_RETURN_CITY'),
        'return_state' => env('SHIPROCKET_RETURN_STATE'),
        'return_phone' => env('SHIPROCKET_RETURN_PHONE'),
        'auto_create_order' => env('SHIPROCKET_AUTO_CREATE', false),
    ],

];
