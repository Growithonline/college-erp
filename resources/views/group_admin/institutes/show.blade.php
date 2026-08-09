@extends('group_admin.layout')
@section('title', $institute->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('group_admin.institutes.index') }}" class="text-decoration-none">Institutes</a></li>
    <li class="breadcrumb-item active">{{ $institute->name }}</li>
@endsection

@section('content')

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('group_admin.institutes.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
    @if($institute->image)
        <img src="{{ asset('storage/' . $institute->image) }}" alt="{{ $institute->name }}"
             class="rounded border" style="height:42px;width:42px;object-fit:contain;background:#f8f9fa;">
    @else
        <div class="rounded border d-flex align-items-center justify-content-center bg-light"
             style="height:42px;width:42px;flex-shrink:0;">
            <i class="bi bi-building text-muted" style="font-size:18px;"></i>
        </div>
    @endif
    <h5 class="mb-0 fw-bold">{{ $institute->name }}</h5>
    @if($institute->status === 'active')
        <span class="badge bg-success-subtle text-success">Active</span>
    @else
        <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
    @endif

    @if($groupAdmin->can_reset_institute_password)
        <div class="ms-auto">
            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#resetPwdModal">
                <i class="bi bi-key me-1"></i> Reset Password
            </button>
        </div>

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
</div>

<div class="row g-3">
    {{-- Institute Info --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pb-0 pt-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-building text-primary me-2"></i>Institute Details</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted fw-semibold" style="width:40%">Institute ID</td><td class="fw-bold">{{ $institute->institute_uid }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Name</td><td>{{ $institute->name }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Short Name</td><td>{{ $institute->short_name }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Mobile</td><td>{{ $institute->mobile }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Email</td><td>{{ $institute->email }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Address</td><td>{{ $institute->address ?? '—' }}</td></tr>
                    <tr><td class="text-muted fw-semibold">City / State</td><td>{{ $institute->city }}@if($institute->state), {{ $institute->state }}@endif @if($institute->pincode) — {{ $institute->pincode }}@endif</td></tr>
                    <tr><td class="text-muted fw-semibold">Students</td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary">
                                {{ number_format($institute->students_count) }} / {{ number_format($institute->student_limit ?? 0) }}
                            </span>
                        </td>
                    </tr>
                </table>
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
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted fw-semibold" style="width:40%">Owner Name</td><td>{{ $institute->owner_name }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Mobile</td><td>{{ $institute->owner_mobile }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Login Email</td><td>{{ $institute->owner_email }}</td></tr>
                    <tr><td class="text-muted fw-semibold">WhatsApp</td><td>{{ $institute->owner_whatsapp ?? '—' }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Address</td><td>{{ $institute->owner_address ?? '—' }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Subscription --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pb-0 pt-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-calendar-check text-warning me-2"></i>Subscription</h6>
            </div>
            <div class="card-body">
                @php
                    $isExpired    = $institute->subscription_end && now()->gt($institute->subscription_end);
                    $expiringSoon = $institute->subscription_end && !$isExpired && now()->addDays(30)->gte($institute->subscription_end);
                @endphp
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted fw-semibold" style="width:40%">Start Date</td>
                        <td>{{ $institute->subscription_start ? \Carbon\Carbon::parse($institute->subscription_start)->format('d M Y') : '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">End Date</td>
                        <td>
                            @if($institute->subscription_end)
                                <span class="text-{{ $isExpired ? 'danger' : ($expiringSoon ? 'warning' : 'success') }} fw-semibold">
                                    {{ \Carbon\Carbon::parse($institute->subscription_end)->format('d M Y') }}
                                </span>
                                @if($isExpired) <span class="badge bg-danger-subtle text-danger ms-1">Expired</span>
                                @elseif($expiringSoon) <span class="badge bg-warning-subtle text-warning ms-1">Expiring Soon</span>
                                @else <span class="badge bg-success-subtle text-success ms-1">Active</span>
                                @endif
                            @else
                                <span class="badge bg-info-subtle text-info">Lifetime</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Days Remaining</td>
                        <td>
                            @if($institute->subscription_end && !$isExpired)
                                {{ now()->diffInDays($institute->subscription_end) }} days
                            @elseif($isExpired)
                                <span class="text-danger">Expired</span>
                            @else —
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- System Info --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pb-0 pt-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-info-circle text-info me-2"></i>System Info</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted fw-semibold" style="width:40%">Onboarded On</td><td>{{ $institute->created_at?->format('d M Y, h:i A') }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Student Limit</td><td>{{ number_format($institute->student_limit ?? 0) }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Current Students</td><td>{{ number_format($institute->students_count) }}</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
