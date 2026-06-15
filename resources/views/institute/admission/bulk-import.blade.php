@extends('institute.layout')
@section('title', 'Bulk Student Import')
@section('breadcrumb', 'Admissions / Bulk Import')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-file-earmark-arrow-up text-primary me-2"></i> Bulk Student Import</h4>
        <small class="text-muted">Import previous students from Excel file — up to 500 rows per file</small>
    </div>
    <a href="{{ route('admissions.bulk-import.template') }}"
       class="btn btn-success btn-sm">
        <i class="bi bi-download me-1"></i> Download Template
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Error:</strong> {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- How it works --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-4">
                <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width:48px;height:48px;">
                    <i class="bi bi-download text-success fs-5"></i>
                </div>
                <div class="fw-semibold small">Step 1</div>
                <div class="small text-muted">Download the Excel template</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-4">
                <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width:48px;height:48px;">
                    <i class="bi bi-pencil-square text-primary fs-5"></i>
                </div>
                <div class="fw-semibold small">Step 2</div>
                <div class="small text-muted">Fill student data, delete example rows</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-4">
                <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width:48px;height:48px;">
                    <i class="bi bi-upload text-warning fs-5"></i>
                </div>
                <div class="fw-semibold small">Step 3</div>
                <div class="small text-muted">Upload file — review preview &amp; errors</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-4">
                <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width:48px;height:48px;">
                    <i class="bi bi-check2-circle text-success fs-5"></i>
                </div>
                <div class="fw-semibold small">Step 4</div>
                <div class="small text-muted">Confirm valid rows to import</div>
            </div>
        </div>
    </div>
</div>

{{-- Upload Form --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-cloud-upload me-2 text-primary"></i>Upload Excel File</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admissions.bulk-import.preview') }}"
              enctype="multipart/form-data" id="uploadForm">
            @csrf

            <div class="row g-3">
                {{-- Session --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Academic Session <span class="text-danger">*</span></label>
                    <select name="session_id" class="form-select" required>
                        @foreach($sessions as $sess)
                            <option value="{{ $sess->id }}"
                                {{ $sess->id == ($activeSession?->id) ? 'selected' : '' }}>
                                {{ $sess->name }}
                                @if($sess->is_active) (Active) @endif
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Students will be created under this session.</div>
                </div>

                {{-- File Upload --}}
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Excel File <span class="text-danger">*</span></label>
                    <input type="file" name="file" id="fileInput" accept=".xlsx,.xls"
                           class="form-control" required>
                    <div class="form-text">Only .xlsx or .xls. Max 5 MB. Max 500 rows.</div>
                </div>

                {{-- Submit --}}
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100" id="previewBtn">
                        <i class="bi bi-eye me-1"></i> Preview & Validate
                    </button>
                </div>
            </div>

            {{-- File info bar --}}
            <div id="fileInfoBar" class="mt-3 d-none">
                <div class="alert alert-info py-2 mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-excel text-success fs-5"></i>
                    <span id="fileInfoText" class="small"></span>
                </div>
            </div>

            {{-- Loading indicator --}}
            <div id="loadingBar" class="mt-3 d-none">
                <div class="d-flex align-items-center gap-2 text-muted">
                    <div class="spinner-border spinner-border-sm text-primary"></div>
                    <span class="small">Parsing and validating file, please wait...</span>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Rules card --}}
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-bottom py-2">
        <span class="fw-semibold small"><i class="bi bi-info-circle me-1 text-info"></i> Important Rules</span>
    </div>
    <div class="card-body py-3">
        <div class="row g-3">
            <div class="col-md-6">
                <ul class="list-unstyled small mb-0">
                    <li class="mb-1"><i class="bi bi-check-circle text-success me-1"></i> Columns marked <strong>*</strong> are required (Name, Mobile, Course, Stream, Semester)</li>
                    <li class="mb-1"><i class="bi bi-check-circle text-success me-1"></i> Course &amp; Stream names must exactly match system entries (see template sheet "Courses_Streams")</li>
                    <li class="mb-1"><i class="bi bi-check-circle text-success me-1"></i> Delete example rows before uploading</li>
                    <li class="mb-1"><i class="bi bi-check-circle text-success me-1"></i> Dates in DD/MM/YYYY format</li>
                </ul>
            </div>
            <div class="col-md-6">
                <ul class="list-unstyled small mb-0">
                    <li class="mb-1"><i class="bi bi-check-circle text-success me-1"></i> Leave Student UID blank — system will auto-generate</li>
                    <li class="mb-1"><i class="bi bi-check-circle text-success me-1"></i> Duplicate mobile numbers or UIDs will be flagged as errors</li>
                    <li class="mb-1"><i class="bi bi-check-circle text-success me-1"></i> Maximum 500 rows per file, max 5 MB</li>
                    <li class="mb-1"><i class="bi bi-check-circle text-success me-1"></i> Only valid rows are imported — invalid rows are skipped</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const fileInput  = document.getElementById('fileInput');
const fileBar    = document.getElementById('fileInfoBar');
const fileText   = document.getElementById('fileInfoText');
const loadingBar = document.getElementById('loadingBar');
const previewBtn = document.getElementById('previewBtn');
const form       = document.getElementById('uploadForm');

fileInput.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) { fileBar.classList.add('d-none'); return; }

    // Client-side type check
    const ext = file.name.split('.').pop().toLowerCase();
    if (!['xlsx', 'xls'].includes(ext)) {
        fileBar.classList.remove('d-none');
        fileText.textContent = '✗ Invalid file type. Only .xlsx or .xls files are allowed.';
        fileBar.querySelector('.alert').className = 'alert alert-danger py-2 mb-0 d-flex align-items-center gap-2';
        this.value = '';
        return;
    }

    // Client-side size check (5 MB)
    if (file.size > 5 * 1024 * 1024) {
        fileBar.classList.remove('d-none');
        fileText.textContent = '✗ File is too large. Maximum size is 5 MB.';
        fileBar.querySelector('.alert').className = 'alert alert-danger py-2 mb-0 d-flex align-items-center gap-2';
        this.value = '';
        return;
    }

    const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
    fileBar.classList.remove('d-none');
    fileBar.querySelector('.alert').className = 'alert alert-info py-2 mb-0 d-flex align-items-center gap-2';
    fileText.textContent = `${file.name}  —  ${sizeMB} MB`;
});

form.addEventListener('submit', function () {
    previewBtn.disabled = true;
    previewBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Validating...';
    loadingBar.classList.remove('d-none');
});
</script>
@endpush
