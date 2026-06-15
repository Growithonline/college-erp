@extends($layout ?? 'institute.layout')

@section('title', 'Daily Attendance')

@section('content')
@php
    $markedIds    = $attendance->keys()->toArray();
    $presentCount = $attendance->where('status', 'Present')->count();
    $absentCount  = $attendance->where('status', 'Absent')->count();
    $halfDayCount = $attendance->where('status', 'Half Day')->count();
    $leaveCount   = $attendance->whereIn('status', ['Paid Leave', 'Unpaid Leave'])->count();
    $otherCount   = $attendance->whereIn('status', ['Holiday', 'Week Off'])->count();
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
@endphp

<div class="container-fluid py-4">

    {{-- Lock Banner --}}
    @if($isLocked)
    <div class="alert alert-danger d-flex align-items-center mb-3 py-2">
        <i class="fas fa-lock me-2"></i>
        <strong>{{ $date->format('F Y') }} locked hai.</strong>
        <span class="ms-1">Attendance edit nahi hogi. Monthly view se unlock karein.</span>
    </div>
    @endif

    {{-- Header row --}}
    <div class="row mb-3 align-items-center">
        <div class="col">
            <h1 class="h4 mb-0">Daily Attendance</h1>
        </div>
    </div>

    {{-- Date navigation + filter --}}
    <div class="row mb-3">
        <div class="col-md-9">
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <a href="?date={{ $date->copy()->subDay()->toDateString() }}&category={{ urlencode($category ?? '') }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <form method="GET" class="d-flex gap-2 align-items-center mb-0">
                    <input type="date" name="date" class="form-control form-control-sm"
                           value="{{ $date->toDateString() }}" style="width: 150px;">
                    <select name="category" class="form-control form-control-sm" style="width: 160px;">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                </form>
                <a href="?date={{ $date->copy()->addDay()->toDateString() }}&category={{ urlencode($category ?? '') }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <span class="badge bg-primary fs-6 ms-1">
                    {{ $date->format('l, d M Y') }}
                </span>
            </div>
        </div>
        <div class="col-md-3 text-end">
            <div class="d-flex gap-2 align-items-center justify-content-end">
                <select id="bulkStatusSelect" class="form-select form-select-sm" style="width: 150px;">
                    <option value="Present">Present</option>
                    <option value="Absent">Absent</option>
                    <option value="Week Off">Week Off</option>
                    <option value="Holiday">Holiday</option>
                    <option value="Paid Leave">Paid Leave</option>
                    <option value="Half Day">Half Day</option>
                </select>
                <button class="btn btn-warning btn-sm" onclick="bulkMark()">
                    <i class="fas fa-check-square me-1"></i>Mark Selected
                </button>
            </div>
        </div>
    </div>

    {{-- Summary Stats Bar --}}
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
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:4%"></th>
                        <th style="width:24%">Staff Name</th>
                        <th style="width:13%">Category</th>
                        <th style="width:14%">Status</th>
                        <th style="width:9%">In Time</th>
                        <th style="width:9%">Out Time</th>
                        <th style="width:9%">Late (min)</th>
                        <th style="width:9%">OT (hrs)</th>
                        <th style="width:9%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staff as $member)
                        @php
                            $att = $attendance->get($member->id);
                            $statusClass = $att ? ($badgeMap[$att->status] ?? 'bg-secondary') : 'bg-secondary';
                        @endphp
                        <tr id="row-{{ $member->id }}"
                            class="{{ $att && in_array($att->status, ['Absent','Unpaid Leave']) ? 'table-danger bg-opacity-25' : '' }}">
                            <td class="text-center">
                                <input type="checkbox" class="staff-checkbox" value="{{ $member->id }}">
                            </td>
                            <td>
                                <strong>{{ $member->name }}</strong><br>
                                <small class="text-muted">{{ $member->role?->name }}</small>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark">{{ $member->staff_category }}</span>
                            </td>
                            <td>
                                <span class="status-badge badge {{ $statusClass }}">
                                    {{ $att?->status ?? 'Not Marked' }}
                                </span>
                            </td>
                            <td class="cell-in-time">{{ $att?->in_time?->format('H:i') ?? '-' }}</td>
                            <td class="cell-out-time">{{ $att?->out_time?->format('H:i') ?? '-' }}</td>
                            <td class="cell-late">{{ ($att?->late_minutes ?? 0) > 0 ? $att->late_minutes : '-' }}</td>
                            <td class="cell-ot">{{ ($att?->overtime_hours ?? 0) > 0 ? number_format($att->overtime_hours, 1) : '-' }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary edit-btn"
                                    data-staff-id="{{ $member->id }}"
                                    data-staff-name="{{ $member->name }}"
                                    data-status="{{ $att?->status ?? 'Present' }}"
                                    data-in-time="{{ $att?->in_time?->format('H:i') ?? '' }}"
                                    data-out-time="{{ $att?->out_time?->format('H:i') ?? '' }}"
                                    data-late-minutes="{{ $att?->late_minutes ?? 0 }}"
                                    data-overtime-hours="{{ $att?->overtime_hours ?? 0 }}"
                                    data-remarks="{{ $att?->remarks ?? '' }}"
                                    onclick="openModal(this)"
                                    @if($isLocked) disabled title="Month is locked" @endif>
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                No staff found for this category
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Attendance Modal --}}
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
                        <option value="Present">Present</option>
                        <option value="Absent">Absent</option>
                        <option value="Half Day">Half Day</option>
                        <option value="Paid Leave">Paid Leave</option>
                        <option value="Unpaid Leave">Unpaid Leave</option>
                        <option value="Holiday">Holiday</option>
                        <option value="Week Off">Week Off</option>
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">In Time</label>
                        <input type="time" id="modalInTime" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Out Time</label>
                        <input type="time" id="modalOutTime" class="form-control">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Late Minutes</label>
                        <input type="number" id="modalLate" class="form-control" min="0" value="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Overtime Hours</label>
                        <input type="number" id="modalOT" class="form-control" min="0" step="0.5" value="0">
                    </div>
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

