@extends('institute.layout')
@section('title', 'Route Assignments')
@section('breadcrumb', 'Transport / Route Assignments')

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Route Assignments</h4>
        <p class="text-muted mb-0" style="font-size:13px;">Link each route with a vehicle and driver</p>
    </div>
    <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i> Add Assignment
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Assignment Cards --}}
@if($assignments->count())
<div class="row g-3 mb-4">
    @foreach($assignments as $a)
    <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100 {{ $a->status ? '' : 'opacity-50' }}">
            <div class="card-body p-3">
                {{-- Route name --}}
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="bg-primary bg-opacity-10 text-primary rounded p-2" style="line-height:1;">
                            <i class="bi bi-signpost-2 fs-5"></i>
                        </span>
                        <div>
                            <div class="fw-semibold" style="font-size:14px;">{{ $a->route->name }}</div>
                            @if($a->session)
                                <div class="text-muted" style="font-size:11px;"><i class="bi bi-calendar3 me-1"></i>{{ $a->session->name }}</div>
                            @else
                                <div class="text-muted" style="font-size:11px;">All Sessions</div>
                            @endif
                        </div>
                    </div>
                    <span class="badge {{ $a->status ? 'text-bg-success' : 'text-bg-secondary' }}">
                        {{ $a->status ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <hr class="my-2">

                {{-- Vehicle --}}
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-truck text-muted" style="width:16px;"></i>
                    @if($a->vehicle)
                        <span class="fw-medium" style="font-size:13px;">{{ $a->vehicle->vehicle_no }}</span>
                        @if($a->vehicle->model)
                            <span class="text-muted" style="font-size:12px;">— {{ $a->vehicle->model }}</span>
                        @endif
                    @else
                        <span class="text-muted" style="font-size:13px;">No vehicle assigned</span>
                    @endif
                </div>

                {{-- Driver --}}
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-person-badge text-muted" style="width:16px;"></i>
                    @if($a->driver)
                        <span class="fw-medium" style="font-size:13px;">{{ $a->driver->name }}</span>
                        @if($a->driver->mobile)
                            <span class="text-muted" style="font-size:12px;">— {{ $a->driver->mobile }}</span>
                        @endif
                    @else
                        <span class="text-muted" style="font-size:13px;">No driver assigned</span>
                    @endif
                </div>

                @if($a->notes)
                <div class="text-muted mb-3" style="font-size:12px;"><i class="bi bi-sticky me-1"></i>{{ $a->notes }}</div>
                @endif

                {{-- Actions --}}
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary flex-grow-1"
                        onclick="openEdit({{ $a->id }}, {{ $a->transport_vehicle_id ?? 'null' }}, {{ $a->transport_driver_id ?? 'null' }}, '{{ addslashes($a->notes ?? '') }}')">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </button>
                    <form method="POST" action="{{ route('transport.route-assignments.toggle', $a) }}">
                        @csrf
                        <button class="btn btn-sm {{ $a->status ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $a->status ? 'Deactivate' : 'Activate' }}">
                            <i class="bi bi-{{ $a->status ? 'pause' : 'play' }}"></i>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('transport.route-assignments.destroy', $a) }}"
                        onsubmit="return confirm('Remove this assignment?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-signpost-2 fs-1 d-block mb-2 opacity-25"></i>
        <p class="mb-1 fw-medium">No assignments yet</p>
        <p class="mb-3" style="font-size:13px;">Assign a vehicle and driver to each route so they auto-fill during student allocation.</p>
        <button class="btn btn-primary btn-sm px-4" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg me-1"></i> Add First Assignment
        </button>
    </div>
</div>
@endif

{{-- ── Add Modal ── --}}
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('transport.route-assignments.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold"><i class="bi bi-plus-circle me-2 text-primary"></i>Add Route Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Route <span class="text-danger">*</span></label>
                            <select class="form-select" name="transport_route_id" required>
                                <option value="">— Select Route —</option>
                                @foreach($routes as $r)
                                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Session</label>
                            <select class="form-select" name="academic_session_id">
                                <option value="">All Sessions (default)</option>
                                @foreach($sessions as $sess)
                                    <option value="{{ $sess->id }}" {{ $sess->is_active ? 'selected' : '' }}>{{ $sess->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Session-specific assignment overrides the default.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Vehicle</label>
                            <select class="form-select" name="transport_vehicle_id">
                                <option value="">— Select Vehicle —</option>
                                @foreach($vehicles as $v)
                                    <option value="{{ $v->id }}">{{ $v->vehicle_no }}{{ $v->model ? ' — '.$v->model : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Driver</label>
                            <select class="form-select" name="transport_driver_id">
                                <option value="">— Select Driver —</option>
                                @foreach($drivers as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}{{ $d->mobile ? ' — '.$d->mobile : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Notes</label>
                            <input type="text" class="form-control" name="notes" placeholder="Optional">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-5"><i class="bi bi-floppy me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Edit Modal ── --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="editForm">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold"><i class="bi bi-pencil me-2 text-warning"></i>Edit Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Vehicle</label>
                            <select class="form-select" name="transport_vehicle_id" id="editVehicle">
                                <option value="">— None —</option>
                                @foreach($vehicles as $v)
                                    <option value="{{ $v->id }}">{{ $v->vehicle_no }}{{ $v->model ? ' — '.$v->model : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Driver</label>
                            <select class="form-select" name="transport_driver_id" id="editDriver">
                                <option value="">— None —</option>
                                @foreach($drivers as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}{{ $d->mobile ? ' — '.$d->mobile : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Notes</label>
                            <input type="text" class="form-control" name="notes" id="editNotes" placeholder="Optional">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-5"><i class="bi bi-floppy me-1"></i> Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEdit(id, vehicleId, driverId, notes) {
    document.getElementById('editForm').action = `/transport/route-assignments/${id}`;
    const v = document.getElementById('editVehicle');
    const d = document.getElementById('editDriver');
    const n = document.getElementById('editNotes');
    v.value = vehicleId ?? '';
    d.value = driverId ?? '';
    n.value = notes;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Auto-open add modal if there was a validation error
@if($errors->any())
    document.addEventListener('DOMContentLoaded', () => {
        new bootstrap.Modal(document.getElementById('addModal')).show();
    });
@endif
</script>
@endsection
