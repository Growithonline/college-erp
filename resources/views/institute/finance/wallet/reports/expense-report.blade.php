@extends('institute.layout')
@section('title', 'Expense Report')
@section('breadcrumb', 'Finance / Wallet / Expense Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-pie-chart me-2 text-danger"></i>Expense Report</h4>
        <small class="text-muted">Category-wise drill-down: L1 → L2 → Vendor</small>
    </div>
    <a href="{{ route('finance.wallet.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Dashboard
    </a>
</div>

{{-- Filters --}}
<form method="GET" id="expenseFilterForm" class="card border-0 shadow-sm mb-4">
    <input type="hidden" name="export" id="exportInput" value="">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>
                            {{ $s->name }} {{ $s->is_active ? '(Active)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">From Date</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">To Date</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Drill-down Category</label>
                <select name="l1_id" class="form-select form-select-sm">
                    <option value="">-- All Categories --</option>
                    @foreach($l1Categories as $l1)
                        <option value="{{ $l1->id }}" {{ $l1Id == $l1->id ? 'selected' : '' }}>
                            {{ $l1->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-danger btn-sm flex-grow-1">
                    <i class="bi bi-search me-1"></i> Apply
                </button>
                <div class="dropdown">
                    <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle px-2" data-bs-toggle="dropdown" title="Export">
                        <i class="bi bi-download"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">Export</h6></li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="erExport('csv')">
                                <i class="bi bi-filetype-csv me-2 text-success"></i>CSV
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="erExport('pdf')">
                                <i class="bi bi-file-earmark-pdf me-2 text-danger"></i>PDF
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Grand total --}}
<div class="alert alert-danger border-0 shadow-sm mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div class="fw-bold fs-5">
            <i class="bi bi-receipt-cutoff me-2"></i>Total Expense (Approved)
        </div>
        <div class="fw-bold fs-4 text-danger">Rs {{ number_format($grandTotal, 2) }}</div>
    </div>
</div>

<div class="row g-4">

    {{-- L1 Category breakdown --}}
    <div class="col-md-{{ $byL2->isEmpty() ? '8' : '6' }}">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-semibold">By Category (L1)</h6>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle small">
                    <thead class="table-light">
                        <tr>
                            <th>Category</th>
                            <th class="text-end">Count</th>
                            <th class="text-end text-danger">Amount</th>
                            <th class="text-end">%</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($byL1 as $row)
                        @php
                            $pct = $grandTotal > 0 ? round((float)$row->total / $grandTotal * 100, 1) : 0;
                            $catName = $row->categoryL1?->name ?? 'Uncategorized';
                        @endphp
                        <tr {{ $l1Id == $row->expense_category_l1_id ? 'class=table-warning' : '' }}>
                            <td class="fw-semibold">{{ $catName }}</td>
                            <td class="text-end text-muted">{{ $row->count }}</td>
                            <td class="text-end text-danger">Rs {{ number_format($row->total, 2) }}</td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end gap-1">
                                    <div class="progress flex-grow-1" style="height:6px;min-width:40px">
                                        <div class="progress-bar bg-danger" style="width:{{ $pct }}%"></div>
                                    </div>
                                    <span>{{ $pct }}%</span>
                                </div>
                            </td>
                            <td>
                                @if($row->expense_category_l1_id)
                                <a href="{{ request()->fullUrlWithQuery(['l1_id' => $row->expense_category_l1_id]) }}"
                                   class="btn btn-outline-warning btn-sm py-0">
                                    <i class="bi bi-zoom-in"></i>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        @if($byL1->isEmpty())
                        <tr><td colspan="5" class="text-center text-muted py-4">No approved expenses found.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- L2/Vendor drill-down --}}
    @if($selectedL1 && $byL2->isNotEmpty())
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 border-warning border-2">
            <div class="card-header bg-warning bg-opacity-10 border-bottom py-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-zoom-in me-1"></i>
                    {{ $selectedL1->name }} — Sub-category & Vendor Breakdown
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle small">
                    <thead class="table-light">
                        <tr>
                            <th>Sub-Category</th>
                            <th>Vendor</th>
                            <th class="text-end">Count</th>
                            <th class="text-end text-danger">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($byL2 as $row)
                        <tr>
                            <td>{{ $row->categoryL2?->name ?? '—' }}</td>
                            <td class="text-muted">{{ $row->vendor?->name ?? $row->vendor_name ?? '—' }}</td>
                            <td class="text-end">{{ $row->count }}</td>
                            <td class="text-end text-danger fw-semibold">Rs {{ number_format($row->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-semibold">
                        <tr>
                            <td colspan="3">Total</td>
                            <td class="text-end text-danger">Rs {{ number_format($byL2->sum('total'), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Month-wise expense --}}
    @if($monthWise->isNotEmpty())
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-semibold">Month-wise Expense</h6>
            </div>
            <div class="card-body">
                <canvas id="expenseChart" height="60"></canvas>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 small">
                    <thead class="table-light">
                        <tr><th>Month</th><th class="text-end text-danger">Expense</th></tr>
                    </thead>
                    <tbody>
                        @foreach($monthWise as $row)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($row->month . '-01')->format('F Y') }}</td>
                            <td class="text-end text-danger">Rs {{ number_format($row->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
function erExport(type) {
    document.getElementById('exportInput').value = type;
    document.getElementById('expenseFilterForm').submit();
    setTimeout(() => document.getElementById('exportInput').value = '', 500);
}
</script>
@if($monthWise->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('expenseChart'), {
    type: 'bar',
    data: {
        labels: @json($monthWise->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m.'-01')->format('M Y'))),
        datasets: [{
            label: 'Expense',
            data: @json($monthWise->pluck('total')->map(fn($v) => round((float)$v, 2))),
            backgroundColor: 'rgba(220, 53, 69, 0.7)',
            borderColor: 'rgba(220, 53, 69, 1)',
            borderWidth: 1,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => 'Rs ' + ctx.parsed.y.toLocaleString('en-IN', {minimumFractionDigits:2}) } }
        },
        scales: { y: { beginAtZero: true, ticks: { callback: v => 'Rs ' + v.toLocaleString('en-IN') } } }
    }
});
</script>
@endif
@endpush
