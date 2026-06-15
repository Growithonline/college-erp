@extends('institute.layout')
@section('title', 'Vehicle Types')
@section('breadcrumb', 'Transport / Vehicle Types')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Vehicle Types</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTypeModal">
        <i class="bi bi-plus-lg me-1"></i> Add Type
    </button>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        {{ $errors->first() }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Default Capacity</th>
                    <th>Vehicles</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($types as $i => $type)
                <tr>
                    <td class="text-muted">{{ $i + 1 }}</td>
                    <td class="fw-semibold">{{ $type->name }}</td>
                    <td>{{ $type->default_capacity ?: '—' }}</td>
                    <td>{{ $type->vehicles_count }}</td>
                    <td>
                        <span class="badge {{ $type->status ? 'bg-success' : 'bg-secondary' }}">
                            {{ $type->status ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#editTypeModal{{ $type->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>

                        <form method="POST" action="{{ route('transport.vehicle-types.toggle', $type) }}" class="d-inline">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-outline-{{ $type->status ? 'warning' : 'success' }}"
                                title="{{ $type->status ? 'Disable' : 'Enable' }}">
                                <i class="bi bi-{{ $type->status ? 'pause-circle' : 'play-circle' }}"></i>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('transport.vehicle-types.destroy', $type) }}" class="d-inline"
                            onsubmit="return confirm('Delete this type?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" {{ $type->vehicles_count > 0 ? 'disabled' : '' }}
                                title="{{ $type->vehicles_count > 0 ? 'Cannot delete — vehicles assigned' : 'Delete' }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>

                {{-- Edit Modal --}}
                <div class="modal fade" id="editTypeModal{{ $type->id }}" tabindex="-1">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content">
                            <div class="modal-header"><h6 class="modal-title">Edit Type</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <form method="POST" action="{{ route('transport.vehicle-types.update', $type) }}">
                                @csrf @method('PUT')
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" name="name" class="form-control" value="{{ $type->name }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Default Capacity</label>
                                        <input type="number" min="0" name="default_capacity" class="form-control" value="{{ $type->default_capacity }}">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <tr><td colspan="5" class="text-center py-5 text-muted">No vehicle types added yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Add Modal --}}
<div class="modal fade" id="addTypeModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header"><h6 class="modal-title">Add Vehicle Type</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('transport.vehicle-types.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Bus, Van, Auto" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Default Capacity</label>
                        <input type="number" min="0" name="default_capacity" class="form-control" placeholder="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-sm">Add Type</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
