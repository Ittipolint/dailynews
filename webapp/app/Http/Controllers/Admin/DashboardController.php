<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryLog;
use App\Models\Member;
use App\Models\News;
use App\Models\NewsSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'totalNews' => News::count(),
            'newsToday' => News::whereDate('fetched_at', today())->count(),
            'newsThisWeek' => News::where('fetched_at', '>=', now()->startOfWeek())->count(),
            'newsThisMonth' => News::where('fetched_at', '>=', now()->startOfMonth())->count(),
            'activeSources' => NewsSource::where('is_active', true)->count(),
            'inactiveSources' => NewsSource::where('is_active', false)->count(),
            'failedSources' => NewsSource::where('last_status', 'failed')->count(),
            'totalMembers' => Member::count(),
            'orgMembers' => Member::whereHas('type', fn ($q) => $q->where('code', 'organization'))->count(),
            'individualMembers' => Member::whereHas('type', fn ($q) => $q->where('code', 'individual'))->count(),
            'deliveriesToday' => DeliveryLog::whereDate('sent_at', today())->count(),
            'deliveriesThisWeek' => DeliveryLog::where('sent_at', '>=', now()->startOfWeek())->count(),
            'deliveriesThisMonth' => DeliveryLog::where('sent_at', '>=', now()->startOfMonth())->count(),
            'deliveryFailures' => DeliveryLog::where('status', 'failed')->whereDate('sent_at', today())->count(),
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $days = (int) ($request->get('days', 14));
        $start = now()->subDays($days - 1)->startOfDay();

        $perDay = collect(range(0, $days - 1))->mapWithKeys(function (int $offset) use ($start): array {
            $day = $start->copy()->addDays($offset);

            return [$day->format('Y-m-d') => 0];
        });

        $fetched = News::selectRaw("to_char(fetched_at, 'YYYY-MM-DD') as day, count(*) as total")
            ->where('fetched_at', '>=', $start)
            ->groupBy('day')
            ->pluck('total', 'day');

        $sent = DeliveryLog::selectRaw("to_char(sent_at, 'YYYY-MM-DD') as day, count(*) as total")
            ->where('sent_at', '>=', $start)
            ->where('status', 'success')
            ->groupBy('day')
            ->pluck('total', 'day');

        $bySource = NewsSource::withCount(['news' => fn ($q) => $q->where('fetched_at', '>=', $start)])
            ->orderByDesc('news_count')
            ->limit(10)
            ->get()
            ->map(fn (NewsSource $s) => ['name' => $s->name, 'count' => $s->news_count]);

        $byCategory = News::selectRaw('coalesce(category, \'general\') as category, count(*) as total')
            ->where('fetched_at', '>=', $start)
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $byChannel = DeliveryLog::selectRaw('channel_type, count(*) as total')
            ->where('sent_at', '>=', $start)
            ->groupBy('channel_type')
            ->get();

        return response()->json([
            'fetchedPerDay' => $perDay->map(fn ($v, $k) => ['date' => $k, 'total' => $fetched[$k] ?? $v])->values(),
            'sentPerDay' => $perDay->map(fn ($v, $k) => ['date' => $k, 'total' => $sent[$k] ?? $v])->values(),
            'bySource' => $bySource,
            'byCategory' => $byCategory,
            'byChannel' => $byChannel,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $from = $request->get('from');
        $to = $request->get('to');

        $newsQuery = News::with('source')->orderByDesc('published_at');
        $deliveryQuery = DeliveryLog::orderByDesc('sent_at');

        if ($from) {
            $newsQuery->whereDate('fetched_at', '>=', $from);
            $deliveryQuery->whereDate('sent_at', '>=', $from);
        }
        if ($to) {
            $newsQuery->whereDate('fetched_at', '<=', $to);
            $deliveryQuery->whereDate('sent_at', '<=', $to);
        }

        return response()->streamDownload(function () use ($newsQuery, $deliveryQuery): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['News Report']);
            fputcsv($out, ['ID', 'Title', 'Source', 'Category', 'Lang', 'Published At', 'URL']);

            $newsQuery->chunk(500, function ($rows) use ($out): void {
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->id,
                        $row->title,
                        $row->source?->name,
                        $row->category,
                        $row->lang,
                        $row->published_at?->toDateTimeString(),
                        $row->source_url,
                    ]);
                }
            });

            fputcsv($out, []);
            fputcsv($out, ['Delivery Report']);
            fputcsv($out, ['ID', 'Member', 'Channel', 'Status', 'Sent At', 'Error']);

            $deliveryQuery->chunk(500, function ($rows) use ($out): void {
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->id,
                        $row->member?->name,
                        $row->channel_type,
                        $row->status,
                        $row->sent_at?->toDateTimeString(),
                        $row->error_message,
                    ]);
                }
            });

            fclose($out);
        }, 'dailynews-report-'.date('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }
}
