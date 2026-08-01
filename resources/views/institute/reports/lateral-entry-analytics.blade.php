@php
    $isStaff = auth()->guard('staff')->check();
    $layout = $isStaff ? 'staff.layout' : 'institute.layout';
    $analyticsRoute = $isStaff ? 'staff.reports.lateral-entry-analytics' : 'reports.lateral-entry-analytics';
@endphp
@extends($layout)
@section('title', 'Lateral Entry Analytics')
@section('breadcrumb', 'Reports / Lateral Entry Analytics')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Lateral Entry Analytics</h4>
        <small class="text-muted">Admissions, fee collection and seat utilization for Lateral Entry students</small>
    </div>
    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success btn-sm">
        <i class="bi bi-filetype-csv me-1"></i> Export CSV
    </a>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route($analyticsRoute) }}" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-auto" style="min-width:180px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Session</label>
                    <select name="session_id" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                        @foreach($sessions as $sess)
                            <option value="{{ $sess->id }}" {{ $sessionId==$sess->id ? 'selected':'' }}>{{ $sess->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- KPI Cards --}}
@php
    $kpis = [
        ['label' => 'Total Lateral Admissions', 'value' => $totalLateral,   'color' => '#0d6efd', 'icon' => 'bi-arrow-repeat', 'money' => false],
        ['label' => 'Active',                   'value' => $activeCount,   'color' => '#198754', 'icon' => 'bi-person-check-fill', 'money' => false],
        ['label' => 'Passed Out',                'value' => $passedOutCount,'color' => '#6f42c1', 'icon' => 'bi-mortarboard-fill', 'money' => false],
        ['label' => 'Backlog / Failed / Dropped','value' => $terminalCount, 'color' => '#dc3545', 'icon' => 'bi-exclamation-triangle-fill', 'money' => false],
        ['label' => 'Fee Collected',             'value' => $feeCollected,  'color' => '#0dcaf0', 'icon' => 'bi-cash-coin', 'money' => true],
        ['label' => 'Fee Pending',               'value' => $feePending,    'color' => '#ffc107', 'icon' => 'bi-hourglass-split', 'money' => true],
    ];
@endphp
<div class="row g-3 mb-3">
    @foreach($kpis as $kpi)
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid {{ $kpi['color'] }}!important; border-radius:10px;">
            <div class="card-body py-3 px-3">
                <div class="text-muted mb-1" style="font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.5px;">{{ $kpi['label'] }}</div>
                <div class="fw-bold" style="font-size:22px; color:{{ $kpi['color'] }}; line-height:1;">
                    {{ $kpi['money'] ? '₹'.number_format($kpi['value']) : number_format($kpi['value']) }}
                </div>
                <i class="bi {{ $kpi['icon'] }} mt-2 d-block" style="font-size:16px; color:{{ $kpi['color'] }};"></i>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Status Chart --}}
<div class="card border-0 shadow-sm mb-3" style="border-radius:10px;">
    <div class="card-header py-2 px-3" style="background:#1e3a5f; color:#fff;">
        <span class="fw-semibold" style="font-size:13px;"><i class="bi bi-bar-chart-steps me-1"></i> Status Breakdown</span>
    </div>
    <div class="card-body">
        <canvas id="statusChart" height="80"></canvas>
    </div>
</div>

<div class="row g-3 mb-3">
    {{-- Course-wise --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:10px; overflow:hidden;">
            <div class="card-header py-2 px-3" style="background:#1e3a5f; color:#fff;">
                <span class="fw-semibold" style="font-size:13px;"><i class="bi bi-mortarboard-fill me-1"></i> Course-wise Breakdown</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0" style="font-size:12px;">
                    <thead style="background:#f0f4f8;">
                        <tr>
                            <th class="ps-3 py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase;">Course</th>
                            <th class="text-end py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase;">Total</th>
                            <th class="text-end py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase;">Active</th>
                            <th class="text-end pe-3 py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase;">Passed Out</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($courseStats as $row)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $row->course_name }}</td>
                            <td class="text-end">{{ $row->total }}</td>
                            <td class="text-end text-success">{{ $row->active_count }}</td>
                            <td class="text-end pe-3">{{ $row->passed_out_count }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No Lateral Entry admissions in this session.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Starting semester-wise --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:10px; overflow:hidden;">
            <div class="card-header py-2 px-3" style="background:#1e3a5f; color:#fff;">
                <span class="fw-semibold" style="font-size:13px;"><i class="bi bi-list-ol me-1"></i> Starting Semester-wise</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0" style="font-size:12px;">
                    <thead style="background:#f0f4f8;">
                        <tr>
                            <th class="ps-3 py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase;">Semester Joined</th>
                            <th class="text-end pe-3 py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase;">Students</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($semesterStats as $row)
                        <tr>
                            <td class="ps-3 fw-semibold">Sem {{ $row->semester_at_time }}</td>
                            <td class="text-end pe-3 fw-bold text-primary">{{ $row->total }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="text-center text-muted py-3">No data available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Seat quota utilization --}}
<div class="card border-0 shadow-sm mb-3" style="border-radius:10px; overflow:hidden;">
    <div class="card-header py-2 px-3" style="background:#1e3a5f; color:#fff;">
        <span class="fw-semibold" style="font-size:13px;"><i class="bi bi-people-fill me-1"></i> Lateral Seat Quota Utilization</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0" style="font-size:12px;">
            <thead style="background:#f0f4f8;">
                <tr>
                    <th class="ps-3 py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase;">Course</th>
                    <th class="py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase;">Stream</th>
                    <th class="text-end py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase;">Limit</th>
                    <th class="text-end py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase;">Filled</th>
                    <th class="text-end pe-3 py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase;">Remaining</th>
                </tr>
            </thead>
            <tbody>
                @forelse($seatStats as $row)
                <tr>
                    <td class="ps-3 fw-semibold">{{ $row['course'] }}</td>
                    <td>{{ $row['stream'] }}</td>
                    <td class="text-end">{{ $row['limit'] }}</td>
                    <td class="text-end">{{ $row['filled'] }}</td>
                    <td class="text-end pe-3 {{ $row['remaining'] <= 0 ? 'text-danger fw-bold' : 'text-success' }}">{{ $row['remaining'] }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-3">No separate Lateral Entry seat quota is configured for this session — Lateral admissions share the same seat pool as regular admissions.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    new Chart(document.getElementById('statusChart'), {
        type: 'bar',
        data: {
            labels: ['Active', 'Passed Out', 'Backlog / Failed / Dropped'],
            datasets: [{
                data: [{{ $activeCount }}, {{ $passedOutCount }}, {{ $terminalCount }}],
                backgroundColor: ['#198754', '#6f42c1', '#dc3545'],
                borderRadius: 6,
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        }
    });
</script>
@endpush

@endsection
