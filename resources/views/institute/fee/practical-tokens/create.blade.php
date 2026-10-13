@php
    $feeLayout = auth()->guard('staff')->check() ? 'staff.layout' : 'institute.layout';
@endphp
@extends($feeLayout)
@section('title', 'Create Practical Token')
@section('breadcrumb', 'Fee / Practical Tokens / Create')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Create Practical Token Batch</h4>
        <small class="text-muted">Course/subject wise total collection token</small>
    </div>
    <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route($routePrefix . '.store') }}" class="row g-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Session</label>
                <select name="academic_session_id" id="sessionSelect" class="form-select" required>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ old('academic_session_id', $activeSession?->id) == $session->id ? 'selected' : '' }}>{{ $session->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Course Type</label>
                <select id="courseTypeSelect" class="form-select">
                    <option value="">All</option>
                    @foreach($courseTypes as $courseType)
                        <option value="{{ $courseType->id }}">{{ $courseType->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Course</label>
                <select name="course_id" id="courseSelect" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($courses as $course)
                        <option
                            value="{{ $course->id }}"
                            data-type="{{ $course->course_type_id }}"
                            data-code="{{ $course->code }}"
                            data-subjects='@json($course->subject_options)'
                            data-parts='@json($course->part_options)'
                            {{ old('course_id') == $course->id ? 'selected' : '' }}
                        >{{ $course->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Semester</label>
                <select name="course_part_id" id="partSelect" class="form-select" required>
                    <option value="">Select</option>
                </select>
                <input type="hidden" name="year_number" id="yearNumber" value="{{ old('year_number', 1) }}">
                <input type="hidden" name="semester" id="semesterNumber" value="{{ old('semester', 1) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Subject</label>
                <select name="subject_id" id="subjectSelect" class="form-select" required>
                    <option value="">Select</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Token Amount</label>
                <input type="number" name="token_amount" value="{{ old('token_amount') }}" min="1" step="0.01" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Payment Mode</label>
                <select name="payment_mode" class="form-select" required>
                    @foreach(['cash' => 'Cash', 'upi' => 'UPI', 'online' => 'Online', 'cheque' => 'Cheque', 'dd' => 'DD', 'neft' => 'NEFT', 'rtgs' => 'RTGS'] as $key => $label)
                        <option value="{{ $key }}" {{ old('payment_mode', 'cash') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Practical Date</label>
                <input type="date" name="collection_date" value="{{ old('collection_date', now()->toDateString()) }}" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Title</label>
                <input type="text" name="title" id="titleInput" value="{{ old('title') }}" class="form-control" placeholder="BA Geography Practical">
            </div>
            <div class="col-12">
                <label class="form-label small fw-semibold">Remarks</label>
                <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-primary">
                    <i class="bi bi-check2 me-1"></i> Create Batch
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const sessionSelect = document.getElementById('sessionSelect');
const courseTypeSelect = document.getElementById('courseTypeSelect');
const courseSelect = document.getElementById('courseSelect');
const subjectSelect = document.getElementById('subjectSelect');
const partSelect = document.getElementById('partSelect');
const yearInput = document.getElementById('yearNumber');
const semesterInput = document.getElementById('semesterNumber');

// Rules as [session, course, subject, year, semester, fee]; semester 0 applies to every semester.
const practicalFeeRules = @json($practicalFeeRules);

function practicalFeeLabel(subjectId) {
    const opt = partSelect.options[partSelect.selectedIndex];
    if (!opt || !opt.dataset.year) {
        return '';
    }
    const [sessionId, courseId, year, semester] = [
        Number(sessionSelect.value), Number(courseSelect.value), Number(opt.dataset.year), Number(opt.dataset.semester),
    ];
    const matches = practicalFeeRules.filter(r =>
        r[0] === sessionId && r[1] === courseId && r[2] === Number(subjectId) && r[3] === year && (r[4] === semester || r[4] === 0));
    if (!matches.length) {
        return ' — Fee not set';
    }
    const exact = matches.filter(r => r[4] === semester);
    const fees = [...new Set((exact.length ? exact : matches).map(r => r[5]))].sort((a, b) => a - b);
    const fmt = n => '₹' + n.toLocaleString('en-IN', { maximumFractionDigits: 2 });
    return ' — ' + (fees.length > 1 ? fmt(fees[0]) + ' to ' + fmt(fees[fees.length - 1]) : fmt(fees[0]));
}

function refreshSubjectLabels() {
    [...subjectSelect.options].forEach(option => {
        if (option.value) {
            option.textContent = option.dataset.name + practicalFeeLabel(option.value);
        }
    });
}

const titleInput = document.getElementById('titleInput');
const dateInput = document.querySelector('input[name="collection_date"]');
// Auto-suggest the title until the user types their own; a title restored after a validation error counts as typed.
let titleEditedByUser = titleInput.value.trim() !== '';

function suggestTitle() {
    if (titleEditedByUser) {
        return;
    }
    const courseOpt = courseSelect.options[courseSelect.selectedIndex];
    const subjectOpt = subjectSelect.options[subjectSelect.selectedIndex];
    const partOpt = partSelect.options[partSelect.selectedIndex];
    const parts = [];
    if (courseOpt && courseOpt.value) {
        parts.push((courseOpt.dataset.code || courseOpt.textContent).trim());
    }
    if (subjectOpt && subjectOpt.value) {
        parts.push(subjectOpt.dataset.name + ' Practical');
    }
    if (partOpt && partOpt.value) {
        parts.push(partOpt.textContent.trim());
    }
    if (dateInput.value) {
        const d = new Date(dateInput.value + 'T00:00:00');
        parts.push(d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }));
    }
    titleInput.value = parts.join(' - ');
}

const oldSubjectId = '{{ old('subject_id') }}';
const oldCoursePartId = '{{ old('course_part_id') }}';

// Snapshot the full course option list once so we can rebuild it after filtering by course type.
const allCourseOptions = Array.from(courseSelect.options).filter(opt => opt.value !== '');

function filterCoursesByType() {
    const typeId = courseTypeSelect.value;
    const selectedCourseId = courseSelect.value;
    courseSelect.innerHTML = '<option value="">Select</option>';
    allCourseOptions.forEach(opt => {
        if (!typeId || opt.dataset.type === typeId) {
            courseSelect.appendChild(opt.cloneNode(true));
        }
    });
    // Keep the previous course selected if it still matches the chosen type.
    if ([...courseSelect.options].some(opt => opt.value === selectedCourseId)) {
        courseSelect.value = selectedCourseId;
    } else {
        fillSubjectsAndParts();
    }
}

function fillSubjectsAndParts() {
    const opt = courseSelect.options[courseSelect.selectedIndex];
    const subjects = opt && opt.dataset.subjects ? JSON.parse(opt.dataset.subjects) : [];
    const parts = opt && opt.dataset.parts ? JSON.parse(opt.dataset.parts) : [];

    subjectSelect.innerHTML = '<option value="">Select</option>';
    subjects.forEach(subject => {
        const option = document.createElement('option');
        option.value = subject.id;
        option.textContent = subject.name;
        option.dataset.name = subject.name;
        subjectSelect.appendChild(option);
    });

    partSelect.innerHTML = '<option value="">Select</option>';
    parts.forEach(part => {
        const option = document.createElement('option');
        option.value = part.id;
        option.textContent = part.part_name;
        option.dataset.year = part.year_number;
        option.dataset.semester = part.part_number;
        partSelect.appendChild(option);
    });

    if (oldSubjectId && [...subjectSelect.options].some(o => o.value === oldSubjectId)) {
        subjectSelect.value = oldSubjectId;
    }
    suggestTitle();
    if (oldCoursePartId && [...partSelect.options].some(o => o.value === oldCoursePartId)) {
        partSelect.value = oldCoursePartId;
    }
    syncPartHiddenFields();
}

function syncPartHiddenFields() {
    const opt = partSelect.options[partSelect.selectedIndex];
    yearInput.value = opt && opt.dataset.year ? opt.dataset.year : 1;
    semesterInput.value = opt && opt.dataset.semester ? opt.dataset.semester : 1;
    refreshSubjectLabels();
    suggestTitle();
}

courseTypeSelect.addEventListener('change', filterCoursesByType);
courseSelect.addEventListener('change', fillSubjectsAndParts);
partSelect.addEventListener('change', syncPartHiddenFields);
sessionSelect.addEventListener('change', refreshSubjectLabels);
subjectSelect.addEventListener('change', suggestTitle);
dateInput.addEventListener('change', suggestTitle);
titleInput.addEventListener('input', () => { titleEditedByUser = titleInput.value.trim() !== ''; });

// Init on load: preselect the course type matching an old course_id (validation re-render), then cascade.
if (courseSelect.value) {
    const selectedOpt = courseSelect.options[courseSelect.selectedIndex];
    if (selectedOpt && selectedOpt.dataset.type) {
        courseTypeSelect.value = selectedOpt.dataset.type;
    }
}
filterCoursesByType();
if ('{{ old('course_id') }}') {
    courseSelect.value = '{{ old('course_id') }}';
}
fillSubjectsAndParts();
</script>
@endpush
