@extends('institute.layout')
@section('title', 'Admissions')
@section('breadcrumb', 'Admissions')

@section('content')
@php
    $selectedSessionId = request()->has('session_id') ? request('session_id') : $activeSession?->id;
    $selectedAdmittedBy = array_map('strval', (array) request('admitted_by', []));
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Admissions</h4>
        <small class="text-muted">
            Session: <span class="fw-semibold text-primary">{{ $activeSession?->name ?? 'No Active Session' }}</span>
            | {{ number_format($students->total()) }} student(s)
        </small>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admissions.quick-create') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-lightning-fill me-1"></i>Quick Register
        </a>
        <a href="{{ route('admissions.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>New Admission
        </a>
    </div>
</div>

<form method="GET" action="{{ route('admissions.index') }}" id="admissionFilterForm" autocomplete="off">
<input type="hidden" name="export" id="exportField" value="">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-bottom-0 py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2"
             style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#advFilterBody" aria-expanded="{{ request()->hasAny(['search','session_id','course_type_id','course_id','course_stream_id','subject_id','course_part_id','current_semester','admission_source','source_detail_id','status','admission_date_from','admission_date_to','admitted_by','form_status']) ? 'true' : 'false' }}">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-funnel text-primary"></i>
                <span class="fw-bold small">Advanced Filters</span>
                <small class="text-muted d-none d-md-inline">Applied filters will affect both the data table and the export file.</small>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-outline-success btn-sm" onclick="event.stopPropagation(); submitExport('excel')">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel
                </button>
                <button type="button" class="btn btn-outline-success btn-sm" onclick="event.stopPropagation(); submitExport('csv')">
                    <i class="bi bi-filetype-csv me-1"></i>CSV
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="event.stopPropagation(); submitExport('pdf')">
                    <i class="bi bi-filetype-pdf me-1"></i>PDF
                </button>
                <i class="bi bi-chevron-down text-muted" id="filterChevron"></i>
            </div>
        </div>
        <div class="collapse {{ request()->hasAny(['search','session_id','course_type_id','course_id','course_stream_id','subject_id','course_part_id','current_semester','admission_source','source_detail_id','status','admission_date_from','admission_date_to','admitted_by','form_status']) ? 'show' : '' }}" id="advFilterBody">
        <div class="card-body pt-2">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Name, mobile, UID, father name...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Session</label>
                    <select name="session_id" class="form-select form-select-sm">
                        <option value="">All Sessions</option>
                        @foreach($sessions as $sess)
                            <option value="{{ $sess->id }}" {{ (string) $selectedSessionId === (string) $sess->id ? 'selected' : '' }}>
                                {{ $sess->name }}{{ $sess->is_active ? ' (Active)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Course Type</label>
                    <select name="course_type_id" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        @foreach($courseTypes as $courseType)
                            <option value="{{ $courseType->id }}" {{ request('course_type_id') == $courseType->id ? 'selected' : '' }}>
                                {{ $courseType->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Course</label>
                    <select name="course_id" class="form-select form-select-sm">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Subject / Stream</label>
                    <select name="course_stream_id" class="form-select form-select-sm">
                        <option value="">All Streams</option>
                        @foreach($streams as $stream)
                            <option value="{{ $stream->id }}" {{ request('course_stream_id') == $stream->id ? 'selected' : '' }}>
                                {{ $stream->course->name ?? '' }} - {{ $stream->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Subject</label>
                    <select name="subject_id" class="form-select form-select-sm">
                        <option value="">All Subjects</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Course Year</label>
                    <select name="course_part_id" class="form-select form-select-sm">
                        <option value="">All Years</option>
                        @foreach($parts as $part)
                            <option value="{{ $part->id }}" {{ request('course_part_id') == $part->id ? 'selected' : '' }}>
                                {{ $part->course->name ?? '' }} - {{ $part->year_label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Semester</label>
                    <select name="current_semester" class="form-select form-select-sm">
                        <option value="">All Semesters</option>
                        @for($semester = 1; $semester <= 12; $semester++)
                            <option value="{{ $semester }}" {{ request('current_semester') == $semester ? 'selected' : '' }}>
                                Semester {{ $semester }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Source</label>
                    <select name="admission_source" id="sourceSelect" class="form-select form-select-sm" onchange="onSourceChange(this.value)">
                        <option value="">All Sources</option>
                        @foreach(['direct' => 'Direct', 'center' => 'Center', 'channel_partner' => 'Channel Partner'] as $sourceValue => $sourceLabel)
                            <option value="{{ $sourceValue }}" {{ request('admission_source') === $sourceValue ? 'selected' : '' }}>
                                {{ $sourceLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3" id="sourceDetailWrap" style="{{ request('admission_source') === 'center' || request('admission_source') === 'channel_partner' ? '' : 'display:none;' }}">
                    <label class="form-label small fw-semibold" id="sourceDetailLabel">
                        {{ request('admission_source') === 'channel_partner' ? 'Channel Partner' : 'Center' }}
                    </label>
                    <select name="source_detail_id" id="sourceDetailSelect" class="form-select form-select-sm">
                        <option value="">— Select —</option>
                        <optgroup label="Centers" id="centerOptions" {{ request('admission_source') === 'channel_partner' ? 'style=display:none' : '' }}>
                            @foreach($centers as $center)
                                <option value="{{ $center->id }}" {{ request('source_detail_id') == $center->id && request('admission_source') === 'center' ? 'selected' : '' }}>
                                    {{ $center->name }}{{ $center->code ? ' ('.$center->code.')' : '' }}
                                </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Channel Partners" id="partnerOptions" {{ request('admission_source') !== 'channel_partner' ? 'style=display:none' : '' }}>
                            @foreach($partners as $partner)
                                <option value="{{ $partner->id }}" {{ request('source_detail_id') == $partner->id && request('admission_source') === 'channel_partner' ? 'selected' : '' }}>
                                    {{ $partner->name }}
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        @foreach(['pending', 'active', 'inactive', 'detained', 'passed_out', 'transferred', 'cancelled'] as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Admission Date From</label>
                    <input type="date" name="admission_date_from" value="{{ request('admission_date_from') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Admission Date To</label>
                    <input type="date" name="admission_date_to" value="{{ request('admission_date_to') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Admitted By</label>
                    <select name="admitted_by[]" class="form-select form-select-sm" multiple size="4">
                        <option value="admin:0" {{ in_array('admin:0', $selectedAdmittedBy, true) ? 'selected' : '' }}>Admin / Direct</option>
                        @foreach($staffMembers as $staffMember)
                            <option value="staff:{{ $staffMember->id }}" {{ in_array('staff:'.$staffMember->id, $selectedAdmittedBy, true) ? 'selected' : '' }}>
                                Staff: {{ $staffMember->name }}
                            </option>
                        @endforeach
                        @foreach($centers as $center)
                            <option value="center:{{ $center->id }}" {{ in_array('center:'.$center->id, $selectedAdmittedBy, true) ? 'selected' : '' }}>
                                Center: {{ $center->name }}{{ $center->code ? ' ('.$center->code.')' : '' }}
                            </option>
                        @endforeach
                        @foreach($partners as $partner)
                            <option value="partner:{{ $partner->id }}" {{ in_array('partner:'.$partner->id, $selectedAdmittedBy, true) ? 'selected' : '' }}>
                                Partner: {{ $partner->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Ctrl/Cmd dabake multiple select kar sakte ho.</small>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Form Status</label>
                    <select name="form_status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="complete" {{ request('form_status') === 'complete' ? 'selected' : '' }}>Complete</option>
                        <option value="incomplete" {{ request('form_status') === 'incomplete' ? 'selected' : '' }}>Incomplete</option>
                    </select>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-funnel me-1"></i>Apply Filters
                    </button>
                    <a href="{{ route('admissions.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                    </a>
                </div>
            </div>
        </div>
        </div>{{-- /collapse --}}
    </div>
</form>

@push('scripts')
<script>
function submitExport(type) {
    const form = document.getElementById('admissionFilterForm');
    document.getElementById('exportField').value = type;
    if (type === 'pdf') form.target = '_blank';
    else form.target = '';
    form.submit();
    // reset so normal Apply Filters submit doesn't carry export param
    setTimeout(() => { document.getElementById('exportField').value = ''; form.target = ''; }, 300);
}

function onSourceChange(val) {
    const wrap = document.getElementById('sourceDetailWrap');
    const label = document.getElementById('sourceDetailLabel');
    const centerOpts = document.getElementById('centerOptions');
    const partnerOpts = document.getElementById('partnerOptions');
    const select = document.getElementById('sourceDetailSelect');

    if (val === 'center') {
        wrap.style.display = '';
        label.textContent = 'Center';
        centerOpts.style.display = '';
        partnerOpts.style.display = 'none';
        select.value = '';
    } else if (val === 'channel_partner') {
        wrap.style.display = '';
        label.textContent = 'Channel Partner';
        centerOpts.style.display = 'none';
        partnerOpts.style.display = '';
        select.value = '';
    } else {
        wrap.style.display = 'none';
        select.value = '';
    }
}

// Chevron rotate on collapse toggle
document.addEventListener('DOMContentLoaded', function () {
    const collapseEl = document.getElementById('advFilterBody');
    const chevron = document.getElementById('filterChevron');
    if (collapseEl && chevron) {
        collapseEl.addEventListener('show.bs.collapse', () => chevron.style.transform = 'rotate(180deg)');
        collapseEl.addEventListener('hide.bs.collapse', () => chevron.style.transform = 'rotate(0deg)');
        if (collapseEl.classList.contains('show')) chevron.style.transform = 'rotate(180deg)';
    }
});
</script>
@endpush

@if(!empty($appliedFilters))
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="small fw-semibold text-muted mb-2">Applied Filters</div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($appliedFilters as $label => $value)
                    <span class="badge rounded-pill text-bg-light border px-3 py-2">
                        {{ $label }}: {{ $value }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:13px;">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">#</th>
                    <th>Student</th>
                    <th>Father Name</th>
                    <th>Mother Name</th>
                    <th>Student ID</th>
                    <th>Course</th>
                    <th>Year / Sem</th>
                    <th>Admission Date</th>
                    <th>Admitted By</th>
                    <th>Source</th>
                    <th>Form Status</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $i => $student)
                    @php
                        $isComplete = !empty($student->name)
                            && !empty($student->mobile)
                            && !empty($student->father_name)
                            && !empty($student->dob)
                            && !empty($student->gender)
                            && !empty($student->category)
                            && !empty($student->course_stream_id)
                            && !empty($student->admission_date);

                        $src = $student->admission_source ?? 'direct';
                        $srcColors = [
                            'direct' => 'success',
                            'center' => 'info',
                            'channel_partner' => 'warning',
                        ];
                        $srcColor = $srcColors[$src] ?? 'secondary';

                        $admittedByLabel = $student->admittedBy?->name
                            ? 'Staff: '.$student->admittedBy->name
                            : 'Admin / Direct';

                        if ($src === 'center') {
                            $centerName = $centers->firstWhere('id', (int) $student->admission_source_id)?->name ?? 'Center';
                            $admittedByLabel = 'Center: '.$centerName;
                        } elseif ($src === 'channel_partner') {
                            $partnerName = $partners->firstWhere('id', (int) $student->admission_source_id)?->name ?? 'Partner';
                            $admittedByLabel = 'Partner: '.$partnerName;
                        }
                    @endphp
                    <tr class="{{ !$isComplete ? 'table-warning bg-opacity-25' : '' }}">
                        <td class="ps-3 text-muted small">{{ $students->firstItem() + $i }}</td>
                        <td>
                            <div class="fw-semibold">{{ $student->name }}</div>
                            <div class="text-muted small">{{ $student->mobile ?: '-' }}</div>
                        </td>
                        <td class="small">{{ $student->father_name ?: '-' }}</td>
                        <td class="small">{{ $student->mother_name ?: '-' }}</td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">
                                {{ $student->student_uid }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold small">{{ $student->stream?->course?->name ?? '-' }}</div>
                            <div class="text-muted" style="font-size:11px;">
                                {{ $student->stream?->name ?? '-' }}
                                @if($student->studentSubjects->isNotEmpty())
                                    | {{ $student->studentSubjects->pluck('subject.name')->filter()->unique()->implode(', ') }}
                                @endif
                            </div>
                        </td>
                        <td class="small text-muted">
                            {{ $student->coursePart?->year_label ?? '-' }}
                            @if($student->current_semester)
                                <span class="badge bg-primary bg-opacity-10 text-primary border ms-1">S{{ $student->current_semester }}</span>
                            @endif
                        </td>
                        <td class="small text-muted">
                            {{ $student->admission_date?->format('d M Y') ?? '-' }}
                        </td>
                        <td class="small">
                            <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle text-wrap">
                                {{ $admittedByLabel }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $srcColor }} bg-opacity-10 text-{{ $srcColor }} border">
                                {{ ucwords(str_replace('_', ' ', $src)) }}
                            </span>
                        </td>
                        <td>
                            @if($isComplete)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">Complete</span>
                            @else
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle">Incomplete</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ ($student->status ?? 'active') === 'pending' ? 'bg-warning text-dark' : (($student->status ?? 'active') === 'active' ? 'bg-success' : 'bg-secondary') }}">
                                {{ ucfirst((string) ($student->status ?? 'active')) }}
                            </span>
                        </td>
                        <td class="text-end pe-3">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('admissions.show', $student->id) }}" class="btn btn-outline-primary btn-sm" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admissions.edit', $student->id) }}" class="btn btn-outline-warning btn-sm" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="{{ route('fee.create') }}?student_id={{ $student->id }}" class="btn btn-outline-success btn-sm" title="Collect Fee">
                                    <i class="bi bi-cash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="text-center py-5 text-muted">
                            <i class="bi bi-person-plus fs-2 d-block mb-2 opacity-25"></i>
                            No admissions found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-top-0">
        @include('institute.components.pagination', ['paginator' => $students, 'perPage' => $perPage ?? 20])
    </div>
</div>
@endsection
