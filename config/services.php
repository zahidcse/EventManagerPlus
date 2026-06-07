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

    'stripe' => [
        'currency' => strtolower((string) env('STRIPE_CURRENCY', 'usd')),
    ],

    /*
    |--------------------------------------------------------------------------
    | PayPal HTTP client (SSL)
    |--------------------------------------------------------------------------
    |
    | Windows stacks (e.g. Laragon) often point php.ini curl.cainfo at a missing
    | pem file → cURL error 77 → "PayPal authentication failed." Set PAYPAL_CACERT_PATH
    | to a valid cacert.pem (https://curl.se/ca/cacert.pem). Only use PAYPAL_VERIFY_SSL=false
    | on localhost for debugging — never in production.
    |
    */
    'paypal' => [
        'ca_bundle' => env('PAYPAL_CACERT_PATH'),
        'verify_ssl' => filter_var(env('PAYPAL_VERIFY_SSL', 'true'), FILTER_VALIDATE_BOOLEAN),
    ],

];
