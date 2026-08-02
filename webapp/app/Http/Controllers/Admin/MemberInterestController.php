<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\MemberInterest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberInterestController extends Controller
{
    public function index(Member $member): View
    {
        $interests = $member->interests()->get();

        return view('admin.members.interests', compact('member', 'interests'));
    }

    public function store(Request $request, Member $member): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:category,tag,keyword'],
            'value' => ['required', 'string', 'max:255'],
            'config' => ['nullable', 'array'],
        ]);

        $interest = MemberInterest::firstOrCreate(
            ['member_id' => $member->id, 'type' => $data['type'], 'value' => $data['value']],
            ['config' => $data['config'] ?? null, 'is_active' => true]
        );

        AuditLog::record('member_interest', 'store', (string) $interest->id);

        return redirect()->route('admin.members.interests.index', $member)->with('success', 'เพิ่มหัวข้อที่สนใจเรียบร้อย');
    }

    public function destroy(MemberInterest $interest): RedirectResponse
    {
        $member = $interest->member;
        AuditLog::record('member_interest', 'delete', (string) $interest->id);
        $interest->delete();

        return redirect()->route('admin.members.interests.index', $member)->with('success', 'ลบหัวข้อที่สนใจเรียบร้อย');
    }
}
