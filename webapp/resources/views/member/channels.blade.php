@extends('layouts.app')

@section('title', 'ช่องทางรับข่าว')
@section('content')
<h4 class="mb-4">ช่องทางรับข่าว</h4>

<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('member.channels.store') }}">
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
                    <input type="text" name="credentials[recipient]" class="form-control" placeholder="Recipient / Email / LINE User ID" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">บันทึก</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr><th>ช่องทาง</th><th>สถานะ</th></tr>
            </thead>
            <tbody>
                @forelse ($channels as $channel)
                    <tr>
                        <td><strong>{{ \App\Enums\ChannelType::from($channel->channel_type)->label() }}</strong></td>
                        <td><span class="badge {{ $channel->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $channel->is_active ? 'Active' : 'Inactive' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-center text-secondary py-4">ยังไม่มีช่องทาง</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
