@extends('institute.layout')
@section('title', 'Add Salary Record')
@section('breadcrumb', 'Finance / Salary / Add')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Add Salary Record</h4>
        <small class="text-muted">Pending salary create karo ya chahe to same screen se paid salary bhi save kar do</small>
    </div>
    <a href="{{ route('finance.salary.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="alert alert-info border-0 shadow-sm">
            <div class="fw-semibold mb-1">Tip</div>
            <div class="small mb-0">Agar payment details blank chhodoge to record `Pending` rahega. Payment details bharoge to salary directly paid mark ho jayegi.</div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('finance.salary.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Staff Member <span class="text-danger">*</span></label>
                            <select name="staff_member_id" class="form-select" required>
                                <option value="">Select staff</option>
                                @foreach($staffMembers as $staff)
                                    <option value="{{ $staff->id }}" data-salary="{{ $staff->salary ?? 0 }}" {{ (string) old('staff_member_id') === (string) $staff->id ? 'selected' : '' }}>
                                        {{ $staff->name }}{{ $staff->role?->name ? ' - ' . $staff->role->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Academic Session</label>
                            <select name="academic_session_id" class="form-select">
                                <option value="">General / Not session specific</option>
                                @foreach($sessions as $session)
                                    <option value="{{ $session->id }}" {{ (string) old('academic_session_id') === (string) $session->id ? 'selected' : '' }}>
                                        {{ $session->name }}{{ $session->is_active ? ' (Active)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Month <span class="text-danger">*</span></label>
                            <select name="salary_month" class="form-select" required>
                                @for($month = 1; $month <= 12; $month++)
                                    <option value="{{ $month }}" {{ (int) old('salary_month', now()->month) === $month ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create()->month($month)->format('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Year <span class="text-danger">*</span></label>
                            <input type="number" name="salary_year" class="form-control" min="2000" max="2100"
                                   value="{{ old('salary_year', now()->year) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Expense Head <span class="text-danger">*</span></label>
                            <select name="expense_account_id" class="form-select" required>
                                <option value="">Select salary head</option>
                                @foreach($expenseAccounts as $account)
                                    <option value="{{ $account->id }}" {{ (string) old('expense_account_id') === (string) $account->id ? 'selected' : '' }}>
                                        {{ $account->code }} - {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Basic Salary <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="basic_salary" id="basic_salary" class="form-control"
                                   value="{{ old('basic_salary') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Allowances</label>
                            <input type="number" step="0.01" min="0" name="allowances" id="allowances" class="form-control"
                                   value="{{ old('allowances', 0) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Deductions</label>
                            <input type="number" step="0.01" min="0" name="deductions" id="deductions" class="form-control"
                                   value="{{ old('deductions', 0) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Payment Mode</label>
                            <select name="payment_mode" id="payment_mode" class="form-select">
                                <option value="">Keep Pending</option>
                                <option value="cash" {{ old('payment_mode') === 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="bank" {{ old('payment_mode') === 'bank' ? 'selected' : '' }}>Bank</option>
                            </select>
                            <div class="form-text">Cash account: {{ $settings?->cashAccount?->name ?? 'Not mapped' }}</div>
                        </div>
                        <div class="col-md-4" id="bank_account_wrap" style="{{ old('payment_mode') === 'bank' ? '' : 'display:none;' }}">
                            <label class="form-label fw-semibold">Bank Account</label>
                            <select name="bank_account_id" class="form-select">
                                <option value="">Select bank account</option>
                                @foreach($bankAccounts as $bankAccount)
                                    <option value="{{ $bankAccount->id }}" {{ (string) old('bank_account_id') === (string) $bankAccount->id ? 'selected' : '' }}>
                                        {{ $bankAccount->display_label ?: $bankAccount->bank_name }}{{ $bankAccount->gl_account_id ? '' : ' (GL mapping pending)' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date') }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Remarks</label>
                            <input type="text" name="remarks" class="form-control" value="{{ old('remarks') }}"
                                   placeholder="Optional note">
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i> Save Salary Record
                        </button>
                        <a href="{{ route('finance.salary.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const salaryStaffEl = document.querySelector('select[name="staff_member_id"]');
    const basicSalaryEl = document.getElementById('basic_salary');
    const paymentModeEl = document.getElementById('payment_mode');
    const bankWrapEl = document.getElementById('bank_account_wrap');

    salaryStaffEl?.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        if (!basicSalaryEl.value && selected?.dataset?.salary) {
            basicSalaryEl.value = selected.dataset.salary;
        }
    });

    function toggleBankAccount() {
        bankWrapEl.style.display = paymentModeEl.value === 'bank' ? '' : 'none';
    }

    paymentModeEl?.addEventListener('change', toggleBankAccount);
    toggleBankAccount();
</script>
@endpush
