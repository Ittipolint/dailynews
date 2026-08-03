<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Member;
use App\Models\MemberChannel;
use App\Models\MemberInterest;
use App\Models\MemberSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    protected function resolveMember(): ?Member
    {
        $user = Auth::user();

        return Member::where('email', $user->email)->first();
    }

    public function index(): View
    {
        return view('member.dashboard', [
            'member' => $this->resolveMember(),
        ]);
    }

    public function channels(): View
    {
        return view('member.channels', [
            'member' => $this->resolveMember(),
            'channels' => $this->resolveMember()?->channels()->get() ?? collect(),
        ]);
    }

    public function storeChannel(Request $request): RedirectResponse
    {
        $member = $this->resolveMember();

        abort_unless($member, 404, 'ไม่พบข้อมูลสมาชิก');

        $data = $request->validate([
            'channel_type' => ['required', 'in:line_personal,line_oa,email'],
            'credentials' => ['nullable', 'array'],
        ]);

        MemberChannel::updateOrCreate(
            ['member_id' => $member->id, 'channel_type' => $data['channel_type']],
            [
                'credentials' => $data['credentials'] ?? [],
                'is_active' => true,
            ]
        );

        return redirect()->route('member.channels')->with('success', 'บันทึกช่องทางเรียบร้อย');
    }

    public function interests(): View
    {
        return view('member.interests', [
            'member' => $this->resolveMember(),
            'interests' => $this->resolveMember()?->interests()->get() ?? collect(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeInterest(Request $request): RedirectResponse
    {
        $member = $this->resolveMember();

        abort_unless($member, 404, 'ไม่พบข้อมูลสมาชิก');

        $data = $request->validate([
            'type' => ['required', 'in:category,tag,keyword'],
            'value' => ['required', 'string', 'max:255'],
        ]);

        if ($data['type'] === 'category') {
            $valid = Category::where('is_active', true)
                ->whereIn('code', [$data['value']])
                ->exists();

            abort_unless($valid, 422, 'หมวดหมู่ที่เลือกไม่ถูกต้อง');
        }

        MemberInterest::firstOrCreate(
            ['member_id' => $member->id, 'type' => $data['type'], 'value' => $data['value']],
            ['is_active' => true]
        );

        return redirect()->route('member.interests')->with('success', 'บันทึกหัวข้อที่สนใจเรียบร้อย');
    }

    public function schedules(): View
    {
        return view('member.schedules', [
            'member' => $this->resolveMember(),
            'schedules' => $this->resolveMember()?->schedules()->get() ?? collect(),
        ]);
    }

    public function storeSchedule(Request $request): RedirectResponse
    {
        $member = $this->resolveMember();

        abort_unless($member, 404, 'ไม่พบข้อมูลสมาชิก');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cron_expression' => ['required', 'string', 'max:100'],
            'channels' => ['required', 'array'],
            'channels.*' => ['in:line_personal,line_oa,email'],
            'categories' => ['nullable', 'array'],
            'languages' => ['nullable', 'array'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $member->schedules()->create([
            ...$data,
            'languages' => $data['languages'] ?? ['th'],
            'limit' => $data['limit'] ?? 10,
            'is_active' => true,
        ]);

        return redirect()->route('member.schedules')->with('success', 'บันทึกตารางเวลาส่งข่าวเรียบร้อย');
    }
}
