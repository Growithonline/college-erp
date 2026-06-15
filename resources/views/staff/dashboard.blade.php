@extends('staff.layout')
@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')

@php
    $canViewAdmissions = $staff->canViewAdmissions();
    $canManageAdmissions = $staff->canManageAdmissions();
    $canEditAdmissions = $staff->canEditAdmissions();
    $canCollectFee = $staff->canCollectFee();
    $canViewFeeHistory = $staff->canViewFeeHistory();
    $canViewAdmissionReports = $staff->canViewAdmissionReports();
    $canViewFeeReports = $staff->canViewFeeReports();
    $canManagePracticalTokens = $staff->canManagePracticalTokens();
    $canViewStatements = $staff->canViewStatements();
    $canViewLibrary = $staff->canViewLibrary();
    $canManageStaff = $staff->canManageStaff();
    $enabledPermissions = $staff->enabledPermissionLabels();
    $permissionColors = [
        'admission_add' => 'primary',
        'admission_approve' => 'info',
        'admission_view' => 'secondary',
        'fee_collect' => 'success',
        'fee_view' => 'dark',
        'fee_reports' => 'warning text-dark',
        'student_view' => 'info',
        'student_edit' => 'secondary',
        'notice_post' => 'warning text-dark',
        'reports' => 'secondary',
        'staff_manage' => 'danger',
    ];
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Welcome, {{ $staff->name }}</h4>
        <small class="text-muted">
            {{ $staff->role?->name ?? 'Staff' }} -
            {{ $activeSession?->name ?? 'No active session' }}
        </small>
    </div>
</div>

