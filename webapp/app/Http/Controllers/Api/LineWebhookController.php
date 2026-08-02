<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $secret = config('services.line.channel_secret');

        if ($secret) {
            $signature = $request->header('X-Line-Signature', '');
            $computed = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

            if (! hash_equals($signature, $computed)) {
                Log::channel('delivery')->warning('Invalid LINE webhook signature');

                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $events = $request->input('events', []);

        foreach ($events as $event) {
            $this->handleEvent($event);
        }

        return response()->json(['status' => 'ok']);
    }

    protected function handleEvent(array $event): void
    {
        $type = $event['type'] ?? null;

        if ($type !== 'message') {
            return;
        }

        $userId = $event['source']['userId'] ?? null;
        $text = $event['message']['text'] ?? '';

        if (! $userId || ! $text) {
            return;
        }

        Log::channel('delivery')->info('LINE message received', [
            'user' => $userId,
            'text' => $text,
        ]);
    }
}
