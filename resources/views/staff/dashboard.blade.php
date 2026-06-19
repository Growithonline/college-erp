@extends('staff.layout')
@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')

@php
    $canViewAdmissions      = $staff->canViewAdmissions();
    $canManageAdmissions    = $staff->canManageAdmissions();
    $canCollectFee          = $staff->canCollectFee();
    $canViewFeeHistory      = $staff->canViewFeeHistory();
    $canViewAdmissionReports = $staff->canViewAdmissionReports();
    $canViewFeeReports      = $staff->canViewFeeReports();
    $canManagePracticalTokens = $staff->canManagePracticalTokens();
    $canViewStatements      = $staff->canViewStatements();
    $canViewLibrary         = $staff->canViewLibrary();
    $canManageStaff         = $staff->canManageStaff();

    $hour     = now()->hour;
    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
@endphp

<style>
/* Hero */
.staff-hero { background:linear-gradient(135deg,#0f4c81 0%,#1d6fa4 55%,#1D9E75 100%); border-radius:16px; padding:26px 28px; color:#fff; margin-bottom:24px; position:relative; overflow:hidden; }
.staff-hero::before { content:''; position:absolute; right:-50px; top:-50px; width:240px; height:240px; border-radius:50%; background:rgba(255,255,255,.06); pointer-events:none; }
.staff-hero::after  { content:''; position:absolute; right:60px; bottom:-70px; width:180px; height:180px; border-radius:50%; background:rgba(255,255,255,.04); pointer-events:none; }

/* KPI */
.kpi-card { border:none; border-radius:12px; box-shadow:0 1px 8px rgba(0,0,0,.07); transition:transform .15s,box-shadow .15s; }
.kpi-card:hover { transform:translateY(-2px); box-shadow:0 4px 16px rgba(0,0,0,.1); }
.kpi-card .card-body { padding:14px 16px !important; }
.kpi-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }

/* Quick Action Cards */
.qa-card { border:1.5px solid #e2e8f0; border-radius:12px; text-decoration:none; color:#1e293b; display:flex; align-items:center; gap:12px; padding:14px 16px; transition:all .18s ease; background:#fff; }
.qa-card:hover { border-color:var(--qa-color,#2563eb); background:var(--qa-bg,#eff6ff); color:var(--qa-color,#2563eb); transform:translateY(-2px); box-shadow:0 4px 16px rgba(0,0,0,.09); }
.qa-card:hover .qa-icon { background:var(--qa-color,#2563eb) !important; color:#fff !important; }
.qa-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; flex-shrink:0; transition:all .18s ease; }
.qa-label { font-size:13px; font-weight:600; line-height:1.3; }
.qa-sub { font-size:11px; color:#94a3b8; line-height:1.2; margin-top:2px; }

/* Section header */
.sec-head { display:flex; align-items:center; gap:8px; margin-bottom:14px; }
.sec-head-title { font-size:14px; font-weight:700; color:#1e293b; }
.sec-head hr { flex:1; border-color:#e2e8f0; margin:0; }

@media(max-width:575px) {
    .staff-hero { padding:18px 16px; border-radius:12px; margin-bottom:16px; }
    .qa-card { padding:12px 13px; gap:10px; }
    .qa-label { font-size:12px; }
}
</style>

{{-- ═══ HERO ═══ --}}
<div class="staff-hero mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
             style="width:48px;height:48px;font-size:20px;">
            {{ strtoupper(substr($staff->name, 0, 1)) }}
        </div>
        <div>
            <div style="font-size:12px;opacity:.75;letter-spacing:.5px;text-transform:uppercase;">{{ $greeting }}</div>
            <h4 class="mb-0 fw-bold" style="font-size:1.3rem;">{{ $staff->name }}</h4>
            <div style="font-size:12px;opacity:.7;margin-top:2px;">
                <i class="bi bi-shield-check me-1"></i>{{ $staff->role?->name ?? 'Staff' }}
                &nbsp;·&nbsp;
                <i class="bi bi-calendar-check me-1"></i>{{ $activeSession?->name ?? 'No active session' }}
            </div>
        </div>
    </div>
</div>

{{-- ═══ KPI STATS ═══ --}}
<div class="row g-3 mb-4">
    @if($canViewAdmissions)
    <div class="col-6 col-md-3">
        <div class="card kpi-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon" style="background:#eff6ff;">
                        <i class="bi bi-people-fill text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Total Students</div>
                        <div class="fw-bold" style="font-size:1.4rem;line-height:1.2;">{{ number_format($totalStudents) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($canViewFeeHistory)
    <div class="col-6 col-md-3">
        <div class="card kpi-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon" style="background:#f0fdf4;">
                        <i class="bi bi-cash-stack text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Today Collected</div>
                        <div class="fw-bold text-success" style="font-size:1.4rem;line-height:1.2;">₹{{ number_format($todayCollected) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($canViewAdmissions)
    <div class="col-6 col-md-3">
        <div class="card kpi-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon" style="background:#fffbeb;">
                        <i class="bi bi-person-plus-fill text-warning"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Today Admissions</div>
                        <div class="fw-bold" style="font-size:1.4rem;line-height:1.2;">{{ $todayAdmissions }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="col-6 col-md-3">
        <div class="card kpi-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon" style="background:#f0fdf4;">
                        <i class="bi bi-shield-fill-check" style="color:#1D9E75;"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">My Role</div>
                        <div class="fw-bold" style="font-size:1rem;line-height:1.3;color:#1D9E75;">{{ $staff->role?->name ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ QUICK ACTIONS ═══ --}}
@php
    $actions = [];

    if($canManageAdmissions) {
        $actions[] = ['route' => route('staff.admissions.quick-create'), 'label' => 'Quick Register', 'sub' => 'Add student fast', 'icon' => 'bi-lightning-fill', 'color' => '#f59e0b', 'bg' => '#fffbeb'];
        $actions[] = ['route' => route('staff.admissions.create'), 'label' => 'Full Admission', 'sub' => 'Complete form', 'icon' => 'bi-person-plus-fill', 'color' => '#3b82f6', 'bg' => '#eff6ff'];
    }
    if($canCollectFee)
        $actions[] = ['route' => route('staff.fee.create'), 'label' => 'Collect Fee', 'sub' => 'Accept payment', 'icon' => 'bi-cash-coin', 'color' => '#10b981', 'bg' => '#f0fdf4'];
    if($canManagePracticalTokens)
        $actions[] = ['route' => route('staff.fee.practical-tokens.index'), 'label' => 'Practical Tokens', 'sub' => 'Manage tokens', 'icon' => 'bi-ticket-perforated-fill', 'color' => '#f97316', 'bg' => '#fff7ed'];
    if($canViewAdmissions) {
        $actions[] = ['route' => route('staff.admissions.index'), 'label' => 'My Students', 'sub' => 'View all students', 'icon' => 'bi-people-fill', 'color' => '#06b6d4', 'bg' => '#ecfeff'];
        $actions[] = ['route' => route('staff.students.search'), 'label' => 'Global Search', 'sub' => 'Find any student', 'icon' => 'bi-search', 'color' => '#6366f1', 'bg' => '#eef2ff'];
    }
    if($canViewStatements)
        $actions[] = ['route' => route('staff.statement.search-student'), 'label' => 'Statements', 'sub' => 'Fee statements', 'icon' => 'bi-file-earmark-text-fill', 'color' => '#64748b', 'bg' => '#f8fafc'];
    if($canViewLibrary)
        $actions[] = ['route' => route('staff.library.dashboard'), 'label' => 'Library', 'sub' => 'Books & members', 'icon' => 'bi-journal-bookmark-fill', 'color' => '#8b5cf6', 'bg' => '#f5f3ff'];
    if($canViewFeeHistory)
        $actions[] = ['route' => route('staff.fee.index'), 'label' => 'Fee History', 'sub' => 'Payment records', 'icon' => 'bi-receipt-cutoff', 'color' => '#475569', 'bg' => '#f1f5f9'];
    if($canViewFeeReports) {
        $actions[] = ['route' => route('staff.reports.fee-collection'), 'label' => 'Fee Reports', 'sub' => 'Collection summary', 'icon' => 'bi-bar-chart-fill', 'color' => '#d97706', 'bg' => '#fffbeb'];
        $actions[] = ['route' => route('staff.reports.fee-due-list'), 'label' => 'Due List', 'sub' => 'Pending dues', 'icon' => 'bi-exclamation-circle-fill', 'color' => '#ef4444', 'bg' => '#fef2f2'];
    }
    if($canManageStaff)
        $actions[] = ['route' => route('staff.staff-manage.index'), 'label' => 'Staff Manage', 'sub' => 'Team members', 'icon' => 'bi-people-fill', 'color' => '#dc2626', 'bg' => '#fef2f2'];
    if($canViewAdmissionReports)
        $actions[] = ['route' => route('staff.reports.admission'), 'label' => 'Admission Report', 'sub' => 'Enrollment data', 'icon' => 'bi-graph-up-arrow', 'color' => '#0891b2', 'bg' => '#ecfeff'];
@endphp

@if(count($actions))
<div class="sec-head">
    <span class="sec-head-title"><i class="bi bi-grid-1x2-fill text-primary"></i> Quick Actions</span>
    <hr>
</div>
<div class="row g-3 mb-4">
    @foreach($actions as $action)
    <div class="col-6 col-md-4 col-lg-3">
        <a href="{{ $action['route'] }}"
           class="qa-card h-100"
           style="--qa-color:{{ $action['color'] }};--qa-bg:{{ $action['bg'] }};">
            <div class="qa-icon" style="background:{{ $action['bg'] }};color:{{ $action['color'] }};">
                <i class="bi {{ $action['icon'] }}"></i>
            </div>
            <div style="min-width:0;">
                <div class="qa-label">{{ $action['label'] }}</div>
                <div class="qa-sub">{{ $action['sub'] }}</div>
            </div>
            <i class="bi bi-chevron-right ms-auto flex-shrink-0" style="font-size:11px;color:#cbd5e1;"></i>
        </a>
    </div>
    @endforeach
</div>
@endif

{{-- ═══ NOTICES ═══ --}}
@include('institute.notices._widget', [
    'dashboardNotices'    => $dashboardNotices,
    'noticeViewRoute'     => 'staff.notices.index',
    'noticeReaderType'    => 'staff',
    'noticeReaderId'      => auth()->guard('staff')->id(),
    'noticeReadUrlPrefix' => '/staff/notices',
])

{{-- ═══ RECENT FEE COLLECTIONS ═══ --}}
@if($recentCollections->isNotEmpty())
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-clock-history text-success"></i>
            <span class="fw-bold" style="font-size:13px;">Recent Fee Collections</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th class="ps-4 fw-semibold text-muted border-0" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Invoice</th>
                        <th class="fw-semibold text-muted border-0" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Student</th>
                        <th class="fw-semibold text-muted border-0" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Date</th>
                        <th class="fw-semibold text-muted border-0" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Mode</th>
                        <th class="text-end pe-4 fw-semibold text-muted border-0" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentCollections as $inv)
                    <tr>
                        <td class="ps-4">
                            <span class="fw-semibold text-primary">{{ $inv->invoice_no }}</span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                                     style="width:28px;height:28px;font-size:10px;background:#6366f1;">
                                    {{ strtoupper(substr($inv->student?->name ?? '?', 0, 1)) }}
                                </div>
                                <span>{{ $inv->student?->name ?? '—' }}</span>
                            </div>
                        </td>
                        <td class="text-muted">{{ $inv->payment_date?->format('d M Y') }}</td>
                        <td>
                            <span class="badge rounded-pill fw-normal"
                                  style="background:#f1f5f9;color:#475569;font-size:11px;">
                                {{ strtoupper($inv->payment_mode) }}
                            </span>
                        </td>
                        <td class="text-end pe-4 fw-bold text-success">
                            ₹{{ number_format($inv->paid_amount) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@endsection
