@extends('library_staff.layout')
@section('title', 'My Profile')
@section('breadcrumb', 'Profile')
@section('content')

<style>
.field-error { font-size:12px; color:#dc2626; margin-top:4px; display:flex; align-items:center; gap:4px; }
.field-error i { font-size:11px; }
.form-control.is-invalid, .form-select.is-invalid {
    border-color:#dc2626 !important;
    box-shadow:0 0 0 3px rgba(220,38,38,.12) !important;
    animation:shake .3s ease;
}
@keyframes shake {
    0%,100%{transform:translateX(0)} 20%{transform:translateX(-4px)}
    40%{transform:translateX(4px)} 60%{transform:translateX(-3px)} 80%{transform:translateX(3px)}
}
.error-banner { background:#fef2f2; border:1px solid #fecaca; border-radius:10px; padding:14px 16px; margin-bottom:20px; }
.error-banner-title { color:#dc2626; font-weight:600; font-size:14px; display:flex; align-items:center; gap:6px; }
.error-list { margin:8px 0 0; padding-left:18px; }
.error-list li { font-size:13px; color:#b91c1c; margin-bottom:2px; }
.read-field { background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:8px; padding:9px 12px; font-size:14px; color:#475569; }
.log-row-success { background:#f0fdf4; }
.log-row-failed  { background:#fef2f2; }
.log-row-locked  { background:#fff7ed; }
.log-row-ip      { background:#f0f9ff; }
.status-pill { display:inline-flex; align-items:center; gap:4px; padding:2px 9px; border-radius:12px; font-size:11px; font-weight:600; }
.pill-success  { background:#dcfce7; color:#15803d; }
.pill-failed   { background:#fee2e2; color:#dc2626; }
.pill-locked   { background:#ffedd5; color:#c2410c; }
.pill-ip       { background:#dbeafe; color:#1d4ed8; }
.pill-default  { background:#f1f5f9; color:#64748b; }
.activity-icon { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:14px; flex-shrink:0; }
</style>

<div class="row g-4">

    {{-- ── LEFT: Identity card ── --}}
    <div class="col-lg-4">

        {{-- Identity --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-4 text-center">
                @if($staff->photo)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($staff->photo) }}"
                         alt="{{ $staff->name }}"
                         class="rounded-circle mx-auto mb-3 d-block"
                         style="width:72px;height:72px;object-fit:cover;border:3px solid #bae6fd;box-shadow:0 4px 16px rgba(14,165,233,.2);">
                @else
                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 text-white fw-bold"
                         style="width:72px;height:72px;font-size:28px;background:linear-gradient(135deg,#0ea5e9,#38bdf8);box-shadow:0 4px 16px rgba(14,165,233,.3);">
                        {{ strtoupper(substr($staff->name, 0, 1)) }}
                    </div>
                @endif
                <h5 class="fw-bold mb-1" style="color:#0c4a6e;">{{ $staff->name }}</h5>
                <div class="text-muted small mb-3">{{ $staff->employee_id }}</div>

                <div class="d-flex justify-content-center gap-2 flex-wrap mb-3">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                        {{ \App\Models\LibraryStaff::DESIGNATION_LABELS[$staff->designation] ?? $staff->designation }}
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                        <i class="bi bi-clock me-1"></i>{{ \App\Models\LibraryStaff::SHIFT_LABELS[$staff->shift] ?? $staff->shift }}
                    </span>
                    @if($staff->isDualRole())
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                        <i class="bi bi-layers me-1"></i>Dual Role
                    </span>
                    @endif
                </div>

                <div class="text-start" style="font-size:13px;">
                    <div class="d-flex gap-2 py-2 border-bottom">
                        <i class="bi bi-envelope text-muted" style="width:16px;"></i>
                        <span class="text-muted">{{ $staff->email }}</span>
                    </div>
                    <div class="d-flex gap-2 py-2 border-bottom">
                        <i class="bi bi-telephone text-muted" style="width:16px;"></i>
                        <span>{{ $staff->phone }}</span>
                    </div>
                    @if($staff->assigned_section)
                    <div class="d-flex gap-2 py-2 border-bottom">
                        <i class="bi bi-bookmark text-muted" style="width:16px;"></i>
                        <span>{{ $staff->assigned_section }}</span>
                    </div>
                    @endif
                    @if($staff->joining_date)
                    <div class="d-flex gap-2 py-2">
                        <i class="bi bi-calendar2-check text-muted" style="width:16px;"></i>
                        <span>Joined {{ $staff->joining_date->format('d M Y') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Security info --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-shield-lock me-2 text-primary"></i>Session Security</h6>
            </div>
            <div class="card-body p-3" style="font-size:13px;">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Last Login</span>
                    <span class="fw-semibold">
                        {{ $staff->last_login_at ? $staff->last_login_at->diffForHumans() : 'Never' }}
                    </span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Last Login IP</span>
                    <code class="small">{{ $staff->last_login_ip ?? '—' }}</code>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Account Status</span>
                    @if($staff->isLocked())
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Locked</span>
                    @elseif($staff->status)
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>
                    @endif
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Session Timeout</span>
                    <span>8 hours inactivity</span>
                </div>
            </div>
        </div>

    </div>

    {{-- ── RIGHT: Edit + History ── --}}
    <div class="col-lg-8">

        {{-- Global error banner --}}
        @if($errors->any())
        <div class="error-banner">
            <div class="error-banner-title">
                <i class="bi bi-exclamation-octagon-fill"></i>
                Please fix the errors below before saving.
            </div>
            <ul class="error-list mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Editable fields --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Profile</h6>
                <small class="text-muted">Email, designation, and shift are managed by the administrator.</small>
            </div>
            <div class="card-body p-3">
                <form method="POST" action="{{ route('library_staff.profile.update') }}" novalidate>
                    @csrf @method('PUT')
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name"
                                   value="{{ old('name', $staff->name) }}"
                                   class="form-control form-control-sm @error('name') is-invalid @enderror">
                            @error('name')
                                <div class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Mobile Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone"
                                   value="{{ old('phone', $staff->phone) }}"
                                   class="form-control form-control-sm @error('phone') is-invalid @enderror">
                            @error('phone')
                                <div class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Address</label>
                            <textarea name="address" rows="2"
                                      class="form-control form-control-sm @error('address') is-invalid @enderror"
                                      placeholder="Your address">{{ old('address', $staff->address) }}</textarea>
                            @error('address')
                                <div class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Read-only fields --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Email (read-only)</label>
                            <div class="read-field">{{ $staff->email }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Designation (read-only)</label>
                            <div class="read-field">
                                {{ \App\Models\LibraryStaff::DESIGNATION_LABELS[$staff->designation] ?? $staff->designation }}
                            </div>
                        </div>

                    </div>
                    <div class="mt-3 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-check-lg me-1"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Login history --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Login Attempts</h6>
                <a href="{{ route('library_staff.activity') }}" class="btn btn-outline-secondary btn-sm" style="font-size:12px;">
                    View All Activity <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            @if($loginHistory->isEmpty())
            <div class="card-body text-center py-4">
                <p class="text-muted small mb-0">No login history found.</p>
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="font-size:12px;">Status</th>
                            <th style="font-size:12px;">IP Address</th>
                            <th style="font-size:12px;">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($loginHistory as $log)
                        <tr class="log-row-{{ match($log->status) { 'success' => 'success', 'failed_otp' => 'failed', 'locked' => 'locked', 'ip_change' => 'ip', default => '' } }}">
                            <td>
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
                                <span class="status-pill {{ $pillClass }}">
                                    <i class="bi bi-{{ $pillIcon }}"></i> {{ $pillLabel }}
                                </span>
                            </td>
                            <td><code class="small">{{ $log->ip_address ?? '—' }}</code></td>
                            <td class="text-muted small">{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- Recent activity --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-activity me-2 text-primary"></i>Recent Activity</h6>
            </div>
            @if($activityHistory->isEmpty())
            <div class="card-body text-center py-4">
                <p class="text-muted small mb-0">No activity recorded yet.</p>
            </div>
            @else
            <div class="card-body p-0">
                @foreach($activityHistory as $log)
                @php
                    $iconBg = match($log->action) {
                        'login'          => 'background:#f0fdf4;color:#16a34a;',
                        'logout'         => 'background:#f8fafc;color:#64748b;',
                        'profile_update' => 'background:#f0f9ff;color:#0ea5e9;',
                        'ip_change'      => 'background:#fffbeb;color:#d97706;',
                        'session_kicked' => 'background:#fef2f2;color:#dc2626;',
                        default          => 'background:#f1f5f9;color:#64748b;',
                    };
                    $icon = match($log->action) {
                        'login'          => 'box-arrow-in-right',
                        'logout'         => 'box-arrow-right',
                        'profile_update' => 'pencil-square',
                        'ip_change'      => 'geo-alt',
                        'session_kicked' => 'shield-exclamation',
                        'otp_sent'       => 'envelope-check',
                        default          => 'circle',
                    };
                @endphp
                <div class="d-flex align-items-start gap-3 px-3 py-3 border-bottom">
                    <div class="activity-icon" style="{{ $iconBg }}">
                        <i class="bi bi-{{ $icon }}"></i>
                    </div>
                    <div class="flex-grow-1" style="min-width:0;">
                        <div class="fw-semibold small">
                            {{ \App\Models\LibraryStaffActivityLog::ACTION_LABELS[$log->action] ?? ucwords(str_replace('_', ' ', $log->action)) }}
                        </div>
                        @if($log->details)
                        <div class="text-muted" style="font-size:12px;">{{ $log->details }}</div>
                        @endif
                        <div class="text-muted" style="font-size:11px;">
                            <code class="small">{{ $log->ip_address }}</code>
                            &nbsp;·&nbsp;
                            {{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>
</div>
@endsection
