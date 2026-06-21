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
        @if($allocation->is_active)
        <a href="{{ route('transport.allocations.edit', $allocation) }}" class="btn btn-outline-primary btn-sm">Edit</a>
        <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#transferModal">Change Route</button>
        <form method="POST" action="{{ route('transport.allocations.close', $allocation) }}"
            onsubmit="return confirm('Close this allocation?')">
            @csrf
            <button class="btn btn-outline-secondary btn-sm">Close</button>
        </form>
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
                    <strong>Transport fee collect karne ke liye</strong> main Fee Collection page use karein.<br>
                    Student search karein — Transport Fee automatically line item mein aayegi.
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
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Change Route</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('transport.allocations.transfer', $allocation) }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning small">Current allocation will be closed. A new allocation will be created on the selected route.</div>
                    <div class="mb-3">
                        <label class="form-label">New Route *</label>
                        <select class="form-select" name="transport_route_id" id="transferRouteSelect" required>
                            <option value="">Select Route</option>
                            @foreach($routes as $r)
                                <option value="{{ $r->id }}" data-fee="{{ $r->fee_amount }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stop</label>
                        <select class="form-select" name="transport_route_stop_id" id="transferStopSelect">
                            <option value="">No Stop</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fee Amount</label>
                        <input type="number" step="0.01" min="0" name="fee_amount" id="transferFeeInput" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Start Date *</label>
                        <input type="date" name="start_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">Transfer Route</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(() => {
    const routeSel = document.getElementById('transferRouteSelect');
    const stopSel  = document.getElementById('transferStopSelect');
    const feeInput = document.getElementById('transferFeeInput');
    if (!routeSel) return;

    routeSel.addEventListener('change', () => {
        const opt = routeSel.options[routeSel.selectedIndex];
        feeInput.value = parseFloat(opt?.dataset?.fee ?? 0).toFixed(2);
        stopSel.innerHTML = '<option value="">No Stop</option>';

        if (!routeSel.value) return;
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
    });

    stopSel.addEventListener('change', () => {
        const opt = stopSel.options[stopSel.selectedIndex];
        const fee = parseFloat(opt?.dataset?.fee ?? 0);
        if (fee > 0) feeInput.value = fee.toFixed(2);
    });
})();
</script>
@endif
@endsection
