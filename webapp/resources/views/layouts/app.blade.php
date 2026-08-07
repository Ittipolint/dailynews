<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DailyNews') — DailyNews Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .sidebar { min-height: 100vh; background: #1f2937; }
        .sidebar .nav-link { color: #cbd5e1; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #fff; background: #374151; }
        .sidebar .brand { color: #fff; font-weight: 700; }
        main { padding: 24px; }
    </style>
    @stack('styles')
</head>
<body>
<div class="d-flex">
    <nav class="sidebar d-flex flex-column p-3 flex-shrink-0" style="width: 260px;">
        <a class="brand d-flex align-items-center mb-4 text-decoration-none" href="{{ route('admin.dashboard') }}">
            <i class="bi bi-newspaper fs-4 me-2"></i> DailyNews
        </a>
        <ul class="nav nav-pills flex-column mb-auto">
            @if (auth()->user()?->canAccessMenu('dashboard'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </li>
            @endif
            @if (auth()->user()?->canAccessMenu('news'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.news*') ? 'active' : '' }}" href="{{ route('admin.news.index') }}">
                    <i class="bi bi-search me-2"></i>ค้นหาข่าว
                </a>
            </li>
            @endif
            @if (auth()->user()?->canAccessMenu('chat'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('chat.*') ? 'active' : '' }}" href="{{ route('chat.index') }}">
                    <i class="bi bi-chat-dots me-2"></i>Chat AI (Graph RAG)
                </a>
            </li>
            @endif
            @if (auth()->user()?->canAccessMenu('sources'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.sources*') ? 'active' : '' }}" href="{{ route('admin.sources.index') }}">
                    <i class="bi bi-rss me-2"></i>แหล่งข่าว
                </a>
            </li>
            @endif
            @if (auth()->user()?->canAccessMenu('members'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.members*') ? 'active' : '' }}" href="{{ route('admin.members.index') }}">
                    <i class="bi bi-people me-2"></i>สมาชิก
                </a>
            </li>
            @endif
            @if (auth()->user()?->canAccessMenu('categories'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.categories*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
                    <i class="bi bi-tags me-2"></i>หมวดหมู่
                </a>
            </li>
            @endif
            @if (auth()->user()?->canAccessMenu('credentials'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.credentials*') ? 'active' : '' }}" href="{{ route('admin.credentials.index') }}">
                    <i class="bi bi-key me-2"></i>Credentials
                </a>
            </li>
            @endif
            @if (auth()->user()?->canAccessMenu('users'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                    <i class="bi bi-people-fill me-2"></i>จัดการผู้ใช้
                </a>
            </li>
            @endif
        </ul>
        <div class="border-top pt-3 mt-3">
            <small class="text-secondary">{{ auth()->user()?->email }}</small>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-light w-100">
                    <i class="bi bi-box-arrow-right me-1"></i>ออกจากระบบ
                </button>
            </form>
        </div>
    </nav>

    <main class="flex-grow-1">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
