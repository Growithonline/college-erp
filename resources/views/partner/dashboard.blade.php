@extends('partner.layout')
@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')
@section('content')

@php
    $canAdmit = $partner->canManageAdmissions();
    $canViewStudents = $partner->canViewStudents();
    $canCollectFee = $partner->canCollectFee();
    $enabledPermissions = collect([
        'admission_add' => 'Add Admission',
        'student_view' => 'View Students',
        'fee_collect' => 'Collect Fee',
    ])->filter(fn($label, $key) => $partner->hasPermission($key));
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Welcome, {{ $partner->name }}</h4>
        <small class="text-muted">
            Channel Partner - {{ $activeSession?->name ?? 'No active session' }} | {{ now()->format('d M Y, D') }}
        </small>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-4">
    @forelse($enabledPermissions as $label)
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
            <i class="bi bi-check-circle me-1"></i>{{ $label }}
        </span>
    @empty
        <span class="text-muted small">Is partner account ko abhi koi operational permission assign nahi hai.</span>
    @endforelse
</div>

<div class="row g-3 mb-4">
    @if($canViewStudents)
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #3b82f6!important;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2" style="background:#eff6ff;">
                        <i class="bi bi-people-fill text-primary fs-4"></i>
                    </div>
                    <div>
                        <div class="small text-muted">My Students</div>
                        <div class="fw-bold fs-5">{{ number_format($totalStudents) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($canCollectFee)
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #f59e0b!important;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2" style="background:#fffbeb;">
                        <i class="bi bi-cash-stack text-warning fs-4"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Fee Collected</div>
                        <div class="fw-bold fs-5">Rs {{ number_format($totalCollected) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($canCollectFee && $totalCommission > 0)
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #10b981!important;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2" style="background:#ecfdf5;">
                        <i class="bi bi-percent text-success fs-4"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Commission Earned</div>
                        <div class="fw-bold fs-5 text-success">Rs {{ number_format($totalCommission) }}</div>
                        <div class="small text-muted">@ {{ $partner->commission_percent }}%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@if($canAdmit || $canViewStudents || $canCollectFee)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-2">
        <h6 class="mb-0 fw-semibold small"><i class="bi bi-person-plus me-2 text-primary"></i>Quick Actions</h6>
    </div>
    <div class="card-body py-3 d-flex gap-2 flex-wrap">
        @if($canAdmit)
        <a href="{{ route('partner.admissions.quick-create') }}" class="btn btn-warning btn-sm fw-semibold">
            <i class="bi bi-lightning me-1"></i> Quick Register
        </a>
        @endif
        @if($canViewStudents)
        <a href="{{ route('partner.students.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-list me-1"></i> My Students
        </a>
        @endif
        @if($canCollectFee)
        @php
            $w = $channelWallet ?? null;
            $wBlocked = $w ? $w->getBlockStatus() : null;
            $wExpired = $w && $w->isExpired();
            $wLow = $w && $wBlocked && !$wBlocked['blocked'] && (float)$w->remaining_tokens < (float)$w->total_tokens * 0.1;
        @endphp
        @if($w && $wBlocked && $wBlocked['blocked'])
            <div class="alert alert-{{ $wExpired ? 'danger' : 'warning' }} py-2 mb-2 small d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>{{ $wBlocked['reason'] }}</span>
            </div>
            <a href="{{ route('partner.fee.wallet.status') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-send me-1"></i> Request Extension
            </a>
        @else
            <a href="{{ route('partner.fee.create') }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-cash-coin me-1"></i> Collect Fee
            </a>
            @if($w)
                <div class="mt-2 small text-muted">
                    Balance: <strong class="{{ $wLow ? 'text-warning' : 'text-success' }}">
                        ₹{{ number_format($w->remaining_tokens, 0) }}
                    </strong>
                    &nbsp;|&nbsp; Expires: {{ $w->expires_at?->format('d M Y') ?? '—' }}
                    @if($wLow) <span class="badge bg-warning text-dark ms-1">Low</span> @endif
                    &nbsp;<a href="{{ route('partner.fee.wallet.status') }}" class="text-muted small">
                        <i class="bi bi-wallet2"></i>
                    </a>
                </div>
            @endif
        @endif
        @endif
    </div>
</div>
@endif

@if($canViewStudents && $recentStudents->count() > 0)
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-2">
        <h6 class="mb-0 fw-semibold small">
            <i class="bi bi-person-check me-2 text-primary"></i>Recent Students
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Student</th>
                    <th>Course</th>
                    <th class="text-end pe-3">Admission Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentStudents as $s)
                <tr>
                    <td class="ps-3">
                        <div class="fw-semibold small">{{ $s->name }}</div>
                        <div class="text-muted" style="font-size:11px;">{{ $s->student_uid }}</div>
                    </td>
                    <td class="small">{{ $s->stream->course->name ?? '-' }}</td>
                    <td class="text-end pe-3 small text-muted">{{ $s->admission_date?->format('d M Y') ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@include('institute.notices._widget', [
    'dashboardNotices'    => $dashboardNotices,
    'noticeViewRoute'     => 'partner.notices.index',
    'noticeReaderType'    => 'partner',
    'noticeReaderId'      => auth()->guard('partner')->id(),
    'noticeReadUrlPrefix' => '/partner/notices',
])

@endsection
