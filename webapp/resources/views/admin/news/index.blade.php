@extends('layouts.app')

@section('title', 'ค้นหาข่าว')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">ค้นหาข่าว</h4>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="{{ route('admin.news.index') }}">
            <div class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="q" class="form-control" placeholder="ค้นหาด้วย keyword..." value="{{ $filters['q'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <select name="category" class="form-select">
                        <option value="">ทุกหมวดหมู่</option>
                        <option value="general" @selected(($filters['category'] ?? '') === 'general')>General</option>
                        <option value="technology" @selected(($filters['category'] ?? '') === 'technology')>Technology</option>
                        <option value="business" @selected(($filters['category'] ?? '') === 'business')>Business</option>
                        <option value="world" @selected(($filters['category'] ?? '') === 'world')>World</option>
                        <option value="politics" @selected(($filters['category'] ?? '') === 'politics')>Politics</option>
                        <option value="sports" @selected(($filters['category'] ?? '') === 'sports')>Sports</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="source_id" class="form-select">
                        <option value="">ทุกแหล่งข่าว</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source->id }}" @selected(($filters['source_id'] ?? '') == $source->id)>{{ $source->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>ค้นหา</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <strong><i class="bi bi-trash me-1"></i>ลบข่าวแบบที่ 1: เลือกทีละข่าว</strong>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.news.destroy-many') }}" id="bulkDeleteForm" onsubmit="return confirmBulkDelete()">
            @csrf
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="selectAll" onchange="toggleAll(this)"></th>
                            <th>หัวข้อข่าว</th>
                            <th>แหล่งข่าว</th>
                            <th>หมวดหมู่</th>
                            <th>ภาษา</th>
                            <th>เผยแพร่</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($news as $item)
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="{{ $item->id }}" class="item-checkbox" onchange="updateBulkDeleteBtn()"></td>
                                <td style="max-width: 400px;">
                                    <strong>{{ $item->title }}</strong>
                                    @if ($item->summary)
                                        <div class="small text-secondary text-truncate">{{ $item->summary }}</div>
                                    @endif
                                </td>
                                <td class="small">{{ $item->source?->name }}</td>
                                <td><span class="badge bg-info">{{ $item->category }}</span></td>
                                <td><span class="badge bg-secondary text-uppercase">{{ $item->lang }}</span></td>
                                <td class="small">{{ $item->published_at?->diffForHumans() }}</td>
                                <td><a href="{{ $item->source_url }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-up-right"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-secondary py-4">ไม่พบข่าว</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-danger" id="bulkDeleteBtn" disabled>
                    <i class="bi bi-trash me-1"></i>ลบข่าวที่เลือก
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="selectAllChecked(true)">เลือกทั้งหมด</button>
                <button type="button" class="btn btn-outline-secondary" onclick="selectAllChecked(false)">ยกเลิกเลือก</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <strong><i class="bi bi-funnel me-1"></i>ลบข่าวแบบที่ 2: ตามแหล่งข่าว + ช่วงวันที่</strong>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.news.destroy-by-filter') }}" onsubmit="return confirm('ยืนยันลบข่าวตามเงื่อนไขที่เลือก? การกระทำนี้ไม่สามารถย้อนกลับได้')">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">แหล่งข่าว</label>
                    <select name="source_id" class="form-select">
                        <option value="">-- เลือกแหล่งข่าว --</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source->id }}">{{ $source->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">วันที่เริ่มต้น (From)</label>
                    <input type="date" name="from" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">วันที่สิ้นสุด (To)</label>
                    <input type="date" name="to" class="form-control">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="bi bi-trash me-1"></i>ลบตามเงื่อนไข
                    </button>
                </div>
            </div>
            <div class="form-text mt-2">ระบุแหล่งข่าว และ/หรือ ช่วงวันที่ จาก-ถึง แล้วกดลบ ระบบจะลบข่าวที่ตรงตามเงื่อนไขทั้งหมด</div>
        </form>
    </div>
</div>

<div class="mt-3">{{ $news->withQueryString()->links() }}</div>
@endsection

@push('scripts')
<script>
function toggleAll(source) {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(cb => cb.checked = source.checked);
    updateBulkDeleteBtn();
}

function selectAllChecked(check) {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(cb => cb.checked = check);
    document.getElementById('selectAll').checked = check;
    updateBulkDeleteBtn();
}

function updateBulkDeleteBtn() {
    const checked = document.querySelectorAll('.item-checkbox:checked').length;
    const btn = document.getElementById('bulkDeleteBtn');
    btn.disabled = checked === 0;
    btn.innerHTML = checked > 0 ? '<i class="bi bi-trash me-1"></i>ลบข่าวที่เลือก (' + checked + ')' : '<i class="bi bi-trash me-1"></i>ลบข่าวที่เลือก';
}

function confirmBulkDelete() {
    const checked = document.querySelectorAll('.item-checkbox:checked').length;
    if (checked === 0) return false;
    return confirm('ยืนยันลบข่าว ' + checked + ' รายการ? การกระทำนี้ไม่สามารถย้อนกลับได้');
}
</script>
@endpush