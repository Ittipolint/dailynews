@extends('layouts.app')

@section('title', $source->exists ? 'แก้ไขแหล่งข่าว' : 'เพิ่มแหล่งข่าว')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="mb-4">{{ $source->exists ? 'แก้ไขแหล่งข่าว' : 'เพิ่มแหล่งข่าว' }}</h4>
                <form method="POST" action="{{ $source->exists ? route('admin.sources.update', $source) : route('admin.sources.store') }}">
                    @csrf
                    @if ($source->exists) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ชื่อ *</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $source->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ภาษา (Locale) *</label>
                            <select name="locale" class="form-select" required>
                                @foreach (['en' => 'English', 'th' => 'Thai', 'zh' => 'Chinese', 'ja' => 'Japanese', 'de' => 'German', 'fr' => 'French'] as $code => $label)
                                    <option value="{{ $code }}" @selected(old('locale', $source->locale) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">รูปแบบการดึงข้อมูล *</label>
                            <select name="fetch_type" class="form-select" required>
                                @foreach (\App\Enums\FetchType::cases() as $type)
                                    <option value="{{ $type->value }}" @selected(old('fetch_type', $source->fetch_type) === $type->value)>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Website URL</label>
                            <input type="url" name="url" class="form-control" value="{{ old('url', $source->url) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Feed URL / API Endpoint</label>
                            <input type="url" name="feed_url" class="form-control" value="{{ old('feed_url', $source->feed_url) }}" placeholder="https://example.com/rss">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ความถี่การดึงข้อมูล</label>
                            <select name="freq" id="freq" class="form-select" required>
                                <option value="hourly" data-default-cron="0 * * * *">ทุก 1 ชม.</option>
                                <option value="daily" data-default-cron="0 8 * * *">ทุกวัน</option>
                                <option value="weekly" data-default-cron="0 8 * * 1">รายสัปดาห์</option>
                                <option value="monthly" data-default-cron="0 8 1 * *">รายเดือน</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">เวลา (กรณีทุกวัน / รายสัปดาห์ / รายเดือน)</label>
                            <input type="time" id="time" class="form-control" value="08:00">
                        </div>
                        <div class="col-md-3 d-none" id="dow-wrap">
                            <label class="form-label">วันในสัปดาห์</label>
                            <select id="dow" class="form-select">
                                <option value="1">จันทร์</option>
                                <option value="2">อังคาร</option>
                                <option value="3">พุธ</option>
                                <option value="4">พฤหัส</option>
                                <option value="5">ศุกร์</option>
                                <option value="6">เสาร์</option>
                                <option value="0">อาทิตย์</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-none" id="dom-wrap">
                            <label class="form-label">วันของเดือน</label>
                            <select id="dom" class="form-select">
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
                        <div class="col-md-6">
                            <label class="form-label">Cron Expression (สร้างอัตโนมัติ)</label>
                            <input type="text" name="cron_expression" id="cron" class="form-control font-monospace" value="{{ old('cron_expression', $source->cron_expression ?? '0 * * * *') }}" required>
                            <div class="form-text">แสดงผลอัตโนมัติจากตัวเลือกด้านบน</div>
                            <div class="form-check form-switch mt-1">
                                <input type="checkbox" class="form-check-input" id="advanced">
                                <label class="form-check-label small" for="advanced">โหมดขั้นสูง (แก้ไข cron ด้วยตนเอง)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">หมวดหมู่เริ่มต้น <span class="text-secondary fw-normal">(ไม่เลือกเลย = ทั้งหมด)</span></label>
                            <div class="input-group mt-1">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="cats-clear">ล้างทั้งหมด</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="cats-all">เลือกทั้งหมด</button>
                            </div>
                            <div class="form-control overflow-auto mt-2" style="max-height: 150px;">
                                @foreach ($categories as $category)
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="categories[]" value="{{ $category->code }}" id="cat-{{ $category->code }}" @checked(in_array($category->code, $selectedCategories, true))>
                                        <label class="form-check-label" for="cat-{{ $category->code }}">{{ $category->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" @checked(old('is_active', $source->is_active ?? true))>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.sources.index') }}" class="btn btn-secondary">ยกเลิก</a>
                        <button type="submit" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const freq = document.getElementById('freq');
    const time = document.getElementById('time');
    const dowWrap = document.getElementById('dow-wrap');
    const domWrap = document.getElementById('dom-wrap');
    const dow = document.getElementById('dow');
    const dom = document.getElementById('dom');
    const cron = document.getElementById('cron');
    const advanced = document.getElementById('advanced');

    const initialCron = (cron.value || '').trim();

    function buildCron() {
        const [h, m] = (time.value || '08:00').split(':');
        const f = freq.value;
        if (f === 'hourly') return '0 * * * *';
        if (f === 'daily') return `${m} ${h} * * *`;
        if (f === 'weekly') return `${m} ${h} * * ${dow.value}`;
        return `${m} ${h} ${dom.value} * *`;
    }

    function sync() {
        const f = freq.value;
        dowWrap.classList.toggle('d-none', f !== 'weekly');
        domWrap.classList.toggle('d-none', f !== 'monthly');
        if (!advanced.checked) cron.value = buildCron();
    }

    function applyStoredCron() {
        const parts = initialCron.split(/\s+/);
        if (parts.length !== 5) return false;
        const [mm, hh, domV, monV, dowV] = parts;
        const restStars = parts.slice(1).every(p => p === '*');
        if (mm === '0' && restStars) { freq.value = 'hourly'; return true; }
        if (mm !== '*' && hh !== '*' && domV === '*' && monV === '*' && dowV === '*') {
            freq.value = 'daily';
            time.value = `${hh}:${mm}`;
            return true;
        }
        if (mm !== '*' && hh !== '*' && domV === '*' && monV === '*' && dowV !== '*' && dowV !== '*') {
            freq.value = 'weekly';
            time.value = `${hh}:${mm}`;
            dow.value = dowV.includes(',') ? dowV.split(',')[0] : dowV;
            return true;
        }
        if (mm !== '*' && hh !== '*' && domV !== '*' && monV === '*' && dowV === '*') {
            freq.value = 'monthly';
            time.value = `${hh}:${mm}`;
            dom.value = domV;
            return true;
        }
        return false;
    }

    if (initialCron && applyStoredCron()) {
        advanced.checked = false;
        cron.readOnly = true;
        sync();
    } else {
        advanced.checked = true;
        cron.readOnly = false;
    }

    freq.addEventListener('change', sync);
    time.addEventListener('input', sync);
    dow.addEventListener('change', sync);
    dom.addEventListener('change', sync);
    advanced.addEventListener('change', function () {
        cron.readOnly = !this.checked;
        if (!this.checked) cron.value = buildCron();
    });

    const catClear = document.getElementById('cats-clear');
    const catAll = document.getElementById('cats-all');
    const catsBoxes = Array.from(document.querySelectorAll('input[name="categories[]"]'));
    if (catClear) catClear.addEventListener('click', () => catsBoxes.forEach(b => { b.checked = false; }));
    if (catAll) catAll.addEventListener('click', () => catsBoxes.forEach(b => { b.checked = true; }));
})();
</script>
@endpush
