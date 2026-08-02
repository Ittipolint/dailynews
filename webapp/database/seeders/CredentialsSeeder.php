<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Credential;
use Illuminate\Database\Seeder;

class CredentialsSeeder extends Seeder
{
    public function run(): void
    {
        $credentials = [
            'line_channel' => [
                'name' => 'LINE Messaging API',
                'config' => [
                    'channel_id' => env('LINE_CHANNEL_ID', '1528339539'),
                    'channel_secret' => env('LINE_CHANNEL_SECRET', ''),
                    'access_token' => env('LINE_ACCESS_TOKEN', ''),
                    'webhook_url' => env('LINE_WEBHOOK_URL', 'https://n8n38-sbu.veya.co.th/webhook/line'),
                ],
            ],
            'smtp' => [
                'name' => 'SMTP (ส่งอีเมล)',
                'config' => [
                    'host' => env('MAIL_HOST', ''),
                    'port' => env('MAIL_PORT', '587'),
                    'username' => env('MAIL_USERNAME', ''),
                    'password' => env('MAIL_PASSWORD', ''),
                    'from_address' => env('MAIL_FROM_ADDRESS', 'dailynews@ittipolint-sbu.veya.co.th'),
                    'from_name' => env('MAIL_FROM_NAME', 'DailyNews'),
                ],
            ],
            'gemini_llm' => [
                'name' => 'Google Gemini LLM (แปล + AI)',
                'config' => [
                    'api_key' => env('GOOGLE_GEMINI_API_KEY', ''),
                    'model' => env('GOOGLE_GEMINI_MODEL', 'gemini-1.5-flash'),
                ],
            ],
            'n8n_api' => [
                'name' => 'n8n API',
                'config' => [
                    'url' => env('N8N_URL', 'https://n8n38-sbu.veya.co.th'),
                    'api_key' => env('N8N_API_KEY', ''),
                ],
            ],
            'neo4j' => [
                'name' => 'Neo4j (Graph RAG)',
                'config' => [
                    'host' => env('NEO4J_HOST', '127.0.0.1'),
                    'port' => env('NEO4J_PORT', '7687'),
                    'username' => env('NEO4J_USERNAME', 'neo4j'),
                    'password' => env('NEO4J_PASSWORD', ''),
                ],
            ],
        ];

        foreach ($credentials as $code => $data) {
            Credential::updateOrCreate(['code' => $code], [
                'name' => $data['name'],
                'config' => $data['config'],
                'is_active' => true,
                'updated_by' => 'system',
            ]);
        }
    }
}
