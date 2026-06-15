@extends('library_staff.layout')
@section('title', 'Activity Log')
@section('breadcrumb', 'Profile / Activity Log')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold" style="color:#0c4a6e;">
            <i class="bi bi-activity me-2" style="color:#0ea5e9;"></i>Activity Log
        </h4>
        <small class="text-muted">All actions recorded for your account.</small>
    </div>
    <a href="{{ route('library_staff.profile') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Profile
    </a>
</div>

<div class="card border-0 shadow-sm">
    @if($logs->isEmpty())
    <div class="card-body text-center py-5">
        <i class="bi bi-activity" style="font-size:3rem;color:#94a3b8;"></i>
        <h6 class="mt-3 text-muted">No activity recorded yet.</h6>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="font-size:12px;">Action</th>
                    <th style="font-size:12px;">Details</th>
                    <th style="font-size:12px;">IP Address</th>
                    <th style="font-size:12px;">Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td>
                        @php
                            $badge = match($log->action) {
                                'login'          => ['bg-success-subtle text-success border-success-subtle', 'box-arrow-in-right'],
                                'logout'         => ['bg-secondary-subtle text-secondary border-secondary-subtle', 'box-arrow-right'],
                                'profile_update' => ['bg-info-subtle text-info border-info-subtle', 'pencil-square'],
                                'ip_change'      => ['bg-warning-subtle text-warning border-warning-subtle', 'geo-alt'],
                                'session_kicked' => ['bg-danger-subtle text-danger border-danger-subtle', 'shield-exclamation'],
                                'otp_sent'       => ['bg-primary-subtle text-primary border-primary-subtle', 'envelope-check'],
                                default          => ['bg-secondary-subtle text-secondary border-secondary-subtle', 'circle'],
                            };
                        @endphp
                        <span class="badge {{ $badge[0] }} border">
                            <i class="bi bi-{{ $badge[1] }} me-1"></i>
                            {{ \App\Models\LibraryStaffActivityLog::ACTION_LABELS[$log->action] ?? ucwords(str_replace('_', ' ', $log->action)) }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ $log->details ?? '—' }}</td>
                    <td><code class="small">{{ $log->ip_address ?? '—' }}</code></td>
                    <td class="text-muted small">
                        {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y, h:i A') }}
                        <div class="text-muted" style="font-size:11px;">{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="p-3 border-top">
        {{ $logs->links() }}
    </div>
    @endif
    @endif
</div>
@endsection
