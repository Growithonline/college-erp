@php($vehicle = $vehicle ?? null)

{{-- ── Section 1: Basic Information ── --}}
<div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-3">
        <span class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" style="width:28px;height:28px;font-size:13px;flex-shrink:0;">
            <i class="bi bi-truck"></i>
        </span>
        <span class="fw-semibold text-dark" style="font-size:14px;">Basic Information</span>
        <hr class="flex-grow-1 my-0 ms-1">
    </div>
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label fw-medium">Vehicle Type</label>
            <select class="form-select" name="transport_vehicle_type_id" id="vehicleTypeSelect">
                <option value="">— Select Type —</option>
                @foreach($vehicleTypes ?? [] as $vt)
                    <option value="{{ $vt->id }}"
                        data-capacity="{{ $vt->default_capacity }}"
                        {{ old('transport_vehicle_type_id', $vehicle->transport_vehicle_type_id ?? '') == $vt->id ? 'selected' : '' }}>
                        {{ $vt->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-medium">Vehicle No <span class="text-danger">*</span></label>
            <input class="form-control text-uppercase" name="vehicle_no"
                value="{{ old('vehicle_no', $vehicle->vehicle_no ?? '') }}"
                placeholder="e.g. UP-58-T-7566" required>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-medium">Registration No</label>
            <input class="form-control" name="registration_no"
                value="{{ old('registration_no', $vehicle->registration_no ?? '') }}"
                placeholder="Registration number">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-medium">Model</label>
            <input class="form-control" name="model"
                value="{{ old('model', $vehicle->model ?? '') }}"
                placeholder="e.g. Tata LP 407">
        </div>
    </div>
</div>

{{-- ── Section 2: Specifications ── --}}
<div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-3">
        <span class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white" style="width:28px;height:28px;font-size:13px;flex-shrink:0;">
            <i class="bi bi-gear"></i>
        </span>
        <span class="fw-semibold text-dark" style="font-size:14px;">Specifications</span>
        <hr class="flex-grow-1 my-0 ms-1">
    </div>
    <div class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label fw-medium">Seating Capacity</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-people text-muted"></i></span>
                <input type="number" min="0" class="form-control" name="capacity" id="capacityInput"
                    value="{{ old('capacity', $vehicle->capacity ?? 0) }}" placeholder="0">
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-medium">Fuel Type</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-fuel-pump text-muted"></i></span>
                <input class="form-control" name="fuel_type"
                    value="{{ old('fuel_type', $vehicle->fuel_type ?? '') }}"
                    placeholder="e.g. Diesel, CNG">
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-medium">Notes</label>
            <input class="form-control" name="notes"
                value="{{ old('notes', $vehicle->notes ?? '') }}"
                placeholder="Any additional info">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-medium d-block">Status</label>
            <div class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" role="switch" name="status" value="1"
                    id="statusToggle"
                    {{ old('status', $vehicle->status ?? true) ? 'checked' : '' }}
                    style="width:42px;height:22px;">
                <label class="form-check-label ms-1 fw-medium" for="statusToggle" id="statusLabel">
                    {{ old('status', $vehicle->status ?? true) ? 'Active' : 'Inactive' }}
                </label>
            </div>
        </div>
    </div>
</div>

{{-- ── Section 3: Vehicle Documents ── --}}
<div class="mb-2">
    <div class="d-flex align-items-center gap-2 mb-3">
        <span class="bg-warning rounded-circle d-flex align-items-center justify-content-center text-white" style="width:28px;height:28px;font-size:13px;flex-shrink:0;">
            <i class="bi bi-file-earmark-text"></i>
        </span>
        <span class="fw-semibold text-dark" style="font-size:14px;">Vehicle Documents</span>
        <hr class="flex-grow-1 my-0 ms-1">
        <button type="button" class="btn btn-sm btn-primary px-3" id="addDocRow" style="white-space:nowrap;">
            <i class="bi bi-plus-lg me-1"></i> Add Document
        </button>
    </div>

    {{-- Existing uploaded documents --}}
    @isset($existingDocuments)
        @if($existingDocuments->count())
        <div class="mb-3">
            <p class="text-muted small mb-2"><i class="bi bi-paperclip me-1"></i>Uploaded Documents</p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0" style="font-size:13px;">
                    <thead class="table-light">
                        <tr>
                            <th style="width:180px;">Document Type</th>
                            <th>File</th>
                            <th style="width:130px;">Expiry Date</th>
                            <th>Notes</th>
                            <th style="width:90px;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($existingDocuments as $doc)
                        <tr>
                            <td>
                                <span class="badge text-bg-primary bg-opacity-10 text-primary fw-medium px-2 py-1" style="font-size:12px;">
                                    {{ $doc->display_name }}
                                </span>
                            </td>
                            <td>
                                <i class="bi bi-file-earmark-pdf text-danger me-1"></i>
                                <span class="text-truncate" style="max-width:200px;display:inline-block;vertical-align:middle;">
                                    {{ $doc->original_name ?? basename($doc->file_path) }}
                                </span>
                            </td>
                            <td>
                                @if($doc->expiry_date)
                                    <span class="{{ $doc->expiry_date->isPast() ? 'text-danger fw-semibold' : 'text-success' }}">
                                        <i class="bi bi-{{ $doc->expiry_date->isPast() ? 'exclamation-circle' : 'calendar-check' }} me-1"></i>
                                        {{ $doc->expiry_date->format('d-m-Y') }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $doc->notes ?: '—' }}</td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank"
                                        class="btn btn-xs btn-outline-secondary px-2 py-1" title="View" style="font-size:12px;">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form method="POST" action="{{ route('transport.vehicles.documents.destroy', [$vehicle, $doc]) }}"
                                        onsubmit="return confirm('Delete this document?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-xs btn-outline-danger px-2 py-1" title="Delete" style="font-size:12px;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    @endisset

    {{-- New document rows added dynamically --}}
    <div id="newDocRows"></div>

    <div id="noDocMsg" class="text-center py-3 text-muted small {{ isset($existingDocuments) && $existingDocuments->count() ? 'd-none' : '' }}">
        <i class="bi bi-folder2-open fs-4 d-block mb-1 text-muted opacity-50"></i>
        No documents yet. Click <strong>Add Document</strong> to upload.
    </div>
</div>

{{-- Save Button --}}
<div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
    <a href="{{ route('transport.vehicles.index') }}" class="btn btn-light px-4">Cancel</a>
    <button type="submit" class="btn btn-primary px-5">
        <i class="bi bi-floppy me-1"></i> Save Vehicle
    </button>
</div>

{{-- ── Document Row Template ── --}}
<template id="docRowTemplate">
    <div class="card border-0 bg-light mb-2 new-doc-row" style="border-left:3px solid #0d6efd !important;">
        <div class="card-body py-2 px-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label form-label-sm fw-medium mb-1">Document Type <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm doc-type-select" name="documents[__IDX__][document_type]" required>
                        <option value="">— Select —</option>
                        @foreach($documentTypes ?? [] as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 doc-name-col" style="display:none">
                    <label class="form-label form-label-sm fw-medium mb-1">Custom Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" name="documents[__IDX__][document_name]" placeholder="Enter name">
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm fw-medium mb-1">Upload File <span class="text-danger">*</span></label>
                    <input type="file" class="form-control form-control-sm" name="documents[__IDX__][file]"
                        accept=".pdf,.jpg,.jpeg,.png" required>
                    <div class="form-text" style="font-size:11px;">PDF / Image · Max 200 KB</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm fw-medium mb-1">Expiry Date</label>
                    <input type="date" class="form-control form-control-sm date-limit"
                        name="documents[__IDX__][expiry_date]" min="1900-01-01" max="2999-12-31">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm fw-medium mb-1">Notes</label>
                    <input type="text" class="form-control form-control-sm" name="documents[__IDX__][doc_notes]" placeholder="Optional">
                </div>
                <div class="col-md-auto d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-doc-row" title="Remove">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
(() => {
    // Vehicle type → auto-fill capacity
    const typeSelect    = document.getElementById('vehicleTypeSelect');
    const capacityInput = document.getElementById('capacityInput');
    if (typeSelect && capacityInput) {
        typeSelect.addEventListener('change', () => {
            const opt = typeSelect.options[typeSelect.selectedIndex];
            const cap = parseInt(opt?.dataset?.capacity ?? '0', 10);
            if (cap > 0) capacityInput.value = cap;
        });
    }

    // Status toggle label
    const statusToggle = document.getElementById('statusToggle');
    const statusLabel  = document.getElementById('statusLabel');
    if (statusToggle && statusLabel) {
        statusToggle.addEventListener('change', () => {
            statusLabel.textContent = statusToggle.checked ? 'Active' : 'Inactive';
        });
    }

    // Limit year to 4 digits on date inputs
    function bindDateLimit(input) {
        input.addEventListener('input', function () {
            const val = this.value;
            if (!val) return;
            const parts = val.split('-');
            if (parts[0] && parts[0].length > 4) {
                parts[0] = parts[0].slice(0, 4);
                this.value = parts.join('-');
            }
        });
    }
    document.querySelectorAll('input[type="date"].date-limit').forEach(bindDateLimit);

    // Add document row
    const hasExistingDocs = {{ (isset($existingDocuments) && $existingDocuments->count()) ? 'true' : 'false' }};
    let docIdx      = 0;
    const newDocRows = document.getElementById('newDocRows');
    const template   = document.getElementById('docRowTemplate');
    const noDocMsg   = document.getElementById('noDocMsg');

    document.getElementById('addDocRow').addEventListener('click', () => {
        if (noDocMsg) noDocMsg.classList.add('d-none');

        const clone = template.content.cloneNode(true);
        clone.querySelectorAll('[name]').forEach(el => {
            el.name = el.name.replace('__IDX__', docIdx);
        });

        const typeSelect = clone.querySelector('.doc-type-select');
        const nameCol    = clone.querySelector('.doc-name-col');

        typeSelect.addEventListener('change', () => {
            const isOther = typeSelect.value === 'Other';
            nameCol.style.display = isOther ? '' : 'none';
            nameCol.querySelector('input').required = isOther;
        });

        clone.querySelector('.remove-doc-row').addEventListener('click', function () {
            this.closest('.new-doc-row').remove();
            if (!newDocRows.querySelector('.new-doc-row') && noDocMsg && !hasExistingDocs) {
                noDocMsg.classList.remove('d-none');
            }
        });

        clone.querySelectorAll('input[type="date"].date-limit').forEach(bindDateLimit);
        newDocRows.appendChild(clone);
        docIdx++;
    });
})();
</script>
