<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #333; margin: 0; }
    h1 { text-align:center; font-size:14px; margin:0 0 2px; color:#1a1a2e; }
    h2 { text-align:center; font-size:10px; margin:0 0 6px; color:#555; font-weight:normal; }
    .meta { width:100%; border-collapse:collapse; margin-bottom:8px; }
    .meta td { padding:3px 6px; font-size:7.5px; border:1px solid #ddd; }
    .meta td.label { background:#f1f5f9; font-weight:bold; width:90px; }
    h3 { font-size:9px; color:#1a1a2e; margin:10px 0 4px; border-bottom:1px solid #ddd; padding-bottom:2px; }
    table.data { width:100%; border-collapse:collapse; margin-bottom:8px; }
    table.data thead tr { background:#1a1a2e; color:#fff; }
    table.data th { padding:4px 3px; text-align:left; font-size:7px; }
    table.data th.r, table.data td.r { text-align:right; }
    table.data td { padding:3px; border-bottom:1px solid #eee; font-size:7.5px; }
    table.data tr:nth-child(even) td { background:#f9f9f9; }
    table.data tfoot tr { background:#f1f5f9; font-weight:bold; }
    .muted { color:#777; font-size:7px; }
    .totals { width:100%; border-collapse:collapse; margin-top:8px; }
    .totals td { padding:6px 8px; border:1px solid #ccc; font-size:8px; }
    .totals td.label { background:#f1f5f9; font-weight:bold; }
    .totals td.grand { background:#e8f5e9; font-weight:bold; font-size:11px; color:#198754; }
    .section { page-break-inside:avoid; }
</style>
</head>
<body>
<h1>{{ $instituteName }}</h1>
<h2>Daily Register — {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}</h2>

<table class="meta">
    <tr>
        <td class="label">Book No.</td><td>{{ $header->book_no ?? '—' }}</td>
        <td class="label">Rec. Range</td><td>{{ $header->rec_range_from ?? '—' }} to {{ $header->rec_range_to ?? '—' }}</td>
        <td class="label">Online</td><td>{{ $header->online_range_from ?? '—' }} to {{ $header->online_range_to ?? '—' }}</td>
        <td class="label">S.R. No.</td><td>{{ $header->sr_no ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Activities</td><td colspan="7">{{ $header->activities ?? '—' }}</td>
    </tr>
</table>

<div class="section">
<h3>By Hand Receipt (Cash): ₹{{ number_format($receiptModeSplit['by_hand']['amount'], 2) }} ({{ $receiptModeSplit['by_hand']['count'] }})
&nbsp;&nbsp;|&nbsp;&nbsp;
Computerized Receipt: ₹{{ number_format($receiptModeSplit['computerized']['amount'], 2) }} ({{ $receiptModeSplit['computerized']['count'] }})
<span class="muted">(reference only, already included in Income below)</span></h3>
@if($receiptModeSplit['bank_wise']->isNotEmpty())
<table class="data">
    <thead>
        <tr>
            <th>Bank Account</th><th class="r">Count</th>
            @foreach($nonCashModes as $mode)<th class="r">{{ $mode }}</th>@endforeach
            <th class="r">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($receiptModeSplit['bank_wise'] as $bank)
        <tr>
            <td>{{ $bank['bank_name'] }}</td>
            <td class="r">{{ $bank['count'] }}</td>
            @foreach($nonCashModes as $mode)
            <td class="r">
                @if(!empty($bank['modes_by_key'][$mode]))
                    ₹{{ number_format($bank['modes_by_key'][$mode]['amount'], 0) }} | {{ $bank['modes_by_key'][$mode]['count'] }}
                @else
                    —
                @endif
            </td>
            @endforeach
            <td class="r">₹{{ number_format($bank['amount'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
</div>

<div class="section">
<h3>Income</h3>
<table class="data">
    <thead>
        <tr>
            <th>Particular</th><th class="r">Count</th>
            @foreach($yearLabels as $label)<th>{{ $label }}</th>@endforeach
            <th class="r">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($incomeRows as $row)
        <tr>
            <td>{{ $row['name'] }}</td>
            <td class="r">{{ $row['count'] }}</td>
            @foreach($yearLabels as $y => $label)
            <td class="muted">
                @if(!empty($row['year_data'][$y]))
                    @foreach($row['year_data'][$y]['sub_columns'] as $sub){{ $sub['label'] }}: {{ $sub['count'] }} (₹{{ number_format($sub['amount'], 0) }}){{ !$loop->last ? ', ' : '' }}@endforeach
                @else
                    —
                @endif
            </td>
            @endforeach
            <td class="r">₹{{ number_format($row['amount'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot><tr><td colspan="{{ 2 + count($yearLabels) }}">Total (A)</td><td class="r">₹{{ number_format($totalIncome, 2) }}</td></tr></tfoot>
</table>
</div>

<div class="section">
<h3>Expenses</h3>
<table class="data">
    <thead><tr><th>Particular</th><th class="r">Count</th><th class="r">Amount</th></tr></thead>
    <tbody>
        @foreach($expenseRows as $row)
        <tr><td>{{ $row['name'] }}</td><td class="r">{{ $row['count'] }}</td><td class="r">₹{{ number_format($row['amount'], 2) }}</td></tr>
        @endforeach
    </tbody>
    <tfoot><tr><td>Total (B)</td><td></td><td class="r">₹{{ number_format($totalExpense, 2) }}</td></tr></tfoot>
</table>
</div>

<table class="totals">
    <tr>
        <td class="label">Total (A − B)</td><td>₹{{ number_format($totalIncome - $totalExpense, 2) }}</td>
        <td class="label">Last Balance</td><td>₹{{ number_format($lastBalance, 2) }}</td>
        <td class="label">Less Expense</td><td>₹{{ number_format($totalExpense, 2) }}</td>
        <td class="grand">Grand Total: ₹{{ number_format($grandTotal, 2) }}</td>
    </tr>
</table>

</body>
</html>
