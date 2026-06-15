@extends('institute.layout')
@section('title', 'Library Staff — Activity Logs')
@section('breadcrumb', 'Library Management / Staff / Activity Logs')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-activity me-2 text-primary"></i>Library Staff — Activity Logs</h4>
        <small class="text-muted">All recorded actions across library staff accounts.</small>
    </div>
    <a href="{{ route('library.staff.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Staff List
    </a>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('library.staff.activity-logs') }}" class="row g-2 align-items-end">
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
                <label class="form-label fw-semibold small mb-1">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">All Actions</option>
                    @foreach(\App\Models\LibraryStaffActivityLog::ACTION_LABELS as $key => $label)
                        <option value="{{ $key }}" {{ request('action') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
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
                @if(request()->hasAny(['staff_id', 'action', 'date']))
                <a href="{{ route('library.staff.activity-logs') }}" class="btn btn-outline-secondary btn-sm">
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
        <i class="bi bi-activity" style="font-size:3rem;color:#94a3b8;"></i>
        <h6 class="mt-3 text-muted">No activity logs found for the selected filters.</h6>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="font-size:12px;">Staff Member</th>
                    <th style="font-size:12px;">Action</th>
                    <th style="font-size:12px;">Details</th>
                    <th style="font-size:12px;">IP Address</th>
                    <th style="font-size:12px;">Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                @php
                    $badgeClass = match($log->action) {
                        'login'          => 'bg-success-subtle text-success border-success-subtle',
                        'logout'         => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                        'profile_update' => 'bg-info-subtle text-info border-info-subtle',
                        'ip_change'      => 'bg-warning-subtle text-warning border-warning-subtle',
                        'session_kicked' => 'bg-danger-subtle text-danger border-danger-subtle',
                        'otp_sent'       => 'bg-primary-subtle text-primary border-primary-subtle',
                        default          => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                    };
                @endphp
                <tr>
                    <td>
                        <div class="fw-semibold small">{{ $log->libraryStaff?->name ?? '—' }}</div>
                        <code class="text-muted" style="font-size:11px;">{{ $log->libraryStaff?->employee_id }}</code>
                    </td>
                    <td>
                        <span class="badge {{ $badgeClass }} border" style="font-size:11px;">
                            {{ \App\Models\LibraryStaffActivityLog::ACTION_LABELS[$log->action] ?? ucwords(str_replace('_', ' ', $log->action)) }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ $log->details ?? '—' }}</td>
                    <td><code class="small">{{ $log->ip_address ?? '—' }}</code></td>
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
