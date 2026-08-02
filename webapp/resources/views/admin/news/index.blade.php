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

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>หัวข้อข่าว</th><th>แหล่งข่าว</th><th>หมวดหมู่</th><th>ภาษา</th><th>เผยแพร่</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($news as $item)
                    <tr>
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
                    <tr><td colspan="6" class="text-center text-secondary py-4">ไม่พบข่าว</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $news->withQueryString()->links() }}</div>
@endsection
