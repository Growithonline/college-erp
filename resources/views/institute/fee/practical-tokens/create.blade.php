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
                <select name="academic_session_id" class="form-select" required>
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
                            data-subjects='@json($course->streams->flatMap->subjects->unique("id")->sortBy("name")->values()->map(fn($s) => ["id" => $s->id, "name" => $s->name])->values())'
                            data-parts='@json($course->parts->map(fn($p) => ["id" => $p->id, "part_number" => $p->part_number, "part_name" => $p->part_name, "year_number" => $p->year_number]))'
                            {{ old('course_id') == $course->id ? 'selected' : '' }}
                        >{{ $course->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Subject</label>
                <select name="subject_id" id="subjectSelect" class="form-select" required>
                    <option value="">Select course first</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Semester</label>
                <select name="course_part_id" id="partSelect" class="form-select" required>
                    <option value="">Select course first</option>
                </select>
                <input type="hidden" name="year_number" id="yearNumber" value="{{ old('year_number', 1) }}">
                <input type="hidden" name="semester" id="semesterNumber" value="{{ old('semester', 1) }}">
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
                <input type="text" name="title" value="{{ old('title') }}" class="form-control" placeholder="BA Geography Practical">
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
const courseTypeSelect = document.getElementById('courseTypeSelect');
const courseSelect = document.getElementById('courseSelect');
const subjectSelect = document.getElementById('subjectSelect');
const partSelect = document.getElementById('partSelect');
const yearInput = document.getElementById('yearNumber');
const semesterInput = document.getElementById('semesterNumber');

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
    if (oldCoursePartId && [...partSelect.options].some(o => o.value === oldCoursePartId)) {
        partSelect.value = oldCoursePartId;
    }
    syncPartHiddenFields();
}

function syncPartHiddenFields() {
    const opt = partSelect.options[partSelect.selectedIndex];
    yearInput.value = opt && opt.dataset.year ? opt.dataset.year : 1;
    semesterInput.value = opt && opt.dataset.semester ? opt.dataset.semester : 1;
}

courseTypeSelect.addEventListener('change', filterCoursesByType);
courseSelect.addEventListener('change', fillSubjectsAndParts);
partSelect.addEventListener('change', syncPartHiddenFields);

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
