@extends('institute.layout')
@section('title', 'Maintenance')
@section('breadcrumb', 'Transport / Maintenance')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Maintenance Logs</h4>
        <small class="text-muted">{{ $logs->total() }} log(s)</small>
    </div>
    <a href="{{ route('transport.maintenance.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Log</a>
</div>

<form class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <select class="form-select" name="vehicle_id">
                    <option value="">All Vehicles</option>
                    @foreach($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}" @selected((string) request('vehicle_id') === (string) $vehicle->id)>{{ $vehicle->vehicle_no }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    @foreach(['completed','pending','cancelled'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-outline-primary">Filter</button></div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Vehicle</th>
                    <th>Service</th>
                    <th>Next Due</th>
                    <th>Cost</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->service_date?->format('d M Y') }}</td>
                        <td>{{ $log->vehicle?->vehicle_no ?? '—' }}<br><small class="text-muted">{{ $log->vehicle?->model ?? '' }}</small></td>
                        <td>
                            <div class="fw-semibold">{{ $log->service_type ?? 'Routine Service' }}</div>
                            <small class="text-muted">{{ $log->garage_name ?? '' }}</small>
                        </td>
                        <td>{{ $log->next_service_due?->format('d M Y') ?? '—' }}</td>
                        <td>₹{{ number_format((float) $log->cost, 2) }}</td>
                        <td><span class="badge bg-{{ $log->status === 'completed' ? 'success' : ($log->status === 'pending' ? 'warning' : 'secondary') }}">{{ ucfirst($log->status) }}</span></td>
                        <td class="small text-muted">{{ $log->remarks ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No maintenance logs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $logs->links() }}</div>
@endsection
