@extends('institute.layout')
@section('title', 'Session Promotion — Single Student')
@section('breadcrumb', 'Admissions / Session Promotion / Single Student')
@section('content')
@php
    $isStaff        = auth()->guard('staff')->check();
    $_rp            = $isStaff ? 'staff.admissions.promote.' : 'admissions.promote.';
    $rSingle        = $_rp . 'single';
    $rSingleSearch  = $_rp . 'single.search';
    $rSessionPage   = $_rp . 'session';
    $rSessionDo     = $_rp . 'session.do';
    $rSemester      = $_rp . 'semester';
    $rMarkComplete  = $_rp . 'mark-complete';
    $rReadmit       = $_rp . 'readmit';
    $rProfile       = $isStaff ? 'staff.admissions.show' : 'admissions.show';
    $singleBlankUrl = route($rSingle);
    $singleSearchUrl = route($rSingleSearch);
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-person-check me-2 text-primary"></i>Session Promotion — Single Student</h4>
        <small class="text-muted">Search one student, review their full details, then promote</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route($rSessionPage) }}" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-people me-1"></i>Bulk Session Promotion
        </a>
        <a href="{{ route($rSemester) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-up-circle me-1"></i>Semester Promotion
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

{{-- Filters + Search --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <form method="GET" action="{{ route($rSingle) }}" class="row g-2 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">From Session</label>
                <select name="from_session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ $fromSessionId == $s->id ? 'selected':'' }}>
                            {{ $s->name }}{{ $s->is_active ? ' *' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Course</label>
                <select name="course_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Courses</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ $courseId == $c->id ? 'selected':'' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-1">
                <a href="{{ $singleBlankUrl }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Clear / New Search
                </a>
            </div>
        </form>

        <label class="form-label small fw-semibold mb-1">Search Student</label>
        <div class="position-relative">
            <input type="text" id="singleStudentSearch" class="form-control form-control-sm"
                   placeholder="Name, Student ID or Mobile..." autocomplete="off">
            <div id="singleSearchResults" class="list-group position-absolute w-100 shadow-sm"
                 style="z-index:100;max-height:360px;overflow-y:auto;"></div>
        </div>
        <div class="form-text" style="font-size:11px;">Filters above narrow the search. Type at least 2 characters.</div>
    </div>
</div>

@if($student)
@php
    $yearLabel = \App\Support\AcademicState::yearLabel(
        $student->stream?->course?->structure_type,
        $student->current_semester,
        $student->coursePart?->year_number,
        $student->stream?->course?->effectiveSemestersPerYear() ?? 0
    );
    $isShortTerm = (bool) $student->stream?->course?->isShortTerm();
@endphp

{{-- Student header strip --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <div class="fw-bold fs-5">{{ $student->name }}
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle ms-2" style="font-size:11px;">
                    {{ $student->student_uid ?? '—' }}
                </span>
                <span class="badge bg-{{ $student->status === 'active' ? 'success' : 'secondary' }} ms-1">
                    {{ ucwords(str_replace('_', ' ', $student->status)) }}
                </span>
            </div>
            <div class="text-muted small mt-1">
                {{ $student->stream?->course?->name ?? '-' }} — {{ $student->stream?->name ?? '-' }}
                @if($yearLabel !== '—') &bull; {{ $yearLabel }} @endif
                @if($student->current_semester) &bull; Sem {{ $student->current_semester }} @endif
                &bull; {{ $student->session?->name ?? '-' }}
            </div>
        </div>
        <a href="{{ route($rProfile, $student) }}" class="btn btn-outline-dark btn-sm" target="_blank">
            <i class="bi bi-person-lines-fill me-1"></i>View Full Profile
        </a>
    </div>
</div>

{{-- Eligibility banner --}}
@if($student->status !== 'active')
<div class="alert alert-secondary border-0 shadow-sm mb-3">
    <i class="bi bi-info-circle-fill me-2"></i>
    This student's status is <strong>{{ ucwords(str_replace('_', ' ', $student->status)) }}</strong> — already finalized, no further session promotion action available here.
    @if(in_array($student->status, ['passed_out', 'backlog', 'failed', 'dropped']))
        <a href="{{ route($rReadmit, $student) }}" class="alert-link ms-1">Re-admit this student &rarr;</a>
    @endif
</div>
@elseif($partMissing)
<div class="alert alert-danger border-0 shadow-sm mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    Cannot determine this student's current year/part — please fix their Course Part assignment before promoting.
</div>
@elseif($isShortTerm)
<div class="alert alert-info border-0 shadow-sm mb-3">
    <i class="bi bi-info-circle-fill me-2"></i>
    This is a short-term / modular course — use <strong>Mark Complete</strong> below instead of session promotion.
</div>
@elseif($check['can_session_promote'])
<div class="alert alert-success border-0 shadow-sm mb-3">
    <i class="bi bi-check-circle-fill me-2"></i>
    Eligible for session promotion.
    @if($check['is_last']) This is the student's final year — an outcome is required below. @endif
</div>
@elseif($check['can_semester_promote'])
<div class="alert alert-warning border-0 shadow-sm mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    Currently at Semester {{ $student->current_semester }} of the year — not yet at year-end.
    <a href="{{ route($rSemester) }}" class="alert-link">Complete Semester Promotion first &rarr;</a>
</div>
@else
<div class="alert alert-warning border-0 shadow-sm mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    This student is not currently eligible for session promotion.
</div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        {{-- Personal Details --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2" style="background:#1e293b;color:white;">
                <span class="fw-bold small"><i class="bi bi-person me-2"></i>Personal Details</span>
            </div>
            <div class="card-body p-0">
                @foreach([
                    'Category'       => strtoupper((string) $student->category),
                    'Gender'         => ucfirst((string) $student->gender),
                    'Date of Birth'  => optional($student->dob)->format('d-m-Y') ?? '—',
                    'Mobile'         => $student->mobile ?: '—',
                    'Email'          => $student->email ?: '—',
                    'Father Name'    => $student->father_name ?: '—',
                    'Father Mobile'  => $student->father_mobile ?: '—',
                    'Mother Name'    => $student->mother_name ?: '—',
                ] as $lbl => $val)
                <div class="d-flex border-bottom px-3 py-2" style="font-size:13px;">
                    <div class="text-muted" style="width:145px;flex-shrink:0;">{{ $lbl }}</div>
                    <div class="fw-semibold">{{ $val }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-md-6">
        {{-- Fee / Wallet Summary --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2" style="background:#1e293b;color:white;">
                <span class="fw-bold small"><i class="bi bi-wallet me-2"></i>Fee / Wallet Summary</span>
            </div>
            <div class="card-body p-0">
                <div class="row g-0 text-center border-bottom">
                    <div class="col border-end py-3">
                        <div class="small text-muted mb-1">Total Charged</div>
                        <div class="fw-bold text-danger">₹{{ number_format($feeSummary['total_charged'], 2) }}</div>
                    </div>
                    <div class="col py-3">
                        <div class="small text-muted mb-1">Total Paid</div>
                        <div class="fw-bold text-success">₹{{ number_format($feeSummary['total_paid'], 2) }}</div>
                    </div>
                </div>
                <div class="d-flex border-bottom px-3 py-2" style="font-size:13px;">
                    <div class="text-muted" style="width:230px;flex-shrink:0;">Fee/Transport Due
                        <span class="d-block text-muted" style="font-size:10px;">carries forward on promotion</span>
                    </div>
                    <div class="fw-bold {{ $walletDue > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $walletDue > 0 ? '₹' . number_format($walletDue, 2) : 'Clear' }}
                    </div>
                </div>
                <div class="d-flex px-3 py-2" style="font-size:13px;">
                    <div class="text-muted" style="width:230px;flex-shrink:0;">
                        Total Outstanding (incl. library)
                    </div>
                    <div class="fw-bold {{ $feeSummary['is_clear'] ? 'text-success' : 'text-warning' }}">
                        @if($feeSummary['is_clear'])
                            <i class="bi bi-check-circle me-1"></i>Clear
                        @else
                            ₹{{ number_format($feeSummary['total_due'], 2) }}
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Library Dues --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2" style="background:#1e293b;color:white;">
                <span class="fw-bold small"><i class="bi bi-book me-2"></i>Library Dues</span>
            </div>
            <div class="card-body py-3" style="font-size:13px;">
                @if($feeSummary['library_fine_due'] > 0)
                    <span class="fw-bold text-danger">₹{{ number_format($feeSummary['library_fine_due'], 2) }}</span>
                    outstanding fine.
                    <span class="text-muted d-block" style="font-size:11px;">Not carried forward automatically — settle separately via the Library module.</span>
                @else
                    <span class="text-success"><i class="bi bi-check-circle me-1"></i>No pending library dues.</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Transport Details --}}
@php $transport = $student->activeTransportAllocation; @endphp
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background:#1e293b;color:white;">
        <span class="fw-bold small"><i class="bi bi-bus-front me-2"></i>Transport Details</span>
        @if($transport)
            <span class="badge {{ $transport->is_active ? 'bg-success' : 'bg-secondary' }}">
                {{ $transport->is_active ? 'Active' : 'Inactive' }}
            </span>
        @endif
    </div>
    @if($transport)
    <div class="card-body p-0">
        @foreach([
            'Route'       => $transport->route?->name ?? '—',
            'Stop'        => $transport->stop?->stop_name ?? '—',
            'Vehicle'     => $transport->vehicle ? ($transport->vehicle->vehicle_no ?? $transport->vehicle->name ?? '—') : '—',
            'Driver'      => $transport->driver?->name ?? '—',
            'Fee Amount'  => $transport->fee_amount ? '₹' . number_format($transport->fee_amount, 2) : '—',
            'Paid Amount' => $transport->paid_amount ? '₹' . number_format($transport->paid_amount, 2) : '₹0.00',
            'Balance Due' => ($transport->balance > 0 ? '₹' . number_format($transport->balance, 2) : 'Clear'),
        ] as $lbl => $val)
        <div class="d-flex border-bottom px-3 py-2" style="font-size:13px;">
            <div class="text-muted" style="width:160px;flex-shrink:0;">{{ $lbl }}</div>
            <div class="fw-semibold {{ $lbl === 'Balance Due' && $transport->balance > 0 ? 'text-danger' : ($lbl === 'Balance Due' ? 'text-success' : '') }}">
                {{ $val }}
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="card-body py-3 text-center text-muted small">
        <i class="bi bi-bus-front me-1"></i> No transport allocation for this student.
    </div>
    @endif
</div>

{{-- Promotion History --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b;color:white;">
        <span class="fw-bold small"><i class="bi bi-clock-history me-2"></i>Promotion History</span>
    </div>
    <div class="card-body p-0">
        @if($promotionLogs->isEmpty())
        <div class="py-3 text-center text-muted small">No prior promotion history for this student.</div>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:12px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">From Session</th>
                        <th>To Session</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Promoted By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($promotionLogs as $log)
                    <tr>
                        <td class="ps-3">{{ $log->fromSession?->name ?? '—' }}</td>
                        <td>{{ $log->toSession?->name ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $log->is_reversed ? 'secondary' : 'info' }} bg-opacity-75">
                                {{ ucwords(str_replace('_', ' ', $log->terminal_status ?? $log->status)) }}
                            </span>
                            @if($log->is_reversed) <span class="badge bg-secondary">Reversed</span> @endif
                        </td>
                        <td class="text-muted">{{ $log->remarks ?: '—' }}</td>
                        <td class="text-muted">{{ $log->promoted_by ?? '—' }} ({{ $log->promoted_by_role ?? '—' }})</td>
                        <td class="text-muted">{{ $log->created_at?->format('d M Y h:i A') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- Promote / Mark Complete action --}}
@if($student->status === 'active' && !$partMissing)
    @if($isShortTerm)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-success text-white py-2"><i class="bi bi-patch-check me-1"></i>Mark Complete</div>
        <div class="card-body">
            <form method="POST" action="{{ route($rMarkComplete, $student) }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Outcome</label>
                    <select name="completion_status" class="form-select form-select-sm" required>
                        <option value="passed_out">Passed Out</option>
                        <option value="failed">Failed</option>
                        <option value="dropped">Dropped</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Remarks</label>
                    <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Optional">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-success btn-sm fw-semibold w-100"
                            onclick="return confirm('Mark this student as completed?')">
                        <i class="bi bi-patch-check me-1"></i>Mark Complete
                    </button>
                </div>
            </form>
        </div>
    </div>
    @elseif($check['can_session_promote'])
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-warning text-dark py-2"><i class="bi bi-calendar-arrow-up me-1"></i>Promote</div>
        <div class="card-body">
            <form method="POST" action="{{ route($rSessionDo) }}" id="promoteForm">
                @csrf
                <input type="hidden" name="student_ids[]" value="{{ $student->id }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">To Session
                            <span class="text-muted fw-normal" style="font-size:10px;">(optional for final-year students)</span>
                        </label>
                        <select name="to_session_id" class="form-select form-select-sm">
                            <option value="">— Not Required (last year / terminal) —</option>
                            @foreach($sessions as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Outcome</label>
                        <select name="completion_status" id="completion_status" class="form-select form-select-sm" onchange="onOutcomeChange(this.value)">
                            <option value="">&#8594; Promote to Next Session / Year</option>
                            <option value="backlog">Backlog (promoted, subjects pending)</option>
                            <option value="failed">Fail (year-back — repeat year)</option>
                            <option value="dropped">Drop (leave / withdraw)</option>
                            @if($check['is_last'])
                            <option value="passed_out">Passed Out (final year)</option>
                            @endif
                        </select>
                        <div id="outcomeHint" class="mt-1" style="font-size:11px;color:#6b7280;"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Remarks</label>
                        <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Optional">
                    </div>

                    <div class="col-12" id="backlogSubjectsSection" style="display:none;">
                        <label class="form-label small fw-semibold">
                            <i class="bi bi-journal-x me-1 text-warning"></i>Backlog Subjects
                            <span class="text-muted fw-normal" style="font-size:10px;">(select subjects the student failed)</span>
                        </label>
                        <div id="backlogSubjectsContainer" class="p-2 rounded border bg-light"
                             style="max-height:160px;overflow-y:auto;font-size:12px;"></div>
                    </div>

                    <div class="col-12 small text-muted">
                        <strong>Promote:</strong> advances to next year in selected session. &nbsp;
                        <strong>Backlog:</strong> advances but backlog subjects are recorded. &nbsp;
                        <strong>Fail:</strong> year-back — same year, new session. &nbsp;
                        <strong>Drop:</strong> student leaves (no session move).
                        Outstanding fee/transport dues are carried forward to the new session wallet.
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
    @endif
@endif

@endif

<script>
const subjectsForPart = @json($subjectsForPart ?? []);

const outcomeHints = {
    '':          '',
    'backlog':   'Student advances to next year. Backlog subjects are recorded and carried forward.',
    'failed':    'Year-back: student repeats the same year in the selected session.',
    'dropped':   'Student is marked as dropped. No session move; outstanding dues stay.',
    'passed_out':'Final-year graduation. Student status updated to Passed Out.',
};

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function onOutcomeChange(val) {
    const hintEl = document.getElementById('outcomeHint');
    if (hintEl) hintEl.textContent = outcomeHints[val] || '';
    const section = document.getElementById('backlogSubjectsSection');
    if (!section) return;
    if (val === 'backlog') {
        section.style.removeProperty('display');
        renderBacklogSubjects();
    } else {
        section.style.setProperty('display', 'none', 'important');
    }
}

function renderBacklogSubjects() {
    const container = document.getElementById('backlogSubjectsContainer');
    if (!container) return;
    if (!subjectsForPart.length) {
        container.innerHTML = '<span class="text-muted">No subjects mapped for this student\'s course/stream/year. Configure them under Master &rarr; Course Subjects.</span>';
        return;
    }
    container.innerHTML = subjectsForPart.map(s =>
        `<div class="form-check form-check-inline me-3 mb-1">
            <input class="form-check-input" type="checkbox" name="backlog_subject_ids[]"
                   value="${escHtml(String(s.id))}" id="bls_${escHtml(String(s.id))}">
            <label class="form-check-label" for="bls_${escHtml(String(s.id))}">
                ${s.code ? '<span class=\'text-muted\'>' + escHtml(s.code) + '</span> — ' : ''}${escHtml(s.name)}
            </label>
        </div>`
    ).join('');
}

// ── Live student search ─────────────────────────────────────────────
let singleSearchTimer;
function currentFilterParams() {
    const course  = document.querySelector('[name="course_id"]')?.value || '';
    const session = document.querySelector('[name="from_session_id"]')?.value || '';
    return (course ? `&course_id=${course}` : '') + (session ? `&from_session_id=${session}` : '');
}
document.getElementById('singleStudentSearch')?.addEventListener('input', function () {
    clearTimeout(singleSearchTimer);
    const q = this.value.trim();
    const box = document.getElementById('singleSearchResults');
    if (q.length < 2) { box.innerHTML = ''; return; }
    singleSearchTimer = setTimeout(() => {
        fetch(`${@json($singleSearchUrl)}?q=${encodeURIComponent(q)}${currentFilterParams()}`)
            .then(r => r.json())
            .then(data => {
                box.innerHTML = !data.length
                    ? '<div class="list-group-item text-muted small">No students found</div>'
                    : data.map(s => `
                        <a href="${@json($singleBlankUrl)}?student_id=${s.id}${currentFilterParams()}"
                           class="list-group-item list-group-item-action py-2">
                            <div class="fw-semibold small">${escHtml(s.name)}
                                <span class="text-muted fw-normal ms-1" style="font-size:10px;">${escHtml(s.student_uid ?? '')}</span></div>
                            <div class="text-muted" style="font-size:11px;">
                                ${escHtml(s.course)}${s.stream ? ' · ' + escHtml(s.stream) : ''} &bull; Sem ${s.sem ?? '-'} &bull; ${escHtml(s.session)}</div>
                        </a>`).join('');
            });
    }, 300);
});
document.addEventListener('click', function (e) {
    const box = document.getElementById('singleSearchResults');
    const input = document.getElementById('singleStudentSearch');
    if (box && input && !box.contains(e.target) && e.target !== input) {
        box.innerHTML = '';
    }
});
</script>
@endsection
