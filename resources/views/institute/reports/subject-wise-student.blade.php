@php
    $isStaff = auth()->guard('staff')->check();
    $layout = $isStaff ? 'staff.layout' : 'institute.layout';
    $subjectWiseRoute = $isStaff ? 'staff.reports.subject-wise-student' : 'reports.subject-wise-student';
    $streamsRoute = $isStaff ? 'staff.reports.streams' : 'reports.streams';
@endphp
@extends($layout)
@section('title', 'Subject Wise Student')
@section('breadcrumb', 'Reports / Subject Wise Student')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Subject Wise Student</h4>
        <small class="text-muted">Students with their allocated subjects, grouped by Major / Minor</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" target="_blank"
           class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}"
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
           class="btn btn-outline-success btn-sm">
            <i class="bi bi-filetype-csv me-1"></i> CSV
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-4" id="filterCard">
    <div class="card-body">
        <form method="GET" action="{{ route($subjectWiseRoute) }}" id="filterForm">
            <div class="row g-3">

                {{-- Session --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Session</label>
                    <select name="session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($sessions as $s)
                            <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Course Type --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Course Type</label>
                    <select name="course_type_id" id="courseTypeFilter" class="form-select form-select-sm">
                        <option value="">— All Types —</option>
                        @foreach($courseTypes as $ct)
                            <option value="{{ $ct->id }}" {{ request('course_type_id') == $ct->id ? 'selected' : '' }}>{{ $ct->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Course --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Course</label>
                    <select name="course_id" id="courseFilter" class="form-select form-select-sm">
                        <option value="">— All Courses —</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Stream --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Stream</label>
                    <select name="stream_id" id="streamFilter" class="form-select form-select-sm">
                        <option value="">— All Streams —</option>
                        @foreach($streams as $st)
                            <option value="{{ $st->id }}" {{ request('stream_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Semester --}}
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Semester</label>
                    <select name="semester" class="form-select form-select-sm">
                        <option value="0" {{ ($filterSemester ?? 0) == 0 ? 'selected' : '' }}>All</option>
                        @for($i=1;$i<=8;$i++)
                            <option value="{{ $i }}" {{ ($filterSemester ?? 0) == $i ? 'selected' : '' }}>S{{ $i }}</option>
                        @endfor
                    </select>
                </div>

                {{-- Search --}}
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control form-control-sm"
                           placeholder="Name, father/mother, UID, roll/enroll no...">
                </div>

                {{-- Buttons --}}
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="{{ route($subjectWiseRoute) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-lg"></i> Reset
                    </a>
                </div>
            </div>
            <input type="hidden" name="per_page" value="{{ $perPage }}">
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm" id="reportTable">
    <div class="card-body p-0">
        @if($students->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-people fs-1 opacity-50"></i>
                <div class="mt-2">No students found. Try changing the filters.</div>
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Std ID</th>
                        <th>Form No</th>
                        <th>UIN No</th>
                        <th>Roll No</th>
                        <th>Enroll No</th>
                        <th>Name</th>
                        <th>Father Name</th>
                        <th>Mother Name</th>
                        <th>Course Type</th>
                        <th>Course</th>
                        <th class="text-center">Sem</th>
                        <th>Major Subjects</th>
                        <th>Minor Subjects</th>
                        <th>Other Subjects</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $i => $student)
                    <tr>
                        <td class="ps-3 text-muted">{{ $students->firstItem() + $i }}</td>
                        <td class="small text-muted">{{ $student->student_uid ?: '—' }}</td>
                        <td class="small text-muted">{{ $student->institute_form_no ?: '—' }}</td>
                        <td class="small text-muted">{{ $student->uin_no ?: '—' }}</td>
                        <td class="small text-muted">{{ $student->roll_no ?: '—' }}</td>
                        <td class="small text-muted">{{ $student->enrollment_no ?: '—' }}</td>
                        <td class="fw-semibold">{{ $student->name }}</td>
                        <td class="small">{{ $student->father_name ?: '—' }}</td>
                        <td class="small">{{ $student->mother_name ?: '—' }}</td>
                        <td class="small">{{ $student->stream?->course?->type?->name ?? '—' }}</td>
                        <td class="small">{{ $student->stream?->course?->name ?? '—' }}</td>
                        <td class="text-center">
                            <span class="badge bg-secondary bg-opacity-10 text-secondary fw-normal">
                                {{ $student->current_semester ? 'S'.$student->current_semester : '—' }}
                            </span>
                        </td>
                        <td class="small">{{ $student->major_subjects ?: '—' }}</td>
                        <td class="small">{{ $student->minor_subjects ?: '—' }}</td>
                        <td class="small">{{ $student->other_subjects ?: '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-3 pb-3">
            @include('institute.components.pagination', [
                'paginator' => $students,
                'perPage'   => $perPage,
            ])
        </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
const COURSES_ALL = @json($courses->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'type_id' => $c->course_type_id]));

// Course Type → filter courses
document.getElementById('courseTypeFilter').addEventListener('change', function() {
    const typeId = this.value;
    const sel    = document.getElementById('courseFilter');
    sel.innerHTML = '<option value="">— All Courses —</option>';
    COURSES_ALL.filter(c => !typeId || String(c.type_id) === typeId).forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id; opt.textContent = c.name;
        sel.appendChild(opt);
    });
    document.getElementById('streamFilter').innerHTML = '<option value="">— All Streams —</option>';
});

// Course → fetch streams
document.getElementById('courseFilter').addEventListener('change', function() {
    const courseId = this.value;
    const streamSel = document.getElementById('streamFilter');
    streamSel.innerHTML = '<option value="">Loading...</option>';
    if (!courseId) { streamSel.innerHTML = '<option value="">— All Streams —</option>'; return; }
    fetch(`{{ route($streamsRoute) }}?course_id=${courseId}`)
        .then(r => r.json())
        .then(data => {
            streamSel.innerHTML = '<option value="">— All Streams —</option>';
            data.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id; opt.textContent = s.name;
                streamSel.appendChild(opt);
            });
        });
});
</script>
@endpush
