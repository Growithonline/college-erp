@extends('institute.layout')
@section('title', 'Allocations')
@section('breadcrumb', 'Transport / Allocations')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Transport Allocations</h4>
        <small class="text-muted">{{ $allocations->total() }} allocation(s)</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('transport.allocations.bulk-create') }}" class="btn btn-outline-primary"><i class="bi bi-people me-1"></i>Bulk Allocate</a>
        <a href="{{ route('transport.allocations.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Allocation</a>
    </div>
</div>

<form class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-3"><input class="form-control" name="student" value="{{ request('student') }}" placeholder="Search student"></div>
            <div class="col-md-3">
                <select class="form-select" name="session_id">
                    <option value="">All Sessions</option>
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" @selected((string) request('session_id') === (string) $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="route_id">
                    <option value="">All Routes</option>
                    @foreach($routes as $route)
                        <option value="{{ $route->id }}" @selected((string) request('route_id') === (string) $route->id)>{{ $route->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    @foreach(['active','partial','paid','closed'] as $status)
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
                    <th>Student</th>
                    <th>Route</th>
                    <th>Vehicle / Driver</th>
                    <th>Fee</th>
                    <th>Paid</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($allocations as $allocation)
                    <tr>
                        <td>{{ $allocation->student?->name ?? '—' }}<br><small class="text-muted">{{ $allocation->student?->roll_no ?? '' }}</small></td>
                        <td>{{ $allocation->route?->name ?? '—' }}<br><small class="text-muted">{{ $allocation->stop?->stop_name ?? '' }}</small></td>
                        <td>{{ $allocation->vehicle?->vehicle_no ?? '—' }}<br><small class="text-muted">{{ $allocation->driver?->name ?? '' }}</small></td>
                        <td>₹{{ number_format((float) $allocation->fee_amount, 2) }}</td>
                        <td>₹{{ number_format((float) $allocation->paid_amount, 2) }}</td>
                        <td><span class="badge bg-{{ $allocation->status === 'paid' ? 'success' : ($allocation->status === 'partial' ? 'warning' : ($allocation->status === 'closed' ? 'secondary' : 'primary')) }}">{{ ucfirst($allocation->status) }}</span></td>
                        <td class="text-nowrap">
                            <a href="{{ route('transport.allocations.show', $allocation) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-eye"></i></a>
                            @if($allocation->is_active)
                            <a href="{{ route('transport.allocations.edit', $allocation) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No allocations found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $allocations->links() }}</div>
@endsection
