<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\MemberType;
use App\Services\Delivery\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(): View
    {
        $members = Member::with('type')->orderBy('name')->paginate(15);

        return view('admin.members.index', compact('members'));
    }

    public function create(): View
    {
        return view('admin.members.form', [
            'member' => new Member(),
            'types' => MemberType::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'member_type_id' => ['required', 'exists:member_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'line_user_id' => ['nullable', 'string', 'max:255'],
            'line_oa_user_id' => ['nullable', 'string', 'max:255'],
            'line_oa_basic_id' => ['nullable', 'string', 'max:255'],
            'line_oa_channel_id' => ['nullable', 'string', 'max:255'],
            'line_oa_channel_secret' => ['nullable', 'string'],
            'line_oa_webhook_url' => ['nullable', 'url', 'max:255'],
            'preferred_locale' => ['required', 'in:th,en,zh'],
            'status' => ['required', 'in:active,expired,trial,suspended'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $member = Member::create([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLog::record('member', 'create', (string) $member->id, null, $data);

        return redirect()->route('admin.members.index')->with('success', 'เพิ่มสมาชิกเรียบร้อย');
    }

    public function edit(Member $member): View
    {
        return view('admin.members.form', [
            'member' => $member,
            'types' => MemberType::where('is_active', true)->get(),
        ]);
    }

    public function update(Request $request, Member $member): RedirectResponse
    {
        $data = $request->validate([
            'member_type_id' => ['required', 'exists:member_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'line_user_id' => ['nullable', 'string', 'max:255'],
            'line_oa_user_id' => ['nullable', 'string', 'max:255'],
            'line_oa_basic_id' => ['nullable', 'string', 'max:255'],
            'line_oa_channel_id' => ['nullable', 'string', 'max:255'],
            'line_oa_channel_secret' => ['nullable', 'string'],
            'line_oa_webhook_url' => ['nullable', 'url', 'max:255'],
            'preferred_locale' => ['required', 'in:th,en,zh'],
            'status' => ['required', 'in:active,expired,trial,suspended'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $old = $member->only(array_keys($data));
        $member->update([...$data, 'is_active' => $request->boolean('is_active', true)]);

        AuditLog::record('member', 'update', (string) $member->id, $old, $data);

        return redirect()->route('admin.members.index')->with('success', 'แก้ไขสมาชิกเรียบร้อย');
    }

    public function destroy(Member $member): RedirectResponse
    {
        AuditLog::record('member', 'delete', (string) $member->id, ['name' => $member->name], null);
        $member->delete();

        return redirect()->route('admin.members.index')->with('success', 'ลบสมาชิกเรียบร้อย');
    }

    public function toggle(Member $member): RedirectResponse
    {
        $member->update(['is_active' => ! $member->is_active]);

        return redirect()->route('admin.members.index')->with('success', 'เปลี่ยนสถานะเรียบร้อย');
    }

    public function sendNews(Member $member): JsonResponse
    {
        if (! $member->is_active) {
            return response()->json(['ok' => false, 'error' => 'สมาชิกนี้ถูกปิดใช้งาน'], 422);
        }

        $result = app(DeliveryService::class)->deliverLatestLot($member, 5);

        if (! ($result['ok'] ?? false)) {
            return response()->json(['ok' => false, 'error' => $result['error'] ?? 'ส่งข่าวไม่สำเร็จ'], 422);
        }

        AuditLog::record('member', 'send_news', (string) $member->id, null, [
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
}
