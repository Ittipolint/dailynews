@extends('layouts.app')

@section('title', 'Dashboard')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Dashboard — ภาพรวมระบบ</h4>
    <div>
        <a href="{{ route('admin.dashboard.export') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-download me-1"></i>Export CSV
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">ข่าวทั้งหมด</div>
            <div class="fs-3 fw-bold">{{ number_format($totalNews) }}</div>
            <div class="small text-success">วันนี้ +{{ $newsToday }} / สัปดาห์ {{ $newsThisWeek }} / เดือน {{ $newsThisMonth }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">แหล่งข่าว</div>
            <div class="fs-3 fw-bold">{{ $activeSources }} <span class="fs-6 text-secondary">active</span></div>
            <div class="small">Inactive {{ $inactiveSources }} | ล้มเหลว <span class="text-danger">{{ $failedSources }}</span></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">สมาชิก</div>
            <div class="fs-3 fw-bold">{{ number_format($totalMembers) }}</div>
            <div class="small">องค์กร {{ $orgMembers }} | บุคคล {{ $individualMembers }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">การส่งข่าว (สำเร็จ)</div>
            <div class="fs-3 fw-bold">{{ number_format($deliveriesToday) }}</div>
            <div class="small text-warning">วันนี้ล้มเหลว {{ $deliveryFailures }} | สัปดาห์ {{ $deliveriesThisWeek }} | เดือน {{ $deliveriesThisMonth }}</div>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card shadow-sm"><div class="card-body">
            <h6 class="mb-3">แนวโน้มรายวัน (รับข่าว vs ส่งข่าว)</h6>
            <canvas id="trendChart" height="120"></canvas>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm mb-3"><div class="card-body">
            <h6 class="mb-3">ข่าวยอดนิยมตามแหล่งข่าว</h6>
            <canvas id="sourceChart" height="150"></canvas>
        </div></div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-md-6">
        <div class="card shadow-sm"><div class="card-body">
            <h6 class="mb-3">ตามหมวดหมู่</h6>
            <canvas id="categoryChart" height="150"></canvas>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm"><div class="card-body">
            <h6 class="mb-3">ตามช่องทางส่งข่าว</h6>
            <canvas id="channelChart" height="150"></canvas>
        </div></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
fetch('{{ route('admin.dashboard.stats', ['days' => 14]) }}')
    .then(r => r.json())
    .then(data => {
        const labels = data.fetchedPerDay.map(d => d.date);

        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: 'รับข่าว', data: data.fetchedPerDay.map(d => d.total), borderColor: '#0d6efd', tension: 0.3 },
                    { label: 'ส่งข่าว', data: data.sentPerDay.map(d => d.total), borderColor: '#198754', tension: 0.3 },
                ],
            },
        });

        new Chart(document.getElementById('sourceChart'), {
            type: 'bar',
            data: {
                labels: data.bySource.map(s => s.name),
                datasets: [{ label: 'จำนวนข่าว', data: data.bySource.map(s => s.count), backgroundColor: '#0d6efd' }],
            },
            options: { indexAxis: 'y', plugins: { legend: { display: false } } },
        });

        new Chart(document.getElementById('categoryChart'), {
            type: 'doughnut',
            data: {
                labels: data.byCategory.map(c => c.category),
                datasets: [{ data: data.byCategory.map(c => c.total) }],
            },
        });

        new Chart(document.getElementById('channelChart'), {
            type: 'bar',
            data: {
                labels: data.byChannel.map(c => c.channel_type),
                datasets: [{ label: 'จำนวนการส่ง', data: data.byChannel.map(c => c.total), backgroundColor: '#198754' }],
            },
        });
    });
</script>
@endpush
