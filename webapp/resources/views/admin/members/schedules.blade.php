@extends('layouts.app')

@section('title', 'ตารางเวลาส่งข่าวของสมาชิก')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">ตารางเวลาส่งข่าว — {{ $member->name }}</h4>
    <a href="{{ route('admin.members.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>กลับ</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <h6>เพิ่มตารางเวลา</h6>
        <form method="POST" action="{{ route('admin.members.schedules.store', $member) }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">ชื่อ schedule</label>
                    <input type="text" name="name" class="form-control" placeholder="เช่น ข่าวเช้า" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cron expression</label>
                    <input type="text" name="cron_expression" class="form-control" value="0 8 * * *" required>
                    <div class="form-text">เช่น 0 8 * * * = ทุกวัน 08:00 น.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">จำนวนข่าวสูงสุด</label>
                    <input type="number" name="limit" class="form-control" value="10" min="1" max="50">
                </div>
                <div class="col-md-12">
                    <label class="form-label">ช่องทาง *</label>
                    <div class="d-flex gap-3">
                        @foreach (\App\Enums\ChannelType::cases() as $channel)
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="channels[]" value="{{ $channel->value }}" id="ch-{{ $channel->value }}" checked>
                                <label class="form-check-label" for="ch-{{ $channel->value }}">{{ $channel->label() }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-12">
                    <label class="form-label">หมวดหมู่ (ไม่ระบุ = ทั้งหมด)</label>
                    <input type="text" name="categories[]" class="form-control" placeholder="technology, business">
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">เพิ่ม</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>ชื่อ</th><th>Cron</th><th>ช่องทาง</th><th>หมวดหมู่</th><th>สถานะ</th><th class="text-end">จัดการ</th></tr>
            </thead>
            <tbody>
                @forelse ($schedules as $schedule)
                    <tr>
                        <td><strong>{{ $schedule->name }}</strong></td>
                        <td><code>{{ $schedule->cron_expression }}</code></td>
                        <td>
                            @foreach ($schedule->channels ?? [] as $ch)
                                <span class="badge bg-info">{{ $ch }}</span>
                            @endforeach
                        </td>
                        <td class="small">{{ implode(', ', $schedule->categories ?? []) ?: '-' }}</td>
                        <td><span class="badge {{ $schedule->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $schedule->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-end">
                            <form action="{{ route('admin.members.schedules.destroy', $schedule) }}" method="POST" class="d-inline" onsubmit="return confirm('ลบตารางเวลานี้?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-secondary py-4">ยังไม่มีตารางเวลา</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
