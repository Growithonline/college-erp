@extends('institute.layout')
@section('title', 'Compliance')
@section('breadcrumb', 'Transport / Compliance')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Compliance Report</h4>
        <small class="text-muted">Insurance, permit, fitness, and pollution expiry tracking</small>
    </div>
    <a href="{{ route('transport.maintenance.index') }}" class="btn btn-outline-primary">
        <i class="bi bi-wrench-adjustable me-1"></i> Maintenance
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fw-bold fs-4">{{ $vehicles->where('compliance_status', 'ok')->count() }}</div><small class="text-muted">Compliant</small></div></div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fw-bold fs-4">{{ $vehicles->where('compliance_status', 'warning')->count() }}</div><small class="text-muted">Expiring Soon</small></div></div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fw-bold fs-4">{{ $vehicles->where('compliance_status', 'expired')->count() }}</div><small class="text-muted">Expired</small></div></div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fw-bold fs-4">{{ $vehicles->count() }}</div><small class="text-muted">Total Vehicles</small></div></div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Vehicle</th>
                    <th>Insurance</th>
                    <th>Permit</th>
                    <th>Fitness</th>
                    <th>Pollution</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vehicles as $vehicle)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $vehicle->vehicle_no }}</div>
                            <small class="text-muted">{{ $vehicle->registration_no ?? $vehicle->model ?? '—' }}</small>
                        </td>
                        <td>{{ $vehicle->insurance_expiry?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $vehicle->permit_expiry?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $vehicle->fitness_expiry?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $vehicle->pollution_expiry?->format('d M Y') ?? '—' }}</td>
                        <td>
                            @if($vehicle->compliance_status === 'expired')
                                <span class="badge bg-danger">Expired</span>
                            @elseif($vehicle->compliance_status === 'warning')
                                <span class="badge bg-warning text-dark">Expiring Soon</span>
                            @else
                                <span class="badge bg-success">Ok</span>
                            @endif
                        </td>
                    </tr>
                    @if(!empty($vehicle->compliance_expired) || !empty($vehicle->compliance_expiring))
                    <tr class="table-light">
                        <td colspan="6" class="small text-muted">
                            @if(!empty($vehicle->compliance_expired))
                                Expired: {{ implode(', ', array_map('ucfirst', $vehicle->compliance_expired)) }}
                            @endif
                            @if(!empty($vehicle->compliance_expiring))
                                @if(!empty($vehicle->compliance_expired)) | @endif
                                Expiring soon: {{ implode(', ', array_map('ucfirst', $vehicle->compliance_expiring)) }}
                            @endif
                        </td>
                    </tr>
                    @endif
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">No vehicles found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
