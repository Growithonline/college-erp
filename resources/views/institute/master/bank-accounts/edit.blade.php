@extends('institute.layout')
@section('title', isset($bankAccount) ? 'Edit Bank Account' : 'Add Bank Account')
@section('breadcrumb', 'Master / Bank Accounts / '.( isset($bankAccount) ? 'Edit' : 'Add'))

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">
        <i class="bi bi-bank me-2 text-primary"></i>
        {{ isset($bankAccount) ? 'Edit Bank Account' : 'Add Bank Account' }}
    </h4>
    <a href="{{ route('master.bank-accounts.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ isset($bankAccount)
                    ? route('master.bank-accounts.update', $bankAccount)
                    : route('master.bank-accounts.store') }}">
                    @csrf
                    @if(isset($bankAccount)) @method('PATCH') @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Bank Name <span class="text-danger">*</span></label>
                            <input type="text" name="bank_name" class="form-control"
                                   value="{{ old('bank_name', $bankAccount->bank_name ?? '') }}"
                                   placeholder="e.g. SBI, HDFC, PNB" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Display Label</label>
                            <input type="text" name="display_label" class="form-control"
                                   value="{{ old('display_label', $bankAccount->display_label ?? '') }}"
                                   placeholder="e.g. SBI Main Account">
                            <div class="form-text">Dropdown mein yeh naam dikhega</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Account Holder Name <span class="text-danger">*</span></label>
                            <input type="text" name="account_name" class="form-control"
                                   value="{{ old('account_name', $bankAccount->account_name ?? '') }}"
                                   placeholder="e.g. Aakash Institute" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Account Number <span class="text-danger">*</span></label>
                            <input type="text" name="account_no" class="form-control"
                                   value="{{ old('account_no', $bankAccount->account_no ?? '') }}"
                                   placeholder="Bank account number" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">IFSC Code</label>
                            <input type="text" name="ifsc_code" class="form-control"
                                   value="{{ old('ifsc_code', $bankAccount->ifsc_code ?? '') }}"
                                   placeholder="e.g. SBIN0001234" maxlength="20"
                                   style="text-transform:uppercase;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Branch</label>
                            <input type="text" name="branch" class="form-control"
                                   value="{{ old('branch', $bankAccount->branch ?? '') }}"
                                   placeholder="Branch name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">UPI ID</label>
                            <input type="text" name="upi_id" class="form-control"
                                   value="{{ old('upi_id', $bankAccount->upi_id ?? '') }}"
                                   placeholder="e.g. college@sbi">
                            <div class="form-text">Is bank se linked UPI ID</div>
                        </div>
                    </div>

                    {{-- Allowed Payment Modes --}}
                    <div class="row mb-3 mt-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                Allowed Payment Modes
                                <span class="text-muted fw-normal small ms-1">(is account pe konse modes accept honge)</span>
                            </label>
                            @php
                                $savedModes = old('allowed_payment_modes',
                                    isset($bankAccount)
                                        ? explode(',', $bankAccount->allowed_payment_modes ?? 'upi,online,cheque,dd,neft,rtgs')
                                        : ['upi','online','cheque','dd','neft','rtgs']
                                );
                                $allModes = [
                                    'upi'    => ['label' => 'UPI',    'icon' => 'bi-phone',          'color' => 'text-success'],
                                    'online' => ['label' => 'Online', 'icon' => 'bi-globe',           'color' => 'text-primary'],
                                    'cheque' => ['label' => 'Cheque', 'icon' => 'bi-bank',            'color' => 'text-info'],
                                    'dd'     => ['label' => 'DD',     'icon' => 'bi-file-earmark',    'color' => 'text-secondary'],
                                    'neft'   => ['label' => 'NEFT',   'icon' => 'bi-arrow-left-right','color' => 'text-warning'],
                                    'rtgs'   => ['label' => 'RTGS',   'icon' => 'bi-arrow-repeat',    'color' => 'text-danger'],
                                ];
                            @endphp
                            <div class="d-flex flex-wrap gap-2 mt-1">
                                <div class="border rounded px-3 py-2 d-flex align-items-center gap-2"
                                     style="background:#f0fdf4;border-color:#86efac!important;">
                                    <i class="bi bi-cash text-success"></i>
                                    <span class="small fw-semibold text-success">Cash</span>
                                    <span class="badge bg-success ms-1" style="font-size:9px;">Always ON</span>
                                </div>
                                @foreach($allModes as $mode => $info)
                                <label class="border rounded px-3 py-2 d-flex align-items-center gap-2"
                                       style="cursor:pointer;user-select:none;">
                                    <input type="checkbox"
                                           name="allowed_payment_modes[]"
                                           value="{{ $mode }}"
                                           class="form-check-input mt-0"
                                           {{ in_array($mode, (array)$savedModes) ? 'checked' : '' }}>
                                    <i class="bi {{ $info['icon'] }} {{ $info['color'] }}"></i>
                                    <span class="small fw-semibold">{{ $info['label'] }}</span>
                                </label>
                                @endforeach
                            </div>
                            <div class="form-text mt-1">
                                <i class="bi bi-info-circle me-1"></i>
                                Cash har account pe available hota hai. Baaki modes yahan select karo.
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i>
                            {{ isset($bankAccount) ? 'Update' : 'Add Bank Account' }}
                        </button>
                        <a href="{{ route('master.bank-accounts.index') }}"
                           class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection