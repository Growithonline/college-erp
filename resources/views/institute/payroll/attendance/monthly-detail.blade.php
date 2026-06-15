@extends($layout ?? 'institute.layout')

@section('title', 'Staff Monthly Attendance Detail')

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="row mb-3">
        <div class="col-12 d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route(($rp ?? 'finance') . '.payroll.attendance.monthly', ['year' => $year, 'month' => $month, 'category' => $staff->staff_category]) }}"
               class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <h1 class="h4 mb-0">{{ $staff->name }}</h1>
            <span class="badge bg-primary">{{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}</span>
            <span class="badge bg-info text-dark">{{ $staff->staff_category }}</span>
            @if($isLocked)
                <span class="badge bg-danger"><i class="fas fa-lock me-1"></i>Locked</span>
            @else
                <span class="badge bg-success"><i class="fas fa-lock-open me-1"></i>Open</span>
            @endif
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="row mb-4 g-3">
        <div class="col-6 col-md-3 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">Present</div>
                    <div class="h3 text-success mb-0">{{ $summary['present'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">Absent</div>
                    <div class="h3 text-danger mb-0">{{ $summary['absent'] }}</div>
                </div>
            </div>
        </div>
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
                    <div class="text-muted small mb-1">Holiday</div>
                    <div class="h3 text-dark mb-0">{{ $summary['holiday'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">Week Off</div>
                    <div class="h3 mb-0">{{ $summary['week_off'] }}</div>
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
                    <div class="text-muted small mb-1">OT Hours</div>
                    <div class="h3 text-primary mb-0">{{ number_format($summary['total_overtime'], 1) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">Late (min)</div>
                    <div class="h3 mb-0">{{ $summary['total_late_minutes'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Salary Estimate Card --}}
    @if($salaryEstimate)
    <div class="card mb-4 border-success">
        <div class="card-header bg-success bg-opacity-10 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-success fw-semibold">
                <i class="fas fa-calculator me-1"></i> Expected Salary (Attendance ke aadhar par)
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
        <i class="fas fa-exclamation-triangle me-1"></i>
        Staff ki salary configure nahi hai. Staff profile mein monthly_salary ya daily_wage set karein.
    </div>
    @endif

    {{-- Daily Records Table --}}
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">Daily Attendance Records</h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 14%">Date</th>
                        <th style="width: 15%">Status</th>
                        <th style="width: 10%">In Time</th>
                        <th style="width: 10%">Out Time</th>
                        <th style="width: 9%">Late (min)</th>
                        <th style="width: 9%">OT (hrs)</th>
                        <th>Remarks</th>
                        <th style="width: 8%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $att)
                        @php
                            $rowClass = match($att->status) {
                                'Absent'       => 'table-danger',
                                'Unpaid Leave' => 'table-warning',
                                'Holiday'      => 'table-secondary',
                                'Week Off'     => 'table-light',
                                default        => '',
                            };
                            $badgeClass = match($att->status) {
                                'Present'      => 'bg-success',
                                'Absent'       => 'bg-danger',
                                'Half Day'     => 'bg-warning text-dark',
                                'Paid Leave'   => 'bg-info text-dark',
                                'Unpaid Leave' => 'bg-secondary',
                                'Holiday'      => 'bg-dark',
                                'Week Off'     => 'bg-light text-dark border',
                                default        => 'bg-secondary',
                            };
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td>
                                {{ $att->attendance_date->format('d-m-Y') }}
                                <small class="text-muted">({{ $att->attendance_date->format('D') }})</small>
                            </td>
                            <td><span class="badge {{ $badgeClass }}">{{ $att->status }}</span></td>
                            <td>{{ $att->in_time?->format('H:i') ?? '-' }}</td>
                            <td>{{ $att->out_time?->format('H:i') ?? '-' }}</td>
                            <td>{{ $att->late_minutes > 0 ? $att->late_minutes : '-' }}</td>
                            <td>{{ $att->overtime_hours > 0 ? number_format($att->overtime_hours, 1) : '-' }}</td>
                            <td><small class="text-muted">{{ $att->remarks ?? '-' }}</small></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary"
                                    data-staff-id="{{ $staff->id }}"
                                    data-staff-name="{{ $staff->name }}"
                                    data-date="{{ $att->attendance_date->toDateString() }}"
                                    data-status="{{ $att->status }}"
                                    data-in-time="{{ $att->in_time?->format('H:i') ?? '' }}"
                                    data-out-time="{{ $att->out_time?->format('H:i') ?? '' }}"
                                    data-late-minutes="{{ $att->late_minutes ?? 0 }}"
                                    data-overtime-hours="{{ $att->overtime_hours ?? 0 }}"
                                    data-remarks="{{ $att->remarks ?? '' }}"
                                    onclick="openEditModal(this)"
                                    @if($isLocked) disabled title="Month is locked" @endif>
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">
                                No attendance records for this period
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Edit Attendance Modal --}}
<div class="modal fade" id="editAttendanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Edit Attendance — <span id="modalStaffName"></span>
                    <small class="text-muted ms-2" id="modalDate"></small>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editStaffId">
                <input type="hidden" id="editDate">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Attendance Status <span class="text-danger">*</span></label>
                    <select id="editStatus" name="status" class="form-select" required>
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
                    <div class="col-md-6">
                        <label class="form-label">In Time</label>
                        <input type="time" id="editInTime" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Out Time</label>
                        <input type="time" id="editOutTime" class="form-control">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Late Minutes</label>
                        <input type="number" id="editLateMinutes" class="form-control" min="0" value="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Overtime Hours</label>
                        <input type="number" id="editOvertimeHours" class="form-control" min="0" step="0.5" value="0">
                    </div>
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

<script>
function openEditModal(btn) {
    document.getElementById('editStaffId').value      = btn.dataset.staffId;
    document.getElementById('editDate').value         = btn.dataset.date;
    document.getElementById('modalStaffName').textContent = btn.dataset.staffName;
    document.getElementById('modalDate').textContent  = btn.dataset.date;
    document.getElementById('editStatus').value       = btn.dataset.status || 'Present';
    document.getElementById('editInTime').value       = btn.dataset.inTime || '';
    document.getElementById('editOutTime').value      = btn.dataset.outTime || '';
    document.getElementById('editLateMinutes').value  = btn.dataset.lateMinutes || 0;
    document.getElementById('editOvertimeHours').value= btn.dataset.overtimeHours || 0;
    document.getElementById('editRemarks').value      = btn.dataset.remarks || '';

    const alertEl = document.getElementById('editModalAlert');
    alertEl.className = 'alert d-none';
    alertEl.textContent = '';

    new bootstrap.Modal(document.getElementById('editAttendanceModal')).show();
}

function saveAttendance() {
    const btn      = document.getElementById('editSaveBtn');
    const btnText  = document.getElementById('editSaveBtnText');
    const spinner  = document.getElementById('editSaveSpinner');
    const alertEl  = document.getElementById('editModalAlert');

    btn.disabled = true;
    spinner.classList.remove('d-none');
    alertEl.className = 'alert d-none';

    const payload = {
        staff_id:       document.getElementById('editStaffId').value,
        date:           document.getElementById('editDate').value,
        status:         document.getElementById('editStatus').value,
        in_time:        document.getElementById('editInTime').value || null,
        out_time:       document.getElementById('editOutTime').value || null,
        late_minutes:   parseInt(document.getElementById('editLateMinutes').value) || 0,
        overtime_hours: parseFloat(document.getElementById('editOvertimeHours').value) || 0,
        remarks:        document.getElementById('editRemarks').value || null,
    };

    fetch('{{ route(($rp ?? 'finance') . ".payroll.attendance.store") }}', {
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
</script>
@endsection
