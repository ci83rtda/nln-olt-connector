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

    'olt' => [
        'host' => env('OLT_HOST'),
        'username' => env('OLT_USERNAME'),
        'password' => env('OLT_PASSWORD'),
        'enable_password' => env('OLT_PASSWORD'),
    ],

    'central_api' => [
        'url' => env('CENTRAL_API_URL'),
        'token' => env('CENTRAL_API_TOKEN'),
    ],

    'onu_password' => [
        'admin' => [
            'username' => env('ONU_ADMIN_USERNAME'),
            'password' => env('ONU_ADMIN_PASSWORD'),
        ],
        'user' => [
            'username' => env('ONU_CLIENT_USERNAME'),
            'password' => env('ONU_CLIENT_PASSWORD'),
        ],
    ],

    'uisp' => [
        'api' => [
            'v1'=> [
                'url' => env('UISP_V1_URL'),
                'token' => env('UISP_V1_TOKEN')
            ],
            'v2' => [
                'url' => env('UISP_V2_URL'),
                'token' => env('UISP_V2_TOKEN')
            ]
        ]
    ],

];
