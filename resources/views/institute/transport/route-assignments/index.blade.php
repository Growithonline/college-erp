@extends('institute.layout')
@section('title', 'Route Assignments')
@section('breadcrumb', 'Transport / Route Assignments')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Route Assignments</h4>
        <p class="text-muted mb-0" style="font-size:13px;">
            Assign vehicle, driver, and helper to a route. The previous record is automatically closed when changed.
        </p>
    </div>
    <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i> New Assignment
    </button>
</div>


{{-- ── Current Active Assignments ── --}}
<h6 class="fw-semibold text-uppercase text-muted mb-3" style="font-size:11px; letter-spacing:.05em;">
    <i class="bi bi-circle-fill text-success me-1" style="font-size:8px;"></i> Current Active Assignments
</h6>

@if($current->count())
<div class="row g-3 mb-4">
    @foreach($current as $a)
    <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="bg-primary bg-opacity-10 text-primary rounded p-2" style="line-height:1;">
                        <i class="bi bi-signpost-2 fs-5"></i>
                    </span>
                    <div>
                        <div class="fw-semibold" style="font-size:14px;">{{ $a->route->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:11px;">
                            <i class="bi bi-calendar3 me-1"></i>From {{ $a->start_date?->format('d M Y') ?? '—' }}
                        </div>
                    </div>
                    <span class="badge text-bg-success ms-auto">Active</span>
                </div>
                <hr class="my-2">

                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-truck text-muted" style="width:16px;"></i>
                    <span style="font-size:13px;">{{ $a->vehicle?->vehicle_no ?? '—' }}</span>
                </div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-person-badge text-muted" style="width:16px;"></i>
                    <span style="font-size:13px;">{{ $a->driver?->name ?? '—' }}</span>
                    @if($a->driver?->mobile)
                        <span class="text-muted" style="font-size:12px;">— {{ $a->driver->mobile }}</span>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-person text-muted" style="width:16px;"></i>
                    <span style="font-size:13px;">{{ $a->helper?->name ?? '—' }}
                        <span class="text-muted" style="font-size:11px;">(Helper)</span>
                    </span>
                </div>

                @if($a->notes)
                <div class="text-muted mb-2" style="font-size:12px;"><i class="bi bi-sticky me-1"></i>{{ $a->notes }}</div>
                @endif

                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-sm btn-outline-primary flex-grow-1"
                        onclick="openEdit({{ $a->id }},
                            {{ $a->transport_vehicle_id ?? 'null' }},
                            {{ $a->transport_driver_id ?? 'null' }},
                            {{ $a->transport_helper_id ?? 'null' }},
                            '{{ addslashes($a->notes ?? '') }}')">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-outline-warning"
                        onclick="openChange({{ $a->transport_route_id }})"
                        title="Change Vehicle/Driver/Helper — previous record will be closed">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>
                    <form id="del-ra-{{ $a->id }}" method="POST" action="{{ route('transport.route-assignments.destroy', $a) }}" class="d-none">
                        @csrf @method('DELETE')
                    </form>
                    <button class="btn btn-sm btn-outline-danger"
                        onclick="deleteConfirm('del-ra-{{ $a->id }}', 'Delete Assignment?', '{{ addslashes($a->route->name ?? '') }}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-signpost-2 fs-1 d-block mb-2 opacity-25"></i>
        <p class="mb-1 fw-medium">No active assignments</p>
        <p style="font-size:13px;">Assign a vehicle and driver to a route so they auto-select in the admission form.</p>
        <button class="btn btn-primary btn-sm px-4" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg me-1"></i> Add Assignment
        </button>
    </div>
</div>
@endif

{{-- ── History ── --}}
@if($history->count())
<h6 class="fw-semibold text-uppercase text-muted mb-3" style="font-size:11px; letter-spacing:.05em;">
    <i class="bi bi-clock-history me-1"></i> Assignment History
</h6>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead style="background:#f8fafc; font-size:12px;">
                <tr>
                    <th class="ps-3">Route</th>
                    <th>Vehicle</th>
                    <th>Driver</th>
                    <th>Helper</th>
                    <th>From</th>
                    <th>To</th>
                </tr>
            </thead>
            <tbody style="font-size:13px;">
                @foreach($history as $h)
                <tr class="text-muted">
                    <td class="ps-3">{{ $h->route?->name ?? '—' }}</td>
                    <td>{{ $h->vehicle?->vehicle_no ?? '—' }}</td>
                    <td>{{ $h->driver?->name ?? '—' }}</td>
                    <td>{{ $h->helper?->name ?? '—' }}</td>
                    <td>{{ $h->start_date?->format('d M Y') ?? '—' }}</td>
                    <td>{{ $h->end_date?->format('d M Y') ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
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
                    <h5 class="modal-title fw-semibold"><i class="bi bi-plus-circle me-2 text-primary"></i>New Route Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3" style="font-size:13px;">
                        <i class="bi bi-info-circle me-1"></i>
                        If a previous assignment exists for this route, it will be automatically closed.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Route <span class="text-danger">*</span></label>
                            <select class="form-select" name="transport_route_id" id="addRouteSelect" required>
                                <option value="">— Select Route —</option>
                                @foreach($routes as $r)
                                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="start_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Vehicle</label>
                            <select class="form-select" name="transport_vehicle_id">
                                <option value="">— None —</option>
                                @foreach($vehicles as $v)
                                    <option value="{{ $v->id }}">{{ $v->vehicle_no }}{{ $v->model ? ' — '.$v->model : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Driver</label>
                            <select class="form-select" name="transport_driver_id">
                                <option value="">— None —</option>
                                @foreach($drivers as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}{{ $d->mobile ? ' — '.$d->mobile : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Helper</label>
                            <select class="form-select" name="transport_helper_id">
                                <option value="">— None —</option>
                                @foreach($helpers as $h)
                                    <option value="{{ $h->id }}">{{ $h->name }}{{ $h->mobile ? ' — '.$h->mobile : '' }}</option>
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
                    <button type="submit" class="btn btn-primary px-5"><i class="bi bi-floppy me-1"></i> Save Assignment</button>
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
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Vehicle</label>
                            <select class="form-select" name="transport_vehicle_id" id="editVehicle">
                                <option value="">— None —</option>
                                @foreach($vehicles as $v)
                                    <option value="{{ $v->id }}">{{ $v->vehicle_no }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Driver</label>
                            <select class="form-select" name="transport_driver_id" id="editDriver">
                                <option value="">— None —</option>
                                @foreach($drivers as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Helper</label>
                            <select class="form-select" name="transport_helper_id" id="editHelper">
                                <option value="">— None —</option>
                                @foreach($helpers as $h)
                                    <option value="{{ $h->id }}">{{ $h->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Notes</label>
                            <input type="text" class="form-control" name="notes" id="editNotes">
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

{{-- ── Change Modal ── --}}
<div class="modal fade" id="changeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('transport.route-assignments.store') }}">
                @csrf
                <input type="hidden" name="transport_route_id" id="changeRouteId">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold"><i class="bi bi-arrow-repeat me-2 text-warning"></i>Change Vehicle / Driver / Helper</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 mb-3" style="font-size:13px;">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        The existing assignment will be closed. A new record will start from the selected date.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="start_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">New Vehicle</label>
                            <select class="form-select" name="transport_vehicle_id">
                                <option value="">— None —</option>
                                @foreach($vehicles as $v)
                                    <option value="{{ $v->id }}">{{ $v->vehicle_no }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">New Driver</label>
                            <select class="form-select" name="transport_driver_id">
                                <option value="">— None —</option>
                                @foreach($drivers as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">New Helper</label>
                            <select class="form-select" name="transport_helper_id">
                                <option value="">— None —</option>
                                @foreach($helpers as $h)
                                    <option value="{{ $h->id }}">{{ $h->name }}</option>
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
                    <button type="submit" class="btn btn-warning px-5"><i class="bi bi-arrow-repeat me-1"></i> Change & Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
@if(session('success'))
    document.addEventListener('DOMContentLoaded', () => showToast('{{ addslashes(session('success')) }}', 'success'));
@endif
@if($errors->any())
    document.addEventListener('DOMContentLoaded', () => showToast('{{ addslashes($errors->first()) }}', 'danger'));
@endif

function openEdit(id, vehicleId, driverId, helperId, notes) {
    document.getElementById('editForm').action = `/transport/route-assignments/${id}`;
    document.getElementById('editVehicle').value = vehicleId ?? '';
    document.getElementById('editDriver').value  = driverId  ?? '';
    document.getElementById('editHelper').value  = helperId  ?? '';
    document.getElementById('editNotes').value   = notes;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
function openChange(routeId) {
    document.getElementById('changeRouteId').value = routeId;
    new bootstrap.Modal(document.getElementById('changeModal')).show();
}
@if($errors->any())
    document.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('addModal')).show());
@endif
</script>
@include('partials.delete-confirm-modal')
@endsection
