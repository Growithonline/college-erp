<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #333; margin: 0; }
    h1 { text-align:center; font-size:13px; margin:0 0 2px; color:#1a1a2e; }
    h2 { text-align:center; font-size:10px; margin:0 0 2px; color:#555; font-weight:normal; }
    .subtitle { text-align:center; font-size:8px; color:#777; margin-bottom:8px; }
    h3 { font-size:9px; color:#1a1a2e; margin:10px 0 4px; border-bottom:1px solid #ddd; padding-bottom:2px; }
    table { width:100%; border-collapse:collapse; margin-bottom:8px; }
    thead tr { background:#1a1a2e; color:#fff; }
    th { padding:4px 3px; text-align:left; font-size:7px; }
    th.r, td.r { text-align:right; }
    td { padding:3px; border-bottom:1px solid #eee; font-size:7.5px; }
    tr:nth-child(even) td { background:#f9f9f9; }
    tfoot tr { background:#f1f5f9; font-weight:bold; }
    .grand { background:#fef2f2; border:1px solid #fca5a5; padding:6px 10px; margin-bottom:8px; border-radius:4px; }
    .grand-label { font-size:11px; font-weight:bold; color:#1a1a2e; }
    .grand-val { font-size:14px; font-weight:bold; color:#dc3545; float:right; }
    .clearfix::after { content:''; display:table; clear:both; }
    .muted { color:#777; }
    .section { page-break-inside:avoid; }
</style>
</head>
<body>
<h1>{{ $instituteName }}</h1>
<h2>Expense Report</h2>
<p class="subtitle">{{ $filterLabel }} &nbsp;|&nbsp; Generated: {{ now()->format('d-m-Y H:i') }}</p>

<div class="grand clearfix">
    <span class="grand-label">Total Expense (Approved)</span>
    <span class="grand-val">₹{{ number_format($grandTotal, 2) }}</span>
</div>

<div class="section">
<h3>By Category (L1)</h3>
<table>
    <thead><tr><th>Category</th><th class="r">Count</th><th class="r">Amount</th><th class="r">%</th></tr></thead>
    <tbody>
        @foreach($byL1 as $row)
        @php $pct = $grandTotal > 0 ? round((float)$row->total / $grandTotal * 100, 1) : 0; @endphp
        <tr>
            <td>{{ $row->categoryL1?->name ?? 'Uncategorized' }}</td>
            <td class="r">{{ $row->count }}</td>
            <td class="r">₹{{ number_format($row->total, 2) }}</td>
            <td class="r">{{ $pct }}%</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot><tr><td>Total</td><td class="r">{{ $byL1->sum('count') }}</td><td class="r">₹{{ number_format($grandTotal, 2) }}</td><td></td></tr></tfoot>
</table>
</div>

@if($selectedL1 && $byL2->isNotEmpty())
<div class="section">
<h3>{{ $selectedL1->name }} — Sub-category & Vendor Breakdown</h3>
<table>
    <thead><tr><th>Sub-Category</th><th>Vendor</th><th class="r">Count</th><th class="r">Amount</th></tr></thead>
    <tbody>
        @foreach($byL2 as $row)
        <tr>
            <td>{{ $row->categoryL2?->name ?? '—' }}</td>
            <td class="muted">{{ $row->vendor?->name ?? '—' }}</td>
            <td class="r">{{ $row->count }}</td>
            <td class="r">₹{{ number_format($row->total, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot><tr><td colspan="3">Total</td><td class="r">₹{{ number_format($byL2->sum('total'), 2) }}</td></tr></tfoot>
</table>
</div>
@endif

@if($monthWise->isNotEmpty())
<div class="section">
<h3>Month-wise Expense</h3>
<table>
    <thead><tr><th>Month</th><th class="r">Amount</th></tr></thead>
    <tbody>
        @foreach($monthWise as $row)
        <tr>
            <td>{{ \Carbon\Carbon::parse($row->month . '-01')->format('F Y') }}</td>
            <td class="r">₹{{ number_format($row->total, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot><tr><td>Total</td><td class="r">₹{{ number_format($monthWise->sum('total'), 2) }}</td></tr></tfoot>
</table>
</div>
@endif

</body>
</html>
