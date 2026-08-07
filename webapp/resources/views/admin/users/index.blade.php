@extends('layouts.app')

@section('title', 'จัดการผู้ใช้')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">จัดการผู้ใช้</h4>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>เพิ่มผู้ใช้</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>ID</th><th>ชื่อ</th><th>รหัสผู้ใช้ / อีเมล</th><th>สิทธิ์</th><th>เมนูที่ใช้งาน</th><th class="text-end">จัดการ</th></tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td><strong>{{ $user->name }}</strong></td>
                        <td>
                            <code>{{ $user->username ?: $user->email }}</code>
                            @if ($user->username)
                                <div class="small text-secondary">{{ $user->email }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $user->role === 'admin' ? 'bg-danger' : ($user->role === 'staff' ? 'bg-warning' : 'bg-info') }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td>
                            @if ($user->isAdmin())
                                <span class="badge bg-success">ทั้งหมด</span>
                            @else
                                @foreach (($user->permissions ?? []) as $perm)
                                    <span class="badge bg-secondary">{{ $perm }}</span>
                                @endforeach
                                @if (empty($user->permissions))
                                    <span class="text-secondary">-</span>
                                @endif
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            @if ($user->id !== auth()->id())
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('ลบผู้ใช้นี้?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-secondary py-4">ยังไม่มีผู้ใช้</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $users->links() }}</div>
@endsection
