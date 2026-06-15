<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $entityType }} Receipts — {{ $entityName }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 7.5px; color: #1a1a1a; }
.header { text-align: center; padding: 8px 0 6px; border-bottom: 2px solid #1e40af; margin-bottom: 8px; }
.header h1 { font-size: 13px; font-weight: bold; color: #1e40af; }
.header h2 { font-size: 9.5px; color: #374151; margin-top: 2px; }
.header .meta { font-size: 7.5px; color: #6b7280; margin-top: 2px; }
.summary { display: flex; gap: 8px; margin-bottom: 10px; flex-wrap: wrap; }
.summary-box { border: 1px solid #e5e7eb; border-radius: 4px; padding: 3px 8px; min-width: 80px; }
.summary-box .lbl { font-size: 6.5px; color: #6b7280; }
.summary-box .val { font-size: 9px; font-weight: bold; color: #111827; }
table { width: 100%; border-collapse: collapse; font-size: 7px; table-layout: fixed; }
th { background: #1e40af; color: #fff; padding: 2px 3px; text-align: left; border: 1px solid #1e40af; overflow: hidden; word-wrap: break-word; }
th.r, td.r { text-align: right; }
th.c, td.c { text-align: center; }
td { padding: 2px 3px; border: 1px solid #e5e7eb; overflow: hidden; word-wrap: break-word; }
tr:nth-child(even) td { background: #f8fafc; }
tfoot td { background: #e2e8f0; font-weight: bold; border-top: 2px solid #1e40af; }
.green { color: #16a34a; }
@page { size: A4 landscape; margin: 8mm; }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $instituteName }}</h1>
    <h2>{{ $entityType }} Collection — {{ $entityName }}@if($entitySubtitle), {{ $entitySubtitle }}@endif</h2>
    <div class="meta">Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }} &nbsp;|&nbsp; Generated: {{ now()->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
</div>

<div class="summary">
    <div class="summary-box"><div class="lbl">Total Receipts</div><div class="val">{{ number_format($totalReceipts) }}</div></div>
    <div class="summary-box"><div class="lbl">Total Collection</div><div class="val green">Rs {{ number_format($totalAmount, 2) }}</div></div>
    <div class="summary-box"><div class="lbl">Cash</div><div class="val">Rs {{ number_format($cashTotal, 2) }}</div></div>
    <div class="summary-box"><div class="lbl">UPI</div><div class="val">Rs {{ number_format($upiTotal, 2) }}</div></div>
    <div class="summary-box"><div class="lbl">Online</div><div class="val">Rs {{ number_format($onlineTotal, 2) }}</div></div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:3%" class="c">#</th>
            <th style="width:9%">Invoice No</th>
            <th style="width:6%">Date</th>
            <th style="width:11%">Student Name</th>
            <th style="width:6%">Student ID</th>
            <th style="width:5%">Roll No</th>
            <th style="width:9%">Father Name</th>
            <th style="width:9%">Mother Name</th>
            <th style="width:10%">Course</th>
            <th style="width:12%">Fee Items</th>
            <th style="width:5%" class="c">Mode</th>
            <th style="width:7%" class="r">Amount (Rs)</th>
            <th style="width:6%" class="r">Discount (Rs)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoices as $i => $inv)
        <tr>
            <td class="c">{{ $i + 1 }}</td>
            <td>{{ $inv->invoice_no }}</td>
            <td>{{ $inv->payment_date?->format('d/m/Y') }}</td>
            <td><strong>{{ $inv->student->name ?? '—' }}</strong></td>
            <td>{{ $inv->student->student_uid ?? '' }}</td>
            <td>{{ $inv->student->roll_no ?? '—' }}</td>
            <td>{{ $inv->student->father_name ?? '—' }}</td>
            <td>{{ $inv->student->mother_name ?? '—' }}</td>
            <td>{{ $inv->student->stream->course->name ?? '—' }}</td>
            <td>{{ $inv->items->pluck('fee_name')->implode(', ') }}</td>
            <td class="c">{{ strtoupper($inv->payment_mode ?? '') }}</td>
            <td class="r green"><strong>{{ number_format($inv->paid_amount, 2) }}</strong></td>
            <td class="r">{{ ($inv->discount ?? 0) > 0 ? number_format($inv->discount, 2) : '—' }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="11" class="r">Total ({{ $totalReceipts }} receipts):</td>
            <td class="r green">{{ number_format($totalAmount, 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

<script>window.onload = () => window.print();</script>
</body>
</html>
