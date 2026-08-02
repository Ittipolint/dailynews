@extends('layouts.app')

@section('title', 'ตารางเวลาส่งข่าว')
@section('content')
<h4 class="mb-4">ตารางเวลาส่งข่าว</h4>

<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('member.schedules.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">ชื่อ schedule</label>
                    <input type="text" name="name" class="form-control" placeholder="ข่าวเช้า" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cron expression</label>
                    <input type="text" name="cron_expression" class="form-control" value="0 8 * * *" required>
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
                                <input type="checkbox" class="form-check-input" name="channels[]" value="{{ $channel->value }}" id="mch-{{ $channel->value }}" checked>
                                <label class="form-check-label" for="mch-{{ $channel->value }}">{{ $channel->label() }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr><th>ชื่อ</th><th>Cron</th><th>ช่องทาง</th><th>สถานะ</th></tr>
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
                        <td><span class="badge {{ $schedule->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $schedule->is_active ? 'Active' : 'Inactive' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-secondary py-4">ยังไม่มีตารางเวลา</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
