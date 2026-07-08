<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Centre Collection Report</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 9px; color: #1a1a1a; }
.header { text-align: center; padding: 8px 0 6px; border-bottom: 2px solid #1e40af; margin-bottom: 8px; }
.header h1 { font-size: 14px; font-weight: bold; color: #1e40af; }
.header h2 { font-size: 10px; color: #374151; margin-top: 2px; }
.header .meta { font-size: 8px; color: #6b7280; margin-top: 2px; }
.summary { display: flex; gap: 12px; margin-bottom: 10px; flex-wrap: wrap; }
.summary-box { border: 1px solid #e5e7eb; border-radius: 4px; padding: 4px 10px; min-width: 100px; }
.summary-box .lbl { font-size: 7px; color: #6b7280; }
.summary-box .val { font-size: 11px; font-weight: bold; color: #111827; }
.section-title { font-size: 11px; font-weight: bold; color: #1e40af; border-bottom: 1px solid #1e40af; padding-bottom: 3px; margin: 14px 0 6px; page-break-after: avoid; }
table { width: 100%; border-collapse: collapse; font-size: 8px; }
th { background: #1e40af; color: #fff; padding: 3px 6px; text-align: left; border: 1px solid #1e40af; }
th.r, td.r { text-align: right; }
th.c, td.c { text-align: center; }
td { padding: 2px 6px; border: 1px solid #e5e7eb; }
tr:nth-child(even) td { background: #f8fafc; }
tfoot td { background: #e2e8f0; font-weight: bold; border-top: 2px solid #1e40af; }
tr.bank-row td { background: #dbeafe !important; }
.green { color: #16a34a; }
.no-print { display: none; }
.bank-section { page-break-inside: avoid; }
@media print { .no-print { display: none; } }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $instituteName }}</h1>
    <h2>Centre Collection Report</h2>
    <div class="meta">Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }} &nbsp;|&nbsp; Generated: {{ now()->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
</div>

<div class="summary">
    <div class="summary-box"><div class="lbl">Total Receipts</div><div class="val">{{ number_format($grandCount) }}</div></div>
    <div class="summary-box"><div class="lbl">Total Collection</div><div class="val green">Rs {{ number_format($grandTotal, 2) }}</div></div>
    <div class="summary-box"><div class="lbl">Centre Count</div><div class="val">{{ number_format($centreData->count()) }}</div></div>
</div>

<div class="section-title">Centre-wise Collection</div>
<table>
    <thead>
        <tr>
            <th style="width:4%">#</th>
            <th style="width:22%">Centre Name</th>
            <th style="width:8%" class="c">Receipts</th>
            <th style="width:10%" class="r">Cash</th>
            <th style="width:10%" class="r">UPI</th>
            <th style="width:10%" class="r">Online</th>
            <th style="width:9%" class="r">Cheque</th>
            <th style="width:8%" class="r">DD</th>
            <th style="width:8%" class="r">NEFT</th>
            <th style="width:8%" class="r">RTGS</th>
            <th style="width:11%" class="r">Total (Rs)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($centreData as $i => $row)
        <tr>
            <td class="c">{{ $i + 1 }}</td>
            <td><strong>{{ $row['center']?->name ?? 'Unknown Centre' }}</strong></td>
            <td class="c">{{ $row['count'] }}</td>
            <td class="r">{{ $row['cash']   > 0 ? number_format($row['cash'], 2)   : '—' }}</td>
            <td class="r">{{ $row['upi']    > 0 ? number_format($row['upi'], 2)    : '—' }}</td>
            <td class="r">{{ $row['online'] > 0 ? number_format($row['online'], 2) : '—' }}</td>
            <td class="r">{{ $row['cheque'] > 0 ? number_format($row['cheque'], 2) : '—' }}</td>
            <td class="r">{{ $row['dd']     > 0 ? number_format($row['dd'], 2)     : '—' }}</td>
            <td class="r">{{ $row['neft']   > 0 ? number_format($row['neft'], 2)   : '—' }}</td>
            <td class="r">{{ $row['rtgs']   > 0 ? number_format($row['rtgs'], 2)   : '—' }}</td>
            <td class="r green"><strong>{{ number_format($row['total'], 2) }}</strong></td>
        </tr>
        @empty
        <tr><td colspan="11" class="c">No centre collections found for this date range.</td></tr>
        @endforelse
    </tbody>
    @if($centreData->isNotEmpty())
    <tfoot>
        <tr>
            <td colspan="2" class="r">Grand Total</td>
            <td class="c">{{ $grandCount }}</td>
            <td class="r">{{ number_format($centreData->sum('cash'), 2) }}</td>
            <td class="r">{{ number_format($centreData->sum('upi'), 2) }}</td>
            <td class="r">{{ number_format($centreData->sum('online'), 2) }}</td>
            <td class="r">{{ number_format($centreData->sum('cheque'), 2) }}</td>
            <td class="r">{{ number_format($centreData->sum('dd'), 2) }}</td>
            <td class="r">{{ number_format($centreData->sum('neft'), 2) }}</td>
            <td class="r">{{ number_format($centreData->sum('rtgs'), 2) }}</td>
            <td class="r green">{{ number_format($grandTotal, 2) }}</td>
        </tr>
    </tfoot>
    @endif
</table>

{{-- Bank-wise breakdown fixed at the bottom, after the centre list --}}
<div class="bank-section">
    <div class="section-title">Bank-wise Collection — Centre Breakdown</div>
    <table>
        <thead>
            <tr>
                <th style="width:40%">Bank / Account</th>
                <th style="width:30%">Centre Name</th>
                <th style="width:14%" class="c">Receipts</th>
                <th style="width:16%" class="r">Amount (Rs)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bankWise as $bw)
            <tr class="bank-row">
                <td colspan="2"><strong>{{ $bw['label'] }}</strong></td>
                <td class="c"><strong>{{ $bw['count'] }}</strong></td>
                <td class="r green"><strong>{{ number_format($bw['total'], 2) }}</strong></td>
            </tr>
            @foreach(($bankDetailWise[$bw['label']] ?? []) as $cr)
            <tr>
                <td></td>
                <td>{{ $cr['name'] }}</td>
                <td class="c">{{ $cr['count'] }}</td>
                <td class="r">{{ number_format($cr['total'], 2) }}</td>
            </tr>
            @endforeach
            @empty
            <tr><td colspan="4" class="c">No bank / online payments found for this date range.</td></tr>
            @endforelse
        </tbody>
        @if($bankWise->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="2" class="r">Grand Total</td>
                <td class="c">{{ $bankWise->sum('count') }}</td>
                <td class="r green">{{ number_format($bankWise->sum('total'), 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

<script>window.onload = () => window.print();</script>
</body>
</html>
