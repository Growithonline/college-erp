@extends('institute.layout')
@section('title', 'Bulk Allocation')
@section('breadcrumb', 'Transport / Allocations / Bulk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Bulk Transport Allocation</h4>
    <a href="{{ route('transport.allocations.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('transport.allocations.bulk-store') }}">
    @csrf
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Route & Vehicle Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Session *</label>
                    <select class="form-select" name="academic_session_id" required>
                        <option value="">Select</option>
                        @foreach($sessions as $s)
                            <option value="{{ $s->id }}" {{ $s->is_active ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Route *</label>
                    <select class="form-select" name="transport_route_id" id="bulkRouteSelect" required>
                        <option value="">Select Route</option>
                        @foreach($routes as $r)
                            <option value="{{ $r->id }}" data-fee="{{ $r->fee_amount }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Stop <small class="text-muted">(optional)</small></label>
                    <select class="form-select" name="transport_route_stop_id" id="bulkStopSelect">
                        <option value="">No Stop</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fee Amount</label>
                    <input type="number" step="0.01" min="0" name="fee_amount" id="bulkFeeInput" class="form-control" placeholder="Auto from stop/route">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Vehicle</label>
                    <select class="form-select" name="transport_vehicle_id">
                        <option value="">None</option>
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}">{{ $v->vehicle_no }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Driver</label>
                    <select class="form-select" name="transport_driver_id">
                        <option value="">None</option>
                        @foreach($drivers as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start Date *</label>
                    <input type="date" name="start_date" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Select Students ({{ $students->count() }} without active allocation)</span>
            <div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAll">Select All</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">Deselect All</button>
            </div>
        </div>
        <div class="card-body p-0" style="max-height:420px;overflow-y:auto;">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light sticky-top">
                    <tr>
                        <th style="width:40px;"></th>
                        <th>Name</th>
                        <th>Roll No</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $s)
                    <tr>
                        <td><input type="checkbox" class="form-check-input student-check" name="student_ids[]" value="{{ $s->id }}"></td>
                        <td>{{ $s->name }}</td>
                        <td class="text-muted">{{ $s->roll_no ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center py-4 text-muted">All students already have active allocations.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-end">
        <button class="btn btn-primary">Allocate Selected Students</button>
    </div>
</form>

<script>
(() => {
    const routeSel = document.getElementById('bulkRouteSelect');
    const stopSel  = document.getElementById('bulkStopSelect');
    const feeInput = document.getElementById('bulkFeeInput');

    routeSel?.addEventListener('change', () => {
        const opt = routeSel.options[routeSel.selectedIndex];
        const fee = parseFloat(opt?.dataset?.fee ?? 0);
        if (fee > 0) feeInput.value = fee.toFixed(2);
        stopSel.innerHTML = '<option value="">No Stop</option>';
        if (!routeSel.value) return;
        fetch(`/transport/routes/${routeSel.value}/stops`)
            .then(r => r.json())
            .then(data => {
                (data.stops ?? []).forEach(s => {
                    const o = document.createElement('option');
                    o.value = s.id; o.dataset.fee = s.fee_amount;
                    o.textContent = s.stop_name + (s.fee_amount > 0 ? ` — ₹${parseFloat(s.fee_amount).toFixed(2)}` : '');
                    stopSel.appendChild(o);
                });
            });
    });

    stopSel?.addEventListener('change', () => {
        const fee = parseFloat(stopSel.options[stopSel.selectedIndex]?.dataset?.fee ?? 0);
        if (fee > 0) feeInput.value = fee.toFixed(2);
    });

    document.getElementById('selectAll')?.addEventListener('click', () =>
        document.querySelectorAll('.student-check').forEach(c => c.checked = true));
    document.getElementById('deselectAll')?.addEventListener('click', () =>
        document.querySelectorAll('.student-check').forEach(c => c.checked = false));
})();
</script>
@endsection
