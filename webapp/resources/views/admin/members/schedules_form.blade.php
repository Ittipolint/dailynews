@extends('layouts.app')

@section('title', 'แก้ไขตารางเวลาส่งข่าว')
@section('content')
@php
    $cronParts = array_pad(preg_split('/\s+/', $schedule->cron_expression), 5, '*');
    [$min, $hour, $dom, $month, $dow] = $cronParts;
    $freq = ($dom === '*' && ($dow === '*' || $dow === '')) ? 'daily' : (($dow !== '*' && $dow !== '') ? 'weekly' : 'monthly');
    $schTime = str_pad($hour, 2, '0', STR_PAD_LEFT).':'.str_pad($min, 2, '0', STR_PAD_LEFT);
    $schDowArr = $freq === 'weekly' ? explode(',', $dow) : [];
    $selChannels = $schedule->channels ?? [];
    $selCats = $schedule->categories ?? [];
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">แก้ไขตารางเวลา — {{ $schedule->name }}</h4>
    <a href="{{ route('admin.members.schedules.index', $member) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>กลับ</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.members.schedules.update', $schedule) }}">
            @csrf
            @method('PATCH')
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">ชื่อ schedule</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $schedule->name) }}" placeholder="เช่น ข่าวเช้า" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">ความถี่</label>
                    <select name="freq" id="sch-freq" class="form-select" required>
                        <option value="daily" @selected($freq === 'daily')>ทุกวัน</option>
                        <option value="weekly" @selected($freq === 'weekly')>รายสัปดาห์</option>
                        <option value="monthly" @selected($freq === 'monthly')>รายเดือน</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">เวลา</label>
                    <input type="time" name="sch_time" id="sch-time" class="form-control" value="{{ $schTime }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">จำนวนข่าวสูงสุด</label>
                    <input type="number" name="limit" class="form-control" value="{{ $schedule->limit }}" min="1" max="50">
                </div>
                <div class="col-md-12 d-none" id="sch-dow-wrap">
                    <label class="form-label">เลือกวัน</label>
                    <div class="d-flex gap-2 flex-wrap" id="sch-dow">
                        <div class="form-check"><input type="checkbox" name="sch_dow[]" class="form-check-input" value="1" @checked(in_array('1', $schDowArr))><label class="form-check-label">จ</label></div>
                        <div class="form-check"><input type="checkbox" name="sch_dow[]" class="form-check-input" value="2" @checked(in_array('2', $schDowArr))><label class="form-check-label">อ</label></div>
                        <div class="form-check"><input type="checkbox" name="sch_dow[]" class="form-check-input" value="3" @checked(in_array('3', $schDowArr))><label class="form-check-label">พ</label></div>
                        <div class="form-check"><input type="checkbox" name="sch_dow[]" class="form-check-input" value="4" @checked(in_array('4', $schDowArr))><label class="form-check-label">พฤ</label></div>
                        <div class="form-check"><input type="checkbox" name="sch_dow[]" class="form-check-input" value="5" @checked(in_array('5', $schDowArr))><label class="form-check-label">ศ</label></div>
                        <div class="form-check"><input type="checkbox" name="sch_dow[]" class="form-check-input" value="6" @checked(in_array('6', $schDowArr))><label class="form-check-label">ส</label></div>
                        <div class="form-check"><input type="checkbox" name="sch_dow[]" class="form-check-input" value="0" @checked(in_array('0', $schDowArr))><label class="form-check-label">อา</label></div>
                    </div>
                </div>
                <div class="col-md-4 d-none" id="sch-dom-wrap">
                    <label class="form-label">วันของเดือน</label>
                    <select id="sch-dom" name="sch_dom" class="form-select">
                        @for ($i = 1; $i <= 28; $i++)
                            <option value="{{ $i }}" @selected($freq === 'monthly' && $dom === (string) $i)>วันที่ {{ $i }}</option>
                        @endfor
                        <option value="L" @selected($freq === 'monthly' && $dom === 'L')>วันสุดท้ายของเดือน</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Cron expression (สร้างอัตโนมัติ)</label>
                    <input type="text" name="cron_expression" id="sch-cron" class="form-control font-monospace" value="{{ $schedule->cron_expression }}" required readonly>
                    <div class="form-text">แสดงผลอัตโนมัติจากตัวเลือกด้านบน — แก้ไขเองได้ในโหมดขั้นสูง</div>
                    <div class="form-check form-switch mt-1">
                        <input type="checkbox" class="form-check-input" id="sch-advanced">
                        <label class="form-check-label small" for="sch-advanced">โหมดขั้นสูง (แก้ไข cron ด้วยตนเอง)</label>
                    </div>
                </div>
                <div class="col-md-12">
                    <label class="form-label">ช่องทาง *</label>
                    <div class="d-flex gap-3">
                        @foreach (\App\Enums\ChannelType::cases() as $channel)
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="channels[]" value="{{ $channel->value }}" id="ch-{{ $channel->value }}" @checked(in_array($channel->value, $selChannels))>
                                <label class="form-check-label" for="ch-{{ $channel->value }}">{{ $channel->label() }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-12">
                    <label class="form-label">หมวดหมู่ <span class="text-secondary fw-normal">(ไม่เลือกเลย = ทั้งหมด)</span></label>
                    <div class="input-group">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="sch-cats-clear">ล้างทั้งหมด</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="sch-cats-all">เลือกทั้งหมด</button>
                    </div>
                    <div class="form-control overflow-auto mt-1" style="max-height: 150px;">
                        @foreach ($categories as $category)
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="categories[]" value="{{ $category->code }}" id="sch-cat-{{ $category->code }}" @checked(in_array($category->code, $selCats))>
                                <label class="form-check-label" for="sch-cat-{{ $category->code }}">{{ $category->name }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>บันทึก</button>
                </div>
            </div>
        </form>
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

    const dows = Array.from(document.querySelectorAll('#sch-dow .form-check-input'));

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

    const catClear = document.getElementById('sch-cats-clear');
    const catAll = document.getElementById('sch-cats-all');
    const catsBoxes = Array.from(document.querySelectorAll('input[name="categories[]"]'));
    if (catClear) catClear.addEventListener('click', () => catsBoxes.forEach(b => { b.checked = false; }));
    if (catAll) catAll.addEventListener('click', () => catsBoxes.forEach(b => b.checked = true));
})();
</script>
@endpush