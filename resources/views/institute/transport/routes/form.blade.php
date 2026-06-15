@php
    $route = $route ?? null;
    $isEdit = isset($route) && $route?->exists;
    $formAction = $isEdit
        ? route('transport.routes.update', $route)
        : route('transport.routes.store');

    $existingStops = collect(old('stops'))->whenEmpty(function () use ($route) {
        return collect($route?->stops ?? []);
    })->map(function ($stop) {
        if (is_array($stop)) {
            return [
                'stop_name'   => $stop['stop_name'] ?? '',
                'landmark'    => $stop['landmark'] ?? '',
                'sequence'    => $stop['sequence'] ?? '',
                'pickup_time' => $stop['pickup_time'] ?? '',
                'drop_time'   => $stop['drop_time'] ?? '',
                'fee_amount'  => $stop['fee_amount'] ?? '',
            ];
        }
        return [
            'stop_name'   => $stop->stop_name ?? '',
            'landmark'    => $stop->landmark ?? '',
            'sequence'    => $stop->sequence ?? '',
            'pickup_time' => $stop->pickup_time ? \Carbon\Carbon::parse($stop->pickup_time)->format('H:i') : '',
            'drop_time'   => $stop->drop_time ? \Carbon\Carbon::parse($stop->drop_time)->format('H:i') : '',
            'fee_amount'  => $stop->fee_amount ?? '',
        ];
    })->values()->all();

    $stopCount = max(5, count($existingStops));
@endphp

