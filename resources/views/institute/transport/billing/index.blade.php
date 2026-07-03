@extends('institute.layout')
@section('title', 'Transport Billing')
@section('breadcrumb', 'Transport / Billing')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Transport Billing</h4>
        <small class="text-muted">Generate semester charges and collect one-time transport fees.</small>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <span><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</span>
        @if(session('receipt_txn_id'))
            <a href="{{ route('transport.billing.receipt', session('receipt_txn_id')) }}" target="_blank"
               class="btn btn-sm btn-success px-3 flex-shrink-0">
                <i class="bi bi-printer me-1"></i> Print Receipt
            </a>
        @endif
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>
@endif

{{-- Session Filter + Generate --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end mb-3">
            <div class="col-md-4">
                <label class="form-label">Session</label>
                <select name="session_id" class="form-select">
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-primary w-100">View</button>
            </div>
        </form>

        @if($pendingCount > 0)
        <form method="POST" action="{{ route('transport.billing.generate') }}"
            onsubmit="return confirm('Generate semester charges for {{ (int) $pendingCount }} pending student(s)?')">
            @csrf
            <input type="hidden" name="academic_session_id" value="{{ $sessionId }}">
            <button class="btn btn-primary">
                <i class="bi bi-lightning me-1"></i>Generate {{ $pendingCount }} Pending Charges
            </button>
        </form>
        @else
            <div class="alert alert-success mb-0">
                <i class="bi bi-check-circle me-1"></i>All semester allocations are already charged for this session.
            </div>
        @endif
    </div>
</div>

{{-- Semester Allocation List --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>
            Semester Allocations
            <span class="badge bg-secondary ms-2">{{ $allocations->count() }} total</span>
            @if($pendingCount > 0)
                <span class="badge bg-danger ms-1">{{ $pendingCount }} pending</span>
            @endif
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student</th>
                    <th>Route</th>
                    <th class="text-end">Fee Amount</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Balance</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($allocations as $a)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $a->student?->name ?? '—' }}</div>
                        <small class="text-muted">{{ $a->student?->roll_no ?? '' }}</small>
                    </td>
                    <td>{{ $a->route?->name ?? '—' }}</td>
                    <td class="text-end">₹{{ number_format((float) $a->fee_amount, 2) }}</td>
                    <td class="text-end {{ (float) $a->paid_amount > 0 ? 'text-success fw-medium' : 'text-muted' }}">
                        ₹{{ number_format((float) $a->paid_amount, 2) }}
                    </td>
                    <td class="text-end {{ (float) $a->balance > 0 ? 'text-danger fw-semibold' : 'text-success fw-medium' }}">
                        ₹{{ number_format(max(0, (float) $a->balance), 2) }}
                    </td>
                    <td class="text-center">
                        @if($a->already_billed)
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Charged</span>
                        @else
                            <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Pending</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        No semester allocations found for this session.<br>
                        <small>Assign routes with <strong>Per Semester</strong> billing to use this feature.</small>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- One-Time Allocations --}}
@if($oneTimeAllocations->count())
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-1-circle text-warning me-2"></i>One-Time Allocations
            <span class="badge bg-secondary ms-2">{{ $oneTimeAllocations->count() }} total</span>
            @php $otPending = $oneTimeAllocations->filter(fn($a) => $a->status !== 'paid')->count(); @endphp
            @if($otPending)
                <span class="badge bg-danger ms-1">{{ $otPending }} pending</span>
            @else
                <span class="badge bg-success ms-1">All collected</span>
            @endif
        </span>
        <small class="text-muted" style="font-size:12px;">Direct payment collection — partial payments allowed</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:14px;">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">#</th>
                    <th>Student</th>
                    <th>Route</th>
                    <th class="text-end">Fee</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Balance</th>
                    <th class="text-center">Status</th>
                    <th class="text-center pe-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($oneTimeAllocations as $i => $a)
                @php
                    $fee     = (float) $a->fee_amount;
                    $paid    = (float) $a->paid_amount;
                    $balance = round($fee - $paid, 2);
                @endphp
                <tr>
                    <td class="ps-3 text-muted">{{ $i + 1 }}</td>
                    <td>
                        <div class="fw-semibold">{{ $a->student?->name ?? '—' }}</div>
                        @if($a->student?->roll_no)
                            <small class="text-muted">{{ $a->student->roll_no }}</small>
                        @endif
                    </td>
                    <td>{{ $a->route?->name ?? '—' }}</td>
                    <td class="text-end">₹{{ number_format($fee, 2) }}</td>
                    <td class="text-end {{ $paid > 0 ? 'text-success fw-medium' : 'text-muted' }}">
                        ₹{{ number_format($paid, 2) }}
                    </td>
                    <td class="text-end {{ $balance > 0 ? 'text-danger fw-semibold' : 'text-success fw-medium' }}">
                        ₹{{ number_format($balance, 2) }}
                    </td>
                    <td class="text-center">
                        @if($a->status === 'paid')
                            <span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>Paid</span>
                        @elseif($a->status === 'partial')
                            <span class="badge text-bg-warning"><i class="bi bi-circle-half me-1"></i>Partial</span>
                        @else
                            <span class="badge text-bg-danger"><i class="bi bi-clock me-1"></i>Pending</span>
                        @endif
                    </td>
                    <td class="text-center pe-3">
                        @if($balance > 0)
                            <button type="button" class="btn btn-sm btn-primary px-3"
                                onclick="openCollectModal(
                                    {{ $a->id }},
                                    '{{ addslashes($a->student?->name ?? '') }}',
                                    '{{ addslashes($a->student?->roll_no ?? '') }}',
                                    '{{ addslashes($a->route?->name ?? '') }}',
                                    {{ $fee }},
                                    {{ $paid }},
                                    {{ $balance }},
                                    '{{ route('transport.billing.collect-one-time', $a) }}'
                                )">
                                <i class="bi bi-cash-coin me-1"></i>Collect
                            </button>
                        @else
                            <span class="text-success fw-medium" style="font-size:13px;"><i class="bi bi-check-circle me-1"></i>Done</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Collect Modal --}}
