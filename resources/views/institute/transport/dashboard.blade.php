@extends('institute.layout')
@section('title', 'Transport Dashboard')
@section('breadcrumb', 'Transport / Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Transport Dashboard</h4>
        <small class="text-muted">Quick view of fleet, routes, and student allocations.</small>
    </div>
    <a href="{{ route('transport.allocations.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Allocation
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-2"><div class="card shadow-sm border-0"><div class="card-body text-center"><div class="fw-bold fs-5">{{ $summary['vehicles'] }}</div><small class="text-muted">Vehicles</small></div></div></div>
    <div class="col-md-2"><div class="card shadow-sm border-0"><div class="card-body text-center"><div class="fw-bold fs-5">{{ $summary['routes'] }}</div><small class="text-muted">Routes</small></div></div></div>
    <div class="col-md-2"><div class="card shadow-sm border-0"><div class="card-body text-center"><div class="fw-bold fs-5">{{ $summary['drivers'] }}</div><small class="text-muted">Drivers</small></div></div></div>
    <div class="col-md-2"><div class="card shadow-sm border-0"><div class="card-body text-center"><div class="fw-bold fs-5">{{ $summary['active_allocations'] }}</div><small class="text-muted">Active Students</small></div></div></div>
    <div class="col-md-2"><div class="card shadow-sm border-0 border-danger"><div class="card-body text-center"><div class="fw-bold fs-5 text-danger">₹{{ number_format($summary['total_due'], 2) }}</div><small class="text-muted">Total Pending</small></div></div></div>
    <div class="col-md-2"><div class="card shadow-sm border-0 border-success"><div class="card-body text-center"><div class="fw-bold fs-5 text-success">₹{{ number_format($summary['total_collected'], 2) }}</div><small class="text-muted">Total Collected</small></div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Pending Transport Balances</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Route</th>
                                <th>Fee</th>
                                <th>Paid</th>
                                <th>Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dueAllocations as $allocation)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $allocation->student?->name ?? '—' }}</div>
                                        <small class="text-muted">{{ $allocation->student?->roll_no ?? '' }}</small>
                                    </td>
                                    <td>{{ $allocation->route?->name ?? '—' }}<br><small class="text-muted">{{ $allocation->stop?->stop_name ?? '' }}</small></td>
                                    <td>₹{{ number_format((float) $allocation->fee_amount, 2) }}</td>
                                    <td>₹{{ number_format((float) $allocation->paid_amount, 2) }}</td>
                                    <td class="text-danger fw-semibold">₹{{ number_format($allocation->balance, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-4 text-muted">No pending transport dues.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Recent Payments</div>
            <div class="list-group list-group-flush">
                @forelse($recentPayments as $payment)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="fw-semibold">{{ $payment->student?->name ?? 'Student' }}</div>
                                <small class="text-muted">{{ $payment->allocation?->route?->name ?? 'Route' }}</small>
                            </div>
                            <div class="fw-bold text-success">₹{{ number_format((float) $payment->amount, 2) }}</div>
                        </div>
                        <small class="text-muted">{{ $payment->payment_mode }} on {{ $payment->payment_date?->format('d M Y') }}</small>
                    </div>
                @empty
                    <div class="list-group-item text-muted">No payments yet.</div>
                @endforelse
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Expiring Vehicles</div>
            <div class="list-group list-group-flush">
                @forelse($expiringVehicles as $vehicle)
                    <div class="list-group-item">
                        <div class="fw-semibold">{{ $vehicle->vehicle_no }}</div>
                        <small class="text-muted">{{ $vehicle->model ?? 'Vehicle' }}</small>
                    </div>
                @empty
                    <div class="list-group-item text-muted">No upcoming expiries in the next 30 days.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
