@extends('layouts.app')

@section('title', 'ช่องทางของสมาชิก')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">ช่องทางรับข่าว — {{ $member->name }}</h4>
    <a href="{{ route('admin.members.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>กลับ</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.members.channels.store', $member) }}">
            @csrf
            <div class="row g-2">
                <div class="col-md-3">
                    <select name="channel_type" class="form-select" required>
                        @foreach (\App\Enums\ChannelType::cases() as $channel)
                            <option value="{{ $channel->value }}">{{ $channel->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-7">
                    <input type="text" name="credentials[recipient]" class="form-control" placeholder="Recipient / Email / LINE User ID">
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
                <tr><th>ช่องทาง</th><th>สถานะ</th><th class="text-end">จัดการ</th></tr>
            </thead>
            <tbody>
                @forelse ($channels as $channel)
                    <tr>
                        <td><strong>{{ \App\Enums\ChannelType::from($channel->channel_type)->label() }}</strong></td>
                        <td><span class="badge {{ $channel->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $channel->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-end">
                            <form action="{{ route('admin.members.channels.destroy', $channel) }}" method="POST" class="d-inline" onsubmit="return confirm('ลบช่องทางนี้?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-secondary py-4">ยังไม่มีช่องทาง</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
