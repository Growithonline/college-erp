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
        <form id="form-resend" method="POST" action="{{ route('super_admin.institutes.resend-credentials', $institute->id) }}">
            @csrf
            <button type="button" class="btn btn-sm btn-outline-primary"
                onclick="openConfirm({
                    formId:  'form-resend',
                    icon:    '📧',
                    iconBg:  '#eff6ff',
                    iconColor: '#3b82f6',
                    title:   'Resend Credentials?',
                    message: 'A new password will be generated and sent to <strong>{{ $institute->owner_email }}</strong>. The current password will be reset.',
                    confirmText: 'Yes, Resend',
                    confirmClass: 'btn-primary'
                })">
                <i class="bi bi-envelope-arrow-up me-1"></i> Resend Credentials
            </button>
        </form>
        <form id="form-toggle" method="POST" action="{{ route('super_admin.institutes.toggle', $institute->id) }}">
            @csrf @method('PATCH')
            <button type="button"
                class="btn btn-sm btn-outline-{{ $institute->status === 'active' ? 'danger' : 'success' }}"
                onclick="openConfirm({
                    formId:  'form-toggle',
                    icon:    '{{ $institute->status === 'active' ? '⛔' : '✅' }}',
                    iconBg:  '{{ $institute->status === 'active' ? '#fef2f2' : '#f0fdf4' }}',
                    iconColor: '{{ $institute->status === 'active' ? '#ef4444' : '#22c55e' }}',
                    title:   '{{ $institute->status === 'active' ? 'Deactivate Institute?' : 'Activate Institute?' }}',
                    message: '{{ $institute->status === 'active' ? 'This will disable access for all users of this institute.' : 'This will restore access for all users of this institute.' }}',
                    confirmText: '{{ $institute->status === 'active' ? 'Yes, Deactivate' : 'Yes, Activate' }}',
                    confirmClass: '{{ $institute->status === 'active' ? 'btn-danger' : 'btn-success' }}'
                })">
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

{{-- Email SMTP Status --}}
<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pb-0 pt-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0"><i class="bi bi-envelope-gear text-primary me-2"></i>Email (SMTP) Configuration</h6>
                @if($institute->hasSmtp())
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                        <i class="bi bi-check-circle-fill me-1"></i> Own SMTP — Verified
                    </span>
                @elseif(filled($institute->smtp_host))
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Saved — Not Verified
                    </span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary px-3 py-2">
                        <i class="bi bi-envelope me-1"></i> Using Platform SMTP
                    </span>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-3">

                    {{-- Status Summary --}}
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 h-100 {{ $institute->hasSmtp() ? 'bg-success-subtle' : (filled($institute->smtp_host) ? 'bg-warning-subtle' : 'bg-light') }}">
                            <p class="small fw-bold mb-2 {{ $institute->hasSmtp() ? 'text-success' : (filled($institute->smtp_host) ? 'text-warning' : 'text-muted') }}">
                                Email Source
                            </p>
                            @if($institute->hasSmtp())
                                <p class="mb-1 small"><strong>From:</strong> {{ $institute->smtp_from_name }}</p>
                                <p class="mb-1 small"><strong>Email:</strong> {{ $institute->smtp_from_email }}</p>
                                <p class="mb-0 small"><strong>Host:</strong> {{ $institute->smtp_host }}:{{ $institute->smtp_port }}</p>
                            @elseif(filled($institute->smtp_host))
                                <p class="mb-1 small"><strong>Host:</strong> {{ $institute->smtp_host }}:{{ $institute->smtp_port }}</p>
                                <p class="mb-0 small text-warning fw-semibold">Connection not verified yet.</p>
                            @else
                                <p class="mb-0 small text-muted">This institute has not configured its own SMTP. All emails are sent via platform mail server.</p>
                            @endif
                        </div>
                    </div>

                    {{-- What goes where --}}
                    <div class="col-md-8">
                        <p class="small fw-semibold text-muted mb-2">Email routing for this institute:</p>
                        <div class="row g-2">
                            @php $ownSmtp = $institute->hasSmtp(); @endphp
                            @foreach([
                                ['icon'=>'bi-person-badge','label'=>'Staff credentials & OTP',    'own'=>true],
                                ['icon'=>'bi-mortarboard','label'=>'Student credentials & OTP',   'own'=>true],
                                ['icon'=>'bi-buildings',  'label'=>'Center credentials & OTP',    'own'=>true],
                                ['icon'=>'bi-people',     'label'=>'Channel partner credentials', 'own'=>true],
                                ['icon'=>'bi-megaphone',  'label'=>'Notice emails',               'own'=>true],
                                ['icon'=>'bi-envelope-at','label'=>'Institute onboarding',         'own'=>false],
                                ['icon'=>'bi-bell',       'label'=>'Subscription notices',        'own'=>false],
                            ] as $row)
                            <div class="col-md-6">
                                <div class="d-flex align-items-center gap-2 p-2 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;">
                                    <i class="bi {{ $row['icon'] }} text-muted" style="font-size:13px;flex-shrink:0;"></i>
                                    <span class="small flex-grow-1" style="font-size:12px;">{{ $row['label'] }}</span>
                                    @if($row['own'] && $ownSmtp)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:9px;white-space:nowrap;">Own SMTP</span>
                                    @elseif($row['own'])
                                        <span class="badge bg-secondary-subtle text-secondary" style="font-size:9px;white-space:nowrap;">Platform</span>
                                    @else
                                        <span class="badge bg-primary-subtle text-primary" style="font-size:9px;white-space:nowrap;">Platform</span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                </div>
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
                <form id="form-reset-pwd" method="POST" action="{{ route('super_admin.institutes.reset-password', $institute->id) }}">
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
                            <button type="button" class="btn btn-danger btn-sm"
                                onclick="openConfirm({
                                    formId:  'form-reset-pwd',
                                    icon:    '🔑',
                                    iconBg:  '#fef2f2',
                                    iconColor: '#ef4444',
                                    title:   'Reset Admin Password?',
                                    message: 'This will update the admin password for this institute. Make sure to notify them if you are not sending an email.',
                                    confirmText: 'Yes, Reset',
                                    confirmClass: 'btn-danger'
                                })">
                                <i class="bi bi-key me-1"></i> Reset Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Custom Confirm Modal --}}
