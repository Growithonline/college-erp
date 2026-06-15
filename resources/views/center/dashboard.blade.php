@extends('center.layout')
@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')
@section('content')

@php
    $canAdmit = $center->canManageAdmissions();
    $canViewStudents = $center->canViewStudents();
    $canCollectFee = $center->canCollectFee();
    $enabledPermissions = collect([
        'admission_add' => 'Add Admission',
        'student_view' => 'View Students',
        'fee_collect' => 'Collect Fee',
    ])->filter(fn($label, $key) => $center->hasPermission($key));
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Welcome, {{ $center->name }}</h4>
        <small class="text-muted">
            Center - {{ $activeSession?->name ?? 'No active session' }} | {{ now()->format('d M Y, D') }}
        </small>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-4">
    @forelse($enabledPermissions as $label)
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
            <i class="bi bi-check-circle me-1"></i>{{ $label }}
        </span>
    @empty
        <span class="text-muted small">Is center account ko abhi koi operational permission assign nahi hai.</span>
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
                        <div class="small text-muted">This session</div>
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
                        <div class="small text-muted">This session</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="row g-3 mb-4">
    @if($canAdmit)
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2">
                <h6 class="mb-0 fw-semibold small"><i class="bi bi-person-plus me-2 text-primary"></i>Admissions</h6>
            </div>
            <div class="card-body py-3 d-flex gap-2 flex-wrap">
                <a href="{{ route('center.admissions.quick-create') }}" class="btn btn-warning btn-sm fw-semibold">
                    <i class="bi bi-lightning me-1"></i> Quick Register
                </a>
                <a href="{{ route('center.admissions.create') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-earmark-person me-1"></i> Full Form
                </a>
                @if($canViewStudents)
                <a href="{{ route('center.students.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-list me-1"></i> My Students
                </a>
                <a href="{{ route('center.students.search') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-search me-1"></i> Global Search
                </a>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if($canCollectFee)
    <div class="col-md-6">
        @php
            $w = $centerWallet ?? null;
            $wBlocked = $w ? $w->getBlockStatus() : null;
            $wExpired = $w && $w->isExpired();
            $wExhausted = $w && (float)$w->remaining_tokens <= 0;
            $wLow = $w && $wBlocked && !$wBlocked['blocked'] && (float)$w->remaining_tokens < (float)$w->total_tokens * 0.1;
        @endphp
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold small"><i class="bi bi-cash-stack me-2 text-success"></i>Fee Collection</h6>
                @if($w)
                    <a href="{{ route('center.fee.wallet.status') }}" class="text-muted small">
                        <i class="bi bi-wallet2 me-1"></i>Wallet
                    </a>
                @endif
            </div>
            <div class="card-body py-3">
                @if($w && $wBlocked && $wBlocked['blocked'])
                    <div class="alert alert-{{ $wExpired ? 'danger' : 'warning' }} py-2 mb-2 small d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span>{{ $wBlocked['reason'] }}</span>
                    </div>
                    <a href="{{ route('center.fee.wallet.status') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-send me-1"></i> Request Extension
                    </a>
                @else
                    <a href="{{ route('center.fee.create') }}" class="btn btn-success btn-sm">
                        <i class="bi bi-plus-circle me-1"></i> Collect Fee
                    </a>
                    @if($w)
                        <div class="mt-2 small text-muted">
                            Balance: <strong class="{{ $wLow ? 'text-warning' : 'text-success' }}">
                                ₹{{ number_format($w->remaining_tokens, 0) }}
                            </strong>
                            &nbsp;|&nbsp; Expires: {{ $w->expires_at?->format('d M Y') ?? '—' }}
                            @if($wLow) <span class="badge bg-warning text-dark ms-1">Low</span> @endif
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

@if($canViewStudents && $recentStudents->count() > 0)
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-2">
        <h6 class="mb-0 fw-semibold small">
            <i class="bi bi-person-check me-2 text-primary"></i>Recent Admissions
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Student</th>
                    <th>Course</th>
                    <th>Mobile</th>
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
                    <td class="small">{{ $s->mobile }}</td>
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
    'noticeViewRoute'     => 'center.notices.index',
    'noticeReaderType'    => 'center',
    'noticeReaderId'      => auth()->guard('center')->id(),
    'noticeReadUrlPrefix' => '/center/notices',
])

@endsection