{{-- Toast --}}
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1100">
    <div id="appToast" class="toast align-items-center border-0 text-white" role="alert" aria-live="assertive">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="toastMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
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

function showToast(msg, type = 'success') {
    const el = document.getElementById('appToast');
    el.className = `toast align-items-center border-0 text-white bg-${type}`;
    document.getElementById('toastMsg').textContent = msg;
    new bootstrap.Toast(el, { delay: 3000 }).show();
}

function updateSummaryStats() {
    const rows    = document.querySelectorAll('tbody tr[id^="row-"]');
    let present = 0, absent = 0, half = 0, leave = 0, notMarked = 0;
    rows.forEach(row => {
        const txt = row.querySelector('.status-badge')?.textContent?.trim() || '';
        if (txt === 'Present')                         present++;
        else if (txt === 'Absent')                     absent++;
        else if (txt === 'Half Day')                   half++;
        else if (txt === 'Paid Leave' || txt === 'Unpaid Leave') leave++;
        else if (txt === 'Not Marked')                 notMarked++;
    });
    document.getElementById('stat-present').textContent   = present;
    document.getElementById('stat-absent').textContent    = absent;
    document.getElementById('stat-half').textContent      = half;
    document.getElementById('stat-leave').textContent     = leave;
    document.getElementById('stat-notmarked').textContent = notMarked;
}

function updateRow(staffId, att) {
    const row = document.getElementById(`row-${staffId}`);
    if (!row) return;

    const badge = row.querySelector('.status-badge');
    badge.textContent = att.status;
    badge.className   = 'status-badge badge ' + (BADGE[att.status] || 'bg-secondary');

    row.querySelector('.cell-in-time').textContent  = att.in_time  ? att.in_time.substring(0,5)  : '-';
    row.querySelector('.cell-out-time').textContent = att.out_time ? att.out_time.substring(0,5) : '-';
    row.querySelector('.cell-late').textContent     = att.late_minutes > 0  ? att.late_minutes  : '-';
    row.querySelector('.cell-ot').textContent       = att.overtime_hours > 0 ? parseFloat(att.overtime_hours).toFixed(1) : '-';

    // Highlight absent rows
    row.classList.toggle('table-danger', ['Absent', 'Unpaid Leave'].includes(att.status));

    const btn = row.querySelector('.edit-btn');
    if (btn) {
        btn.dataset.status        = att.status;
        btn.dataset.inTime        = att.in_time  ? att.in_time.substring(0,5)  : '';
        btn.dataset.outTime       = att.out_time ? att.out_time.substring(0,5) : '';
        btn.dataset.lateMinutes   = att.late_minutes   || 0;
        btn.dataset.overtimeHours = att.overtime_hours || 0;
        btn.dataset.remarks       = att.remarks || '';
    }

    updateSummaryStats();
}