<div id="confirmModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;">
    <div id="confirmBackdrop"
         style="position:absolute;inset:0;background:rgba(15,23,42,0.45);backdrop-filter:blur(3px);transition:opacity 0.2s;"
         onclick="closeConfirm()"></div>
    <div id="confirmBox"
         style="position:relative;background:#fff;border-radius:18px;padding:32px 28px 24px;max-width:420px;width:90%;box-shadow:0 24px 64px rgba(0,0,0,0.18);transform:scale(0.92) translateY(12px);transition:transform 0.22s ease,opacity 0.22s ease;opacity:0;">
        <div style="text-align:center;margin-bottom:20px;">
            <div id="confirmIconWrap" style="width:60px;height:60px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:26px;margin-bottom:14px;"></div>
            <h5 id="confirmTitle" style="margin:0 0 8px;font-size:18px;font-weight:700;color:#0f172a;"></h5>
            <p id="confirmMessage" style="margin:0;font-size:14px;color:#64748b;line-height:1.6;"></p>
        </div>
        <div style="display:flex;gap:10px;justify-content:center;">
            <button onclick="closeConfirm()"
                    style="flex:1;padding:10px 0;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;color:#475569;font-size:14px;font-weight:600;cursor:pointer;">
                Cancel
            </button>
            <button id="confirmBtn"
                    style="flex:1;padding:10px 0;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;color:#fff;">
            </button>
        </div>
    </div>
</div>

<script>
var _confirmFormId = null;

function openConfirm(opts) {
    _confirmFormId = opts.formId;
    var wrap = document.getElementById('confirmIconWrap');
    wrap.style.background   = opts.iconBg;
    wrap.style.color         = opts.iconColor;
    wrap.textContent         = opts.icon;
    document.getElementById('confirmTitle').textContent   = opts.title;
    document.getElementById('confirmMessage').innerHTML   = opts.message;
    var btn = document.getElementById('confirmBtn');
    btn.textContent = opts.confirmText;
    btn.className   = '';
    btn.style.background = opts.confirmClass === 'btn-danger'  ? '#ef4444'
                         : opts.confirmClass === 'btn-success' ? '#22c55e'
                         : '#3b82f6';
    btn.onclick = function () { submitConfirm(); };

    var modal = document.getElementById('confirmModal');
    modal.style.display = 'flex';
    requestAnimationFrame(function () {
        requestAnimationFrame(function () {
            document.getElementById('confirmBox').style.transform = 'scale(1) translateY(0)';
            document.getElementById('confirmBox').style.opacity   = '1';
        });
    });
}

function closeConfirm() {
    var box = document.getElementById('confirmBox');
    box.style.transform = 'scale(0.92) translateY(12px)';
    box.style.opacity   = '0';
    setTimeout(function () {
        document.getElementById('confirmModal').style.display = 'none';
        _confirmFormId = null;
    }, 220);
}

function submitConfirm() {
    if (_confirmFormId) {
        document.getElementById(_confirmFormId).submit();
    }
    closeConfirm();
}

function togglePwd(id, btn) {
    var input = document.getElementById(id);
    var isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    btn.innerHTML = isText ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
}
</script>

@endsection
