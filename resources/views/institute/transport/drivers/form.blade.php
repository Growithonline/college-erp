@php($driver = $driver ?? null)

{{-- ── Section 1: Personal Information ── --}}
<div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-3">
        <span class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" style="width:28px;height:28px;font-size:13px;flex-shrink:0;">
            <i class="bi bi-person"></i>
        </span>
        <span class="fw-semibold text-dark" style="font-size:14px;">Personal Information</span>
        <hr class="flex-grow-1 my-0 ms-1">
    </div>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label fw-medium">Full Name <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person-fill text-muted"></i></span>
                <input class="form-control @error('name') is-invalid @enderror" name="name"
                    value="{{ old('name', $driver->name ?? '') }}"
                    placeholder="Driver's full name" required maxlength="120">
            </div>
            @error('name')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label fw-medium">Mobile</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-phone text-muted"></i></span>
                <input class="form-control @error('mobile') is-invalid @enderror" name="mobile"
                    value="{{ old('mobile', $driver->mobile ?? '') }}"
                    maxlength="10" inputmode="numeric" pattern="\d{10}" placeholder="10-digit number">
            </div>
            @error('mobile')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label fw-medium">Notes</label>
            <input class="form-control @error('notes') is-invalid @enderror" name="notes"
                value="{{ old('notes', $driver->notes ?? '') }}"
                placeholder="Any additional info">
            @error('notes')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

{{-- ── Section 2: License & Helper ── --}}
<div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-3">
        <span class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white" style="width:28px;height:28px;font-size:13px;flex-shrink:0;">
            <i class="bi bi-card-text"></i>
        </span>
        <span class="fw-semibold text-dark" style="font-size:14px;">License & Helper Details</span>
        <hr class="flex-grow-1 my-0 ms-1">
    </div>
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-medium">License No</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-credit-card text-muted"></i></span>
                <input class="form-control text-uppercase @error('license_no') is-invalid @enderror" name="license_no"
                    value="{{ old('license_no', $driver->license_no ?? '') }}"
                    placeholder="e.g. UP14 20110012345" maxlength="80">
            </div>
            @error('license_no')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label fw-medium">Helper Name</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person text-muted"></i></span>
                <input class="form-control @error('helper_name') is-invalid @enderror" name="helper_name"
                    value="{{ old('helper_name', $driver->helper_name ?? '') }}"
                    placeholder="Helper / Conductor name" maxlength="120">
            </div>
            @error('helper_name')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label fw-medium">Helper Mobile</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-phone text-muted"></i></span>
                <input class="form-control @error('helper_mobile') is-invalid @enderror" name="helper_mobile"
                    value="{{ old('helper_mobile', $driver->helper_mobile ?? '') }}"
                    maxlength="10" inputmode="numeric" pattern="\d{10}" placeholder="10-digit">
            </div>
            @error('helper_mobile')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-1">
            <label class="form-label fw-medium d-block">Status</label>
            <div class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" role="switch" name="status" value="1"
                    id="statusToggle"
                    {{ old('status', $driver->status ?? true) ? 'checked' : '' }}
                    style="width:42px;height:22px;">
                <label class="form-check-label ms-1 fw-medium" for="statusToggle" id="statusLabel"
                    style="white-space:nowrap;">
                    {{ old('status', $driver->status ?? true) ? 'Active' : 'Inactive' }}
                </label>
            </div>
        </div>
    </div>
</div>

{{-- ── Section 3: Documents ── --}}
<div class="mb-2">
    <div class="d-flex align-items-center gap-2 mb-3">
        <span class="bg-warning rounded-circle d-flex align-items-center justify-content-center text-white" style="width:28px;height:28px;font-size:13px;flex-shrink:0;">
            <i class="bi bi-file-earmark-text"></i>
        </span>
        <span class="fw-semibold text-dark" style="font-size:14px;">Driver Documents</span>
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
                                        class="btn btn-outline-secondary px-2 py-1" title="View" style="font-size:12px;">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form method="POST" action="{{ route('transport.drivers.documents.destroy', [$driver, $doc]) }}"
                                        onsubmit="return confirm('Delete this document?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger px-2 py-1" title="Delete" style="font-size:12px;">
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

    <div id="newDocRows"></div>

    <div id="noDocMsg" class="text-center py-3 text-muted small {{ isset($existingDocuments) && $existingDocuments->count() ? 'd-none' : '' }}">
        <i class="bi bi-folder2-open fs-4 d-block mb-1 text-muted opacity-50"></i>
        No documents yet. Click <strong>Add Document</strong> to upload.
    </div>
</div>

{{-- Save Button --}}
<div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
    <a href="{{ route('transport.drivers.index') }}" class="btn btn-light px-4">Cancel</a>
    <button type="submit" class="btn btn-primary px-5">
        <i class="bi bi-floppy me-1"></i> Save Driver
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
    // Status toggle label
    const statusToggle = document.getElementById('statusToggle');
    const statusLabel  = document.getElementById('statusLabel');
    if (statusToggle && statusLabel) {
        statusToggle.addEventListener('change', () => {
            statusLabel.textContent = statusToggle.checked ? 'Active' : 'Inactive';
        });
    }

    // Limit year to 4 digits
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
    let docIdx       = 0;
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
