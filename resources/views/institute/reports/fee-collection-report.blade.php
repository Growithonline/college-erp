@php
    $isStaff = auth()->guard('staff')->check();
    $layout = $isStaff ? 'staff.layout' : 'institute.layout';
    $feeCollectionRoute = $isStaff ? 'staff.reports.fee-collection' : 'reports.fee-collection';
    $receiptRoute = $isStaff ? 'staff.fee.receipt' : 'fee.receipt';
@endphp
@extends($layout)
@section('title', 'Fee Collection Report')
@section('breadcrumb', 'Reports / Fee Collection')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Fee Collection Report</h4>
        <small class="text-muted">{{ $sessionId ? ($sessionObj?->name ?? 'Session') : 'All Sessions' }} — Date-wise, mode-wise collection</small>
    </div>
    <div class="d-flex gap-2">
        <button onclick="printReport()" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </button>
        <a href="{{ request()->fullUrlWithQuery(['export'=>'excel']) }}"
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </a>
        <a href="{{ request()->fullUrlWithQuery(['export'=>'csv']) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-filetype-csv me-1"></i> CSV
        </a>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success bg-opacity-10 p-2">
                        <i class="bi bi-cash-stack text-success fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Collected</div>
                        <div class="fw-bold fs-6 text-success">₹ {{ number_format($totalCollected) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-2">
                        <i class="bi bi-receipt text-primary fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Invoices</div>
                        <div class="fw-bold fs-6">{{ number_format($totalInvoices) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-info bg-opacity-10 p-2">
                        <i class="bi bi-people text-info fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Students</div>
                        <div class="fw-bold fs-6">{{ number_format($totalStudents) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning bg-opacity-10 p-2">
                        <i class="bi bi-tag text-warning fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Discount</div>
                        <div class="fw-bold fs-6 text-warning">₹ {{ number_format($totalDiscount) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100" style="border-left:3px solid #f59e0b !important;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2" style="background:rgba(245,158,11,0.1);">
                        <i class="bi bi-exclamation-triangle fs-5" style="color:#f59e0b;"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Fine</div>
                        <div class="fw-bold fs-6" style="color:#f59e0b;">₹ {{ number_format($totalFine) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Mode-wise + Fee-type breakdown --}}
<div class="row g-3 mb-4">
    {{-- Payment Mode breakdown --}}
    @if($modeWise->isNotEmpty())
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header py-2 bg-white border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-semibold small"><i class="bi bi-pie-chart me-1 text-primary"></i> Payment Mode Breakdown</span>
                <span class="text-muted" style="font-size:10px;"><i class="bi bi-hand-index me-1"></i>Click row for details</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Mode</th>
                            <th class="text-center">Count</th>
                            <th class="text-end pe-3">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($modeWise as $m)
                        @php $modeKey = $m->payment_mode; @endphp
                        <tr style="cursor:pointer;" class="table-row-hover"
                            onclick="showModeDetail('{{ $modeKey }}')">
                            <td class="ps-3">
                                <span class="badge
                                    {{ $modeKey=='cash' ? 'bg-success' : '' }}
                                    {{ $modeKey=='upi' ? 'bg-primary' : '' }}
                                    {{ $modeKey=='online' ? 'bg-info text-dark' : '' }}
                                    {{ $modeKey=='cheque' ? 'bg-warning text-dark' : '' }}
                                    {{ $modeKey=='dd' ? 'bg-secondary' : '' }}
                                    {{ $modeKey=='neft' ? 'bg-dark' : '' }}
                                    {{ $modeKey=='rtgs' ? 'bg-danger' : '' }}
                                    bg-opacity-75">
                                    {{ strtoupper($modeKey) }}
                                </span>
                            </td>
                            <td class="text-center small">{{ $m->cnt }}</td>
                            <td class="text-end pe-3 fw-semibold small">₹ {{ number_format($m->total) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-semibold">
                        <tr>
                            <td class="ps-3">Total</td>
                            <td class="text-center">{{ $modeWise->sum('cnt') }}</td>
                            <td class="text-end pe-3 text-success">₹ {{ number_format($modeWise->sum('total')) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Fee Type breakdown --}}
    @if($feeTypeWise->isNotEmpty())
    <div class="col-md-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header py-2 bg-white border-bottom">
                <span class="fw-semibold small"><i class="bi bi-list-ul me-1 text-success"></i> Fee Type Breakdown</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:12px;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Fee Type</th>
                            <th class="text-center">Cnt</th>
                            <th class="text-end">Charged</th>
                            <th class="text-end text-danger">Fine</th>
                            <th class="text-end text-success">Paid</th>
                            <th class="text-end" style="color:#7c3aed;">Disc</th>
                            <th class="text-end text-danger pe-3">Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($feeTypeWise as $f)
                        @php $fDue = max(0, ($f->charged_total ?? 0) + ($f->fine_total ?? 0) - ($f->paid_total ?? 0) - ($f->disc_total ?? 0)); @endphp
                        <tr>
                            <td class="ps-3 small">{{ $f->fee_name }}</td>
                            <td class="text-center small text-muted">{{ $f->cnt }}</td>
                            <td class="text-end small">₹ {{ number_format($f->charged_total ?? 0) }}</td>
                            <td class="text-end small text-danger">
                                {{ ($f->fine_total ?? 0) > 0 ? '₹ '.number_format($f->fine_total) : '—' }}
                            </td>
                            <td class="text-end small text-success fw-semibold">₹ {{ number_format($f->paid_total ?? 0) }}</td>
                            <td class="text-end small" style="color:#7c3aed;">
                                {{ ($f->disc_total ?? 0) > 0 ? '₹ '.number_format($f->disc_total) : '—' }}
                            </td>
                            <td class="text-end small pe-3 {{ $fDue > 0 ? 'text-danger fw-semibold' : 'text-success' }}">
                                {{ $fDue > 0 ? '₹ '.number_format($fDue) : '✓' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-semibold">
                        @php
                            $ftChargedTotal = $feeTypeWise->sum('charged_total');
                            $ftFineTotal    = $feeTypeWise->sum('fine_total');
                            $ftPaidTotal    = $feeTypeWise->sum('paid_total');
                            $ftDiscTotal    = $feeTypeWise->sum('disc_total');
                            $ftDueTotal     = max(0, $ftChargedTotal + $ftFineTotal - $ftPaidTotal - $ftDiscTotal);
                        @endphp
                        <tr>
                            <td class="ps-3">Total</td>
                            <td class="text-center">{{ $feeTypeWise->sum('cnt') }}</td>
                            <td class="text-end">₹ {{ number_format($ftChargedTotal) }}</td>
                            <td class="text-end text-danger">{{ $ftFineTotal > 0 ? '₹ '.number_format($ftFineTotal) : '—' }}</td>
                            <td class="text-end text-success">₹ {{ number_format($ftPaidTotal) }}</td>
                            <td class="text-end" style="color:#7c3aed;">₹ {{ number_format($ftDiscTotal) }}</td>
                            <td class="text-end pe-3 {{ $ftDueTotal > 0 ? 'text-danger' : 'text-success' }}">₹ {{ number_format($ftDueTotal) }}</td>
                        </tr>
                    </tfoot>
                </table>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- Mode Detail Modal --}}
<div class="modal fade" id="modeDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold" id="modeDetailTitle">Mode Breakdown</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="modeDetailBody"></div>
            </div>
        </div>
    </div>
</div>

@php
    // Prepare modeBankWise as JSON for JS
    $modeBankJson = [];
    foreach ($modeBankWise as $mode => $rows) {
        $modeBankJson[$mode] = $rows->map(fn($r) => [
            'bank'      => $r->bank_label ?: '—',
            'collector' => $r->collector,
            'cnt'       => $r->cnt,
            'total'     => $r->total,
        ])->values()->toArray();
    }
@endphp

{{-- Collector-wise Breakdown Table --}}
@if(isset($collectorWise) && $collectorWise->isNotEmpty())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-2">
        <h6 class="mb-0 fw-semibold small">
            <i class="bi bi-person-badge me-2 text-primary"></i>Collector-wise Breakdown
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0" style="font-size:13px;">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Collected By</th>
                    <th class="text-end">Cash (₹)</th>
                    <th class="text-end">UPI (₹)</th>
                    <th class="text-end">Online (₹)</th>
                    <th class="text-end">Cheque (₹)</th>
                    <th class="text-end">DD (₹)</th>
                    <th class="text-end">Invoices</th>
                    <th class="text-end pe-3 fw-bold">Total (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($collectorWise as $cw)
                <tr>
                    <td class="ps-3 fw-semibold">{{ $cw->collected_by ?? '— Direct —' }}</td>
                    <td class="text-end">{{ $cw->cash_amt   > 0 ? number_format($cw->cash_amt,0)   : '—' }}</td>
                    <td class="text-end">{{ $cw->upi_amt    > 0 ? number_format($cw->upi_amt,0)    : '—' }}</td>
                    <td class="text-end">{{ $cw->online_amt > 0 ? number_format($cw->online_amt,0) : '—' }}</td>
                    <td class="text-end">{{ $cw->cheque_amt > 0 ? number_format($cw->cheque_amt,0) : '—' }}</td>
                    <td class="text-end">{{ $cw->dd_amt     > 0 ? number_format($cw->dd_amt,0)     : '—' }}</td>
                    <td class="text-end text-muted small">{{ $cw->invoice_cnt }}</td>
                    <td class="text-end pe-3 fw-bold text-success">₹ {{ number_format($cw->total_amt,0) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-dark">
                <tr>
                    <td class="ps-3 fw-bold">Total</td>
                    <td class="text-end fw-bold">₹ {{ number_format($collectorWise->sum('cash_amt'),0) }}</td>
                    <td class="text-end fw-bold">₹ {{ number_format($collectorWise->sum('upi_amt'),0) }}</td>
                    <td class="text-end fw-bold">₹ {{ number_format($collectorWise->sum('online_amt'),0) }}</td>
                    <td class="text-end fw-bold">₹ {{ number_format($collectorWise->sum('cheque_amt'),0) }}</td>
                    <td class="text-end fw-bold">₹ {{ number_format($collectorWise->sum('dd_amt'),0) }}</td>
                    <td class="text-end fw-bold text-muted">{{ $collectorWise->sum('invoice_cnt') }}</td>
                    <td class="text-end pe-3 fw-bold">₹ {{ number_format($collectorWise->sum('total_amt'),0) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- Bank-wise Breakdown --}}
@if(isset($bankWise) && $bankWise->isNotEmpty())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
        <span class="fw-semibold small"><i class="bi bi-bank me-1 text-info"></i> Bank-wise Collection</span>
        <span class="text-muted" style="font-size:10px;"><i class="bi bi-hand-index me-1"></i>Click a row for details</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Bank / Account</th>
                    <th class="text-center">Invoices</th>
                    <th class="text-end pe-3">Amount Collected</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bankWise as $bw)
                <tr style="cursor:pointer;" class="table-row-hover"
                    onclick="showBankDetail('{{ addslashes($bw->bank_label) }}')">
                    <td class="ps-3 fw-semibold">
                        <i class="bi bi-bank2 me-1 text-info"></i>{{ $bw->bank_label }}
                    </td>
                    <td class="text-center small text-muted">{{ $bw->cnt }}</td>
                    <td class="text-end pe-3 fw-semibold text-success small">₹ {{ number_format($bw->total) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light fw-semibold">
                <tr>
                    <td class="ps-3">Total</td>
                    <td class="text-center">{{ $bankWise->sum('cnt') }}</td>
                    <td class="text-end pe-3 text-success">₹ {{ number_format($bankWise->sum('total')) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- Bank Detail Modal --}}
<div class="modal fade" id="bankDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold" id="bankDetailTitle">Bank Collection Details</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="bankDetailBody"></div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route($feeCollectionRoute) }}" id="filterForm">
            {{-- Quick Date Shortcuts --}}
            @php
                $todayStr     = now()->toDateString();
                $yesterdayStr = now()->subDay()->toDateString();
                $monthStart   = now()->startOfMonth()->toDateString();
                $isToday      = ($dateFrom === $todayStr && $dateTo === $todayStr);
                $isYesterday  = ($dateFrom === $yesterdayStr && $dateTo === $yesterdayStr);
                $isThisMonth  = ($dateFrom === $monthStart && $dateTo === $todayStr);
                $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
                $lastMonthEnd   = now()->subMonthNoOverflow()->endOfMonth()->toDateString();
                $isLastMonth  = ($dateFrom === $lastMonthStart && $dateTo === $lastMonthEnd);
            @endphp
            <div class="d-flex flex-wrap align-items-center gap-1 mb-3">
                <span class="text-muted me-1" style="font-size:11px;">Quick:</span>
                <button type="button" onclick="setDateRange('today')"
                    class="btn btn-sm py-0 px-2 {{ $isToday ? 'btn-primary' : 'btn-outline-secondary' }}"
                    style="font-size:11px;">Today</button>
                <button type="button" onclick="setDateRange('yesterday')"
                    class="btn btn-sm py-0 px-2 {{ $isYesterday ? 'btn-primary' : 'btn-outline-secondary' }}"
                    style="font-size:11px;">Yesterday</button>
                <button type="button" onclick="setDateRange('month')"
                    class="btn btn-sm py-0 px-2 {{ $isThisMonth ? 'btn-primary' : 'btn-outline-secondary' }}"
                    style="font-size:11px;">This Month</button>
                <button type="button" onclick="setDateRange('lastmonth')"
                    class="btn btn-sm py-0 px-2 {{ $isLastMonth ? 'btn-primary' : 'btn-outline-secondary' }}"
                    style="font-size:11px;">Last Month</button>
            </div>
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Session</label>
                    <select name="session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="" {{ !$sessionId ? 'selected':'' }}>All Sessions</option>
                        @foreach($sessions as $s)
                            <option value="{{ $s->id }}" {{ $sessionId==$s->id ? 'selected':'' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm"
                           value="{{ $dateFrom ?? now()->toDateString() }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm"
                           value="{{ $dateTo ?? now()->toDateString() }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Sem</label>
                    <select name="semester" class="form-select form-select-sm">
                        <option value="">All</option>
                        @for($i=1;$i<=8;$i++)
                            <option value="{{ $i }}" {{ request('semester')==$i ? 'selected':'' }}>S{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Collected By</label>
                    @if($isLimitedCollector ?? false)
                        <input type="hidden" name="collected_by" value="{{ $limitedToCollector }}">
                        <div class="form-control form-control-sm bg-light text-muted d-flex align-items-center gap-1" style="cursor:not-allowed;">
                            <i class="bi bi-lock-fill text-warning" style="font-size:11px;"></i>
                            {{ $limitedToCollector }}
                        </div>
                    @else
                        <select name="collected_by" class="form-select form-select-sm">
                            <option value="">All Staff</option>
                            @foreach($collectedByList ?? [] as $cb)
                                <option value="{{ $cb }}" {{ request('collected_by')==$cb ? 'selected':'' }}>{{ $cb }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Mode</label>
                    <select name="payment_mode" class="form-select form-select-sm">
                        <option value="">All Modes</option>
                        @foreach(['cash'=>'Cash','upi'=>'UPI','online'=>'Online','cheque'=>'Cheque','dd'=>'DD'] as $v=>$l)
                            <option value="{{ $v }}" {{ request('payment_mode')==$v ? 'selected':'' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Course Type</label>
                    <select name="course_type_id" id="fcCourseType" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        @foreach($courseTypes as $ct)
                            <option value="{{ $ct->id }}" {{ request('course_type_id')==$ct->id ? 'selected':'' }}>{{ $ct->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Course</label>
                    <select name="course_id" id="fcCourse" class="form-select form-select-sm">
                        <option value="">All Courses</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" {{ request('course_id')==$c->id ? 'selected':'' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Stream</label>
                    <select name="stream_id" id="fcStream" class="form-select form-select-sm">
                        <option value="">All Streams</option>
                        @foreach($streams as $st)
                            <option value="{{ $st->id }}" {{ request('stream_id')==$st->id ? 'selected':'' }}>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           value="{{ request('search') }}" placeholder="Invoice, Name, UID...">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="{{ route($feeCollectionRoute) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-lg"></i> Reset
                    </a>
                </div>
            </div>
            <input type="hidden" name="per_page" value="{{ $perPage }}">
        </form>
    </div>
</div>

{{-- Invoices Table --}}
<div class="card border-0 shadow-sm" id="reportTable">
    <div class="card-body p-0">
        @if($invoices->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                No invoices found. Try adjusting the filters.
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:12px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Invoice No</th>
                        <th>Date</th>
                        <th class="text-center">Sem</th>
                        <th>Student</th>
                        <th>Father / Mother</th>
                        <th>Roll No</th>
                        <th>UIN</th>
                        <th>Course</th>
                        <th>Fee Items</th>
                        <th class="text-center">Mode</th>
                        <th>Bank / Ref</th>
                        <th>Collected By</th>
                        <th class="text-end" style="white-space:nowrap;">Collection</th>
                        <th class="text-end text-danger" style="white-space:nowrap;">Fine</th>
                        <th class="text-end" style="color:#7c3aed;white-space:nowrap;">Discount</th>
                        <th class="text-end text-success pe-2" style="white-space:nowrap;">Total Amt</th>
                        <th class="text-end text-danger pe-3" style="white-space:nowrap;">Due</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $i => $inv)
                    @php
                        $invRef = $inv->transaction_ref ?: ($inv->remarks ?: null);
                        $bankLabel = $inv->bankAccount?->display_label ?: ($inv->bank_name ?: null);
                    @endphp
                    <tr>
                        <td class="ps-3 text-muted">{{ $invoices->firstItem() + $i }}</td>
                        <td>
                            <a href="{{ route($receiptRoute, ['student' => $inv->student_id, 'invoice' => $inv->id]) }}"
                               target="_blank" class="fw-semibold text-primary text-decoration-none">
                                {{ $inv->invoice_no }}
                            </a>
                        </td>
                        <td class="text-muted" style="white-space:nowrap;">
                            {{ $inv->payment_date?->format('d M Y') }}
                            <span class="text-muted" style="font-size:10px;display:block;">{{ $inv->created_at?->setTimezone('Asia/Kolkata')->format('h:i A') }}</span>
                        </td>
                        <td class="text-center" style="white-space:nowrap;">
                            @if($inv->semester)
                                <span class="badge bg-primary bg-opacity-10 text-primary border" style="font-size:10px;">S{{ $inv->semester }}</span>
                            @endif
                            @if($inv->student?->coursePart)
                                <span class="text-muted" style="font-size:10px;display:block;">{{ $inv->student->coursePart->year_label }}</span>
                            @endif
                            @if(!$inv->semester && !$inv->student?->coursePart)
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $inv->student->name ?? '—' }}</div>
                            <div class="text-muted" style="font-size:10px;">{{ $inv->student->student_uid ?? '' }}</div>
                        </td>
                        <td style="max-width:110px;">
                            <div class="text-muted" style="font-size:10px;">F: {{ $inv->student->father_name ?: '—' }}</div>
                            <div class="text-muted" style="font-size:10px;">M: {{ $inv->student->mother_name ?: '—' }}</div>
                        </td>
                        <td style="font-size:11px;">{{ $inv->student->roll_no ?: '—' }}</td>
                        <td style="font-size:11px;">{{ $inv->student->uin_no ?: '—' }}</td>
                        <td class="text-muted" style="font-size:11px;">
                            {{ $inv->student?->stream?->course?->name ?? '—' }}
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($inv->items->take(3) as $item)
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary fw-normal" style="font-size:10px;">
                                        {{ $item->fee_name }}
                                    </span>
                                @endforeach
                                @if($inv->items->count() > 3)
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary fw-normal" style="font-size:10px;">
                                        +{{ $inv->items->count() - 3 }} more
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge
                                {{ $inv->payment_mode=='cash' ? 'bg-success' : '' }}
                                {{ $inv->payment_mode=='upi' ? 'bg-primary' : '' }}
                                {{ $inv->payment_mode=='online' ? 'bg-info text-dark' : '' }}
                                {{ $inv->payment_mode=='cheque' ? 'bg-warning text-dark' : '' }}
                                {{ $inv->payment_mode=='dd' ? 'bg-secondary' : '' }}
                                {{ $inv->payment_mode=='neft' ? 'bg-dark' : '' }}
                                {{ $inv->payment_mode=='rtgs' ? 'bg-danger' : '' }}
                                bg-opacity-75" style="font-size:10px;">
                                {{ strtoupper($inv->payment_mode) }}
                            </span>
                        </td>
                        <td style="max-width:130px;">
                            @if($bankLabel)
                                <div style="font-size:10px;" class="fw-semibold text-info">{{ $bankLabel }}</div>
                            @endif
                            @if($invRef)
                                <div class="text-muted" style="font-size:10px;" title="{{ $invRef }}">
                                    {{ strlen($invRef) > 18 ? substr($invRef, 0, 18).'…' : $invRef }}
                                </div>
                            @endif
                            @if(!$bankLabel && !$invRef)
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td style="font-size:11px;">{{ $inv->collected_by ?: '—' }}</td>
                        {{-- Collection (cash paid) --}}
                        <td class="text-end fw-bold text-success">
                            ₹ {{ number_format($inv->paid_amount) }}
                        </td>
                        {{-- Fine --}}
                        @php $invFine = $inv->items->sum('fine'); @endphp
                        <td class="text-end">
                            @if($invFine > 0)
                                <span class="fw-semibold small text-danger">₹{{ number_format($invFine) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        {{-- Discount --}}
                        <td class="text-end">
                            @if(($inv->discount ?? 0) > 0)
                                <span class="fw-semibold small" style="color:#7c3aed;">-₹{{ number_format($inv->discount) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        {{-- Total Amount (cash + discount) --}}
                        <td class="text-end fw-bold pe-2">
                            ₹ {{ number_format($inv->paid_amount + ($inv->discount ?? 0)) }}
                        </td>
                        {{-- Due --}}
                        @php $invTotalCharged = $inv->items->sum(fn($item) => $item->total_fee ?? $item->amount); $invDue = max(0, $invTotalCharged - $inv->paid_amount - ($inv->discount ?? 0)); @endphp
                        <td class="text-end pe-3">
                            @if($invDue > 0)
                                <span class="fw-semibold small text-danger">₹{{ number_format($invDue) }}</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-semibold">
                    @php
                        $pageFineTotal = $invoices->sum(fn($inv) => $inv->items->sum('fine'));
                        $pageDueTotal  = $invoices->sum(fn($inv) => max(0, $inv->items->sum(fn($item) => $item->total_fee ?? $item->amount) - $inv->paid_amount - ($inv->discount ?? 0)));
                    @endphp
                    <tr>
                        <td colspan="13" class="ps-3 text-muted small">This page total ({{ $invoices->count() }} invoices)</td>
                        <td class="text-end text-success" style="white-space:nowrap;">₹ {{ number_format($invoices->sum('paid_amount')) }}</td>
                        <td class="text-end text-danger" style="white-space:nowrap;">{{ $pageFineTotal > 0 ? '₹ '.number_format($pageFineTotal) : '—' }}</td>
                        <td class="text-end" style="color:#7c3aed;white-space:nowrap;">{{ $invoices->sum('discount') > 0 ? '-₹ '.number_format($invoices->sum('discount')) : '—' }}</td>
                        <td class="text-end text-success pe-2 fw-bold" style="white-space:nowrap;">₹ {{ number_format($invoices->sum('paid_amount') + $invoices->sum('discount')) }}</td>
                        <td class="text-end pe-3 {{ $pageDueTotal > 0 ? 'text-danger fw-bold' : 'text-muted' }}" style="white-space:nowrap;">{{ $pageDueTotal > 0 ? '₹ '.number_format($pageDueTotal) : '—' }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="px-3 pb-3">
            @include('institute.components.pagination', ['paginator' => $invoices, 'perPage' => $perPage])
        </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
function setDateRange(range) {
    const today = new Date();
    const fmt = d => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };
    let from, to = fmt(today);
    if (range === 'today') {
        from = to;
    } else if (range === 'yesterday') {
        const y = new Date(today); y.setDate(y.getDate() - 1);
        from = to = fmt(y);
    } else if (range === 'month') {
        from = fmt(new Date(today.getFullYear(), today.getMonth(), 1));
    } else if (range === 'lastmonth') {
        from = fmt(new Date(today.getFullYear(), today.getMonth() - 1, 1));
        to   = fmt(new Date(today.getFullYear(), today.getMonth(), 0));
    }
    document.querySelector('[name="date_from"]').value = from;
    document.querySelector('[name="date_to"]').value   = to;
    document.getElementById('filterForm').submit();
}

const MODE_BANK_DATA = @json($modeBankJson ?? []);

@php
    $bankDetailJson = [];
    foreach ($bankDetailWise ?? [] as $bankLbl => $rows) {
        $bankDetailJson[$bankLbl] = $rows->map(fn($r) => [
            'collector' => $r->collector,
            'mode'      => strtoupper($r->payment_mode ?? ''),
            'cnt'       => $r->cnt,
            'total'     => (float) $r->total,
        ])->values()->toArray();
    }
@endphp
const BANK_DETAIL_DATA = @json($bankDetailJson);
const FC_COURSES_ALL   = @json($courses->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'type_id' => $c->course_type_id ?? null]));
const FC_STREAMS_ROUTE = '{{ route($isStaff ? "staff.reports.streams" : "reports.streams") }}';

function showBankDetail(bankLabel) {
    const rows = BANK_DETAIL_DATA[bankLabel] || [];
    document.getElementById('bankDetailTitle').textContent = bankLabel + ' — Collector & Mode Breakdown';

    let html = '<div class="table-responsive"><table class="table table-sm mb-0" style="font-size:13px;">'
             + '<thead class="table-light"><tr>'
             + '<th class="ps-3">Collected By</th>'
             + '<th class="text-center">Mode</th>'
             + '<th class="text-center">Count</th>'
             + '<th class="text-end pe-3">Amount (₹)</th>'
             + '</tr></thead><tbody>';

    let grandTotal = 0, grandCnt = 0;
    if (rows.length === 0) {
        html += '<tr><td colspan="4" class="text-center text-muted py-3">No data</td></tr>';
    } else {
        rows.forEach(r => {
            grandTotal += r.total || 0;
            grandCnt   += r.cnt   || 0;
            const modeColors = {CASH:'bg-success',UPI:'bg-primary',ONLINE:'bg-info text-dark',CHEQUE:'bg-warning text-dark',DD:'bg-secondary',NEFT:'bg-dark',RTGS:'bg-danger'};
            const badgeCls = modeColors[r.mode] || 'bg-secondary';
            html += `<tr>
                <td class="ps-3 fw-semibold">${r.collector}</td>
                <td class="text-center"><span class="badge ${badgeCls} bg-opacity-75" style="font-size:10px;">${r.mode}</span></td>
                <td class="text-center">${r.cnt}</td>
                <td class="text-end pe-3 fw-semibold text-success">₹ ${parseFloat(r.total).toLocaleString('en-IN',{maximumFractionDigits:0})}</td>
            </tr>`;
        });
    }

    const fmt = n => n.toLocaleString('en-IN', {maximumFractionDigits:0});
    html += `</tbody><tfoot class="table-dark"><tr>
        <td class="ps-3 fw-bold" colspan="2">Total</td>
        <td class="text-center fw-bold">${grandCnt}</td>
        <td class="text-end pe-3 fw-bold">₹ ${fmt(grandTotal)}</td>
    </tr></tfoot></table></div>`;

    document.getElementById('bankDetailBody').innerHTML = html;
    new bootstrap.Modal(document.getElementById('bankDetailModal')).show();
}

// Course Type → filter courses
document.getElementById('fcCourseType')?.addEventListener('change', function() {
    const typeId = this.value;
    const sel = document.getElementById('fcCourse');
    sel.innerHTML = '<option value="">All Courses</option>';
    FC_COURSES_ALL.filter(c => !typeId || String(c.type_id) === typeId).forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id; opt.textContent = c.name;
        sel.appendChild(opt);
    });
    document.getElementById('fcStream').innerHTML = '<option value="">All Streams</option>';
});

// Course → fetch streams
document.getElementById('fcCourse')?.addEventListener('change', function() {
    const courseId = this.value;
    const streamSel = document.getElementById('fcStream');
    if (!courseId) { streamSel.innerHTML = '<option value="">All Streams</option>'; return; }
    streamSel.innerHTML = '<option value="">Loading...</option>';
    fetch(FC_STREAMS_ROUTE + '?course_id=' + courseId)
        .then(r => r.json())
        .then(data => {
            streamSel.innerHTML = '<option value="">All Streams</option>';
            data.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id; opt.textContent = s.name;
                streamSel.appendChild(opt);
            });
        });
});

function showModeDetail(mode) {
    const rows = MODE_BANK_DATA[mode] || [];
    const title = document.getElementById('modeDetailTitle');
    title.textContent = mode.toUpperCase() + ' — Collector & Bank Breakdown';

    let html = '<div class="table-responsive"><table class="table table-sm mb-0" style="font-size:13px;"><thead class="table-light"><tr>'
             + '<th class="ps-3">Collected By</th><th>Bank / Account</th><th class="text-center">Count</th><th class="text-end pe-3">Amount (₹)</th>'
             + '</tr></thead><tbody>';

    let grandTotal = 0, grandCnt = 0;
    if (rows.length === 0) {
        html += '<tr><td colspan="4" class="text-center text-muted py-3">No data</td></tr>';
    } else {
        rows.forEach(r => {
            grandTotal += parseFloat(r.total) || 0;
            grandCnt   += parseInt(r.cnt) || 0;
            html += `<tr>
                <td class="ps-3 fw-semibold">${r.collector}</td>
                <td class="text-muted">${r.bank || '—'}</td>
                <td class="text-center">${r.cnt}</td>
                <td class="text-end pe-3 fw-semibold text-success">₹ ${grandTotal > 0 ? parseFloat(r.total).toLocaleString('en-IN') : '—'}</td>
            </tr>`;
        });
    }

    const fmt = n => n.toLocaleString('en-IN', {maximumFractionDigits:0});
    html += `</tbody><tfoot class="table-dark"><tr>
        <td class="ps-3 fw-bold" colspan="2">Total</td>
        <td class="text-center fw-bold">${grandCnt}</td>
        <td class="text-end pe-3 fw-bold">₹ ${fmt(grandTotal)}</td>
    </tr></tfoot></table></div>`;

    document.getElementById('modeDetailBody').innerHTML = html;
    const modal = new bootstrap.Modal(document.getElementById('modeDetailModal'));
    modal.show();
}

function printReport() {
    const instituteName = '{{ auth()->user()?->institute?->name ?? (auth()->guard("staff")->user()?->institute?->name ?? "Institute") }}';
    const session      = '{{ $sessionObj?->name ?? "" }}';
    const dateFrom     = '{{ \Carbon\Carbon::parse($dateFrom)->format("d M Y") }}';
    const dateTo       = '{{ \Carbon\Carbon::parse($dateTo)->format("d M Y") }}';
    const printDate    = new Date().toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
    const printTime    = new Date().toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit'});

    // Collect filter info
    const filters = [];
    const modeEl = document.querySelector('[name="payment_mode"]');
    const collEl = document.querySelector('[name="collected_by"]');
    const semEl  = document.querySelector('[name="semester"]');
    if (modeEl?.value)  filters.push('Mode: ' + modeEl.options[modeEl.selectedIndex].text);
    if (collEl?.value)  filters.push('Collected By: ' + collEl.value);
    if (semEl?.value)   filters.push('Sem: ' + semEl.options[semEl.selectedIndex].text);

    // Clone table and strip action buttons / links color for print
    const tableEl = document.querySelector('#reportTable table');
    if (!tableEl) return;
    const tableClone = tableEl.cloneNode(true);
    // Convert invoice links to plain text
    tableClone.querySelectorAll('a').forEach(a => {
        const span = document.createElement('span');
        span.innerHTML = a.innerHTML;
        span.style.fontWeight = '600';
        a.replaceWith(span);
    });

    const win = window.open('', '_blank');
    win.document.write(`<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Fee Collection Report</title>
<style>
@page { size: A4 landscape; margin: 8mm 18mm; }
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 9px; color: #1e293b; background: white; }

.print-header { display: flex; justify-content: space-between; align-items: flex-start;
    border-bottom: 2px solid #1e293b; padding-bottom: 5px; margin-bottom: 6px; }
.print-header .inst-name { font-size: 14px; font-weight: 700; color: #1e293b; }
.print-header .report-title { font-size: 11px; font-weight: 600; color: #1d4ed8; margin-top: 1px; }
.print-header .meta { text-align: right; font-size: 9px; color: #475569; line-height: 1.5; }
.filter-bar { font-size: 8.5px; color: #64748b; margin-bottom: 5px; padding: 3px 6px;
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 3px; }

table { width: 100%; border-collapse: collapse; font-size: 8.5px; }
thead th { background: #1e293b; color: white; padding: 4px 4px; text-align: left;
    font-size: 8px; font-weight: 600; white-space: nowrap; }
thead th.r { text-align: right; }
thead th.c { text-align: center; }
tbody tr:nth-child(even) { background: #f8fafc; }
tbody td { padding: 3px 4px; border-bottom: 1px solid #e2e8f0; vertical-align: top; font-size: 8.5px; }
tbody td.r { text-align: right; }
tbody td.c { text-align: center; }
tfoot td { padding: 4px 4px; font-weight: 700; background: #f1f5f9;
    border-top: 2px solid #1e293b; font-size: 9px; }
tfoot td.r { text-align: right; }
.badge-cash  { background:#dcfce7; color:#166534; padding:1px 4px; border-radius:3px; font-weight:700; }
.badge-upi   { background:#dbeafe; color:#1d4ed8; padding:1px 4px; border-radius:3px; font-weight:700; }
.badge-online{ background:#cffafe; color:#0e7490; padding:1px 4px; border-radius:3px; font-weight:700; }
.badge-cheque{ background:#fef9c3; color:#854d0e; padding:1px 4px; border-radius:3px; font-weight:700; }
.badge-dd    { background:#f1f5f9; color:#475569; padding:1px 4px; border-radius:3px; font-weight:700; }
.badge-neft  { background:#1e293b; color:#ffffff; padding:1px 4px; border-radius:3px; font-weight:700; }
.badge-rtgs  { background:#fee2e2; color:#b91c1c; padding:1px 4px; border-radius:3px; font-weight:700; }
.t-success { color: #16a34a; font-weight: 600; }
.t-danger  { color: #dc2626; font-weight: 600; }
.t-purple  { color: #7c3aed; font-weight: 600; }
.t-muted   { color: #94a3b8; }
.footer { margin-top: 8px; display: flex; justify-content: space-between;
    font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 4px; }
</style>
</head>
<body>

<div class="print-header">
    <div>
        <div class="inst-name">${instituteName}</div>
        <div class="report-title">Fee Collection Report &mdash; ${session}</div>
    </div>
    <div class="meta">
        <div><strong>Date Range:</strong> ${dateFrom} &mdash; ${dateTo}</div>
        ${filters.length ? '<div><strong>Filters:</strong> ' + filters.join(' | ') + '</div>' : ''}
        <div>Printed: ${printDate} ${printTime}</div>
    </div>
</div>

<table>
<thead>
<tr>
    <th style="width:14px;">#</th>
    <th style="width:72px;">Invoice No</th>
    <th style="width:48px;">Date & Time</th>
    <th class="c" style="width:36px;">Year / Sem</th>
    <th style="width:65px;">Student</th>
    <th style="width:48px;">Father</th>
    <th style="width:36px;">Roll No</th>
    <th style="width:38px;">UIN</th>
    <th style="width:44px;">Course</th>
    <th style="width:62px;">Fee Items</th>
    <th class="c" style="width:28px;">Mode</th>
    <th style="width:48px;">Bank / Ref</th>
    <th style="width:40px;">Collected By</th>
    <th class="r" style="width:34px;">Collection</th>
    <th class="r" style="width:26px;">Fine</th>
    <th class="r" style="width:30px;">Discount</th>
    <th class="r" style="width:34px;">Total</th>
    <th class="r" style="width:26px;">Due</th>
</tr>
</thead>
<tbody id="printTbody"></tbody>
<tfoot id="printTfoot"></tfoot>
</table>

<div class="footer">
    <span>Fee Collection Report &mdash; ${instituteName} &mdash; Session: ${session}</span>
    <span>Generated: ${printDate} ${printTime}</span>
</div>

<script>
// Build rows from page data
const rows = ${JSON.stringify(getTableData())};
const tbody = document.getElementById('printTbody');
const tfoot = document.getElementById('printTfoot');

let totColl = 0, totFine = 0, totDisc = 0, totAmt = 0, totDue = 0;
rows.forEach((r, i) => {
    totColl += r.coll; totFine += r.fine; totDisc += r.disc; totAmt += r.amt; totDue += r.due;
    const modeClass = {cash:'badge-cash',upi:'badge-upi',online:'badge-online',cheque:'badge-cheque',dd:'badge-dd',neft:'badge-neft',rtgs:'badge-rtgs'}[r.mode] || '';
    tbody.innerHTML += \`<tr>
        <td class="t-muted">\${i+1}</td>
        <td style="font-weight:600;">\${r.invoice}</td>
        <td>\${r.date}<br><span class="t-muted">\${r.time}</span></td>
        <td class="c">\${r.year ? '<span style="font-size:7.5px;color:#64748b;">'+r.year+'</span><br>' : ''}<span style="background:#eff6ff;color:#1d4ed8;padding:1px 3px;border-radius:2px;font-weight:600;">\${r.sem}</span></td>
        <td><span style="font-weight:600;">\${r.student}</span></td>
        <td class="t-muted">\${r.father}</td>
        <td class="t-muted">\${r.rollno}</td>
        <td class="t-muted">\${r.uin}</td>
        <td class="t-muted">\${r.course}</td>
        <td>\${r.feeItems}</td>
        <td class="c"><span class="\${modeClass}">\${r.mode.toUpperCase()}</span></td>
        <td class="t-muted" style="font-size:8px;">\${r.bank}\${r.ref ? '<br>'+r.ref : ''}</td>
        <td>\${r.collBy}</td>
        <td class="r t-success">₹\${r.coll.toLocaleString('en-IN')}</td>
        <td class="r t-danger">\${r.fine > 0 ? '₹'+r.fine.toLocaleString('en-IN') : '—'}</td>
        <td class="r t-purple">\${r.disc > 0 ? '-₹'+r.disc.toLocaleString('en-IN') : '—'}</td>
        <td class="r" style="font-weight:700;">₹\${r.amt.toLocaleString('en-IN')}</td>
        <td class="r \${r.due > 0 ? 't-danger' : 't-muted'}">\${r.due > 0 ? '₹'+r.due.toLocaleString('en-IN') : '—'}</td>
    </tr>\`;
});

const fmt = n => n.toLocaleString('en-IN', {maximumFractionDigits:0});
tfoot.innerHTML = \`<tr>
    <td colspan="13">Page Total (\${rows.length} invoices)</td>
    <td class="r t-success">₹\${fmt(totColl)}</td>
    <td class="r t-danger">\${totFine > 0 ? '₹'+fmt(totFine) : '—'}</td>
    <td class="r t-purple">\${totDisc > 0 ? '-₹'+fmt(totDisc) : '—'}</td>
    <td class="r" style="font-weight:700;">₹\${fmt(totAmt)}</td>
    <td class="r t-danger">\${totDue > 0 ? '₹'+fmt(totDue) : '—'}</td>
</tr>\`;

window.onload = () => window.print();
<\/script>
</body>
</html>`);

    win.document.close();
}

function getTableData() {
    const rows = [];
    document.querySelectorAll('#reportTable tbody tr').forEach(tr => {
        const tds = tr.querySelectorAll('td');
        if (tds.length < 16) return;

        const getT = (el) => el ? el.innerText.trim() : '';
        const getN = (s) => parseFloat(s.replace(/[^0-9.]/g,'')) || 0;

        // Extract badge text for mode (col 10 after adding Roll No + UIN cols)
        const modeBadge = tds[10].querySelector('.badge');
        const mode = modeBadge ? modeBadge.innerText.trim().toLowerCase() : getT(tds[10]).toLowerCase();

        // Bank cell (col 11)
        const bankDivs = tds[11].querySelectorAll('div,span');
        const bank = bankDivs[0] ? bankDivs[0].innerText.trim() : getT(tds[11]);
        const ref  = bankDivs[1] ? bankDivs[1].innerText.trim() : '';

        // Date & time from date cell
        const dateSpans = tds[2].querySelectorAll('span');
        const date = tds[2].firstChild ? tds[2].firstChild.textContent.trim() : getT(tds[2]);
        const time = dateSpans[0] ? dateSpans[0].innerText.trim() : '';

        // Sem cell: badge + year
        const semBadge = tds[3].querySelector('.badge');
        const sem  = semBadge ? semBadge.innerText.trim() : getT(tds[3]);
        const yearSpan = tds[3].querySelector('span:last-child');
        const year = yearSpan && yearSpan !== semBadge ? yearSpan.innerText.trim() : '';

        // Student (name only, col 4)
        const studentDivs = tds[4].querySelectorAll('div');
        const student = studentDivs[0] ? studentDivs[0].innerText.trim() : getT(tds[4]);

        // Fee items — join badge texts (col 9)
        const feeBadges = tds[9].querySelectorAll('.badge');
        const feeItems = feeBadges.length
            ? Array.from(feeBadges).map(b => b.innerText.trim()).filter(t => !t.startsWith('+')).join(', ')
            : getT(tds[9]);

        rows.push({
            invoice:  tds[1].querySelector('a,span') ? (tds[1].querySelector('a,span').innerText.trim()) : getT(tds[1]),
            date, time, year, sem,
            student,
            father:   getT(tds[5]).replace(/^F:\s*/,'').replace(/\nM:.*$/s,'').trim() || '—',
            rollno:   getT(tds[6]),
            uin:      getT(tds[7]),
            course:   getT(tds[8]),
            feeItems,
            mode,
            bank:  bank === '—' ? '' : bank,
            ref:   ref  === '—' ? '' : ref,
            collBy: getT(tds[12]),
            coll:  getN(getT(tds[13])),
            fine:  getN(getT(tds[14])),
            disc:  getN(getT(tds[15])),
            amt:   getN(getT(tds[16])),
            due:   getN(getT(tds[17])),
        });
    });
    return rows;
}
</script>
@endpush