<div class="row g-3 mb-4">
    @if($canViewAdmissions)
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-2">
                        <i class="bi bi-people text-primary fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Students</div>
                        <div class="fw-bold fs-5">{{ number_format($totalStudents) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($canViewFeeHistory)
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success bg-opacity-10 p-2">
                        <i class="bi bi-cash-stack text-success fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Today Collected</div>
                        <div class="fw-bold fs-5 text-success">Rs {{ number_format($todayCollected) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($canViewAdmissions)
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning bg-opacity-10 p-2">
                        <i class="bi bi-person-plus text-warning fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Today Admissions</div>
                        <div class="fw-bold fs-5">{{ $todayAdmissions }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-info bg-opacity-10 p-2">
                        <i class="bi bi-shield-check text-info fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">My Role</div>
                        <div class="fw-semibold" style="font-size:13px;">{{ $staff->role?->name ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-4">
    @forelse($enabledPermissions as $key => $label)
        <span class="badge bg-{{ $permissionColors[$key] ?? 'secondary' }} bg-opacity-75">
            <i class="bi bi-check-circle me-1"></i>{{ $label }}
        </span>
    @empty
        <span class="text-muted small">Is role ko abhi koi module permission assign nahi hai.</span>
    @endforelse
</div>

<div class="row g-3 mb-4">
    @if($canManageAdmissions)
    <div class="col-6 col-md-2">
        <a href="{{ route('staff.admissions.quick-create') }}"
           class="card border-0 shadow-sm text-decoration-none text-dark h-100">
            <div class="card-body text-center py-3">
                <i class="bi bi-lightning-fill text-warning fs-4 mb-1 d-block"></i>
                <div class="small fw-semibold">Quick Register</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-2">
        <a href="{{ route('staff.admissions.create') }}"
           class="card border-0 shadow-sm text-decoration-none text-dark h-100">
            <div class="card-body text-center py-3">
                <i class="bi bi-person-plus text-primary fs-4 mb-1 d-block"></i>
                <div class="small fw-semibold">Full Admission</div>
            </div>
        </a>
    </div>
    @endif

    @if($canCollectFee)
    <div class="col-6 col-md-2">
        <a href="{{ route('staff.fee.create') }}"
           class="card border-0 shadow-sm text-decoration-none text-dark h-100">
            <div class="card-body text-center py-3">
                <i class="bi bi-cash-coin text-success fs-4 mb-1 d-block"></i>
                <div class="small fw-semibold">Collect Fee</div>
            </div>
        </a>
    </div>
    @endif

    @if($canManagePracticalTokens)
    <div class="col-6 col-md-2">
        <a href="{{ route('staff.fee.practical-tokens.index') }}"
           class="card border-0 shadow-sm text-decoration-none text-dark h-100">
            <div class="card-body text-center py-3">
                <i class="bi bi-ticket-perforated text-warning fs-4 mb-1 d-block"></i>
                <div class="small fw-semibold">Practical Tokens</div>
            </div>
        </a>
    </div>
    @endif

    @if($canViewAdmissions)
    <div class="col-6 col-md-2">
        <a href="{{ route('staff.admissions.index') }}"
           class="card border-0 shadow-sm text-decoration-none text-dark h-100">
            <div class="card-body text-center py-3">
                <i class="bi bi-people text-info fs-4 mb-1 d-block"></i>
                <div class="small fw-semibold">Students</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-2">
        <a href="{{ route('staff.students.search') }}"
           class="card border-0 shadow-sm text-decoration-none text-dark h-100">
            <div class="card-body text-center py-3">
                <i class="bi bi-search text-primary fs-4 mb-1 d-block"></i>
                <div class="small fw-semibold">Global Search</div>
            </div>
        </a>
    </div>
    @endif

    @if($canViewStatements)
    <div class="col-6 col-md-2">
        <a href="{{ route('staff.statement.search-student') }}"
           class="card border-0 shadow-sm text-decoration-none text-dark h-100">
            <div class="card-body text-center py-3">
                <i class="bi bi-file-earmark-text text-dark fs-4 mb-1 d-block"></i>
                <div class="small fw-semibold">Statements</div>
            </div>
        </a>
    </div>
    @endif

    @if($canViewLibrary)
    <div class="col-6 col-md-2">
        <a href="{{ route('staff.library.dashboard') }}"
           class="card border-0 shadow-sm text-decoration-none text-dark h-100">
            <div class="card-body text-center py-3">
                <i class="bi bi-journal-bookmark text-primary fs-4 mb-1 d-block"></i>
                <div class="small fw-semibold">Library</div>
            </div>
        </a>
    </div>
    @endif

    @if($canViewFeeHistory)
    <div class="col-6 col-md-2">
        <a href="{{ route('staff.fee.index') }}"
           class="card border-0 shadow-sm text-decoration-none text-dark h-100">
            <div class="card-body text-center py-3">
                <i class="bi bi-receipt text-secondary fs-4 mb-1 d-block"></i>
                <div class="small fw-semibold">Fee History</div>
            </div>
        </a>
    </div>
    @endif

    @if($canViewFeeReports)
    <div class="col-6 col-md-2">
        <a href="{{ route('staff.reports.fee-collection') }}"
           class="card border-0 shadow-sm text-decoration-none text-dark h-100">
            <div class="card-body text-center py-3">
                <i class="bi bi-bar-chart-line text-warning fs-4 mb-1 d-block"></i>
                <div class="small fw-semibold">Fee Reports</div>
            </div>
        </a>
    </div>
    @endif

    @if($canViewFeeReports)
    <div class="col-6 col-md-2">
        <a href="{{ route('staff.reports.fee-due-list') }}"
           class="card border-0 shadow-sm text-decoration-none text-dark h-100">
            <div class="card-body text-center py-3">
                <i class="bi bi-wallet2 text-danger fs-4 mb-1 d-block"></i>
                <div class="small fw-semibold">Due List</div>
            </div>
        </a>
    </div>
    @endif

    @if($canManageStaff)
    <div class="col-6 col-md-2">
        <a href="{{ route('staff.staff-manage.index') }}"
           class="card border-0 shadow-sm text-decoration-none text-dark h-100">
            <div class="card-body text-center py-3">
                <i class="bi bi-people-fill text-danger fs-4 mb-1 d-block"></i>
                <div class="small fw-semibold">Staff Manage</div>
            </div>
        </a>
    </div>
    @endif

    @if($canViewAdmissionReports)
    <div class="col-6 col-md-2">
        <a href="{{ route('staff.reports.admission') }}"
           class="card border-0 shadow-sm text-decoration-none text-dark h-100">
            <div class="card-body text-center py-3">
                <i class="bi bi-graph-up-arrow text-info fs-4 mb-1 d-block"></i>
                <div class="small fw-semibold">Admissions Report</div>
            </div>
        </a>
    </div>
    @endif
</div>

@include('institute.notices._widget', [
    'dashboardNotices'    => $dashboardNotices,
    'noticeViewRoute'     => 'staff.notices.index',
    'noticeReaderType'    => 'staff',
    'noticeReaderId'      => auth()->guard('staff')->id(),
    'noticeReadUrlPrefix' => '/staff/notices',
])

@if($recentCollections->isNotEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-2">
        <span class="fw-semibold small">
            <i class="bi bi-clock-history me-1 text-success"></i> Recent Fee Collections
        </span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Invoice</th>
                    <th>Student</th>
                    <th>Date</th>
                    <th>Mode</th>
                    <th class="text-end pe-3">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentCollections as $inv)
                <tr>
                    <td class="ps-3 fw-semibold text-primary">{{ $inv->invoice_no }}</td>
                    <td>{{ $inv->student?->name ?? '-' }}</td>
                    <td class="text-muted">{{ $inv->payment_date?->format('d M Y') }}</td>
                    <td>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary">
                            {{ strtoupper($inv->payment_mode) }}
                        </span>
                    </td>
                    <td class="text-end pe-3 fw-semibold text-success">
                        Rs {{ number_format($inv->paid_amount) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
