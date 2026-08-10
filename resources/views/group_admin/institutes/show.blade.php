@extends('group_admin.layout')
@section('title', $institute->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('group_admin.institutes.index') }}" class="text-decoration-none">Institutes</a></li>
    <li class="breadcrumb-item active">{{ $institute->name }}</li>
@endsection

@section('content')

@php
    $isExpired    = $institute->subscription_end && now()->gt($institute->subscription_end);
    $expiringSoon = $institute->subscription_end && !$isExpired && now()->addDays(30)->gte($institute->subscription_end);
    $daysLeft     = $institute->subscription_end && !$isExpired ? (int) floor(now()->diffInDays($institute->subscription_end)) : null;
    $studentPct   = $institute->student_limit ? min(100, round(($institute->students_count / $institute->student_limit) * 100)) : 0;
@endphp

<a href="{{ route('group_admin.institutes.index') }}" class="btn btn-outline-secondary btn-sm mb-3">
    <i class="bi bi-arrow-left me-1"></i> Back to Institutes
</a>

{{-- Hero --}}
<div class="ip-hero mb-4">
    <div class="ip-hero-bg"></div>
    <div class="d-flex align-items-center gap-3 flex-wrap position-relative">
        @if($institute->image)
            <img src="{{ asset('storage/' . $institute->image) }}" alt="{{ $institute->name }}" class="ip-avatar">
        @else
            <div class="ip-avatar d-flex align-items-center justify-content-center">
                <i class="bi bi-building" style="font-size:34px;color:#fff;"></i>
            </div>
        @endif
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h4 class="mb-0 fw-bold text-white">{{ $institute->name }}</h4>
                @if($institute->status === 'active')
                    <span class="badge bg-success">Active</span>
                @else
                    <span class="badge bg-secondary">Inactive</span>
                @endif
            </div>
            <div class="ip-hero-sub mt-1">
                <i class="bi bi-upc-scan me-1"></i>{{ $institute->institute_uid }}
                <span class="mx-2">&bull;</span>
                <i class="bi bi-geo-alt me-1"></i>{{ $institute->city ?? 'Location not set' }}@if($institute->state), {{ $institute->state }}@endif
            </div>
        </div>
        @if($groupAdmin->can_reset_institute_password)
            <button type="button" class="btn btn-light btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#resetPwdModal">
                <i class="bi bi-key me-1"></i> Reset Password
            </button>
        @endif
    </div>
</div>

