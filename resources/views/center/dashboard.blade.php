@extends('center.layout')
@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')
@section('content')

@php
    $canAdmit        = $center->canManageAdmissions();
    $canQuick        = $canAdmit && $center->canUseQuickAdmissionForm();
    $canFull         = $canAdmit && $center->canUseFullAdmissionForm();
    $canViewStudents = $center->canViewStudents();
    $canCollectFee   = $center->canCollectFee();

    $hour     = now()->hour;
    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');

    $w         = $centerWallet ?? null;
    $wBlocked  = $w ? $w->getBlockStatus() : null;
    $wExpired  = $w && $w->isExpired();
    $wExhausted= $w && (float)$w->remaining_tokens <= 0;
    $wLow      = $w && $wBlocked && !$wBlocked['blocked'] && (float)$w->remaining_tokens < (float)$w->total_tokens * 0.1;
@endphp

<style>
.hero-card { background:linear-gradient(135deg,#0d3b75 0%,#185FA5 55%,#1a7abf 100%); border-radius:16px; padding:26px 28px; color:#fff; margin-bottom:24px; position:relative; overflow:hidden; }
.hero-card::before { content:''; position:absolute; right:-50px; top:-50px; width:220px; height:220px; border-radius:50%; background:rgba(255,255,255,.06); pointer-events:none; }
.hero-card::after  { content:''; position:absolute; right:60px; bottom:-70px; width:170px; height:170px; border-radius:50%; background:rgba(255,255,255,.04); pointer-events:none; }
.kpi-card { border:none; border-radius:12px; box-shadow:0 1px 8px rgba(0,0,0,.07); transition:transform .15s,box-shadow .15s; }
.kpi-card:hover { transform:translateY(-2px); box-shadow:0 4px 16px rgba(0,0,0,.1); }
.kpi-card .card-body { padding:14px 16px !important; }
.kpi-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.qa-card { border:1.5px solid #e2e8f0; border-radius:12px; text-decoration:none; color:#1e293b; display:flex; align-items:center; gap:12px; padding:14px 16px; transition:all .18s ease; background:#fff; }
.qa-card:hover { border-color:var(--qa-color,#2563eb); background:var(--qa-bg,#eff6ff); color:var(--qa-color,#2563eb); transform:translateY(-2px); box-shadow:0 4px 16px rgba(0,0,0,.09); }
.qa-card:hover .qa-icon { background:var(--qa-color,#2563eb) !important; color:#fff !important; }
.qa-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; flex-shrink:0; transition:all .18s ease; }
.qa-label { font-size:13px; font-weight:600; line-height:1.3; }
.qa-sub { font-size:11px; color:#94a3b8; line-height:1.2; margin-top:2px; }
.sec-head { display:flex; align-items:center; gap:8px; margin-bottom:14px; }
.sec-head-title { font-size:14px; font-weight:700; color:#1e293b; white-space:nowrap; }
.sec-head hr { flex:1; border-color:#e2e8f0; margin:0; }
</style>

{{-- Hero --}}
<div class="hero-card mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
             style="width:48px;height:48px;font-size:20px;">
            {{ strtoupper(substr($center->name, 0, 1)) }}
        </div>
        <div>
            <div style="font-size:12px;opacity:.75;letter-spacing:.5px;text-transform:uppercase;">{{ $greeting }}</div>
            <h4 class="mb-0 fw-bold" style="font-size:1.3rem;">{{ $center->name }}</h4>
            <div style="font-size:12px;opacity:.7;margin-top:2px;">
                <i class="bi bi-building me-1"></i>Center Portal
                &nbsp;·&nbsp;
                <i class="bi bi-calendar-check me-1"></i>{{ $activeSession?->name ?? 'No active session' }}
                &nbsp;·&nbsp;
                {{ now()->format('d M Y, D') }}
            </div>
        </div>
    </div>
</div>

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    @if($canViewStudents)
    <div class="col-6 col-md-3">
        <div class="card kpi-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon" style="background:#eff6ff;">
                        <i class="bi bi-people-fill text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">My Students</div>
                        <div class="fw-bold" style="font-size:1.4rem;line-height:1.2;">{{ number_format($totalStudents) }}</div>
                        <div class="text-muted" style="font-size:10px;">This session</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($canCollectFee)
    <div class="col-6 col-md-3">
        <div class="card kpi-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon" style="background:#fffbeb;">
                        <i class="bi bi-cash-stack text-warning"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Fee Collected</div>
                        <div class="fw-bold" style="font-size:1.4rem;line-height:1.2;">₹{{ number_format($totalCollected) }}</div>
                        <div class="text-muted" style="font-size:10px;">This session</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($w && $canCollectFee)
    <div class="col-6 col-md-3">
        <div class="card kpi-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon" style="background:{{ $wExpired || $wExhausted ? '#fef2f2' : ($wLow ? '#fffbeb' : '#f0fdf4') }};">
                        <i class="bi bi-wallet2" style="color:{{ $wExpired || $wExhausted ? '#ef4444' : ($wLow ? '#f59e0b' : '#10b981') }};"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Wallet Balance</div>
                        <div class="fw-bold" style="font-size:1.4rem;line-height:1.2;color:{{ $wExpired || $wExhausted ? '#ef4444' : ($wLow ? '#f59e0b' : '#10b981') }};">
                            ₹{{ number_format((float)$w->remaining_tokens) }}
                        </div>
                        <div style="font-size:10px;color:{{ $wExpired ? '#ef4444' : '#94a3b8' }};">
                            {{ $wExpired ? 'Expired' : 'Expires '.$w->expires_at?->format('d M Y') }}
                        </div>
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
                    <div class="kpi-icon" style="background:#eff6ff;">
                        <i class="bi bi-building-fill" style="color:#185FA5;"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Portal Type</div>
                        <div class="fw-bold" style="font-size:1rem;line-height:1.3;color:#185FA5;">Center</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Wallet blocked alert --}}
@if($w && $wBlocked && $wBlocked['blocked'])
<div class="alert d-flex align-items-center gap-3 border-0 rounded-3 shadow-sm mb-4"
     style="background:{{ $wExpired ? '#fef2f2' : '#fffbeb' }};border-left:4px solid {{ $wExpired ? '#ef4444' : '#f59e0b' }} !important;">
    <i class="bi bi-exclamation-triangle-fill fs-5" style="color:{{ $wExpired ? '#ef4444' : '#f59e0b' }};flex-shrink:0;"></i>
    <div class="flex-grow-1">
        <div class="fw-semibold" style="font-size:13px;">{{ $wBlocked['reason'] }}</div>
    </div>
    <a href="{{ route('center.fee.wallet.status') }}" class="btn btn-sm btn-outline-primary flex-shrink-0" style="font-size:12px;">
        <i class="bi bi-send me-1"></i>Request Extension
    </a>
</div>
@endif

{{-- Quick Actions --}}
@php
    $actions = [];
    if($canQuick)
        $actions[] = ['route'=>route('center.admissions.quick-create'),'label'=>'Quick Register','sub'=>'Add student fast','icon'=>'bi-lightning-fill','color'=>'#f59e0b','bg'=>'#fffbeb'];
    if($canFull)
        $actions[] = ['route'=>route('center.admissions.create'),'label'=>'Full Admission','sub'=>'Complete form','icon'=>'bi-person-plus-fill','color'=>'#3b82f6','bg'=>'#eff6ff'];
    if($canViewStudents) {
        $actions[] = ['route'=>route('center.students.index'),'label'=>'My Students','sub'=>'View all students','icon'=>'bi-people-fill','color'=>'#06b6d4','bg'=>'#ecfeff'];
        $actions[] = ['route'=>route('center.students.search'),'label'=>'Global Search','sub'=>'Find any student','icon'=>'bi-search','color'=>'#6366f1','bg'=>'#eef2ff'];
    }
    if($canCollectFee && (!$w || !$wBlocked || !$wBlocked['blocked']))
        $actions[] = ['route'=>route('center.fee.create'),'label'=>'Collect Fee','sub'=>'Accept payment','icon'=>'bi-cash-coin','color'=>'#10b981','bg'=>'#f0fdf4'];
    if($canCollectFee)
        $actions[] = ['route'=>route('center.fee.index'),'label'=>'My Collections','sub'=>'Fee history','icon'=>'bi-receipt-cutoff','color'=>'#475569','bg'=>'#f1f5f9'];
    if($w)
        $actions[] = ['route'=>route('center.fee.wallet.status'),'label'=>'Fee Wallet','sub'=>'Wallet status','icon'=>'bi-wallet2','color'=>'#8b5cf6','bg'=>'#f5f3ff'];
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

{{-- Notices --}}
@include('institute.notices._widget', [
    'dashboardNotices'    => $dashboardNotices,
    'noticeViewRoute'     => 'center.notices.index',
    'noticeReaderType'    => 'center',
    'noticeReaderId'      => auth()->guard('center')->id(),
    'noticeReadUrlPrefix' => '/center/notices',
])

{{-- Recent Admissions --}}
@if($canViewStudents && $recentStudents->count() > 0)
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-check text-primary"></i>
            <span class="fw-bold" style="font-size:13px;">Recent Admissions</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th class="ps-4 fw-semibold text-muted border-0" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Student</th>
                        <th class="fw-semibold text-muted border-0" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Course</th>
                        <th class="fw-semibold text-muted border-0" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Mobile</th>
                        <th class="text-end pe-4 fw-semibold text-muted border-0" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Admission Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentStudents as $s)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                                     style="width:28px;height:28px;font-size:10px;background:#185FA5;">
                                    {{ strtoupper(substr($s->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $s->name }}</div>
                                    <div class="text-muted" style="font-size:11px;">{{ $s->student_uid }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted">{{ $s->stream->course->name ?? '—' }}</td>
                        <td class="text-muted">{{ $s->mobile }}</td>
                        <td class="text-end pe-4 text-muted">{{ $s->admission_date?->format('d M Y') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@endsection
