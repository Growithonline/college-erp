@extends('institute.layout')
@section('title', 'Promotion Report')
@section('breadcrumb', 'Admissions / Promotion Report')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text me-2 text-success"></i>Promotion Report</h4>
        <small class="text-muted">Full promotion history — date, time, promoted by</small>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <a href="{{ request()->fullUrlWithQuery(['export'=>'csv']) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-download me-1"></i> CSV
        </a>
        <a href="{{ route('admissions.promote.semester') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-up-circle me-1"></i> Semester
        </a>
        <a href="{{ route('admissions.promote.session') }}" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-calendar-arrow-up me-1"></i> Session
        </a>
        <a href="{{ route('admissions.promote.outcomes') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-award me-1"></i> Outcomes
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Type</label>
                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="semester"    {{ request('type')=='semester'    ? 'selected':'' }}>Semester</option>
                    <option value="session"     {{ request('type')=='session'     ? 'selected':'' }}>Session</option>
                    <option value="readmission" {{ request('type')=='readmission' ? 'selected':'' }}>Re-Admission</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">From Session</label>
                <select name="from_session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ request('from_session_id') == $s->id ? 'selected':'' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">To Session</label>
                <select name="to_session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ request('to_session_id') == $s->id ? 'selected':'' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach(['promoted' => 'Promoted', 'completed' => 'Completed', 'passed_out' => 'Passed Out', 'backlog' => 'Backlog', 'failed' => 'Failed', 'dropped' => 'Dropped', 'reversed' => 'Reversed'] as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control form-control-sm" placeholder="Name, UID...">
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary btn-sm px-4"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('admissions.promote.report') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-2">
        <span class="fw-semibold small"><i class="bi bi-clock-history me-2 text-success"></i>Promotion Logs ({{ $total }})</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:13px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th style="min-width:110px;">Std ID</th>
                        <th style="min-width:130px;">Student</th>
                        <th style="min-width:100px;">Father</th>
                        <th style="min-width:100px;">Mother</th>
                        <th>Course</th>
                        <th class="text-center">Type</th>
                        <th>From</th>
                        <th>To</th>
                        <th class="text-end">Dues CF</th>
                        <th>Promoted By</th>
                        <th>Date & Time</th>
                        <th class="text-center no-print">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $i => $log)
                    @php
                        $structureType = strtolower((string) ($log->student->stream->course->structure_type ?? ''));
                        $fromYearNumber = $log->from_semester ? (int) ceil($log->from_semester / 2) : (int) ($log->fromCoursePart->year_number ?? 0);
                        $toYearNumber = $log->to_semester ? (int) ceil($log->to_semester / 2) : (int) ($log->toCoursePart->year_number ?? 0);
                        $terminalStatus = $log->terminal_status ?: ($log->status === 'completed' ? 'passed_out' : $log->status);
                        $fromYearLabel = $structureType === 'semester' && $fromYearNumber > 0
                            ? \App\Support\AcademicState::ordinalYearLabel($fromYearNumber)
                            : ($log->fromCoursePart->year_label ?? '');
                        $toYearLabel = $structureType === 'semester' && $toYearNumber > 0
                            ? \App\Support\AcademicState::ordinalYearLabel($toYearNumber)
                            : ($log->toCoursePart->year_label ?? '');
                    @endphp
                    <tr class="{{ $log->is_reversed ? 'opacity-50' : '' }}">
                        <td class="ps-3 text-muted small">{{ $logs->firstItem() + $i }}</td>
                        <td style="white-space:nowrap;">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle" style="font-size:10.5px;">
                                {{ $log->student->student_uid ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $log->student->name ?? '—' }}</div>
                        </td>
                        <td class="small">{{ $log->student->father_name ?? '—' }}</td>
                        <td class="small">{{ $log->student->mother_name ?? '—' }}</td>
                        <td class="small">{{ $log->student->stream->course->name ?? '—' }}</td>
                        <td class="text-center">
                            @if($log->promotion_type === 'semester')
                                <span class="badge text-white" style="font-size:10px; background:#0dcaf0;">Semester</span>
                            @elseif($log->promotion_type === 'session')
                                <span class="badge text-dark" style="font-size:10px; background:#ffc107;">Session</span>
                            @else
                                <span class="badge bg-secondary" style="font-size:10px;">{{ $log->promotion_type ?? '—' }}</span>
                            @endif
                        </td>
                        <td class="small">
                            <div>{{ $log->fromSession->name ?? '—' }}</div>
                            <div class="text-muted">{{ $fromYearLabel }}
                                @if($log->from_semester) · Sem {{ $log->from_semester }} @endif
                            </div>
                        </td>
                        <td class="small">
                            <div class="fw-semibold text-success">{{ $log->toSession->name ?? '—' }}</div>
                            <div class="text-muted">{{ $toYearLabel }}
                                @if($log->to_semester) · Sem {{ $log->to_semester }} @endif
                            </div>
                        </td>
                        <td class="text-end small">
                            @if((float)$log->dues_carried_forward > 0)
                                <span class="text-danger fw-semibold">₹ {{ number_format((float)$log->dues_carried_forward) }}</span>
                                @if(!empty($log->carry_forward_context['from_session_name']))
                                    <div class="text-muted" style="font-size:11px;">
                                        {{ $log->carry_forward_context['from_session_name'] }}
                                        @if(!empty($log->carry_forward_context['from_semester']))
                                            · Sem {{ $log->carry_forward_context['from_semester'] }}
                                        @endif
                                    </div>
                                @endif
                            @else <span class="text-muted">—</span> @endif
                        </td>
                        <td class="small">
                            <div>{{ $log->promoted_by ?? '—' }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ ucfirst($log->promoted_by_role ?? '') }}</div>
                        </td>
                        <td class="small">
                            <div>{{ $log->created_at?->setTimezone('Asia/Kolkata')->format('d M Y') }}</div>
                            <div class="text-muted">{{ $log->created_at?->setTimezone('Asia/Kolkata')->format('h:i A') }}</div>
                        </td>
                        <td class="text-center no-print">
                            @if($log->is_reversed)
                                <span class="badge bg-secondary" style="font-size:9px;">Reversed</span>
                            @elseif(in_array($terminalStatus, ['passed_out', 'backlog', 'failed', 'dropped'], true))
                                <span class="badge bg-success bg-opacity-15 text-success border" style="font-size:9px;">{{ ucwords(str_replace('_', ' ', $terminalStatus)) }}</span>
                            @elseif($log->status === 'reversed')
                                <span class="badge bg-secondary" style="font-size:9px;">Reversal</span>
                            @else
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="confirmReverse({{ $log->id }}, '{{ addslashes($log->student->name ?? '') }}')"
                                        title="Reverse Promotion">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13" class="text-center py-5 text-muted">
                            <i class="bi bi-clock-history fs-2 d-block mb-2"></i>No promotion records found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-top-0">
        {{ $logs->withQueryString()->links() }}
    </div>
