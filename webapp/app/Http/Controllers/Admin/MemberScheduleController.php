<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\MemberSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberScheduleController extends Controller
{
    public function index(Member $member): View
    {
        $schedules = $member->schedules()->get();

        return view('admin.members.schedules', compact('member', 'schedules'));
    }

    public function store(Request $request, Member $member): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cron_expression' => ['required', 'string', 'max:100'],
            'channels' => ['required', 'array'],
            'channels.*' => ['in:line_personal,line_oa,email'],
            'categories' => ['nullable', 'array'],
            'languages' => ['nullable', 'array'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $schedule = $member->schedules()->create([
            ...$data,
            'languages' => $data['languages'] ?? ['th'],
            'limit' => $data['limit'] ?? 10,
            'is_active' => true,
        ]);

        AuditLog::record('member_schedule', 'store', (string) $schedule->id);

        return redirect()->route('admin.members.schedules.index', $member)->with('success', 'เพิ่มตารางเวลาส่งข่าวเรียบร้อย');
    }

    public function update(Request $request, MemberSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cron_expression' => ['required', 'string', 'max:100'],
            'channels' => ['required', 'array'],
            'channels.*' => ['in:line_personal,line_oa,email'],
            'categories' => ['nullable', 'array'],
            'languages' => ['nullable', 'array'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $schedule->update([
            ...$data,
            'is_active' => $request->boolean('is_active', $schedule->is_active),
        ]);

        AuditLog::record('member_schedule', 'update', (string) $schedule->id);

        return redirect()->route('admin.members.schedules.index', $schedule->member)->with('success', 'แก้ไขตารางเวลาเรียบร้อย');
    }

    public function destroy(MemberSchedule $schedule): RedirectResponse
    {
        $member = $schedule->member;
        AuditLog::record('member_schedule', 'delete', (string) $schedule->id);
        $schedule->delete();

        return redirect()->route('admin.members.schedules.index', $member)->with('success', 'ลบตารางเวลาเรียบร้อย');
    }
}
