@extends('institute.layout')
@section('title', 'Fee Balance')
@section('breadcrumb', 'Master / Form Builder / Fee Balance')

@php
    $feeBalanceUrl = url('/fee-balance/' . $institute->short_name);
    $feeBalanceEmbedCode = '<iframe src="' . $feeBalanceUrl . '" style="width:100%;max-width:560px;height:900px;border:none;" title="Know Your Fee Balance"></iframe>';
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-search-heart me-2 text-primary"></i>
            Know Your Fee Balance
        </h4>
        <small class="text-muted">Public page where students check their own fee balance (OTP verified) — no login required</small>
    </div>
    <a href="{{ route('master.forms.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <span class="fw-semibold"><i class="bi bi-link-45deg text-primary me-2"></i>Public Link</span>
                <form method="POST" action="{{ route('master.settings.branding.fee-balance') }}" class="mb-0">
                    @csrf
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="fee_balance_enabled" value="1"
                               id="feeBalanceToggle" {{ $institute->fee_balance_enabled ? 'checked' : '' }}
                               onchange="this.form.submit()" style="cursor:pointer;">
                    </div>
                </form>
            </div>
            <div class="card-body p-4">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <label class="form-label small fw-semibold text-muted">Link</label>
                <div class="input-group input-group-sm mb-3">
                    <input type="text" class="form-control" readonly
                           value="{{ $feeBalanceUrl }}" id="feeBalanceUrlInput">
                    <button class="btn btn-outline-secondary copy-url-btn" type="button" data-target="feeBalanceUrlInput">
                        <i class="bi bi-clipboard"></i> Copy
                    </button>
                </div>

                <label class="form-label small fw-semibold text-muted">Embed Code <span class="fw-normal">(paste this into your website)</span></label>
                <div class="input-group input-group-sm">
                    <textarea class="form-control font-monospace" readonly rows="2" style="font-size:11px;" id="feeBalanceEmbedInput">{{ $feeBalanceEmbedCode }}</textarea>
                    <button class="btn btn-outline-secondary copy-url-btn" type="button" data-target="feeBalanceEmbedInput">
                        <i class="bi bi-clipboard"></i> Copy
                    </button>
                </div>

                <small class="text-muted d-block mt-2">
                    @if($institute->fee_balance_enabled)
                        <span class="text-success"><i class="bi bi-check-circle me-1"></i>Live</span> — students can check their fee balance here. Make sure your SMS OTP provider is configured, since this page sends an OTP before showing any balance.
                    @else
                        <span class="text-muted"><i class="bi bi-slash-circle me-1"></i>Off</span> — this link currently shows a 404. Turn it on above once your SMS OTP provider is configured.
                    @endif
                </small>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-bottom py-3">
                <span class="fw-semibold"><i class="bi bi-info-circle text-primary me-2"></i>How it works</span>
            </div>
            <div class="card-body p-4">
                <ol class="small text-muted mb-0 ps-3">
                    <li>Student picks Course Type / Course / Stream / Semester</li>
                    <li>Enters one identifier (Student ID / Aadhar / Enrollment No / UIN / Roll No), Date of Birth, and Mobile Number</li>
                    <li>Solves a simple verification question (captcha)</li>
                    <li>An OTP is sent to the mobile number on file — only after all details match exactly</li>
                    <li>Once verified, only the due amount is shown — no other student details</li>
                </ol>
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
