@extends('layouts.app')

@section('title', 'จัดการแหล่งข่าว')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">แหล่งข่าว (News Sources)</h4>
    <a href="{{ route('admin.sources.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>เพิ่มแหล่งข่าว</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ชื่อ</th>
                    <th>รูปแบบ</th>
                    <th>ภาษา</th>
                    <th>Feed / URL</th>
                    <th>สถานะ</th>
                    <th>ข่าวล่าสุด</th>
                    <th class="text-end">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sources as $source)
                    <tr>
                        <td>
                            <strong>{{ $source->name }}</strong>
                            <div class="small text-secondary">{{ $source->slug }}</div>
                        </td>
                        <td><span class="badge bg-info">{{ \App\Enums\FetchType::from($source->fetch_type)->label() }}</span></td>
                        <td><span class="badge bg-secondary text-uppercase">{{ $source->locale }}</span></td>
                        <td class="text-truncate" style="max-width: 250px;">
                            <a href="{{ $source->feed_url ?: $source->url }}" target="_blank" class="small">{{ $source->feed_url ?: $source->url }}</a>
                            <div class="small text-secondary">{{ $source->news_count }} ข่าว</div>
                        </td>
                        <td>
                            @if ($source->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                            @if ($source->last_status === 'failed')
                                <div class="small text-danger mt-1" title="{{ $source->last_error }}">ล้มเหลว</div>
                            @endif
                        </td>
                        <td class="small">
                            {{ $source->last_fetched_at?->diffForHumans() ?? '-' }}
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.sources.edit', $source) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('admin.sources.toggle', $source) }}" method="POST" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm {{ $source->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                    <i class="bi {{ $source->is_active ? 'bi-pause' : 'bi-play' }}"></i>
                                </button>
                            </form>
                            <form action="{{ route('admin.sources.destroy', $source) }}" method="POST" class="d-inline" onsubmit="return confirm('ลบแหล่งข่าวนี้?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-secondary py-4">ยังไม่มีแหล่งข่าว</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $sources->links() }}</div>
@endsection
