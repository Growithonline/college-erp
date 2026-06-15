@extends($layout ?? 'institute.layout')
@section('title', 'Student Promotions')
@section('breadcrumb', 'Admissions / Student Promotions')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-arrow-up-circle-fill me-2 text-primary"></i>Student Promotions</h4>
        <small class="text-muted">Promote students to the next year / session</small>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Session</label>
                <select name="session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ $selectedSession == $s->id ? 'selected' : '' }}>
                            {{ $s->name }}{{ $s->is_active ? ' (Active)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Course</label>
                <select name="course_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Courses</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ $selectedCourse == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control form-control-sm" placeholder="Name, Mobile, Student ID...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Bulk Promote Bar --}}
<div class="card border-0 shadow-sm mb-3" id="bulkBar" style="display:none;background:#eff6ff;border-left:4px solid #3b82f6!important;">
    <div class="card-body py-2 d-flex align-items-center gap-3 flex-wrap">
        <span class="fw-semibold text-primary small"><span id="selectedCount">0</span> students selected</span>
        <div class="d-flex align-items-center gap-2">
            <label class="small text-muted mb-0">Promote To Session:</label>
            <select class="form-select form-select-sm" id="bulkNewSession" style="width:160px;">
                <option value="">-- Select --</option>
                @foreach($nextSessions as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label class="small text-muted mb-0">Year/Part:</label>
            <select class="form-select form-select-sm" id="bulkNewPart" style="width:160px;">
                <option value="">-- Select --</option>
            </select>
        </div>
        <button class="btn btn-primary btn-sm" onclick="bulkPromoteConfirm()">
            <i class="bi bi-arrow-up-circle me-1"></i>Promote Selected
        </button>
        <button class="btn btn-outline-secondary btn-sm" onclick="clearSelection()">Cancel</button>
    </div>
</div>

{{-- Students Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
        <span class="fw-semibold small">
            <i class="bi bi-people me-2"></i>Students
            <span class="badge bg-secondary ms-1">{{ $students->total() }}</span>
        </span>
        <div class="d-flex align-items-center gap-2">
            <input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleAll(this)">
            <label for="selectAll" class="small text-muted mb-0">Select All</label>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0" style="font-size:13px;">
            <thead class="table-light">
                <tr>
                    <th style="width:40px;"></th>
                    <th>Student</th>
                    <th>Student ID</th>
                    <th>Course / Stream</th>
                    <th>Current Year</th>
                    <th>Session</th>
                    <th class="text-end">Balance</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                @php
                    $wd  = $walletData[$student->id] ?? ['balance'=>0,'total_due'=>0];
                    $due = $wd['total_due'];
                @endphp
                <tr id="row-{{ $student->id }}">
                    <td>
                        <input type="checkbox" class="form-check-input student-check"
                               value="{{ $student->id }}"
                               data-stream="{{ $student->course_stream_id }}"
                               onchange="updateBulkBar()">
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $student->name }}</div>
                        <div class="text-muted small">{{ $student->mobile }}</div>
                    </td>
                    <td>
                        <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:11px;">
                            {{ $student->student_uid }}
                        </span>
                    </td>
                    <td>
                        <div>{{ $student->stream->course->name ?? '—' }}</div>
                        <div class="text-muted small">{{ $student->stream->name ?? '' }}</div>
                    </td>
                    <td>
                        <span class="badge bg-secondary">
                            {{ $student->coursePart?->year_label ?? '1st Year' }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ $student->session->name ?? '—' }}</td>
                    <td class="text-end">
                        @if($due > 0)
                            <span class="text-danger fw-semibold">-₹{{ number_format($due) }}</span>
                        @else
                            <span class="text-success">✓ Clear</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <button class="btn btn-primary btn-sm"
                                onclick="openPromoteModal({{ $student->id }}, '{{ addslashes($student->name) }}', {{ $student->course_stream_id }})">
                            <i class="bi bi-arrow-up-circle me-1"></i>Promote
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-people fs-2 d-block mb-2 opacity-25"></i>
                        No students found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-3 pb-2">
        @include('institute.components.pagination', ['paginator' => $students, 'perPage' => $perPage])
    </div>
</div>

{{-- Promote Modal --}}
<div class="modal fade" id="promoteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2" style="background:#1e293b;color:white;">
                <h6 class="modal-title fw-bold mb-0">
                    <i class="bi bi-arrow-up-circle me-2"></i>Promote Student
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Promote To Session *</label>
                        <select class="form-select form-select-sm" id="modalNewSession"
                                onchange="loadParts(this.value, 'modalNewPart'); loadPreview()">
                            <option value="">-- Select Session --</option>
                            @foreach($nextSessions as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">New Year / Part *</label>
                        <select class="form-select form-select-sm" id="modalNewPart"
                                onchange="loadPreview()">
                            <option value="">-- Select Year --</option>
                        </select>
                    </div>
                </div>

                {{-- Preview box --}}
                <div id="previewBox" style="display:none;">
                    <div class="card bg-light border-0 p-3 mb-3" style="font-size:13px;">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="text-muted small">Student</div>
                                <div class="fw-semibold" id="prev_student"></div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted small">From</div>
                                <div id="prev_from"></div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted small">To</div>
                                <div class="text-primary fw-semibold" id="prev_to"></div>
                            </div>
                        </div>
                        <div id="dueAlert" style="display:none;" class="alert alert-warning py-2 mt-2 mb-0 small">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <span id="dueText"></span> — this amount will be forwarded to the new session.
                        </div>
                        <div class="mt-2" id="newSubjectsBox" style="display:none;">
                            <div class="text-muted small mb-1">New Year Subjects:</div>
                            <div id="newSubjectsList" class="d-flex flex-wrap gap-1"></div>
                        </div>
                    </div>
                </div>

                <div id="loadingPreview" style="display:none;" class="text-center text-muted py-2 small">
                    <div class="spinner-border spinner-border-sm me-2"></div> Loading preview...
                </div>

                {{-- Checklist --}}
                <div id="promoteChecklist" style="display:none;">
                    <div class="border rounded p-3 mt-2" style="background:#f8fafc;">
                        <div class="fw-semibold small mb-2">
                            <i class="bi bi-clipboard-check me-1 text-primary"></i>
                            Please confirm before promoting:
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <label class="d-flex align-items-center gap-2 small" style="cursor:pointer;">
                                <input type="checkbox" class="form-check-input promote-check" id="chk_exam" onchange="updatePromoteBtn()">
                                <span>Semester exam results have been submitted</span>
                            </label>
                            <label class="d-flex align-items-center gap-2 small" style="cursor:pointer;">
                                <input type="checkbox" class="form-check-input promote-check" id="chk_attend" onchange="updatePromoteBtn()">
                                <span>Attendance requirement has been met</span>
                            </label>
                            <label class="d-flex align-items-center gap-2 small" id="chk_fee_label" style="cursor:pointer;">
                                <input type="checkbox" class="form-check-input promote-check" id="chk_fee" onchange="updatePromoteBtn()">
                                <span id="chk_fee_text">Fee dues have been cleared</span>
                            </label>
                            <label class="d-flex align-items-center gap-2 small" style="cursor:pointer;">
                                <input type="checkbox" class="form-check-input promote-check" id="chk_docs" onchange="updatePromoteBtn()">
                                <span>Required documents have been submitted</span>
                            </label>
                        </div>
                        <div class="mt-2 small text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Students with pending dues can still be promoted — the due amount will be carried forward to the new session.
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="confirmPromoteBtn"
                        onclick="doPromote()" disabled>
                    <i class="bi bi-arrow-up-circle me-1"></i>Confirm Promote
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const promoteBase = '{{ $promoteBase ?? "/admissions/promote" }}';
let currentStudentId  = null;
let currentStreamId   = null;
let previewTimer      = null;
let partsBySession    = {};

// ── Open modal ──────────────────────────────────────────────────────────
function openPromoteModal(studentId, studentName, streamId) {
    currentStudentId = studentId;
    currentStreamId  = streamId;
    document.getElementById('previewBox').style.display       = 'none';
    document.getElementById('promoteChecklist').style.display = 'none';
    document.getElementById('confirmPromoteBtn').disabled     = true;
    document.getElementById('modalNewSession').value          = '';
    document.getElementById('modalNewPart').innerHTML         = '<option value="">-- Select Year --</option>';
    document.querySelectorAll('.promote-check').forEach(c => c.checked = false);
    new bootstrap.Modal(document.getElementById('promoteModal')).show();
}

// ── Load course parts for a session (AJAX ke bina — local data) ─────────
async function loadParts(sessionId, targetSelectId) {
    if (!sessionId || !currentStreamId) return;

    const sel = document.getElementById(targetSelectId);
    sel.innerHTML = '<option value="">Loading...</option>';

    const res = await fetch(`/master/streams/${currentStreamId}/subjects/for-admission?stream_id=${currentStreamId}&year_number=1`, {
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    }).catch(() => null);

    // Parts — fetch from course parts endpoint
    const partRes = await fetch(`${promoteBase}/parts?stream_id=${currentStreamId}`, {
        headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
    });

    if (partRes.ok) {
        const data = await partRes.json();
        sel.innerHTML = '<option value="">-- Select Year --</option>' +
            data.parts.map(p => `<option value="${p.id}">${p.part_name}</option>`).join('');
    } else {
        sel.innerHTML = '<option value="">-- Reload page --</option>';
    }
}

// ── Load preview ────────────────────────────────────────────────────────
async function loadPreview() {
    const sessionId = document.getElementById('modalNewSession').value;
    const partId    = document.getElementById('modalNewPart').value;

    document.getElementById('previewBox').style.display    = 'none';
    document.getElementById('confirmPromoteBtn').disabled  = true;

    if (!sessionId || !partId || !currentStudentId) return;

    document.getElementById('loadingPreview').style.display = 'block';

    const res = await fetch(`${promoteBase}/preview`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            student_id:     currentStudentId,
            new_session_id: sessionId,
            new_part_id:    partId,
        })
    });

    document.getElementById('loadingPreview').style.display = 'none';

    if (!res.ok) return;
    const data = await res.json();
    if (!data.success) return;

    document.getElementById('prev_student').textContent = data.student.name + ' (' + data.student.student_uid + ')';
    document.getElementById('prev_from').textContent    = data.student.old_session + ' — ' + data.student.old_part;
    document.getElementById('prev_to').textContent      = data.new_session + ' — ' + data.new_part;

    if (data.old_due > 0) {
        document.getElementById('dueText').textContent = 'Previous due: ₹' + data.old_due.toLocaleString('en-IN');
        document.getElementById('dueAlert').style.display = 'block';
        // Fee due pending — checkbox ko warning style do but allow
        document.getElementById('chk_fee_text').textContent = '⚠ Fee dues pending — will be carried forward to the new session';
        document.getElementById('chk_fee_label').style.color = '#d97706';
    } else {
        document.getElementById('dueAlert').style.display = 'none';
        document.getElementById('chk_fee_text').textContent = 'Fee dues cleared ✓';
        document.getElementById('chk_fee_label').style.color = '';
    }

    if (data.new_subjects && data.new_subjects.length) {
        document.getElementById('newSubjectsList').innerHTML = data.new_subjects.map(s =>
            `<span class="badge bg-primary bg-opacity-10 text-primary border">${s.name} <small>(${s.role})</small></span>`
        ).join('');
        document.getElementById('newSubjectsBox').style.display = 'block';
    } else {
        document.getElementById('newSubjectsBox').style.display = 'none';
    }

    // Reset checklist
    document.querySelectorAll('.promote-check').forEach(c => c.checked = false);
    document.getElementById('promoteChecklist').style.display = 'block';

    document.getElementById('previewBox').style.display   = 'block';
    document.getElementById('confirmPromoteBtn').disabled = true; // checklist complete hone pe enable hoga
}

