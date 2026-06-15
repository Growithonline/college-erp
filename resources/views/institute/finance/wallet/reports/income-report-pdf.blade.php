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
    .badge { padding:1px 4px; border-radius:3px; font-size:6.5px; }
    .grand { background:#e8f5e9; border:1px solid #a5d6a7; padding:6px 10px; margin-bottom:8px; border-radius:4px; }
    .grand-label { font-size:11px; font-weight:bold; color:#1a1a2e; }
    .grand-val { font-size:14px; font-weight:bold; color:#198754; float:right; }
    .clearfix::after { content:''; display:table; clear:both; }
    .muted { color:#777; }
    .section { page-break-inside:avoid; }
</style>
</head>
<body>
<h1>{{ $instituteName }}</h1>
<h2>Income Report</h2>
<p class="subtitle">{{ $filterLabel }} &nbsp;|&nbsp; Generated: {{ now()->format('d-m-Y H:i') }}</p>

<div class="grand clearfix">
    <span class="grand-label">Total Income</span>
    <span class="grand-val">₹{{ number_format($grandTotal, 2) }}</span>
</div>

{{-- By Source --}}
<div class="section">
<h3>Income by Source</h3>
<table>
    <thead><tr><th>Source</th><th class="r">Count</th><th class="r">Amount</th><th class="r">%</th></tr></thead>
    <tbody>
        @foreach($bySource as $row)
        @php $src = $row->source_type ?? 'unknown'; $pct = $grandTotal > 0 ? round((float)$row->total/$grandTotal*100,1) : 0; @endphp
        <tr><td>{{ $sourceLabels[$src] ?? $src }}</td><td class="r">{{ $row->count }}</td><td class="r">₹{{ number_format($row->total, 2) }}</td><td class="r">{{ $pct }}%</td></tr>
        @endforeach
    </tbody>
    <tfoot><tr><td>Total</td><td class="r">{{ $bySource->sum('count') }}</td><td class="r">₹{{ number_format($grandTotal, 2) }}</td><td></td></tr></tfoot>
</table>
</div>

{{-- Fee by Mode --}}
<div class="section">
<h3>Fee Collection by Payment Mode</h3>
<table>
    <thead><tr><th>Mode</th><th class="r">Transactions</th><th class="r">Amount</th><th class="r">%</th></tr></thead>
    <tbody>
        @php $feeT = (float) $feeByMode->sum('total'); @endphp
        @foreach($feeByMode as $r)
        @php $pct = $feeT > 0 ? round($r->total/$feeT*100,1) : 0; @endphp
        <tr><td>{{ $modeLabels[$r->mode] ?? ucfirst($r->mode) }}</td><td class="r">{{ $r->cnt }}</td><td class="r">₹{{ number_format($r->total, 2) }}</td><td class="r">{{ $pct }}%</td></tr>
        @endforeach
    </tbody>
    <tfoot><tr><td>Total</td><td class="r">{{ $feeByMode->sum('cnt') }}</td><td class="r">₹{{ number_format($feeT, 2) }}</td><td></td></tr></tfoot>
</table>
</div>

{{-- Bank-wise --}}
@if($bankWiseRows->isNotEmpty())
<div class="section">
<h3>Bank-wise Income</h3>
<table>
    <thead><tr><th>Bank Account</th><th>A/c No.</th><th>Mode</th><th class="r">Transactions</th><th class="r">Amount</th></tr></thead>
    <tbody>
        @foreach($bankWiseRows as $bankId => $rows)
        @php $bank = $bankAccountsMap[$bankId] ?? null; $bankName = $bank?->account_name ?? $bank?->bank_name ?? 'Bank #'.$bankId; @endphp
        @foreach($rows as $r)
        @php $mode = strtolower($r->payment_mode ?? 'other'); @endphp
        <tr>
            <td>{{ $bankName }}</td>
            <td class="muted">{{ $bank?->account_no }}</td>
            <td>{{ $modeLabels[$mode] ?? ucfirst($mode) }}</td>
            <td class="r">{{ $r->cnt }}</td>
            <td class="r">₹{{ number_format($r->total, 2) }}</td>
        </tr>
        @endforeach
        @endforeach
    </tbody>
</table>
</div>
@endif

{{-- Staff-wise --}}
@if($staffWiseRows->isNotEmpty())
<div class="section">
<h3>Staff-wise Collection</h3>
<table>
    <thead><tr><th>Staff Name</th><th>Mode</th><th class="r">Transactions</th><th class="r">Amount</th></tr></thead>
    <tbody>
        @foreach($staffWiseRows as $staffId => $rows)
        @php $name = $staffMap[$staffId] ?? 'Staff #'.$staffId; @endphp
        @foreach($rows as $r)
        @php $mode = strtolower($r->payment_mode ?? 'other'); @endphp
        <tr><td>{{ $name }}</td><td>{{ $modeLabels[$mode] ?? ucfirst($mode) }}</td><td class="r">{{ $r->cnt }}</td><td class="r">₹{{ number_format($r->total, 2) }}</td></tr>
        @endforeach
        @endforeach
    </tbody>
</table>
</div>
@endif

{{-- Center-wise --}}
@if($centerWiseRows->isNotEmpty())
<div class="section">
<h3>Center-wise Collection</h3>
<table>
    <thead><tr><th>Center Name</th><th>Mode</th><th class="r">Transactions</th><th class="r">Amount</th></tr></thead>
    <tbody>
        @foreach($centerWiseRows as $cId => $rows)
        @php $name = $centerMap[$cId] ?? 'Center #'.$cId; @endphp
        @foreach($rows as $r)
        @php $mode = strtolower($r->payment_mode ?? 'other'); @endphp
        <tr><td>{{ $name }}</td><td>{{ $modeLabels[$mode] ?? ucfirst($mode) }}</td><td class="r">{{ $r->cnt }}</td><td class="r">₹{{ number_format($r->total, 2) }}</td></tr>
        @endforeach
        @endforeach
    </tbody>
</table>
</div>
@endif

{{-- Partner-wise --}}
@if($partnerWiseRows->isNotEmpty())
<div class="section">
<h3>Partner-wise Collection</h3>
<table>
    <thead><tr><th>Partner</th><th>Mode</th><th class="r">Transactions</th><th class="r">Amount</th><th class="r">Commission</th></tr></thead>
    <tbody>
        @foreach($partnerWiseRows as $pId => $rows)
        @php $partner = $partnerMap[$pId] ?? null; $name = $partner?->name ?? 'Partner #'.$pId; $pct = (float)($partner?->commission_percent ?? 0); @endphp
        @foreach($rows as $r)
        @php $mode = strtolower($r->payment_mode ?? 'other'); $comm = $pct > 0 ? round((float)$r->total * $pct / 100, 2) : 0; @endphp
        <tr>
            <td>{{ $name }}</td>
            <td>{{ $modeLabels[$mode] ?? ucfirst($mode) }}</td>
            <td class="r">{{ $r->cnt }}</td>
            <td class="r">₹{{ number_format($r->total, 2) }}</td>
            <td class="r">{{ $comm > 0 ? '₹'.number_format($comm,2) : '—' }}</td>
        </tr>
        @endforeach
        @endforeach
    </tbody>
</table>
</div>
@endif

</body>
</html>
