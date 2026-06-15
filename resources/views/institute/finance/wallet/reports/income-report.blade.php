@extends('institute.layout')
@section('title', 'Income Report')
@section('breadcrumb', 'Finance / Wallet / Income Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-bar-chart me-2 text-success"></i>Income Report</h4>
        <small class="text-muted">Source-wise, bank-wise aur collector-wise income breakdown</small>
    </div>
    <a href="{{ route('finance.wallet.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Dashboard
    </a>
</div>

{{-- ── Filter Form ─────────────────────────────────────────────────────── --}}
<form method="GET" id="incomeFilterForm" class="card border-0 shadow-sm mb-4">
<div class="card-body pb-2">

    {{-- Row 1: Session | Date | Buttons --}}
    <div class="row g-2 align-items-end mb-2">
        <div class="col-md-2">
            <label class="form-label fw-semibold small mb-1">Session</label>
            <select name="session_id" class="form-select form-select-sm">
                @foreach($sessions as $s)
                    <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>
                        {{ $s->name }}{{ $s->is_active ? ' (Active)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small mb-1">Date Range</label>
            <div class="d-flex gap-2 align-items-center">
                <input type="date" name="from" id="irFrom" class="form-control form-control-sm" value="{{ $from }}">
                <span class="text-muted small">to</span>
                <input type="date" name="to" id="irTo" class="form-control form-control-sm" value="{{ $to }}">
            </div>
            <div class="mt-1 d-flex gap-1">
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="irPreset('today')" style="font-size:11px"><i class="bi bi-calendar-day me-1"></i>Today</button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="irPreset('month')" style="font-size:11px"><i class="bi bi-calendar-month me-1"></i>This Month</button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="irPreset('all')" style="font-size:11px"><i class="bi bi-infinity me-1"></i>All</button>
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold small mb-1">Payment Type</label>
            <select name="payment_type" class="form-select form-select-sm">
                <option value="">-- Cash + Non-Cash --</option>
                <option value="cash"     {{ ($paymentType ?? '') === 'cash'     ? 'selected' : '' }}>Cash Only</option>
                <option value="non_cash" {{ ($paymentType ?? '') === 'non_cash' ? 'selected' : '' }}>Non-Cash Only</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold small mb-1">Collector Type</label>
            <select name="collector_type" class="form-select form-select-sm">
                <option value="">-- All --</option>
                <option value="staff"   {{ ($collectorType ?? '') === 'staff'   ? 'selected' : '' }}>Staff Only</option>
                <option value="center"  {{ ($collectorType ?? '') === 'center'  ? 'selected' : '' }}>Center Only</option>
                <option value="partner" {{ ($collectorType ?? '') === 'partner' ? 'selected' : '' }}>Partner Only</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                <i class="bi bi-search me-1"></i> Filter
            </button>
            <div class="dropdown">
                <button class="btn btn-success btn-sm dropdown-toggle px-2" type="button" data-bs-toggle="dropdown" title="Export">
                    <i class="bi bi-download"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li><button type="button" class="dropdown-item" onclick="irExport('csv')"><i class="bi bi-filetype-csv me-2 text-success"></i>CSV (.csv)</button></li>
                    <li><button type="button" class="dropdown-item" onclick="irExport('excel')"><i class="bi bi-file-earmark-excel me-2 text-success"></i>Excel (.xlsx)</button></li>
                    <li><button type="button" class="dropdown-item" onclick="irExport('pdf')"><i class="bi bi-file-earmark-pdf me-2 text-danger"></i>PDF (.pdf)</button></li>
                </ul>
            </div>
        </div>
    </div>

</div>
</form>
<input type="hidden" id="irExportInput" name="export" form="incomeFilterForm" value="">
<input type="hidden" name="filtered" value="1" form="incomeFilterForm">

{{-- ── Grand Total Banner ──────────────────────────────────────────────── --}}
<div class="alert alert-success border-0 shadow-sm mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div class="fw-bold fs-5"><i class="bi bi-cash-coin me-2"></i>Total Income</div>
        <div class="fw-bold fs-4 text-success">₹{{ number_format($grandTotal, 2) }}</div>
    </div>
</div>

{{-- ── Row 1: Source Breakdown + Manual by Category ───────────────────── --}}
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-semibold">By Income Source</h6>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle small">
                    <thead class="table-light">
                        <tr><th>Source</th><th class="text-end">Count</th><th class="text-end text-success">Amount</th><th>%</th></tr>
                    </thead>
                    <tbody>
                        @foreach($bySource as $row)
                        @php $src = $row->source_type ?? 'unknown'; $pct = $grandTotal > 0 ? round((float)$row->total/$grandTotal*100,1) : 0; @endphp
                        <tr>
                            <td>
                                @if($src==='fee_invoice')<i class="bi bi-receipt text-success me-1"></i>
                                @elseif($src==='library_fine')<i class="bi bi-book text-warning me-1"></i>
                                @elseif($src==='manual_income')<i class="bi bi-pencil-square text-info me-1"></i>
                                @else<i class="bi bi-cash text-secondary me-1"></i>@endif
                                {{ $sourceLabels[$src] ?? $src }}
                            </td>
                            <td class="text-end text-muted">{{ $row->count }}</td>
                            <td class="text-end fw-semibold text-success">₹{{ number_format($row->total, 2) }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <div class="progress flex-grow-1" style="height:6px;min-width:50px"><div class="progress-bar bg-success" style="width:{{ $pct }}%"></div></div>
                                    <span class="small">{{ $pct }}%</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-semibold">
                        <tr><td>Total</td><td class="text-end">{{ $bySource->sum('count') }}</td><td class="text-end text-success">₹{{ number_format($grandTotal, 2) }}</td><td></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-semibold">Fee Collection by Payment Mode</h6>
            </div>
            <div class="card-body">
                @forelse($feeByMode as $row)
                @php
                    $label = $modeLabels[$row->mode] ?? ucfirst($row->mode);
                    $color = $modeColors[$row->mode] ?? 'secondary';
                    $pct   = $feeTotal > 0 ? round($row->total / $feeTotal * 100, 1) : 0;
                @endphp
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span><span class="badge bg-{{ $color }} me-1">{{ $label }}</span> <span class="text-muted">({{ $row->cnt }})</span></span>
                        <strong>₹{{ number_format($row->total, 2) }} &nbsp;<span class="text-muted">{{ $pct }}%</span></strong>
                    </div>
                    <div class="progress" style="height:8px">
                        <div class="progress-bar bg-{{ $color }}" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-muted small text-center py-3">No fee data for this period.</p>
                @endforelse
                @if($feeByMode->isNotEmpty())
                <div class="border-top mt-2 pt-2 d-flex justify-content-between small fw-semibold">
                    <span>Total Fee</span><span class="text-success">₹{{ number_format($feeTotal, 2) }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Phase 2: Bank-wise Breakdown ───────────────────────────────────── --}}
@if($bankWiseRows->isNotEmpty())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-bank me-2 text-primary"></i>Bank-wise Income</h6>
        <small class="text-muted">Bank pe click karo — payment method breakdown dekhne ke liye</small>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle small">
            <thead class="table-light">
                <tr><th>Bank Account</th><th>A/c No.</th><th class="text-end">Transactions</th><th class="text-end text-success">Total</th><th style="width:30px"></th></tr>
            </thead>
            <tbody>
                @foreach($bankWiseRows as $bankId => $rows)
                @php
                    $bank     = $bankAccountsMap[$bankId] ?? null;
                    $bankName = $bank?->account_name ?? $bank?->bank_name ?? 'Bank #'.$bankId;
                    $bankTotal= (float) $rows->sum('total');
                    $bankCnt  = (int)   $rows->sum('cnt');
                @endphp
                <tr class="cursor-pointer" data-bs-toggle="collapse" data-bs-target="#bank{{ $bankId }}" style="cursor:pointer">
                    <td class="fw-semibold"><i class="bi bi-bank me-2 text-primary"></i>{{ $bankName }}</td>
                    <td class="text-muted small">{{ $bank?->account_no ?? '—' }}</td>
                    <td class="text-end text-muted">{{ $bankCnt }}</td>
                    <td class="text-end fw-bold text-success">₹{{ number_format($bankTotal, 2) }}</td>
                    <td class="text-center text-muted"><i class="bi bi-chevron-down small"></i></td>
                </tr>
                <tr class="collapse" id="bank{{ $bankId }}">
                    <td colspan="5" class="p-0">
                        <div class="px-4 py-2" style="background:#f8fafc">
                            <div class="row g-3">
                                @foreach($rows as $r)
                                @php
                                    $mode  = strtolower($r->payment_mode ?? 'other');
                                    $mlbl  = $modeLabels[$mode] ?? ucfirst($mode);
                                    $mcol  = $modeColors[$mode] ?? 'secondary';
                                    $mpct  = $bankTotal > 0 ? round((float)$r->total/$bankTotal*100,1) : 0;
                                @endphp
                                <div class="col-md-3">
                                    <div class="card border shadow-sm text-center py-2 px-2">
                                        <div><span class="badge bg-{{ $mcol }}">{{ $mlbl }}</span></div>
                                        <div class="fw-bold mt-1 small">₹{{ number_format($r->total, 2) }}</div>
                                        <div class="text-muted" style="font-size:10px">{{ $r->cnt }} txns &nbsp;·&nbsp; {{ $mpct }}%</div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── Phase 3+4: Collector Tabs ───────────────────────────────────────── --}}
@if($staffWiseRows->isNotEmpty() || $centerWiseRows->isNotEmpty() || $partnerWiseRows->isNotEmpty())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-people me-2 text-info"></i>Collector-wise Income</h6>
    </div>
    <div class="card-body p-0">

        {{-- Tabs --}}
        <ul class="nav nav-tabs px-3 pt-2" role="tablist">
            @if($staffWiseRows->isNotEmpty())
            <li class="nav-item">
                <button class="nav-link {{ $staffWiseRows->isNotEmpty() ? 'active' : '' }}"
                        data-bs-toggle="tab" data-bs-target="#tabStaff">
                    <i class="bi bi-person-badge me-1"></i>Staff
                    <span class="badge bg-primary ms-1">{{ $staffWiseRows->count() }}</span>
                </button>
            </li>
            @endif
            @if($centerWiseRows->isNotEmpty())
            <li class="nav-item">
                <button class="nav-link {{ !$staffWiseRows->isNotEmpty() && $centerWiseRows->isNotEmpty() ? 'active' : '' }}"
                        data-bs-toggle="tab" data-bs-target="#tabCenter">
                    <i class="bi bi-building me-1"></i>Centers
                    <span class="badge bg-info ms-1">{{ $centerWiseRows->count() }}</span>
                </button>
            </li>
            @endif
            @if($partnerWiseRows->isNotEmpty())
            <li class="nav-item">
                <button class="nav-link {{ !$staffWiseRows->isNotEmpty() && !$centerWiseRows->isNotEmpty() ? 'active' : '' }}"
                        data-bs-toggle="tab" data-bs-target="#tabPartner">
                    <i class="bi bi-diagram-2 me-1"></i>Partners
                    <span class="badge bg-warning text-dark ms-1">{{ $partnerWiseRows->count() }}</span>
                </button>
            </li>
            @endif
        </ul>

        <div class="tab-content">

            {{-- Staff Tab --}}
            @if($staffWiseRows->isNotEmpty())
            <div class="tab-pane fade show active" id="tabStaff">
                @include('institute.finance.wallet.reports._collector-table', [
                    'rows'     => $staffWiseRows,
                    'nameMap'  => $staffMap,
                    'nameKey'  => fn($id) => $staffMap[$id] ?? 'Staff #'.$id,
                    'modeLabels' => $modeLabels,
                    'modeColors' => $modeColors,
                ])
            </div>
            @endif

            {{-- Center Tab --}}
            @if($centerWiseRows->isNotEmpty())
            <div class="tab-pane fade {{ !$staffWiseRows->isNotEmpty() ? 'show active' : '' }}" id="tabCenter">
                @include('institute.finance.wallet.reports._collector-table', [
                    'rows'     => $centerWiseRows,
                    'nameMap'  => $centerMap,
                    'nameKey'  => fn($id) => $centerMap[$id] ?? 'Center #'.$id,
                    'modeLabels' => $modeLabels,
                    'modeColors' => $modeColors,
                ])
            </div>
            @endif

            {{-- Partner Tab --}}
            @if($partnerWiseRows->isNotEmpty())
            <div class="tab-pane fade {{ !$staffWiseRows->isNotEmpty() && !$centerWiseRows->isNotEmpty() ? 'show active' : '' }}" id="tabPartner">
                @include('institute.finance.wallet.reports._collector-table', [
                    'rows'     => $partnerWiseRows,
                    'nameMap'  => $partnerMap,
                    'nameKey'  => fn($id) => $partnerMap[$id]?->name ?? 'Partner #'.$id,
                    'modeLabels' => $modeLabels,
                    'modeColors' => $modeColors,
                    'showCommission' => true,
                    'partnerMap' => $partnerMap,
                ])
            </div>
            @endif

        </div>
    </div>
</div>
@endif

{{-- ── Manual income by category ──────────────────────────────────────── --}}
@if($manualByCategory->isNotEmpty())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-semibold">Manual Income by Category</h6>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle small">
            <thead class="table-light">
                <tr><th>Category</th><th class="text-end">Count</th><th class="text-end text-success">Amount</th></tr>
            </thead>
            <tbody>
                @foreach($manualByCategory as $row)
                <tr>
                    <td class="fw-semibold">{{ $row->category?->name ?? 'Unknown' }}</td>
                    <td class="text-end text-muted">{{ $row->count }}</td>
                    <td class="text-end fw-semibold text-success">₹{{ number_format($row->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light fw-semibold">
                <tr><td>Total</td><td class="text-end">{{ $manualByCategory->sum('count') }}</td><td class="text-end text-success">₹{{ number_format($manualByCategory->sum('total'), 2) }}</td></tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- ── Month-wise Chart ────────────────────────────────────────────────── --}}
@if($monthWise->isNotEmpty())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3"><h6 class="mb-0 fw-semibold">Month-wise Income</h6></div>
    <div class="card-body"><canvas id="incomeChart" height="60"></canvas></div>
    <div class="table-responsive">
        <table class="table table-sm mb-0 small">
            <thead class="table-light"><tr><th>Month</th><th class="text-end text-success">Income</th></tr></thead>
            <tbody>
                @foreach($monthWise as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row->month.'-01')->format('F Y') }}</td>
                    <td class="text-end text-success">₹{{ number_format($row->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@push('scripts')
<script>
const IR_TODAY = '{{ now()->toDateString() }}';
const IR_MONTH = '{{ now()->startOfMonth()->toDateString() }}';
function irPreset(p) {
    const f = document.getElementById('irFrom'), t = document.getElementById('irTo');
    if (p==='today')      { f.value=IR_TODAY; t.value=IR_TODAY; }
    else if (p==='month') { f.value=IR_MONTH; t.value=IR_TODAY; }
    else                  { f.value=''; t.value=''; }
    document.getElementById('incomeFilterForm').submit();
}
function irExport(fmt) {
    document.getElementById('irExportInput').value=fmt;
    document.getElementById('incomeFilterForm').submit();
    setTimeout(() => document.getElementById('irExportInput').value='', 500);
}
</script>
@if($monthWise->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('incomeChart'), {
    type: 'line',
    data: {
        labels: @json($monthWise->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m.'-01')->format('M Y'))),
        datasets: [{ label: 'Income', data: @json($monthWise->pluck('total')->map(fn($v) => round((float)$v,2))),
            borderColor:'rgba(25,135,84,1)', backgroundColor:'rgba(25,135,84,0.1)', fill:true, tension:0.3 }]
    },
    options: { responsive:true, plugins:{ legend:{display:false}, tooltip:{callbacks:{label:ctx=>'₹'+ctx.parsed.y.toLocaleString('en-IN',{minimumFractionDigits:2})}}},
        scales:{ y:{ beginAtZero:true, ticks:{callback:v=>'₹'+v.toLocaleString('en-IN')} } } }
});
</script>
@endif
@endpush
@endsection
