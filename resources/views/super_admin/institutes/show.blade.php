@extends('super_admin.layout')
@section('title', $institute->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('super_admin.institutes.index') }}" class="text-decoration-none">Institutes</a></li>
    <li class="breadcrumb-item active">{{ $institute->name }}</li>
@endsection

@section('content')

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('super_admin.institutes.index') }}" class="btn btn-outline-secondary btn-sm">
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
    <div class="ms-auto d-flex gap-2">
        <form method="POST" action="{{ route('super_admin.institutes.resend-credentials', $institute->id) }}"
              onsubmit="return confirm('This will reset the admin password and send new credentials to {{ $institute->owner_email }}. Continue?')">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-envelope-arrow-up me-1"></i> Resend Credentials
            </button>
        </form>
        <form method="POST" action="{{ route('super_admin.institutes.toggle', $institute->id) }}">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn-sm btn-outline-{{ $institute->status === 'active' ? 'danger' : 'success' }}">
                <i class="bi bi-{{ $institute->status === 'active' ? 'slash-circle' : 'check-circle' }} me-1"></i>
                {{ $institute->status === 'active' ? 'Deactivate' : 'Activate' }}
            </button>
        </form>
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
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted fw-semibold" style="width:40%">Institute UID</td><td class="fw-bold">{{ $institute->institute_uid }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Name</td><td>{{ $institute->name }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Short Name</td><td>{{ $institute->short_name }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Mobile</td><td>{{ $institute->mobile }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Email</td><td>{{ $institute->email }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Address</td><td>{{ $institute->address }}</td></tr>
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
                    <tr><td class="text-muted fw-semibold">Email</td><td>{{ $institute->owner_email }}</td></tr>
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
                            @else —
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

    {{-- Created Info --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pb-0 pt-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-info-circle text-info me-2"></i>System Info</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted fw-semibold" style="width:40%">Onboarded On</td><td>{{ $institute->created_at?->format('d M Y, h:i A') }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Last Updated</td><td>{{ $institute->updated_at?->format('d M Y, h:i A') }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Student Limit</td><td>{{ number_format($institute->student_limit ?? 0) }}</td></tr>
                    <tr><td class="text-muted fw-semibold">Current Students</td><td>{{ number_format($institute->students_count) }}</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Password Reset --}}
<div class="row g-3 mt-1">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pb-0 pt-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-key text-danger me-2"></i>Reset Admin Password</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('super_admin.institutes.reset-password', $institute->id) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small">New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password" id="newPassword"
                                       class="form-control @error('password') is-invalid @enderror"
                                       required minlength="8" placeholder="Min. 8 characters">
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePwd('newPassword', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                                @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password_confirmation" id="confirmPassword"
                                       class="form-control" required minlength="8" placeholder="Re-enter password">
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePwd('confirmPassword', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="notify_email" id="notifyEmail" value="1" checked>
                                <label class="form-check-label small" for="notifyEmail">
                                    Send new password to owner's email
                                    <span class="text-muted">({{ $institute->owner_email }})</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Reset password for this institute admin?')">
                                <i class="bi bi-key me-1"></i> Reset Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function togglePwd(id, btn) {
    var input = document.getElementById(id);
    var isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    btn.innerHTML = isText ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
}
</script>

@endsection
