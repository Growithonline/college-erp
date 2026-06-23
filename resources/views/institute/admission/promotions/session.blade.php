@extends('institute.layout')
@section('title', 'Session Promotion')
@section('breadcrumb', 'Admissions / Session Promotion')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-calendar-arrow-up me-2 text-warning"></i>Session Promotion</h4>
        <small class="text-muted">Auto-promote students to the next session / next year after the final semester</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admissions.promote.semester') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-up-circle me-1"></i>Semester Promotion
        </a>
        <a href="{{ route('admissions.promote.identity') }}" class="btn btn-outline-info btn-sm">
            <i class="bi bi-person-badge me-1"></i>Identity
        </a>
        <a href="{{ route('admissions.promote.outcomes') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-award me-1"></i>Outcomes
        </a>
        <a href="{{ route('admissions.promote.report') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-earmark-text me-1"></i>Report
        </a>
    </div>
</div>

@if(session('success'))
<div class="d-flex align-items-start gap-3 mb-3 p-3 rounded-3 shadow-sm alert-dismissible fade show"
     style="background:#f0fdf4;border:1px solid #86efac;" role="alert">
    <div style="width:32px;height:32px;border-radius:8px;background:#dcfce7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="bi bi-check-circle-fill" style="color:#16a34a;font-size:15px;"></i>
    </div>
    <div class="flex-grow-1" style="font-size:13px;color:#166534;font-weight:500;padding-top:5px;">{{ session('success') }}</div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size:11px;"></button>
</div>
@endif
@if(session('warning'))
<div class="d-flex align-items-start gap-3 mb-3 p-3 rounded-3 shadow-sm alert-dismissible fade show"
     style="background:#fffbeb;border:1px solid #fcd34d;" role="alert">
    <div style="width:32px;height:32px;border-radius:8px;background:#fef9c3;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="bi bi-exclamation-triangle-fill" style="color:#b45309;font-size:15px;"></i>
    </div>
    <div class="flex-grow-1" style="font-size:13px;color:#92400e;font-weight:500;padding-top:5px;">{{ session('warning') }}</div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size:11px;"></button>
</div>
@endif
@if($errors->any())
<div class="d-flex align-items-start gap-3 mb-3 p-3 rounded-3 shadow-sm"
     style="background:#fff1f0;border:1px solid #fca5a5;">
    <div style="width:32px;height:32px;border-radius:8px;background:#fee2e2;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
        <i class="bi bi-x-circle-fill" style="color:#dc2626;font-size:15px;"></i>
    </div>
    <div style="flex-grow:1;">
        @foreach($errors->all() as $e)
        <div style="font-size:13px;color:#991b1b;font-weight:500;{{ !$loop->first ? 'margin-top:4px;' : '' }}">{{ $e }}</div>
        @endforeach
    </div>
</div>
@endif

<div class="card border-0 shadow-sm mb-3" id="promotePanel" style="display:none;">
    <div class="card-header bg-warning text-dark py-2 d-flex justify-content-between align-items-center">
        <div><i class="bi bi-lightning-charge me-1"></i>Promote Selected Students</div>
        <button type="button" class="btn btn-sm btn-light" onclick="closePanel()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admissions.promote.session.do') }}" id="promoteForm">
            @csrf
            <div id="selectedStudentInputs"></div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">To Session
                        <span class="text-muted fw-normal" style="font-size:10px;">(optional for final-year students)</span>
                    </label>
                    <select name="to_session_id" class="form-select form-select-sm">
                        <option value="">— Not Required (last year / terminal) —</option>
                        @foreach($sessions as $s)
                            <option value="{{ $s->id }}" {{ $toSessionId == $s->id ? 'selected':'' }}>
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Auto Rule</label>
                    <div class="form-control form-control-sm bg-light">
                        Non-final year students will be automatically promoted to the next session.
                        For final year students, the selected <strong>Final Outcome</strong> will be applied (default: Passed Out).
                    </div>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Selected</label>
                    <div class="form-control form-control-sm bg-light">
                        <strong id="selectedCount">0</strong>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Final Outcome
                        <span class="text-muted fw-normal" style="font-size:10px;">(last year only)</span>
                    </label>
                    <select name="completion_status" class="form-select form-select-sm">
                        <option value="">&#8594; Promote to Next Session</option>
                        <option value="passed_out">Passed Out</option>
                        <option value="backlog">Backlog</option>
                        <option value="failed">Fail</option>
                        <option value="dropped">Drop</option>
                    </select>
                </div>
                <div class="col-md-12 col-lg-2">
                    <label class="form-label small fw-semibold">Remarks</label>
                    <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Optional">
                </div>
                <div class="col-12 small text-muted">
                    Outstanding dues will be carried forward to the new session wallet with the source session and semester recorded. Final-year students will not be promoted to the next session — their selected final outcome will be saved instead.
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-warning btn-sm fw-semibold">
                        <i class="bi bi-calendar-arrow-up me-1"></i>Promote
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
                <label class="form-label small fw-semibold mb-1">From Session</label>
                <select name="from_session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ $fromSessionId == $s->id ? 'selected':'' }}>
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
                <label class="form-label small fw-semibold mb-1">To Session</label>
                <select name="to_session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Select</option>
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ $toSessionId == $s->id ? 'selected':'' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control form-control-sm" placeholder="Name, UID, mobile...">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button class="btn btn-primary btn-sm flex-fill"><i class="bi bi-search"></i> Filter</button>
                <a href="{{ route('admissions.promote.session') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
            <div class="col-auto ms-auto">
                <button type="button" class="btn btn-warning btn-sm fw-semibold" onclick="openPromotePanel()" id="promoteBtn" disabled>
                    <i class="bi bi-calendar-arrow-up me-1"></i>Promote Selected
                </button>
            </div>
        </form>
    </div>
</div>

<div class="alert alert-info border-0 py-2 mb-3 small">
    <i class="bi bi-info-circle me-2"></i>
    <strong>From:</strong> {{ $fromSessionObj?->name ?? 'Select a session' }}
    <i class="bi bi-arrow-right mx-2"></i>
    <strong>To:</strong> {{ $toSessionObj?->name ?? 'Select in the promote panel' }}
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
        <span class="fw-semibold small">
            <i class="bi bi-people me-2 text-warning"></i>Students ({{ $students->total() }})
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
                        <th class="text-end">Dues</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                    @php
                        $due = (float) ($student->promotion_due ?? 0);
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
                                <span class="badge bg-warning bg-opacity-20 text-warning-emphasis border" style="font-size:11px;">
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
                            @if($due > 0)
                                <span class="text-danger">Rs {{ number_format($due, 2) }} Due</span>
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
                                <button type="button" class="btn btn-sm btn-outline-warning"
                                        onclick="promoteSingle({{ $student->id }})">
                                    <i class="bi bi-calendar-arrow-up me-1"></i>Promote
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
