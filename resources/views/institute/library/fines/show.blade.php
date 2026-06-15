@extends($libraryLayout)
@section('title', 'Fine Collection — ' . $member->name)
@section('breadcrumb', 'Library / Fine Collection / Collect')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Fine Collect Karo</h4>
        <small class="text-muted">Har book ka fine alag-alag enter karo.</small>
    </div>
    <a href="{{ route($libraryRoutePrefix . '.fines.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="POST" action="{{ route($libraryRoutePrefix . '.fines.collect', $member) }}" id="fineForm">
    @csrf

    {{-- Member Card --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom fw-semibold">
            <i class="bi bi-person-circle me-2 text-primary"></i>Member Details
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-muted small mb-1">Name</div>
                    <div class="fw-bold fs-6">{{ $member->name }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small mb-1">Member Code / Type</div>
                    <div class="fw-semibold">{{ $member->member_code }}</div>
                    <span class="badge bg-secondary">{{ ucfirst($member->member_type) }}</span>
                </div>
                @if($member->student)
                    <div class="col-md-3">
                        <div class="text-muted small mb-1">Roll No / UIN</div>
                        <div class="small">Roll: <strong>{{ $member->student->roll_no ?: '—' }}</strong></div>
                        <div class="small">UIN: <strong>{{ $member->student->uin_no ?: '—' }}</strong></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small mb-1">Father / Mother</div>
                        <div class="small">{{ $member->student->father_name ?: '—' }}</div>
                        <div class="small text-muted">{{ $member->student->mother_name ?: '—' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small mb-1">Course</div>
                        <div class="small">{{ $member->student->stream->course->name ?? '—' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small mb-1">Mobile</div>
                        <div class="small">{{ $member->mobile ?: '—' }}</div>
                    </div>
                @elseif($member->staffMember)
                    <div class="col-md-3">
                        <div class="text-muted small mb-1">Role</div>
                        <div class="small">{{ $member->staffMember->role->name ?? '—' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small mb-1">Mobile</div>
                        <div class="small">{{ $member->mobile ?: '—' }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Fine Items Table --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom fw-semibold">
            <i class="bi bi-book me-2 text-danger"></i>Pending Fine Items
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th class="text-start">Book Title</th>
                            <th>Accession No</th>
                            <th>Issued On</th>
                            <th>Due On</th>
                            <th>Returned On</th>
                            <th class="text-end">Total Fine</th>
                            <th class="text-end">Already Paid</th>
                            <th class="text-end text-danger">Pending</th>
                            <th class="text-end" style="min-width:140px">Collect Now (Rs)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingTransactions as $i => $tx)
                            <input type="hidden" name="items[{{ $i }}][transaction_id]" value="{{ $tx->id }}">
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $tx->copy->book->title ?? '—' }}</div>
                                    <div class="small text-muted">{{ $tx->rule_name_snapshot }}</div>
                                </td>
                                <td class="text-center small">{{ $tx->copy->accession_no ?? '—' }}</td>
                                <td class="text-center small">{{ optional($tx->issued_on)->format('d-m-Y') }}</td>
                                <td class="text-center small {{ $tx->is_overdue ? 'text-danger fw-semibold' : '' }}">
                                    {{ optional($tx->due_on)->format('d-m-Y') }}
                                </td>
                                <td class="text-center small">{{ optional($tx->returned_on)->format('d-m-Y') ?: '—' }}</td>
                                <td class="text-end">{{ number_format((float)$tx->fine_amount, 2) }}</td>
                                <td class="text-end text-success">{{ number_format((float)$tx->fine_paid, 2) }}</td>
                                <td class="text-end fw-bold text-danger">{{ number_format($tx->pending_fine, 2) }}</td>
                                <td class="text-end">
                                    <input type="number"
                                           name="items[{{ $i }}][amount]"
                                           value="{{ number_format($tx->pending_fine, 2, '.', '') }}"
                                           max="{{ number_format($tx->pending_fine, 2, '.', '') }}"
                                           min="0"
                                           step="0.01"
                                           class="form-control form-control-sm text-end fine-item-input"
                                           style="width:130px; margin-left:auto">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="7" class="text-end fw-bold">Total to Collect:</td>
                            <td class="text-end fw-bold text-danger">Rs {{ number_format($totalPending, 2) }}</td>
                            <td class="text-end fw-bold text-primary" id="collect-total">
                                Rs {{ number_format($totalPending, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Payment Details --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom fw-semibold">
            <i class="bi bi-credit-card me-2 text-success"></i>Payment Details
        </div>
        <div class="card-body">
            <div class="row g-3">

                {{-- Payment Mode --}}
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Payment Mode <span class="text-danger">*</span></label>
                    <select name="payment_mode" id="finePaymentMode" class="form-select" onchange="fineModeChange()" required>
                        <option value="cash">Cash</option>
                        <option value="upi">UPI</option>
                        <option value="neft">NEFT / IMPS</option>
                        <option value="rtgs">RTGS</option>
                        <option value="cheque">Cheque</option>
                        <option value="dd">Demand Draft</option>
                        <option value="online">Online</option>
                    </select>
                </div>

                {{-- Bank Account (non-cash) --}}
                <div class="col-md-4" id="fineBankAccountWrap" style="display:none;">
                    <label class="form-label small fw-semibold">Bank Account</label>
                    <select name="bank_account_id" id="fineBankAccountSelect" class="form-select">
                        <option value="">— Select Bank Account —</option>
                        @foreach($bankAccounts as $ba)
                            <option value="{{ $ba->id }}"
                                    data-modes="{{ $ba->allowed_payment_modes ?? 'cash,upi,online,cheque,dd,neft,rtgs' }}">
                                {{ $ba->bank_name }}
                                @if($ba->account_no) — {{ $ba->account_no }} @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Payment Date (cash) / Payment Date+Time (non-cash) --}}
                <div class="col-md-4">
                    <label class="form-label small fw-semibold" id="fineDateLabel">
                        Payment Date <span class="text-danger">*</span>
                    </label>
                    <div id="fineCashDate">
                        <input type="date" name="payment_date" id="fineCashDateInput"
                               value="{{ now()->toDateString() }}" class="form-control" required>
                    </div>
                    <div id="fineNonCashDatetime" style="display:none;">
                        <input type="datetime-local" name="payment_datetime" id="finePaymentDatetime"
                               value="{{ $defaultPaymentDatetime }}" class="form-control" disabled>
                        <div class="form-text"><i class="bi bi-clock me-1"></i>Actual payment date aur time enter karo.</div>
                    </div>
                </div>

                {{-- Transaction Ref / UTR (non-cash) --}}
                <div class="col-md-4" id="fineTxnRefWrap" style="display:none;">
                    <label class="form-label small fw-semibold" id="fineTxnRefLabel">
                        Transaction Ref / UTR <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="transaction_ref" id="fineTxnRefInput"
                           class="form-control" placeholder="Reference number...">
                </div>

                {{-- Bank Name (cheque / DD only) --}}
                <div class="col-md-4" id="fineBankNameWrap" style="display:none;">
                    <label class="form-label small fw-semibold">Bank Name</label>
                    <input type="text" name="bank_name" class="form-control" placeholder="Bank name...">
                </div>

                {{-- Receipt No --}}
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Receipt No</label>
                    <input type="text" name="receipt_no" value="{{ $nextReceiptNo }}"
                           class="form-control" placeholder="Auto-generate hoga agar blank">
                </div>

                {{-- Remarks --}}
                <div class="col-md-8">
                    <label class="form-label small fw-semibold">Remarks</label>
                    <input type="text" name="remarks" class="form-control" placeholder="Optional">
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <div class="fs-5 fw-bold">
                    Total Collect: <span class="text-primary" id="collect-total-btn">
                        Rs {{ number_format($totalPending, 2) }}
                    </span>
                </div>
                <button type="submit" class="btn btn-success btn-lg px-5">
                    <i class="bi bi-cash-coin me-2"></i>Collect Fine &amp; Print Receipt
                </button>
            </div>
        </div>
    </div>

</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ── Fine item total calculator ─────────────────────────────────
    const inputs = document.querySelectorAll('.fine-item-input');

    function updateTotal() {
        let total = 0;
        inputs.forEach(input => {
            const val = parseFloat(input.value) || 0;
            const max = parseFloat(input.max) || 0;
            total += Math.min(val, max);
        });
        const formatted = 'Rs ' + total.toFixed(2);
        document.getElementById('collect-total').textContent = formatted;
        document.getElementById('collect-total-btn').textContent = formatted;
    }

    inputs.forEach(input => input.addEventListener('input', updateTotal));
    updateTotal();

    // ── Sync payment_date from datetime input ──────────────────────
    const dtInput = document.getElementById('finePaymentDatetime');
    const dateInput = document.getElementById('fineCashDateInput');
    if (dtInput && dateInput) {
        dtInput.addEventListener('change', function () {
            if (this.value) dateInput.value = this.value.split('T')[0];
        });
    }

    // Init on load
    fineModeChange();
});

// ── Payment mode toggle (fee-collection style) ──────────────────────
function fineModeChange() {
    const mode       = document.getElementById('finePaymentMode')?.value || 'cash';
    const isNonCash  = mode !== 'cash';
    const isChequeDD = ['cheque', 'dd'].includes(mode);
    const needsRef   = ['upi', 'online', 'neft', 'rtgs', 'cheque', 'dd'].includes(mode);

    // Date / Datetime toggle
    const cashDiv    = document.getElementById('fineCashDate');
    const nonCashDiv = document.getElementById('fineNonCashDatetime');
    const dtInput    = document.getElementById('finePaymentDatetime');
    const cashInput  = document.getElementById('fineCashDateInput');
    const dateLabel  = document.getElementById('fineDateLabel');

    if (cashDiv && nonCashDiv) {
        cashDiv.style.display    = isNonCash ? 'none'  : 'block';
        nonCashDiv.style.display = isNonCash ? 'block' : 'none';
        if (dtInput)   { dtInput.required  = isNonCash; dtInput.disabled = !isNonCash; }
        if (cashInput)   cashInput.required  = !isNonCash;
    }
    if (dateLabel) {
        dateLabel.innerHTML = isNonCash
            ? 'Payment Date &amp; Time <span class="text-danger">*</span> <small class="text-muted fw-normal">(Actual payment time)</small>'
            : 'Payment Date <span class="text-danger">*</span>';
    }

    // Bank account dropdown
    const bankAccountWrap = document.getElementById('fineBankAccountWrap');
    if (bankAccountWrap) {
        bankAccountWrap.style.display = isNonCash ? 'block' : 'none';
        filterBankAccounts(mode);
    }

    // Transaction ref
    const txnWrap  = document.getElementById('fineTxnRefWrap');
    const txnInput = document.getElementById('fineTxnRefInput');
    const txnLabel = document.getElementById('fineTxnRefLabel');
    if (txnWrap) {
        txnWrap.style.display = needsRef ? 'block' : 'none';
        if (txnInput) txnInput.required = needsRef;
        if (txnLabel) {
            if (mode === 'cheque')      txnLabel.innerHTML = 'Cheque No <span class="text-danger">*</span>';
            else if (mode === 'dd')     txnLabel.innerHTML = 'DD No <span class="text-danger">*</span>';
            else                        txnLabel.innerHTML = 'Transaction Ref / UTR <span class="text-danger">*</span>';
        }
        if (txnInput) {
            txnInput.placeholder = mode === 'cheque' ? 'Cheque number...'
                                 : mode === 'dd'     ? 'DD number...'
                                 : 'Transaction Ref / UTR...';
        }
    }

    // Bank name (cheque / DD only)
    const bankNameWrap = document.getElementById('fineBankNameWrap');
    if (bankNameWrap) bankNameWrap.style.display = isChequeDD ? 'block' : 'none';
}

function filterBankAccounts(mode) {
    const select = document.getElementById('fineBankAccountSelect');
    if (!select) return;
    Array.from(select.options).forEach(opt => {
        if (!opt.value) return;
        const allowed = (opt.dataset.modes || '').split(',').map(s => s.trim());
        opt.hidden = !allowed.includes(mode);
    });
    if (select.options[select.selectedIndex]?.hidden) select.value = '';
}
</script>
@endpush

@endsection
