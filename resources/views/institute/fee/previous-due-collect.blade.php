@php
    $isStaff = auth()->guard('staff')->check();
    $layout = $isStaff ? 'staff.layout' : 'institute.layout';
    $indexRoute = $isStaff ? 'staff.fee.previous-dues.index' : 'fee.previous-dues.index';
    $storeRoute = $isStaff ? 'staff.fee.previous-dues.store' : 'fee.previous-dues.store';
@endphp
@extends($layout)
@section('title', 'Collect Previous Due')
@section('breadcrumb', 'Fee / Previous Dues / ' . $student->name)

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-cash-coin text-success me-2"></i> Collect Previous Due</h4>
        <small class="text-muted">{{ $student->name }} — {{ $student->student_uid }}</small>
    </div>
    <a href="{{ route($indexRoute) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Error:</strong> {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Student summary --}}
<div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #dc3545 !important;">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="small text-muted">Course / Stream</div>
                <div class="fw-semibold">{{ $student->stream?->course?->name ?? '—' }} — {{ $student->stream?->name ?? '—' }}</div>
            </div>
            <div class="col-md-2">
                <div class="small text-muted">Last Semester</div>
                <div class="fw-semibold">Semester {{ $student->current_semester }}</div>
            </div>
            <div class="col-md-2">
                <div class="small text-muted">Session</div>
                <div class="fw-semibold">{{ $student->session?->name ?? '—' }}</div>
            </div>
            <div class="col-md-2">
                <div class="small text-muted">Mobile</div>
                <div class="fw-semibold">{{ $student->mobile }}</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Status</div>
                <div class="fw-semibold">
                    <span class="badge bg-danger bg-opacity-10 text-danger">
                        {{ ucfirst(str_replace('_', ' ', $student->status)) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

@if(count($pendingRows) === 0)
    <div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #16a34a !important;">
        <div class="card-body py-4 text-center">
            <i class="bi bi-check-circle text-success fs-1 d-block mb-2"></i>
            <div class="fw-semibold">This student has no outstanding due.</div>
        </div>
    </div>
@else
<form method="POST" action="{{ route($storeRoute, $student->id) }}" id="collectForm">
    @csrf

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
            <span class="fw-semibold small">
                <i class="bi bi-list-check me-1 text-primary"></i>
                Outstanding Dues — {{ count($pendingRows) }} item(s)
            </span>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="fillAllBtn">
                <i class="bi bi-lightning-fill me-1"></i> Fill Full Amount
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" style="font-size:13px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Fee Item</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Already Paid</th>
                        <th class="text-end">Outstanding</th>
                        <th class="text-end" style="width:130px;">Collect ₹</th>
                        <th class="text-end" style="width:110px;">Fine ₹</th>
                        <th class="text-end" style="width:110px;">Discount ₹</th>
                        <th class="text-end pe-3" style="width:120px;">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingRows as $i => $row)
                    @php $key = 'row' . $i; @endphp
                    <tr>
                        <td class="ps-3 fw-semibold">
                            {{ $row['name'] }}
                            <input type="hidden" name="items[{{ $row['name'] }}][__key]" value="{{ $key }}">
                        </td>
                        <td class="text-end text-muted">₹{{ number_format($row['charged'], 2) }}</td>
                        <td class="text-end text-muted">₹{{ number_format($row['collection'] + $row['discount'], 2) }}</td>
                        <td class="text-end text-danger fw-semibold pending-amount" data-key="{{ $key }}" data-pending="{{ $row['pending'] }}">
                            ₹{{ number_format($row['pending'], 2) }}
                        </td>
                        <td class="text-end">
                            <input type="number" step="0.01" min="0"
                                   name="items[{{ $row['name'] }}][collect]"
                                   class="form-control form-control-sm text-end collect-input"
                                   data-key="{{ $key }}"
                                   value="{{ old("items.{$row['name']}.collect", 0) }}">
                        </td>
                        <td class="text-end">
                            <input type="number" step="0.01" min="0"
                                   name="items[{{ $row['name'] }}][fine]"
                                   class="form-control form-control-sm text-end fine-input"
                                   data-key="{{ $key }}"
                                   value="{{ old("items.{$row['name']}.fine", 0) }}">
                        </td>
                        <td class="text-end">
                            <input type="number" step="0.01" min="0"
                                   name="items[{{ $row['name'] }}][discount]"
                                   class="form-control form-control-sm text-end discount-input"
                                   data-key="{{ $key }}"
                                   value="{{ old("items.{$row['name']}.discount", 0) }}">
                        </td>
                        <td class="text-end pe-3 fw-semibold">
                            <span class="row-balance" data-key="{{ $key }}">₹0.00</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <td class="ps-3 fw-bold" colspan="4">Totals</td>
                        <td class="text-end fw-bold" id="totalCollect">₹0.00</td>
                        <td class="text-end fw-bold" id="totalFine">₹0.00</td>
                        <td class="text-end fw-bold" id="totalDiscount">₹0.00</td>
                        <td class="text-end pe-3 fw-bold text-success" id="grandTotal">₹0.00</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-2">
            <span class="fw-semibold small"><i class="bi bi-credit-card me-1 text-primary"></i> Payment Details</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Payment Mode <span class="text-danger">*</span></label>
                    <select name="payment_mode" id="paymentMode" class="form-select form-select-sm" required>
                        <option value="cash">Cash</option>
                        <option value="online">Online</option>
                        <option value="upi">UPI</option>
                        <option value="neft">NEFT</option>
                        <option value="rtgs">RTGS</option>
                        <option value="cheque">Cheque</option>
                        <option value="dd">DD</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control form-control-sm"
                           value="{{ old('payment_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                </div>
                <div class="col-md-3" id="bankAccountWrap" style="display:none;">
                    <label class="form-label small fw-semibold">Bank Account</label>
                    <select name="bank_account_id" class="form-select form-select-sm">
                        <option value="">— Select —</option>
                        @foreach($bankAccounts as $bank)
                            <option value="{{ $bank->id }}">{{ $bank->display_label ?: ($bank->bank_name . ' — ' . $bank->account_name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3" id="transactionRefWrap" style="display:none;">
                    <label class="form-label small fw-semibold">Transaction Ref / UTR / Cheque No.</label>
                    <input type="text" name="transaction_ref" class="form-control form-control-sm" maxlength="100">
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route($indexRoute) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Cancel
        </a>
        <button type="submit" class="btn btn-success px-4" id="collectBtn">
            <i class="bi bi-cash-coin me-1"></i> Collect Due
        </button>
    </div>
</form>
@endif

@endsection

@push('scripts')
<script>
function fmt(n) {
    return '₹' + (isNaN(n) ? 0 : n).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function recalcRow(key) {
    const pendingEl = document.querySelector(`.pending-amount[data-key="${key}"]`);
    const collectEl = document.querySelector(`.collect-input[data-key="${key}"]`);
    const fineEl     = document.querySelector(`.fine-input[data-key="${key}"]`);
    const discEl     = document.querySelector(`.discount-input[data-key="${key}"]`);
    const balanceEl  = document.querySelector(`.row-balance[data-key="${key}"]`);
    if (!pendingEl || !collectEl || !fineEl || !discEl || !balanceEl) return;

    const pending = parseFloat(pendingEl.dataset.pending) || 0;
    let collect = parseFloat(collectEl.value) || 0;
    let fine = parseFloat(fineEl.value) || 0;
    let disc = parseFloat(discEl.value) || 0;

    const cap = pending + fine;
    if (collect + disc > cap) {
        // Cap client-side for a clear, immediate signal — the server re-validates
        // this itself before saving anything.
        if (disc > cap) { disc = cap; discEl.value = disc; }
        collect = Math.max(0, cap - disc);
        collectEl.value = collect;
    }

    const balance = Math.max(0, pending + fine - collect - disc);
    balanceEl.textContent = fmt(balance);
    balanceEl.classList.toggle('text-danger', balance > 0);
    balanceEl.classList.toggle('text-success', balance <= 0);

    recalcTotals();
}

function recalcTotals() {
    let totalCollect = 0, totalFine = 0, totalDiscount = 0;
    document.querySelectorAll('.collect-input').forEach(el => totalCollect += parseFloat(el.value) || 0);
    document.querySelectorAll('.fine-input').forEach(el => totalFine += parseFloat(el.value) || 0);
    document.querySelectorAll('.discount-input').forEach(el => totalDiscount += parseFloat(el.value) || 0);

    document.getElementById('totalCollect').textContent = fmt(totalCollect);
    document.getElementById('totalFine').textContent = fmt(totalFine);
    document.getElementById('totalDiscount').textContent = fmt(totalDiscount);
    document.getElementById('grandTotal').textContent = fmt(totalCollect + totalFine);
}

document.querySelectorAll('.collect-input, .fine-input, .discount-input').forEach(el => {
    el.addEventListener('input', () => recalcRow(el.dataset.key));
});

document.getElementById('fillAllBtn')?.addEventListener('click', function () {
    document.querySelectorAll('.pending-amount').forEach(pendingEl => {
        const key = pendingEl.dataset.key;
        const collectEl = document.querySelector(`.collect-input[data-key="${key}"]`);
        if (collectEl) collectEl.value = pendingEl.dataset.pending;
        recalcRow(key);
    });
});

document.querySelectorAll('.pending-amount').forEach(el => recalcRow(el.dataset.key));

const paymentModeEl = document.getElementById('paymentMode');
function toggleNonCashFields() {
    const isCash = paymentModeEl.value === 'cash';
    document.getElementById('bankAccountWrap').style.display = isCash ? 'none' : '';
    document.getElementById('transactionRefWrap').style.display = isCash ? 'none' : '';
}
paymentModeEl?.addEventListener('change', toggleNonCashFields);
toggleNonCashFields();

document.getElementById('collectForm')?.addEventListener('submit', function () {
    const btn = document.getElementById('collectBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Collecting...';
});
</script>
@endpush
