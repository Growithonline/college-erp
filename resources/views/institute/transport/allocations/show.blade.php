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
        <a href="{{ route('transport.allocations.pass', $allocation) }}" class="btn btn-outline-primary btn-sm" target="_blank">
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
            <div class="card-header bg-white fw-semibold">Allocation Info</div>
            <div class="card-body small">
                <div class="mb-2"><span class="text-muted">Fee:</span> ₹{{ number_format((float) $allocation->fee_amount, 2) }}</div>
                <div class="mb-2"><span class="text-muted">Paid:</span> ₹{{ number_format((float) $allocation->paid_amount, 2) }}</div>
                <div class="mb-2"><span class="text-muted">Due:</span> ₹{{ number_format($allocation->balance, 2) }}</div>
                <div class="mb-2"><span class="text-muted">Vehicle:</span> {{ $allocation->vehicle?->vehicle_no ?? '—' }}</div>
                <div class="mb-2"><span class="text-muted">Driver:</span> {{ $allocation->driver?->name ?? '—' }}</div>
                <div class="mb-2"><span class="text-muted">Status:</span> {{ ucfirst($allocation->status) }}</div>
            </div>
        </div>

        @if($allocation->balance > 0)
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="alert alert-info mb-0 small">
                    <strong>To collect the transport fee,</strong> use the Fee Collection page.<br>
                    Search for the student — the transport fee will appear automatically as a line item.
                </div>
                <a href="{{ route('fee.create', ['student_id' => $allocation->student_id]) }}" class="btn btn-primary w-100 mt-2">
                    Go to Fee Collection
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
    const routeSel    = document.getElementById('transferRouteSelect');
    const stopSel     = document.getElementById('transferStopSelect');
    const feeInput    = document.getElementById('transferFeeInput');
    const creditInput = document.getElementById('creditAmountInput');
    const summary     = document.getElementById('transferSummary');
    const oldBalance  = {{ $oldBalance }};

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
