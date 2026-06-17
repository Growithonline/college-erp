@extends('institute.layout')
@section('title', 'Email Settings')
@section('breadcrumb', 'Settings / Email (SMTP)')

@section('content')

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Email (SMTP) Settings</h4>
        <small class="text-muted">Configure your own mail server — staff, student & partner emails will be sent from your email ID</small>
    </div>
    @if($institute->hasSmtp())
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2" style="font-size:13px;">
            <i class="bi bi-check-circle-fill me-1"></i> Connected & Verified
        </span>
    @elseif(filled($institute->smtp_host))
        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2" style="font-size:13px;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> Saved — Not Verified
        </span>
    @else
        <span class="badge bg-secondary-subtle text-secondary border px-3 py-2" style="font-size:13px;">
            <i class="bi bi-x-circle me-1"></i> Not Configured
        </span>
    @endif
</div>

{{-- Info banner --}}
<div class="alert border-0 mb-4" style="background:#eff6ff;border-left:4px solid #3b82f6 !important;border-radius:10px;">
    <div class="d-flex gap-2">
        <i class="bi bi-info-circle-fill text-primary mt-1"></i>
        <div style="font-size:13px;color:#1e40af;line-height:1.6;">
            <strong>How it works:</strong> Once your SMTP is verified, all emails sent <em>within your institute</em>
            (staff credentials, student welcome, OTPs, notices) will go from your own email ID.
            Super admin emails (your onboarding, subscription notices) will still come from the platform.
        </div>
    </div>
</div>

