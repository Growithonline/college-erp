@extends('institute.layout')
@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
@php
    $instituteUser   = auth()->user();
    $maxMonthly      = max(1, (float) collect($monthlyData)->max('amount'));
    $totalModeAmt    = max(1, (float) $paymentModes->sum('total'));
    $maxExpense      = max(1, (float) $expenseByAccount->max('total'));
    $maxFinIncome    = max(1, (float) collect($monthlyFinanceData)->max('income'));
    $maxFinExpense   = max(1, (float) collect($monthlyFinanceData)->max('expense'));

    $monthLabels     = collect($monthlyData)->pluck('label')->toJson();
    $monthAmounts    = collect($monthlyData)->pluck('amount')->toJson();
    $courseLabels    = $courseWise->pluck('course_name')->toJson();
    $courseCounts    = $courseWise->pluck('count')->toJson();
    $modeLabels      = $paymentModes->pluck('payment_mode')->map(fn($m) => strtoupper($m))->toJson();
    $modeTotals      = $paymentModes->pluck('total')->toJson();
    $finLabels       = collect($monthlyFinanceData)->pluck('label')->toJson();
    $finIncome       = collect($monthlyFinanceData)->pluck('income')->toJson();
    $finExpense      = collect($monthlyFinanceData)->pluck('expense')->toJson();

    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');

    $netToday = $feeToday - $expenseToday;
@endphp

