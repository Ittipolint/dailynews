<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Member;
use App\Models\MemberSchedule;
use App\Services\Delivery\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberScheduleController extends Controller
{
    public function index(Member $member): View
    {
        $schedules = $member->schedules()
            ->with(['deliveryLogs' => fn ($q) => $q->orderByDesc('sent_at')])
            ->get();
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('admin.members.schedules', compact('member', 'schedules', 'categories'));
    }

    public function store(Request $request, Member $member): RedirectResponse
    {
        $data = $this->validated($request);

        $schedule = $member->schedules()->create([
            ...$data,
            'cron_expression' => $this->cronExpression($request),
            'languages' => $data['languages'] ?? ['th'],
            'limit' => $data['limit'] ?? 10,
            'is_active' => true,
        ]);

        AuditLog::record('member_schedule', 'store', (string) $schedule->id);

        return redirect()->route('admin.members.schedules.index', $member)->with('success', 'เพิ่มตารางเวลาส่งข่าวเรียบร้อย');
    }

    public function edit(MemberSchedule $schedule): View
    {
        return view('admin.members.schedules_form', [
            'schedule' => $schedule,
            'member' => $schedule->member,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, MemberSchedule $schedule): RedirectResponse
    {
        $data = $this->validated($request);

        $schedule->update([
            ...$data,
            'cron_expression' => $this->cronExpression($request),
            'is_active' => $request->boolean('is_active', $schedule->is_active),
        ]);

        AuditLog::record('member_schedule', 'update', (string) $schedule->id);

        return redirect()->route('admin.members.schedules.index', $schedule->member)->with('success', 'แก้ไขตารางเวลาเรียบร้อย');
    }

    /**
     * Immediately deliver to this schedule's channels using its own news
     * collection rules (categories, keyword interests, limit). Returns JSON
     * for the schedule page "ส่งข่าว" button.
     */
    public function sendNews(MemberSchedule $schedule): JsonResponse
    {
        if ($schedule->member->is_active && ! $schedule->is_active) {
            return response()->json(['ok' => false, 'error' => 'ตารางเวลานี้ถูกปิดใช้งาน'], 422);
        }

        $result = app(DeliveryService::class)->deliverScheduleNow($schedule);

        if (! ($result['ok'] ?? false)) {
            return response()->json(['ok' => false, 'error' => $result['error'] ?? 'ส่งข่าวไม่สำเร็จ'], 422);
        }

        AuditLog::record('member_schedule', 'send_news', (string) $schedule->id, null, [
            'channels' => $result['channels'],
            'news_count' => $result['news_count'],
        ]);

        return response()->json([
            'ok' => true,
            'news_count' => $result['news_count'],
            'channels' => $result['channels'],
            'results' => $result['results'],
        ]);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cron_expression' => ['nullable', 'string', 'max:100'],
            'freq' => ['nullable', 'in:daily,weekly,monthly'],
            'sch_time' => ['nullable', 'date_format:H:i'],
            'sch_dow' => ['nullable', 'array'],
            'sch_dow.*' => ['in:0,1,2,3,4,5,6'],
            'sch_dom' => ['nullable', 'string', 'max:2'],
            'channels' => ['required', 'array'],
            'channels.*' => ['in:line_personal,line_oa,email'],
            'categories' => ['nullable', 'array'],
            'languages' => ['nullable', 'array'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    protected function cronExpression(Request $request): string
    {
        $freq = $request->input('freq');

        if (! in_array($freq, ['daily', 'weekly', 'monthly'], true)) {
            return $request->input('cron_expression', '0 8 * * *');
        }

        [$h, $m] = array_pad(explode(':', (string) $request->input('sch_time', '08:00')), 2, '00');

        if ($freq === 'weekly') {
            $days = collect($request->input('sch_dow', []))->sort()->join(',');
            $days = $days ?: '*';

            return "{$m} {$h} * * {$days}";
        }

        if ($freq === 'monthly') {
            return "{$m} {$h} ".($request->input('sch_dom') ?: '1').' * *';
        }

        return "{$m} {$h} * * *";
    }

    public function destroy(MemberSchedule $schedule): RedirectResponse
    {
        $member = $schedule->member;
        AuditLog::record('member_schedule', 'delete', (string) $schedule->id);
        $schedule->delete();

        return redirect()->route('admin.members.schedules.index', $member)->with('success', 'ลบตารางเวลาเรียบร้อย');
    }
}
