@php($vehicle = $vehicle ?? null)
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">Vehicle Type</label>
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
    <div class="col-md-3"><label class="form-label">Vehicle No *</label><input class="form-control" name="vehicle_no" value="{{ old('vehicle_no', $vehicle->vehicle_no ?? '') }}" required></div>
    <div class="col-md-3"><label class="form-label">Registration No</label><input class="form-control" name="registration_no" value="{{ old('registration_no', $vehicle->registration_no ?? '') }}"></div>
    <div class="col-md-3"><label class="form-label">Model</label><input class="form-control" name="model" value="{{ old('model', $vehicle->model ?? '') }}"></div>
    <div class="col-md-3"><label class="form-label">Capacity</label><input type="number" min="0" class="form-control" name="capacity" id="capacityInput" value="{{ old('capacity', $vehicle->capacity ?? 0) }}"></div>
    <div class="col-md-3"><label class="form-label">Fuel Type</label><input class="form-control" name="fuel_type" value="{{ old('fuel_type', $vehicle->fuel_type ?? '') }}"></div>
    <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="status" value="1" {{ old('status', $vehicle->status ?? true) ? 'checked' : '' }}><label class="form-check-label">Active</label></div></div>
    <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="3">{{ old('notes', $vehicle->notes ?? '') }}</textarea></div>
</div>

{{-- Vehicle Documents --}}
<div class="mt-4">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="fw-semibold mb-0">Vehicle Documents</h6>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addDocRow">
            <i class="bi bi-plus-circle me-1"></i> Add Document
        </button>
    </div>

    <div id="docRows">
        {{-- existing documents on edit page --}}
        @isset($existingDocuments)
            @foreach($existingDocuments as $doc)
            <div class="card border mb-2 doc-existing-card">
                <div class="card-body py-2 px-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-2 fw-semibold small text-primary">
                            {{ $doc->display_name }}
                        </div>
                        <div class="col-md-3 small text-muted text-truncate">
                            <i class="bi bi-file-earmark-pdf text-danger me-1"></i>{{ $doc->original_name ?? basename($doc->file_path) }}
                        </div>
                        <div class="col-md-2 small text-muted">
                            @if($doc->expiry_date)
                                Expiry: {{ $doc->expiry_date->format('d-m-Y') }}
                            @endif
                        </div>
                        <div class="col-md-3 small text-muted">{{ $doc->notes }}</div>
                        <div class="col-md-2 d-flex gap-2 justify-content-end">
                            <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <form method="POST" action="{{ route('transport.vehicles.documents.destroy', [$vehicle, $doc]) }}" onsubmit="return confirm('Delete this document?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        @endisset
    </div>

    <div id="newDocRows"></div>
</div>

<div class="mt-4 d-flex justify-content-end">
    <button class="btn btn-primary">Save Vehicle</button>
</div>

{{-- Document Row Template --}}
<template id="docRowTemplate">
    <div class="card border mb-2 new-doc-row">
        <div class="card-body py-2 px-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1">Document Type *</label>
                    <select class="form-select form-select-sm doc-type-select" name="documents[__IDX__][document_type]" required>
                        <option value="">— Select —</option>
                        @foreach($documentTypes ?? [] as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 doc-name-col" style="display:none">
                    <label class="form-label form-label-sm mb-1">Document Name</label>
                    <input type="text" class="form-control form-control-sm" name="documents[__IDX__][document_name]" placeholder="Enter name">
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1">Upload File (PDF/Image) *</label>
                    <input type="file" class="form-control form-control-sm" name="documents[__IDX__][file]" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Expiry Date</label>
                    <input type="date" class="form-control form-control-sm date-limit" name="documents[__IDX__][expiry_date]" min="1900-01-01" max="2999-12-31">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Notes</label>
                    <input type="text" class="form-control form-control-sm" name="documents[__IDX__][doc_notes]" placeholder="Optional">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-doc-row" title="Remove"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
(() => {
    const typeSelect     = document.getElementById('vehicleTypeSelect');
    const capacityInput  = document.getElementById('capacityInput');
    if (typeSelect && capacityInput) {
        typeSelect.addEventListener('change', () => {
            const opt = typeSelect.options[typeSelect.selectedIndex];
            const cap = parseInt(opt?.dataset?.capacity ?? '0', 10);
            if (cap > 0) capacityInput.value = cap;
        });
    }

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

    let docIdx = 0;
    const newDocRows = document.getElementById('newDocRows');
    const template   = document.getElementById('docRowTemplate');

    document.getElementById('addDocRow').addEventListener('click', () => {
        const clone = template.content.cloneNode(true);
        clone.querySelectorAll('[name]').forEach(el => {
            el.name = el.name.replace('__IDX__', docIdx);
        });
        const row = clone.querySelector('.new-doc-row');
        const typeSelect = clone.querySelector('.doc-type-select');
        const nameCol    = clone.querySelector('.doc-name-col');

        typeSelect.addEventListener('change', () => {
            nameCol.style.display = typeSelect.value === 'Other' ? '' : 'none';
            nameCol.querySelector('input').required = typeSelect.value === 'Other';
        });

        clone.querySelector('.remove-doc-row').addEventListener('click', function () {
            this.closest('.new-doc-row').remove();
        });

        clone.querySelectorAll('input[type="date"].date-limit').forEach(bindDateLimit);
        newDocRows.appendChild(clone);
        docIdx++;
    });
})();
</script>
