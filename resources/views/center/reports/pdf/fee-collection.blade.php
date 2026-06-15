<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Fee Collection Report</title>
<style>
    @page { size: A4 landscape; margin: 10mm 8mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #1f2937; margin:0; padding:0; }
    .header { border-bottom: 2.5px solid #d97706; padding-bottom: 7px; margin-bottom: 8px; }
    .header-table { width:100%; }
    .header-table td { vertical-align:middle; }
    .logo-box { width:46px; height:46px; border:1px solid #fde68a; border-radius:8px;
                text-align:center; line-height:46px; font-size:15px; font-weight:800; color:#d97706; }
    .logo-box img { width:46px; height:46px; object-fit:cover; border-radius:8px; }
    .report-title { font-size:17px; font-weight:800; color:#0f172a; }
    .report-sub   { font-size:9px; color:#b45309; font-weight:600; margin-top:2px; }
    .meta-right   { text-align:right; font-size:8px; color:#475569; line-height:1.9; }
    .meta-right strong { color:#0f172a; }

    .filter-bar { background:#fffbeb; border:1px solid #fde68a; border-radius:5px;
                  padding:5px 8px; margin-bottom:8px; }
    .f-chip { display:inline; font-size:7.5px; color:#92400e; margin-right:12px; }
    .f-chip strong { color:#78350f; }

    .s-chip { display:inline-block; background:#fff; border:1px solid #e2e8f0;
              border-radius:5px; padding:4px 10px; margin-right:6px; }
    .s-chip .lbl { font-size:7px; color:#94a3b8; text-transform:uppercase; letter-spacing:.4px; }
    .s-chip .val { font-size:13px; font-weight:800; }

    table.data { width:100%; border-collapse:collapse; }
    table.data thead th {
        background:#b45309; color:#fff; font-size:7.5px; font-weight:700;
        padding:4.5px 4px; text-align:left; white-space:nowrap; letter-spacing:.2px;
    }
    table.data tbody td { padding:3.5px 4px; font-size:8px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
    table.data tbody tr:nth-child(even) { background:#fffbeb; }
    .num { text-align:center; color:#94a3b8; }
    .fw  { font-weight:700; color:#0f172a; }
    .muted { color:#6b7280; font-size:7.5px; }
    .inv { background:#fffbeb; color:#92400e; border:1px solid #fde68a;
           border-radius:3px; padding:1px 4px; font-size:7.5px; font-weight:700; }
    .mode-badge { display:inline-block; padding:1.5px 5px; border-radius:3px; font-size:7px; font-weight:700; }
    .m-cash   { background:#dcfce7; color:#166534; }
    .m-upi    { background:#eff6ff; color:#1d4ed8; }
    .m-online { background:#e0f2fe; color:#0369a1; }
    .m-cheque { background:#fef3c7; color:#92400e; }
    .m-dd     { background:#f3f4f6; color:#374151; }
    .m-neft   { background:#f5f3ff; color:#5b21b6; }
    .m-rtgs   { background:#f5f3ff; color:#5b21b6; }
    .amount   { font-weight:800; color:#065f46; text-align:right; }

    tfoot td { background:#fef3c7; font-weight:700; font-size:8.5px; padding:5px 4px;
               border-top: 2px solid #d97706; }

    .footer-line { margin-top:7px; border-top:1px solid #e2e8f0; padding-top:5px; width:100%; }
    .footer-line td { font-size:7px; color:#94a3b8; }
    .footer-line td:last-child { text-align:right; }
</style>
</head>
<body>

<div class="header">
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:56px; padding-right:10px;">
                <div class="logo-box">
                    @if(!empty($institute->image) && file_exists(public_path($institute->image)))
                        <img src="{{ public_path($institute->image) }}" alt="">
                    @else
                        {{ strtoupper(substr($institute->short_name ?: $institute->name, 0, 2)) }}
                    @endif
                </div>
            </td>
            <td>
                <div class="report-title">Fee Collection Report</div>
                <div class="report-sub">{{ $institute->name }} &nbsp;|&nbsp; {{ $center->name }}</div>
            </td>
            <td class="meta-right">
                @php $totalAmt = $invoices->sum('paid_amount'); @endphp
                <div>Total Invoices: <strong>{{ $invoices->count() }}</strong></div>
                <div>Total Amount: <strong>Rs {{ number_format($totalAmt, 0) }}</strong></div>
                <div>Generated: <strong>{{ now()->format('d M Y, h:i A') }}</strong></div>
            </td>
        </tr>
    </table>
</div>

@if(!empty($filterSummary))
<div class="filter-bar">
    <span style="font-size:7.5px; color:#78350f; font-weight:700; margin-right:8px;">FILTERS:</span>
    @foreach($filterSummary as $label => $value)
        <span class="f-chip"><strong>{{ $label }}:</strong> {{ $value }}</span>
    @endforeach
</div>
@endif

{{-- Mode-wise summary --}}
@php
    $byMode = $invoices->groupBy('payment_mode');
    $modeColors = ['cash'=>'#dcfce7','upi'=>'#eff6ff','online'=>'#e0f2fe','cheque'=>'#fef3c7','dd'=>'#f3f4f6','neft'=>'#f5f3ff','rtgs'=>'#f5f3ff'];
@endphp
<div style="margin-bottom:8px;">
    <span class="s-chip"><span class="lbl">Total</span><br><span class="val" style="color:#0f172a;">{{ $invoices->count() }}</span></span>
    <span class="s-chip"><span class="lbl">Total Amt</span><br><span class="val" style="color:#065f46;">Rs {{ number_format($totalAmt, 0) }}</span></span>
    @foreach($byMode as $mode => $group)
    <span class="s-chip">
        <span class="lbl">{{ strtoupper($mode) }}</span><br>
        <span class="val" style="color:#b45309; font-size:10px;">
            {{ $group->count() }} &mdash; Rs {{ number_format($group->sum('paid_amount'), 0) }}
        </span>
    </span>
    @endforeach
</div>

<table class="data" cellspacing="0" cellpadding="0">
    <thead>
        <tr>
            <th class="num" style="width:18px;">#</th>
            <th style="width:68px;">Invoice No</th>
            <th style="width:50px;">Date</th>
            <th style="width:90px;">Student Name</th>
            <th style="width:52px;">Student ID</th>
            <th style="width:55px;">Mobile</th>
            <th style="width:80px;">Course</th>
            <th style="width:55px;">Stream</th>
            <th style="width:52px;">Session</th>
            <th style="width:42px;">Mode</th>
            <th style="width:55px; text-align:right;">Amount</th>
            <th style="width:80px;">Remarks</th>
        </tr>
    </thead>
    <tbody>
        @forelse($invoices as $i => $inv)
        @php
            $mode   = $inv->payment_mode ?? 'cash';
            $mCls   = 'm-' . $mode;
            $st     = $inv->student;
        @endphp
        <tr>
            <td class="num">{{ $i + 1 }}</td>
            <td><span class="inv">{{ $inv->invoice_no ?? '—' }}</span></td>
            <td class="muted">{{ $inv->payment_date?->format('d/m/Y') ?? '—' }}</td>
            <td class="fw">{{ $st?->name ?? '—' }}</td>
            <td class="muted">{{ $st?->student_uid ?? '—' }}</td>
            <td class="muted">{{ $st?->mobile ?? '—' }}</td>
            <td style="font-size:7.5px;">{{ $st?->stream?->course?->name ?? '—' }}</td>
            <td class="muted">{{ $st?->stream?->name ?? '—' }}</td>
            <td class="muted">{{ $inv->session?->name ?? '—' }}</td>
            <td><span class="mode-badge {{ $mCls }}">{{ strtoupper($mode) }}</span></td>
            <td class="amount">Rs {{ number_format($inv->paid_amount ?? 0) }}</td>
            <td class="muted">{{ $inv->remarks ?: '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="12" style="text-align:center; padding:16px; color:#94a3b8;">No records found</td></tr>
        @endforelse
    </tbody>
    @if($invoices->count())
    <tfoot>
        <tr>
            <td colspan="10" style="text-align:right; color:#78350f;">Total Amount Collected:</td>
            <td class="amount" style="font-size:10px;">Rs {{ number_format($totalAmt, 0) }}</td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>

<table class="footer-line" cellpadding="0" cellspacing="0">
    <tr>
        <td>{{ $institute->name }} &mdash; {{ $center->name }} &nbsp;|&nbsp; Fee Collection Report</td>
        <td>Generated on {{ now()->format('d M Y, h:i A') }}</td>
    </tr>
</table>

</body>
</html>
