<?php

declare(strict_types=1);

return [

    'translation' => [
        'driver' => env('TRANSLATION_DRIVER', 'google'),
        'api_key' => env('GOOGLE_GEMINI_API_KEY'),
        'model' => env('GOOGLE_GEMINI_MODEL', 'gemini-2.5-flash'),
    ],

    'llm' => [
        'driver' => env('LLM_DRIVER', 'google'),
        'api_key' => env('LLM_API_KEY', env('GOOGLE_GEMINI_API_KEY')),
        'model' => env('LLM_MODEL', 'gemini-2.5-flash'),
    ],

    'embedding' => [
        'driver' => env('EMBEDDING_DRIVER', 'google'),
        'api_key' => env('EMBEDDING_API_KEY', env('GOOGLE_GEMINI_API_KEY')),
        'model' => env('EMBEDDING_MODEL', 'gemini-embedding-001'),
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
