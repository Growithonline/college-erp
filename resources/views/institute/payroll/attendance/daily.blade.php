@extends($layout ?? 'institute.layout')

@section('title', 'Daily Attendance')

@section('content')
@php
    $markedIds    = $attendance->keys()->toArray();
    $presentCount = $attendance->where('status', 'Present')->count();
    $absentCount  = $attendance->where('status', 'Absent')->count();
    $halfDayCount = $attendance->where('status', 'Half Day')->count();
    $leaveCount   = $attendance->whereIn('status', ['Paid Leave', 'Unpaid Leave'])->count();
    $notMarked    = $staff->filter(fn($m) => !$attendance->has($m->id))->count();
    $totalStaff   = $staff->count();

    $badgeMap = [
        'Present'      => 'bg-success',
        'Absent'       => 'bg-danger',
        'Half Day'     => 'bg-warning text-dark',
        'Paid Leave'   => 'bg-info text-dark',
        'Unpaid Leave' => 'bg-secondary',
        'Holiday'      => 'bg-dark',
        'Week Off'     => 'bg-light text-dark border',
    ];

    // Quick-mark statuses shown as one-click buttons on every row; everything
    // else (Unpaid Leave, Holiday, Week Off) plus in/out time & remarks live
    // behind the "More" action so the row stays scannable.
    $quickStatuses = [
        'Present'    => ['label' => 'P',  'color' => 'success'],
        'Absent'     => ['label' => 'A',  'color' => 'danger'],
        'Half Day'   => ['label' => 'HD', 'color' => 'warning'],
        'Paid Leave' => ['label' => 'Lv', 'color' => 'info'],
    ];

    $initials = fn($name) => collect(explode(' ', trim($name)))
        ->filter()
        ->map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