<form method="POST" action="{{ $formAction }}" autocomplete="off">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Route Code *</label>
            <input type="text" name="route_code" value="{{ old('route_code', $route->route_code ?? '') }}" class="form-control @error('route_code') is-invalid @enderror" required>
            @error('route_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-5">
            <label class="form-label">Route Name *</label>
            <input type="text" name="name" value="{{ old('name', $route->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Fee Amount</label>
            <input type="number" step="0.01" min="0" name="fee_amount" value="{{ old('fee_amount', $route->fee_amount ?? 0) }}" class="form-control @error('fee_amount') is-invalid @enderror">
            @error('fee_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Billing</label>
            <select name="billing_frequency" class="form-select">
                @foreach(['one_time' => 'One Time', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'semester' => 'Per Semester'] as $val => $label)
                    <option value="{{ $val }}" {{ old('billing_frequency', $route->billing_frequency ?? 'one_time') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">Start Point</label>
            <input type="text" name="start_point" value="{{ old('start_point', $route->start_point ?? '') }}" class="form-control @error('start_point') is-invalid @enderror">
            @error('start_point')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">End Point</label>
            <input type="text" name="end_point" value="{{ old('end_point', $route->end_point ?? '') }}" class="form-control @error('end_point') is-invalid @enderror">
            @error('end_point')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Distance (KM)</label>
            <input type="number" step="0.01" min="0" name="distance_km" value="{{ old('distance_km', $route->distance_km ?? '') }}" class="form-control @error('distance_km') is-invalid @enderror">
            @error('distance_km')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <div class="form-check">
                <input type="hidden" name="status" value="0">
                <input class="form-check-input" type="checkbox" name="status" value="1" id="routeStatus" {{ old('status', $route->status ?? 1) ? 'checked' : '' }}>
                <label class="form-check-label" for="routeStatus">Active</label>
            </div>
        </div>

        <div class="col-md-3">
            <label class="form-label">Morning Time</label>
            <input type="time" name="morning_time" value="{{ old('morning_time', !empty($route->morning_time) ? \Carbon\Carbon::parse($route->morning_time)->format('H:i') : '') }}" class="form-control @error('morning_time') is-invalid @enderror">
            @error('morning_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Evening Time</label>
            <input type="time" name="evening_time" value="{{ old('evening_time', !empty($route->evening_time) ? \Carbon\Carbon::parse($route->evening_time)->format('H:i') : '') }}" class="form-control @error('evening_time') is-invalid @enderror">
            @error('evening_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Notes</label>
            <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $route->notes ?? '') }}</textarea>
            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="mt-4 d-flex align-items-center justify-content-between">
        <h6 class="mb-0">Route Stops</h6>
        <button type="button" class="btn btn-outline-primary btn-sm" id="addTransportStopRow">Add Stop</button>
    </div>

    <div class="table-responsive mt-3">
        <table class="table table-bordered align-middle" id="transportStopsTable">
            <thead>
                <tr>
                    <th>Stop Name</th>
                    <th>Landmark</th>
                    <th style="width: 90px;">Seq</th>
                    <th style="width: 130px;">Fee (₹)</th>
                    <th style="width: 130px;">Pickup</th>
                    <th style="width: 130px;">Drop</th>
                    <th style="width: 70px;"></th>
                </tr>
            </thead>
            <tbody>
                @for($index = 0; $index < $stopCount; $index++)
                    @php $stop = $existingStops[$index] ?? []; @endphp
                    <tr>
                        <td><input type="text" name="stops[{{ $index }}][stop_name]" value="{{ $stop['stop_name'] ?? '' }}" class="form-control"></td>
                        <td><input type="text" name="stops[{{ $index }}][landmark]" value="{{ $stop['landmark'] ?? '' }}" class="form-control"></td>
                        <td><input type="number" name="stops[{{ $index }}][sequence]" value="{{ $stop['sequence'] ?? $index + 1 }}" class="form-control"></td>
                        <td><input type="number" step="0.01" min="0" name="stops[{{ $index }}][fee_amount]" value="{{ $stop['fee_amount'] ?? '' }}" class="form-control" placeholder="0.00"></td>
                        <td><input type="time" name="stops[{{ $index }}][pickup_time]" value="{{ $stop['pickup_time'] ?? '' }}" class="form-control"></td>
                        <td><input type="time" name="stops[{{ $index }}][drop_time]" value="{{ $stop['drop_time'] ?? '' }}" class="form-control"></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-stop-row">&#215;</button>
                        </td>
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>
    <small class="text-muted">Blank rows are ignored. Add at least one stop if you want route-wise pickup mapping.</small>

    <div class="mt-4 d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update Route' : 'Save Route' }}</button>
    </div>
</form>

<template id="transportStopRowTemplate">
    <tr>
        <td><input type="text" name="stops[__INDEX__][stop_name]" class="form-control"></td>
        <td><input type="text" name="stops[__INDEX__][landmark]" class="form-control"></td>
        <td><input type="number" name="stops[__INDEX__][sequence]" value="__SEQ__" class="form-control"></td>
        <td><input type="number" step="0.01" min="0" name="stops[__INDEX__][fee_amount]" class="form-control" placeholder="0.00"></td>
        <td><input type="time" name="stops[__INDEX__][pickup_time]" class="form-control"></td>
        <td><input type="time" name="stops[__INDEX__][drop_time]" class="form-control"></td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm remove-stop-row">&#215;</button>
        </td>
    </tr>
</template>

<script>
(() => {
    const tableBody = document.querySelector('#transportStopsTable tbody');
    const addButton = document.getElementById('addTransportStopRow');
    const template = document.getElementById('transportStopRowTemplate');

    if (!tableBody || !addButton || !template) return;

    const nextIndex = () => tableBody.querySelectorAll('tr').length;

    addButton.addEventListener('click', () => {
        const index = nextIndex();
        const rowHtml = template.innerHTML
            .replaceAll('__INDEX__', index)
            .replaceAll('__SEQ__', index + 1);
        const wrapper = document.createElement('tbody');
        wrapper.innerHTML = rowHtml.trim();
        tableBody.appendChild(wrapper.firstElementChild);
    });

    tableBody.addEventListener('click', (event) => {
        const button = event.target.closest('.remove-stop-row');
        if (!button) return;

        const rows = tableBody.querySelectorAll('tr');
        if (rows.length <= 1) return;

        button.closest('tr')?.remove();
    });
})();
</script>
