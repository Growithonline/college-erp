@extends('institute.layout')
@section('title', 'Edit Allocation')
@section('breadcrumb', 'Transport / Allocations / Edit')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Edit Allocation</h4>
        <small class="text-muted">{{ $allocation->student?->name }} — {{ $allocation->route?->name }}</small>
    </div>
    <a href="{{ route('transport.allocations.show', $allocation) }}" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('transport.allocations.update', $allocation) }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Stop</label>
                    <select class="form-select" name="transport_route_stop_id">
                        <option value="">No specific stop</option>
                        @foreach($stops as $stop)
                            <option value="{{ $stop->id }}"
                                @selected(old('transport_route_stop_id', $allocation->transport_route_stop_id) == $stop->id)>
                                {{ $stop->stop_name }}{{ $stop->fee_amount > 0 ? ' — ₹' . number_format($stop->fee_amount, 2) : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vehicle</label>
                    <select class="form-select" name="transport_vehicle_id">
                        <option value="">None</option>
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}" @selected(old('transport_vehicle_id', $allocation->transport_vehicle_id) == $v->id)>{{ $v->vehicle_no }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Driver</label>
                    <select class="form-select" name="transport_driver_id">
                        <option value="">None</option>
                        @foreach($drivers as $d)
                            <option value="{{ $d->id }}" @selected(old('transport_driver_id', $allocation->transport_driver_id) == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fee Amount Override</label>
                    <input type="number" step="0.01" min="0" name="fee_amount"
                        class="form-control" value="{{ old('fee_amount', $allocation->fee_amount) }}">
                    <div class="form-text">Change only if needed. Currently: ₹{{ number_format($allocation->fee_amount, 2) }}</div>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Remarks</label>
                    <input type="text" name="remarks" class="form-control" value="{{ old('remarks', $allocation->remarks) }}">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2 justify-content-end">
                <a href="{{ route('transport.allocations.show', $allocation) }}" class="btn btn-outline-secondary">Cancel</a>
                <button class="btn btn-primary">Update Allocation</button>
            </div>
        </form>
    </div>
</div>
@endsection