<div class="container-fluid py-4" id="attendancePage" data-locked="{{ $isLocked ? '1' : '0' }}">

    {{-- Lock banner --}}
    @if($isLocked)
    <div class="alert alert-danger d-flex align-items-center mb-3 py-2">
        <i class="bi bi-lock-fill me-2"></i>
        <strong>{{ $date->format('F Y') }} is locked.</strong>
        <span class="ms-1">Attendance can't be edited. Unlock it from the monthly view first.</span>
    </div>
    @endif

    {{-- Header --}}
    <div class="row mb-3 align-items-center">
        <div class="col">
            <h1 class="h4 mb-0">Daily Attendance</h1>
            <div class="text-muted small">Mark today's staff attendance in one click, adjust exceptions as needed.</div>
        </div>
    </div>

    {{-- Date navigation + category + search --}}
    <div class="row mb-3 g-2 align-items-center">
        <div class="col-md-7">
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <a href="?date={{ $date->copy()->subDay()->toDateString() }}&category={{ urlencode($category ?? '') }}"
                   class="btn btn-outline-secondary btn-sm" title="Previous day">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <form method="GET" class="d-flex gap-2 align-items-center mb-0">
                    <input type="date" name="date" class="form-control form-control-sm"
                           value="{{ $date->toDateString() }}" style="width: 150px;">
                    <select name="category" class="form-select form-select-sm" style="width: 160px;">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                </form>
                <a href="?date={{ $date->copy()->addDay()->toDateString() }}&category={{ urlencode($category ?? '') }}"
                   class="btn btn-outline-secondary btn-sm" title="Next day">
                    <i class="bi bi-chevron-right"></i>
                </a>
                <span class="badge bg-primary fs-6 ms-1">{{ $date->format('l, d M Y') }}</span>
            </div>
        </div>
        <div class="col-md-5">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" id="staffSearch" class="form-control" placeholder="Search staff by name…">
            </div>
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <button type="button" class="btn btn-success btn-sm" id="markAllPresentBtn" @if($isLocked) disabled @endif>
            <i class="bi bi-check2-all me-1"></i> Mark All Present
        </button>
        <button type="button" class="btn btn-outline-success btn-sm" id="markRemainingBtn" @if($isLocked) disabled @endif>
            <i class="bi bi-check2 me-1"></i> Mark Remaining Present
            <span class="badge bg-success ms-1" id="remainingBadge">{{ $notMarked }}</span>
        </button>

        <span class="vr mx-1 d-none d-md-inline"></span>

        <div class="d-flex gap-2 align-items-center ms-md-0">
            <select id="bulkStatusSelect" class="form-select form-select-sm" style="width: 150px;">
                @foreach(\App\Models\StaffAttendance::STATUSES as $s)
                    <option value="{{ $s }}">{{ $s }}</option>
                @endforeach
            </select>
            <button class="btn btn-outline-secondary btn-sm" id="bulkMarkBtn" @if($isLocked) disabled @endif>
                <i class="bi bi-check-square me-1"></i>Mark Selected
            </button>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="row mb-3 g-2" id="summaryBar">
        <div class="col-auto">
            <div class="px-3 py-2 rounded border text-center" style="min-width:90px">
                <div class="text-muted small">Total</div>
                <div class="fw-bold" id="stat-total">{{ $totalStaff }}</div>
            </div>
        </div>
        <div class="col-auto">
            <div class="px-3 py-2 rounded bg-success bg-opacity-10 border border-success text-center" style="min-width:90px">
                <div class="text-success small">Present</div>
                <div class="fw-bold text-success" id="stat-present">{{ $presentCount }}</div>
            </div>
        </div>
        <div class="col-auto">
            <div class="px-3 py-2 rounded bg-danger bg-opacity-10 border border-danger text-center" style="min-width:90px">
                <div class="text-danger small">Absent</div>
                <div class="fw-bold text-danger" id="stat-absent">{{ $absentCount }}</div>
            </div>
        </div>
        <div class="col-auto">
            <div class="px-3 py-2 rounded bg-warning bg-opacity-10 border border-warning text-center" style="min-width:90px">
                <div class="text-warning small">Half Day</div>
                <div class="fw-bold" id="stat-half">{{ $halfDayCount }}</div>
            </div>
        </div>
        <div class="col-auto">
            <div class="px-3 py-2 rounded bg-info bg-opacity-10 border border-info text-center" style="min-width:90px">
                <div class="text-info small">Leave</div>
                <div class="fw-bold" id="stat-leave">{{ $leaveCount }}</div>
            </div>
        </div>
        <div class="col-auto">
            <div class="px-3 py-2 rounded bg-secondary bg-opacity-10 border border-secondary text-center" style="min-width:90px">
                <div class="text-secondary small">Not Marked</div>
                <div class="fw-bold text-secondary" id="stat-notmarked">{{ $notMarked }}</div>
            </div>
        </div>
    </div>

    {{-- Main Table --}}
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold">
                Attendance Register
                @if($category) — {{ $category }} @endif
            </span>
            <label class="d-flex align-items-center gap-2 mb-0 small text-muted">
                <input type="checkbox" id="selectAll" onchange="toggleAll(this)"> Select All
            </label>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:3%"></th>
                        <th style="width:22%">Staff</th>
                        <th style="width:10%">Category</th>
                        <th style="width:13%">Status</th>
                        <th style="width:16%">Timing</th>
                        <th style="width:26%">Quick Mark</th>
                        <th style="width:5%"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staff as $member)
                        @php
                            $att = $attendance->get($member->id);
                            $statusClass = $att ? ($badgeMap[$att->status] ?? 'bg-secondary') : 'bg-secondary';
                        @endphp
                        <tr id="row-{{ $member->id }}" data-staff-name="{{ $member->name }}"
                            class="{{ $att && in_array($att->status, ['Absent','Unpaid Leave']) ? 'table-danger bg-opacity-25' : '' }}">
                            <td class="text-center">
                                <input type="checkbox" class="staff-checkbox" value="{{ $member->id }}">
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="width:32px;height:32px;font-size:12px;">
                                        {{ $initials($member->name) }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $member->name }}</div>
                                        <div class="small text-muted">{{ $member->role?->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark">{{ $member->staff_category }}</span>
                            </td>
                            <td>
                                <span class="status-badge badge {{ $statusClass }}">
                                    {{ $att?->status ?? 'Not Marked' }}
                                </span>
                            </td>
                            <td class="cell-timing small">
                                @if($att?->in_time || $att?->out_time)
                                    <div class="text-nowrap">{{ $att?->in_time?->format('H:i') ?? '—' }} → {{ $att?->out_time?->format('H:i') ?? '—' }}</div>
                                    <div class="d-flex gap-1 mt-1">
                                        @if(($att?->late_minutes ?? 0) > 0)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Late {{ $att->late_minutes }}m</span>
                                        @endif
                                        @if(($att?->overtime_hours ?? 0) > 0)
                                            <span class="badge bg-info-subtle text-info border border-info-subtle">+{{ number_format($att->overtime_hours, 1) }}h OT</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group quick-status" role="group">
                                    @foreach($quickStatuses as $statusName => $cfg)
                                        @php $isActive = $att?->status === $statusName; @endphp
                                        <button type="button"
                                            class="btn btn-sm qs-btn {{ $isActive ? 'btn-' . $cfg['color'] : 'btn-outline-' . $cfg['color'] }}"
                                            data-status="{{ $statusName }}"
                                            title="Mark {{ $statusName }}"
                                            onclick="quickMark({{ $member->id }}, '{{ $statusName }}', this)"
                                            @if($isLocked) disabled @endif>
                                            {{ $cfg['label'] }}
                                        </button>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-link text-muted more-btn p-0"
                                    data-staff-id="{{ $member->id }}"
                                    data-staff-name="{{ $member->name }}"
                                    data-status="{{ $att?->status ?? 'Present' }}"
                                    data-in-time="{{ $att?->in_time?->format('H:i') ?? '' }}"
                                    data-out-time="{{ $att?->out_time?->format('H:i') ?? '' }}"
                                    data-late-minutes="{{ $att?->late_minutes ?? 0 }}"
                                    data-overtime-hours="{{ $att?->overtime_hours ?? 0 }}"
                                    data-remarks="{{ $att?->remarks ?? '' }}"
                                    onclick="openModal(this)"
                                    title="More options (time, remarks, other statuses)"
                                    @if($isLocked) disabled @endif>
                                    <i class="bi bi-three-dots-vertical fs-5"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No staff found for this category
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div id="noSearchResults" class="text-center text-muted py-4 d-none">
                No staff match your search.
            </div>
        </div>
    </div>
</div>

{{-- Attendance Detail Modal --}}
<div class="modal fade" id="attendanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Mark Attendance — <span id="modalStaffName"></span>
                    <small class="text-muted ms-1">{{ $date->format('d M Y (D)') }}</small>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modalStaffId">
                <input type="hidden" id="modalDate" value="{{ $date->toDateString() }}">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select id="modalStatus" class="form-select" required>
                        @foreach(\App\Models\StaffAttendance::STATUSES as $s)
                            <option value="{{ $s }}">{{ $s }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="row g-3 mb-2">
                    <div class="col-6">
                        <label class="form-label">In Time</label>
                        <input type="time" id="modalInTime" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Out Time</label>
                        <input type="time" id="modalOutTime" class="form-control">
                    </div>
                </div>
                <div class="small text-muted mb-3">
                    Late arrival / overtime are calculated automatically from in/out time against the standard shift (09:00–17:00) — no need to enter them manually.
                    <span id="modalCalcPreview" class="fw-semibold"></span>
                </div>

                <div class="mb-2">
                    <label class="form-label">Remarks</label>
                    <textarea id="modalRemarks" class="form-control" rows="2"></textarea>
                </div>

                <div id="modalAlert" class="alert d-none mt-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="modalSaveBtn" onclick="submitAttendance()">
                    Save
                    <span id="modalSpinner" class="spinner-border spinner-border-sm d-none ms-1"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const STORE_URL = '{{ route(($rp ?? "finance") . ".payroll.attendance.store") }}';
const BULK_URL  = '{{ route(($rp ?? "finance") . ".payroll.attendance.bulk-mark") }}';
const CSRF      = document.querySelector('meta[name="csrf-token"]').content;
const IS_LOCKED = document.getElementById('attendancePage').dataset.locked === '1';

const BADGE = {
    'Present':      'bg-success',
    'Absent':       'bg-danger',
    'Half Day':     'bg-warning text-dark',
    'Paid Leave':   'bg-info text-dark',
    'Unpaid Leave': 'bg-secondary',
    'Holiday':      'bg-dark',
    'Week Off':     'bg-light text-dark border',
    'Not Marked':   'bg-secondary',
};
const QUICK_COLORS = { 'Present': 'success', 'Absent': 'danger', 'Half Day': 'warning', 'Paid Leave': 'info' };

let markedIds = new Set(@json(array_map('intval', $markedIds)));

function apiCall(url, payload) {
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(payload),
    }).then(r => r.json());
}

function updateSummaryStats() {
    const rows = Array.from(document.querySelectorAll('tbody tr[id^="row-"]'));
    let present = 0, absent = 0, half = 0, leave = 0, notMarked = 0;
    rows.forEach(row => {
        const txt = row.querySelector('.status-badge')?.textContent?.trim() || '';
        if (txt === 'Present') present++;
        else if (txt === 'Absent') absent++;
        else if (txt === 'Half Day') half++;
        else if (txt === 'Paid Leave' || txt === 'Unpaid Leave') leave++;
        else if (txt === 'Not Marked') notMarked++;
    });
    document.getElementById('stat-present').textContent   = present;
    document.getElementById('stat-absent').textContent    = absent;
    document.getElementById('stat-half').textContent      = half;
    document.getElementById('stat-leave').textContent     = leave;
    document.getElementById('stat-notmarked').textContent = notMarked;
    document.getElementById('remainingBadge').textContent = notMarked;
}

function updateRow(staffId, att) {
    const row = document.getElementById(`row-${staffId}`);
    if (!row) return;

    const badge = row.querySelector('.status-badge');
    badge.textContent = att.status;
    badge.className   = 'status-badge badge ' + (BADGE[att.status] || 'bg-secondary');

    const timing = row.querySelector('.cell-timing');
    if (att.in_time || att.out_time) {
        const inT  = att.in_time  ? att.in_time.substring(0,5)  : '—';
        const outT = att.out_time ? att.out_time.substring(0,5) : '—';
        let tags = '';
        if (att.late_minutes > 0) tags += `<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Late ${att.late_minutes}m</span> `;
        if (att.overtime_hours > 0) tags += `<span class="badge bg-info-subtle text-info border border-info-subtle">+${parseFloat(att.overtime_hours).toFixed(1)}h OT</span>`;
        timing.innerHTML = `<div class="text-nowrap">${inT} → ${outT}</div>` + (tags ? `<div class="d-flex gap-1 mt-1">${tags}</div>` : '');
    } else {
        timing.innerHTML = '<span class="text-muted">—</span>';
    }

    row.querySelectorAll('.qs-btn').forEach(btn => {
        const s = btn.dataset.status;
        const color = QUICK_COLORS[s];
        btn.className = 'btn btn-sm qs-btn ' + (s === att.status ? `btn-${color}` : `btn-outline-${color}`);
    });

    row.classList.toggle('table-danger', ['Absent', 'Unpaid Leave'].includes(att.status));

    const moreBtn = row.querySelector('.more-btn');
    if (moreBtn) {
        moreBtn.dataset.status        = att.status;
        moreBtn.dataset.inTime        = att.in_time  ? att.in_time.substring(0,5)  : '';
        moreBtn.dataset.outTime       = att.out_time ? att.out_time.substring(0,5) : '';
        moreBtn.dataset.lateMinutes   = att.late_minutes   || 0;
        moreBtn.dataset.overtimeHours = att.overtime_hours || 0;
        moreBtn.dataset.remarks       = att.remarks || '';
    }

    if (att.status) markedIds.add(parseInt(staffId));
    updateSummaryStats();
}

function quickMark(staffId, status, btnEl) {
    if (IS_LOCKED) return;
    const row = document.getElementById(`row-${staffId}`);
    const buttons = row.querySelectorAll('.qs-btn');
    buttons.forEach(b => b.disabled = true);

    apiCall(STORE_URL, { staff_id: staffId, date: document.getElementById('modalDate').value, status })
        .then(data => {
            buttons.forEach(b => b.disabled = false);
            if (data.success) {
                updateRow(staffId, data.data);
                showToast(`${row.dataset.staffName} marked ${status}`, 'success', 2000);
            } else {
                showToast(data.message, 'danger');
            }
        })
        .catch(() => {
            buttons.forEach(b => b.disabled = false);
            showToast('Network error. Please try again.', 'danger');
        });
}

function openModal(btn) {
    document.getElementById('modalStaffId').value  = btn.dataset.staffId;
    document.getElementById('modalStaffName').textContent = btn.dataset.staffName;
    document.getElementById('modalStatus').value   = btn.dataset.status || 'Present';
    document.getElementById('modalInTime').value   = btn.dataset.inTime || '';
    document.getElementById('modalOutTime').value  = btn.dataset.outTime || '';
    document.getElementById('modalRemarks').value  = btn.dataset.remarks || '';

    document.getElementById('modalAlert').className = 'alert d-none';
    updateCalcPreview();

    new bootstrap.Modal(document.getElementById('attendanceModal')).show();
}

function updateCalcPreview() {
    const inVal  = document.getElementById('modalInTime').value;
    const outVal = document.getElementById('modalOutTime').value;
    const preview = document.getElementById('modalCalcPreview');
    let parts = [];
    if (inVal) {
        const lateMin = Math.max(0, toMinutes(inVal) - toMinutes('09:00'));
        if (lateMin > 0) parts.push(`Late by ${lateMin} min`);
    }
    if (outVal) {
        const otMin = Math.max(0, toMinutes(outVal) - toMinutes('17:00'));
        if (otMin > 0) parts.push(`OT ${(otMin/60).toFixed(1)}h`);
    }
    preview.textContent = parts.length ? ('→ ' + parts.join(', ')) : '';
}
function toMinutes(hhmm) {
    const [h, m] = hhmm.split(':').map(Number);
    return h * 60 + m;
}
document.getElementById('modalInTime').addEventListener('input', updateCalcPreview);
document.getElementById('modalOutTime').addEventListener('input', updateCalcPreview);

function submitAttendance() {
    const saveBtn = document.getElementById('modalSaveBtn');
    const spinner = document.getElementById('modalSpinner');
    const alertEl = document.getElementById('modalAlert');
    const staffId = document.getElementById('modalStaffId').value;

    saveBtn.disabled = true;
    spinner.classList.remove('d-none');
    alertEl.className = 'alert d-none';

    apiCall(STORE_URL, {
        staff_id: staffId,
        date:     document.getElementById('modalDate').value,
        status:   document.getElementById('modalStatus').value,
        in_time:  document.getElementById('modalInTime').value  || null,
        out_time: document.getElementById('modalOutTime').value || null,
        remarks:  document.getElementById('modalRemarks').value || null,
    })
    .then(data => {
        saveBtn.disabled = false;
        spinner.classList.add('d-none');
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('attendanceModal')).hide();
            updateRow(staffId, data.data);
            showToast('Attendance saved', 'success');
        } else {
            alertEl.className = 'alert alert-danger';
            alertEl.textContent = data.message;
        }
    })
    .catch(() => {
        saveBtn.disabled = false;
        spinner.classList.add('d-none');
        alertEl.className = 'alert alert-danger';
        alertEl.textContent = 'Network error. Please try again.';
    });
}

