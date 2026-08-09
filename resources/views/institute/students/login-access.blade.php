@extends('institute.layout')
@section('title', 'Student Login Access')
@section('breadcrumb', 'Students / Login Access')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-2">
    <div>
        <h5 class="mb-0 fw-bold"><i class="bi bi-shield-lock me-1 text-primary"></i> Student Login Access</h5>
        <small class="text-muted">
            Session: <span class="fw-semibold text-primary">{{ $sessions->firstWhere('id', $sessionId)?->name ?? 'All Sessions' }}</span>
            &mdash; {{ $students->total() }} student(s)
        </small>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <a href="{{ route('students.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to All Students
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-2">
    <div class="card-body py-2 px-3">
        <form method="GET" id="filterForm">
            <div class="row g-2 align-items-end">

                {{-- Search --}}
                <div class="col-12 col-md-3">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Search</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Name, Father, Mother, Mobile, ID..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                {{-- Session --}}
                <div class="col-auto" style="min-width:110px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Session</label>
                    <select name="session_id" class="form-select form-select-sm" onchange="stdAutoSubmit()">
                        <option value="">All</option>
                        @foreach($sessions as $sess)
                            <option value="{{ $sess->id }}" {{ request('session_id') == $sess->id ? 'selected' : '' }}>
                                {{ $sess->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Course Type → drives Course dropdown --}}
                @if($courseTypes->isNotEmpty())
                <div class="col-auto" style="min-width:120px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Course Type</label>
                    <select name="course_type_id" id="filterCourseType" class="form-select form-select-sm"
                            onchange="stdFilterCourses(this.value); stdFilterStreams('');">
                        <option value="">All Types</option>
                        @foreach($courseTypes as $ct)
                            <option value="{{ $ct->id }}" {{ request('course_type_id') == $ct->id ? 'selected' : '' }}>
                                {{ $ct->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Course → drives Stream dropdown --}}
                <div class="col-auto" style="min-width:150px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Course</label>
                    <select name="course_id" id="filterCourse" class="form-select form-select-sm"
                            onchange="stdFilterStreams(this.value);">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}"
                                    data-type="{{ $course->course_type_id }}"
                                    {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Stream → linked to Course --}}
                <div class="col-auto" style="min-width:140px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Stream</label>
                    <select name="course_stream_id" id="filterStream" class="form-select form-select-sm" onchange="stdAutoSubmit()">
                        <option value="">All Streams</option>
                        @foreach($streams as $stream)
                            <option value="{{ $stream->id }}"
                                    data-course="{{ $stream->course_id }}"
                                    {{ request('course_stream_id') == $stream->id ? 'selected' : '' }}>
                                {{ $stream->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Semester --}}
                <div class="col-auto" style="min-width:95px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Semester</label>
                    <select name="current_semester" class="form-select form-select-sm" onchange="stdAutoSubmit()">
                        <option value="">All Sem</option>
                        @for($s = 1; $s <= $maxSemester; $s++)
                            <option value="{{ $s }}" {{ request('current_semester') == $s ? 'selected' : '' }}>
                                Sem {{ $s }}
                            </option>
                        @endfor
                    </select>
                </div>

                {{-- Student Type --}}
                <div class="col-auto" style="min-width:120px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Student Type</label>
                    <select name="student_type" class="form-select form-select-sm" onchange="stdAutoSubmit()">
                        <option value="">All Types</option>
                        @foreach($studentTypes as $st)
                            <option value="{{ $st->slug }}" {{ request('student_type') === $st->slug ? 'selected' : '' }}>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div class="col-auto" style="min-width:105px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="stdAutoSubmit()">
                        <option value="">All Status</option>
                        <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Pending</option>
                        <option value="active"    {{ request('status') === 'active'    ? 'selected' : '' }}>Active</option>
                        <option value="inactive"  {{ request('status') === 'inactive'  ? 'selected' : '' }}>Inactive</option>
                        <option value="detained"  {{ request('status') === 'detained'  ? 'selected' : '' }}>Detained</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                {{-- Date Range --}}
                <div class="col-auto">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm"
                           value="{{ request('from_date') }}" style="width:128px;">
                </div>
                <div class="col-auto">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm"
                           value="{{ request('to_date') }}" style="width:128px;">
                </div>

                {{-- Buttons --}}
                <div class="col-auto d-flex align-items-end gap-1">
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="{{ route('students.login-access') }}" class="btn btn-outline-secondary btn-sm">
                        Clear
                    </a>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- Bulk Action Bar --}}
