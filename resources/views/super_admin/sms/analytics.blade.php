@extends('super_admin.layout')
@section('title', 'SMS Analytics')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('super_admin.sms.index') }}" class="text-decoration-none">SMS</a></li>
    <li class="breadcrumb-item active">Analytics</li>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('super_admin.sms.index') }}" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Back to SMS
        </a>
        <h4 class="mb-0 fw-bold mt-1">SMS Analytics</h4>
        <small class="text-muted">Platform-wide SMS usage trends &amp; institute performance</small>
    </div>
</div>

{{-- All-Time Totals --}}
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-3 fw-bold text-primary">{{ number_format($totals->grand_total ?? 0) }}</div>
            <div class="small text-muted">Total SMS</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-3 fw-bold text-success">{{ number_format($totals->total_sent ?? 0) }}</div>
            <div class="small text-muted">Sent</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-3 fw-bold text-danger">{{ number_format($totals->total_failed ?? 0) }}</div>
            <div class="small text-muted">Failed</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-3 fw-bold text-info">{{ number_format($totals->total_otp ?? 0) }}</div>
            <div class="small text-muted">OTP (Platform Cost)</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-3 fw-bold text-warning">{{ number_format($totals->total_notice ?? 0) }}</div>
            <div class="small text-muted">Notices</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-3 fw-bold" style="color:#7c3aed;">{{ number_format($totals->total_due_reminder ?? 0) }}</div>
            <div class="small text-muted">Due Reminders</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- Monthly Trend Chart --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2">
                <span class="fw-semibold small"><i class="bi bi-graph-up me-2 text-primary"></i>Monthly SMS Trend (Last 6 Months)</span>
            </div>
            <div class="card-body p-3">
                <canvas id="trendChart" height="100"></canvas>
            </div>
        </div>
    </div>

    {{-- OTP Stats + Type Donut --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2">
                <span class="fw-semibold small"><i class="bi bi-shield-check me-2 text-info"></i>OTP Performance</span>
            </div>
            <div class="card-body p-3">
                <div class="row g-2 text-center mb-3">
                    <div class="col-6">
                        <div class="fs-4 fw-bold text-info">{{ number_format($otpThisMonth->total ?? 0) }}</div>
                        <div class="small text-muted">This Month</div>
                    </div>
                    <div class="col-6">
                        <div class="fs-4 fw-bold text-secondary">{{ number_format($otpLastMonth) }}</div>
                        <div class="small text-muted">Last Month</div>
                    </div>
                </div>
                @php
                    $otpTotal = $otpThisMonth->total ?? 0;
                    $otpSent  = $otpThisMonth->sent ?? 0;
                    $otpRate  = $otpTotal > 0 ? round(($otpSent / $otpTotal) * 100, 1) : 0;
                @endphp
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>Success Rate (This Month)</span>
                    <strong class="{{ $otpRate >= 90 ? 'text-success' : ($otpRate >= 70 ? 'text-warning' : 'text-danger') }}">{{ $otpRate }}%</strong>
                </div>
                <div class="progress" style="height:6px;">
                    <div class="progress-bar {{ $otpRate >= 90 ? 'bg-success' : ($otpRate >= 70 ? 'bg-warning' : 'bg-danger') }}"
                         style="width:{{ $otpRate }}%"></div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2">
                <span class="fw-semibold small"><i class="bi bi-pie-chart me-2 text-warning"></i>Type Distribution (All Time)</span>
            </div>
            <div class="card-body p-3 d-flex flex-column align-items-center">
                <canvas id="typeDonut" width="160" height="160" style="max-width:160px;"></canvas>
                <div class="mt-2 small text-center">
                    <span class="me-2"><span class="badge" style="background:#6366f1;">&nbsp;</span> OTP</span>
                    <span class="me-2"><span class="badge" style="background:#f59e0b;">&nbsp;</span> Notice</span>
                    <span><span class="badge" style="background:#7c3aed;">&nbsp;</span> Due Reminder</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Top Institutes Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-2">
        <span class="fw-semibold small"><i class="bi bi-trophy me-2 text-warning"></i>Top 10 Institutes by SMS Sent</span>
    </div>
    <div class="card-body p-0">
        @if($topInstitutes->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>No institute SMS data yet.
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Institute</th>
                        <th class="text-end">Total</th>
                        <th class="text-end text-success">Sent</th>
                        <th class="text-end text-danger">Failed</th>
                        <th class="text-end">Notices</th>
                        <th class="text-end">Reminders</th>
                        <th class="text-center">Success Rate</th>
                        <th>Last SMS</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topInstitutes as $i => $row)
                    @php
                        $rate = $row['total'] > 0 ? round(($row['sent_count'] / $row['total']) * 100, 0) : 0;
                    @endphp
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $row['name'] }}</td>
                        <td class="text-end fw-bold">{{ number_format($row['total']) }}</td>
                        <td class="text-end text-success">{{ number_format($row['sent_count']) }}</td>
                        <td class="text-end text-danger">{{ $row['failed_count'] > 0 ? number_format($row['failed_count']) : '—' }}</td>
                        <td class="text-end">{{ number_format($row['notice_count']) }}</td>
                        <td class="text-end">{{ number_format($row['reminder_count']) }}</td>
                        <td class="text-center">
                            <div class="d-flex align-items-center gap-1 justify-content-center">
                                <div class="progress flex-grow-1" style="height:5px;min-width:50px;">
                                    <div class="progress-bar {{ $rate >= 90 ? 'bg-success' : ($rate >= 70 ? 'bg-warning' : 'bg-danger') }}"
                                         style="width:{{ $rate }}%"></div>
                                </div>
                                <small class="text-muted">{{ $rate }}%</small>
                            </div>
                        </td>
                        <td class="text-muted text-nowrap">
                            {{ $row['last_at'] ? \Carbon\Carbon::parse($row['last_at'])->diffForHumans() : '—' }}
                        </td>
                        <td>
                            <a href="{{ route('super_admin.sms.institute-logs', $row['id']) }}"
                               class="btn btn-xs btn-outline-secondary" title="View Logs">
                                <i class="bi bi-list-ul"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

<style>
.btn-xs { padding: 2px 6px; font-size: 0.7rem; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const monthlyData = @json($monthlyData->values());

new Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
        labels: monthlyData.map(d => d.label),
        datasets: [
            {
                label: 'OTP',
                data: monthlyData.map(d => d.otp),
                backgroundColor: '#6366f1',
                borderRadius: 3,
            },
            {
                label: 'Notice',
                data: monthlyData.map(d => d.notice),
                backgroundColor: '#f59e0b',
                borderRadius: 3,
            },
            {
                label: 'Due Reminder',
                data: monthlyData.map(d => d.due_reminder),
                backgroundColor: '#7c3aed',
                borderRadius: 3,
            },
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 } } },
            tooltip: { mode: 'index', intersect: false },
        },
        scales: {
            x: { stacked: true, grid: { display: false } },
            y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
        },
    }
});

new Chart(document.getElementById('typeDonut'), {
    type: 'doughnut',
    data: {
        labels: ['OTP', 'Notice', 'Due Reminder'],
        datasets: [{
            data: [
                {{ $totals->total_otp ?? 0 }},
                {{ $totals->total_notice ?? 0 }},
                {{ $totals->total_due_reminder ?? 0 }}
            ],
            backgroundColor: ['#6366f1', '#f59e0b', '#7c3aed'],
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed.toLocaleString()}` } }
        },
        cutout: '65%',
    }
});
</script>

@endsection