// ── Checklist — sab check hone pe button enable ──────────────────────
function updatePromoteBtn() {
    const allChecked = [...document.querySelectorAll('.promote-check')].every(c => c.checked);
    document.getElementById('confirmPromoteBtn').disabled = !allChecked;
}

// ── Do Promote ──────────────────────────────────────────────────────────
async function doPromote() {
    const btn = document.getElementById('confirmPromoteBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Promoting...';

    const res = await fetch(promoteBase, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            student_id:     currentStudentId,
            new_session_id: document.getElementById('modalNewSession').value,
            new_part_id:    document.getElementById('modalNewPart').value,
        })
    });

    const data = await res.json();

    if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('promoteModal')).hide();
        // Remove row from table
        const row = document.getElementById('row-' + currentStudentId);
        if (row) {
            row.style.background = '#f0fdf4';
            row.querySelector('td:last-child').innerHTML =
                '<span class="badge bg-success">✓ Promoted</span>';
            setTimeout(() => row.remove(), 1500);
        }
        showToast(data.message, 'success');
    } else {
        showToast('Error: ' + (data.message || 'Something went wrong'), 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-up-circle me-1"></i>Confirm Promote';
    }
}

// ── Bulk Promote ────────────────────────────────────────────────────────
function toggleAll(cb) {
    document.querySelectorAll('.student-check').forEach(c => c.checked = cb.checked);
    updateBulkBar();
}