<div class="row g-4">

    {{-- Left: Configuration Form --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <span class="fw-semibold"><i class="bi bi-gear text-primary me-2"></i>SMTP Configuration</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('master.settings.email.save') }}">
                    @csrf

                    {{-- Host + Port --}}
                    <div class="row g-3 mb-3">
                        <div class="col-8">
                            <label class="form-label fw-semibold small">SMTP Host <span class="text-danger">*</span></label>
                            <input type="text" name="smtp_host" class="form-control @error('smtp_host') is-invalid @enderror"
                                   value="{{ old('smtp_host', $institute->smtp_host) }}"
                                   placeholder="smtp.gmail.com">
                            @error('smtp_host')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold small">Port <span class="text-danger">*</span></label>
                            <input type="number" name="smtp_port" class="form-control @error('smtp_port') is-invalid @enderror"
                                   value="{{ old('smtp_port', $institute->smtp_port ?? 587) }}"
                                   placeholder="587">
                            @error('smtp_port')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Encryption --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Encryption <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            @foreach(['tls' => 'TLS (Recommended)', 'ssl' => 'SSL', 'none' => 'None'] as $val => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="smtp_encryption"
                                       id="enc_{{ $val }}" value="{{ $val }}"
                                       {{ old('smtp_encryption', $institute->smtp_encryption ?? 'tls') === $val ? 'checked' : '' }}>
                                <label class="form-check-label small" for="enc_{{ $val }}">{{ $label }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Username --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">SMTP Username (Email) <span class="text-danger">*</span></label>
                        <input type="email" name="smtp_username" class="form-control @error('smtp_username') is-invalid @enderror"
                               value="{{ old('smtp_username', $institute->smtp_username) }}"
                               placeholder="yourname@gmail.com">
                        @error('smtp_username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Password --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">
                            SMTP Password / App Password
                            @if(filled($institute->smtp_password))
                                <span class="text-muted fw-normal">(leave blank to keep existing)</span>
                            @else
                                <span class="text-danger">*</span>
                            @endif
                        </label>
                        <div class="input-group">
                            <input type="password" name="smtp_password" id="smtpPassword"
                                   class="form-control @error('smtp_password') is-invalid @enderror"
                                   placeholder="{{ filled($institute->smtp_password) ? '••••••••••••' : 'Enter SMTP password or app password' }}">
                            <button type="button" class="btn btn-outline-secondary"
                                    onclick="var i=document.getElementById('smtpPassword');i.type=i.type==='password'?'text':'password';">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('smtp_password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        <div class="form-text">For Gmail, use an <strong>App Password</strong> (not your Gmail login password).</div>
                    </div>

                    <hr class="my-3">

                    {{-- From Name + From Email --}}
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">From Name <span class="text-danger">*</span></label>
                            <input type="text" name="smtp_from_name" class="form-control @error('smtp_from_name') is-invalid @enderror"
                                   value="{{ old('smtp_from_name', $institute->smtp_from_name ?? $institute->name) }}"
                                   placeholder="{{ $institute->name }}">
                            @error('smtp_from_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">From Email <span class="text-danger">*</span></label>
                            <input type="email" name="smtp_from_email" class="form-control @error('smtp_from_email') is-invalid @enderror"
                                   value="{{ old('smtp_from_email', $institute->smtp_from_email ?? $institute->email) }}"
                                   placeholder="{{ $institute->email }}">
                            @error('smtp_from_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-floppy me-1"></i> Save Settings
                        </button>
                        @if(filled($institute->smtp_host))
                        <button type="button" class="btn btn-outline-danger"
                                onclick="document.getElementById('form-disconnect').submit()">
                            <i class="bi bi-x-circle me-1"></i> Remove Configuration
                        </button>
                        @endif
                    </div>
                </form>

                <form id="form-disconnect" method="POST" action="{{ route('master.settings.email.disconnect') }}" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </div>

    {{-- Right: Status + Test + Help --}}
    <div class="col-lg-5 d-flex flex-column gap-3">

        {{-- Test Connection Card --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <span class="fw-semibold"><i class="bi bi-send text-success me-2"></i>Test Connection</span>
            </div>
            <div class="card-body p-4">
                @if(filled($institute->smtp_host))
                    <p class="small text-muted mb-3">
                        Send a test email to <strong>{{ Auth::user()->email }}</strong> to confirm your SMTP is working correctly.
                    </p>
                    <form method="POST" action="{{ route('master.settings.email.test') }}">
                        @csrf
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-envelope-check me-1"></i> Send Test Email
                        </button>
                    </form>
                @else
                    <p class="small text-muted mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Save your SMTP settings first, then test the connection here.
                    </p>
                @endif
            </div>
        </div>

        {{-- Current Status Card --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <span class="fw-semibold"><i class="bi bi-activity text-info me-2"></i>Current Status</span>
            </div>
            <div class="card-body p-4">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted small fw-semibold" style="width:45%">Status</td>
                        <td>
                            @if($institute->hasSmtp())
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Verified</span>
                            @elseif(filled($institute->smtp_host))
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Not Verified</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Not Configured</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted small fw-semibold">Host</td>
                        <td class="small">{{ $institute->smtp_host ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small fw-semibold">Port / Enc.</td>
                        <td class="small">
                            @if(filled($institute->smtp_host))
                                {{ $institute->smtp_port }} / {{ strtoupper($institute->smtp_encryption) }}
                            @else —
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted small fw-semibold">From</td>
                        <td class="small">{{ $institute->smtp_from_email ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small fw-semibold">Email Source</td>
                        <td>
                            @if($institute->hasSmtp())
                                <span class="small text-success fw-semibold">Your SMTP</span>
                            @else
                                <span class="small text-muted">Platform SMTP</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Quick Help Card --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <span class="fw-semibold"><i class="bi bi-question-circle text-warning me-2"></i>Quick Help</span>
            </div>
            <div class="card-body p-4">
                <p class="small fw-semibold text-dark mb-2">Common SMTP settings:</p>
                <div class="d-flex flex-column gap-2" style="font-size:12px;">
                    <div class="p-2 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;">
                        <strong>Gmail</strong><br>
                        Host: <code>smtp.gmail.com</code> · Port: <code>587</code> · TLS<br>
                        <span class="text-muted">Use App Password, not login password.</span>
                    </div>
                    <div class="p-2 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;">
                        <strong>Zoho Mail</strong><br>
                        Host: <code>smtp.zoho.in</code> · Port: <code>587</code> · TLS
                    </div>
                    <div class="p-2 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;">
                        <strong>Outlook / Office 365</strong><br>
                        Host: <code>smtp.office365.com</code> · Port: <code>587</code> · TLS
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection
