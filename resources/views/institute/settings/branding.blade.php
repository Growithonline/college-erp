@extends('institute.layout')
@section('title', 'Branding')
@section('breadcrumb', 'Settings / Branding')

@php
    $admissionUrl = url('/apply/' . $institute->short_name);
    $admissionEmbedCode = '<iframe src="' . $admissionUrl . '" style="width:100%;max-width:560px;height:900px;border:none;" title="Admission Enquiry"></iframe>';
    $feeBalanceUrl = url('/fee-balance/' . $institute->short_name);
    $feeBalanceEmbedCode = '<iframe src="' . $feeBalanceUrl . '" style="width:100%;max-width:560px;height:900px;border:none;" title="Know Your Fee Balance"></iframe>';
@endphp

@section('content')

<div class="mb-4">
    <h4 class="mb-0 fw-bold">Branding</h4>
    <small class="text-muted">Set the brand color and manage the public pages students can access without logging in</small>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <span class="fw-semibold"><i class="bi bi-palette text-primary me-2"></i>Brand Color</span>
            </div>
            <div class="card-body p-4">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @error('primary_color')
                    <div class="alert alert-danger">{{ $message }}</div>
                @enderror

                <form method="POST" action="{{ route('master.settings.branding.save') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Color</label>
                        <input type="color" name="primary_color" class="form-control form-control-color"
                               value="{{ $institute->primary_color ?? '#2563EB' }}">
                        <small class="text-muted d-block mt-1">Used for buttons and highlights on your public enquiry and application forms. Your logo is managed by the platform team — contact support to change it.</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check2 me-1"></i> Save
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <span class="fw-semibold"><i class="bi bi-link-45deg text-primary me-2"></i>Public Links</span>
            </div>
            <div class="card-body p-4">
                <div class="mb-4">
                    <label class="form-label fw-semibold small">Admission Enquiry</label>
                    <div class="input-group input-group-sm mb-2">
                        <input type="text" class="form-control" readonly
                               value="{{ $admissionUrl }}" id="admissionUrlInput">
                        <button class="btn btn-outline-secondary copy-url-btn" type="button" data-target="admissionUrlInput">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                    <div class="input-group input-group-sm">
                        <textarea class="form-control font-monospace" readonly rows="2" style="font-size:11px;" id="admissionEmbedInput">{{ $admissionEmbedCode }}</textarea>
                        <button class="btn btn-outline-secondary copy-url-btn" type="button" data-target="admissionEmbedInput">
                            <i class="bi bi-clipboard"></i> Copy Embed
                        </button>
                    </div>
                    <small class="text-muted d-block mt-1">Share the link or paste the embed code into your website — students can enquire about admission.</small>
                </div>

                <hr>

                <div class="mb-2 d-flex align-items-center justify-content-between">
                    <label class="form-label fw-semibold small mb-0">Know Your Fee Balance</label>
                    <form method="POST" action="{{ route('master.settings.branding.fee-balance') }}" class="mb-0">
                        @csrf
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="fee_balance_enabled" value="1"
                                   id="feeBalanceToggle" {{ $institute->fee_balance_enabled ? 'checked' : '' }}
                                   onchange="this.form.submit()" style="cursor:pointer;">
                        </div>
                    </form>
                </div>
                <div class="input-group input-group-sm mb-2">
                    <input type="text" class="form-control" readonly
                           value="{{ $feeBalanceUrl }}" id="feeBalanceUrlInput">
                    <button class="btn btn-outline-secondary copy-url-btn" type="button" data-target="feeBalanceUrlInput">
                        <i class="bi bi-clipboard"></i> Copy
                    </button>
                </div>
                <div class="input-group input-group-sm">
                    <textarea class="form-control font-monospace" readonly rows="2" style="font-size:11px;" id="feeBalanceEmbedInput">{{ $feeBalanceEmbedCode }}</textarea>
                    <button class="btn btn-outline-secondary copy-url-btn" type="button" data-target="feeBalanceEmbedInput">
                        <i class="bi bi-clipboard"></i> Copy Embed
                    </button>
                </div>
                <small class="text-muted d-block mt-1">
                    @if($institute->fee_balance_enabled)
                        <span class="text-success"><i class="bi bi-check-circle me-1"></i>Live</span> — students can check their fee balance here.
                    @else
                        <span class="text-muted"><i class="bi bi-slash-circle me-1"></i>Off</span> — this link currently shows a 404. Turn it on above to make it live.
                    @endif
                </small>

                <hr>

                <div class="mb-2 d-flex align-items-center justify-content-between">
                    <label class="form-label fw-semibold small mb-0">Require OTP Verification</label>
                    <form method="POST" action="{{ route('master.settings.branding.fee-balance-otp') }}" class="mb-0">
                        @csrf
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="require_otp" value="1"
                                   id="requireOtpToggle" {{ !$institute->fee_balance_otp_bypass ? 'checked' : '' }}
                                   onchange="if (!this.checked && !confirm('Turning this off means students will see their fee balance right after their details match — without an OTP step. This is less secure. Continue?')) { this.checked = true; return; } this.form.submit();"
                                   style="cursor:pointer;">
                        </div>
                    </form>
                </div>
                <small class="text-muted d-block">
                    @if($institute->fee_balance_otp_bypass)
                        <span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Off</span> — students see their balance immediately once details match, no OTP sent.
                    @else
                        <span class="text-success"><i class="bi bi-check-circle me-1"></i>On</span> — an OTP is sent to the mobile on file before any balance is shown. Needs your SMS OTP provider configured, or students will see a "Failed to send OTP" error — if SMS isn't set up yet, turn this off temporarily.
                    @endif
                </small>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.copy-url-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.getElementById(btn.dataset.target);
            navigator.clipboard.writeText(input.value).then(function () {
                const original = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check2"></i> Copied';
                setTimeout(function () { btn.innerHTML = original; }, 1500);
            });
        });
    });
</script>

@endsection