function clearSelection() {
    document.querySelectorAll('.student-check, #selectAll').forEach(c => c.checked = false);
    updateBulkBar();
}

function updateBulkBar() {
    const checked = document.querySelectorAll('.student-check:checked');
    const bar     = document.getElementById('bulkBar');
    document.getElementById('selectedCount').textContent = checked.length;
    bar.style.display = checked.length > 0 ? 'block' : 'none';

    // Load parts for first selected student's stream
    if (checked.length > 0) {
        const streamId = checked[0].dataset.stream;
        if (streamId !== currentStreamId) {
            currentStreamId = streamId;
            loadBulkParts();
        }
    }
}

async function loadBulkParts() {
    const res = await fetch(`${promoteBase}/parts?stream_id=${currentStreamId}`, {
        headers: {'Accept': 'application/json'}
    });
    if (!res.ok) return;
    const data = await res.json();
    const sel  = document.getElementById('bulkNewPart');
    sel.innerHTML = '<option value="">-- Select Year --</option>' +
        data.parts.map(p => `<option value="${p.id}">${p.part_name}</option>`).join('');
}

async function bulkPromoteConfirm() {
    const ids       = [...document.querySelectorAll('.student-check:checked')].map(c => c.value);
    const sessionId = document.getElementById('bulkNewSession').value;
    const partId    = document.getElementById('bulkNewPart').value;

    if (!sessionId || !partId) {
        alert('Please select a Session and Year.');
        return;
    }

    if (!confirm(`Promote ${ids.length} student(s)?`)) return;

    const res = await fetch(`${promoteBase}/bulk`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ student_ids: ids, new_session_id: sessionId, new_part_id: partId })
    });

    const data = await res.json();
    showToast(data.message, data.success ? 'success' : 'danger');
    if (data.success) setTimeout(() => location.reload(), 1500);
}

// ── Toast ───────────────────────────────────────────────────────────────
function showToast(msg, type) {
    const t = document.createElement('div');
    t.className = `alert alert-${type} shadow position-fixed bottom-0 end-0 m-3`;
    t.style.cssText = 'z-index:9999;min-width:250px;';
    t.innerHTML = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}
</script>
@endsection