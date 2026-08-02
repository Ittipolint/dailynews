<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\MemberChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberChannelController extends Controller
{
    public function index(Member $member): View
    {
        $channels = $member->channels()->get();

        return view('admin.members.channels', compact('member', 'channels'));
    }

    public function store(Request $request, Member $member): RedirectResponse
    {
        $data = $request->validate([
            'channel_type' => ['required', 'in:line_personal,line_oa,email'],
            'credentials' => ['nullable', 'array'],
        ]);

        $channel = MemberChannel::updateOrCreate(
            ['member_id' => $member->id, 'channel_type' => $data['channel_type']],
            ['credentials' => $data['credentials'] ?? [], 'is_active' => true]
        );

        AuditLog::record('member_channel', 'store', (string) $channel->id, null, $data);

        return redirect()->route('admin.members.channels.index', $member)->with('success', 'เพิ่มช่องทางเรียบร้อย');
    }

    public function update(Request $request, MemberChannel $channel): RedirectResponse
    {
        $data = $request->validate([
            'credentials' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $channel->update([
            'credentials' => $data['credentials'] ?? $channel->credentials,
            'is_active' => $request->boolean('is_active', $channel->is_active),
        ]);

        AuditLog::record('member_channel', 'update', (string) $channel->id);

        return redirect()->route('admin.members.channels.index', $channel->member)->with('success', 'แก้ไขช่องทางเรียบร้อย');
    }

    public function destroy(MemberChannel $channel): RedirectResponse
    {
        $member = $channel->member;
        AuditLog::record('member_channel', 'delete', (string) $channel->id);
        $channel->delete();

        return redirect()->route('admin.members.channels.index', $member)->with('success', 'ลบช่องทางเรียบร้อย');
    }
}
