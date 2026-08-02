@extends('layouts.app')

@section('title', 'หน้าสมาชิก')
@section('content')
<h4 class="mb-4">หน้าสมาชิก — {{ $member?->name ?? 'Guest' }}</h4>

<div class="alert alert-light border">
    <p class="mb-1"><strong>อีเมล:</strong> {{ $member?->email ?? '-' }}</p>
    <p class="mb-1"><strong>ประเภท:</strong> {{ $member?->type?->name ?? '-' }}</p>
    <p class="mb-1"><strong>ภาษา:</strong> {{ $member?->preferred_locale ?? '-' }}</p>
    <p class="mb-0"><strong>สถานะ:</strong>
        <span class="badge {{ $member?->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $member?->is_active ? 'Active' : 'Inactive' }}</span>
    </p>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <a href="{{ route('member.channels') }}" class="card text-decoration-none text-dark shadow-sm">
            <div class="card-body">
                <h5><i class="bi bi-broadcast me-2 text-primary"></i>ช่องทางรับข่าว</h5>
                <div class="text-secondary small">ตั้งค่า LINE / Email</div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('member.interests') }}" class="card text-decoration-none text-dark shadow-sm">
            <div class="card-body">
                <h5><i class="bi bi-star me-2 text-warning"></i>หัวข้อที่สนใจ</h5>
                <div class="text-secondary small">เลือกหมวดหมู่ / keyword</div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('member.schedules') }}" class="card text-decoration-none text-dark shadow-sm">
            <div class="card-body">
                <h5><i class="bi bi-clock me-2 text-success"></i>ตารางเวลาส่งข่าว</h5>
                <div class="text-secondary small">กำหนดวัน / เวลารับข่าว</div>
            </div>
        </a>
    </div>
</div>
@endsection
