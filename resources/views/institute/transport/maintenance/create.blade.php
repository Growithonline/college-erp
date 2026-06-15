@extends('institute.layout')
@section('title', 'Add Maintenance Log')
@section('breadcrumb', 'Transport / Maintenance / Add')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Add Maintenance Log</div>
    <div class="card-body">
        <form method="POST" action="{{ route('transport.maintenance.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Vehicle *</label>
                    <select name="transport_vehicle_id" class="form-select" required>
                        <option value="">Select Vehicle</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}">{{ $vehicle->vehicle_no }}{{ $vehicle->model ? ' - ' . $vehicle->model : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Service Date *</label><input type="date" name="service_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                <div class="col-md-3"><label class="form-label">Next Service Due</label><input type="date" name="next_service_due" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">Odometer</label><input type="number" min="0" name="odometer_km" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Service Type</label><input type="text" name="service_type" class="form-control" placeholder="Oil change, tyre work, etc."></div>
                <div class="col-md-4"><label class="form-label">Garage / Vendor</label><input type="text" name="garage_name" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">Cost</label><input type="number" step="0.01" min="0" name="cost" class="form-control" value="0"></div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-12"><label class="form-label">Issues Found</label><textarea name="issues_found" class="form-control" rows="2"></textarea></div>
                <div class="col-12"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="mt-4 d-flex justify-content-end">
                <button class="btn btn-primary">Save Log</button>
            </div>
        </form>
    </div>
</div>
@endsection