function openModal(btn) {
    document.getElementById('modalStaffId').value  = btn.dataset.staffId;
    document.getElementById('modalStaffName').textContent = btn.dataset.staffName;
    document.getElementById('modalStatus').value   = btn.dataset.status || 'Present';
    document.getElementById('modalInTime').value   = btn.dataset.inTime || '';
    document.getElementById('modalOutTime').value  = btn.dataset.outTime || '';
    document.getElementById('modalLate').value     = btn.dataset.lateMinutes || 0;
    document.getElementById('modalOT').value       = btn.dataset.overtimeHours || 0;
    document.getElementById('modalRemarks').value  = btn.dataset.remarks || '';

    const alert = document.getElementById('modalAlert');
    alert.className = 'alert d-none';

    new bootstrap.Modal(document.getElementById('attendanceModal')).show();
}

function submitAttendance() {
    const saveBtn = document.getElementById('modalSaveBtn');
    const spinner = document.getElementById('modalSpinner');
    const alertEl = document.getElementById('modalAlert');
    const staffId = document.getElementById('modalStaffId').value;

    saveBtn.disabled = true;
    spinner.classList.remove('d-none');
    alertEl.className = 'alert d-none';

    fetch('{{ route(($rp ?? 'finance') . ".payroll.attendance.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            staff_id:       staffId,
            date:           document.getElementById('modalDate').value,
            status:         document.getElementById('modalStatus').value,
            in_time:        document.getElementById('modalInTime').value  || null,
            out_time:       document.getElementById('modalOutTime').value || null,
            late_minutes:   parseInt(document.getElementById('modalLate').value)  || 0,
            overtime_hours: parseFloat(document.getElementById('modalOT').value)   || 0,
            remarks:        document.getElementById('modalRemarks').value || null,
        })
    })
    .then(r => r.json())
    .then(data => {
        saveBtn.disabled = false;
        spinner.classList.add('d-none');
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('attendanceModal')).hide();
            updateRow(staffId, data.data);
            showToast('Attendance saved successfully');
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

function bulkMark() {
    const checked = document.querySelectorAll('.staff-checkbox:checked');
    if (!checked.length) {
        showToast('Pehle kuch staff select karein', 'warning');
        return;
    }
    const status  = document.getElementById('bulkStatusSelect').value;
    const staffIds = Array.from(checked).map(c => c.value);

    if (!confirm(`${staffIds.length} staff ko "${status}" mark karein?`)) return;

    fetch('{{ route(($rp ?? 'finance') . ".payroll.attendance.bulk-mark") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            date:      document.getElementById('modalDate').value,
            staff_ids: staffIds,
            status:    status
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const failedIds = new Set((data.failures || []).map(f => String(f.staff_id)));
            checked.forEach(cb => {
                cb.checked = false;
                if (!failedIds.has(cb.value)) {
                    updateRow(cb.value, {
                        status: status, in_time: null, out_time: null,
                        late_minutes: 0, overtime_hours: 0, remarks: null
                    });
                }
            });
            document.getElementById('selectAll').checked = false;

            const msg = data.failures?.length
                ? `${data.count} marked, ${data.failures.length} failed`
                : data.message;
            showToast(msg, data.failures?.length ? 'warning' : 'success');
        } else {
            showToast(data.message, 'danger');
        }
    })
    .catch(() => showToast('Network error', 'danger'));
}

function toggleAll(cb) {
    document.querySelectorAll('.staff-checkbox').forEach(c => c.checked = cb.checked);
}
</script>
@endsection
