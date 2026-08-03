@extends('layouts.app')

@section('title', 'Credentials')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Credentials ระบบ</h4>
</div>

<div class="alert alert-info">
    <i class="bi bi-shield-lock me-1"></i> Credential ทั้งหมดถูกเก็บแบบเข้ารหัส (encrypted at rest) และแสดงเฉพาะค่าที่ซ่อนไว้
</div>

<div class="row g-3">
    @forelse ($credentials as $credential)
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0"><i class="bi bi-key me-2"></i>{{ $credential->name }}</h6>
                        <span class="badge {{ $credential->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $credential->is_active ? 'Active' : 'Inactive' }}</span>
                    </div>
                    <form method="POST" action="{{ route('admin.credentials.update', $credential) }}">
                        @csrf @method('PUT')
                        @foreach (($credential->config ?? []) as $key => $value)
                            <div class="mb-2">
                                <label class="form-label small text-secondary">{{ $key }}</label>
                                <div class="input-group input-group-sm">
                                    <input type="password" name="config[{{ $key }}]" class="form-control pw-field" placeholder="••••••••••••" autocomplete="off">
                                    <button type="button" class="btn btn-outline-secondary pw-toggle" tabindex="-1" aria-label="แสดง/ซ่อนค่า"><i class="bi bi-eye"></i></button>
                                </div>
                                <div class="form-text">เว้นว่างไว้เพื่อเก็บค่าเดิม</div>
                            </div>
                        @endforeach
                        @if (empty($credential->config))
                            <div class="text-secondary small mb-2">ไม่มีค่า config ที่ตั้งไว้</div>
                        @endif
                        <button type="submit" class="btn btn-sm btn-primary mt-2">บันทึก</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card shadow-sm"><div class="card-body text-center text-secondary py-4">
                ยังไม่มี Credential — รัน seeder เพื่อสร้างรายการเริ่มต้น
            </div></div>
        </div>
    @endforelse
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.pw-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = btn.closest('.input-group').querySelector('.pw-field');
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    });
});
</script>
@endpush
