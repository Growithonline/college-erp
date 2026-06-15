@extends('library_staff.layout')
@section('title', 'Library Staff Dashboard')
@section('breadcrumb', 'Dashboard')
@section('content')

@php
    $perms = $staff->getPermissions();
    $institute = $staff->institute;
@endphp

<style>
.stat-card { border:none; border-radius:14px; box-shadow:0 2px 12px rgba(0,0,0,.06); overflow:hidden; }
.stat-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; }
.perm-chip { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; background:#f0f9ff; color:#0ea5e9; border:1px solid #bae6fd; margin:2px; }
</style>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h4 class="mb-0 fw-bold" style="color:#0c4a6e;">
            <i class="bi bi-journals me-2" style="color:#0ea5e9;"></i>Library Dashboard
        </h4>
        <small class="text-muted">
            {{ $staff->employee_id }} &nbsp;·&nbsp;
            {{ \App\Models\LibraryStaff::DESIGNATION_LABELS[$staff->designation] ?? $staff->designation }} &nbsp;·&nbsp;
            {{ \App\Models\LibraryStaff::SHIFT_LABELS[$staff->shift] ?? $staff->shift }} Shift
        </small>
    </div>
    <div class="text-end">
        @if($staff->last_login_at)
        <small class="text-muted d-block">Last login: {{ $staff->last_login_at->diffForHumans() }}</small>
        @endif
        @if($staff->last_login_ip)
        <small class="text-muted d-block">IP: {{ $staff->last_login_ip }}</small>
        @endif
    </div>
</div>

{{-- Quick stats --}}
<div class="row g-3 mb-4">
    @if(in_array('issue_create', $perms) || in_array('return_process', $perms))
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon" style="background:#f0f9ff;">
                    <i class="bi bi-arrow-left-right" style="color:#0ea5e9;"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5" style="color:#0c4a6e;">
                        {{ \App\Models\Library\LibraryTransaction::whereHas('bookCopy.book', fn($q) => $q->where('institute_id', $institute->id))->whereDate('issued_at', today())->count() }}
                    </div>
                    <div class="text-muted small">Today's Issues</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon" style="background:#fff7ed;">
                    <i class="bi bi-exclamation-triangle" style="color:#f97316;"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5" style="color:#c2410c;">
                        {{ \App\Models\Library\LibraryTransaction::whereHas('bookCopy.book', fn($q) => $q->where('institute_id', $institute->id))->whereNull('returned_at')->where('due_date', '<', today())->count() }}
                    </div>
                    <div class="text-muted small">Overdue</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(in_array('books_view', $perms))
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon" style="background:#f0fdf4;">
                    <i class="bi bi-book" style="color:#16a34a;"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5" style="color:#166534;">
                        {{ \App\Models\Library\LibraryBook::where('institute_id', $institute->id)->where('is_active', true)->count() }}
                    </div>
                    <div class="text-muted small">Total Books</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(in_array('fine_view', $perms))
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon" style="background:#fef2f2;">
                    <i class="bi bi-cash-coin" style="color:#dc2626;"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5" style="color:#991b1b;">
                        ₹{{ number_format(\App\Models\Library\LibraryFinePayment::whereHas('transaction.bookCopy.book', fn($q) => $q->where('institute_id', $institute->id))->whereDate('paid_at', today())->sum('amount'), 0) }}
                    </div>
                    <div class="text-muted small">Fines Today</div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- Permissions summary --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-shield-check me-2 text-primary"></i>Your Access Permissions</h6>
    </div>
    <div class="card-body p-3">
        @php
            $allLabels = collect(\App\Models\LibraryStaff::PERMISSION_GROUPS)->flatMap(fn($g) => $g)->toArray();
            $preset = $staff->permissionRecord?->preset ?? 'custom';
        @endphp
        <div class="mb-2">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                Preset: {{ \App\Models\LibraryStaff::PRESET_LABELS[$preset] ?? 'Custom' }}
            </span>
        </div>
        @if(!empty($perms))
            @foreach($perms as $perm)
                <span class="perm-chip">
                    <i class="bi bi-check2"></i>
                    {{ $allLabels[$perm] ?? $perm }}
                </span>
            @endforeach
        @else
            <p class="text-muted small mb-0">No permissions assigned. Please contact your administrator.</p>
        @endif
    </div>
</div>

{{-- Quick actions based on permissions --}}
@if(!empty($perms))
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-lightning-charge me-2 text-warning"></i>Quick Actions</h6>
    </div>
    <div class="card-body p-3">
        <div class="d-flex flex-wrap gap-2">
            @if(in_array('issue_create', $perms))
            <a href="{{ route('library.circulation.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-up-right-circle me-1"></i>Issue Book
            </a>
            @endif
            @if(in_array('return_process', $perms))
            <a href="{{ route('library.circulation.index') }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-arrow-down-left-circle me-1"></i>Process Return
            </a>
            @endif
            @if(in_array('books_view', $perms))
            <a href="{{ route('library.books.index') }}" class="btn btn-outline-info btn-sm">
                <i class="bi bi-search me-1"></i>Search Books
            </a>
            @endif
            @if(in_array('fine_collect', $perms))
            <a href="{{ route('library.fines.index') }}" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-cash me-1"></i>Collect Fine
            </a>
            @endif
            @if(in_array('members_view', $perms))
            <a href="{{ route('library.members.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-person-vcard me-1"></i>View Members
            </a>
            @endif
        </div>
    </div>
</div>
@endif

@endsection
