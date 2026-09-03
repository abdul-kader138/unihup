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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    // WhatsApp Business Cloud API (Meta, direct — no BSP). Powers the
    // student <-> staff support chat. Leave the credentials blank to disable
    // the feature; App\Services\WhatsApp\WhatsAppClient::configured() gates on
    // phone_number_id + access_token.
    'whatsapp' => [
        'api_version' => env('WHATSAPP_API_VERSION', 'v22.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'waba_id' => env('WHATSAPP_WABA_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        // App secret — used to verify the X-Hub-Signature-256 header on
        // inbound webhooks.
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        // Random string you invent; echoed back to Meta during webhook setup.
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'media_disk' => env('WHATSAPP_MEDIA_DISK', 'local'),
        // Approved template used to re-open a thread once the 24h free-form
        // window has closed. Must exist and be approved in the WhatsApp
        // Manager, with a language matching reopen_template_language.
        'reopen_template' => env('WHATSAPP_REOPEN_TEMPLATE', 'support_reply_reopen'),
        'reopen_template_language' => env('WHATSAPP_REOPEN_TEMPLATE_LANGUAGE', 'en'),
    ],

];
