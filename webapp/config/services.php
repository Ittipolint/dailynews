<?php

declare(strict_types=1);

return [

    'translation' => [
        'driver' => env('TRANSLATION_DRIVER', env('LLM_DRIVER', 'google')),
        'api_key' => env('TRANSLATION_API_KEY', env('LLM_API_KEY', env('GOOGLE_GEMINI_API_KEY'))),
        'model' => env('TRANSLATION_MODEL', env('LLM_MODEL', 'gemini-2.5-flash')),
        'base_url' => env('TRANSLATION_BASE_URL', env('LLM_BASE_URL')),
        'batch_size' => (int) env('TRANSLATION_BATCH_SIZE', 5),
        'retry_attempts' => (int) env('TRANSLATION_RETRY_ATTEMPTS', 3),
    ],

    'llm' => [
        'driver' => env('LLM_DRIVER', 'google'),
        'api_key' => env('LLM_API_KEY', env('GOOGLE_GEMINI_API_KEY')),
        'model' => env('LLM_MODEL', 'gemini-2.5-flash'),
        'base_url' => env('LLM_BASE_URL'),
        'timeout' => (int) env('LLM_TIMEOUT', 90),
        'fallback' => [
            'driver' => env('LLM_FALLBACK_DRIVER'),
            'api_key' => env('LLM_FALLBACK_API_KEY'),
            'model' => env('LLM_FALLBACK_MODEL'),
            'base_url' => env('LLM_FALLBACK_BASE_URL'),
            'timeout' => (int) env('LLM_FALLBACK_TIMEOUT', 90),
        ],
    ],

    'embedding' => [
        'driver' => env('EMBEDDING_DRIVER', 'google'),
        'api_key' => env('EMBEDDING_API_KEY', env('GOOGLE_GEMINI_API_KEY')),
        'model' => env('EMBEDDING_MODEL', 'gemini-embedding-001'),
    ],

    'category_classifier' => [
        'llm_enabled' => (bool) env('CATEGORY_CLASSIFIER_LLM_ENABLED', false),
    ],

    'line' => [
        'channel_id' => env('LINE_CHANNEL_ID', '1528339539'),
        'channel_secret' => env('LINE_CHANNEL_SECRET'),
        'access_token' => env('LINE_ACCESS_TOKEN'),
        'webhook_url' => env('LINE_WEBHOOK_URL', 'https://n8n38-sbu.veya.co.th/webhook/line'),
    ],

    'n8n' => [
        'url' => env('N8N_URL', 'https://n8n38-sbu.veya.co.th'),
        'api_key' => env('N8N_API_KEY'),
        'fetch_webhook' => env('N8N_FETCH_WEBHOOK', 'https://n8n38-sbu.veya.co.th/webhook/dailynews-fetch-now'),
    ],

    'neo4j' => [
        'host' => env('NEO4J_HOST', '127.0.0.1'),
        'port' => env('NEO4J_PORT', 7687),
        'username' => env('NEO4J_USERNAME', 'neo4j'),
        'password' => env('NEO4J_PASSWORD'),
    ],

    'vector' => [
        'driver' => env('VECTOR_DRIVER', 'pgvector'),
        'dimensions' => (int) env('VECTOR_DIMENSIONS', 768),
    ],

    'credential_encryption_key' => env('CREDENTIAL_ENCRYPTION_KEY'),

    'api_token' => env('API_TOKEN'),

];