<div id="bulkBar" class="alert alert-primary d-flex align-items-center justify-content-between mb-2 py-2 px-3" style="display:none;">
    <div><i class="bi bi-check2-square me-1"></i><span id="bulkCount">0</span> student(s) selected</div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="submitBulkSend()">
            <i class="bi bi-envelope-arrow-up me-1"></i> Send Login Details
        </button>
        <button type="button" class="btn btn-sm btn-danger" onclick="openBulkLoginAccessModal()">
            <i class="bi bi-shield-lock me-1"></i> Manage Login Access
        </button>
    </div>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0" style="font-size:12px;">
            <thead style="background:#1e3a5f; color:#fff;">
                <tr>
                    <th class="ps-2" style="width:30px;">
                        <input type="checkbox" id="selectAll" class="form-check-input" title="Select all">
                    </th>
                    <th style="width:30px; white-space:nowrap;">#</th>
                    <th style="min-width:60px; white-space:nowrap;">Session</th>
                    <th style="min-width:110px; white-space:nowrap;">Student ID</th>
                    <th style="min-width:145px; white-space:nowrap;">Student Name</th>
                    <th style="min-width:100px; white-space:nowrap;">Father Name</th>
                    <th style="min-width:100px; white-space:nowrap;">Mother Name</th>
                    <th style="min-width:65px; white-space:nowrap;">Roll No</th>
                    <th style="min-width:75px; white-space:nowrap;">Enroll No</th>
                    <th style="min-width:65px; white-space:nowrap;">UIN No</th>
                    <th style="min-width:120px; white-space:nowrap;">Course</th>
                    <th style="min-width:65px; white-space:nowrap;">Year/Sem</th>
                    <th style="min-width:90px; white-space:nowrap;">Student Type</th>
                    <th style="min-width:90px; white-space:nowrap;">Admitted By</th>
                    <th style="min-width:85px; white-space:nowrap;">Source</th>
                    <th style="min-width:80px; white-space:nowrap;">Adm. Date</th>
                    <th style="min-width:68px; white-space:nowrap;">Status</th>
                    <th style="min-width:90px; white-space:nowrap;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php $studentTypeNames = $studentTypes->pluck('name', 'slug'); @endphp
                @forelse($students as $i => $student)
                @php
                    $src = $student->admission_source ?? 'direct';

                    $sourceName = match($src) {
                        'center'  => ($centersMap[$student->admission_source_id] ?? null)
                                        ? 'Center: ' . $centersMap[$student->admission_source_id]
                                        : 'Center',
                        'partner', 'channel_partner' => ($partnersMap[$student->admission_source_id] ?? null)
                                        ? 'Partner: ' . $partnersMap[$student->admission_source_id]
                                        : 'Partner',
                        default   => 'Direct',
                    };

                    $admittedByLabel = $student->admittedBy?->name
                        ?? match($src) {
                            'center'                     => $sourceName,
                            'partner', 'channel_partner' => $sourceName,
                            default                      => 'Admin / Direct',
                        };

                    $admittedByBadge = match(true) {
                        $student->admittedBy !== null          => 'bg-info bg-opacity-10 text-info border-info-subtle',
                        $src === 'center'                      => 'bg-info bg-opacity-10 text-info border-info-subtle',
                        in_array($src, ['partner','channel_partner']) => 'bg-warning bg-opacity-10 text-warning border-warning-subtle',
                        default                                => 'bg-secondary bg-opacity-10 text-secondary border-secondary-subtle',
                    };
                    $admittedByIcon = match(true) {
                        $student->admittedBy !== null          => 'bi-person-badge',
                        $src === 'center'                      => 'bi-building',
                        in_array($src, ['partner','channel_partner']) => 'bi-people',
                        default                                => 'bi-shield-check',
                    };

                    $sourceBadge = match($src) {
                        'center'                      => 'bg-info bg-opacity-10 text-info border-info-subtle',
                        'partner', 'channel_partner'  => 'bg-warning bg-opacity-10 text-warning border-warning-subtle',
                        default                       => 'bg-success bg-opacity-10 text-success border-success-subtle',
                    };
                    $sourceIcon = match($src) {
                        'center'                      => 'bi-building',
                        'partner', 'channel_partner'  => 'bi-people',
                        default                       => 'bi-arrow-right-circle',
                    };
                    $sourceShort = match($src) {
                        'center'           => 'Center',
                        'partner',
                        'channel_partner'  => 'Partner',
                        default            => 'Direct',
                    };

                    $statusColor = match($student->status) {
                        'active'    => 'bg-success-subtle text-success border-success-subtle',
                        'pending'   => 'bg-warning-subtle text-warning border-warning-subtle',
                        'inactive'  => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                        'detained'  => 'bg-danger-subtle text-danger border-danger-subtle',
                        'cancelled' => 'bg-dark-subtle text-dark border-dark-subtle',
                        default     => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                    };

                    $loginState = $student->login_blocked
                        ? 'blocked'
                        : (($student->suspended_until && now()->toDateString() <= $student->suspended_until->toDateString()) ? 'suspended' : 'allowed');
                @endphp
                <tr>
                    <td class="ps-2">
                        <input type="checkbox" class="form-check-input row-check" value="{{ $student->id }}">
                    </td>
                    <td class="text-muted fw-semibold">{{ $students->firstItem() + $i }}</td>

                    {{-- Session --}}
                    <td>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle"
                              style="font-size:10px; font-weight:600; white-space:nowrap;">
                            <i class="bi bi-calendar3 me-1"></i>{{ $student->session?->name ?? '—' }}
                        </span>
                    </td>

                    {{-- Student ID --}}
                    <td>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle"
                              style="font-size:10px; font-weight:600;">
                            {{ $student->student_uid }}
                        </span>
                    </td>

                    {{-- Student Name --}}
                    <td>
                        <div class="fw-semibold" style="font-size:12px; line-height:1.3;">{{ $student->name }}</div>
                        <div class="text-muted" style="font-size:10.5px;">{{ $student->mobile }}</div>
                    </td>

                    <td class="fw-semibold" style="white-space:nowrap;">{{ $student->father_name ?: '—' }}</td>
                    <td class="fw-semibold" style="white-space:nowrap;">{{ $student->mother_name ?: '—' }}</td>
                    <td class="fw-semibold text-muted">{{ $student->roll_no ?: '—' }}</td>
                    <td class="fw-semibold text-muted">{{ $student->enrollment_no ?: '—' }}</td>
                    <td class="fw-semibold text-muted">{{ $student->uin_no ?: '—' }}</td>

                    {{-- Course --}}
                    <td>
                        <div class="fw-semibold" style="font-size:12px; line-height:1.3;">{{ $student->stream?->course?->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:10.5px;">{{ $student->stream?->name ?? '—' }}</div>
                    </td>

                    {{-- Year/Sem --}}
                    <td class="fw-semibold" style="white-space:nowrap;">
                        {{ $student->coursePart?->year_label ?? '—' }}
                        @if($student->current_semester)
                            <span class="badge bg-primary bg-opacity-10 text-primary border ms-1"
                                  style="font-size:9px;">S{{ $student->current_semester }}</span>
                        @endif
                    </td>

                    {{-- Student Type --}}
                    <td class="fw-semibold" style="white-space:nowrap;">
                        {{ $studentTypeNames[$student->student_type] ?? ucfirst(str_replace('_', ' ', $student->student_type ?? '')) ?: '—' }}
                    </td>

                    {{-- Admitted By --}}
                    <td>
                        <span class="badge {{ $admittedByBadge }} border"
                              style="font-size:10px; font-weight:600; white-space:normal; max-width:120px; display:inline-block; text-align:left;">
                            <i class="bi {{ $admittedByIcon }} me-1"></i>{{ $admittedByLabel }}
                        </span>
                    </td>

                    {{-- Source --}}
                    <td>
                        <span class="badge {{ $sourceBadge }} border"
                              style="font-size:10px; font-weight:600; white-space:nowrap;">
                            <i class="bi {{ $sourceIcon }} me-1"></i>{{ $sourceShort }}
                        </span>
                    </td>

                    {{-- Adm. Date --}}
                    <td class="fw-semibold text-muted" style="white-space:nowrap;">
                        {{ $student->admission_date?->format('d M Y') ?? '—' }}
                    </td>

                    {{-- Status --}}
                    <td>
                        <span class="badge border {{ $statusColor }}" style="font-size:10px; font-weight:600;">
                            {{ ucfirst($student->status ?? 'pending') }}
                        </span>
                    </td>

                    {{-- Actions --}}
                    <td>
                        <div class="d-flex gap-1">
                            <form method="POST" action="{{ route('admissions.resend-credentials', $student->id) }}"
                                  onsubmit="return confirm('Send new login credentials to {{ addslashes($student->name) }}?');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Send Login Details"
                                        style="padding:2px 6px; font-size:11px;">
                                    <i class="bi bi-envelope-arrow-up"></i>
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm {{ $loginState === 'allowed' ? 'btn-outline-success' : 'btn-danger' }}"
                                    title="Manage Login Access" style="padding:2px 6px; font-size:11px;"
                                    onclick="openLoginAccessModal('{{ route('admissions.login-access', $student->id) }}', '{{ addslashes($student->name) }}', '{{ $loginState }}', '{{ $student->suspended_until?->format('Y-m-d') }}')">
                                <i class="bi bi-{{ $loginState === 'allowed' ? 'unlock-fill' : 'lock-fill' }}"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="18" class="text-center text-muted py-5">
                        <i class="bi bi-people fs-2 d-block mb-2"></i>
                        No students found matching your filters.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($students->hasPages())
    <div class="card-footer bg-white border-top py-2">
        {{ $students->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>

{{-- Bulk Send Credentials (hidden form, auto-submitted after confirm) --}}
<form id="bulkSendForm" method="POST" action="{{ route('students.login-access.bulk-send-credentials') }}" style="display:none;">
    @csrf
    <div id="bulkSendIdsContainer"></div>
</form>

{{-- Bulk Login Access Modal --}}
<div id="bulkLoginAccessModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px 32px;max-width:420px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.18);">
        <h6 class="fw-bold mb-1"><i class="bi bi-shield-lock text-primary me-2"></i>Manage Login Access</h6>
        <p class="text-muted mb-3" style="font-size:13px;">
            Applying to <strong id="bulkAccessCount">0</strong> selected student(s). Admission records, fees, and admission-source listing stay unchanged — only portal login is affected.
        </p>
        <form id="bulkAccessForm" method="POST" action="{{ route('students.login-access.bulk-update-access') }}">
            @csrf
            <div id="bulkAccessIdsContainer"></div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="mode" id="bulkModeAllowed" value="allowed">
                <label class="form-check-label" for="bulkModeAllowed">Allowed — normal login</label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="mode" id="bulkModeBlocked" value="blocked">
                <label class="form-check-label" for="bulkModeBlocked">Blocked — until manually unblocked</label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="mode" id="bulkModeSuspended" value="suspended">
                <label class="form-check-label" for="bulkModeSuspended">Suspended until a date — auto-restores after</label>
            </div>
            <input type="date" class="form-control mt-2" name="suspended_until" id="bulkSuspendedUntilInput" style="display:none;">
            <div class="d-flex gap-2 justify-content-end mt-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('bulkLoginAccessModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Apply</button>
            </div>
        </form>
    </div>
</div>

{{-- Single-row Login Access Modal --}}
<div id="loginAccessModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px 32px;max-width:420px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.18);">
        <h6 class="fw-bold mb-1"><i class="bi bi-shield-lock text-primary me-2"></i>Manage Login Access</h6>
        <p class="mb-3" style="font-size:13px;">Set login access for <strong id="loginAccessTargetName"></strong></p>
        <form id="loginAccessForm" method="POST">
            @csrf
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="mode" id="modeAllowed" value="allowed">
                <label class="form-check-label" for="modeAllowed">Allowed — normal login</label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="mode" id="modeBlocked" value="blocked">
                <label class="form-check-label" for="modeBlocked">Blocked — until manually unblocked</label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="mode" id="modeSuspended" value="suspended">
                <label class="form-check-label" for="modeSuspended">Suspended until a date — auto-restores after</label>
            </div>
            <input type="date" class="form-control mt-2" name="suspended_until" id="suspendedUntilInput" style="display:none;">
            <div class="d-flex gap-2 justify-content-end mt-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('loginAccessModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Save</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    function getEl(id) { return document.getElementById(id); }

    // Hide/disable options by data-attribute. Resets selection if no longer visible.
    function filterOptions(selectId, dataAttr, val) {
        const sel = getEl(selectId);
        if (!sel) return;
        let selectedStillVisible = false;
        Array.from(sel.options).forEach(function (opt) {
            if (opt.value === '') return;
            const match = !val || opt.dataset[dataAttr] === String(val);
            opt.hidden   = !match;
            opt.disabled = !match;
            if (match && opt.selected) selectedStillVisible = true;
        });
        if (!selectedStillVisible) sel.value = '';
    }

    window.stdFilterCourses = function (courseTypeId) {
        filterOptions('filterCourse', 'type', courseTypeId);
        const cVal = getEl('filterCourse') ? getEl('filterCourse').value : '';
        filterOptions('filterStream', 'course', cVal);
        submitFilter();
    };

    window.stdFilterStreams = function (courseId) {
        filterOptions('filterStream', 'course', courseId);
        submitFilter();
    };

    window.stdAutoSubmit = function () {
        submitFilter();
    };

    function submitFilter() {
        const form = document.getElementById('filterForm');
        if (form) form.submit();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const ctVal = getEl('filterCourseType') ? getEl('filterCourseType').value : '';
        const cVal  = getEl('filterCourse')     ? getEl('filterCourse').value     : '';
        if (ctVal) filterOptions('filterCourse', 'type', ctVal);
        if (cVal)  filterOptions('filterStream', 'course', cVal);
    });

    // ── Bulk selection ──────────────────────────────────────────────
    function updateBulkBar() {
        const checked = document.querySelectorAll('.row-check:checked');
        const all     = document.querySelectorAll('.row-check');
        getEl('bulkCount').textContent = checked.length;
        getEl('bulkBar').style.display = checked.length > 0 ? 'flex' : 'none';
        const selectAll = getEl('selectAll');
        if (selectAll) selectAll.checked = all.length > 0 && checked.length === all.length;
    }

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.row-check:checked')).map(function (cb) { return cb.value; });
    }

    function fillHiddenIds(containerId) {
        const container = getEl(containerId);
        container.innerHTML = '';
        getSelectedIds().forEach(function (id) {
            const inp = document.createElement('input');
            inp.type  = 'hidden';
            inp.name  = 'student_ids[]';
            inp.value = id;
            container.appendChild(inp);
        });
    }

    window.submitBulkSend = function () {
        const ids = getSelectedIds();
        if (ids.length === 0) return;
        if (!confirm('Send login details to ' + ids.length + ' selected student(s)?')) return;
        fillHiddenIds('bulkSendIdsContainer');
        getEl('bulkSendForm').submit();
    };

    window.openBulkLoginAccessModal = function () {
        const ids = getSelectedIds();
        if (ids.length === 0) return;
        fillHiddenIds('bulkAccessIdsContainer');
        getEl('bulkAccessCount').textContent = ids.length;
        getEl('bulkLoginAccessModal').style.display = 'flex';
    };

    document.querySelectorAll('#bulkAccessForm input[name="mode"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            getEl('bulkSuspendedUntilInput').style.display = this.value === 'suspended' ? 'block' : 'none';
        });
    });

    const selectAllBox = getEl('selectAll');
    if (selectAllBox) {
        selectAllBox.addEventListener('change', function () {
            const checked = this.checked;
            document.querySelectorAll('.row-check').forEach(function (cb) { cb.checked = checked; });
            updateBulkBar();
        });
    }
    document.querySelectorAll('.row-check').forEach(function (cb) {
        cb.addEventListener('change', updateBulkBar);
    });

    // ── Single-row login access modal ───────────────────────────────
    window.openLoginAccessModal = function (url, name, state, suspendedUntil) {
        getEl('loginAccessForm').action = url;
        getEl('loginAccessTargetName').textContent = name;
        getEl('mode' + state.charAt(0).toUpperCase() + state.slice(1)).checked = true;
        getEl('suspendedUntilInput').style.display = state === 'suspended' ? 'block' : 'none';
        getEl('suspendedUntilInput').value = suspendedUntil || '';
        getEl('loginAccessModal').style.display = 'flex';
    };

    document.querySelectorAll('#loginAccessForm input[name="mode"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            getEl('suspendedUntilInput').style.display = this.value === 'suspended' ? 'block' : 'none';
        });
    });
}());
</script>
@endpush
