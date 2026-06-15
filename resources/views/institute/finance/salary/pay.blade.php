@extends($layout ?? 'institute.layout')
@section('title', 'Pay Salary')
@section('breadcrumb', 'Finance / Salary / Pay')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-cash-coin me-2 text-primary"></i>Pay Salary</h4>
        <small class="text-muted">Selected salary record ko paid mark karke accounting journal generate karo</small>
    </div>
    <a href="{{ route(($rp ?? 'finance') . '.salary.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Staff</div>
                <div class="fw-bold">{{ $salaryRecord->staffMember?->name ?? '-' }}</div>
                <div class="small text-muted">{{ $salaryRecord->staffMember?->role?->name ?? 'Staff' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Salary Period</div>
                <div class="fw-bold">{{ \Carbon\Carbon::createFromDate($salaryRecord->salary_year, $salaryRecord->salary_month, 1)->format('F Y') }}</div>
                <div class="small text-muted">Expense Head: {{ $salaryRecord->expenseAccount?->name ?? '-' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Net Payable</div>
                <div class="fw-bold fs-4 text-success">Rs {{ number_format($salaryRecord->net_payable, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route(($rp ?? 'finance') . '.salary.mark-paid', $salaryRecord) }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Payment Mode <span class="text-danger">*</span></label>
                    <select name="payment_mode" id="payment_mode" class="form-select" required>
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                    </select>
                    <div class="form-text">Cash account: {{ $settings?->cashAccount?->name ?? 'Not mapped' }}</div>
                </div>
                <div class="col-md-4" id="bank_account_wrap" style="display:none;">
                    <label class="form-label fw-semibold">Bank Account <span class="text-danger">*</span></label>
                    <select name="bank_account_id" class="form-select">
                        <option value="">Select bank account</option>
                        @foreach($bankAccounts as $bankAccount)
                            <option value="{{ $bankAccount->id }}">
                                {{ $bankAccount->display_label ?: $bankAccount->bank_name }}{{ $bankAccount->gl_account_id ? '' : ' (GL mapping pending)' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Remarks</label>
                    <input type="text" name="remarks" class="form-control"
                           value="{{ old('remarks', $salaryRecord->remarks) }}" placeholder="Optional note">
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i> Mark Paid
                </button>
                <a href="{{ route(($rp ?? 'finance') . '.salary.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const salaryPaymentModeEl = document.getElementById('payment_mode');
    const salaryBankWrapEl = document.getElementById('bank_account_wrap');

    function toggleSalaryBankAccount() {
        salaryBankWrapEl.style.display = salaryPaymentModeEl.value === 'bank' ? '' : 'none';
    }

    salaryPaymentModeEl?.addEventListener('change', toggleSalaryBankAccount);
    toggleSalaryBankAccount();
</script>
@endpush
