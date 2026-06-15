@php
    $isStaff = auth()->guard('staff')->check();
    $layout          = $isStaff ? 'staff.layout'              : 'institute.layout';
    $feeIndexRoute   = $isStaff ? 'staff.fee.index'           : 'fee.index';
    $feeCreateRoute  = $isStaff ? 'staff.fee.create'          : 'fee.create';
    $receiptRoute    = $isStaff ? 'staff.fee.receipt'         : 'fee.receipt';
    $historyRoute    = $isStaff ? 'staff.fee.student-history' : 'fee.student-history';
    $studentHistoryRoute = $historyRoute; // backward compat alias
    $cancelRoute     = $isStaff ? 'staff.fee.cancel'          : 'fee.cancel';
    $canCollectFee   = !$isStaff || auth()->guard('staff')->user()?->canCollectFee();
@endphp
@extends($layout)
@section('title','Fee Collection')
@section('breadcrumb','Fee / Collections')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Fee Collection</h4>
        <small class="text-muted">{{ $activeSession->name ?? '' }}</small>
    </div>
    @if($canCollectFee)
    <a href="{{ route($feeCreateRoute) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Collect Fee
    </a>
    @endif
</div>

{{-- Stats cards — date range ke hisaab se --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">Total Collected</div>
                <div class="fw-bold fs-5 text-success">Rs {{ number_format($totalPaid, 0) }}</div>
                <div class="text-muted" style="font-size:11px;">{{ $totalInvoices }} invoices</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">Cash / UPI / Online</div>
                <div class="fw-bold small">
                    <span class="text-success">{{ $cashCount }} (Rs {{ number_format($cashAmt,0) }})</span><br>
                    <span class="text-primary">{{ $upiCount }} (Rs {{ number_format($upiAmt,0) }})</span><br>
                    <span class="text-info">{{ $onlineCount }} (Rs {{ number_format($onlineAmt,0) }})</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">Cheque / DD</div>
                <div class="fw-bold small">
                    <span class="text-warning">Cheque: {{ $chequeCount }} (Rs {{ number_format($chequeAmt,0) }})</span><br>
                    <span class="text-secondary">DD: {{ $ddCount }} (Rs {{ number_format($ddAmt,0) }})</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">Date Range</div>
                <div class="fw-semibold small">
                    {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}<br>
                    to {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">Cancelled Invoices</div>
                <div class="fw-bold fs-5 text-danger">{{ number_format($cancelledInvoices ?? 0) }}</div>
                <div class="text-muted" style="font-size:11px;">Visible in current filters</div>
            </div>
        </div>
    </div>
    @if(($libFineCount ?? 0) > 0)
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:3px solid #0891b2!important;">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">
                    <i class="bi bi-book me-1" style="color:#0891b2;"></i>Library Fine Collected
                </div>
                <div class="fw-bold fs-5" style="color:#0891b2;">Rs {{ number_format($libFineTotal ?? 0, 0) }}</div>
                <div class="text-muted" style="font-size:11px;">{{ $libFineCount }} payment(s)</div>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" id="filterForm">
            <div class="row g-2 mb-2">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm"
                           value="{{ $dateFrom }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm"
                           value="{{ $dateTo }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Session</label>
                    <select name="session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Sessions</option>
                        @foreach($sessions as $s)
                            <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected':'' }}>
                                {{ $s->name }}{{ $s->is_active ? ' (Active)':'' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Course</label>
                    <select name="course_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Courses</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected':'' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold mb-1">Sem</label>
                    <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All</option>
                        @for($i=1;$i<=8;$i++)
                            <option value="{{ $i }}" {{ request('semester') == $i ? 'selected':'' }}>S{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Collected By</label>
                    <select name="collected_by" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Staff</option>
                        @foreach($collectedByList as $cb)
                            <option value="{{ $cb }}" {{ request('collected_by') == $cb ? 'selected':'' }}>{{ $cb }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Mode</label>
                    <select name="payment_mode" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Modes</option>
                        @foreach(['cash'=>'Cash','upi'=>'UPI','online'=>'Online','cheque'=>'Cheque','dd'=>'DD','neft'=>'NEFT','rtgs'=>'RTGS'] as $v=>$l)
                            <option value="{{ $v }}" {{ request('payment_mode')==$v ? 'selected':'' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                        <option value="active" {{ ($status ?? 'all') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="cancelled" {{ ($status ?? 'all') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control form-control-sm" placeholder="Student name, mobile, invoice no...">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Search</button>
                </div>
                <div class="col-auto">
                    <a href="{{ route($feeIndexRoute) }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
                <div class="col-auto ms-auto d-flex gap-1 flex-wrap">
                    @php
                        $todayParams = array_merge(request()->except(['date_from','date_to']), ['date_from'=>now()->toDateString(),'date_to'=>now()->toDateString()]);
                        $monthParams = array_merge(request()->except(['date_from','date_to']), ['date_from'=>now()->startOfMonth()->toDateString(),'date_to'=>now()->toDateString()]);
                        $isToday = $dateFrom == now()->toDateString() && $dateTo == now()->toDateString();
                    @endphp
                    <a href="{{ route($feeIndexRoute, $todayParams) }}"
                       class="btn btn-sm {{ $isToday ? 'btn-primary' : 'btn-outline-secondary' }}">Today</a>
                    <a href="{{ route($feeIndexRoute, $monthParams) }}"
                       class="btn btn-sm btn-outline-secondary">This Month</a>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-layout-three-columns"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width:200px;" onclick="event.stopPropagation()">
                            @foreach(['col_invoice'=>'Invoice No & Time','col_student'=>'Student','col_uid'=>'Student ID / UIN','col_father'=>'Father / Mother','col_course'=>'Course / Year','col_session'=>'Session','col_utr'=>'UTR / Ref No.','col_discount'=>'Discount','col_remarks'=>'Remarks','col_by'=>'Collected By','col_centre'=>'Centre','col_mode'=>'Payment Mode','col_amount'=>'Amount'] as $col=>$label)
                            <li>
                                <label class="dropdown-item d-flex align-items-center gap-2 small py-1" style="cursor:pointer;">
                                    <input type="checkbox" class="col-toggle" data-col="{{ $col }}"
                                           onchange="toggleCol('{{ $col }}', this.checked)"
                                           {{ in_array($col, ['col_invoice','col_student','col_uid','col_father','col_course','col_mode','col_amount']) ? 'checked' : '' }}>
                                    {{ $label }}
                                </label>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:13px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3 col_invoice">Invoice No <span class="text-muted fw-normal">(Time)</span></th>
                        <th class="col_student">Student</th>
                        <th class="col_uid">Student ID</th>
                        <th class="col_father">Father / Mother</th>
                        <th class="col_course">Course / Year</th>
                        <th class="col_session" style="display:none;">Session</th>
                        <th class="col_utr" style="display:none;">UTR / Ref</th>
                        <th class="col_discount" style="display:none;">Discount</th>
                        <th class="col_remarks" style="display:none;">Remarks</th>
                        <th class="col_by" style="display:none;">Collected By</th>
                        <th class="col_centre" style="display:none;">Centre</th>
                        <th class="col_mode">Mode</th>
                        <th class="col_amount text-end">Collection</th>
                        <th class="text-end">Total Amt</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    @php
                        $modeColors = ['cash'=>'success','upi'=>'primary','online'=>'info','cheque'=>'warning','dd'=>'secondary','neft'=>'dark','rtgs'=>'dark'];
                        $color = $modeColors[$inv->payment_mode] ?? 'secondary';
                        $student = $inv->student;
                    @endphp
                    <tr class="{{ $inv->is_cancelled ? 'table-danger opacity-75' : '' }}">
                        <td class="ps-3 col_invoice">
                            <span class="badge bg-light text-dark border fw-semibold">{{ $inv->invoice_no }}</span>
                            <div class="text-muted" style="font-size:10px;">{{ $inv->created_at?->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
                            @if($inv->is_cancelled)<span class="badge bg-danger" style="font-size:9px;">Cancelled</span>@endif
                        </td>
                        <td class="col_student">
                            <div class="fw-semibold">{{ $student->name }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ $student->mobile ?? '' }}</div>
                        </td>
                        <td class="col_uid">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle" style="font-size:10px;">
                                {{ $student->student_uid }}
                            </span>
                            @if($student->roll_no)
                            <div class="text-muted" style="font-size:10px;">Roll: {{ $student->roll_no }}</div>
                            @endif
                        </td>
                        <td class="col_father">
                            <div class="small">{{ $student->father_name ?? '—' }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ $student->mother_name ?? '' }}</div>
                        </td>
                        <td class="col_course">
                            <div class="small fw-semibold">{{ $student->stream->course->name ?? '—' }}</div>
                            <div class="text-muted" style="font-size:11px;">
                                {{ $student->stream->name ?? '' }}
                                @if($student->coursePart) · {{ $student->coursePart->year_label }} @endif
                                @if($inv->semester) · S{{ $inv->semester }} @endif
                            </div>
                        </td>
                        <td class="col_session small text-muted" style="display:none;">{{ $inv->session->name ?? '—' }}</td>
                        <td class="col_utr small" style="display:none;">
                            {{ $inv->transaction_ref ?? '—' }}
                            @if($inv->bank_name)
                                <div class="text-muted" style="font-size:11px;">{{ $inv->bank_name }}</div>
                            @endif
                        </td>
                        <td class="col_discount text-end small" style="display:none;">
                            @if(($inv->discount ?? 0) > 0)
                                <span class="text-danger fw-semibold">-Rs{{ number_format($inv->discount) }}</span>
                            @else <span class="text-muted">—</span> @endif
                        </td>
                        <td class="col_remarks small text-muted" style="display:none;">{{ $inv->remarks ?? '—' }}</td>
                        <td class="col_by small" style="display:none;">{{ $inv->collected_by ?? '—' }}</td>
                        <td class="col_centre small text-muted" style="display:none;">—</td>
                        <td class="col_mode">
                            <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }} border border-{{ $color }}">
                                {{ strtoupper($inv->payment_mode) }}
                            </span>
                        </td>
                        <td class="col_amount text-end fw-bold text-success">Rs {{ number_format($inv->paid_amount) }}</td>
                        <td class="text-end fw-bold">Rs {{ number_format($inv->paid_amount + ($inv->discount ?? 0)) }}</td>
                        <td class="text-center">
                            <a href="{{ route($receiptRoute, [$inv->student_id, $inv->id]) }}"
                               class="btn btn-sm btn-outline-primary" title="Print" target="_blank">
                                <i class="bi bi-printer"></i>
                            </a>
                            <a href="{{ route($historyRoute, $inv->student_id) }}"
                               class="btn btn-sm btn-outline-secondary ms-1" title="History">
                                <i class="bi bi-clock-history"></i>
                            </a>
                            @if(!$inv->is_cancelled && $canCollectFee)
                            <button type="button" class="btn btn-sm btn-outline-danger ms-1"
                                    onclick="showCancelModal('{{ $inv->invoice_no }}', '{{ route($cancelRoute, [$inv->student_id, $inv->id]) }}')">
                                <i class="bi bi-x-circle"></i>
                            </button>
                            @else
                            <span class="badge bg-danger ms-1" style="font-size:9px;">Cancelled</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="15" class="text-center py-5 text-muted">
                            <i class="bi bi-receipt fs-2 d-block mb-2"></i>No fee collections found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-top-0">
        @include('institute.components.pagination', ['paginator' => $invoices, 'perPage' => $perPage ?? 20])
    </div>
</div>

{{-- ── Library Fine Collections ─────────────────────────────────────── --}}
@if(($libFineCount ?? 0) > 0)
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-semibold">
            <i class="bi bi-book me-2" style="color:#0891b2;"></i>Library Fine Collections
            <span class="badge ms-2 fw-normal" style="background:#e0f2fe;color:#0369a1;">{{ $libFineCount }}</span>
        </h6>
        <span class="fw-bold" style="color:#0891b2;">Total: Rs {{ number_format($libFineTotal, 0) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:13px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Receipt No</th>
                        <th>Student</th>
                        <th>Student ID</th>
                        <th>Book(s)</th>
                        <th>Mode</th>
                        <th>Date</th>
                        <th>Collected By</th>
                        <th class="text-end pe-3">Amount</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($libFinePayments as $lfp)
                    @php
                        $lfStudent  = $lfp->member?->student;
                        $modeColors = ['cash'=>'success','upi'=>'primary','online'=>'info','cheque'=>'warning','dd'=>'secondary','neft'=>'dark','rtgs'=>'dark'];
                        $lfColor    = $modeColors[$lfp->payment_mode] ?? 'secondary';
                        $bookTitle  = $lfp->transaction?->copy?->book?->title ?? '—';
                        $accNo      = $lfp->transaction?->copy?->accession_no ?? '';
                    @endphp
                    <tr>
                        <td class="ps-3">
                            <span class="badge bg-light border fw-semibold text-dark">{{ $lfp->receipt_no ?? '—' }}</span>
                            <div class="text-muted" style="font-size:10px;">{{ $lfp->created_at?->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $lfp->member?->name ?? '—' }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ $lfStudent?->mobile ?? '' }}</div>
                        </td>
                        <td>
                            @if($lfStudent)
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle" style="font-size:10px;">
                                {{ $lfStudent->student_uid }}
                            </span>
                            @else <span class="text-muted small">—</span> @endif
                        </td>
                        <td class="small">
                            <div>{{ $bookTitle }}</div>
                            @if($accNo) <div class="text-muted" style="font-size:10px;">{{ $accNo }}</div> @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $lfColor }} bg-opacity-10 text-{{ $lfColor }} border border-{{ $lfColor }}">
                                {{ strtoupper($lfp->payment_mode) }}
                            </span>
                        </td>
                        <td class="small text-muted">{{ \Carbon\Carbon::parse($lfp->payment_date)->format('d M Y') }}</td>
                        <td class="small text-muted">{{ $lfp->collected_by ?? '—' }}</td>
                        <td class="text-end pe-3 fw-bold" style="color:#0891b2;">Rs {{ number_format($lfp->amount, 0) }}</td>
                        <td class="text-center">
                            @if($lfp->member_id ?? $lfp->library_member_id)
                            <a href="{{ route($libFineReceiptRoute, [$lfp->library_member_id, urlencode($lfp->receipt_no)]) }}"
                               class="btn btn-sm btn-outline-primary" title="Print Receipt" target="_blank">
                                <i class="bi bi-printer"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-semibold">
                    <tr>
                        <td colspan="7" class="ps-3 small text-muted">{{ $libFineCount }} payment(s) in selected date range</td>
                        <td class="text-end pe-3 fw-bold" style="color:#0891b2;">Rs {{ number_format($libFineTotal, 0) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    const saved = JSON.parse(localStorage.getItem('feeColPrefs') || '{}');
    document.querySelectorAll('.col-toggle').forEach(cb => {
        const col = cb.dataset.col;
        if (saved[col] !== undefined) { cb.checked = saved[col]; toggleCol(col, saved[col], false); }
        else { toggleCol(col, cb.checked, false); }
    });
});
function toggleCol(col, visible, save = true) {
    document.querySelectorAll('.' + col).forEach(el => { el.style.display = visible ? '' : 'none'; });
    if (save) {
        const saved = JSON.parse(localStorage.getItem('feeColPrefs') || '{}');
        saved[col] = visible;
        localStorage.setItem('feeColPrefs', JSON.stringify(saved));
    }
}
function showCancelModal(invoiceNo, actionUrl) {
    document.getElementById('cancelInvoiceNo').textContent = invoiceNo;
    document.getElementById('cancelForm').action = actionUrl;
    document.getElementById('cancelReason').value = '';
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}
</script>

<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-x-circle me-2"></i>Cancel Invoice</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="cancelForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning border-0 py-2 small mb-3">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Invoice <strong id="cancelInvoiceNo"></strong> cannot be reversed once cancelled.
                        Student's due amount will be automatically restored.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Cancel Reason <span class="text-danger">*</span></label>
                        <textarea name="cancel_reason" id="cancelReason" class="form-control" rows="3" required
                                  placeholder="Enter reason for cancellation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Go Back</button>
                    <button type="submit" class="btn btn-danger btn-sm">Yes, Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
