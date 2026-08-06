<?php

declare(strict_types=1);

namespace App\Services\Delivery;

use App\Models\DeliveryLog;
use App\Models\Member;
use App\Models\MemberChannel;
use App\Models\MemberSchedule;
use App\Models\News;
use App\Services\Translation\TranslationService;
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
            $this->recordLog($schedule->member, null, 'success', [], null, $schedule->id);

            return ['delivered' => 0, 'reason' => 'no matching news'];
        }

        $this->ensureTranslations($news, $schedule->member->preferred_locale);

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
                $this->recordLog($schedule->member, $memberChannel, 'success', $news->pluck('id')->all(), null, $schedule->id);
                $results[$channelType] = ['status' => 'success', 'sent' => $sent];
            } catch (\Throwable $e) {
                $this->recordLog($schedule->member, $memberChannel, 'failed', $news->pluck('id')->all(), $e->getMessage(), $schedule->id);
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
            ->filter(function (MemberSchedule $schedule): bool {
                return $this->isDue($schedule);
            })
            ->each(function (MemberSchedule $schedule) use (&$results): void {
                $results[$schedule->id] = $this->processSchedule($schedule);
            });

        return $results;
    }

    /**
     * Determine whether the schedule's cron expression matches the current
     * minute/hour/day (Asia/Bangkok), using the same expression parser that
     * Laravel's scheduler uses.
     */
    protected function isDue(MemberSchedule $schedule): bool
    {
        $expression = trim((string) $schedule->cron_expression);

        if ($expression === '') {
            return true;
        }

        try {
            return \Cron\CronExpression::factory($expression)
                ->isDue(new \DateTimeImmutable('now', new \DateTimeZone('Asia/Bangkok')));
        } catch (\Throwable $e) {
            // Unparsable cron -> treat as due so it still runs rather than silently never firing.
            return true;
        }
    }

    /**
     * Send the most recently ingested news lot to every active channel
     * of the given member, immediately (admin "send news" button).
     */
    public function deliverLatestLot(Member $member, int $limit = 5): array
    {
        if (! $member->is_active) {
            return ['ok' => false, 'error' => 'member inactive'];
        }

        $latestFetchedAt = News::max('fetched_at');

        if (! $latestFetchedAt) {
            return ['ok' => false, 'error' => 'no news in system'];
        }

        $news = News::where('fetched_at', '>=', \Carbon\Carbon::parse($latestFetchedAt)->subMinutes(10))
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();

        if ($news->isEmpty()) {
            return ['ok' => false, 'error' => 'latest lot is empty'];
        }

        $channels = $member->channels()
            ->where('is_active', true)
            ->get();

        if ($channels->isEmpty()) {
            return ['ok' => false, 'error' => 'no active channels'];
        }

        // Ensure every item is translated into the member's preferred locale
        // before sending, so the delivered content matches their language.
        $this->ensureTranslations($news, $member->preferred_locale);

        $results = [];

        foreach ($channels as $channel) {
            try {
                $sent = $this->deliver($member, $channel, $news);
                $this->recordLog($member, $channel, 'success', $news->pluck('id')->all(), null);
                $results[$channel->channel_type] = ['status' => 'success', 'sent' => $sent];
            } catch (\Throwable $e) {
                $this->recordLog($member, $channel, 'failed', $news->pluck('id')->all(), $e->getMessage());
                $results[$channel->channel_type] = ['status' => 'failed', 'error' => $e->getMessage()];
                Log::channel('delivery')->error('Deliver latest lot failed', [
                    'member' => $member->id,
                    'channel' => $channel->channel_type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'ok' => true,
            'news_count' => $news->count(),
            'channels' => array_keys($results),
            'results' => $results,
        ];
    }

    /**
     * Immediately deliver to a schedule's configured channels using its own
     * collection rules (categories, keyword interests, limit) — regardless of
     * whether the cron time is currently due (admin "send news" button on the
     * schedule page).
     */
    public function deliverScheduleNow(MemberSchedule $schedule): array
    {
        if (! $schedule->member->is_active) {
            return ['ok' => false, 'error' => 'member inactive'];
        }

        $news = $this->collectNews($schedule);

        if ($news->isEmpty()) {
            return ['ok' => false, 'error' => 'no matching news'];
        }

        $this->ensureTranslations($news, $schedule->member->preferred_locale);

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
                $this->recordLog($schedule->member, $memberChannel, 'success', $news->pluck('id')->all(), null, $schedule->id);
                $results[$channelType] = ['status' => 'success', 'sent' => $sent];
            } catch (\Throwable $e) {
                $this->recordLog($schedule->member, $memberChannel, 'failed', $news->pluck('id')->all(), $e->getMessage(), $schedule->id);
                $results[$channelType] = ['status' => 'failed', 'error' => $e->getMessage()];
                Log::channel('delivery')->error('Schedule send-now failed', [
                    'schedule' => $schedule->id,
                    'channel' => $channelType,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (empty($results)) {
            return ['ok' => false, 'error' => 'no active channels'];
        }

        return [
            'ok' => true,
            'news_count' => $news->count(),
            'channels' => array_keys($results),
            'results' => $results,
        ];
    }

    protected function ensureTranslations(iterable $news, ?string $locale): void
    {
        if (! $locale) {
            return;
        }

        $items = is_array($news) ? collect($news) : $news;

        try {
            app(TranslationService::class)->translateBatch($items, $locale);
        } catch (\Throwable $e) {
            // Never let a translation failure block delivery. When the
            // translation provider is unavailable (e.g. rate-limited), fall
            // back to sending the original-language content rather than
            // failing the whole send.
            Log::channel('delivery')->warning('Translation skipped, sending original', [
                'locale' => $locale,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function collectNews(MemberSchedule $schedule)
    {
        $query = News::where('status', '!=', 'failed')
            ->where('published_at', '>=', now()->subHours(24));

        if (! empty($schedule->categories)) {
            $query->where(function ($q) use ($schedule): void {
                foreach ($schedule->categories as $category) {
                    // news.category stores a comma-separated list of category
                    // codes, so an exact match would always fail. Use FIND_IN_SET.
                    $q->orWhereRaw('FIND_IN_SET(?, category)', [$category]);
                }
            });
        }

        $interests = $schedule->member->interests()->where('is_active', true)->get();
        $keywords = $interests->where('type', 'keyword')->pluck('value')->filter()->values();

        if ($keywords->isNotEmpty()) {
            $query->where(function ($q) use ($keywords): void {
                foreach ($keywords as $keyword) {
                    $q->orWhere('title', 'like', "%{$keyword}%")
                        ->orWhere('summary', 'like', "%{$keyword}%");
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
        $userId = $member->line_oa_user_id;

        $messages = [];
        foreach ($news as $item) {
            $title = $item->translatedTitle($member->preferred_locale ?? 'th');
            $messages[] = [
                'type' => 'text',
                'text' => "{$title}\n\n".$item->source_url,
            ];
        }

        $payload = 'broadcast';
        $label = 'broadcast';

        // A valid LINE push recipient is a userId (starts with "U"). Basic IDs
        // (e.g. "@dailynews") or any other value are not recipients — in that
        // case fall back to broadcasting to every follower of the OA account.
        if ($userId && str_starts_with($userId, 'U')) {
            $endpoint = 'https://api.line.me/v2/bot/message/push';
            $payload = ['to' => $userId, 'messages' => array_slice($messages, 0, 5)];
            $label = 'push';
        } else {
            // No valid recipient ID: broadcast to every follower of the LINE OA account.
            $endpoint = 'https://api.line.me/v2/bot/message/broadcast';
            $payload = ['messages' => array_slice($messages, 0, 5)];
        }

        $response = Http::withToken($this->lineOaAccessToken($member))
            ->post($endpoint, $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("LINE OA {$label} failed: {$response->status()} {$response->body()}");
        }

        return count($messages);
    }

    /**
     * Obtain a LINE OA channel access token. If the member carries its own
     * OA channel credentials, exchange them for a short-lived token via the
     * LINE OAuth endpoint; otherwise fall back to the shared channel token.
     */
    protected function lineOaAccessToken(Member $member): string
    {
        $channelId = $member->line_oa_channel_id;
        $channelSecret = $member->line_oa_channel_secret;

        if ($channelId && $channelSecret) {
            $response = Http::asForm()->post('https://api.line.me/v2/oauth/accessToken', [
                'grant_type' => 'client_credentials',
                'client_id' => $channelId,
                'client_secret' => $channelSecret,
            ]);

            if ($response->successful() && $response->json('access_token')) {
                return $response->json('access_token');
            }

            throw new \RuntimeException("LINE OA token exchange failed: {$response->status()} {$response->body()}");
        }

        return $this->lineAccessToken();
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

    protected function recordLog(Member $member, ?MemberChannel $channel, string $status, array $newsIds, ?string $error, ?int $scheduleId = null): void
    {
        DeliveryLog::create([
            'member_id' => $member->id,
            'schedule_id' => $scheduleId,
            'channel_type' => $channel?->channel_type ?? 'none',
            'news_ids' => $newsIds,
            'status' => $status,
            'error_message' => $error,
            'sent_at' => now(),
        ]);
    }
}