</div>

<style>
@media print {
    .no-print, form, .card-footer, nav[aria-label="pagination"], .alert { display: none !important; }
    .print-header { display: block !important; }
    body, table, th, td { font-size: 8.5px !important; }
    th, td { padding: 2px 4px !important; white-space: nowrap; }
    .badge { font-size: 7.5px !important; padding: 1px 3px !important; background: none !important; border: none !important; color: inherit !important; }
    .opacity-50 { opacity: 1 !important; }
    h4, h5 { font-size: 13px !important; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
}
.print-header { display: none; }
</style>

<div class="print-header mb-2">
    <div class="d-flex justify-content-between align-items-end border-bottom pb-1">
        <div>
            <h5 class="fw-bold mb-0"><i class="bi bi-file-earmark-text me-1"></i>Promotion Report</h5>
            <small class="text-muted">Full promotion history — date, time, promoted by</small>
        </div>
        <div class="text-end">
            <div style="font-size:10px;"><strong>Total:</strong> {{ $total }}</div>
            <div style="font-size:9px;color:#666;">Printed: {{ now()->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
        </div>
    </div>
</div>

{{-- Reversal Modal --}}
<div class="modal fade" id="reverseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-arrow-counterclockwise me-2"></i>Reverse Promotion
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="reverseForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning border-0 py-2 small mb-3">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        The promotion for <strong id="reverseStudentName"></strong> will be reversed.
                        The student will be restored to their previous state.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required
                                  placeholder="Enter reason for reversal..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Yes, Reverse
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmReverse(logId, studentName) {
    document.getElementById('reverseStudentName').textContent = studentName;
    document.getElementById('reverseForm').action = `/admissions/promote/reverse/${logId}`;
    new bootstrap.Modal(document.getElementById('reverseModal')).show();
}
</script>
@endsection

