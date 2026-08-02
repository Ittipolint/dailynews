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
                            <label class="form-label">Cron Expression (เริ่มต้นทุก 1 ชม.)</label>
                            <input type="text" name="cron_expression" class="form-control" value="{{ old('cron_expression', $source->cron_expression ?? '0 * * * *') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">หมวดหมู่เริ่มต้น</label>
                            <input type="text" name="category" class="form-control" value="{{ old('category', $source->category) }}">
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
