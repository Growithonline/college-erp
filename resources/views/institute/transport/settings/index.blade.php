@extends('institute.layout')
@section('title', 'Transport Settings')
@section('breadcrumb', 'Transport / Settings')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">

        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="bi bi-gear me-2 text-primary"></i>Transport Settings
                </h5>
                <small class="text-muted">Configure transport billing policies for your institute.</small>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('transport.settings.update') }}">
                    @csrf
                    @method('PUT')

                    {{-- Route Transfer Policy --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Route Transfer Policy <span class="text-danger">*</span></label>
                        <small class="d-block text-muted mb-2">What happens when a student transfers from one route to another?</small>

                        <div class="row g-3">
                            @foreach([
                                'full_charge'     => ['title' => 'Full Charge', 'desc' => 'New route ki full fee charge hogi on transfer. (Default)', 'icon' => 'bi-cash-coin', 'color' => 'warning'],
                                'no_charge'       => ['title' => 'No Charge',   'desc' => 'Transfer pe naya charge nahi hoga. Purani fee valid rahegi year bhar.', 'icon' => 'bi-shield-check', 'color' => 'success'],
                                'prorated_charge' => ['title' => 'Prorated Charge', 'desc' => 'Remaining days ke hisab se partial fee charge hogi.', 'icon' => 'bi-percent', 'color' => 'info'],
                            ] as $value => $opt)
                            <div class="col-md-4">
                                <label class="d-block cursor-pointer">
                                    <input type="radio" name="on_route_transfer" value="{{ $value }}"
                                           class="d-none policy-radio"
                                           {{ $setting->on_route_transfer === $value ? 'checked' : '' }}>
                                    <div class="card border-2 h-100 p-3 policy-card {{ $setting->on_route_transfer === $value ? 'border-primary bg-primary bg-opacity-5' : 'border-light' }}"
                                         style="cursor:pointer; transition: all .15s;">
                                        <i class="bi {{ $opt['icon'] }} text-{{ $opt['color'] }} fs-4 mb-2"></i>
                                        <div class="fw-semibold small">{{ $opt['title'] }}</div>
                                        <div class="text-muted" style="font-size:11px;">{{ $opt['desc'] }}</div>
                                    </div>
                                </label>
                            </div>
                            @endforeach
                        </div>
                        @error('on_route_transfer') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <hr>

                    {{-- Prorated Billing --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Prorated Monthly Billing</label>
                        <small class="d-block text-muted mb-2">Monthly routes ke liye — naya student mid-month join kare to kitna charge hoga?</small>

                        <div class="row g-3">
                            @foreach([
                                'disabled'      => ['title' => 'Disabled',        'desc' => 'Hamesha full month ka charge. Join date matter nahi karti.', 'icon' => 'bi-dash-circle'],
                                'after_midmonth'=> ['title' => 'Half After 15th', 'desc' => '15 tarikh ke baad join kare to aadha charge lo.', 'icon' => 'bi-calendar-half'],
                                'daily_basis'   => ['title' => 'Daily Basis',     'desc' => 'Exact days: (remaining days / total days) × fee.', 'icon' => 'bi-calculator'],
                            ] as $value => $opt)
                            <div class="col-md-4">
                                <label class="d-block cursor-pointer">
                                    <input type="radio" name="prorated_billing" value="{{ $value }}"
                                           class="d-none policy-radio"
                                           {{ $setting->prorated_billing === $value ? 'checked' : '' }}>
                                    <div class="card border-2 h-100 p-3 policy-card {{ $setting->prorated_billing === $value ? 'border-primary bg-primary bg-opacity-5' : 'border-light' }}"
                                         style="cursor:pointer; transition: all .15s;">
                                        <i class="bi {{ $opt['icon'] }} text-secondary fs-4 mb-2"></i>
                                        <div class="fw-semibold small">{{ $opt['title'] }}</div>
                                        <div class="text-muted" style="font-size:11px;">{{ $opt['desc'] }}</div>
                                    </div>
                                </label>
                            </div>
                            @endforeach
                        </div>
                        @error('prorated_billing') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <hr>

                    {{-- Yearly Fee Cross Session --}}
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="yearlyFee"
                                   name="yearly_fee_cross_session" value="1"
                                   {{ $setting->yearly_fee_cross_session ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="yearlyFee">
                                Yearly Fee Cross-Session Protection
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">
                            Jab <strong>ON</strong> ho: agar student ne Semester 1 mein yearly transport fee di hai, to Semester 2 mein dobara charge nahi hoga (same academic year ke andar).<br>
                            Jab <strong>OFF</strong> ho: har session change pe dobara charge hoga.
                        </small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i> Save Settings
                        </button>
                        <a href="{{ route('transport.dashboard') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.policy-radio').forEach(radio => {
    radio.addEventListener('change', function () {
        const name = this.name;
        document.querySelectorAll(`input[name="${name}"]`).forEach(r => {
            const card = r.closest('label').querySelector('.policy-card');
            card.classList.remove('border-primary', 'bg-primary', 'bg-opacity-5');
            card.classList.add('border-light');
        });
        const activeCard = this.closest('label').querySelector('.policy-card');
        activeCard.classList.remove('border-light');
        activeCard.classList.add('border-primary', 'bg-primary', 'bg-opacity-5');
    });
});

// Click on card triggers radio
document.querySelectorAll('.policy-card').forEach(card => {
    card.addEventListener('click', function () {
        this.closest('label').querySelector('.policy-radio').click();
    });
});
</script>
@endpush
@endsection
