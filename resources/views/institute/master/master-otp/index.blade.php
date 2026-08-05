@extends('institute.layout')
@section('title', 'Master OTP')
@section('breadcrumb', 'Master / Security / Master OTP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Master OTP</h4>
        <small class="text-muted">Backup login code for your own admin account — use it only if your email/SMS OTP doesn't arrive</small>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        @if($masterOtp && $masterOtp->isValidNow())
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                    <i class="bi bi-check-circle me-1"></i> Active this month
                </span>
            </div>

            <div class="d-flex align-items-center gap-2 mb-3">
                <code id="masterOtpValue" class="bg-light px-3 py-2 rounded fs-5" data-revealed="0">&bull;&bull;&bull;&bull;&bull;&bull;</code>
                <button type="button" id="masterOtpToggle" class="btn btn-outline-secondary btn-sm" title="Show / hide">
                    <i class="bi bi-eye"></i>
                </button>
            </div>

            <div class="small text-muted mb-3">
                Last generated {{ $masterOtp->generated_at->format('d M Y, h:i A') }}
                @if($masterOtp->generatedBy)
                    by {{ $masterOtp->generatedBy->name }}
                @endif
                — valid only for {{ \Carbon\Carbon::createFromFormat('Y-m', $masterOtp->valid_month)->format('F Y') }}.
            </div>
        @else
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                    <i class="bi bi-dash-circle me-1"></i>
                    {{ $masterOtp ? "Expired — this month's code hasn't been generated yet" : 'Not set up' }}
                </span>
            </div>
        @endif

        <form method="POST" action="{{ route('master.master-otp.generate') }}"
              onsubmit="return confirm('{{ $masterOtp ? 'This replaces your current Master OTP — the old one will stop working immediately. Continue?' : 'Generate a new Master OTP?' }}');">
            @csrf
            <button class="btn btn-primary">
                <i class="bi bi-arrow-repeat me-1"></i> {{ $masterOtp ? 'Reset / Generate New Master OTP' : 'Generate Master OTP' }}
            </button>
        </form>
    </div>
</div>

<div class="alert alert-info border-info small mb-0">
    <i class="bi bi-info-circle me-1"></i>
    The Master OTP automatically stops working at the start of every month — generate a fresh one whenever
    you need it. It only works for <strong>your own</strong> admin login, not for Staff, Center or Channel
    Partner accounts. Every reveal is recorded in the audit log.
</div>

@push('scripts')
<script>
(function () {
    var toggleBtn = document.getElementById('masterOtpToggle');
    if (!toggleBtn) return;

    var valueEl = document.getElementById('masterOtpValue');
    var revealUrl = '{{ route('master.master-otp.reveal') }}';
    var actualOtp = null;

    toggleBtn.addEventListener('click', async function () {
        var revealed = valueEl.dataset.revealed === '1';

        if (revealed) {
            valueEl.textContent = '••••••';
            valueEl.dataset.revealed = '0';
            toggleBtn.innerHTML = '<i class="bi bi-eye"></i>';
            return;
        }

        if (actualOtp) {
            valueEl.textContent = actualOtp;
            valueEl.dataset.revealed = '1';
            toggleBtn.innerHTML = '<i class="bi bi-eye-slash"></i>';
            return;
        }

        toggleBtn.disabled = true;
        try {
            var res = await fetch(revealUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
            if (!res.ok) {
                window.showToast?.('Could not fetch Master OTP. Generate a new one.', 'danger');
                return;
            }
            var data = await res.json();
            actualOtp = data.otp;
            valueEl.textContent = actualOtp;
            valueEl.dataset.revealed = '1';
            toggleBtn.innerHTML = '<i class="bi bi-eye-slash"></i>';
        } catch (e) {
            window.showToast?.('Network error. Please try again.', 'danger');
        } finally {
            toggleBtn.disabled = false;
        }
    });
})();
</script>
@endpush
@endsection