<div class="modal fade" id="collectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="collectForm">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold"><i class="bi bi-cash-coin me-2 text-primary"></i>Collect Transport Fee</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2">
                    <div class="bg-light rounded p-3 mb-3" style="font-size:13px;">
                        <div class="fw-semibold text-dark" id="cm-student"></div>
                        <div class="text-muted" id="cm-route"></div>
                        <div class="mt-2 d-flex justify-content-between">
                            <span class="text-muted">Total Fee</span>
                            <span class="fw-medium" id="cm-fee"></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Already Paid</span>
                            <span class="text-success fw-medium" id="cm-paid"></span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold">Balance Due</span>
                            <span class="fw-bold text-danger" id="cm-balance"></span>
                        </div>
                    </div>

                    <label class="form-label fw-medium" style="font-size:13px;">Amount to Collect <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" step="0.01" min="1" class="form-control" name="amount" id="cm-amount"
                            placeholder="Enter amount" required>
                    </div>
                    <div class="d-flex gap-2 mt-2 mb-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1" id="cm-partial-btn">Partial</button>
                        <button type="button" class="btn btn-outline-primary btn-sm flex-grow-1" id="cm-full-btn">Full Amount</button>
                    </div>

                    <label class="form-label fw-medium" style="font-size:13px;">Payment Mode <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm mb-2" name="payment_mode" id="cm-payment-mode" required>
                        <option value="cash">Cash</option>
                        <option value="upi">UPI</option>
                        <option value="online">Online Transfer</option>
                        <option value="cheque">Cheque</option>
                    </select>

                    <label class="form-label fw-medium" style="font-size:13px;">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control form-control-sm mb-2" name="payment_date" id="cm-payment-date"
                        value="{{ now()->toDateString() }}" required>

                    <div id="cm-ref-wrapper" style="display:none;">
                        <label class="form-label fw-medium" style="font-size:13px;">Reference No <small class="text-muted">(UTR / Cheque No)</small></label>
                        <input type="text" class="form-control form-control-sm mb-1" name="reference_no" id="cm-reference-no"
                            placeholder="Optional">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i>Collect</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var _collectBalance = 0;

function openCollectModal(id, name, roll, route, fee, paid, balance, actionUrl) {
    _collectBalance = balance;

    document.getElementById('cm-student').textContent  = name + (roll ? ' (' + roll + ')' : '');
    document.getElementById('cm-route').textContent    = route;
    document.getElementById('cm-fee').textContent      = '₹' + fee.toFixed(2);
    document.getElementById('cm-paid').textContent     = '₹' + paid.toFixed(2);
    document.getElementById('cm-balance').textContent  = '₹' + balance.toFixed(2);
    document.getElementById('cm-amount').value         = balance.toFixed(2);
    document.getElementById('cm-amount').max           = balance;
    document.getElementById('cm-payment-date').value   = new Date().toISOString().slice(0, 10);
    document.getElementById('cm-payment-mode').value   = 'cash';
    document.getElementById('cm-reference-no').value  = '';
    document.getElementById('cm-ref-wrapper').style.display = 'none';
    document.getElementById('collectForm').action      = actionUrl;

    new bootstrap.Modal(document.getElementById('collectModal')).show();
    setTimeout(() => document.getElementById('cm-amount').select(), 400);
}

document.getElementById('cm-full-btn')?.addEventListener('click', function () {
    document.getElementById('cm-amount').value = _collectBalance.toFixed(2);
});
document.getElementById('cm-partial-btn')?.addEventListener('click', function () {
    document.getElementById('cm-amount').value = '';
    document.getElementById('cm-amount').focus();
});

document.getElementById('cm-payment-mode')?.addEventListener('change', function () {
    document.getElementById('cm-ref-wrapper').style.display =
        ['upi', 'online', 'cheque'].includes(this.value) ? '' : 'none';
});

document.getElementById('collectForm')?.addEventListener('submit', function (e) {
    const amt = parseFloat(document.getElementById('cm-amount').value);
    if (!amt || amt <= 0) {
        e.preventDefault();
        document.getElementById('cm-amount').classList.add('is-invalid');
        return;
    }
    if (amt > _collectBalance + 0.001) {
        e.preventDefault();
        document.getElementById('cm-amount').classList.add('is-invalid');
        showToast('Amount cannot exceed balance ₹' + _collectBalance.toFixed(2), 'danger');
        return;
    }
    document.getElementById('cm-amount').classList.remove('is-invalid');
});
</script>
@endsection
