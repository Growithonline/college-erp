@php
    $isStaff = auth()->guard('staff')->check();
    $layout = $isStaff ? 'staff.layout' : 'institute.layout';
    $receiptRoute = $isStaff ? 'staff.fee.receipt' : 'fee.receipt';
@endphp
@extends($layout)
@section('title','Daily / Monthly Collection')
@section('breadcrumb','Reports / Daily Collection')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Daily / Monthly Collection</h4>
        <small class="text-muted">Date range wise collection summary</small>
    </div>
    <div class="d-flex gap-2">
        <button onclick="printReport()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <a href="{{ request()->fullUrlWithQuery(['export'=>'csv']) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-download me-1"></i> Export CSV
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-3 align-items-end" id="filterForm">
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    @foreach($sessions as $s)
                    <option value="{{ $s->id }}" {{ $s->id == $sessionId ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Date From</label>
                <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Date To</label>
                <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Group By</label>
                <select name="group_by" id="group_by" class="form-select form-select-sm">
                    <option value="day"   {{ $groupBy=='day'   ? 'selected':'' }}>Day Wise</option>
                    <option value="month" {{ $groupBy=='month' ? 'selected':'' }}>Month Wise</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search me-1"></i> Apply
                </button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <small id="rangeWarning" class="text-danger d-none">
                    <i class="bi bi-exclamation-triangle me-1"></i>Max 3 months for monthly view
                </small>
            </div>
        </form>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4" id="summaryCards">
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">Total Collected</div>
                <div class="fw-bold fs-5 text-primary">₹ {{ number_format($totalCollected, 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">Total Discount</div>
                <div class="fw-bold fs-5 text-warning">₹ {{ number_format($totalDiscount, 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100" style="border-left:3px solid #f59e0b !important;">
        <div class="card-body py-3">
                <div class="small text-muted mb-1">Total Fine</div>
                <div class="fw-bold fs-5" style="color:#f59e0b;">₹ {{ number_format($totalFine, 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">Total Payable</div>
                <div class="fw-bold fs-5 text-success">₹ {{ number_format($totalCollected + $totalDiscount, 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">Total Invoices</div>
                <div class="fw-bold fs-5">{{ number_format($totalInvoices) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">Unique Students</div>
                <div class="fw-bold fs-5 text-info">{{ number_format($totalStudents) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Main table --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold small">
                    <i class="bi bi-calendar3 me-2 text-primary"></i>
                    {{ $groupBy === 'month' ? 'Month' : 'Day' }} Wise Collection
                </h6>
                <span class="text-muted" style="font-size:10px;"><i class="bi bi-hand-index me-1"></i>Click row for details</span>
            </div>
            <div class="table-responsive" id="mainCollectionTable">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">{{ $groupBy === 'month' ? 'Month' : 'Date' }}</th>
                            <th class="text-end">Collection</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end" style="color:#f59e0b;">Fine</th>
                            <th class="text-end">Payable</th>
                            <th class="text-end">Invoices</th>
                            <th class="text-end pe-3">Students</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($grouped as $row)
                        @php $rowFine = (float)($fineGrouped[$row->period] ?? 0); @endphp
                        <tr style="cursor:pointer;" onclick="showPeriodDetail('{{ $row->period }}', '{{ $row->label }}')">
                            <td class="ps-3 fw-semibold small text-primary">{{ $row->label }}</td>
                            <td class="text-end small text-primary fw-semibold">₹ {{ number_format($row->collected, 0) }}</td>
                            <td class="text-end small text-warning">{{ $row->discount > 0 ? '₹ '.number_format($row->discount, 0) : '—' }}</td>
                            <td class="text-end small fw-semibold" style="color:#f59e0b;">{{ $rowFine > 0 ? '₹ '.number_format($rowFine, 0) : '—' }}</td>
                            <td class="text-end small text-success">₹ {{ number_format($row->collected + $row->discount, 0) }}</td>
                            <td class="text-end small">{{ $row->invoices }}</td>
                            <td class="text-end pe-3 small">{{ $row->students }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4 small">
                                <i class="bi bi-inbox d-block fs-3 mb-2"></i>
                                Is date range mein koi collection nahi
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($grouped->count() > 0)
                    <tfoot class="table-dark">
                        <tr>
                            <td class="ps-3 fw-bold">Total</td>
                            <td class="text-end fw-bold">₹ {{ number_format($totalCollected, 0) }}</td>
                            <td class="text-end fw-bold">₹ {{ number_format($totalDiscount, 0) }}</td>
                            <td class="text-end fw-bold" style="color:#fcd34d;">{{ $totalFine > 0 ? '₹ '.number_format($totalFine, 0) : '—' }}</td>
                            <td class="text-end fw-bold">₹ {{ number_format($totalCollected + $totalDiscount, 0) }}</td>
                            <td class="text-end fw-bold">{{ $totalInvoices }}</td>
                            <td class="text-end pe-3 fw-bold">{{ $totalStudents }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Mode wise --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold small">
                    <i class="bi bi-pie-chart me-2 text-success"></i>Payment Mode Wise
                </h6>
                <span class="text-muted" style="font-size:10px;"><i class="bi bi-hand-index me-1"></i>Click for bank details</span>
            </div>
            <div class="card-body p-0" id="modeWiseTable">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Mode</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end pe-3">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $modeLabels = ['cash'=>'💵 Cash','upi'=>'📱 UPI','online'=>'🌐 Online',
                                          'cheque'=>'🏦 Cheque','dd'=>'📄 DD','neft'=>'🔁 NEFT','rtgs'=>'🔄 RTGS'];
                        @endphp
                        @forelse($modeWise as $m)
                        <tr style="cursor:pointer;" onclick="showModeDetail('{{ $m->payment_mode }}')">
                            <td class="ps-3 small">{{ $modeLabels[$m->payment_mode] ?? strtoupper($m->payment_mode) }}</td>
                            <td class="text-end small fw-semibold">₹ {{ number_format($m->total, 0) }}</td>
                            <td class="text-end pe-3 small text-muted">{{ $m->cnt }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted small py-3">No data</td></tr>
                        @endforelse
                    </tbody>
                    @if($modeWise->count() > 0)
                    <tfoot class="table-light fw-semibold">
                        <tr>
                            <td class="ps-3 small">Total</td>
                            <td class="text-end small text-success">₹ {{ number_format($modeWise->sum('total'), 0) }}</td>
                            <td class="text-end pe-3 small">{{ $modeWise->sum('cnt') }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Period Detail Modal --}}
<div class="modal fade" id="periodDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold" id="periodDetailTitle">Collection Detail</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="periodDetailBody"></div>
            </div>
        </div>
    </div>
</div>

{{-- Mode Bank Detail Modal --}}
<div class="modal fade" id="modeBankModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold" id="modeBankTitle">Mode Breakdown</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="modeBankBody"></div>
            </div>
        </div>
    </div>
</div>

@php
    $periodInvoicesJson = [];
    foreach ($periodInvoices as $period => $rows) {
        $periodInvoicesJson[$period] = $rows;
    }
    $modeBankJson = [];
    foreach ($modeBankWise as $mode => $rows) {
        $modeBankJson[$mode] = $rows->map(fn($r) => [
            'bank'      => $r->bank_label ?: '—',
            'collector' => $r->collector,
            'cnt'       => $r->cnt,
            'total'     => $r->total,
        ])->values()->toArray();
    }

    $printGrouped = $grouped->map(fn($row) => [
        'label'     => $row->label,
        'collected' => (float) $row->collected,
        'discount'  => (float) $row->discount,
        'fine'      => (float) ($fineGrouped[$row->period] ?? 0),
        'invoices'  => (int) $row->invoices,
        'students'  => (int) $row->students,
    ])->values();

    $printModeWise = $modeWise->map(fn($m) => [
        'mode'  => strtoupper($m->payment_mode),
        'total' => (float) $m->total,
        'cnt'   => (int) $m->cnt,
    ])->values();

    $printInstitute = auth()->guard('staff')->check()
        ? auth()->guard('staff')->user()->institute
        : auth()->user()->institute;

    $printLogoUrl = null;
    if (!empty($printInstitute?->image)) {
        if (file_exists(public_path('storage/' . $printInstitute->image))) {
            $printLogoUrl = asset('storage/' . $printInstitute->image);
        } elseif (file_exists(public_path($printInstitute->image))) {
            $printLogoUrl = asset($printInstitute->image);
        }
    }
    $printInitials = strtoupper(substr($printInstitute?->short_name ?: ($printInstitute?->name ?: 'IN'), 0, 2));
@endphp

@push('scripts')
<script>
const PERIOD_DATA   = @json($periodInvoicesJson);
const MODE_BANK     = @json($modeBankJson);
const MODE_LABELS   = {cash:'💵 Cash',upi:'📱 UPI',online:'🌐 Online',cheque:'🏦 Cheque',dd:'📄 DD',neft:'🔁 NEFT',rtgs:'🔄 RTGS'};
const fmt = n => parseFloat(n).toLocaleString('en-IN',{maximumFractionDigits:0});

function showPeriodDetail(period, label) {
    const rows = PERIOD_DATA[period] || [];
    document.getElementById('periodDetailTitle').textContent = label + ' — Invoice Detail';
    let html = '<div class="table-responsive"><table class="table table-sm mb-0" style="font-size:12px;"><thead class="table-light"><tr>'
             + '<th class="ps-3">#</th><th>Invoice</th><th>Student</th><th class="text-center">Mode</th>'
             + '<th>Bank / Ref</th><th>Collected By</th>'
             + '<th class="text-end">Amount (₹)</th><th class="text-end">Disc (₹)</th><th class="text-end pe-3">Payable (₹)</th>'
             + '</tr></thead><tbody>';
    let totAmt = 0, totDisc = 0;
    if (!rows.length) {
        html += '<tr><td colspan="9" class="text-center text-muted py-3">No invoices</td></tr>';
    } else {
        rows.forEach((r, i) => {
            totAmt  += r.amount;
            totDisc += r.disc;
            const bankRef = [r.bank, r.ref].filter(Boolean).join(' / ') || '—';
            html += `<tr>
                <td class="ps-3 text-muted">${i+1}</td>
                <td class="fw-semibold text-primary">${r.no}</td>
                <td>${r.student}<br><small class="text-muted">${r.uid}</small></td>
                <td class="text-center"><span class="badge bg-secondary bg-opacity-75" style="font-size:10px;">${r.mode.toUpperCase()}</span></td>
                <td class="text-muted small">${bankRef}</td>
                <td class="small">${r.by || '—'}</td>
                <td class="text-end fw-semibold text-success">₹ ${fmt(r.amount)}</td>
                <td class="text-end text-warning">${r.disc > 0 ? '-₹'+fmt(r.disc) : '—'}</td>
                <td class="text-end fw-bold pe-3">₹ ${fmt(r.amount+r.disc)}</td>
            </tr>`;
        });
    }
    html += `</tbody><tfoot class="table-dark"><tr>
        <td class="ps-3 fw-bold" colspan="6">Total (${rows.length} invoices)</td>
        <td class="text-end fw-bold">₹ ${fmt(totAmt)}</td>
        <td class="text-end fw-bold">${totDisc > 0 ? '-₹'+fmt(totDisc) : '—'}</td>
        <td class="text-end fw-bold pe-3">₹ ${fmt(totAmt+totDisc)}</td>
    </tr></tfoot></table></div>`;
    document.getElementById('periodDetailBody').innerHTML = html;
    new bootstrap.Modal(document.getElementById('periodDetailModal')).show();
}

function showModeDetail(mode) {
    const rows = MODE_BANK[mode] || [];
    document.getElementById('modeBankTitle').textContent = (MODE_LABELS[mode] || mode.toUpperCase()) + ' — Bank & Collector Breakdown';
    let html = '<div class="table-responsive"><table class="table table-sm mb-0" style="font-size:13px;"><thead class="table-light"><tr>'
             + '<th class="ps-3">Collected By</th><th>Bank / Account</th><th class="text-center">Count</th><th class="text-end pe-3">Amount (₹)</th>'
             + '</tr></thead><tbody>';
    let grandTotal = 0, grandCnt = 0;
    if (!rows.length) {
        html += '<tr><td colspan="4" class="text-center text-muted py-3">No data</td></tr>';
    } else {
        rows.forEach(r => {
            grandTotal += parseFloat(r.total) || 0;
            grandCnt   += parseInt(r.cnt) || 0;
            html += `<tr>
                <td class="ps-3 fw-semibold">${r.collector}</td>
                <td class="text-muted">${r.bank === '—' ? '—' : r.bank}</td>
                <td class="text-center">${r.cnt}</td>
                <td class="text-end pe-3 fw-semibold text-success">₹ ${fmt(r.total)}</td>
            </tr>`;
        });
    }
    html += `</tbody><tfoot class="table-dark"><tr>
        <td class="ps-3 fw-bold" colspan="2">Total</td>
        <td class="text-center fw-bold">${grandCnt}</td>
        <td class="text-end pe-3 fw-bold">₹ ${fmt(grandTotal)}</td>
    </tr></tfoot></table></div>`;
    document.getElementById('modeBankBody').innerHTML = html;
    new bootstrap.Modal(document.getElementById('modeBankModal')).show();
}

const PRINT_GROUPED       = @json($printGrouped);
const PRINT_MODEWISE      = @json($printModeWise);
const PRINT_INSTITUTE     = @json($printInstitute?->name ?? 'Institute');
const PRINT_LOGO_URL      = @json($printLogoUrl);
const PRINT_INITIALS      = @json($printInitials);
const PRINT_TOTALS = {
    collected: {{ (float) $totalCollected }},
    discount:  {{ (float) $totalDiscount }},
    fine:      {{ (float) $totalFine }},
    invoices:  {{ (int) $totalInvoices }},
    students:  {{ (int) $totalStudents }},
};

function printReport() {
    const session    = '{{ $sessionObj?->name ?? "" }}';
    const dateRange  = '{{ \Carbon\Carbon::parse($dateFrom)->format("d M Y") }} – {{ \Carbon\Carbon::parse($dateTo)->format("d M Y") }}';
    const groupLabel = '{{ $groupBy === "month" ? "Month" : "Day" }}';
    const payable    = PRINT_TOTALS.collected + PRINT_TOTALS.discount;
    const fmt        = n => n.toLocaleString('en-IN', {maximumFractionDigits:0});
    const printDate  = new Date().toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
    const printTime  = new Date().toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit'});

    let rowsHtml = '';
    PRINT_GROUPED.forEach(r => {
        rowsHtml += `<tr>
            <td style="font-weight:700;">${r.label}</td>
            <td class="r">${fmt(r.collected)}</td>
            <td class="r">${r.discount > 0 ? fmt(r.discount) : '—'}</td>
            <td class="r">${r.fine > 0 ? fmt(r.fine) : '—'}</td>
            <td class="r">${fmt(r.collected + r.discount)}</td>
            <td class="r c">${r.invoices}</td>
            <td class="r c">${r.students}</td>
        </tr>`;
    });
    if (!PRINT_GROUPED.length) {
        rowsHtml = '<tr><td colspan="7" style="text-align:center; padding:12px; font-weight:700;">No collections in this date range.</td></tr>';
    }

    let modeHtml = '';
    PRINT_MODEWISE.forEach(m => {
        modeHtml += `<tr>
            <td style="font-weight:700;">${m.mode}</td>
            <td class="r">${fmt(m.total)}</td>
            <td class="r c">${m.cnt}</td>
        </tr>`;
    });
    if (!PRINT_MODEWISE.length) {
        modeHtml = '<tr><td colspan="3" style="text-align:center; padding:12px; font-weight:700;">No data.</td></tr>';
    }

    const win = window.open('', '_blank', 'width=1000,height=750');
    win.document.write(`<!DOCTYPE html><html><head>
<meta charset="UTF-8">
<title>Daily / Monthly Collection</title>
<style>
* { box-sizing: border-box; margin:0; padding:0; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #000; background: #fff; padding: 8mm; font-weight:600; line-height:1.3; }

.hdr { display:table; width:100%; border-bottom:2px solid #000; padding-bottom:8px; margin-bottom:10px; }
.hdr-l, .hdr-m, .hdr-r { display:table-cell; vertical-align:middle; }
.hdr-l { width:44px; padding-right:9px; }
.logo-box { width:38px; height:38px; border:1.5px solid #000; border-radius:4px; text-align:center; line-height:38px; font-size:15px; font-weight:800; color:#000; overflow:hidden; background:#e8e8e8; }
.logo-box img { width:38px; height:38px; object-fit:cover; border-radius:4px; display:block; }
.inst-name { font-size:15px; font-weight:800; }
.inst-sub  { font-size:10.5px; font-weight:600; margin-top:2px; }
.hdr-r { text-align:right; font-size:9px; font-weight:600; white-space:nowrap; }
.hdr-r div { margin-bottom:2px; }
.hdr-r strong { font-weight:800; }

.stats { width:100%; border-collapse:collapse; margin-bottom:12px; table-layout:fixed; }
.stats td { border:1px solid #000; padding:6px 8px; vertical-align:top; }
.stats .lbl { font-size:8px; text-transform:uppercase; font-weight:700; }
.stats .val { font-size:14px; font-weight:800; margin-top:2px; }

.sec-title { font-size:11px; font-weight:800; border-bottom:1.5px solid #1e3a5f; color:#1e3a5f; padding-bottom:3px; margin-bottom:5px; }
table.t { width:100%; border-collapse:collapse; }
table.t thead th { background:#1e3a5f; color:#fff; font-size:8.5px; font-weight:800; padding:4px 5px; text-align:left; border:0.5px solid #0d2540; }
table.t thead th.r { text-align:right; }
table.t thead th.c { text-align:center; }
table.t tbody td { padding:3px 5px; font-size:8.5px; font-weight:600; border:1px solid #e5e7eb; }
table.t tbody tr:nth-child(even) td { background:#f8fafc; }
table.t tfoot td { padding:4px 5px; font-size:9px; font-weight:800; background:#1e3a5f; color:#fff; border:0.5px solid #0d2540; }
.r { text-align:right; }
.c { text-align:center; }

.two-col { display:table; width:100%; table-layout:fixed; }
.two-col .col-a, .two-col .col-b { display:table-cell; vertical-align:top; }
.two-col .col-a { width:64%; padding-right:12px; }
.two-col .col-b { width:36%; }

.ftr { margin-top:10px; border-top:1.5px solid #000; padding-top:4px; display:table; width:100%; }
.ftr-l, .ftr-r { display:table-cell; font-size:8px; font-weight:600; }
.ftr-r { text-align:right; }

.no-print { margin-bottom:10px; }
.no-print button { padding:6px 14px; background:#1e3a5f; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:13px; font-weight:700; }
@media print {
    @page { size: A4 landscape; margin: 14mm 12mm 12mm 12mm; }
    .no-print { display:none !important; }
    body { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>
</head><body>

<div class="no-print"><button onclick="window.print()">Print / Save PDF</button></div>

<div class="hdr">
    <div class="hdr-l">
        <div class="logo-box">${PRINT_LOGO_URL ? `<img src="${PRINT_LOGO_URL}" alt="Logo">` : PRINT_INITIALS}</div>
    </div>
    <div class="hdr-m">
        <div class="inst-name">${PRINT_INSTITUTE}</div>
        <div class="inst-sub">Daily / Monthly Collection &mdash; ${session}</div>
    </div>
    <div class="hdr-r">
        <div>Period: <strong>${dateRange}</strong></div>
        <div>Generated: <strong>${printDate}, ${printTime}</strong></div>
    </div>
</div>

<table class="stats">
    <tr>
        <td><div class="lbl">Total Collected</div><div class="val">₹ ${fmt(PRINT_TOTALS.collected)}</div></td>
        <td><div class="lbl">Total Discount</div><div class="val">₹ ${fmt(PRINT_TOTALS.discount)}</div></td>
        <td><div class="lbl">Total Fine</div><div class="val">₹ ${fmt(PRINT_TOTALS.fine)}</div></td>
        <td><div class="lbl">Total Payable</div><div class="val">₹ ${fmt(payable)}</div></td>
        <td><div class="lbl">Total Invoices</div><div class="val">${PRINT_TOTALS.invoices}</div></td>
        <td><div class="lbl">Unique Students</div><div class="val">${PRINT_TOTALS.students}</div></td>
    </tr>
</table>

<div class="two-col">
    <div class="col-a">
        <div class="sec-title">${groupLabel} Wise Collection</div>
        <table class="t">
            <thead>
                <tr>
                    <th>${groupLabel}</th>
                    <th class="r">Collection</th>
                    <th class="r">Discount</th>
                    <th class="r">Fine</th>
                    <th class="r">Payable</th>
                    <th class="r c">Invoices</th>
                    <th class="r c">Students</th>
                </tr>
            </thead>
            <tbody>${rowsHtml}</tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="r">${fmt(PRINT_TOTALS.collected)}</td>
                    <td class="r">${PRINT_TOTALS.discount > 0 ? fmt(PRINT_TOTALS.discount) : '—'}</td>
                    <td class="r">${PRINT_TOTALS.fine > 0 ? fmt(PRINT_TOTALS.fine) : '—'}</td>
                    <td class="r">${fmt(payable)}</td>
                    <td class="r c">${PRINT_TOTALS.invoices}</td>
                    <td class="r c">${PRINT_TOTALS.students}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="col-b">
        <div class="sec-title">Payment Mode Wise</div>
        <table class="t">
            <thead><tr><th>Mode</th><th class="r">Amount</th><th class="r c">Count</th></tr></thead>
            <tbody>${modeHtml}</tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="r">${fmt(PRINT_MODEWISE.reduce((s,m)=>s+m.total,0))}</td>
                    <td class="r c">${PRINT_MODEWISE.reduce((s,m)=>s+m.cnt,0)}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="ftr">
    <div class="ftr-l">${PRINT_INSTITUTE} &mdash; Daily / Monthly Collection &mdash; Confidential</div>
    <div class="ftr-r">Generated: ${printDate}, ${printTime}</div>
</div>

<script>window.onload = () => window.print();<\/script>
</body></html>`);
    win.document.close();
}

// Max 3-month validation for monthly view
document.getElementById('filterForm').addEventListener('submit', function(e) {
    const groupBy = document.getElementById('group_by').value;
    if (groupBy !== 'month') return;
    const from = new Date(document.getElementById('date_from').value);
    const to   = new Date(document.getElementById('date_to').value);
    const diffMonths = (to.getFullYear() - from.getFullYear()) * 12 + (to.getMonth() - from.getMonth());
    if (diffMonths > 3) {
        e.preventDefault();
        const warn = document.getElementById('rangeWarning');
        warn.classList.remove('d-none');
        // Auto-correct date_to to from + 3 months
        const corrected = new Date(from);
        corrected.setMonth(corrected.getMonth() + 3);
        document.getElementById('date_to').value = corrected.toISOString().split('T')[0];
        setTimeout(() => { warn.classList.add('d-none'); this.submit(); }, 1200);
    }
});
</script>
@endpush
@endsection