<style>
/* ── Dashboard global ────────────────────────────── */
.dash-hero { background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 60%,#1d4ed8 100%); border-radius:16px; padding:28px 32px; color:#fff; margin-bottom:24px; position:relative; overflow:hidden; }
.dash-hero::before { content:''; position:absolute; right:-60px; top:-60px; width:280px; height:280px; border-radius:50%; background:rgba(255,255,255,.06); pointer-events:none; }
.dash-hero::after  { content:''; position:absolute; right:80px; bottom:-80px; width:200px; height:200px; border-radius:50%; background:rgba(255,255,255,.04); pointer-events:none; }

/* ── KPI cards ───────────────────────────────────── */
.kpi-card { border:none; border-radius:12px; box-shadow:0 1px 8px rgba(0,0,0,.07); transition:transform .15s,box-shadow .15s; }
.kpi-card:hover { transform:translateY(-2px); box-shadow:0 4px 16px rgba(0,0,0,.1); }
.kpi-card .card-body { padding:12px 14px !important; }
.kpi-icon { width:36px; height:36px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }

/* ── Action tiles ────────────────────────────────── */
.action-tile { border:1.5px solid #e2e8f0; border-radius:12px; padding:12px 8px; text-align:center; text-decoration:none; color:#374151; transition:all .15s ease; display:flex; flex-direction:column; align-items:center; gap:5px; height:100%; }
.action-tile:hover { border-color:var(--tile-color,#2563eb); background:var(--tile-bg,#eff6ff); color:var(--tile-color,#2563eb); transform:translateY(-2px); box-shadow:0 4px 14px rgba(0,0,0,.08); }
.action-tile i { font-size:20px; color:var(--tile-color,#2563eb); }
.action-tile span { font-size:10px; font-weight:600; line-height:1.3; }

/* ── Section headers ─────────────────────────────── */
.section-header { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid #f1f5f9; flex-wrap:wrap; gap:8px; }
.section-title { font-size:13px; font-weight:700; color:#1e293b; margin:0; display:flex; align-items:center; gap:7px; }
.section-title i { font-size:14px; }

/* ── Tab pills ───────────────────────────────────── */
.dash-tabs { border:none; gap:4px; margin-bottom:20px; flex-wrap:wrap; }
.dash-tabs .nav-link { border:none; border-radius:8px; padding:7px 16px; font-size:13px; font-weight:600; color:#64748b; background:transparent; }
.dash-tabs .nav-link.active { background:#2563eb; color:#fff; box-shadow:0 2px 8px rgba(37,99,235,.3); }
.dash-tabs .nav-link:hover:not(.active) { background:#f1f5f9; color:#374151; }

/* ── Trend badge ─────────────────────────────────── */
.trend { display:inline-flex; align-items:center; gap:3px; font-size:10px; font-weight:600; padding:2px 6px; border-radius:20px; white-space:nowrap; }
.trend-up   { background:#dcfce7; color:#16a34a; }
.trend-warn { background:#fef3c7; color:#b45309; }
.trend-red  { background:#fee2e2; color:#dc2626; }

/* ── Progress custom ─────────────────────────────── */
.mode-bar { height:6px; border-radius:3px; background:#e2e8f0; overflow:hidden; margin-top:4px; }
.mode-fill { height:100%; border-radius:3px; transition:width .4s ease; }

/* ── Recent table ────────────────────────────────── */
.avatar-sm { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:#fff; flex-shrink:0; }

/* ══ RESPONSIVE BREAKPOINTS ═══════════════════════ */

/* Mobile (< 576px) */
@media (max-width:575.98px) {
    .dash-hero { padding:18px 16px; border-radius:12px; margin-bottom:16px; }
    .dash-hero h3 { font-size:1.25rem !important; }
    .dash-hero .hero-meta { font-size:11px !important; gap:8px !important; }
    .dash-hero .hero-avatar { display:none !important; }
    .dash-hero .hero-mini-stats > div { padding:8px 10px !important; }
    .dash-hero .hero-mini-stats .mini-val { font-size:14px !important; }
    .kpi-card .card-body { padding:10px 12px !important; }
    .kpi-icon { width:30px; height:30px; font-size:14px; }
    .section-header { padding:10px 12px; }
    .action-tile { padding:10px 6px; border-radius:10px; }
    .action-tile i { font-size:18px; }
    .dash-tabs .nav-link { padding:6px 12px; font-size:12px; }
    canvas { max-height:200px !important; }
}

/* Tablet (576px – 767px) */
@media (min-width:576px) and (max-width:767.98px) {
    .dash-hero { padding:20px 22px; }
    .dash-hero .hero-avatar { display:none !important; }
    canvas { max-height:220px !important; }
}

/* Tablet landscape / small laptop (768px – 991px) */
@media (min-width:768px) and (max-width:991.98px) {
    .dash-hero { padding:24px 28px; }
    canvas { max-height:240px !important; }
}
</style>

{{-- ══════════════ HERO ══════════════ --}}
<div class="dash-hero">
    <div class="d-flex align-items-start justify-content-between">
        <div style="min-width:0;flex:1;">
            <div class="mb-1" style="font-size:11px;opacity:.75;text-transform:uppercase;letter-spacing:.8px;">{{ $greeting }}</div>
            <h3 class="fw-bold mb-1" style="font-size:1.5rem;word-break:break-word;">{{ $instituteUser->name }}</h3>
            <div class="hero-meta d-flex align-items-center gap-3 flex-wrap" style="font-size:12px;opacity:.85;">
                <span><i class="bi bi-calendar3 me-1"></i>{{ now()->format('d M Y, D') }}</span>
                @if($activeSession)
                    <span class="badge" style="background:rgba(255,255,255,.2);font-size:11px;">
                        <i class="bi bi-check-circle me-1"></i>{{ $activeSession->name }}
                    </span>
                @else
                    <span class="badge" style="background:rgba(220,38,38,.35);font-size:11px;">
                        <i class="bi bi-exclamation-triangle me-1"></i>No Active Session
                    </span>
                @endif
                <span class="d-none d-sm-inline"><i class="bi bi-building me-1"></i>{{ $instituteUser->institute?->name ?? 'Institute' }}</span>
            </div>
        </div>
        <div class="hero-avatar text-end ms-3" style="z-index:1;position:relative;flex-shrink:0;">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                 style="width:48px;height:48px;font-size:18px;background:rgba(255,255,255,.2);border:2px solid rgba(255,255,255,.3);">
                {{ strtoupper(substr($instituteUser->name, 0, 1)) }}
            </div>
            <div style="font-size:10px;opacity:.7;margin-top:3px;">Admin</div>
        </div>
    </div>

    {{-- Mini stats inside hero --}}
    <div class="row hero-mini-stats g-2 mt-3">
        <div class="col-6 col-sm-3">
            <div style="background:rgba(255,255,255,.12);border-radius:10px;padding:10px 12px;">
                <div style="font-size:10px;opacity:.7;">Today's Collection</div>
                <div class="mini-val" style="font-size:16px;font-weight:700;">₹{{ number_format($feeToday, 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div style="background:rgba(255,255,255,.12);border-radius:10px;padding:10px 12px;">
                <div style="font-size:10px;opacity:.7;">This Month</div>
                <div class="mini-val" style="font-size:16px;font-weight:700;">₹{{ number_format($feeThisMonth, 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div style="background:rgba(255,255,255,.12);border-radius:10px;padding:10px 12px;">
                <div style="font-size:10px;opacity:.7;">Total Students</div>
                <div class="mini-val" style="font-size:16px;font-weight:700;">{{ number_format($totalStudents) }}</div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div style="background:rgba(255,255,255,.12);border-radius:10px;padding:10px 12px;">
                <div style="font-size:10px;opacity:.7;">Pending Due</div>
                <div class="mini-val" style="font-size:16px;font-weight:700;color:#fca5a5;">₹{{ number_format($feeDue, 0) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════ TABS ══════════════ --}}
<ul class="nav dash-tabs" id="dashboardTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview-pane" type="button">
            <i class="bi bi-grid-1x2 me-2"></i>Overview
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#finance-pane" type="button">
            <i class="bi bi-graph-up-arrow me-2"></i>Income / Expense
        </button>
    </li>
</ul>

<div class="tab-content">

{{-- ══════════════ OVERVIEW TAB ══════════════ --}}
<div class="tab-pane fade show active" id="overview-pane" role="tabpanel">

    {{-- ── KPI Row 1 ── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card kpi-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="kpi-icon" style="background:#eff6ff;">
                            <i class="bi bi-people-fill" style="color:#2563eb;"></i>
                        </div>
                        <span class="trend trend-up"><i class="bi bi-arrow-up-short"></i>Active</span>
                    </div>
                    <div class="fw-bold mb-0" style="font-size:20px;color:#1e293b;">{{ number_format($totalStudents) }}</div>
                    <div class="text-muted" style="font-size:11px;">Total Students</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kpi-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="kpi-icon" style="background:#f0fdf4;">
                            <i class="bi bi-person-plus-fill" style="color:#16a34a;"></i>
                        </div>
                        <span class="trend trend-up"><i class="bi bi-check-circle"></i>Session</span>
                    </div>
                    <div class="fw-bold mb-0" style="font-size:20px;color:#1e293b;">{{ number_format($totalAdmissions) }}</div>
                    <div class="text-muted" style="font-size:11px;">Total Admissions</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kpi-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="kpi-icon" style="background:#fffbeb;">
                            <i class="bi bi-hourglass-split" style="color:#d97706;"></i>
                        </div>
                        @if($pendingAdmissions > 0)
                            <span class="trend trend-warn"><i class="bi bi-exclamation"></i>Action needed</span>
                        @endif
                    </div>
                    <div class="fw-bold mb-0" style="font-size:20px;color:{{ $pendingAdmissions > 0 ? '#d97706' : '#1e293b' }};">
                        {{ number_format($pendingAdmissions) }}
                    </div>
                    <div class="text-muted" style="font-size:11px;">Pending Approvals</div>
                    @if($pendingAdmissions > 0)
                        <a href="{{ route('admissions.approvals.index') }}" class="small text-warning text-decoration-none">
                            Review now <i class="bi bi-arrow-right"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kpi-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="kpi-icon" style="background:#fef2f2;">
                            <i class="bi bi-exclamation-circle-fill" style="color:#dc2626;"></i>
                        </div>
                        @if($feeDue > 0)
                            <span class="trend trend-red"><i class="bi bi-people"></i>{{ $studentsWithDue }}</span>
                        @endif
                    </div>
                    <div class="fw-bold mb-0" style="font-size:18px;color:#dc2626;">₹{{ number_format($feeDue, 0) }}</div>
                    <div class="text-muted" style="font-size:11px;">Pending Due</div>
                    <div style="font-size:10px;color:#94a3b8;">{{ $studentsWithDue }} student(s) overdue</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── KPI Row 2 ── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <div class="kpi-icon mb-1" style="background:#f0f9ff;">
                        <i class="bi bi-calendar-check" style="color:#0ea5e9;"></i>
                    </div>
                    <div class="fw-bold" style="font-size:15px;color:#0c4a6e;">₹{{ number_format($feeThisMonth, 0) }}</div>
                    <div class="text-muted" style="font-size:11px;">This Month Collection</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <div class="kpi-icon mb-1" style="background:#eff6ff;">
                        <i class="bi bi-database-fill" style="color:#2563eb;"></i>
                    </div>
                    <div class="fw-bold" style="font-size:15px;color:#1e40af;">₹{{ number_format($feeTotalSession, 0) }}</div>
                    <div class="text-muted" style="font-size:11px;">Session Collection</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <div class="kpi-icon mb-1" style="background:#faf5ff;">
                        <i class="bi bi-calendar3" style="color:#7c3aed;"></i>
                    </div>
                    <div class="fw-bold" style="font-size:15px;color:#6d28d9;">{{ number_format($sessions->count()) }}</div>
                    <div class="text-muted" style="font-size:11px;">Academic Sessions</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <div class="kpi-icon mb-1" style="background:#f0fdf4;">
                        <i class="bi bi-credit-card-2-front" style="color:#16a34a;"></i>
                    </div>
                    <div class="fw-bold" style="font-size:15px;color:#15803d;">{{ number_format($paymentModes->count()) }}</div>
                    <div class="text-muted" style="font-size:11px;">Payment Modes Used</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Charts Row ── --}}
    <div class="row g-3 mb-4">
        {{-- Monthly Collection Bar Chart --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="section-header">
                    <h6 class="section-title">
                        <i class="bi bi-bar-chart-line-fill text-primary"></i>
                        Last 6 Months Collection
                    </h6>
                </div>
                <div class="card-body" style="padding:16px 20px;">
                    <canvas id="monthlyChart" height="180"></canvas>
                </div>
            </div>
        </div>

        {{-- Course Distribution Doughnut --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="section-header">
                    <h6 class="section-title">
                        <i class="bi bi-pie-chart-fill text-success"></i>
                        Course Distribution
                    </h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center" style="padding:16px;">
                    @if($courseWise->isEmpty())
                        <div class="text-muted small text-center py-4">No student data available.</div>
                    @else
                        <canvas id="courseChart" height="220" style="max-height:220px;"></canvas>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Quick Actions + Payment Modes ── --}}
    <div class="row g-3 mb-4">
        {{-- Quick Actions --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="section-header">
                    <h6 class="section-title">
                        <i class="bi bi-lightning-charge-fill text-warning"></i>
                        Quick Actions
                    </h6>
                    @if($pendingAdmissions > 0)
                        <a href="{{ route('admissions.approvals.index') }}"
                           class="badge bg-warning-subtle text-warning border border-warning-subtle text-decoration-none"
                           style="font-size:11px;">
                            <i class="bi bi-exclamation-triangle me-1"></i>{{ $pendingAdmissions }} Pending
                        </a>
                    @endif
                </div>
                <div class="card-body p-3">
                    <div class="row g-2">
                        @php
                        $actions = [
                            ['route' => route('admissions.quick-create'), 'icon' => 'bi-lightning-fill',           'label' => 'Quick Register',     'color' => '#f59e0b', 'bg' => '#fffbeb'],
                            ['route' => route('admissions.create'),       'icon' => 'bi-person-plus-fill',         'label' => 'Full Admission',      'color' => '#2563eb', 'bg' => '#eff6ff'],
                            ['route' => route('fee.create'),              'icon' => 'bi-cash-coin',                'label' => 'Collect Fee',         'color' => '#16a34a', 'bg' => '#f0fdf4'],
                            ['route' => route('students.search'),         'icon' => 'bi-search',                   'label' => 'Global Search',       'color' => '#0ea5e9', 'bg' => '#f0f9ff'],
                            ['route' => route('admissions.index'),        'icon' => 'bi-people',                   'label' => 'All Students',        'color' => '#7c3aed', 'bg' => '#faf5ff'],
                            ['route' => route('admissions.approvals.index'),'icon'=> 'bi-shield-check',            'label' => 'Approvals',          'color' => '#d97706', 'bg' => '#fffbeb'],
                            ['route' => route('fee.index'),               'icon' => 'bi-receipt',                  'label' => 'Fee History',         'color' => '#64748b', 'bg' => '#f8fafc'],
                            ['route' => route('admissions.bulk-correction'),'icon'=> 'bi-file-earmark-spreadsheet','label' => 'Bulk Correction',     'color' => '#0891b2', 'bg' => '#ecfeff'],
                            ['route' => route('finance.expenses.create'), 'icon' => 'bi-receipt-cutoff',           'label' => 'Add Expense',         'color' => '#dc2626', 'bg' => '#fef2f2'],
                            ['route' => route('library.dashboard'),       'icon' => 'bi-journals',                 'label' => 'Library',             'color' => '#0c4a6e', 'bg' => '#f0f9ff'],
                        ];
                        @endphp
                        @foreach($actions as $action)
                        <div class="col-4 col-sm-3 col-md-2">
                            <a href="{{ $action['route'] }}"
                               class="action-tile"
                               style="--tile-color:{{ $action['color'] }};--tile-bg:{{ $action['bg'] }};">
                                <i class="bi {{ $action['icon'] }}" style="color:{{ $action['color'] }};font-size:20px;"></i>
                                <span style="font-size:10px;color:#374151;">{{ $action['label'] }}</span>
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment Mode Summary --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="section-header">
                    <h6 class="section-title">
                        <i class="bi bi-wallet2 text-info"></i>
                        Payment Mode Summary
                    </h6>
                    <span class="text-muted" style="font-size:12px;">Session total</span>
                </div>
                <div class="card-body p-3">
                    @forelse($paymentModes as $mode)
                    @php
                        $pct = round(($mode->total / $totalModeAmt) * 100);
                        $colors = ['cash'=>'#16a34a','upi'=>'#2563eb','online'=>'#7c3aed','cheque'=>'#d97706','neft'=>'#0891b2','dd'=>'#64748b'];
                        $modeKey = strtolower($mode->payment_mode);
                        $col = $colors[$modeKey] ?? '#64748b';
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:8px;height:8px;border-radius:50%;background:{{ $col }};flex-shrink:0;"></div>
                                <span style="font-size:13px;font-weight:600;">{{ strtoupper($mode->payment_mode) }}</span>
                                <span class="text-muted" style="font-size:11px;">{{ $mode->count }} invoices</span>
                            </div>
                            <div class="text-end">
                                <div style="font-size:13px;font-weight:700;">₹{{ number_format($mode->total, 0) }}</div>
                                <div style="font-size:10px;color:#94a3b8;">{{ $pct }}%</div>
                            </div>
                        </div>
                        <div class="mode-bar">
                            <div class="mode-fill" style="width:{{ $pct }}%;background:{{ $col }};"></div>
                        </div>
                    </div>
                    @empty
                    <div class="text-muted small text-center py-4">No payment records available.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ── Notices Widget ── --}}
    @include('institute.notices._widget', [
        'dashboardNotices'    => $dashboardNotices,
        'noticeViewRoute'     => 'notices.index',
        'noticeReaderType'    => 'institute',
        'noticeReaderId'      => auth()->id(),
        'noticeReadUrlPrefix' => '/notices',
    ])

    {{-- ── Recent Admissions ── --}}
    <div class="card border-0 shadow-sm">
        <div class="section-header">
            <h6 class="section-title">
                <i class="bi bi-person-badge text-primary"></i>
                Recent Admissions
            </h6>
            <a href="{{ route('admissions.index') }}" class="btn btn-outline-primary btn-sm" style="font-size:12px;">
                View All <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th class="ps-3 py-2 fw-semibold text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Student</th>
                        <th class="py-2 fw-semibold text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Course</th>
                        <th class="py-2 fw-semibold text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Session</th>
                        <th class="py-2 text-end pe-3 fw-semibold text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentAdmissions as $student)
                    @php
                        $colors2 = ['#2563eb','#16a34a','#7c3aed','#d97706','#0891b2','#dc2626','#0ea5e9','#059669'];
                        $avatarColor = $colors2[$loop->index % count($colors2)];
                    @endphp
                    <tr>
                        <td class="ps-3 py-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-sm" style="background:{{ $avatarColor }};">
                                    {{ strtoupper(substr($student->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $student->name }}</div>
                                    <div class="text-muted" style="font-size:11px;">{{ $student->student_uid }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $student->stream->course->name ?? '—' }}</td>
                        <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $student->session->name ?? '—' }}</span></td>
                        <td class="text-end pe-3 text-muted">{{ $student->created_at?->format('d M Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                            No recent admissions found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ══════════════ INCOME / EXPENSE TAB ══════════════ --}}
<div class="tab-pane fade" id="finance-pane" role="tabpanel">

    @if(!$financeReady)
    <div class="alert border-0 shadow-sm mb-4" style="background:#fffbeb;border-left:4px solid #f59e0b !important;border-radius:12px;">
        <div class="d-flex gap-3 align-items-start">
            <i class="bi bi-exclamation-triangle-fill text-warning fs-5 mt-1"></i>
            <div>
                <div class="fw-semibold mb-1">Finance module is not configured yet.</div>
                <div class="small text-muted">Run the expense migration to enable the income and expense snapshot.</div>
            </div>
        </div>
    </div>
    @endif

    {{-- Finance KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card kpi-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="kpi-icon" style="background:#f0fdf4;"><i class="bi bi-arrow-down-circle-fill" style="color:#16a34a;"></i></div>
                        <span class="trend trend-up">Income</span>
                    </div>
                    <div class="fw-bold" style="font-size:18px;color:#15803d;">₹{{ number_format($feeToday, 0) }}</div>
                    <div class="text-muted" style="font-size:11px;">Today's Income</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kpi-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="kpi-icon" style="background:#fef2f2;"><i class="bi bi-arrow-up-circle-fill" style="color:#dc2626;"></i></div>
                        <span class="trend trend-red">Expense</span>
                    </div>
                    <div class="fw-bold" style="font-size:18px;color:#dc2626;">₹{{ number_format($expenseToday, 0) }}</div>
                    <div class="text-muted" style="font-size:11px;">Today's Expense</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kpi-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="kpi-icon" style="background:#eff6ff;"><i class="bi bi-graph-up-arrow" style="color:#2563eb;"></i></div>
                        <span class="trend {{ $netToday >= 0 ? 'trend-up' : 'trend-red' }}">Net</span>
                    </div>
                    <div class="fw-bold" style="font-size:18px;color:{{ $netToday >= 0 ? '#2563eb' : '#dc2626' }};">
                        ₹{{ number_format(abs($netToday), 0) }}
                    </div>
                    <div class="text-muted" style="font-size:11px;">Today Net {{ $netToday >= 0 ? 'Profit' : 'Loss' }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kpi-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="kpi-icon" style="background:#fffbeb;"><i class="bi bi-clock-history" style="color:#d97706;"></i></div>
                        @if($pendingExpensePostings > 0)
                            <span class="trend trend-warn">Pending</span>
                        @endif
                    </div>
                    <div class="fw-bold" style="font-size:18px;color:#d97706;">{{ number_format($pendingExpensePostings) }}</div>
                    <div class="text-muted" style="font-size:11px;">Unposted Expenses</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Income vs Expense Chart --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="section-header">
                    <h6 class="section-title">
                        <i class="bi bi-bar-chart-fill text-success"></i>
                        Income vs Expense (Last 6 Months)
                    </h6>
                </div>
                <div class="card-body" style="padding:16px 20px;">
                    @if($monthlyFinanceData->isEmpty())
                        <div class="text-muted small text-center py-5">Finance trend data not available yet.</div>
                    @else
                        <canvas id="financeChart" height="200"></canvas>
                    @endif
                </div>
            </div>
        </div>

        {{-- Finance Snapshot --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="section-header">
                    <h6 class="section-title"><i class="bi bi-clipboard-data text-primary"></i>Finance Snapshot</h6>
                    <a href="{{ route('finance.expenses.create') }}" class="btn btn-danger btn-sm" style="font-size:12px;">
                        <i class="bi bi-plus me-1"></i>Add Expense
                    </a>
                </div>
                <div class="card-body p-0">
                    @php
                    $rows = [
                        ['This Month Income',       '₹'.number_format($feeThisMonth,0),     'text-success', 'bi-arrow-down-circle'],
                        ['This Month Expense',      '₹'.number_format($expenseThisMonth,0),  'text-danger',  'bi-arrow-up-circle'],
                        ['This Month Net',          '₹'.number_format($feeThisMonth-$expenseThisMonth,0), ($feeThisMonth>=$expenseThisMonth?'text-primary':'text-danger'), 'bi-graph-up'],
                        ['Session Income',          '₹'.number_format($feeTotalSession,0),   'text-success', 'bi-database'],
                        ['Session Expense',         '₹'.number_format($expenseTotalSession,0),'text-danger', 'bi-database-dash'],
                        ['Session Net',             '₹'.number_format($feeTotalSession-$expenseTotalSession,0), ($feeTotalSession>=$expenseTotalSession?'text-primary':'text-danger'), 'bi-calculator'],
                    ];
                    @endphp
                    @foreach($rows as $row)
                    <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
                        <div class="d-flex align-items-center gap-2 text-muted" style="font-size:13px;">
                            <i class="bi {{ $row[3] }}"></i>{{ $row[0] }}
                        </div>
                        <span class="fw-semibold {{ $row[2] }}" style="font-size:14px;">{{ $row[1] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Expense Head --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="section-header">
                    <h6 class="section-title"><i class="bi bi-pie-chart text-danger"></i>Expense by Head</h6>
                </div>
                <div class="card-body p-3">
                    @forelse($expenseByAccount as $i => $row)
                    @php
                        $epct = round(($row->total / $maxExpense) * 100);
                        $ecols = ['#dc2626','#d97706','#7c3aed','#2563eb','#0891b2','#16a34a'];
                        $ec = $ecols[$i % count($ecols)];
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span style="font-size:13px;font-weight:600;">{{ $row->expenseAccount?->name ?? 'Other' }}</span>
                            <div class="text-end">
                                <div style="font-size:13px;font-weight:700;color:{{ $ec }};">₹{{ number_format($row->total, 0) }}</div>
                                <div style="font-size:10px;color:#94a3b8;">{{ $row->count }} entries</div>
                            </div>
                        </div>
                        <div class="mode-bar">
                            <div class="mode-fill" style="width:{{ $epct }}%;background:{{ $ec }};"></div>
                        </div>
                    </div>
                    @empty
                    <div class="text-muted small text-center py-4">No expense data available.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent Expenses --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="section-header">
                    <h6 class="section-title"><i class="bi bi-receipt-cutoff text-danger"></i>Recent Expenses</h6>
                    <a href="{{ route('finance.expenses.index') }}" class="btn btn-outline-secondary btn-sm" style="font-size:12px;">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                        <thead style="background:#f8fafc;">
                            <tr>
                                <th class="ps-3 py-2 text-muted fw-semibold" style="font-size:11px;">Date</th>
                                <th class="py-2 text-muted fw-semibold" style="font-size:11px;">Head</th>
                                <th class="py-2 text-muted fw-semibold" style="font-size:11px;">Status</th>
                                <th class="py-2 text-end pe-3 text-muted fw-semibold" style="font-size:11px;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentExpenses as $expense)
                            <tr>
                                <td class="ps-3 py-2 text-muted">{{ $expense->expense_date?->format('d M Y') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $expense->expenseAccount?->name ?? '—' }}</div>
                                    <div class="text-muted" style="font-size:11px;">{{ $expense->vendor_name ?: strtoupper($expense->payment_mode) }}</div>
                                </td>
                                <td>
                                    @if($expense->journal_entry_id)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Posted</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3 fw-semibold text-danger">₹{{ number_format($expense->amount, 0) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                                    No expenses recorded yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif";
Chart.defaults.font.size   = 12;
Chart.defaults.color       = '#64748b';

// ── Monthly Collection Bar Chart ──────────────────────────
(function() {
    const ctx = document.getElementById('monthlyChart');
    if (!ctx) return;
    const labels  = {!! $monthLabels !!};
    const amounts = {!! $monthAmounts !!};
    const max = Math.max(...amounts);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Collection (₹)',
                data: amounts,
                backgroundColor: amounts.map((v, i) =>
                    i === amounts.length - 1 ? '#2563eb' : 'rgba(37,99,235,.18)'
                ),
                borderColor: amounts.map((v, i) =>
                    i === amounts.length - 1 ? '#2563eb' : 'rgba(37,99,235,.5)'
                ),
                borderWidth: 1.5,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => '₹ ' + Number(ctx.raw).toLocaleString('en-IN')
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,.05)' },
                    ticks: {
                        callback: v => '₹' + (v >= 1000 ? (v/1000).toFixed(0)+'k' : v)
                    }
                },
                x: { grid: { display: false } }
            }
        }
    });
})();

// ── Course Doughnut Chart ─────────────────────────────────
(function() {
    const ctx = document.getElementById('courseChart');
    if (!ctx) return;
    const labels = {!! $courseLabels !!};
    const counts = {!! $courseCounts !!};
    const palette = ['#2563eb','#16a34a','#7c3aed','#d97706','#0891b2','#dc2626'];

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: counts,
                backgroundColor: palette.slice(0, labels.length),
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 14, boxWidth: 12, font: { size: 11 } }
                },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.label}: ${ctx.raw} students`
                    }
                }
            }
        }
    });
})();

// ── Finance Grouped Bar Chart ─────────────────────────────
(function() {
    const ctx = document.getElementById('financeChart');
    if (!ctx) return;
    const labels  = {!! $finLabels !!};
    const income  = {!! $finIncome !!};
    const expense = {!! $finExpense !!};

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Income',
                    data: income,
                    backgroundColor: 'rgba(22,163,74,.75)',
                    borderColor: '#16a34a',
                    borderWidth: 1.5,
                    borderRadius: 5,
                    borderSkipped: false,
                },
                {
                    label: 'Expense',
                    data: expense,
                    backgroundColor: 'rgba(220,38,38,.65)',
                    borderColor: '#dc2626',
                    borderWidth: 1.5,
                    borderRadius: 5,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, padding: 16 } },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.dataset.label}: ₹${Number(ctx.raw).toLocaleString('en-IN')}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,.05)' },
                    ticks: {
                        callback: v => '₹' + (v >= 1000 ? (v/1000).toFixed(0)+'k' : v)
                    }
                },
                x: { grid: { display: false } }
            }
        }
    });
})();
</script>
@endpush
@endsection
