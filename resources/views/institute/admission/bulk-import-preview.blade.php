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
    <div class="col-6 col-md-3">
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
    <div class="col-6 col-md-3">
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
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-2">
                        <i class="bi bi-file-earmark-spreadsheet text-primary fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Rows</div>
                        <div class="fw-bold fs-5">{{ count($validRows) + count($invalidRows) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
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

{{-- Alert: invalid rows --}}
@if(count($invalidRows) > 0)
<div class="alert alert-warning alert-dismissible fade show d-flex align-items-start gap-2">
    <i class="bi bi-exclamation-triangle-fill fs-5 mt-1 flex-shrink-0"></i>
    <div>
        <strong>{{ count($invalidRows) }} row(s) have errors</strong> and will NOT be imported.
        Review them below, fix in your Excel file, and re-upload to import them.
        Only the <strong>{{ count($validRows) }} valid row(s)</strong> will be imported when you confirm.
    </div>
    <button type="button" class="btn-close ms-auto flex-shrink-0" data-bs-dismiss="alert"></button>
</div>
@endif

@if(count($validRows) === 0)
<div class="alert alert-danger">
    <i class="bi bi-x-circle-fill me-2"></i>
    <strong>No valid rows found.</strong> All rows have errors. Please fix the issues in your Excel file and upload again.
</div>
@endif

{{-- Confirm Import Button --}}
@if(count($validRows) > 0)
<div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #16a34a !important;">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3 py-3">
        <div>
            <div class="fw-semibold text-success">
                <i class="bi bi-check-circle-fill me-1"></i>
                Ready to import {{ count($validRows) }} student(s)
            </div>
            <small class="text-muted">
                Invalid rows ({{ count($invalidRows) }}) will be skipped.
                This action cannot be undone.
            </small>
        </div>
        <button type="button" class="btn btn-success px-4" data-bs-toggle="modal" data-bs-target="#importConfirmModal">
            <i class="bi bi-cloud-upload me-1"></i>
            Confirm Import ({{ count($validRows) }} students)
        </button>
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
                        <th>UID</th>
                        <th>Roll No</th>
                        <th>Enrollment</th>
                        <th>Father</th>
                        <th>Mother</th>
                        <th>Gender</th>
                        <th>Category</th>
                        <th>Source</th>
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
@if(count($validRows) > 0)
<div class="d-flex justify-content-between align-items-center">
    <a href="{{ route('admissions.bulk-import.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Upload Different File
    </a>
    <button type="button" class="btn btn-success px-5" data-bs-toggle="modal" data-bs-target="#importConfirmModal">
        <i class="bi bi-cloud-upload me-1"></i>
        Confirm &amp; Import {{ count($validRows) }} Students
    </button>
</div>
@endif

{{-- Import Confirmation Modal --}}
@if(count($validRows) > 0)
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
                        {{ count($validRows) }} student(s) will be imported
                    </div>
                    <small class="text-muted">Session: <strong>{{ $session->name }}</strong></small>
                </div>
                @if(count($invalidRows) > 0)
                <div class="alert alert-warning border-0 bg-warning bg-opacity-10 py-2 mb-3">
                    <i class="bi bi-exclamation-triangle me-1 text-warning"></i>
                    <small><strong>{{ count($invalidRows) }} row(s)</strong> with errors will be skipped.</small>
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
                    <button type="submit" class="btn btn-success px-4" id="importBtn">
                        <i class="bi bi-cloud-upload me-1"></i>
                        Yes, Import {{ count($validRows) }} Students
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
document.getElementById('importForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('importBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Importing...';
});
</script>
@endpush
