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

    'whatsapp' => [
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4-turbo-preview'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'), // Available: gemini-2.5-flash, gemini-2.5-pro, gemini-2.0-flash
        'imagen_model' => env('GEMINI_IMAGEN_MODEL', 'imagen-4.0-fast-generate-001'), // Available: imagen-4.0-fast-generate-001, imagen-4.0-generate-001, imagen-4.0-ultra-generate-001
    ],

    'ai' => [
        'provider' => env('AI_PROVIDER', 'gemini'), // 'openai' or 'gemini'
    ],

    'google' => [
        'api_key' => env('GOOGLE_API_KEY'),
        'search_engine_id' => env('GOOGLE_SEARCH_ENGINE_ID'),
    ],

    'google_search' => [
        'api_key' => env('GOOGLE_SEARCH_API_KEY'),
        'search_engine_id' => env('GOOGLE_SEARCH_ENGINE_ID'),
    ],

    'google_cloud' => [
        'project_id' => env('GOOGLE_CLOUD_PROJECT_ID', '923148411282'),
        'location' => env('GOOGLE_CLOUD_LOCATION', 'global'),
        'datastore_id' => env('GOOGLE_CLOUD_DATASTORE_ID', 'bd26-election-agent_1763640025270'),
        'service_account_path' => env('GOOGLE_CLOUD_SERVICE_ACCOUNT_PATH', storage_path('app/google-cloud-key.json')),
    ],

    'stability_ai' => [
        'api_key' => env('STABILITY_AI_API_KEY'),
    ],

    'replicate' => [
        'api_token' => env('REPLICATE_API_TOKEN'),
    ],

    'news_scraper' => [
        'enabled' => env('NEWS_SCRAPER_ENABLED', true),
        'max_results' => env('NEWS_SCRAPER_MAX_RESULTS', 5),
        'time_filter' => env('NEWS_SCRAPER_TIME_FILTER', 'h1'), // h1 = last hour
        'keywords' => ['নির্বাচন', 'election bd', 'বাংলাদেশ নির্বাচন'],
    ],

];
