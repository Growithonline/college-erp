@extends('institute.layout')
@section('title', 'Semester Promotion')
@section('breadcrumb', 'Admissions / Semester Promotion')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-arrow-up-circle me-2 text-primary"></i>Semester Promotion</h4>
        <small class="text-muted">Promote students to the next semester within the same session</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admissions.promote.session') }}" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-calendar-arrow-up me-1"></i>Session Promotion
        </a>
        <a href="{{ route('admissions.promote.identity') }}" class="btn btn-outline-info btn-sm">
            <i class="bi bi-person-badge me-1"></i>Identity
        </a>
        <a href="{{ route('admissions.promote.report') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-earmark-text me-1"></i>Report
        </a>
    </div>
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

<div class="card border-0 shadow-sm mb-3" id="promotePanel" style="display:none;">
    <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
        <div><i class="bi bi-lightning-charge me-1"></i>Promote Selected Students</div>
        <button type="button" class="btn btn-sm btn-light" onclick="closePanel()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admissions.promote.semester.do') }}" id="promoteForm">
            @csrf
            <div id="selectedStudentInputs"></div>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label small fw-semibold">Auto Rule</label>
                    <div class="form-control form-control-sm bg-light">
                        Each selected student will be automatically promoted from their current semester to the next semester.
                        Year / Part will remain the same.
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Selected</label>
                    <div class="form-control form-control-sm bg-light">
                        <strong id="selectedCount">0</strong> students
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Remarks</label>
                    <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Optional">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-arrow-up-circle me-1"></i>Promote
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Session</label>
                <select name="session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected':'' }}>
                            {{ $s->name }}{{ $s->is_active ? ' *' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Course</label>
                <select name="course_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Courses</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ $courseId == $c->id ? 'selected':'' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">From Semester</label>
                <select name="from_semester" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0">All Semesters</option>
                    @for($i=1; $i<=8; $i++)
                        <option value="{{ $i }}" {{ $fromSem == $i ? 'selected':'' }}>Semester {{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control form-control-sm" placeholder="Name, UID, mobile...">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button class="btn btn-primary btn-sm flex-fill"><i class="bi bi-search"></i> Filter</button>
                <a href="{{ route('admissions.promote.semester') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
            <div class="col-auto ms-auto">
                <button type="button" class="btn btn-success btn-sm" onclick="openPromotePanel()" id="promoteBtn" disabled>
                    <i class="bi bi-arrow-up-circle me-1"></i>Promote Selected
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
        <span class="fw-semibold small">
            <i class="bi bi-people me-2 text-primary"></i>Students ({{ $students->total() }})
        </span>
        <div class="form-check mb-0">
            <input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleSelectAll(this)">
            <label class="form-check-label small" for="selectAll">Select All (this page)</label>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:13px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:40px;"></th>
                        <th style="min-width:110px;">Std ID</th>
                        <th style="min-width:140px;">Student</th>
                        <th style="min-width:110px;">Father Name</th>
                        <th style="min-width:110px;">Mother Name</th>
                        <th>Course / Stream</th>
                        <th>Current Year / Sem</th>
                        <th>Session</th>
                        <th class="text-end">Balance</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                    @php
                        $dueAmount = (float) ($student->promotion_due ?? 0);
                        $isDue = $dueAmount > 0;
                        $yearLabel = \App\Support\AcademicState::yearLabel(
                            $student->stream?->course?->structure_type,
                            $student->current_semester,
                            $student->coursePart?->year_number
                        );
                    @endphp
                    <tr>
                        <td class="ps-3">
                            <input type="checkbox" class="form-check-input student-cb"
                                   value="{{ $student->id }}" onchange="onCbChange()">
                        </td>
                        <td style="white-space:nowrap;">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle" style="font-size:10.5px;">
                                {{ $student->student_uid ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $student->name }}</div>
                        </td>
                        <td class="small">{{ $student->father_name ?: '-' }}</td>
                        <td class="small">{{ $student->mother_name ?: '-' }}</td>
                        <td>
                            <div class="small">{{ $student->stream->course->name ?? '-' }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ $student->stream->name ?? '' }}</div>
                        </td>
                        <td>
                            @if($yearLabel !== '—')
                                <span class="badge bg-primary bg-opacity-10 text-primary border" style="font-size:11px;">
                                    {{ $yearLabel }}
                                </span>
                            @endif
                            @if($student->current_semester)
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border ms-1" style="font-size:11px;">
                                    Sem {{ $student->current_semester }}
                                </span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $student->session->name ?? '-' }}</td>
                        <td class="text-end fw-semibold small">
                            @if($isDue)
                                <span class="text-danger">Rs {{ number_format($dueAmount, 2) }} Due</span>
                            @else
                                <span class="text-success">Clear</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($student->stream?->course?->isShortTerm())
                                <form method="POST" action="{{ route('admissions.promote.mark-complete', $student) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="completion_status" value="passed_out">
                                    <button type="submit" class="btn btn-sm btn-outline-success"
                                            onclick="return confirm('Mark this student as completed?')">
                                        <i class="bi bi-patch-check me-1"></i>Mark Complete
                                    </button>
                                </form>
                            @else
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        onclick="promoteSingle({{ $student->id }})">
                                    <i class="bi bi-arrow-up-circle me-1"></i>Promote
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-2 d-block mb-2"></i>No students found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-top-0">
        {{ $students->withQueryString()->links() }}
    </div>
</div>

<script>
function toggleSelectAll(cb) {
    document.querySelectorAll('.student-cb').forEach(c => c.checked = cb.checked);
    onCbChange();
}

function onCbChange() {
    const checked = document.querySelectorAll('.student-cb:checked');
    const count = checked.length;
    document.getElementById('selectedCount').textContent = count;
    document.getElementById('promoteBtn').disabled = count === 0;
    if (count > 0) {
        document.getElementById('promotePanel').style.removeProperty('display');
    }
}

function openPromotePanel() {
    document.getElementById('promotePanel').style.removeProperty('display');
    document.getElementById('promotePanel').scrollIntoView({behavior:'smooth'});
}

function closePanel() {
    document.getElementById('promotePanel').style.setProperty('display', 'none', 'important');
    document.querySelectorAll('.student-cb').forEach(c => c.checked = false);
    document.getElementById('selectAll').checked = false;
    onCbChange();
}

function promoteSingle(id) {
    document.querySelectorAll('.student-cb').forEach(c => c.checked = false);
    const cb = document.querySelector(`.student-cb[value="${id}"]`);
    if (cb) cb.checked = true;
    onCbChange();
    openPromotePanel();
}

document.getElementById('promoteForm').addEventListener('submit', function() {
    const inputs = document.getElementById('selectedStudentInputs');
    inputs.innerHTML = '';
    document.querySelectorAll('.student-cb:checked').forEach(cb => {
        inputs.innerHTML += `<input type="hidden" name="student_ids[]" value="${cb.value}">`;
    });
});
</script>
@endsection
