@extends('institute.layout')
@section('title', 'Import Preview')
@section('breadcrumb', 'Admissions / Bulk Import / Preview')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-table text-primary me-2"></i> Import Preview</h4>
        <small class="text-muted">Session: <strong>{{ $session->name }}</strong></small>
    </div>
    <a href="{{ route('admissions.bulk-import.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Upload Different File
    </a>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100" style="border-left:3px solid #16a34a !important;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-success bg-opacity-10 p-2">
                        <i class="bi bi-check-circle text-success fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Valid Rows</div>
                        <div class="fw-bold fs-5 text-success">{{ count($validRows) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100" style="border-left:3px solid #d97706 !important;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-warning bg-opacity-10 p-2">
                        <i class="bi bi-exclamation-triangle text-warning fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Minor Issues</div>
                        <div class="fw-bold fs-5 text-warning">{{ count($softRows) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100" style="border-left:3px solid #dc2626 !important;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-danger bg-opacity-10 p-2">
                        <i class="bi bi-x-circle text-danger fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Rows with Errors</div>
                        <div class="fw-bold fs-5 text-danger">{{ count($invalidRows) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-2">
                        <i class="bi bi-file-earmark-spreadsheet text-primary fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Rows</div>
                        <div class="fw-bold fs-5">{{ count($validRows) + count($softRows) + count($invalidRows) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-info bg-opacity-10 p-2">
                        <i class="bi bi-calendar text-info fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Session</div>
                        <div class="fw-bold" style="font-size:0.9rem;">{{ $session->name }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Alert: hard-invalid rows --}}
@if(count($invalidRows) > 0)
<div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2">
    <i class="bi bi-x-circle-fill fs-5 mt-1 flex-shrink-0"></i>
    <div>
        <strong>{{ count($invalidRows) }} row(s)</strong> are missing a mandatory field (Name / Mobile / Course / Stream / Semester)
        and will NOT be imported. Fix them in your Excel file and re-upload.
    </div>
    <button type="button" class="btn-close ms-auto flex-shrink-0" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Alert: soft-issue rows --}}
@if(count($softRows) > 0)
<div class="alert alert-warning alert-dismissible fade show d-flex align-items-start gap-2">
    <i class="bi bi-exclamation-triangle-fill fs-5 mt-1 flex-shrink-0"></i>
    <div>
        <strong>{{ count($softRows) }} row(s)</strong> have minor issues in optional fields only (mandatory fields are fine).
        You choose below whether to import them anyway — the problematic fields will just be left blank.
    </div>
    <button type="button" class="btn-close ms-auto flex-shrink-0" data-bs-dismiss="alert"></button>
</div>
@endif

@if(count($validRows) === 0 && count($softRows) === 0)
<div class="alert alert-danger">
    <i class="bi bi-x-circle-fill me-2"></i>
    <strong>No importable rows found.</strong> All rows are missing a mandatory field. Please fix the issues in your Excel file and upload again.
</div>
@endif

{{-- Confirm Import Button --}}
@if(count($validRows) > 0 || count($softRows) > 0)
<div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #16a34a !important;">
    <div class="card-body py-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <div class="fw-semibold text-success">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    Ready to import <span id="readyCount">{{ count($validRows) }}</span> student(s)
                </div>
                <small class="text-muted">
                    Rows with mandatory-field errors ({{ count($invalidRows) }}) will always be skipped.
                    This action cannot be undone.
                </small>
            </div>
            <button type="button" class="btn btn-success px-4" data-bs-toggle="modal" data-bs-target="#importConfirmModal">
                <i class="bi bi-cloud-upload me-1"></i>
                Confirm Import
            </button>
        </div>
        @if(count($softRows) > 0)
        <div class="form-check mt-3 pt-3 border-top">
            <input class="form-check-input" type="checkbox" id="includeSoftTop" data-soft-checkbox>
            <label class="form-check-label small" for="includeSoftTop">
                Also import the <strong>{{ count($softRows) }}</strong> row(s) with minor issues (their problematic fields will be left blank)
            </label>
        </div>
        @endif
    </div>
</div>
@endif

{{-- Valid Rows Table --}}
@if(count($validRows) > 0)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
        <span class="fw-semibold small text-success">
            <i class="bi bi-check-circle-fill me-1"></i>
            Valid Rows ({{ count($validRows) }}) — Will be imported
        </span>
        <button class="btn btn-outline-secondary btn-sm" type="button"
                data-bs-toggle="collapse" data-bs-target="#validTable">
            Show / Hide
        </button>
    </div>
    <div class="collapse show" id="validTable">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:12px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Row</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Course</th>
                        <th>Stream</th>
                        <th class="text-center">Sem</th>
                        <th>Status</th>
                        <th>UID</th>
                        <th>Roll No</th>
                        <th>Enrollment</th>
                        <th>Father</th>
                        <th>Mother</th>
                        <th>Gender</th>
                        <th>Category</th>
                        <th>Source</th>
                        <th>Prev. Due</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($validRows as $row)
                    <tr>
                        <td class="ps-3 text-muted">{{ $row['row_num'] }}</td>
                        <td class="fw-semibold">{{ $row['name'] }}</td>
                        <td>{{ $row['mobile'] }}</td>
                        <td class="text-muted small">{{ $row['course_name'] }}</td>
                        <td class="text-muted small">{{ $row['stream_name'] }}</td>
                        <td class="text-center">
                            <span class="badge bg-primary bg-opacity-10 text-primary border" style="font-size:10px;">
                                S{{ $row['current_semester'] }}
                            </span>
                        </td>
                        <td>
                            @if(($row['student_status'] ?? 'active') === 'active')
                                <span class="badge bg-success bg-opacity-10 text-success fw-normal" style="font-size:10px;">Active</span>
                            @else
                                <span class="badge bg-warning bg-opacity-25 text-warning-emphasis fw-normal" style="font-size:10px;">
                                    {{ ucfirst(str_replace('_', ' ', $row['student_status'])) }}
                                </span>
                            @endif
                        </td>
                        <td class="text-muted" style="font-size:11px;">
                            {!! $row['student_uid'] ? e($row['student_uid']) : '<em class="text-success">Auto</em>' !!}
                        </td>
                        <td class="text-muted small">{{ $row['roll_no'] ?? '—' }}</td>
                        <td class="text-muted small">{{ $row['enrollment_no'] ?? '—' }}</td>
                        <td class="text-muted small">{{ $row['father_name'] ?? '—' }}</td>
                        <td class="text-muted small">{{ $row['mother_name'] ?? '—' }}</td>
                        <td>
                            @if($row['gender'])
                                <span class="badge bg-secondary bg-opacity-10 text-secondary fw-normal" style="font-size:10px;">
                                    {{ ucfirst($row['gender']) }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($row['category'])
                                <span class="badge bg-secondary bg-opacity-10 text-secondary fw-normal" style="font-size:10px;">
                                    {{ strtoupper($row['category']) }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ ucfirst($row['admission_source']) }}</td>
                        <td class="text-muted small">
                            @if(!empty($row['semester_dues']))
                                @php $totalDue = array_sum($row['semester_dues']); @endphp
                                <span class="text-danger fw-semibold">₹{{ number_format($totalDue, 0) }}</span>
                                <span class="text-muted" style="font-size:10px;">
                                    (Sem {{ implode(', ', array_keys($row['semester_dues'])) }})
                                </span>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Soft-Issue Rows Table --}}
@if(count($softRows) > 0)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
        <span class="fw-semibold small text-warning">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Rows with Minor Issues ({{ count($softRows) }}) — Optional fields only
        </span>
        <button class="btn btn-outline-secondary btn-sm" type="button"
                data-bs-toggle="collapse" data-bs-target="#softTable">
            Show / Hide
        </button>
    </div>
    <div class="collapse show" id="softTable">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" style="font-size:12px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Row</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Course</th>
                        <th>Stream</th>
                        <th class="text-center">Sem</th>
                        <th>Minor Issues</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($softRows as $row)
                    <tr class="table-warning" style="--bs-table-bg:rgba(217,119,6,0.06);">
                        <td class="ps-3 fw-semibold">{{ $row['row_num'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['mobile'] }}</td>
                        <td class="text-muted small">{{ $row['course_name'] }}</td>
                        <td class="text-muted small">{{ $row['stream_name'] }}</td>
                        <td class="text-center text-muted small">{{ $row['current_semester'] }}</td>
                        <td>
                            @foreach($row['soft_errors'] as $err)
                                <div class="d-flex align-items-start gap-1 mb-1">
                                    <i class="bi bi-exclamation-circle-fill text-warning flex-shrink-0 mt-1" style="font-size:10px;"></i>
                                    <span class="text-warning-emphasis small">{{ $err }}</span>
                                </div>
                            @endforeach
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Invalid Rows Table --}}
@if(count($invalidRows) > 0)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
        <span class="fw-semibold small text-danger">
            <i class="bi bi-x-circle-fill me-1"></i>
            Rows with Errors ({{ count($invalidRows) }}) — Will NOT be imported
        </span>
        <button class="btn btn-outline-secondary btn-sm" type="button"
                data-bs-toggle="collapse" data-bs-target="#invalidTable">
            Show / Hide
        </button>
    </div>
    <div class="collapse show" id="invalidTable">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" style="font-size:12px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Row</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Course</th>
                        <th>Stream</th>
                        <th class="text-center">Sem</th>
                        <th>Errors</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invalidRows as $row)
                    <tr class="table-danger" style="--bs-table-bg:rgba(220,53,69,0.05);">
                        <td class="ps-3 fw-semibold">{{ $row['row_num'] }}</td>
                        <td>{{ $row['name'] ?: '<em class="text-muted">—</em>' }}</td>
                        <td>{{ $row['mobile'] ?: '—' }}</td>
                        <td class="text-muted small">{{ $row['course_name'] ?: '—' }}</td>
                        <td class="text-muted small">{{ $row['stream_name'] ?: '—' }}</td>
                        <td class="text-center text-muted small">{{ $row['current_semester'] ?: '—' }}</td>
                        <td>
                            @foreach($row['errors'] as $err)
                                <div class="d-flex align-items-start gap-1 mb-1">
                                    <i class="bi bi-exclamation-circle-fill text-danger flex-shrink-0 mt-1" style="font-size:10px;"></i>
                                    <span class="text-danger small">{{ $err }}</span>
                                </div>
                            @endforeach
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Bottom Confirm Button --}}
@if(count($validRows) > 0 || count($softRows) > 0)
<div class="d-flex justify-content-between align-items-center">
    <a href="{{ route('admissions.bulk-import.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Upload Different File
    </a>
    <button type="button" class="btn btn-success px-5" data-bs-toggle="modal" data-bs-target="#importConfirmModal">
        <i class="bi bi-cloud-upload me-1"></i>
        Confirm &amp; Import
    </button>
