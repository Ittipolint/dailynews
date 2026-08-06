@extends('layouts.app')

@section('title', 'จัดการสมาชิก')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">สมาชิก</h4>
    <a href="{{ route('admin.members.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>เพิ่มสมาชิก</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ชื่อ</th>
                    <th>ประเภท</th>
                    <th>อีเมล</th>
                    <th>LINE</th>
                    <th>ภาษา</th>
                    <th>สถานะ</th>
                    <th>การตั้งค่า</th>
                    <th class="text-end">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($members as $member)
                    <tr>
                        <td><strong>{{ $member->name }}</strong></td>
                        <td><span class="badge bg-primary">{{ $member->type?->name ?? '-' }}</span></td>
                        <td class="small">{{ $member->email ?? '-' }}</td>
                        <td class="small">
                            @if ($member->line_user_id)<i class="bi bi-person-lines-fill text-success" title="LINE ส่วนตัว"></i>@endif
                            @if ($member->line_oa_user_id)<i class="bi bi-building text-primary ms-1" title="LINE OA"></i>@endif
                        </td>
                        <td><span class="badge bg-secondary text-uppercase">{{ $member->preferred_locale }}</span></td>
                        <td>
                            @php
                                $memberStatus = \App\Enums\MemberStatus::tryFrom($member->status);
                                $statusColor = match ($member->status) {
                                    'active' => 'bg-success',
                                    'trial' => 'bg-info',
                                    'suspended' => 'bg-danger',
                                    'expired' => 'bg-warning text-dark',
                                    default => 'bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $statusColor }}">{{ $memberStatus?->label() ?? $member->status }}</span>
                        </td>
                        <td class="small">
                            <a href="{{ route('admin.members.schedules.index', $member) }}">ตารางเวลา</a>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.members.edit', $member) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('admin.members.toggle', $member) }}" method="POST" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm {{ $member->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                    <i class="bi {{ $member->is_active ? 'bi-pause' : 'bi-play' }}"></i>
                                </button>
                            </form>
                            <form action="{{ route('admin.members.destroy', $member) }}" method="POST" class="d-inline" onsubmit="return confirm('ลบสมาชิกนี้?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-secondary py-4">ยังไม่มีสมาชิก</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $members->links() }}</div>
@endsection
