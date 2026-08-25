@extends('institute.layout')
@section('title', 'Branding')
@section('breadcrumb', 'Settings / Branding')

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
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" readonly
                               value="{{ url('/apply/' . $institute->short_name) }}" id="admissionUrlInput">
                        <button class="btn btn-outline-secondary copy-url-btn" type="button" data-target="admissionUrlInput">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                    <small class="text-muted d-block mt-1">Share or embed this on your website — students can enquire about admission.</small>
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
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" readonly
                           value="{{ url('/fee-balance/' . $institute->short_name) }}" id="feeBalanceUrlInput">
                    <button class="btn btn-outline-secondary copy-url-btn" type="button" data-target="feeBalanceUrlInput">
                        <i class="bi bi-clipboard"></i> Copy
                    </button>
                </div>
                <small class="text-muted d-block mt-1">
                    @if($institute->fee_balance_enabled)
                        <span class="text-success"><i class="bi bi-check-circle me-1"></i>Live</span> — students can check their fee balance here. Make sure your SMS OTP provider is configured under Settings, since this page sends an OTP before showing any balance.
                    @else
                        <span class="text-muted"><i class="bi bi-slash-circle me-1"></i>Off</span> — this link currently shows a 404. Turn it on above once your SMS OTP provider is configured.
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
