@extends($staffLayout['layout'] ?? 'institute.layout')
@section('title', 'Income Book')
@section('breadcrumb', 'Finance / Income Book')

@push('styles')
<style>
/* ── Screen ─────────────────────────────────────── */
.print-header { display: none; }

/* ── Print ──────────────────────────────────────── */
@media print {
    .no-print { display: none !important; }
    .print-header { display: block !important; }

    .print-header { text-align: center; margin-bottom: 18px; border-bottom: 2px solid #1a1a2e; padding-bottom: 12px; }
    .print-header .inst-name { font-size: 18pt; font-weight: 800; color: #1a1a2e; letter-spacing: .5px; margin: 0 0 2px; }
    .print-header .report-title { font-size: 13pt; font-weight: 700; color: #1e3a5f; margin: 4px 0 2px; }
    .print-header .report-meta { font-size: 9pt; color: #555; margin: 0; }

    .summary-print { display: flex !important; gap: 0; margin-bottom: 14px; border: 1.5px solid #1a1a2e; border-radius: 6px; overflow: hidden; }
    .summary-print .s-item { flex: 1; padding: 8px 14px; border-right: 1px solid #ccc; }
    .summary-print .s-item:last-child { border-right: none; }
    .summary-print .s-label { font-size: 8pt; color: #666; margin-bottom: 2px; }
    .summary-print .s-value { font-size: 12pt; font-weight: 800; color: #1a1a2e; }
    .summary-print .s-value.total { color: #1a6b3c; }

    .print-table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-top: 4px; }
    .print-table thead tr { background: #1a1a2e !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .print-table thead th { color: #fff !important; padding: 6px 8px; text-align: left; font-weight: 700; font-size: 8.5pt; border: 1px solid #1a1a2e; }
    .print-table thead th:last-child { text-align: right; }
    .print-table tbody tr:nth-child(even) { background: #f7f9fc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .print-table tbody td { padding: 5px 8px; border: 1px solid #dde3ec; vertical-align: middle; color: #222; }
    .print-table tbody td:last-child { text-align: right; font-weight: 600; }
    .print-table tfoot td { padding: 6px 8px; border: 1.5px solid #1a1a2e; font-weight: 800; font-size: 10pt; background: #eef2ff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .print-table tfoot td:last-child { text-align: right; color: #1a6b3c; font-size: 11pt; }

    .badge-fee  { background: #dbeafe; color: #1e40af; padding: 2px 7px; border-radius: 4px; font-size: 8pt; font-weight: 700; }
    .badge-fine { background: #fef3c7; color: #92400e; padding: 2px 7px; border-radius: 4px; font-size: 8pt; font-weight: 700; }

    .print-footer { margin-top: 24px; display: flex; justify-content: space-between; font-size: 8.5pt; color: #666; border-top: 1px solid #ccc; padding-top: 10px; }
    .sign-block { text-align: center; }
    .sign-line { border-top: 1px solid #333; margin-top: 28px; padding-top: 4px; font-weight: 600; color: #222; font-size: 8pt; min-width: 130px; }

    @page { size: A4 portrait; margin: 14mm 12mm 12mm 12mm; }
    body { font-family: 'Arial', sans-serif !important; }
}
</style>
@endpush

@section('content')

{{-- ── Print-only header ─────────────────────────────────────────── --}}
@php
    $instituteName = auth()->guard('staff')->check()
        ? (auth()->guard('staff')->user()->institute->name ?? 'Institute')
        : (auth()->user()->institute->name ?? 'Institute');
    $typeLabel = match($type) {
        'fee'          => 'Fee Collections',
        'library_fine' => 'Library Fines',
        default        => 'All Income',
    };
@endphp
<div class="print-header">
    <p class="inst-name">{{ $instituteName }}</p>
    <p class="report-title">Income Book</p>
    <p class="report-meta">
        Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} &ndash; {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
        &nbsp;|&nbsp; Type: {{ $typeLabel }}
        &nbsp;|&nbsp; Generated: {{ now()->format('d M Y, h:i A') }}
    </p>
</div>

{{-- Print summary strip (hidden on screen, shown via JS clone trick isn't needed — CSS handles it) --}}
<div class="summary-print" style="display:none">
    <div class="s-item">
        <div class="s-label">Fee Collections</div>
        <div class="s-value">&#8377;{{ number_format($totals['fee'], 2) }}</div>
    </div>
    <div class="s-item">
        <div class="s-label">Library Fines</div>
        <div class="s-value">&#8377;{{ number_format($totals['library_fine'], 2) }}</div>
    </div>
    <div class="s-item">
        <div class="s-label">Total Income</div>
        <div class="s-value total">&#8377;{{ number_format($totals['grand'], 2) }}</div>
    </div>
</div>

{{-- ── Screen: Page title & actions ─────────────────────────────── --}}
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Income Book</h4>
        <small class="text-muted">Fee collections aur library fines ki saari income ek jagah dekho</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-filetype-csv me-1"></i> Export CSV
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i> Print / PDF
        </button>
    </div>
</div>

{{-- ── Filters ────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-body py-3">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Income Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="all"          {{ $type === 'all'          ? 'selected' : '' }}>All Income</option>
                    <option value="fee"          {{ $type === 'fee'          ? 'selected' : '' }}>Fee Collections</option>
                    <option value="library_fine" {{ $type === 'library_fine' ? 'selected' : '' }}>Library Fines</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Summary Cards (screen) ─────────────────────────────────────── --}}
<div class="row g-3 mb-4 no-print">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Fee Collections</p>
                        <h5 class="fw-bold mb-0 text-primary">₹{{ number_format($totals['fee'], 2) }}</h5>
                    </div>
                    <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                        <i class="bi bi-receipt text-primary fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Library Fines</p>
                        <h5 class="fw-bold mb-0 text-warning">₹{{ number_format($totals['library_fine'], 2) }}</h5>
                    </div>
                    <div class="bg-warning bg-opacity-10 rounded-circle p-2">
                        <i class="bi bi-book text-warning fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Total Income</p>
                        <h5 class="fw-bold mb-0 text-success">₹{{ number_format($totals['grand'], 2) }}</h5>
                    </div>
                    <div class="bg-success bg-opacity-10 rounded-circle p-2">
                        <i class="bi bi-graph-up-arrow text-success fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Entries Table ───────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm no-print">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Income Entries</span>
        <span class="badge bg-secondary">{{ $entries->count() }} records</span>
    </div>
    <div class="card-body p-0">
        @if($entries->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Is period mein koi income record nahi mila.
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Payment Mode</th>
                        <th>Reference</th>
                        <th class="text-end pe-3">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $i => $row)
                    <tr>
                        <td class="ps-3 text-muted small">{{ $i + 1 }}</td>
                        <td class="small">{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</td>
                        <td>
                            @if($row['type'] === 'fee')
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Fee</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Library Fine</span>
                            @endif
                        </td>
                        <td class="small">{{ $row['description'] }}</td>
                        <td class="small text-capitalize">{{ str_replace('_', ' ', $row['payment_mode'] ?? '-') }}</td>
                        <td class="small text-muted">{{ $row['reference'] }}</td>
                        <td class="text-end pe-3 fw-semibold">{{ number_format($row['amount'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="6" class="ps-3 text-end">Total Income</td>
                        <td class="text-end pe-3 text-success">₹{{ number_format($totals['grand'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- ── Print-only table ────────────────────────────────────────────── --}}
@if($entries->isNotEmpty())
<div style="display:none" class="print-only-table">
    <table class="print-table">
        <thead>
            <tr>
                <th style="width:30px">#</th>
                <th style="width:80px">Date</th>
                <th style="width:80px">Type</th>
                <th>Description</th>
                <th style="width:80px">Payment Mode</th>
                <th style="width:120px">Reference</th>
                <th style="width:80px; text-align:right">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</td>
                <td>
                    @if($row['type'] === 'fee')
                        <span class="badge-fee">Fee</span>
                    @else
                        <span class="badge-fine">Library Fine</span>
                    @endif
                </td>
                <td>{{ $row['description'] }}</td>
                <td style="text-transform:capitalize">{{ str_replace('_', ' ', $row['payment_mode'] ?? '-') }}</td>
                <td>{{ $row['reference'] }}</td>
                <td>{{ number_format($row['amount'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"><strong>Summary:</strong> Fee ₹{{ number_format($totals['fee'], 2) }} + Library Fine ₹{{ number_format($totals['library_fine'], 2) }}</td>
                <td colspan="3" style="text-align:right; font-weight:800">Total Income</td>
                <td style="text-align:right; color:#1a6b3c; font-size:11pt">₹{{ number_format($totals['grand'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

<div style="display:none" class="print-footer">
    <div>
        <div>Generated on: {{ now()->format('d M Y, h:i A') }}</div>
        <div style="margin-top:4px; color:#888">{{ $instituteName }} — Income Book</div>
    </div>
    <div class="sign-block">
        <div class="sign-line">Authorised Signatory</div>
    </div>
</div>
@endif

<style>
@media print {
    .print-only-table { display: block !important; }
    .print-footer { display: flex !important; }
}
</style>

@endsection
