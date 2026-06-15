@extends('institute.layout')
@section('title', 'SMS History')
@section('breadcrumb', 'Master / SMS / History')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('master.sms.index') }}" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Back to SMS Settings
        </a>
        <h4 class="mb-0 fw-bold mt-1">SMS History</h4>
        @if($setting)
        <small class="text-muted">Provider: <strong>{{ strtoupper($setting->provider) }}</strong> &nbsp;|&nbsp; Sender: <strong>{{ $setting->sender_id }}</strong></small>
        @endif
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <select name="type" class="form-select form-select-sm" style="width:auto;">
                <option value="">All Types</option>
                <option value="notice" {{ request('type') === 'notice' ? 'selected' : '' }}>Notice</option>
                <option value="due_reminder" {{ request('type') === 'due_reminder' ? 'selected' : '' }}>Due Reminder</option>
            </select>
            <select name="status" class="form-select form-select-sm" style="width:auto;">
                <option value="">All Status</option>
                <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
            <a href="{{ route('master.sms.logs') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($logs->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-chat-square fs-3 d-block mb-2"></i>Koi SMS nahi mila.
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Date & Time</th>
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
                                $typeMap = ['notice' => ['Notice','info'], 'due_reminder' => ['Due Reminder','warning']];
                                [$label, $color] = $typeMap[$log->type] ?? [$log->type, 'secondary'];
                            @endphp
                            <span class="badge bg-{{ $color }}-subtle text-{{ $color }} border border-{{ $color }}-subtle">{{ $label }}</span>
                        </td>
                        <td>{{ $log->mobile }}</td>
                        <td class="text-truncate" style="max-width:350px;" title="{{ $log->message }}">
                            {{ $log->message }}
                        </td>
                        <td class="text-center">
                            @if($log->status === 'sent')
                                <i class="bi bi-check-circle-fill text-success" title="Delivered"></i>
                            @elseif($log->status === 'failed')
                                <i class="bi bi-x-circle-fill text-danger" title="Failed"></i>
                            @else
                                <i class="bi bi-hourglass-split text-muted" title="Pending"></i>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $logs->links() }}</div>
        @endif
    </div>
</div>

@endsection
