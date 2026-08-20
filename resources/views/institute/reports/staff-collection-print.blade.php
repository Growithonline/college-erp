<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Collection Report</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 9px; color: #1a1a1a; }

.hdr { display:table; width:100%; border-bottom:2px solid #000; padding-bottom:8px; margin-bottom:10px; }
.hdr-l, .hdr-m, .hdr-r { display:table-cell; vertical-align:middle; }
.hdr-l { width:44px; padding-right:9px; }
.logo-box {
    width:38px; height:38px; border:1.5px solid #000; border-radius:4px;
    text-align:center; line-height:38px; font-size:15px; font-weight:800;
    color:#000; overflow:hidden; background:#e8e8e8;
}
.logo-box img { width:38px; height:38px; object-fit:cover; border-radius:4px; display:block; }
.inst-name { font-size:15px; font-weight:800; color:#000; letter-spacing:0.2px; }
.inst-sub  { font-size:10.5px; color:#000; font-weight:600; margin-top:2px; }
.hdr-r { text-align:right; font-size:9px; color:#000; font-weight:600; white-space:nowrap; }
.hdr-r div { margin-bottom:2px; }
.hdr-r strong { font-weight:800; color:#000; }

.summary { display: flex; gap: 12px; margin-bottom: 10px; flex-wrap: wrap; }
.summary-box { border: 1px solid #e5e7eb; border-radius: 4px; padding: 4px 10px; min-width: 100px; }
.summary-box .lbl { font-size: 7px; color: #6b7280; }
.summary-box .val { font-size: 11px; font-weight: bold; color: #111827; }
.section-title { font-size: 11px; font-weight: bold; color: #1e3a5f; border-bottom: 1px solid #1e3a5f; padding-bottom: 3px; margin: 14px 0 6px; page-break-after: avoid; }
table { width: 100%; border-collapse: collapse; font-size: 8px; }
thead { display: table-header-group; }
tfoot { display: table-footer-group; }
th { background: #1e3a5f; color: #fff; padding: 4px 6px; text-align: left; border: 0.5px solid #0d2540; }
th.r, td.r { text-align: right; }
th.c, td.c { text-align: center; }
td { padding: 3px 6px; border: 1px solid #e5e7eb; }
tr:nth-child(even) td { background: #f8fafc; }
tbody tr { page-break-inside: avoid; break-inside: avoid; }
tfoot td { background: #e2e8f0; font-weight: bold; border-top: 2px solid #1e3a5f; }
tfoot tr { page-break-inside: avoid; break-inside: avoid; page-break-before: avoid; break-before: avoid-page; }
tr.bank-row td { background: #dbeafe !important; }
.green { color: #16a34a; }
.no-print { display: none; }
.bank-section { page-break-inside: avoid; }
@media print {
    @page { size: A4 landscape; margin: 16mm 10mm 12mm 10mm; }
    .no-print { display: none; }
    body { padding: 4mm 3mm 3mm 3mm; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
</head>
<body>

@php
    $logoUrl = null;
    if (!empty($institute?->image)) {
        if (file_exists(public_path('storage/' . $institute->image))) {
            $logoUrl = asset('storage/' . $institute->image);
        } elseif (file_exists(public_path($institute->image))) {
            $logoUrl = asset($institute->image);
        }
    }
    $initials = strtoupper(substr($institute?->short_name ?: ($institute?->name ?: 'IN'), 0, 2));
@endphp

<div class="hdr">
    <div class="hdr-l">
        <div class="logo-box">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo">
            @else
                {{ $initials }}
            @endif
        </div>
    </div>
    <div class="hdr-m">
        <div class="inst-name">{{ $institute?->name ?? 'Institute' }}</div>
        <div class="inst-sub">Staff Collection Report</div>
    </div>
    <div class="hdr-r">
        <div>Period: <strong>{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} &ndash; {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</strong></div>
        <div>Generated: <strong>{{ now()->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</strong></div>
    </div>
</div>

<div class="summary">
    <div class="summary-box"><div class="lbl">Total Receipts</div><div class="val">{{ number_format($grandCount) }}</div></div>
    <div class="summary-box"><div class="lbl">Total Collection</div><div class="val green">Rs {{ number_format($grandTotal, 2) }}</div></div>
    <div class="summary-box"><div class="lbl">Staff Count</div><div class="val">{{ number_format($staffData->count()) }}</div></div>
</div>

<div class="section-title">Staff-wise Collection</div>
<table>
    <thead>
        <tr>
            <th style="width:3%">#</th>
            <th style="width:16%">Staff Name</th>
            <th style="width:9%">Designation</th>
            <th style="width:6%" class="c">Receipts</th>
            <th style="width:9%" class="r">Cash</th>
            <th style="width:9%" class="r">UPI</th>
            <th style="width:9%" class="r">Online</th>
            <th style="width:9%" class="r">Cheque</th>
            <th style="width:8%" class="r">DD</th>
            <th style="width:8%" class="r">NEFT</th>
            <th style="width:8%" class="r">RTGS</th>
            <th style="width:10%" class="r">Total (Rs)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($staffData as $i => $row)
        <tr>
            <td class="c">{{ $i + 1 }}</td>
            <td><strong>{{ $row['staff']?->name ?? 'Unknown Staff' }}</strong></td>
            <td>{{ $row['staff']?->designation ?? '' }}</td>
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
        <tr><td colspan="12" class="c">No staff collections found for this date range.</td></tr>
        @endforelse
    </tbody>
    @if($staffData->isNotEmpty())
    <tfoot>
        <tr>
            <td colspan="3" class="r">Grand Total</td>
            <td class="c">{{ $grandCount }}</td>
            <td class="r">{{ number_format($staffData->sum('cash'), 2) }}</td>
            <td class="r">{{ number_format($staffData->sum('upi'), 2) }}</td>
            <td class="r">{{ number_format($staffData->sum('online'), 2) }}</td>
            <td class="r">{{ number_format($staffData->sum('cheque'), 2) }}</td>
            <td class="r">{{ number_format($staffData->sum('dd'), 2) }}</td>
            <td class="r">{{ number_format($staffData->sum('neft'), 2) }}</td>
            <td class="r">{{ number_format($staffData->sum('rtgs'), 2) }}</td>
            <td class="r green">{{ number_format($grandTotal, 2) }}</td>
        </tr>
    </tfoot>
    @endif
</table>

{{-- Bank-wise breakdown fixed at the bottom, after the staff list --}}
<div class="bank-section">
    <div class="section-title">Bank-wise Collection — Staff Breakdown</div>
    <table>
        <thead>
            <tr>
                <th style="width:40%">Bank / Account</th>
                <th style="width:30%">Staff Name</th>
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
            @foreach(($bankDetailWise[$bw['label']] ?? []) as $sr)
            <tr>
                <td></td>
                <td>{{ $sr['staff'] }}</td>
                <td class="c">{{ $sr['count'] }}</td>
                <td class="r">{{ number_format($sr['total'], 2) }}</td>
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
