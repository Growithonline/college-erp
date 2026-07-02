@extends('institute.layout')
@section('title', 'Vehicles')
@section('breadcrumb', 'Transport / Vehicles')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Vehicles</h4>
        <small class="text-muted">{{ $vehicles->total() }} vehicle(s)</small>
    </div>
    <a href="{{ route('transport.vehicles.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Vehicle</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Vehicle</th>
                    <th>Capacity</th>
                    <th>Compliance</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vehicles as $i => $vehicle)
                    <tr>
                        <td>{{ $vehicles->firstItem() + $i }}</td>
                        <td>
                            <div class="fw-semibold">{{ $vehicle->vehicle_no }}</div>
                            <small class="text-muted">{{ $vehicle->registration_no ?? $vehicle->model ?? '—' }}</small>
                        </td>
                        <td>{{ $vehicle->capacity ?: '—' }}</td>
                        <td class="small text-muted">
                            Ins: {{ $vehicle->insurance_expiry?->format('d M Y') ?? '—' }}<br>
                            Perm: {{ $vehicle->permit_expiry?->format('d M Y') ?? '—' }}<br>
                            Fit: {{ $vehicle->fitness_expiry?->format('d M Y') ?? '—' }}
                        </td>
                        <td>
                            <form method="POST" action="{{ route('transport.vehicles.toggle', $vehicle) }}">
                                @csrf
                                <button class="btn btn-sm {{ $vehicle->status ? 'btn-success' : 'btn-secondary' }}">
                                    {{ $vehicle->status ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('transport.vehicles.edit', $vehicle) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                                <form id="del-v-{{ $vehicle->id }}" method="POST" action="{{ route('transport.vehicles.destroy', $vehicle) }}" class="d-none">
                                    @csrf @method('DELETE')
                                </form>
                                <button class="btn btn-outline-danger btn-sm"
                                    onclick="deleteConfirm('del-v-{{ $vehicle->id }}', 'Delete Vehicle?', '{{ addslashes($vehicle->vehicle_no) }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">No vehicles added yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $vehicles->links() }}</div>
@include('partials.delete-confirm-modal')
@endsection
