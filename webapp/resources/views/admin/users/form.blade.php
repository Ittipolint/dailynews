@extends('layouts.app')

@section('title', $user->exists ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="mb-4">{{ $user->exists ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้' }}</h4>
                <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
                    @csrf
                    @if ($user->exists) @method('PUT') @endif
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ชื่อ *</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">รหัสผู้ใช้ (username) *</label>
                            <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $user->username) }}" required>
                            <div class="form-text">ใช้สำหรับเข้าสู่ระบบ เช่น admin, user1</div>
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">อีเมล *</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">รหัสผ่าน {{ $user->exists ? '(เว้นว่างไว้เพื่อคงเดิม)' : '*' }}</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" {{ $user->exists ? '' : 'required' }}>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">สิทธิ์ *</label>
                            <select name="role" class="form-select" id="roleSelect" required>
                                @foreach (['admin', 'staff', 'user'] as $role)
                                    <option value="{{ $role }}" @selected(old('role', $user->role ?? 'user') === $role)>{{ ucfirst($role) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <hr>
                    <label class="form-label fw-bold mb-2">สิทธิ์ใช้งานเมนู</label>
                    <div class="row" id="permissionBox">
                        @foreach ($menus as $menu)
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input menu-check" name="permissions[]" value="{{ $menu }}"
                                           id="menu_{{ $menu }}" @checked(in_array($menu, old('permissions', $user->permissions ?? []), true))>
                                    <label class="form-check-label" for="menu_{{ $menu }}">{{ $menu }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-text mb-3">ผู้ใช้สิทธิ์ Admin จะใช้งานทุกเมนูโดยอัตโนมัติ</div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">ยกเลิก</a>
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
        const roleSelect = document.getElementById('roleSelect');
        const box = document.getElementById('permissionBox');

        function syncPermissions() {
            const disabled = roleSelect.value === 'admin';
            box.querySelectorAll('.menu-check').forEach((cb) => {
                cb.disabled = disabled;
            });
        }

        roleSelect.addEventListener('change', syncPermissions);
        syncPermissions();
    })();
</script>
@endpush
