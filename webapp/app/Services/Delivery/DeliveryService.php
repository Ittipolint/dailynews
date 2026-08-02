<?php

declare(strict_types=1);

namespace App\Services\Delivery;

use App\Models\DeliveryLog;
use App\Models\Member;
use App\Models\MemberChannel;
use App\Models\MemberSchedule;
use App\Models\News;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DeliveryService
{
    public function processSchedule(MemberSchedule $schedule): array
    {
        if (! $schedule->is_active || ! $schedule->member->is_active) {
            return ['skipped' => 'schedule or member inactive'];
        }

        $news = $this->collectNews($schedule);

        if ($news->isEmpty()) {
            $this->recordLog($schedule, null, 'success', [], null);

            return ['delivered' => 0, 'reason' => 'no matching news'];
        }

        $results = [];
        $channels = $schedule->channels ?: [];

        foreach ($channels as $channelType) {
            $memberChannel = $schedule->member->channels()
                ->where('channel_type', $channelType)
                ->where('is_active', true)
                ->first();

            if (! $memberChannel) {
                continue;
            }

            try {
                $sent = $this->deliver($schedule->member, $memberChannel, $news);
                $this->recordLog($schedule, $memberChannel, 'success', $news->pluck('id')->all(), null);
                $results[$channelType] = ['status' => 'success', 'sent' => $sent];
            } catch (\Throwable $e) {
                $this->recordLog($schedule, $memberChannel, 'failed', $news->pluck('id')->all(), $e->getMessage());
                $results[$channelType] = ['status' => 'failed', 'error' => $e->getMessage()];
                Log::channel('delivery')->error('Delivery failed', [
                    'schedule' => $schedule->id,
                    'channel' => $channelType,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    public function processAllDueSchedules(): array
    {
        $results = [];

        MemberSchedule::where('is_active', true)
            ->whereHas('member', fn ($q) => $q->where('is_active', true))
            ->get()
            ->each(function (MemberSchedule $schedule) use (&$results): void {
                $results[$schedule->id] = $this->processSchedule($schedule);
            });

        return $results;
    }

    protected function collectNews(MemberSchedule $schedule)
    {
        $query = News::where('status', '!=', 'failed')
            ->where('published_at', '>=', now()->subHours(24));

        if (! empty($schedule->categories)) {
            $query->whereIn('category', $schedule->categories);
        }

        $interests = $schedule->member->interests()->where('is_active', true)->get();
        $keywords = $interests->where('type', 'keyword')->pluck('value')->filter()->values();

        if ($keywords->isNotEmpty()) {
            $query->where(function ($q) use ($keywords): void {
                foreach ($keywords as $keyword) {
                    $q->orWhere('title', 'ilike', "%{$keyword}%")
                        ->orWhere('summary', 'ilike', "%{$keyword}%");
                }
            });
        }

        return $query->orderByDesc('published_at')
            ->limit($schedule->limit ?: 10)
            ->get();
    }

    protected function deliver(Member $member, MemberChannel $channel, iterable $news): int
    {
        return match ($channel->channel_type) {
            'line_personal' => $this->deliverLinePersonal($member, $channel, $news),
            'line_oa' => $this->deliverLineOa($member, $channel, $news),
            'email' => $this->deliverEmail($member, $channel, $news),
            default => throw new \RuntimeException('Unknown channel type'),
        };
    }

    protected function deliverLinePersonal(Member $member, MemberChannel $channel, iterable $news): int
    {
        $credentials = $channel->credentials ?? [];
        $userId = $member->line_user_id;

        if (! $userId) {
            throw new \RuntimeException('LINE personal user_id not set');
        }

        $token = $this->lineAccessToken();

        $messages = [];
        foreach ($news as $item) {
            $title = $item->translatedTitle($member->preferred_locale ?? 'th');
            $messages[] = [
                'type' => 'text',
                'text' => "{$title}\n\n".$item->source_url,
            ];
        }

        $payload = [
            'to' => $userId,
            'messages' => array_slice($messages, 0, 5),
        ];

        $response = Http::withToken($token)
            ->post('https://api.line.me/v2/bot/message/push', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("LINE push failed: {$response->status()} {$response->body()}");
        }

        return count($payload['messages']);
    }

    protected function deliverLineOa(Member $member, MemberChannel $channel, iterable $news): int
    {
        $credentials = $channel->credentials ?? [];
        $userId = $member->line_oa_user_id;

        if (! $userId) {
            throw new \RuntimeException('LINE OA user_id not set');
        }

        $token = $this->lineAccessToken();

        $messages = [];
        foreach ($news as $item) {
            $title = $item->translatedTitle($member->preferred_locale ?? 'th');
            $messages[] = [
                'type' => 'text',
                'text' => "{$title}\n\n".$item->source_url,
            ];
        }

        $response = Http::withToken($token)
            ->post('https://api.line.me/v2/bot/message/push', [
                'to' => $userId,
                'messages' => array_slice($messages, 0, 5),
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException("LINE OA push failed: {$response->status()} {$response->body()}");
        }

        return count($messages);
    }

    protected function deliverEmail(Member $member, MemberChannel $channel, iterable $news): int
    {
        $credentials = $channel->credentials ?? [];
        $email = $credentials['email'] ?? $member->email;

        if (! $email) {
            throw new \RuntimeException('Email address not set');
        }

        $items = '';
        foreach ($news as $item) {
            $title = $item->translatedTitle($member->preferred_locale ?? 'th');
            $summary = $item->translatedSummary($member->preferred_locale ?? 'th');
            $items .= '<li><a href="'.e($item->source_url).'"><strong>'.e($title).'</strong></a>'
                .($summary ? '<br>'.e($summary) : '').'</li>';
        }

        $body = "<html><body><h2>DailyNews — ข่าวประจำวัน</h2><ul>{$items}</ul></body></html>";

        Mail::html($body, function ($message) use ($member, $email): void {
            $message->to($email, $member->name)
                ->subject('DailyNews — ข่าวประจำวัน');
        });

        return $news instanceof \Countable ? count($news) : iterator_count($news);
    }

    protected function lineAccessToken(): string
    {
        $credential = \App\Models\Credential::where('code', 'line_channel')->first();

        if ($credential && ! empty($credential->config['access_token'])) {
            return $credential->config['access_token'];
        }

        return config('services.line.access_token', '');
    }

    protected function recordLog(MemberSchedule $schedule, ?MemberChannel $channel, string $status, array $newsIds, ?string $error): void
    {
        DeliveryLog::create([
            'member_id' => $schedule->member_id,
            'schedule_id' => $schedule->id,
            'channel_type' => $channel?->channel_type ?? 'none',
            'news_ids' => $newsIds,
            'status' => $status,
            'error_message' => $error,
            'sent_at' => now(),
        ]);
    }
}
