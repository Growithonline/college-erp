@extends('institute.layout')
@section('title', 'Bulk Allocation')
@section('breadcrumb', 'Transport / Allocations / Bulk')

@section('content')

{{-- Page Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Bulk Transport Allocation</h4>
        <p class="text-muted mb-0" style="font-size:13px;">Assign transport route to multiple students at once</p>
    </div>
    <a href="{{ route('transport.allocations.index') }}" class="btn btn-outline-secondary btn-sm px-3">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
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

<form method="POST" action="{{ route('transport.allocations.bulk-store') }}">
@csrf

{{-- ── Section 1: Route & Vehicle Details ── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <span class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" style="width:28px;height:28px;font-size:13px;flex-shrink:0;">
                <i class="bi bi-signpost-2"></i>
            </span>
            <span class="fw-semibold text-dark" style="font-size:14px;">Route & Vehicle Details</span>
            <hr class="flex-grow-1 my-0 ms-1">
        </div>

        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-medium">Session <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-calendar3 text-muted"></i></span>
                    <select class="form-select" name="academic_session_id" required>
                        <option value="">Select</option>
                        @foreach($sessions as $sess)
                            <option value="{{ $sess->id }}" {{ $sess->is_active ? 'selected' : '' }}>{{ $sess->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-medium">Route <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-map text-muted"></i></span>
                    <select class="form-select" name="transport_route_id" id="bulkRouteSelect" required>
                        <option value="">Select Route</option>
                        @foreach($routes as $r)
                            <option value="{{ $r->id }}" data-fee="{{ $r->fee_amount }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-medium">Stop <span class="text-muted fw-normal" style="font-size:12px;">(optional)</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-geo-alt text-muted"></i></span>
                    <select class="form-select" name="transport_route_stop_id" id="bulkStopSelect">
                        <option value="">No Stop</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-medium">Fee Amount</label>
                <div class="input-group">
                    <span class="input-group-text bg-light">₹</span>
                    <input type="number" step="0.01" min="0" name="fee_amount" id="bulkFeeInput"
                        class="form-control" placeholder="Auto from stop/route">
                </div>
            </div>
            <div class="col-12">
                <div id="bulkAutoFill" class="d-none alert alert-success py-2 px-3 mb-0" style="font-size:13px;"></div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-medium">Vehicle</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-truck text-muted"></i></span>
                    <select class="form-select" name="transport_vehicle_id">
                        <option value="">None</option>
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}">{{ $v->vehicle_no }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-medium">Driver</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-person text-muted"></i></span>
                    <select class="form-select" name="transport_driver_id">
                        <option value="">None</option>
                        @foreach($drivers as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-medium">Start Date <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-calendar-event text-muted"></i></span>
                    <input type="date" name="start_date" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Section 2: Select Students ── --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">

        {{-- Section heading + controls --}}
        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
            <span class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white" style="width:28px;height:28px;font-size:13px;flex-shrink:0;">
                <i class="bi bi-people"></i>
            </span>
            <span class="fw-semibold text-dark" style="font-size:14px;">Select Students</span>
            <span class="badge bg-secondary" id="totalBadge">{{ $students->count() }} available</span>
            <span class="badge bg-primary d-none" id="selectedBadge">0 selected</span>
            <hr class="flex-grow-1 my-0 ms-1">
            <div class="d-flex gap-2 align-items-center ms-auto">
                <div class="input-group input-group-sm" style="width:220px;">
                    <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control" id="studentSearch" placeholder="Search name / mobile / roll...">
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary px-3" id="selectAll">Select All</button>
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" id="deselectAll">Deselect All</button>
            </div>
        </div>

        @if($students->count())
        <div style="max-height:460px;overflow-y:auto;border:1px solid #dee2e6;border-radius:6px;">
            <table class="table table-sm table-hover mb-0 align-middle" style="font-size:13px;" id="studentTable">
                <thead style="position:sticky;top:0;z-index:1;background:#f8f9fa;">
                    <tr>
                        <th class="text-center ps-3" style="width:40px;">
                            <input type="checkbox" class="form-check-input" id="checkAll" title="Select / Deselect All">
                        </th>
                        <th class="text-center" style="width:42px;">#</th>
                        <th style="min-width:160px;">Student Name</th>
                        <th style="min-width:140px;">Father Name</th>
                        <th style="min-width:140px;">Mother Name</th>
                        <th style="min-width:115px;">Mobile No</th>
                        <th style="min-width:95px;">Roll No</th>
                        <th style="min-width:120px;">Enroll No</th>
                        <th style="min-width:120px;">UIN</th>
                    </tr>
                </thead>
                <tbody id="studentTbody">
                    @foreach($students as $i => $s)
                    <tr class="student-row" data-search="{{ strtolower($s->name . ' ' . $s->mobile . ' ' . $s->roll_no . ' ' . $s->enrollment_no . ' ' . $s->father_name) }}">
                        <td class="text-center ps-3">
                            <input type="checkbox" class="form-check-input student-check" name="student_ids[]" value="{{ $s->id }}">
                        </td>
                        <td class="text-center text-muted">{{ $i + 1 }}</td>
                        <td class="fw-medium">{{ $s->name }}</td>
                        <td class="text-muted">{{ $s->father_name ?: '—' }}</td>
                        <td class="text-muted">{{ $s->mother_name ?: '—' }}</td>
                        <td class="text-muted">{{ $s->mobile ?: '—' }}</td>
                        <td class="text-muted">{{ $s->roll_no ?: '—' }}</td>
                        <td class="text-muted">{{ $s->enrollment_no ?: '—' }}</td>
                        <td class="text-muted">{{ $s->uin_no ?: '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="bi bi-person-check fs-1 d-block mb-2 opacity-25"></i>
            <p class="mb-0">All students already have active allocations.</p>
        </div>
        @endif
    </div>
</div>

{{-- Submit --}}
<div class="mt-4 d-flex justify-content-between align-items-center">
    <p class="text-muted mb-0 small"><i class="bi bi-info-circle me-1"></i>Only checked students will be allocated.</p>
    <div class="d-flex gap-2">
        <a href="{{ route('transport.allocations.index') }}" class="btn btn-light px-4">Cancel</a>
        <button type="submit" class="btn btn-primary px-5" id="submitBtn" disabled>
            <i class="bi bi-check2-circle me-1"></i>
            Allocate <span id="submitCount">0</span> Students
        </button>
    </div>
</div>

</form>

<style>
.student-row.selected-row { background-color: #eef3ff !important; }
#studentTable thead tr th { font-size: 12px; font-weight: 600; color: #555; white-space: nowrap; }
</style>

<script>
(() => {
    // Route → fee + stops
    const routeSel = document.getElementById('bulkRouteSelect');
    const stopSel  = document.getElementById('bulkStopSelect');
    const feeInput = document.getElementById('bulkFeeInput');

    const vehicleSel  = document.querySelector('[name="transport_vehicle_id"]');
    const driverSel   = document.querySelector('[name="transport_driver_id"]');
    const sessionSel  = document.querySelector('[name="academic_session_id"]');
    const autoFillMsg = document.getElementById('bulkAutoFill');

    function fetchRouteAssignment(routeId) {
        if (!routeId || !vehicleSel || !driverSel) return;
        const sessionId = sessionSel?.value ?? '';
        fetch(`/transport/route-assignments/for-route?route_id=${routeId}&session_id=${sessionId}`)
            .then(r => r.json())
            .then(data => {
                if (data.vehicle_id) vehicleSel.value = data.vehicle_id;
                if (data.driver_id)  driverSel.value  = data.driver_id;
                if (autoFillMsg) {
                    if (data.vehicle_id || data.driver_id) {
                        autoFillMsg.innerHTML = `<i class="bi bi-magic me-1"></i>Auto-filled: <strong>${data.vehicle_no ?? '—'}</strong> / <strong>${data.driver_name ?? '—'}</strong>`;
                        autoFillMsg.classList.remove('d-none');
                    } else {
                        autoFillMsg.classList.add('d-none');
                    }
                }
            });
    }

    routeSel?.addEventListener('change', () => {
        const opt = routeSel.options[routeSel.selectedIndex];
        const fee = parseFloat(opt?.dataset?.fee ?? 0);
        if (fee > 0) feeInput.value = fee.toFixed(2);
        stopSel.innerHTML = '<option value="">No Stop</option>';
        if (!routeSel.value) { if(autoFillMsg) autoFillMsg.classList.add('d-none'); return; }
        fetch(`/transport/routes/${routeSel.value}/stops`)
            .then(r => r.json())
            .then(data => {
                (data.stops ?? []).forEach(s => {
                    const o = document.createElement('option');
                    o.value = s.id;
                    o.dataset.fee = s.fee_amount;
                    o.textContent = s.stop_name + (s.fee_amount > 0 ? ` — ₹${parseFloat(s.fee_amount).toFixed(2)}` : '');
                    stopSel.appendChild(o);
                });
            });
        fetchRouteAssignment(routeSel.value);
    });

    stopSel?.addEventListener('change', () => {
        const fee = parseFloat(stopSel.options[stopSel.selectedIndex]?.dataset?.fee ?? 0);
        if (fee > 0) feeInput.value = fee.toFixed(2);
    });

    // Checkbox logic
    const checkAll   = document.getElementById('checkAll');
    const submitBtn  = document.getElementById('submitBtn');
    const submitCount = document.getElementById('submitCount');
    const selectedBadge = document.getElementById('selectedBadge');

    function getChecks()   { return document.querySelectorAll('.student-check'); }
    function getVisible()  { return document.querySelectorAll('.student-row:not([style*="display: none"]) .student-check'); }

    function updateCount() {
        const checked = document.querySelectorAll('.student-check:checked').length;
        submitCount.textContent = checked;
        submitBtn.disabled = checked === 0;
        if (checked > 0) {
            selectedBadge.textContent = checked + ' selected';
            selectedBadge.classList.remove('d-none');
        } else {
            selectedBadge.classList.add('d-none');
        }
        // header checkbox state
        const visChecks  = getVisible();
        const visChecked = Array.from(visChecks).filter(c => c.checked).length;
        if (checkAll) {
            checkAll.indeterminate = visChecked > 0 && visChecked < visChecks.length;
            checkAll.checked = visChecks.length > 0 && visChecked === visChecks.length;
        }
    }

    function toggleRowHighlight(checkbox) {
        const row = checkbox.closest('tr');
        if (row) row.classList.toggle('selected-row', checkbox.checked);
    }

    document.getElementById('studentTbody')?.addEventListener('change', e => {
        if (e.target.classList.contains('student-check')) {
            toggleRowHighlight(e.target);
            updateCount();
        }
    });

    checkAll?.addEventListener('change', () => {
        getVisible().forEach(c => {
            c.checked = checkAll.checked;
            toggleRowHighlight(c);
        });
        updateCount();
    });

    document.getElementById('selectAll')?.addEventListener('click', () => {
        getVisible().forEach(c => { c.checked = true; toggleRowHighlight(c); });
        updateCount();
    });
    document.getElementById('deselectAll')?.addEventListener('click', () => {
        getChecks().forEach(c => { c.checked = false; toggleRowHighlight(c); });
        updateCount();
    });

    // Search / filter
    document.getElementById('studentSearch')?.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('.student-row').forEach(row => {
            const match = !q || row.dataset.search.includes(q);
            row.style.display = match ? '' : 'none';
        });
        updateCount();
    });

    updateCount();
})();
</script>
@endsection