@if($groupAdmin->can_reset_institute_password)
<div class="modal fade" id="resetPwdModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('group_admin.institutes.reset-password', $institute->id) }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title">Reset Password — {{ $institute->name }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">New Password</label>
                        <input type="password" name="password" class="form-control form-control-sm" minlength="8" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control form-control-sm" minlength="8" required>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="notify_email" id="notifyEmail" value="1" checked>
                        <label class="form-check-label small" for="notifyEmail">
                            Send new password to owner's email ({{ $institute->owner_email }})
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Stat strip --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="ip-stat">
            <div class="ip-stat-icon" style="background:#ede9fe;"><i class="bi bi-people-fill" style="color:#7c3aed;"></i></div>
            <div class="flex-grow-1">
                <div class="text-muted small">Students</div>
                <div class="fw-bold">{{ number_format($institute->students_count) }} / {{ number_format($institute->student_limit ?? 0) }}</div>
                <div class="progress mt-1" style="height:4px;">
                    <div class="progress-bar bg-purple" style="width:{{ $studentPct }}%;background:#7c3aed;"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ip-stat">
            <div class="ip-stat-icon" style="background:{{ $institute->subscription_end === null ? '#e0f2fe' : ($isExpired ? '#fee2e2' : ($expiringSoon ? '#fef9c3' : '#dcfce7')) }};">
                <i class="bi {{ $institute->subscription_end === null ? 'bi-infinity' : ($isExpired ? 'bi-x-circle-fill' : 'bi-check-circle-fill') }}"
                   style="color:{{ $institute->subscription_end === null ? '#0284c7' : ($isExpired ? '#dc2626' : ($expiringSoon ? '#ca8a04' : '#16a34a')) }};"></i>
            </div>
            <div>
                <div class="text-muted small">Subscription</div>
                <div class="fw-bold">
                    @if($institute->subscription_end === null) Lifetime
                    @elseif($isExpired) Expired
                    @elseif($expiringSoon) Expiring Soon
                    @else Active
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ip-stat">
            <div class="ip-stat-icon" style="background:#dbeafe;"><i class="bi bi-calendar-event" style="color:#2563eb;"></i></div>
            <div>
                <div class="text-muted small">Days Remaining</div>
                <div class="fw-bold">
                    @if($daysLeft !== null) {{ $daysLeft }} days
                    @elseif($isExpired) <span class="text-danger">Expired</span>
                    @else —
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ip-stat">
            <div class="ip-stat-icon" style="background:#fee2e2;"><i class="bi bi-clock-history" style="color:#dc2626;"></i></div>
            <div>
                <div class="text-muted small">Member Since</div>
                <div class="fw-bold">{{ $institute->created_at?->format('d M Y') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Institute Info --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pb-0 pt-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-building text-primary me-2"></i>Institute Details</h6>
            </div>
            <div class="card-body">
                <div class="ip-row"><div class="ip-label"><i class="bi bi-upc-scan"></i> Institute ID</div><div class="ip-value fw-bold">{{ $institute->institute_uid }}</div></div>
                <div class="ip-row"><div class="ip-label"><i class="bi bi-signpost"></i> Short Name</div><div class="ip-value">{{ $institute->short_name }}</div></div>
                <div class="ip-row"><div class="ip-label"><i class="bi bi-telephone"></i> Mobile</div><div class="ip-value">{{ $institute->mobile }}</div></div>
                <div class="ip-row"><div class="ip-label"><i class="bi bi-envelope"></i> Email</div><div class="ip-value">{{ $institute->email }}</div></div>
                <div class="ip-row"><div class="ip-label"><i class="bi bi-geo-alt"></i> Address</div><div class="ip-value">{{ $institute->address ?? '—' }}</div></div>
                <div class="ip-row"><div class="ip-label"><i class="bi bi-map"></i> City / State</div><div class="ip-value">{{ $institute->city ?? '—' }}@if($institute->state), {{ $institute->state }}@endif @if($institute->pincode) — {{ $institute->pincode }}@endif</div></div>
            </div>
        </div>
    </div>

    {{-- Owner Info --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pb-0 pt-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-person text-success me-2"></i>Owner Details</h6>
            </div>
            <div class="card-body">
                <div class="ip-row"><div class="ip-label"><i class="bi bi-person-badge"></i> Owner Name</div><div class="ip-value">{{ $institute->owner_name }}</div></div>
                <div class="ip-row"><div class="ip-label"><i class="bi bi-telephone"></i> Mobile</div><div class="ip-value">{{ $institute->owner_mobile }}</div></div>
                <div class="ip-row"><div class="ip-label"><i class="bi bi-envelope-check"></i> Login Email</div><div class="ip-value">{{ $institute->owner_email }}</div></div>
                <div class="ip-row"><div class="ip-label"><i class="bi bi-whatsapp"></i> WhatsApp</div><div class="ip-value">{{ $institute->owner_whatsapp ?? '—' }}</div></div>
                <div class="ip-row"><div class="ip-label"><i class="bi bi-geo-alt"></i> Address</div><div class="ip-value">{{ $institute->owner_address ?? '—' }}</div></div>
            </div>
        </div>
    </div>
</div>

<style>
.ip-hero {
    position: relative;
    border-radius: 16px;
    padding: 28px;
    overflow: hidden;
}
.ip-hero-bg {
    position: absolute; inset: 0;
    background: linear-gradient(135deg, #4338ca 0%, #6366f1 55%, #818cf8 100%);
}
.ip-hero-bg::after {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(circle at 90% -10%, rgba(255,255,255,.15) 0%, transparent 55%);
}
.ip-avatar {
    height: 76px; width: 76px; flex-shrink: 0;
    border-radius: 16px; object-fit: contain;
    background: rgba(255,255,255,.15);
    border: 2px solid rgba(255,255,255,.35);
    padding: 6px;
}
.ip-hero-sub {
    color: rgba(255,255,255,.85);
    font-size: 13px;
}
.ip-stat {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    padding: 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    height: 100%;
}
.ip-stat-icon {
    width: 40px; height: 40px; flex-shrink: 0;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px;
}
.ip-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
}
.ip-row:last-child { border-bottom: none; padding-bottom: 0; }
.ip-label {
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 6px;
}
.ip-value {
    text-align: right;
    font-size: 14px;
    word-break: break-word;
}
</style>

@endsection
