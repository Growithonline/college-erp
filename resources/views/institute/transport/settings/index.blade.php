@extends('institute.layout')
@section('title', 'Transport Settings')
@section('breadcrumb', 'Transport / Settings')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">

        @if(session('success'))
            @push('scripts')
            <script>document.addEventListener('DOMContentLoaded', () => showToast('{{ addslashes(session('success')) }}', 'success'));</script>
            @endpush
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
                                'full_charge'     => ['title' => 'Full Charge',     'desc' => 'Full fee of the new route will be charged on transfer. (Default)', 'icon' => 'bi-cash-coin',    'color' => 'warning'],
                                'no_charge'       => ['title' => 'No Charge',       'desc' => 'No additional charge on transfer. The existing fee remains valid for the academic year.',  'icon' => 'bi-shield-check', 'color' => 'success'],
                                'prorated_charge' => ['title' => 'Prorated Charge', 'desc' => 'A partial fee will be charged based on remaining days.',                'icon' => 'bi-percent',      'color' => 'info'],
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

                    {{-- Semester Duration --}}
                    <div class="mb-4">
                        <label for="semesterDurationMonths" class="form-label fw-semibold">Semester Duration (months) <span class="text-danger">*</span></label>
                        <small class="d-block text-muted mb-2">
                            Reference length of one semester, used to suggest a prorated credit when a student
                            transfers routes or cancels transport mid-semester. Staff can always edit the
                            suggested amount before confirming.
                        </small>
                        <input type="number" min="1" max="12" step="1" id="semesterDurationMonths"
                               name="semester_duration_months" class="form-control" style="max-width: 160px;"
                               value="{{ old('semester_duration_months', $setting->semester_duration_months ?? 6) }}" required>
                        @error('semester_duration_months') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
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
                            <strong>ON:</strong> If a student has already paid the yearly transport fee in Semester 1, they will not be charged again in Semester 2 within the same academic year.<br>
                            <strong>OFF:</strong> Transport fee will be charged again on every session change.
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
