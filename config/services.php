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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('MICROSOFT_REDIRECT_URI'),

        // IMPORTANT: for Any Entra tenant + Personal accounts use "common"
        'tenant' => env('MICROSOFT_TENANT', 'common'),
    ],

    'cachewraith' => [
        'agent_token' => env('CACHEWRAITH_AGENT_TOKEN'),
    ],

    'github' => [
        'token' => env('GITHUB_TOKEN'),
        'owner' => env('GITHUB_OWNER', 'hushstack'),
    ],

    'messenger' => [
        'url' => env('MESSENGER_API_URL'),
        'internal_key' => env('MESSENGER_INTERNAL_KEY'),
        'connect_timeout' => (int) env('MESSENGER_API_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('MESSENGER_API_TIMEOUT', 10),
    ],

    'frontend_redirect_whitelist' => array_filter(explode(',', env('FRONTEND_REDIRECT_WHITELIST', ''))),
];
