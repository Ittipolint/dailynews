@extends('layouts.app')

@section('title', $member->exists ? 'แก้ไขสมาชิก' : 'เพิ่มสมาชิก')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="mb-4">{{ $member->exists ? 'แก้ไขสมาชิก' : 'เพิ่มสมาชิก' }}</h4>
                <form method="POST" action="{{ $member->exists ? route('admin.members.update', $member) : route('admin.members.store') }}">
                    @csrf
                    @if ($member->exists) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ชื่อ *</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $member->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ประเภทสมาชิก *</label>
                            <select name="member_type_id" class="form-select" required>
                                @foreach ($types as $type)
                                    <option value="{{ $type->id }}" @selected(old('member_type_id', $member->member_type_id) == $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">อีเมล</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $member->email) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ภาษาที่ต้องการรับข่าว *</label>
                            <select name="preferred_locale" class="form-select" required>
                                @foreach (['th' => 'ไทย', 'en' => 'English', 'zh' => '中文'] as $code => $label)
                                    <option value="{{ $code }}" @selected(old('preferred_locale', $member->preferred_locale) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">LINE User ID (ส่วนตัว)</label>
                            <input type="text" name="line_user_id" class="form-control" value="{{ old('line_user_id', $member->line_user_id) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">LINE OA User ID (ผู้รับ)</label>
                            <input type="text" name="line_oa_user_id" class="form-control" value="{{ old('line_oa_user_id', $member->line_oa_user_id) }}" placeholder="Uxxxxxx...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">LINE OA ชื่อบัญชี</label>
                            <input type="text" name="line_oa_basic_id" class="form-control" value="{{ old('line_oa_basic_id', $member->line_oa_basic_id) }}" placeholder="@dailynews">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">LINE OA Channel ID</label>
                            <input type="text" name="line_oa_channel_id" class="form-control" value="{{ old('line_oa_channel_id', $member->line_oa_channel_id) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">LINE OA Channel Secret</label>
                            <div class="input-group">
                                <input type="password" name="line_oa_channel_secret" class="form-control pw-field" value="{{ $member->exists ? '••••••••••••' : '' }}" placeholder="{{ $member->exists ? 'เว้นว่างไว้เพื่อเก็บค่าเดิม' : '8d81847c...' }}" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary pw-toggle" tabindex="-1" aria-label="แสดง/ซ่อน Channel Secret"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">LINE OA Webhook URL</label>
                            <input type="text" name="line_oa_webhook_url" class="form-control" value="{{ old('line_oa_webhook_url', $member->line_oa_webhook_url) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">สถานะสมาชิก</label>
                            <select name="status" class="form-select">
                                @foreach (\App\Enums\MemberStatus::cases() as $status)
                                    <option value="{{ $status->value }}" @selected(old('status', $member->status) === $status->value)>{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" @checked(old('is_active', $member->is_active ?? true))>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.members.index') }}" class="btn btn-secondary">ยกเลิก</a>
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
