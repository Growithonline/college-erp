<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $entityType }}</title>
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
table { width: 100%; border-collapse: collapse; font-size: 8px; }
th { background: #1e40af; color: #fff; padding: 3px 6px; text-align: left; border: 1px solid #1e40af; }
th.r, td.r { text-align: right; }
th.c, td.c { text-align: center; }
td { padding: 2px 6px; border: 1px solid #e5e7eb; }
tr:nth-child(even) td { background: #f8fafc; }
tfoot td { background: #e2e8f0; font-weight: bold; border-top: 2px solid #1e40af; }
.green { color: #16a34a; }
.no-print { display: none; }
@media print { .no-print { display: none; } }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $instituteName }}</h1>
    <h2>{{ $entityType }}</h2>
    <div class="meta">Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }} &nbsp;|&nbsp; Generated: {{ now()->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
</div>

<div class="summary">
    <div class="summary-box"><div class="lbl">Total Receipts</div><div class="val">{{ number_format($grandCount) }}</div></div>
    <div class="summary-box"><div class="lbl">Total Collection</div><div class="val green">Rs {{ number_format($grandTotal, 2) }}</div></div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:4%">#</th>
            <th style="width:28%">Name</th>
            @if(isset($entityData->first()['sub']) && $entityData->first()['sub'] !== null && $entityData->filter(fn($r) => ($r['sub'] ?? '') !== '')->count() > 0)
            <th style="width:16%">Designation</th>
            @endif
            <th style="width:8%" class="c">Receipts</th>
            <th style="width:11%" class="r">Cash (Rs)</th>
            <th style="width:11%" class="r">UPI (Rs)</th>
            <th style="width:11%" class="r">Online (Rs)</th>
            <th style="width:11%" class="r">Total (Rs)</th>
        </tr>
    </thead>
    <tbody>
        @php $hasSub = $entityData->filter(fn($r) => ($r['sub'] ?? '') !== '')->count() > 0; @endphp
        @foreach($entityData as $i => $row)
        <tr>
            <td class="c">{{ $i + 1 }}</td>
            <td><strong>{{ $row['name'] }}</strong></td>
            @if($hasSub)<td>{{ $row['sub'] ?? '' }}</td>@endif
            <td class="c">{{ $row['count'] }}</td>
            <td class="r">{{ number_format($row['cash'], 2) }}</td>
            <td class="r">{{ number_format($row['upi'], 2) }}</td>
            <td class="r">{{ number_format($row['online'], 2) }}</td>
            <td class="r green"><strong>{{ number_format($row['total'], 2) }}</strong></td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="{{ $hasSub ? 3 : 2 }}" class="r">Grand Total</td>
            <td class="c">{{ $grandCount }}</td>
            <td class="r">{{ number_format($entityData->sum('cash'), 2) }}</td>
            <td class="r">{{ number_format($entityData->sum('upi'), 2) }}</td>
            <td class="r">{{ number_format($entityData->sum('online'), 2) }}</td>
            <td class="r green">{{ number_format($grandTotal, 2) }}</td>
        </tr>
    </tfoot>
</table>

<script>window.onload = () => window.print();</script>
</body>
</html>
