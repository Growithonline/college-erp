@extends('institute.layout')
@section('title', 'Route Details')
@section('breadcrumb', 'Transport / Routes / Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">{{ $route->name }}</h4>
        <small class="text-muted">{{ $route->route_code }} | {{ $route->start_point ?? 'Start' }} to {{ $route->end_point ?? 'End' }}</small>
    </div>
    <a href="{{ route('transport.routes.edit', $route) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>Edit Route</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Route Stops</div>
            <div class="list-group list-group-flush">
                @forelse($route->stops as $stop)
                    <div class="list-group-item">
                        <div class="fw-semibold">{{ $stop->sequence }}. {{ $stop->stop_name }}</div>
                        <small class="text-muted">{{ $stop->landmark ?? 'No landmark' }} | Pickup {{ $stop->pickup_time ? \Carbon\Carbon::parse($stop->pickup_time)->format('H:i') : '—' }}</small>
                    </div>
                @empty
                    <div class="list-group-item text-muted">No stops configured yet.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Active Allocations</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Vehicle</th>
                            <th>Driver</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($route->allocations as $allocation)
                            <tr>
                                <td>{{ $allocation->student?->name ?? '—' }}<br><small class="text-muted">{{ $allocation->student?->roll_no ?? '' }}</small></td>
                                <td>{{ $allocation->vehicle?->vehicle_no ?? '—' }}</td>
                                <td>{{ $allocation->driver?->name ?? '—' }}</td>
                                <td class="fw-semibold {{ $allocation->balance > 0 ? 'text-danger' : 'text-success' }}">₹{{ number_format($allocation->balance, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">No allocations for this route.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
