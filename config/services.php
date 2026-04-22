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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'fallback_models' => array_values(array_filter(array_map(
            static fn ($model): string => trim((string) $model),
            explode(',', (string) env('GEMINI_FALLBACK_MODELS', 'gemini-2.5-flash-lite,gemini-2.0-flash'))
        ), static fn (string $model): bool => $model !== '')),
        'embedding_model' => env('GEMINI_EMBEDDING_MODEL', 'gemini-embedding-001'),
        'embedding_output_dimensionality' => (int) env('GEMINI_EMBEDDING_OUTPUT_DIMENSIONALITY', 768),
        'embedding_normalize_vectors' => (bool) env('GEMINI_EMBEDDING_NORMALIZE_VECTORS', true),
        'timeout' => (int) env('GEMINI_TIMEOUT', 25),
        'retry_attempts' => (int) env('GEMINI_RETRY_ATTEMPTS', 3),
        'retry_base_sleep_ms' => (int) env('GEMINI_RETRY_BASE_SLEEP_MS', 350),
        'retry_max_sleep_ms' => (int) env('GEMINI_RETRY_MAX_SLEEP_MS', 2200),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/models'),
        'force_json_response' => (bool) env('GEMINI_FORCE_JSON_RESPONSE', true),
    ],

    'qdrant' => [
        'url' => env('QDRANT_URL'),
        'api_key' => env('QDRANT_API_KEY'),
        'collection' => env('QDRANT_COLLECTION', 'ai_knowledge_items_v1'),
        'vector_size' => (int) env('QDRANT_VECTOR_SIZE', 768),
        'distance' => env('QDRANT_DISTANCE', 'Cosine'),
        'timeout' => (int) env('QDRANT_TIMEOUT', 10),
        'auto_index' => (bool) env('QDRANT_AUTO_INDEX', true),
    ],

    'chat' => [
        'history_rate_limit' => (int) env('CHAT_HISTORY_RATE_LIMIT', 60),
        'send_rate_limit' => (int) env('CHAT_SEND_RATE_LIMIT', 18),
        'sync_rate_limit' => (int) env('CHAT_SYNC_RATE_LIMIT', 8),
        'admin_preview_rate_limit' => (int) env('CHAT_ADMIN_PREVIEW_RATE_LIMIT', 20),
    ],

];
