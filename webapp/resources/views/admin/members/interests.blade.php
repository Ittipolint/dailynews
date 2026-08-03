@extends('layouts.app')

@section('title', 'หัวข้อที่สนใจของสมาชิก')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">หัวข้อข่าวที่สนใจ — {{ $member->name }}</h4>
    <a href="{{ route('admin.members.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>กลับ</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.members.interests.store', $member) }}">
            @csrf
            <div class="row g-2">
                <div class="col-md-3">
                    <select name="type" id="interest-type" class="form-select" required>
                        <option value="category">หมวดหมู่ (Category)</option>
                        <option value="tag">แท็ก (Tag)</option>
                        <option value="keyword">คำค้น (Keyword)</option>
                    </select>
                </div>
                <div class="col-md-7">
                    <select name="value" id="interest-category" class="form-select" required>
                        <option value="">— เลือกหมวดหมู่ —</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->code }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="value" id="interest-value" class="form-control d-none" placeholder="เช่น technology / AI / เศรษฐกิจ">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i>เพิ่ม</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>ประเภท</th><th>ค่า</th><th class="text-end">จัดการ</th></tr>
            </thead>
            <tbody>
                @forelse ($interests as $interest)
                    <tr>
                        <td><span class="badge bg-info">{{ $interest->type }}</span></td>
                        <td>{{ $interest->value }}</td>
                        <td class="text-end">
                            <form action="{{ route('admin.members.interests.destroy', $interest) }}" method="POST" class="d-inline" onsubmit="return confirm('ลบรายการนี้?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-secondary py-4">ยังไม่มีหัวข้อที่สนใจ</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const type = document.getElementById('interest-type');
    const category = document.getElementById('interest-category');
    const value = document.getElementById('interest-value');

    function sync() {
        const isCategory = type.value === 'category';
        category.classList.toggle('d-none', !isCategory);
        value.classList.toggle('d-none', isCategory);
        category.disabled = !isCategory;
        value.disabled = isCategory;
        category.required = isCategory;
        value.required = !isCategory;
        value.value = isCategory ? '' : value.value;
        category.value = isCategory ? category.value : '';
    }

    type.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
