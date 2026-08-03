@extends($layout ?? 'institute.layout')

@section('title', 'Staff Monthly Attendance Detail')

@section('content')
@php
    $firstOfMonth = \Carbon\Carbon::createFromDate($year, $month, 1);
    $daysInMonth  = $firstOfMonth->daysInMonth;
    $startOffset  = $firstOfMonth->dayOfWeek; // 0 = Sunday
    $today        = \Carbon\Carbon::today();
    $attByDate    = $attendances->keyBy(fn($a) => $a->attendance_date->toDateString());
    $prevMonth    = $firstOfMonth->copy()->subMonthNoOverflow();
    $nextMonth    = $firstOfMonth->copy()->addMonthNoOverflow();

    $statusMeta = [
        'Present'      => ['label' => 'P',  'bg' => 'success',   'dot' => '#198754'],
        'Absent'       => ['label' => 'A',  'bg' => 'danger',    'dot' => '#dc3545'],
        'Half Day'     => ['label' => 'HD', 'bg' => 'warning',   'dot' => '#ffc107'],
        'Paid Leave'   => ['label' => 'PL', 'bg' => 'info',      'dot' => '#0dcaf0'],
        'Unpaid Leave' => ['label' => 'UL', 'bg' => 'secondary', 'dot' => '#6c757d'],
        'Holiday'      => ['label' => 'H',  'bg' => 'dark',      'dot' => '#212529'],
        'Week Off'     => ['label' => 'WO', 'bg' => 'light',     'dot' => '#dee2e6'],
    ];

    $markedWorking = $summary['present'] + $summary['absent'] + $summary['half_day'] + $summary['paid_leave'] + $summary['unpaid_leave'];
    $attended      = $summary['present'] + ($summary['half_day'] * 0.5) + $summary['paid_leave'];
    $attendancePct = $markedWorking > 0 ? round(($attended / $markedWorking) * 100, 1) : null;
@endphp

