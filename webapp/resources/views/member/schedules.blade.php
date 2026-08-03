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
                    <label class="form-label">ความถี่</label>
                    <select name="freq" id="sch-freq" class="form-select" required>
                        <option value="daily">ทุกวัน</option>
                        <option value="weekly">รายสัปดาห์</option>
                        <option value="monthly">รายเดือน</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">เวลา</label>
                    <input type="time" name="sch_time" id="sch-time" class="form-control" value="08:00" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">จำนวนข่าวสูงสุด</label>
                    <input type="number" name="limit" class="form-control" value="10" min="1" max="50">
                </div>
                <div class="col-md-12 d-none" id="sch-dow-wrap">
                    <label class="form-label">เลือกวัน</label>
                    <div class="d-flex gap-2 flex-wrap" id="sch-dow">
                        <div class="form-check"><input type="checkbox" class="form-check-input sch-dow-cb" value="1"><label class="form-check-label">จ</label></div>
                        <div class="form-check"><input type="checkbox" class="form-check-input sch-dow-cb" value="2"><label class="form-check-label">อ</label></div>
                        <div class="form-check"><input type="checkbox" class="form-check-input sch-dow-cb" value="3"><label class="form-check-label">พ</label></div>
                        <div class="form-check"><input type="checkbox" class="form-check-input sch-dow-cb" value="4"><label class="form-check-label">พฤ</label></div>
                        <div class="form-check"><input type="checkbox" class="form-check-input sch-dow-cb" value="5"><label class="form-check-label">ศ</label></div>
                        <div class="form-check"><input type="checkbox" class="form-check-input sch-dow-cb" value="6"><label class="form-check-label">ส</label></div>
                        <div class="form-check"><input type="checkbox" class="form-check-input sch-dow-cb" value="0"><label class="form-check-label">อา</label></div>
                    </div>
                </div>
                <div class="col-md-4 d-none" id="sch-dom-wrap">
                    <label class="form-label">วันของเดือน</label>
                    <select id="sch-dom" class="form-select">
                        <option value="1">วันที่ 1</option>
                        <option value="2">วันที่ 2</option>
                        <option value="3">วันที่ 3</option>
                        <option value="4">วันที่ 4</option>
                        <option value="5">วันที่ 5</option>
                        <option value="6">วันที่ 6</option>
                        <option value="7">วันที่ 7</option>
                        <option value="8">วันที่ 8</option>
                        <option value="9">วันที่ 9</option>
                        <option value="10">วันที่ 10</option>
                        <option value="11">วันที่ 11</option>
                        <option value="12">วันที่ 12</option>
                        <option value="13">วันที่ 13</option>
                        <option value="14">วันที่ 14</option>
                        <option value="15">วันที่ 15</option>
                        <option value="16">วันที่ 16</option>
                        <option value="17">วันที่ 17</option>
                        <option value="18">วันที่ 18</option>
                        <option value="19">วันที่ 19</option>
                        <option value="20">วันที่ 20</option>
                        <option value="21">วันที่ 21</option>
                        <option value="22">วันที่ 22</option>
                        <option value="23">วันที่ 23</option>
                        <option value="24">วันที่ 24</option>
                        <option value="25">วันที่ 25</option>
                        <option value="26">วันที่ 26</option>
                        <option value="27">วันที่ 27</option>
                        <option value="28">วันที่ 28</option>
                        <option value="L">วันสุดท้ายของเดือน</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Cron expression (สร้างอัตโนมัติ)</label>
                    <input type="text" name="cron_expression" id="sch-cron" class="form-control font-monospace" value="0 8 * * *" required readonly>
                    <div class="form-text">แสดงผลอัตโนมัติจากตัวเลือกด้านบน — แก้ไขเองได้ในโหมดขั้นสูง</div>
                    <div class="form-check form-switch mt-1">
                        <input type="checkbox" class="form-check-input" id="sch-advanced">
                        <label class="form-check-label small" for="sch-advanced">โหมดขั้นสูง (แก้ไข cron ด้วยตนเอง)</label>
                    </div>
                </div>
                <div class="col-md-12">
                    <label class="form-label">หมวดหมู่ <span class="text-secondary fw-normal">(ไม่เลือกเลย = ทั้งหมด)</span></label>
                    <div class="input-group">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="cats-clear">ล้างทั้งหมด</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="cats-all">เลือกทั้งหมด</button>
                    </div>
                    <div class="form-control overflow-auto mt-1" style="max-height: 150px;">
                        @foreach ($categories as $category)
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="categories[]" value="{{ $category->code }}" id="cat-{{ $category->code }}">
                                <label class="form-check-label" for="cat-{{ $category->code }}">{{ $category->name }}</label>
                            </div>
                        @endforeach
                    </div>
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
                <tr><th>ชื่อ</th><th>ตารางเวลา</th><th>ช่องทาง</th><th>ทำงานล่าสุด</th><th>สถานะ</th></tr>
            </thead>
            <tbody>
                @forelse ($schedules as $schedule)
                    @php
                        $lastLog = $schedule->deliveryLogs->first();
                        $lastOk = $lastLog && $lastLog->status === 'success';
                        $lastFailed = $lastLog && $lastLog->status === 'failed';
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $schedule->name }}</strong>
                            <div class="small text-secondary"><code>{{ $schedule->cron_expression }}</code></div>
                        </td>
                        <td>{{ $schedule->humanSchedule() }}</td>
                        <td>
                            @foreach ($schedule->channels ?? [] as $ch)
                                <span class="badge bg-info">{{ $ch }}</span>
                            @endforeach
                        </td>
                        <td>
                            @if ($lastLog)
                                <span class="badge {{ $lastOk ? 'bg-success' : ($lastFailed ? 'bg-danger' : 'bg-secondary') }}">
                                    {{ $lastOk ? 'สำเร็จ' : ($lastFailed ? 'ล้มเหลว' : $lastLog->status) }}
                                </span>
                                <div class="small text-secondary">{{ $lastLog->sent_at?->diffForHumans() ?? '-' }}</div>
                            @else
                                <span class="text-secondary small">ยังไม่เคยทำงาน</span>
                            @endif
                        </td>
                        <td><span class="badge {{ $schedule->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $schedule->is_active ? 'Active' : 'Inactive' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-4">ยังไม่มีตารางเวลา</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const freq = document.getElementById('sch-freq');
    const time = document.getElementById('sch-time');
    const dowWrap = document.getElementById('sch-dow-wrap');
    const domWrap = document.getElementById('sch-dom-wrap');
    const dom = document.getElementById('sch-dom');
    const cron = document.getElementById('sch-cron');
    const advanced = document.getElementById('sch-advanced');

    const dows = Array.from(document.querySelectorAll('.sch-dow-cb'));

    function buildCron() {
        const [h, m] = (time.value || '08:00').split(':');
        if (freq.value === 'daily') return `${m} ${h} * * *`;
        if (freq.value === 'weekly') {
            const days = dows.filter(d => d.checked).map(d => d.value).sort();
            return days.length ? `${m} ${h} * * ${days.join(',')}` : `${m} ${h} * * *`;
        }
        return `${m} ${h} ${dom.value} * *`;
    }

    function sync() {
        const isWeekly = freq.value === 'weekly';
        const isMonthly = freq.value === 'monthly';
        dowWrap.classList.toggle('d-none', !isWeekly);
        domWrap.classList.toggle('d-none', !isMonthly);
        if (advanced.checked) {
            cron.readOnly = false;
        } else {
            cron.readOnly = true;
            cron.value = buildCron();
        }
    }

    freq.addEventListener('change', sync);
    time.addEventListener('input', sync);
    dows.forEach(d => d.addEventListener('change', sync));
    dom.addEventListener('change', sync);
    advanced.addEventListener('change', sync);
    sync();

    const catClear = document.getElementById('cats-clear');
    const catAll = document.getElementById('cats-all');
    const catsBoxes = Array.from(document.querySelectorAll('input[name="categories[]"]'));
    if (catClear) catClear.addEventListener('click', () => catsBoxes.forEach(b => { b.checked = false; }));
    if (catAll) catAll.addEventListener('click', () => catsBoxes.forEach(b => { b.checked = true; }));
})();
</script>
@endpush