function visibleStaffIds() {
    return Array.from(document.querySelectorAll('tbody tr[id^="row-"]'))
        .filter(row => row.style.display !== 'none')
        .map(row => row.id.replace('row-', ''));
}

function bulkApply(staffIds, status, successMsg) {
    if (!staffIds.length) return;
    apiCall(BULK_URL, { date: document.getElementById('modalDate').value, staff_ids: staffIds, status })
        .then(data => {
            if (data.success) {
                const failedIds = new Set((data.failures || []).map(f => String(f.staff_id)));
                staffIds.forEach(id => {
                    if (!failedIds.has(String(id))) {
                        updateRow(id, { status, in_time: null, out_time: null, late_minutes: 0, overtime_hours: 0, remarks: null });
                    }
                });
                const msg = data.failures?.length
                    ? `${data.count} marked, ${data.failures.length} failed`
                    : successMsg;
                showToast(msg, data.failures?.length ? 'warning' : 'success');
            } else {
                showToast(data.message, 'danger');
            }
        })
        .catch(() => showToast('Network error. Please try again.', 'danger'));
}

document.getElementById('markAllPresentBtn').addEventListener('click', function () {
    if (IS_LOCKED) return;
    const ids = visibleStaffIds();
    if (!ids.length) return;
    bulkApply(ids, 'Present', `Marked ${ids.length} staff Present`);
});

