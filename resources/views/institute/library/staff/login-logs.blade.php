@extends('institute.layout')
@section('title', 'Library Staff — Login Logs')
@section('breadcrumb', 'Library Management / Staff / Login Logs')
@section('content')

<style>
.pill { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600; }
.pill-success { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.pill-failed  { background:#fee2e2; color:#dc2626; border:1px solid #fecaca; }
.pill-locked  { background:#ffedd5; color:#c2410c; border:1px solid #fed7aa; }
.pill-ip      { background:#dbeafe; color:#1d4ed8; border:1px solid #bfdbfe; }
.pill-default { background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Library Staff — Login Logs</h4>
        <small class="text-muted">All login attempts across library staff accounts.</small>
    </div>
    <a href="{{ route('library.staff.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Staff List
    </a>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('library.staff.login-logs') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold small mb-1">Staff Member</label>
                <select name="staff_id" class="form-select form-select-sm">
                    <option value="">All Staff</option>
                    @foreach($staffList as $s)
                        <option value="{{ $s->id }}" {{ request('staff_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->name }} ({{ $s->employee_id }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="success"   {{ request('status') === 'success'    ? 'selected' : '' }}>Success</option>
                    <option value="failed_otp"{{ request('status') === 'failed_otp' ? 'selected' : '' }}>Failed OTP</option>
                    <option value="locked"    {{ request('status') === 'locked'     ? 'selected' : '' }}>Account Locked</option>
                    <option value="ip_change" {{ request('status') === 'ip_change'  ? 'selected' : '' }}>IP Changed</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small mb-1">Date</label>
                <input type="date" name="date" class="form-control form-control-sm"
                       value="{{ request('date') }}" max="{{ date('Y-m-d') }}">
            </div>
            <div class="col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                @if(request()->hasAny(['staff_id', 'status', 'date']))
                <a href="{{ route('library.staff.login-logs') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    @if($logs->isEmpty())
    <div class="card-body text-center py-5">
        <i class="bi bi-clock-history" style="font-size:3rem;color:#94a3b8;"></i>
        <h6 class="mt-3 text-muted">No login logs found for the selected filters.</h6>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="font-size:12px;">Staff Member</th>
                    <th style="font-size:12px;">Status</th>
                    <th style="font-size:12px;">IP Address</th>
                    <th style="font-size:12px;">User Agent</th>
                    <th style="font-size:12px;">Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                @php
                    $pillClass = match($log->status) {
                        'success'    => 'pill-success',
                        'failed_otp' => 'pill-failed',
                        'locked'     => 'pill-locked',
                        'ip_change'  => 'pill-ip',
                        default      => 'pill-default',
                    };
                    $pillIcon = match($log->status) {
                        'success'    => 'check-circle-fill',
                        'failed_otp' => 'x-circle-fill',
                        'locked'     => 'lock-fill',
                        'ip_change'  => 'geo-alt-fill',
                        default      => 'circle',
                    };
                    $pillLabel = match($log->status) {
                        'success'    => 'Success',
                        'failed_otp' => 'Failed OTP',
                        'locked'     => 'Account Locked',
                        'ip_change'  => 'IP Changed',
                        default      => ucwords(str_replace('_', ' ', $log->status)),
                    };
                @endphp
                <tr>
                    <td>
                        <div class="fw-semibold small">{{ $log->libraryStaff?->name ?? '—' }}</div>
                        <code class="text-muted" style="font-size:11px;">{{ $log->libraryStaff?->employee_id }}</code>
                    </td>
                    <td>
                        <span class="pill {{ $pillClass }}">
                            <i class="bi bi-{{ $pillIcon }}"></i> {{ $pillLabel }}
                        </span>
                    </td>
                    <td><code class="small">{{ $log->ip_address ?? '—' }}</code></td>
                    <td class="text-muted" style="font-size:11px; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                        title="{{ $log->user_agent }}">
                        {{ $log->user_agent ? \Illuminate\Support\Str::limit($log->user_agent, 50) : '—' }}
                    </td>
                    <td class="text-muted small">
                        {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y, h:i A') }}
                        <div style="font-size:11px;">{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="p-3 border-top d-flex align-items-center justify-content-between">
        <small class="text-muted">Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} entries</small>
        {{ $logs->links() }}
    </div>
    @endif
    @endif
</div>
@endsection
