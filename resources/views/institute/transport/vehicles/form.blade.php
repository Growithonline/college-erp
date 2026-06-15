@php($vehicle = $vehicle ?? null)
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">Vehicle Type</label>
        <select class="form-select" name="transport_vehicle_type_id" id="vehicleTypeSelect">
            <option value="">— Select Type —</option>
            @foreach($vehicleTypes ?? [] as $vt)
                <option value="{{ $vt->id }}"
                    data-capacity="{{ $vt->default_capacity }}"
                    {{ old('transport_vehicle_type_id', $vehicle->transport_vehicle_type_id ?? '') == $vt->id ? 'selected' : '' }}>
                    {{ $vt->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3"><label class="form-label">Vehicle No *</label><input class="form-control" name="vehicle_no" value="{{ old('vehicle_no', $vehicle->vehicle_no ?? '') }}" required></div>
    <div class="col-md-3"><label class="form-label">Registration No</label><input class="form-control" name="registration_no" value="{{ old('registration_no', $vehicle->registration_no ?? '') }}"></div>
    <div class="col-md-3"><label class="form-label">Model</label><input class="form-control" name="model" value="{{ old('model', $vehicle->model ?? '') }}"></div>
    <div class="col-md-3"><label class="form-label">Capacity</label><input type="number" min="0" class="form-control" name="capacity" id="capacityInput" value="{{ old('capacity', $vehicle->capacity ?? 0) }}"></div>
    <div class="col-md-3"><label class="form-label">Fuel Type</label><input class="form-control" name="fuel_type" value="{{ old('fuel_type', $vehicle->fuel_type ?? '') }}"></div>
    <div class="col-md-3"><label class="form-label">Insurance Expiry</label><input type="date" class="form-control" name="insurance_expiry" value="{{ old('insurance_expiry', optional($vehicle?->insurance_expiry)->format('Y-m-d')) }}"></div>
    <div class="col-md-3"><label class="form-label">Permit Expiry</label><input type="date" class="form-control" name="permit_expiry" value="{{ old('permit_expiry', optional($vehicle?->permit_expiry)->format('Y-m-d')) }}"></div>
    <div class="col-md-3"><label class="form-label">Fitness Expiry</label><input type="date" class="form-control" name="fitness_expiry" value="{{ old('fitness_expiry', optional($vehicle?->fitness_expiry)->format('Y-m-d')) }}"></div>
    <div class="col-md-3"><label class="form-label">Pollution Expiry</label><input type="date" class="form-control" name="pollution_expiry" value="{{ old('pollution_expiry', optional($vehicle?->pollution_expiry)->format('Y-m-d')) }}"></div>
    <div class="col-md-3"><label class="form-label">Service Due Date</label><input type="date" class="form-control" name="service_due_date" value="{{ old('service_due_date', optional($vehicle?->service_due_date)->format('Y-m-d')) }}"></div>
    <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="status" value="1" {{ old('status', $vehicle->status ?? true) ? 'checked' : '' }}><label class="form-check-label">Active</label></div></div>
    <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="3">{{ old('notes', $vehicle->notes ?? '') }}</textarea></div>
</div>
<div class="mt-4 d-flex justify-content-end">
    <button class="btn btn-primary">Save Vehicle</button>
</div>

<script>
(() => {
    const typeSelect     = document.getElementById('vehicleTypeSelect');
    const capacityInput  = document.getElementById('capacityInput');
    if (!typeSelect || !capacityInput) return;

    typeSelect.addEventListener('change', () => {
        const opt = typeSelect.options[typeSelect.selectedIndex];
        const cap = parseInt(opt?.dataset?.capacity ?? '0', 10);
        if (cap > 0) capacityInput.value = cap;
    });
})();
</script>
