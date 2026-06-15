@extends('institute.layout')
@section('title', 'Bulk Student Correction')
@section('breadcrumb', 'Admissions / Bulk Student Correction')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-file-earmark-spreadsheet me-2 text-primary"></i>Bulk Student Correction
        </h4>
        <small class="text-muted">Excel download, manual correction, UIN ke basis par bulk update</small>
    </div>
    <a href="{{ route('admissions.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-people me-1"></i>Students
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('warning'))
<div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm">
    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($errors->any())
<div class="alert alert-danger border-0 shadow-sm">
    @foreach($errors->all() as $e)<div><i class="bi bi-x-circle me-1"></i>{{ $e }}</div>@endforeach
</div>
@endif

@if(session('bulkReport'))
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <h6 class="mb-2 fw-semibold">Bulk Upload Results</h6>
        <div class="row g-2">
            <div class="col-md-4">
                <div class="border rounded p-2 bg-light small">
                    <strong>{{ session('bulkReport.updated') }}</strong> records updated
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-2 bg-light small">
                    <strong>{{ session('bulkReport.skipped') }}</strong> rows failed
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-2 bg-light small">
                    <strong>{{ session('bulkReport.total_rows') }}</strong> rows processed
                </div>
            </div>
        </div>

        @if(count(session('bulkReport.errors', [])))
        <div class="mt-3">
            <div class="fw-semibold small mb-1">Issues</div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th style="width:70px;">Row</th>
                            <th style="width:130px;">UIN</th>
                            <th>Student</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach(session('bulkReport.errors') as $error)
                        <tr>
                            <td>{{ is_array($error) ? ($error['row'] ?? '') : '' }}</td>
                            <td>{{ is_array($error) ? ($error['uin'] ?? '') : '' }}</td>
                            <td>{{ is_array($error) ? ($error['student'] ?? '') : '' }}</td>
                            <td>{{ is_array($error) ? ($error['error'] ?? '') : $error }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endif

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-0 fw-semibold">Template Excel Download</h6>
            <small class="text-muted">{{ number_format($studentsCount) }} students match current selection</small>
        </div>
        <a href="{{ route('admissions.bulk-correction.template', array_merge(request()->only(['course_id','course_part_id','current_semester']), ['session_id' => $sessionId])) }}"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-earmark-arrow-down me-1"></i>Download Excel Template
        </a>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admissions.bulk-correction') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected':'' }}>
                            {{ $s->name }}{{ $s->is_active ? ' ✓':'' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Course</label>
                <select name="course_id" class="form-select form-select-sm">
                    <option value="">All Courses</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected':'' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Year/Part</label>
                <select name="course_part_id" class="form-select form-select-sm">
                    <option value="">All Parts</option>
                    @foreach($courseParts as $part)
                        <option value="{{ $part->id }}" {{ request('course_part_id') == $part->id ? 'selected':'' }}>
                            {{ $part->course->name ?? 'Course' }} - {{ $part->year_label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Semester</label>
                <input type="number" name="current_semester" min="1" max="20" value="{{ request('current_semester') }}"
                       class="form-control form-control-sm" placeholder="All">
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary btn-sm w-100"><i class="bi bi-filter"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-semibold">Upload Corrected Excel</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admissions.bulk-correction.upload') }}" enctype="multipart/form-data" class="row g-3 align-items-end">
            @csrf
            <input type="hidden" name="session_id" value="{{ $sessionId }}">
            <input type="hidden" name="course_id" value="{{ request('course_id') }}">
            <input type="hidden" name="course_part_id" value="{{ request('course_part_id') }}">
            <input type="hidden" name="current_semester" value="{{ request('current_semester') }}">

            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Upload File</label>
                <input type="file" name="bulk_file" accept=".xlsx,.csv,text/csv" required class="form-control form-control-sm">
            </div>
            <div class="col-md-5">
                <div class="small text-muted mb-1">Next: file headers will be read, then you can map UIN and the fields to update.</div>
            </div>
            <div class="col-md-3 text-end">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-upload me-1"></i>Upload & Read Columns
                </button>
            </div>
        </form>
    </div>
</div>

@if(isset($mappingUpload))
<div class="card border-0 shadow-sm mt-3">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-0 fw-semibold">Column Mapping</h6>
            <small class="text-muted">{{ $mappingUpload['original_name'] }} | {{ number_format($mappingUpload['row_count']) }} rows detected</small>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admissions.bulk-correction.apply') }}">
            @csrf
            <input type="hidden" name="upload_token" value="{{ $mappingUpload['token'] }}">

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold mb-1">Select Where UIN Column</label>
                    <select name="identity_column" class="form-select form-select-sm" required>
                        <option value="">Select UIN column</option>
                        @foreach($mappingUpload['headers'] as $header)
                            <option value="{{ $header }}" {{ ($mappingUpload['identity_suggestion'] ?? '') === $header ? 'selected' : '' }}>
                                {{ $header }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="small text-muted pt-4">
                        Only mapped fields will be updated. Leave a field blank if you do not want to update it.
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <div class="fw-semibold">Map Update Fields</div>
                    <div class="small text-muted">Open a section, then map only the columns you want to update.</div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.querySelectorAll('.bulk-map-collapse').forEach(el => new bootstrap.Collapse(el, {show:true}))">
                        Expand All
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.querySelectorAll('.bulk-map-collapse.show').forEach(el => bootstrap.Collapse.getOrCreateInstance(el).hide())">
                        Collapse All
                    </button>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold mb-1">Search Field</label>
                            <input type="text" id="bulkMappingSearch" class="form-control form-control-sm"
                                   placeholder="Search by label or key, e.g. father, mobile, 10th, aadhar">
                        </div>
                        <div class="col-md-3">
                            <div class="form-check pt-md-4">
                                <input class="form-check-input" type="checkbox" id="bulkOnlyUnmapped">
                                <label class="form-check-label small fw-semibold" for="bulkOnlyUnmapped">
                                    Show only unmapped
                                </label>
                            </div>
                        </div>
                        <div class="col-md-2 text-md-end">
                            <div class="small text-muted">Visible Fields</div>
                            <div class="fw-semibold" id="bulkVisibleFieldCount">0</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion" id="mappingSectionsAccordion">
                @foreach($mappingFieldSections as $sectionIndex => $section)
                    @php
                        $sectionFieldsCount = count($section['fields']) + collect($section['subsections'])->sum(fn($sub) => count($sub['fields']));
                        $sectionMappedCount = collect($section['fields'])->filter(fn($field) => !empty($mappingUpload['field_suggestions'][$field['key']] ?? ''))->count()
                            + collect($section['subsections'])->sum(fn($sub) => collect($sub['fields'])->filter(fn($field) => !empty($mappingUpload['field_suggestions'][$field['key']] ?? ''))->count());
                        $sectionCollapseId = 'mapping-section-' . $sectionIndex;
                    @endphp
                    <div class="accordion-item border-0 shadow-sm mb-3 rounded-3 overflow-hidden bulk-map-section" data-section-key="{{ $section['key'] }}">
                        <h2 class="accordion-header" id="heading-{{ $sectionCollapseId }}">
                            <button class="accordion-button {{ $sectionIndex === 0 ? '' : 'collapsed' }} fw-semibold" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#{{ $sectionCollapseId }}" aria-expanded="{{ $sectionIndex === 0 ? 'true' : 'false' }}">
                                <span class="me-2"><i class="bi {{ $section['icon'] }}"></i></span>
                                <span>{{ $section['label'] }}</span>
                                <span class="ms-auto me-3 d-flex gap-2">
                                    <span class="badge bg-light text-dark border">{{ $sectionFieldsCount }} fields</span>
                                    <span class="badge {{ $sectionMappedCount ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' }}">
                                        {{ $sectionMappedCount }} mapped
                                    </span>
                                </span>
                            </button>
                        </h2>
                        <div id="{{ $sectionCollapseId }}" class="accordion-collapse collapse bulk-map-collapse {{ $sectionIndex === 0 ? 'show' : '' }}">
                            <div class="accordion-body bg-light-subtle">
                                @if(!empty($section['fields']))
                                    <div class="card border-0 shadow-sm mb-3">
                                        <div class="card-body p-3">
                                            <div class="row g-3">
                                                @foreach($section['fields'] as $field)
                                                <div class="col-12 bulk-map-field"
                                                     data-field-key="{{ strtolower($field['key']) }}"
                                                     data-field-label="{{ strtolower($field['label']) }}">
                                                    <div class="row g-2 align-items-center">
                                                        <div class="col-md-5">
                                                            <div class="fw-semibold">{{ $field['label'] }}</div>
                                                            <div class="small text-muted">{{ $field['key'] }}</div>
                                                        </div>
                                                        <div class="col-md-7">
                                                            <select name="field_map[{{ $field['key'] }}]" class="form-select form-select-sm bulk-map-select">
                                                                <option value="">Selected None</option>
                                                                @foreach($mappingUpload['headers'] as $header)
                                                                    <option value="{{ $header }}" {{ ($mappingUpload['field_suggestions'][$field['key']] ?? '') === $header ? 'selected' : '' }}>
                                                                        {{ $header }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if(!empty($section['subsections']))
                                    <div class="row g-3">
                                        @foreach($section['subsections'] as $subIndex => $subsection)
                                            @php
                                                $subMappedCount = collect($subsection['fields'])->filter(fn($field) => !empty($mappingUpload['field_suggestions'][$field['key']] ?? ''))->count();
                                            @endphp
                                            <div class="col-12">
                                                <div class="card border-0 shadow-sm h-100 bulk-map-subsection" data-subsection-key="{{ $subsection['key'] }}">
                                                    <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                                                        <div class="fw-semibold small">{{ $subsection['label'] }}</div>
                                                        <div class="d-flex gap-2">
                                                            <span class="badge bg-light text-dark border">{{ count($subsection['fields']) }} fields</span>
                                                            <span class="badge {{ $subMappedCount ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' }}">
                                                                {{ $subMappedCount }} mapped
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="card-body p-3">
                                                        <div class="row g-3">
                                                            @foreach($subsection['fields'] as $field)
                                                            <div class="col-12 bulk-map-field"
                                                                 data-field-key="{{ strtolower($field['key']) }}"
                                                                 data-field-label="{{ strtolower($field['label']) }}">
                                                                <div class="row g-2 align-items-center">
                                                                    <div class="col-md-5">
                                                                        <div class="fw-semibold">{{ $field['label'] }}</div>
                                                                        <div class="small text-muted">{{ $field['key'] }}</div>
                                                                    </div>
                                                                    <div class="col-md-7">
                                                                        <select name="field_map[{{ $field['key'] }}]" class="form-select form-select-sm bulk-map-select">
                                                                            <option value="">Selected None</option>
                                                                            @foreach($mappingUpload['headers'] as $header)
                                                                                <option value="{{ $header }}" {{ ($mappingUpload['field_suggestions'][$field['key']] ?? '') === $header ? 'selected' : '' }}>
                                                                                    {{ $header }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if(!empty($mappingUpload['sample_rows']))
            <div class="mt-3">
                <div class="fw-semibold small mb-2">Sample Preview</div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm small mb-0">
                        <thead class="table-light">
                            <tr>
                                @foreach($mappingUpload['headers'] as $header)
                                    <th>{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mappingUpload['sample_rows'] as $sampleRow)
                            <tr>
                                @foreach($mappingUpload['headers'] as $idx => $header)
                                    <td>{{ $sampleRow[$idx] ?? '' }}</td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-check2-square me-1"></i>Apply Mapped Updates
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('bulkMappingSearch');
    const onlyUnmappedCheckbox = document.getElementById('bulkOnlyUnmapped');
    const visibleCount = document.getElementById('bulkVisibleFieldCount');
    const fields = Array.from(document.querySelectorAll('.bulk-map-field'));
    const sections = Array.from(document.querySelectorAll('.bulk-map-section'));
    const collapses = Array.from(document.querySelectorAll('.bulk-map-collapse'));

    if (!fields.length) {
        return;
    }

    function isMapped(field) {
        const select = field.querySelector('.bulk-map-select');
        return !!(select && String(select.value || '').trim() !== '');
    }

    function updateCounts() {
        sections.forEach(section => {
            const visibleFields = section.querySelectorAll('.bulk-map-field:not([style*="display: none"])').length;
            section.style.display = visibleFields ? '' : 'none';

            section.querySelectorAll('.bulk-map-subsection').forEach(subsection => {
                const subsectionVisibleFields = subsection.querySelectorAll('.bulk-map-field:not([style*="display: none"])').length;
                subsection.style.display = subsectionVisibleFields ? '' : 'none';
            });
        });

        if (visibleCount) {
            visibleCount.textContent = document.querySelectorAll('.bulk-map-field:not([style*="display: none"])').length;
        }
    }

    function applyFilters() {
        const search = String(searchInput?.value || '').trim().toLowerCase();
        const onlyUnmapped = !!onlyUnmappedCheckbox?.checked;

        fields.forEach(field => {
            const haystack = `${field.dataset.fieldLabel || ''} ${field.dataset.fieldKey || ''}`;
            const matchesSearch = !search || haystack.includes(search);
            const matchesMappedState = !onlyUnmapped || !isMapped(field);
            field.style.display = matchesSearch && matchesMappedState ? '' : 'none';
        });

        updateCounts();

        if (search || onlyUnmapped) {
            collapses.forEach(collapse => {
                const visibleFieldsInside = collapse.querySelectorAll('.bulk-map-field:not([style*="display: none"])').length;
                const instance = bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false });
                if (visibleFieldsInside) {
                    instance.show();
                } else {
                    instance.hide();
                }
            });
        }
    }

    searchInput?.addEventListener('input', applyFilters);
    onlyUnmappedCheckbox?.addEventListener('change', applyFilters);
    document.querySelectorAll('.bulk-map-select').forEach(select => {
        select.addEventListener('change', applyFilters);
    });

    applyFilters();
});
</script>
@endpush
