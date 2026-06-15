@extends($layout ?? 'institute.layout')
@section('title', 'Add Expense')
@section('breadcrumb', 'Finance / Expenses / Add')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-2 text-danger"></i>Add Expense</h4>
        <small class="text-muted">Expense wallet se debit hoga aur GL entry auto post hogi</small>
    </div>
    <a href="{{ route(($rp ?? 'finance') . '.expenses.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-9">

        {{-- Wallet balance card --}}
        @if(isset($walletBalance))
        <div class="alert border-0 shadow-sm {{ $walletBalance > 0 ? 'alert-success' : 'alert-danger' }} mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-wallet2 me-2"></i>
                    <strong>Wallet Balance (Active Session):</strong>
                    Rs {{ number_format($walletBalance, 2) }}
                </div>
                @if(isset($autoApproveLimit) && $autoApproveLimit < PHP_INT_MAX)
                <div class="small text-muted">
                    Auto-approve limit: Rs {{ number_format($autoApproveLimit, 2) }}
                </div>
                @endif
            </div>
        </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route(($rp ?? 'finance') . '.expenses.store') }}">
                    @csrf

                    <div class="row g-3">

                        {{-- Date & Session --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Expense Date <span class="text-danger">*</span></label>
                            <input type="date" name="expense_date" class="form-control"
                                   value="{{ old('expense_date', now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Academic Session</label>
                            <select name="academic_session_id" class="form-select" id="session_select">
                                <option value="">Active Session (default)</option>
                                @foreach($sessions as $session)
                                    <option value="{{ $session->id }}"
                                        {{ old('academic_session_id', $activeSessionId) == $session->id ? 'selected' : '' }}>
                                        {{ $session->name }}{{ $session->is_active ? ' (Active)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 3-Level Expense Category --}}
                        <div class="col-12">
                            <div class="p-3 border rounded bg-light">
                                <div class="fw-semibold small text-muted mb-2">
                                    <i class="bi bi-diagram-3 me-1"></i>Expense Category (Optional but recommended)
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Category (L1)</label>
                                        <select name="expense_category_l1_id" id="l1_select" class="form-select form-select-sm">
                                            <option value="">-- Select Category --</option>
                                            @foreach($l1Categories as $l1)
                                                <option value="{{ $l1->id }}" {{ old('expense_category_l1_id') == $l1->id ? 'selected' : '' }}>
                                                    {{ $l1->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Sub-Category (L2)</label>
                                        <select name="expense_category_l2_id" id="l2_select" class="form-select form-select-sm">
                                            <option value="">-- Select Sub-Category --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Vendor (L3)</label>
                                        <select name="expense_vendor_id" id="vendor_select" class="form-select form-select-sm">
                                            <option value="">-- Select Vendor --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- GL Expense Head --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">GL Expense Head <span class="text-danger">*</span></label>
                            <select name="expense_account_id" class="form-select" required>
                                <option value="">Select expense head</option>
                                @foreach($expenseAccounts as $account)
                                    <option value="{{ $account->id }}"
                                        {{ old('expense_account_id') == $account->id ? 'selected' : '' }}>
                                        {{ $account->code }} - {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Amount --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount (Rs) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="amount_field"
                                   class="form-control @error('amount') is-invalid @enderror"
                                   value="{{ old('amount') }}" placeholder="0.00" required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if(isset($autoApproveLimit) && $autoApproveLimit < PHP_INT_MAX)
                            <div id="approval_warning" class="form-text text-warning" style="display:none">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Amount > Rs {{ number_format($autoApproveLimit, 2) }} — approval required from admin.
                            </div>
                            @endif
                        </div>

                        {{-- Payment Mode --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Mode <span class="text-danger">*</span></label>
                            <select name="payment_mode" id="payment_mode" class="form-select" required>
                                <option value="cash" {{ old('payment_mode', 'cash') === 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="bank" {{ old('payment_mode') === 'bank' ? 'selected' : '' }}>Bank</option>
                            </select>
                            <div class="form-text">Cash account: {{ $settings?->cashAccount?->name ?? 'Not mapped' }}</div>
                        </div>
                        <div class="col-md-6" id="bank_account_wrap" style="{{ old('payment_mode') === 'bank' ? '' : 'display:none;' }}">
                            <label class="form-label fw-semibold">Bank Account</label>
                            <select name="bank_account_id" class="form-select">
                                <option value="">Select bank account</option>
                                @foreach($bankAccounts as $bankAccount)
                                    <option value="{{ $bankAccount->id }}"
                                        {{ old('bank_account_id') == $bankAccount->id ? 'selected' : '' }}>
                                        {{ $bankAccount->display_label ?: $bankAccount->bank_name }}
                                        {{ $bankAccount->gl_account_id ? '' : ' (GL mapping pending)' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Vendor & Bill --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Vendor Name (manual)</label>
                            <input type="text" name="vendor_name" class="form-control"
                                   value="{{ old('vendor_name') }}" placeholder="Or select from L3 above">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Bill No</label>
                            <input type="text" name="bill_no" class="form-control"
                                   value="{{ old('bill_no') }}" placeholder="Optional">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                            <textarea name="description" rows="3" class="form-control" required
                                      placeholder="Expense ka short narration">{{ old('description') }}</textarea>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex gap-2 align-items-center">
                        <button type="submit" class="btn btn-danger px-4">
                            <i class="bi bi-check-lg me-1"></i> Save Expense
                        </button>
                        <a href="{{ route(($rp ?? 'finance') . '.expenses.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                        <span class="small text-muted ms-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Auto-approve hone par wallet se debit hoga. Limit se zyada hone par admin approval jayegi.
                        </span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const paymentModeEl = document.getElementById('payment_mode');
const bankWrapEl    = document.getElementById('bank_account_wrap');
const l1Select      = document.getElementById('l1_select');
const l2Select      = document.getElementById('l2_select');
const vendorSelect  = document.getElementById('vendor_select');
const amountField   = document.getElementById('amount_field');
const approvalWarn  = document.getElementById('approval_warning');
const autoLimit     = {{ isset($autoApproveLimit) && $autoApproveLimit < PHP_INT_MAX ? $autoApproveLimit : 'null' }};

// Payment mode toggle
paymentModeEl.addEventListener('change', () => {
    bankWrapEl.style.display = paymentModeEl.value === 'bank' ? '' : 'none';
});

// Amount approval warning
if (amountField && approvalWarn && autoLimit !== null) {
    amountField.addEventListener('input', () => {
        approvalWarn.style.display = parseFloat(amountField.value) > autoLimit ? '' : 'none';
    });
}

// Cascade dropdowns
l1Select.addEventListener('change', async () => {
    l2Select.innerHTML = '<option value="">Loading...</option>';
    vendorSelect.innerHTML = '<option value="">-- Select Vendor --</option>';

    const l1Id = l1Select.value;
    if (!l1Id) {
        l2Select.innerHTML = '<option value="">-- Select Sub-Category --</option>';
        return;
    }

    const res = await fetch(`{{ $subCategoryAjaxUrl }}?l1_id=${l1Id}`);
    const data = await res.json();

    l2Select.innerHTML = '<option value="">-- Select Sub-Category --</option>';
    data.forEach(s => {
        l2Select.innerHTML += `<option value="${s.id}">${s.name}</option>`;
    });
});

l2Select.addEventListener('change', async () => {
    vendorSelect.innerHTML = '<option value="">Loading...</option>';

    const l2Id = l2Select.value;
    if (!l2Id) {
        vendorSelect.innerHTML = '<option value="">-- Select Vendor --</option>';
        return;
    }

    const res = await fetch(`{{ $vendorAjaxUrl }}?l2_id=${l2Id}`);
    const data = await res.json();

    vendorSelect.innerHTML = '<option value="">-- Select Vendor --</option>';
    data.forEach(v => {
        const phone = v.contact_phone ? ` (${v.contact_phone})` : '';
        vendorSelect.innerHTML += `<option value="${v.id}">${v.name}${phone}</option>`;
    });
});
</script>
@endpush
