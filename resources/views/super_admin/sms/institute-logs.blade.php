@extends('super_admin.layout')
@section('title', 'Institute SMS Logs')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('super_admin.sms.index') }}" class="text-decoration-none">SMS</a></li>
    <li class="breadcrumb-item active">{{ $setting->institute->name ?? 'Institute' }} — Logs</li>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('super_admin.sms.index') }}" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Back to SMS
        </a>
        <h4 class="mb-0 fw-bold mt-1">{{ $setting->institute->name ?? 'Institute' }} — SMS History</h4>
        <small class="text-muted">Provider: <strong>{{ strtoupper($setting->provider) }}</strong> &nbsp;|&nbsp; Sender: <strong>{{ $setting->sender_id }}</strong></small>
    </div>
    <form method="POST" action="{{ route('super_admin.sms.toggle-institute', $setting->institute_id) }}">
        @csrf
        <button type="submit"
            class="btn btn-sm {{ $setting->is_sms_disabled ? 'btn-outline-success' : 'btn-outline-danger' }}"
            onclick="return confirm('Are you sure?')">
            <i class="bi {{ $setting->is_sms_disabled ? 'bi-check-circle' : 'bi-slash-circle' }} me-1"></i>
            {{ $setting->is_sms_disabled ? 'Enable SMS' : 'Disable SMS' }}
        </button>
    </form>
</div>

{{-- All-Time Stats --}}
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-primary">{{ number_format($stats->total ?? 0) }}</div>
            <div class="small text-muted">Total Sent</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-success">{{ number_format($stats->sent ?? 0) }}</div>
            <div class="small text-muted">Delivered</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-danger">{{ number_format($stats->failed ?? 0) }}</div>
            <div class="small text-muted">Failed</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-warning">{{ number_format($stats->notices ?? 0) }}</div>
            <div class="small text-muted">Notices</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold" style="color:#7c3aed;">{{ number_format($stats->reminders ?? 0) }}</div>
            <div class="small text-muted">Due Reminders</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small mb-1">Type</label>
                <select name="type" class="form-select form-select-sm" style="width:160px;">
                    <option value="">All Types</option>
                    <option value="otp"          {{ request('type') === 'otp'          ? 'selected' : '' }}>OTP</option>
                    <option value="notice"       {{ request('type') === 'notice'       ? 'selected' : '' }}>Notice</option>
                    <option value="due_reminder" {{ request('type') === 'due_reminder' ? 'selected' : '' }}>Due Reminder</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm" style="width:140px;">
                    <option value="">All Status</option>
                    <option value="sent"    {{ request('status') === 'sent'    ? 'selected' : '' }}>Sent</option>
                    <option value="failed"  {{ request('status') === 'failed'  ? 'selected' : '' }}>Failed</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">Month</label>
                <input type="month" name="month" class="form-control form-control-sm" style="width:160px;"
                       value="{{ request('month') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                @if(request()->hasAny(['type', 'status', 'month']))
                <a href="{{ route('super_admin.sms.institute-logs', $setting->institute_id) }}" class="btn btn-outline-secondary btn-sm ms-1">
                    <i class="bi bi-x me-1"></i>Clear
                </a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($logs->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-chat-square fs-3 d-block mb-2"></i>No SMS found for selected filters.
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Mobile</th>
                        <th>Message</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td class="text-muted text-nowrap">{{ $log->created_at->format('d M Y H:i') }}</td>
                        <td>
                            @php
                                $typeMap = [
                                    'otp'          => ['OTP', 'secondary'],
                                    'notice'       => ['Notice', 'warning'],
                                    'due_reminder' => ['Due Reminder', 'purple'],
                                ];
                                [$label, $color] = $typeMap[$log->type] ?? [$log->type, 'light'];
                            @endphp
                            @if($color === 'purple')
                                <span class="badge" style="background:#7c3aed;font-size:0.7rem;">{{ $label }}</span>
                            @else
                                <span class="badge bg-{{ $color }}-subtle text-{{ $color }} border border-{{ $color }}-subtle">{{ $label }}</span>
                            @endif
                        </td>
                        <td>{{ $log->mobile }}</td>
                        <td class="text-truncate" style="max-width:300px;" title="{{ $log->message }}">{{ $log->message }}</td>
                        <td class="text-center">
                            @if($log->status === 'sent')
                                <i class="bi bi-check-circle-fill text-success" title="Sent"></i>
                            @elseif($log->status === 'failed')
                                <i class="bi bi-x-circle-fill text-danger" title="Failed: {{ $log->provider_response }}"></i>
                            @else
                                <i class="bi bi-hourglass-split text-muted" title="Pending"></i>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3 d-flex align-items-center justify-content-between">
            <small class="text-muted">{{ $logs->total() }} records</small>
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>

@endsection
