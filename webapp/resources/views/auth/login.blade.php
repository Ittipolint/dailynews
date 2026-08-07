@extends('layouts.app')

@section('title', 'เข้าสู่ระบบ')
@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="text-center mb-4"><i class="bi bi-newspaper me-2"></i>DailyNews</h4>
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">รหัสผู้ใช้ / อีเมล</label>
                        <input type="text" name="login" class="form-control @error('login') is-invalid @enderror" value="{{ old('login', request()->cookie('dailynews_last_login')) }}" required autofocus autocomplete="username">
                        @error('login')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รหัสผ่าน</label>
                        <input type="password" name="password" class="form-control" required autocomplete="current-password">
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="remember" id="remember" class="form-check-input">
                        <label class="form-check-label" for="remember">จดจำฉัน</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
