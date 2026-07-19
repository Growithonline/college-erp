@php
    $isStaff = auth()->guard('staff')->check();
    $layout = $isStaff ? 'staff.layout' : 'institute.layout';
    $analyticsRoute = $isStaff ? 'staff.reports.admission-analytics' : 'reports.admission-analytics';
@endphp
@extends($layout)
@section('title', 'Admission Analytics')
@section('breadcrumb', 'Reports / Admission Analytics')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Admission Analytics</h4>
        <small class="text-muted">Online admission funnel — enquiry to admission conversion</small>
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
                <div class="col-auto" style="min-width:150px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Session</label>
                    <select name="session_id" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                        @foreach($sessions as $sess)
                            <option value="{{ $sess->id }}" {{ $sessionId==$sess->id ? 'selected':'' }}>{{ $sess->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
                </div>
                <div class="col-auto">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-funnel me-1"></i> Apply
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- KPI Cards --}}
@php
    $kpis = [
        ['label' => 'Enquiries',           'value' => $totalEnquiries,   'color' => '#0d6efd', 'icon' => 'bi-chat-left-text-fill'],
        ['label' => 'Applications',        'value' => $totalApplications,'color' => '#6f42c1', 'icon' => 'bi-file-earmark-text-fill'],
        ['label' => 'Payments Done',       'value' => $totalPayments,    'color' => '#0dcaf0', 'icon' => 'bi-credit-card-fill'],
        ['label' => 'Admissions Approved', 'value' => $totalAdmissions,  'color' => '#198754', 'icon' => 'bi-patch-check-fill'],
        ['label' => 'Waitlisted',          'value' => $totalWaitlisted,  'color' => '#ffc107', 'icon' => 'bi-hourglass-split'],
    ];
@endphp
<div class="row g-3 mb-3">
    @foreach($kpis as $kpi)
    <div class="col-6 col-md-4 col-lg">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid {{ $kpi['color'] }}!important; border-radius:10px;">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted mb-1" style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.5px;">{{ $kpi['label'] }}</div>
                        <div class="fw-bold" style="font-size:26px; color:{{ $kpi['color'] }}; line-height:1;">{{ number_format($kpi['value']) }}</div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:44px;height:44px;background:{{ $kpi['color'] }}1a;">
                        <i class="bi {{ $kpi['icon'] }}" style="font-size:18px; color:{{ $kpi['color'] }};"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Funnel Chart --}}
<div class="card border-0 shadow-sm mb-3" style="border-radius:10px;">
    <div class="card-header py-2 px-3" style="background:#1e3a5f; color:#fff;">
        <span class="fw-semibold" style="font-size:13px;"><i class="bi bi-bar-chart-steps me-1"></i> Conversion Funnel</span>
    </div>
    <div class="card-body">
        <canvas id="funnelChart" height="90"></canvas>
    </div>
</div>

<div class="row g-3 mb-3">
    {{-- Source-wise --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:10px; overflow:hidden;">
            <div class="card-header py-2 px-3" style="background:#1e3a5f; color:#fff;">
                <span class="fw-semibold" style="font-size:13px;"><i class="bi bi-megaphone-fill me-1"></i> Source-wise Conversion</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0" style="font-size:12px;">
                    <thead style="background:#f0f4f8;">
                        <tr>
                            <th class="ps-3 py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase;">Source</th>
                            <th class="text-end py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase;">Enquiries</th>
                            <th class="text-end py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase;">Applications</th>
                            <th class="text-end pe-3 py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase;">Admissions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sourceStats as $row)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $row->source_label }}</td>
                            <td class="text-end">{{ $row->enquiries }}</td>
                            <td class="text-end">{{ $row->applications }}</td>
                            <td class="text-end pe-3 fw-bold text-success">{{ $row->admissions }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No enquiries in this date range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Counselor performance --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:10px; overflow:hidden;">
            <div class="card-header py-2 px-3" style="background:#1e3a5f; color:#fff;">
                <span class="fw-semibold" style="font-size:13px;"><i class="bi bi-person-badge-fill me-1"></i> Counselor Performance</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0" style="font-size:12px;">
                    <thead style="background:#f0f4f8;">
                        <tr>
                            <th class="ps-3 py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase;">Staff</th>
                            <th class="text-end py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase;">Enquiries</th>
                            <th class="text-end py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase;">Applications</th>
                            <th class="text-end pe-3 py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase;">Admissions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($counselorStats as $row)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $row->staff_name }}</td>
                            <td class="text-end">{{ $row->enquiries }}</td>
                            <td class="text-end">{{ $row->applications }}</td>
                            <td class="text-end pe-3 fw-bold text-success">{{ $row->admissions }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No enquiries in this date range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    new Chart(document.getElementById('funnelChart'), {
        type: 'bar',
        data: {
            labels: ['Enquiries', 'Applications', 'Payments Done', 'Admissions Approved'],
            datasets: [{
                data: [{{ $totalEnquiries }}, {{ $totalApplications }}, {{ $totalPayments }}, {{ $totalAdmissions }}],
                backgroundColor: ['#0d6efd', '#6f42c1', '#0dcaf0', '#198754'],
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
