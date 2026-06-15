@extends('center.layout')
@section('title', 'Download Reports')
@section('breadcrumb', 'Reports')

@push('styles')
<style>
.report-card { border-radius: 10px; border: 1px solid #e2e8f0; }
.report-card-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px; background: #f8fafc; border-radius: 10px 10px 0 0;
    border-bottom: 1px solid #e2e8f0; cursor: pointer;
}
.report-card-header:hover { background: #f1f5f9; }
.report-icon {
    width: 40px; height: 40px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center; font-size: 18px;
}
.filter-panel { padding: 16px 18px; border-bottom: 1px solid #e2e8f0; background: #fff; }
.filter-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 4px; }
.report-footer { padding: 12px 18px; background: #fff; border-radius: 0 0 10px 10px; display: flex; align-items: center; gap: 10px; }
.collapse-arrow { transition: transform .2s; }
.collapsed .collapse-arrow { transform: rotate(-90deg); }
.active-filter-chip {
    display: inline-flex; align-items: center; gap: 4px;
    background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;
    border-radius: 99px; font-size: 10px; padding: 2px 8px; font-weight: 500;
}
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-download me-2 text-primary"></i>Download Reports</h4>
        <small class="text-muted">Filter deeply and download CSV reports for students, admissions, and fee collections</small>
    </div>
</div>

@php
    $coursesJson    = $courses->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'type_id' => $c->course_type_id])->values()->toJson();
    $streamsJson    = $streams->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'course_id' => $s->course_id])->values()->toJson();
    $activeId       = $activeSession?->id ?? '';
@endphp

