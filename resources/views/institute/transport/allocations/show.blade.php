@extends('institute.layout')
@section('title', 'Allocation Details')
@section('breadcrumb', 'Transport / Allocations / Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">{{ $allocation->student?->name ?? 'Student' }}</h4>
        <small class="text-muted">{{ $allocation->route?->name ?? 'Route' }} | {{ $allocation->stop?->stop_name ?? 'No stop' }}</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('transport.allocations.pdf', $allocation) }}" class="btn btn-outline-danger btn-sm" target="_blank">
            <i class="bi bi-file-earmark-pdf me-1"></i>Download PDF
        </a>
        <a href="{{ route('transport.allocations.pass', $allocation) }}" class="btn btn-outline-secondary btn-sm" target="_blank">
            <i class="bi bi-eye me-1"></i>View Pass
        </a>
        <a href="{{ route('transport.allocations.pass', ['allocation' => $allocation, 'print' => 1]) }}" class="btn btn-outline-primary btn-sm" target="_blank">
            <i class="bi bi-qr-code me-1"></i>Print Pass
        </a>
        @if($allocation->is_active)
        <a href="{{ route('transport.allocations.edit', $allocation) }}" class="btn btn-outline-primary btn-sm">Edit</a>
        <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#transferModal">Change Route</button>
        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">Cancel Transport</button>
        @endif
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Allocation Info</span>
                @if($allocation->is_active)
                    <span class="badge bg-success">Active</span>
                @elseif($allocation->status === 'closed')
                    <span class="badge bg-secondary">Closed</span>
                @else
                    <span class="badge bg-warning text-dark">{{ ucfirst($allocation->status) }}</span>
                @endif
            </div>
            <div class="card-body small">
                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                    <span class="text-muted">Session</span>
                    <span class="fw-semibold">{{ $allocation->session?->name ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                    <span class="text-muted">Start Date</span>
                    <span class="fw-semibold">{{ $allocation->start_date?->format('d M Y') ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                    <span class="text-muted">Billing</span>
                    <span class="fw-semibold">{{ $allocation->route?->billing_frequency ? ucfirst(str_replace('_', ' ', $allocation->route->billing_frequency)) : '—' }}</span>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                    <span class="text-muted">Vehicle</span>
                    <span class="fw-semibold">{{ $allocation->vehicle?->vehicle_no ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between {{ $allocation->remarks ? 'border-bottom pb-2 mb-2' : 'mb-0' }}">
                    <span class="text-muted">Driver</span>
                    <span class="fw-semibold">{{ $allocation->driver?->name ?? '—' }}</span>
                </div>
                @if($allocation->remarks)
                <div class="text-muted fst-italic mb-0" style="font-size:12px;">{{ $allocation->remarks }}</div>
                @endif

                <div class="rounded p-3 mt-3 {{ $allocation->balance > 0 ? 'bg-danger-subtle' : 'bg-success-subtle' }}">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Fee Charged</span>
                        <span class="fw-semibold">₹{{ number_format($allocation->effective_charged, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Paid</span>
                        <span class="fw-semibold text-success">₹{{ number_format((float) $allocation->paid_amount, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between pt-1 border-top">
                        <span class="fw-bold">Due</span>
                        <span class="fw-bold {{ $allocation->balance > 0 ? 'text-danger' : 'text-success' }}" style="font-size:16px;">
                            ₹{{ number_format($allocation->balance, 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        @if($allocation->balance > 0)
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#collectPaymentModal">
                    <i class="bi bi-cash-coin me-1"></i>Collect Payment (₹{{ number_format($allocation->balance, 2) }} due)
                </button>
                <a href="{{ route('fee.create', ['student_id' => $allocation->student_id]) }}" class="btn btn-link btn-sm w-100 mt-1">
                    or collect together with other dues via Fee Collection
                </a>
            </div>
        </div>
        @endif
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Payments</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Mode</th>
                            <th>Reference</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                                <td>{{ ucfirst($payment->payment_mode) }}</td>
                                <td>{{ $payment->reference_no ?? '—' }}</td>
                                <td class="fw-semibold text-success">₹{{ number_format((float) $payment->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">No payments recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
{{-- Route Change History --}}
@if($history->count() > 1)
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-arrow-left-right me-2 text-warning"></i>Route Change History
        <span class="badge bg-secondary ms-2">{{ $history->count() }} allocations</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Session</th>
                    <th>Route</th>
                    <th>Stop</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th class="text-end">Fee</th>
                    <th class="text-end">Paid</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($history as $i => $h)
                <tr class="{{ $h->id === $allocation->id ? 'table-primary fw-semibold' : '' }}">
                    <td>{{ $i + 1 }}</td>
                    <td><small>{{ $h->session?->name ?? '—' }}</small></td>
                    <td>
                        {{ $h->route?->name ?? '—' }}
                        @if($h->route?->route_code)
                            <small class="text-muted">({{ $h->route->route_code }})</small>
                        @endif
                    </td>
                    <td><small>{{ $h->stop?->stop_name ?? '—' }}</small></td>
                    <td><small>{{ $h->start_date?->format('d M Y') ?? '—' }}</small></td>
                    <td><small>{{ $h->end_date?->format('d M Y') ?? '—' }}</small></td>
                    <td class="text-end small">₹{{ number_format((float) $h->fee_amount, 2) }}</td>
                    <td class="text-end small text-success">₹{{ number_format((float) $h->paid_amount, 2) }}</td>
                    <td class="text-center">
                        @if($h->is_active)
                            <span class="badge bg-success">Active</span>
                        @elseif($h->status === 'closed')
                            <span class="badge bg-secondary">Closed</span>
                        @else
                            <span class="badge bg-warning text-dark">{{ ucfirst($h->status) }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Collect Payment Modal — works for closed allocations with a residual due too, so it
     lives outside the is_active guard that wraps the Transfer/Cancel modals below. --}}
@if($allocation->balance > 0)
<div class="modal fade" id="collectPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold"><i class="bi bi-cash-coin me-2 text-primary"></i>Collect Transport Payment</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('transport.allocations.collect-payment', $allocation) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Amount (₹) — Max ₹{{ number_format($allocation->balance, 2) }}</label>
                        <input type="number" step="0.01" min="0.01" max="{{ $allocation->balance }}" name="amount"
                               class="form-control" value="{{ number_format($allocation->balance, 2, '.', '') }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Payment Date</label>
                        <input type="date" name="payment_date" class="form-control"
                               value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Payment Mode</label>
                        <select name="payment_mode" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="upi">UPI</option>
                            <option value="online">Online</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Reference No (optional)</label>
                        <input type="text" name="reference_no" class="form-control" maxlength="100">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Note (optional)</label>
                        <textarea name="note" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-5">
                        <i class="bi bi-cash-coin me-1"></i> Collect Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Route Transfer Modal --}}
@if($allocation->is_active)
@php
    $oldBalance      = (float) $allocation->balance;
    $oldCharged      = $allocation->effective_charged;
    $oldPaid         = (float) $allocation->paid_amount;
    $allocStart      = $allocation->start_date;
    $today           = now()->startOfDay();

    // Prorated suggestion: unused portion of charged fee, based on the institute's
    // configured semester length (NOT the academic session's dates — a session spans
    // an entire academic year, not a single semester).
    $semesterMonths  = max(1, (int) $setting->semester_duration_months);
    $semesterEnd     = $allocStart ? $allocStart->copy()->addMonths($semesterMonths) : null;
    $suggestedCredit = $setting->proratedUnusedAmount($allocation, $today);
@endphp
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold"><i class="bi bi-arrow-left-right me-2 text-warning"></i>Change Route</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('transport.allocations.transfer', $allocation) }}">
                @csrf
                <div class="modal-body">

                    @include('institute.transport.allocations._allocation-summary')

                    @include('institute.transport.allocations._credit-note', [
                        'heading' => 'Credit Note on Old Route',
                        'inputId' => 'creditAmountInput',
                    ])

                    <hr class="my-3">

                    {{-- New Route Section --}}
                    <div class="fw-semibold text-muted mb-3" style="font-size:11px; letter-spacing:.05em; text-transform:uppercase;">New Route Details</div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">New Route <span class="text-danger">*</span></label>
                            <select class="form-select" name="transport_route_id" id="transferRouteSelect" required>
                                <option value="">Select Route</option>
                                @foreach($routes as $r)
                                    <option value="{{ $r->id }}" data-fee="{{ $r->fee_amount }}">{{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="transferStartDate" class="form-control"
                                   value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stop</label>
                            <select class="form-select" name="transport_route_stop_id" id="transferStopSelect">
                                <option value="">No Stop</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Route Fee</label>
                            <input type="number" step="0.01" min="0" name="fee_amount"
                                   id="transferFeeInput" class="form-control" placeholder="Auto-filled on route select">
                        </div>
                    </div>

                    {{-- Net Summary --}}
                    <div class="mt-3 p-3 rounded bg-light" id="transferSummary" style="font-size:13px; display:none;">
                        <div class="fw-semibold mb-2">Transfer Summary</div>
                        <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                            <span class="text-muted">Old Route Outstanding</span>
                            <span class="text-danger">₹{{ number_format($oldBalance, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                            <span class="text-muted">Credit Note Applied</span>
                            <span class="text-success" id="summaryCredit">₹0.00</span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                            <span class="text-muted">Remaining Old Balance After Credit</span>
                            <span class="text-danger fw-semibold" id="summaryOldRemain">₹{{ number_format($oldBalance, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                            <span class="text-muted">New Route Fee</span>
                            <span id="summaryNewFee">₹0.00</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total Payable After Transfer</span>
                            <span class="text-danger" id="summaryTotal">₹{{ number_format($oldBalance, 2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-5">
                        <i class="bi bi-arrow-left-right me-1"></i> Confirm Transfer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Cancel Transport Modal --}}
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold"><i class="bi bi-x-circle me-2 text-danger"></i>Cancel Transport</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('transport.allocations.close', $allocation) }}">
                @csrf
                <div class="modal-body">

                    @include('institute.transport.allocations._allocation-summary')

                    <div class="mb-3">
                        <label class="form-label">Cancellation Date <span class="text-danger">*</span></label>
                        <input type="date" name="cancellation_date" id="cancelDateInput" class="form-control"
                               value="{{ $today->toDateString() }}"
                               min="{{ $allocStart?->toDateString() }}"
                               max="{{ $today->toDateString() }}" required>
                        <small class="text-muted">The last day this student actually used the route. Can be backdated.</small>
                    </div>

                    @include('institute.transport.allocations._credit-note', [
                        'heading' => 'Credit Note',
                        'inputId' => 'cancelCreditAmountInput',
                    ])

                    <div class="alert alert-warning py-2 px-3 mb-0" style="font-size:12px;">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        This allocation will be closed and no further transport fee will be charged against it.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Back</button>
                    <button type="submit" class="btn btn-danger px-5">
                        <i class="bi bi-x-circle me-1"></i> Confirm Cancellation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(() => {
    const proratedCreditUrl = '{{ route('transport.allocations.prorated-credit', $allocation) }}';

    // The suggested credit note is only correct for whatever date it was computed
    // against. Cancellation Date / Transfer Start Date can be backdated, so re-fetch
    // the suggestion from the server (single source of truth: proratedUnusedAmount())
    // whenever the date changes, instead of leaving it pinned to page-load's "today".
    function refreshSuggestedCredit(asOfDate, inputEl, onDone) {
        if (!asOfDate || !inputEl) return;
        fetch(`${proratedCreditUrl}?as_of=${encodeURIComponent(asOfDate)}`, {
            headers: { 'Accept': 'application/json' },
        })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (!data) return;
                inputEl.value = Number(data.suggested_credit).toFixed(2);
                if (onDone) onDone();
            })
            .catch(() => {});
    }

    const cancelDateInput   = document.getElementById('cancelDateInput');
    const cancelCreditInput = document.getElementById('cancelCreditAmountInput');
    cancelDateInput?.addEventListener('change', () => {
        refreshSuggestedCredit(cancelDateInput.value, cancelCreditInput);
    });

    const routeSel    = document.getElementById('transferRouteSelect');
    const stopSel     = document.getElementById('transferStopSelect');
    const feeInput    = document.getElementById('transferFeeInput');
    const creditInput = document.getElementById('creditAmountInput');
    const summary     = document.getElementById('transferSummary');
    const transferStartDate = document.getElementById('transferStartDate');
    const oldBalance  = {{ $oldBalance }};

    transferStartDate?.addEventListener('change', () => {
        refreshSuggestedCredit(transferStartDate.value, creditInput, updateSummary);
    });

    if (!routeSel) return;

    function updateSummary() {
        const credit  = Math.min(Math.max(0, parseFloat(creditInput?.value ?? 0) || 0), oldBalance);
        const newFee  = parseFloat(feeInput?.value ?? 0) || 0;
        const oldRemain = Math.max(0, oldBalance - credit);
        const total   = oldRemain + newFee;

        if (document.getElementById('summaryCredit'))     document.getElementById('summaryCredit').textContent     = '₹' + credit.toFixed(2);
        if (document.getElementById('summaryOldRemain'))  document.getElementById('summaryOldRemain').textContent  = '₹' + oldRemain.toFixed(2);
        if (document.getElementById('summaryNewFee'))     document.getElementById('summaryNewFee').textContent     = '₹' + newFee.toFixed(2);
        if (document.getElementById('summaryTotal'))      document.getElementById('summaryTotal').textContent      = '₹' + total.toFixed(2);

        if (summary && routeSel.value) summary.style.display = '';
    }

    routeSel.addEventListener('change', () => {
        const opt = routeSel.options[routeSel.selectedIndex];
        feeInput.value = parseFloat(opt?.dataset?.fee ?? 0).toFixed(2);
        stopSel.innerHTML = '<option value="">No Stop</option>';

        if (!routeSel.value) { if (summary) summary.style.display = 'none'; return; }

        fetch(`/transport/routes/${routeSel.value}/stops`)
            .then(r => r.json())
            .then(data => {
                (data.stops ?? []).forEach(s => {
                    const o = document.createElement('option');
                    o.value = s.id;
                    o.dataset.fee = s.fee_amount;
                    o.textContent = s.stop_name + (s.fee_amount > 0 ? ` — ₹${parseFloat(s.fee_amount).toFixed(2)}` : '');
                    stopSel.appendChild(o);
                });
            });

        updateSummary();
    });

    stopSel.addEventListener('change', () => {
        const opt = stopSel.options[stopSel.selectedIndex];
        const fee = parseFloat(opt?.dataset?.fee ?? 0);
        if (fee > 0) { feeInput.value = fee.toFixed(2); updateSummary(); }
    });

    feeInput?.addEventListener('input', updateSummary);
    creditInput?.addEventListener('input', updateSummary);
})();
</script>
@endif
@endsection
