@extends('layouts.app')

@section('title', $category->exists ? 'แก้ไขหมวดหมู่' : 'เพิ่มหมวดหมู่')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="mb-4">{{ $category->exists ? 'แก้ไขหมวดหมู่' : 'เพิ่มหมวดหมู่' }}</h4>
                <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
                    @csrf
                    @if ($category->exists) @method('PUT') @endif
                    <div class="mb-3">
                        <label class="form-label">ชื่อ *</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $category->name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รหัส (code) *</label>
                        <input type="text" name="code" class="form-control" value="{{ old('code', $category->code) }}" required>
                        <div class="form-text">เช่น technology, business, sports</div>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" @checked(old('is_active', $category->is_active ?? true))>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">ยกเลิก</a>
                        <button type="submit" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