</div>
@endif

{{-- Import Confirmation Modal --}}
@if(count($validRows) > 0 || count($softRows) > 0)
<div class="modal fade" id="importConfirmModal" tabindex="-1" aria-labelledby="importConfirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-success bg-opacity-10 p-2">
                        <i class="bi bi-cloud-upload text-success fs-5"></i>
                    </div>
                    <h5 class="modal-title fw-bold mb-0" id="importConfirmLabel">Confirm Import</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="alert alert-success border-0 bg-success bg-opacity-10 mb-3">
                    <div class="fw-semibold text-success mb-1">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        <span id="modalImportCount">{{ count($validRows) }}</span> student(s) will be imported
                    </div>
                    <small class="text-muted">Session: <strong>{{ $session->name }}</strong></small>
                </div>
                @if(count($softRows) > 0)
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="includeSoftModal" data-soft-checkbox>
                    <label class="form-check-label small" for="includeSoftModal">
                        Also import the <strong>{{ count($softRows) }}</strong> row(s) with minor issues (problematic fields left blank)
                    </label>
                </div>
                @endif
                @if(count($invalidRows) > 0)
                <div class="alert alert-danger border-0 bg-danger bg-opacity-10 py-2 mb-3">
                    <i class="bi bi-x-circle me-1 text-danger"></i>
                    <small><strong>{{ count($invalidRows) }} row(s)</strong> with mandatory-field errors will always be skipped.</small>
                </div>
                @endif
                <p class="text-muted small mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    This action <strong>cannot be undone</strong>. Students will be added to the system immediately.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x me-1"></i> Cancel
                </button>
                <form method="POST" action="{{ route('admissions.bulk-import.import') }}" id="importForm">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="include_soft_rows" id="includeSoftRowsField" value="0">
                    <button type="submit" class="btn btn-success px-4" id="importBtn">
                        <i class="bi bi-cloud-upload me-1"></i>
                        Yes, Import <span id="modalImportBtnCount">{{ count($validRows) }}</span> Students
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
const VALID_COUNT = {{ count($validRows) }};
const SOFT_COUNT   = {{ count($softRows) }};
const softCheckboxes = document.querySelectorAll('[data-soft-checkbox]');
const includeSoftField = document.getElementById('includeSoftRowsField');
const readyCountEl = document.getElementById('readyCount');
const modalCountEl = document.getElementById('modalImportCount');
const modalBtnCountEl = document.getElementById('modalImportBtnCount');

function syncSoftCheckboxes(checked, source) {
    softCheckboxes.forEach(cb => { if (cb !== source) cb.checked = checked; });
    const total = VALID_COUNT + (checked ? SOFT_COUNT : 0);
    if (includeSoftField) includeSoftField.value = checked ? '1' : '0';
    if (readyCountEl) readyCountEl.textContent = total;
    if (modalCountEl) modalCountEl.textContent = total;
    if (modalBtnCountEl) modalBtnCountEl.textContent = total;
}

softCheckboxes.forEach(cb => {
    cb.addEventListener('change', function () { syncSoftCheckboxes(this.checked, this); });
});

document.getElementById('importForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('importBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Importing...';
});
</script>
@endpush
