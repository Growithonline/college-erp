<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Student *</label>
        <select class="form-select" name="student_id" required>
            <option value="">Select Student</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->name }}{{ $student->roll_no ? ' (' . $student->roll_no . ')' : '' }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Academic Session *</label>
        <select class="form-select" name="academic_session_id" required>
            <option value="">Select Session</option>
            @foreach($sessions as $session)
                <option value="{{ $session->id }}" @selected(old('academic_session_id') == $session->id)>{{ $session->name ?? ('Session ' . $session->id) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Route *</label>
        <select class="form-select" name="transport_route_id" id="routeSelect" required>
            <option value="">Select Route</option>
            @foreach($routes as $route)
                <option value="{{ $route->id }}"
                    @selected(old('transport_route_id') == $route->id)
                    data-fee="{{ $route->fee_amount }}">
                    {{ $route->name }} (₹{{ number_format((float) $route->fee_amount, 2) }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Stop <small class="text-muted">(fee auto-fills if set)</small></label>
        <select class="form-select" name="transport_route_stop_id" id="stopSelect">
            <option value="">No Stop / Select Stop</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Vehicle</label>
        <select class="form-select" name="transport_vehicle_id">
            <option value="">Select Vehicle</option>
            @foreach($vehicles as $vehicle)
                <option value="{{ $vehicle->id }}" @selected(old('transport_vehicle_id') == $vehicle->id)>{{ $vehicle->vehicle_no }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Driver</label>
        <select class="form-select" name="transport_driver_id">
            <option value="">Select Driver</option>
            @foreach($drivers as $driver)
                <option value="{{ $driver->id }}" @selected(old('transport_driver_id') == $driver->id)>{{ $driver->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Fee Amount <small class="text-muted">(auto from stop/route)</small></label>
        <input type="number" step="0.01" min="0" class="form-control" name="fee_amount" id="feeAmountInput" value="{{ old('fee_amount') }}">
    </div>
    <div class="col-md-3"><label class="form-label">Start Date *</label><input type="date" class="form-control" name="start_date" value="{{ old('start_date', now()->toDateString()) }}" required></div>
    <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="charge_now" value="1" {{ old('charge_now', true) ? 'checked' : '' }}><label class="form-check-label">Charge wallet now</label></div></div>
    <div class="col-12"><label class="form-label">Remarks</label><textarea class="form-control" name="remarks" rows="3">{{ old('remarks') }}</textarea></div>
</div>
<div class="mt-4 d-flex justify-content-end">
    <button class="btn btn-primary">Save Allocation</button>
</div>

<script>
(() => {
    const routeSelect   = document.getElementById('routeSelect');
    const stopSelect    = document.getElementById('stopSelect');
    const feeInput      = document.getElementById('feeAmountInput');

    if (!routeSelect || !stopSelect || !feeInput) return;

    const oldRouteId = "{{ old('transport_route_id') }}";
    const oldStopId  = "{{ old('transport_route_stop_id') }}";

    function loadStops(routeId, preselectStopId) {
        stopSelect.innerHTML = '<option value="">No Stop / Select Stop</option>';
        if (!routeId) return;

        fetch(`/transport/routes/${routeId}/stops`)
            .then(r => r.json())
            .then(data => {
                (data.stops ?? []).forEach(stop => {
                    const opt = document.createElement('option');
                    opt.value = stop.id;
                    opt.dataset.fee = stop.fee_amount;
                    const feeLabel = stop.fee_amount > 0
                        ? ` — ₹${parseFloat(stop.fee_amount).toFixed(2)}`
                        : '';
                    opt.textContent = `${stop.stop_name}${feeLabel}`;
                    if (String(stop.id) === String(preselectStopId)) opt.selected = true;
                    stopSelect.appendChild(opt);
                });
                updateFee();
            });
    }

    function updateFee() {
        const selectedStop = stopSelect.options[stopSelect.selectedIndex];
        const stopFee = parseFloat(selectedStop?.dataset?.fee ?? 0);

        if (stopFee > 0) {
            feeInput.value = stopFee.toFixed(2);
            return;
        }

        // Fall back to route fee
        const selectedRoute = routeSelect.options[routeSelect.selectedIndex];
        const routeFee = parseFloat(selectedRoute?.dataset?.fee ?? 0);
        if (routeFee > 0) feeInput.value = routeFee.toFixed(2);
    }

    routeSelect.addEventListener('change', () => {
        loadStops(routeSelect.value, null);
        updateFee();
    });

    stopSelect.addEventListener('change', updateFee);

    // On page load — restore old selections (validation failure redirect)
    if (oldRouteId) {
        loadStops(oldRouteId, oldStopId);
    }
})();
</script>