<div class="d-flex flex-column gap-4">

    {{-- ══ 1. STUDENT LIST ══ --}}
    <div class="report-card shadow-sm">
        <div class="report-card-header" onclick="togglePanel('panel-students', this)">
            <div class="d-flex align-items-center gap-3">
                <div class="report-icon" style="background:#eff6ff;">
                    <i class="bi bi-people-fill text-primary"></i>
                </div>
                <div>
                    <div class="fw-bold">Student List</div>
                    <small class="text-muted">All students in your scope — filter by session, course, status, gender & date</small>
                </div>
            </div>
            <i class="bi bi-chevron-down collapse-arrow fs-5 text-muted"></i>
        </div>

        <div id="panel-students" class="filter-panel">
            <form method="GET" action="{{ route('center.reports.students') }}" id="form-students">
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="filter-label">Academic Session</div>
                        <select name="session_id" class="form-select form-select-sm">
                            <option value="">All Sessions</option>
                            @foreach($sessions as $sess)
                            <option value="{{ $sess->id }}" {{ (string)$activeId === (string)$sess->id ? 'selected' : '' }}>
                                {{ $sess->name }}{{ $sess->is_active ? ' ★' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="filter-label">Course Type</div>
                        <select name="course_type_id" class="form-select form-select-sm ct-select" data-target="s-course">
                            <option value="">All Types</option>
                            @foreach($courseTypes as $ct)
                            <option value="{{ $ct->id }}">{{ $ct->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="filter-label">Course</div>
                        <select name="course_id" id="s-course" class="form-select form-select-sm course-select" data-target="s-stream">
                            <option value="">All Courses</option>
                            @foreach($courses as $c)
                            <option value="{{ $c->id }}" data-type="{{ $c->course_type_id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="filter-label">Stream / Branch</div>
                        <select name="stream_id" id="s-stream" class="form-select form-select-sm">
                            <option value="">All Streams</option>
                            @foreach($streams as $st)
                            <option value="{{ $st->id }}" data-course="{{ $st->course_id }}">{{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="filter-label">Status</div>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Status</option>
                            @foreach($studentStatuses as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="filter-label">Gender</div>
                        <select name="gender" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="filter-label">Admission From</div>
                        <input type="date" name="date_from" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="filter-label">Admission To</div>
                        <input type="date" name="date_to" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 d-flex align-items-end gap-2 flex-wrap mt-1">
                        <div class="btn-group" role="group">
                            <button type="submit" name="format" value="csv" class="btn btn-primary btn-sm">
                                <i class="bi bi-filetype-csv me-1"></i> CSV
                            </button>
                            <button type="submit" name="format" value="excel" class="btn btn-success btn-sm">
                                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Excel
                            </button>
                            <button type="submit" name="format" value="pdf" class="btn btn-danger btn-sm">
                                <i class="bi bi-filetype-pdf me-1"></i> PDF
                            </button>
                        </div>
                        <button type="reset" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle me-1"></i> Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ══ 2. ADMISSION REPORT ══ --}}
    <div class="report-card shadow-sm">
        <div class="report-card-header" onclick="togglePanel('panel-admissions', this)">
            <div class="d-flex align-items-center gap-3">
                <div class="report-icon" style="background:#f0fdf4;">
                    <i class="bi bi-person-plus-fill text-success"></i>
                </div>
                <div>
                    <div class="fw-bold">Admission Report</div>
                    <small class="text-muted">Students admitted by your center — filter by session, course, status, gender & date</small>
                </div>
            </div>
            <i class="bi bi-chevron-down collapse-arrow fs-5 text-muted"></i>
        </div>

        <div id="panel-admissions" class="filter-panel" style="display:none;">
            <form method="GET" action="{{ route('center.reports.admissions') }}" id="form-admissions">
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="filter-label">Academic Session</div>
                        <select name="session_id" class="form-select form-select-sm">
                            <option value="">All Sessions</option>
                            @foreach($sessions as $sess)
                            <option value="{{ $sess->id }}" {{ (string)$activeId === (string)$sess->id ? 'selected' : '' }}>
                                {{ $sess->name }}{{ $sess->is_active ? ' ★' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="filter-label">Course Type</div>
                        <select name="course_type_id" class="form-select form-select-sm ct-select" data-target="a-course">
                            <option value="">All Types</option>
                            @foreach($courseTypes as $ct)
                            <option value="{{ $ct->id }}">{{ $ct->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="filter-label">Course</div>
                        <select name="course_id" id="a-course" class="form-select form-select-sm course-select" data-target="a-stream">
                            <option value="">All Courses</option>
                            @foreach($courses as $c)
                            <option value="{{ $c->id }}" data-type="{{ $c->course_type_id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="filter-label">Stream / Branch</div>
                        <select name="stream_id" id="a-stream" class="form-select form-select-sm">
                            <option value="">All Streams</option>
                            @foreach($streams as $st)
                            <option value="{{ $st->id }}" data-course="{{ $st->course_id }}">{{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="filter-label">Status</div>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Status</option>
                            @foreach($studentStatuses as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="filter-label">Gender</div>
                        <select name="gender" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="filter-label">Admission From</div>
                        <input type="date" name="date_from" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="filter-label">Admission To</div>
                        <input type="date" name="date_to" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 d-flex align-items-end gap-2 flex-wrap mt-1">
                        <div class="btn-group" role="group">
                            <button type="submit" name="format" value="csv" class="btn btn-primary btn-sm">
                                <i class="bi bi-filetype-csv me-1"></i> CSV
                            </button>
                            <button type="submit" name="format" value="excel" class="btn btn-success btn-sm">
                                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Excel
                            </button>
                            <button type="submit" name="format" value="pdf" class="btn btn-danger btn-sm">
                                <i class="bi bi-filetype-pdf me-1"></i> PDF
                            </button>
                        </div>
                        <button type="reset" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle me-1"></i> Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ══ 3. FEE COLLECTION ══ --}}
    <div class="report-card shadow-sm">
        <div class="report-card-header" onclick="togglePanel('panel-fee', this)">
            <div class="d-flex align-items-center gap-3">
                <div class="report-icon" style="background:#fffbeb;">
                    <i class="bi bi-cash-coin text-warning"></i>
                </div>
                <div>
                    <div class="fw-bold">Fee Collection</div>
                    <small class="text-muted">Fees collected by your center — filter by session, course, payment mode & date range</small>
                </div>
            </div>
            <i class="bi bi-chevron-down collapse-arrow fs-5 text-muted"></i>
        </div>

        <div id="panel-fee" class="filter-panel" style="display:none;">
            <form method="GET" action="{{ route('center.reports.fee-collection') }}" id="form-fee">
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="filter-label">Academic Session</div>
                        <select name="session_id" class="form-select form-select-sm">
                            <option value="">All Sessions</option>
                            @foreach($sessions as $sess)
                            <option value="{{ $sess->id }}" {{ (string)$activeId === (string)$sess->id ? 'selected' : '' }}>
                                {{ $sess->name }}{{ $sess->is_active ? ' ★' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="filter-label">Course</div>
                        <select name="course_id" id="f-course" class="form-select form-select-sm course-select" data-target="f-stream">
                            <option value="">All Courses</option>
                            @foreach($courses as $c)
                            <option value="{{ $c->id }}" data-type="{{ $c->course_type_id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="filter-label">Stream / Branch</div>
                        <select name="stream_id" id="f-stream" class="form-select form-select-sm">
                            <option value="">All Streams</option>
                            @foreach($streams as $st)
                            <option value="{{ $st->id }}" data-course="{{ $st->course_id }}">{{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="filter-label">Payment Mode</div>
                        <select name="payment_mode" class="form-select form-select-sm">
                            <option value="">All Modes</option>
                            @foreach(['cash'=>'Cash','upi'=>'UPI','online'=>'Online','cheque'=>'Cheque','dd'=>'DD','neft'=>'NEFT','rtgs'=>'RTGS'] as $v=>$l)
                            <option value="{{ $v }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="filter-label">Payment Date From</div>
                        <input type="date" name="from_date" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="filter-label">Payment Date To</div>
                        <input type="date" name="to_date" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 d-flex align-items-end gap-2 flex-wrap mt-1">
                        <div class="btn-group" role="group">
                            <button type="submit" name="format" value="csv" class="btn btn-primary btn-sm">
                                <i class="bi bi-filetype-csv me-1"></i> CSV
                            </button>
                            <button type="submit" name="format" value="excel" class="btn btn-success btn-sm">
                                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Excel
                            </button>
                            <button type="submit" name="format" value="pdf" class="btn btn-danger btn-sm">
                                <i class="bi bi-filetype-pdf me-1"></i> PDF
                            </button>
                        </div>
                        <button type="reset" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle me-1"></i> Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
const allCourses = @json($courses->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'type_id' => $c->course_type_id])->values());
const allStreams = @json($streams->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'course_id' => $s->course_id])->values());

// Toggle panel open/close
function togglePanel(id, header) {
    const panel = document.getElementById(id);
    const arrow = header.querySelector('.collapse-arrow');
    const isHidden = panel.style.display === 'none' || panel.style.display === '';
    panel.style.display = isHidden ? 'block' : 'none';
    arrow.style.transform = isHidden ? 'rotate(0deg)' : 'rotate(-90deg)';
}

// Course type → filter course dropdown
document.querySelectorAll('.ct-select').forEach(function(ctSel) {
    ctSel.addEventListener('change', function() {
        const typeId  = parseInt(this.value) || 0;
        const target  = document.getElementById(this.dataset.target);
        const streamId = target.dataset.target;
        filterCourses(target, typeId);
        if (streamId) filterStreams(document.getElementById(streamId), 0);
    });
});

// Course → filter stream dropdown
document.querySelectorAll('.course-select').forEach(function(cSel) {
    cSel.addEventListener('change', function() {
        const courseId = parseInt(this.value) || 0;
        const target   = document.getElementById(this.dataset.target);
        if (target) filterStreams(target, courseId);
    });
});

function filterCourses(select, typeId) {
    const current = select.value;
    select.innerHTML = '<option value="">All Courses</option>';
    allCourses.forEach(function(c) {
        if (!typeId || c.type_id === typeId) {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.dataset.type = c.type_id;
            opt.textContent = c.name;
            if (parseInt(current) === c.id) opt.selected = true;
            select.appendChild(opt);
        }
    });
    select.dispatchEvent(new Event('change'));
}

function filterStreams(select, courseId) {
    const current = select.value;
    select.innerHTML = '<option value="">All Streams</option>';
    allStreams.forEach(function(s) {
        if (!courseId || s.course_id === courseId) {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.dataset.course = s.course_id;
            opt.textContent = s.name;
            if (parseInt(current) === s.id) opt.selected = true;
            select.appendChild(opt);
        }
    });
}
</script>
@endpush
@endsection