<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="row mb-3">
        <div class="col-12 d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route(($rp ?? 'finance') . '.payroll.attendance.monthly', ['year' => $year, 'month' => $month, 'category' => $staff->staff_category]) }}"
               class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center"
                 style="width:36px;height:36px;font-size:13px;">
                {{ collect(explode(' ', trim($staff->name)))->filter()->map(fn($p) => mb_strtoupper(mb_substr($p,0,1)))->take(2)->implode('') }}
            </div>
            <h1 class="h4 mb-0">{{ $staff->name }}</h1>
            <span class="badge bg-info text-dark">{{ $staff->staff_category }}</span>
            @if($isLocked)
                <span class="badge bg-danger"><i class="bi bi-lock-fill me-1"></i>Locked</span>
            @else
                <span class="badge bg-success"><i class="bi bi-unlock-fill me-1"></i>Open</span>
            @endif
        </div>
    </div>

    {{-- Calendar card --}}
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route(($rp ?? 'finance') . '.payroll.attendance.monthly', ['staff_id' => $staff->id, 'year' => $prevMonth->year, 'month' => $prevMonth->month]) }}"
                   class="btn btn-outline-secondary btn-sm" title="Previous month">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <span class="fw-semibold fs-6">{{ $firstOfMonth->format('F Y') }}</span>
                <a href="{{ route(($rp ?? 'finance') . '.payroll.attendance.monthly', ['staff_id' => $staff->id, 'year' => $nextMonth->year, 'month' => $nextMonth->month]) }}"
                   class="btn btn-outline-secondary btn-sm {{ $nextMonth->startOfMonth()->isAfter($today) ? 'disabled' : '' }}" title="Next month">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="exportMonth()">
                    <i class="bi bi-download me-1"></i> Export This Month
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="calendarToggleBtn"
                        data-bs-toggle="collapse" data-bs-target="#calendarBody"
                        aria-expanded="true" aria-controls="calendarBody" title="Collapse/expand calendar">
                    <i class="bi bi-chevron-up" id="calendarToggleIcon"></i>
                </button>
            </div>
        </div>

        <div class="collapse show" id="calendarBody">
        <div class="card-body">
            <div class="calendar-grid">
                @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dow)
                    <div class="cal-dow">{{ $dow }}</div>
                @endforeach

                @for($i = 0; $i < $startOffset; $i++)
                    <div class="cal-day cal-day-empty"></div>
                @endfor

                @for($d = 1; $d <= $daysInMonth; $d++)
                    @php
                        $cellDate = $firstOfMonth->copy()->day($d);
                        $iso      = $cellDate->toDateString();
                        $att      = $attByDate->get($iso);
                        $meta     = $att ? ($statusMeta[$att->status] ?? null) : null;
                        $isFuture = $cellDate->isAfter($today);
                        $isToday  = $cellDate->isSameDay($today);
                    @endphp
                    <div class="cal-day {{ $isToday ? 'cal-day-today' : '' }} {{ $isFuture ? 'cal-day-future' : '' }} {{ $att && !$isLocked && !$isFuture ? 'cal-day-clickable' : '' }}"
                         data-marked="{{ $att ? 1 : 0 }}"
                         data-date="{{ $iso }}"
                         data-day-name="{{ $cellDate->format('l') }}"
                         data-status="{{ $att?->status ?? '' }}"
                         data-in-time="{{ $att?->in_time?->format('H:i') ?? '' }}"
                         data-out-time="{{ $att?->out_time?->format('H:i') ?? '' }}"
                         data-late-minutes="{{ $att?->late_minutes ?? 0 }}"
                         data-overtime-hours="{{ $att?->overtime_hours ?? 0 }}"
                         data-remarks="{{ $att?->remarks ?? '' }}"
                         @if($att && !$isLocked && !$isFuture) onclick="openEditModal(this)" @endif
                         title="{{ $att ? $att->status : ($isFuture ? 'Future date' : 'Not marked') }}">
                        <div class="cal-day-num">{{ $d }}</div>
                        @if($meta)
                            <span class="badge bg-{{ $meta['bg'] }} {{ in_array($meta['bg'], ['warning','light']) ? 'text-dark' : '' }} cal-status-badge">{{ $meta['label'] }}</span>
                        @elseif(!$isFuture)
                            <span class="cal-not-marked" title="Not marked">·</span>
                        @endif
                    </div>
                @endfor
            </div>

            {{-- Legend + quick stats --}}
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3 pt-3 border-top">
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $summary['present'] }} Present</span>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ $summary['absent'] }} Absent</span>
                    @if($attendancePct !== null)
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Attendance: {{ $attendancePct }}%</span>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-3 small text-muted">
                    <span><span class="cal-not-marked">·</span> Not marked</span>
                    @foreach($statusMeta as $label => $meta)
                        <span><span class="badge bg-{{ $meta['bg'] }} {{ in_array($meta['bg'], ['warning','light']) ? 'text-dark' : '' }} cal-status-badge">{{ $meta['label'] }}</span> = {{ $label }}</span>
                    @endforeach
                </div>
            </div>
        </div>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="row mb-4 g-3">
        <div class="col-6 col-md-3 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">Half Day</div>
                    <div class="h3 text-warning mb-0">{{ $summary['half_day'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">Paid Leave</div>
                    <div class="h3 text-info mb-0">{{ $summary['paid_leave'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">Unpaid Leave</div>
                    <div class="h3 text-secondary mb-0">{{ $summary['unpaid_leave'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">Holiday / Week Off</div>
                    <div class="h3 mb-0">{{ $summary['holiday'] + $summary['week_off'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <div class="card text-center border-success h-100">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">Payable Days</div>
                    <div class="h3 text-success mb-0">{{ number_format($summary['payable_days'], 1) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">OT Hours / Late (min)</div>
                    <div class="h3 mb-0"><span class="text-primary">{{ number_format($summary['total_overtime'], 1) }}</span> / {{ $summary['total_late_minutes'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Salary Estimate Card --}}
    @if($salaryEstimate)
    <div class="card mb-4 border-success">
        <div class="card-header bg-success bg-opacity-10 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-success fw-semibold">
                <i class="bi bi-calculator me-1"></i> Expected Salary (based on attendance)
            </h6>
            <small class="text-muted">
                {{ $staff->payroll_type === 'monthly' ? 'Monthly' : 'Daily Wage' }} —
                {{ $staff->payroll_type === 'monthly'
                    ? '₹' . number_format($staff->monthly_salary, 2) . '/month'
                    : '₹' . number_format($staff->daily_wage, 2) . '/day' }}
            </small>
        </div>
        <div class="card-body py-3">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="text-muted small mb-1">Basic Pay</div>
                    <div class="fw-bold fs-5">₹{{ number_format($salaryEstimate['basic_salary'], 2) }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted small mb-1">OT Allowance</div>
                    <div class="fw-bold fs-5 text-primary">₹{{ number_format($salaryEstimate['allowances'], 2) }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted small mb-1">Deductions</div>
                    <div class="fw-bold fs-5 text-danger">— ₹{{ number_format($salaryEstimate['deductions'], 2) }}</div>
                    @if($salaryEstimate['deductions'] > 0)
                        <small class="text-muted">
                            ({{ $summary['unpaid_leave'] }} unpaid leave
                            @if($summary['half_day'] > 0) + {{ $summary['half_day'] }} half day @endif)
                        </small>
                    @endif
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted small mb-1">Net Payable</div>
                    <div class="fw-bold fs-4 text-success">₹{{ number_format($salaryEstimate['net_payable'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    @elseif($staff->monthly_salary === null && $staff->daily_wage === null)
    <div class="alert alert-warning mb-4">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        This staff member's salary is not configured. Set a monthly salary or daily wage in the staff profile.
    </div>
    @endif
</div>

{{-- Edit Attendance Modal --}}
<div class="modal fade" id="editAttendanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Edit Attendance — <span id="modalStaffName">{{ $staff->name }}</span>
                    <small class="text-muted ms-2" id="modalDate"></small>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editStaffId" value="{{ $staff->id }}">
                <input type="hidden" id="editDate">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Attendance Status <span class="text-danger">*</span></label>
                    <select id="editStatus" name="status" class="form-select" required>
                        @foreach(\App\Models\StaffAttendance::STATUSES as $s)
                            <option value="{{ $s }}">{{ $s }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="row g-3 mb-2">
                    <div class="col-md-6">
                        <label class="form-label">In Time</label>
                        <input type="time" id="editInTime" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Out Time</label>
                        <input type="time" id="editOutTime" class="form-control">
                    </div>
                </div>
                <div class="small text-muted mb-3">
                    Late arrival / overtime are calculated automatically from in/out time against the standard shift (09:00–17:00).
                </div>

                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea id="editRemarks" class="form-control" rows="2"></textarea>
                </div>

                <div id="editModalAlert" class="alert d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="editSaveBtn" onclick="saveAttendance()">
                    <span id="editSaveBtnText">Save Changes</span>
                    <span id="editSaveSpinner" class="spinner-border spinner-border-sm d-none ms-1"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
}
.cal-dow {
    text-align: center;
    font-size: 12px;
    font-weight: 600;
    color: #6c757d;
    padding: 4px 0;
    text-transform: uppercase;
    letter-spacing: .03em;
}
.cal-day {
    position: relative;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    min-height: 64px;
    padding: 6px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}
.cal-day-empty { border: none; }
.cal-day-num { font-size: 12px; color: #495057; align-self: flex-start; }
.cal-day-future { opacity: .45; }
.cal-day-today { border-color: #0d6efd; border-width: 2px; }
.cal-day-clickable { cursor: pointer; transition: background-color .15s ease; }
.cal-day-clickable:hover { background-color: #f8f9fa; }
.cal-status-badge { font-size: 11px; font-weight: 700; padding: 3px 7px; }
.cal-not-marked { color: #adb5bd; font-size: 20px; line-height: 1; }
</style>

<script>
const calendarBody = document.getElementById('calendarBody');
const calendarIcon = document.getElementById('calendarToggleIcon');
calendarBody.addEventListener('show.bs.collapse', () => calendarIcon.className = 'bi bi-chevron-up');
calendarBody.addEventListener('hide.bs.collapse', () => calendarIcon.className = 'bi bi-chevron-down');

function openEditModal(cell) {
    document.getElementById('editDate').value         = cell.dataset.date;
    document.getElementById('modalDate').textContent  = `${cell.dataset.date} (${cell.dataset.dayName})`;
    document.getElementById('editStatus').value       = cell.dataset.status || 'Present';
    document.getElementById('editInTime').value       = cell.dataset.inTime || '';
    document.getElementById('editOutTime').value      = cell.dataset.outTime || '';
    document.getElementById('editRemarks').value      = cell.dataset.remarks || '';

    const alertEl = document.getElementById('editModalAlert');
    alertEl.className = 'alert d-none';
    alertEl.textContent = '';

    new bootstrap.Modal(document.getElementById('editAttendanceModal')).show();
}

function saveAttendance() {
    const btn      = document.getElementById('editSaveBtn');
    const spinner  = document.getElementById('editSaveSpinner');
    const alertEl  = document.getElementById('editModalAlert');

    btn.disabled = true;
    spinner.classList.remove('d-none');
    alertEl.className = 'alert d-none';

    const payload = {
        staff_id: document.getElementById('editStaffId').value,
        date:     document.getElementById('editDate').value,
        status:   document.getElementById('editStatus').value,
        in_time:  document.getElementById('editInTime').value || null,
        out_time: document.getElementById('editOutTime').value || null,
        remarks:  document.getElementById('editRemarks').value || null,
    };

    fetch('{{ route(($rp ?? "finance") . ".payroll.attendance.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        spinner.classList.add('d-none');
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('editAttendanceModal')).hide();
            location.reload();
        } else {
            alertEl.className = 'alert alert-danger';
            alertEl.textContent = data.message;
        }
    })
    .catch(() => {
        btn.disabled = false;
        spinner.classList.add('d-none');
        alertEl.className = 'alert alert-danger';
        alertEl.textContent = 'Network error. Please try again.';
    });
}

function exportMonth() {
    const rows = [['Date', 'Day', 'Status', 'In Time', 'Out Time', 'Late (min)', 'OT (hrs)', 'Remarks']];
    document.querySelectorAll('.cal-day[data-marked="1"]').forEach(cell => {
        rows.push([
            cell.dataset.date, cell.dataset.dayName, cell.dataset.status,
            cell.dataset.inTime || '-', cell.dataset.outTime || '-',
            cell.dataset.lateMinutes || '0', cell.dataset.overtimeHours || '0',
            cell.dataset.remarks || '',
        ]);
    });
    const csv = rows.map(r => r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `attendance-{{ \Illuminate\Support\Str::slug($staff->name) }}-{{ $year }}-{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}
</script>
@endsection