document.getElementById('markRemainingBtn').addEventListener('click', function () {
    if (IS_LOCKED) return;
    const ids = visibleStaffIds().filter(id => !markedIds.has(parseInt(id)));
    if (!ids.length) {
        showToast('Everyone visible is already marked', 'info');
        return;
    }
    bulkApply(ids, 'Present', `Marked ${ids.length} remaining staff Present`);
});

document.getElementById('bulkMarkBtn').addEventListener('click', function () {
    if (IS_LOCKED) return;
    const checked = document.querySelectorAll('.staff-checkbox:checked');
    if (!checked.length) {
        showToast('Select at least one staff member first', 'warning');
        return;
    }
    const status = document.getElementById('bulkStatusSelect').value;
    const staffIds = Array.from(checked).map(c => c.value);

    if (!confirm(`Mark ${staffIds.length} staff as "${status}"?`)) return;

    bulkApply(staffIds, status, `${staffIds.length} staff marked ${status}`);
    checked.forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
});

document.getElementById('staffSearch').addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    let anyVisible = false;
    document.querySelectorAll('tbody tr[id^="row-"]').forEach(row => {
        const match = row.dataset.staffName.toLowerCase().includes(q);
        row.style.display = match ? '' : 'none';
        if (match) anyVisible = true;
    });
    document.getElementById('noSearchResults').classList.toggle('d-none', anyVisible || !q);
});

function toggleAll(cb) {
    document.querySelectorAll('tbody tr[id^="row-"]').forEach(row => {
        if (row.style.display !== 'none') {
            const box = row.querySelector('.staff-checkbox');
            if (box) box.checked = cb.checked;
        }
    });
}
</script>
@endsection
