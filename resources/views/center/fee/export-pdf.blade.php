<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Collection Report</title>
    <style>
        @page { size: A4 landscape; margin: 12mm 10mm 12mm 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; margin: 0; padding: 0; }

        /* Header */
        .header { border-bottom: 2px solid #0f766e; padding-bottom: 8px; margin-bottom: 10px; }
        .header-table { width: 100%; }
        .header-table td { vertical-align: middle; }
        .logo-box { width: 52px; height: 52px; border: 1px solid #d1d5db; border-radius: 8px;
                    text-align: center; line-height: 52px; font-size: 18px; font-weight: 700; color: #0f766e; overflow: hidden; }
        .logo-box img { width: 52px; height: 52px; object-fit: cover; }
        .title { font-size: 17px; font-weight: 700; color: #0f172a; line-height: 1.2; }
        .subtitle { font-size: 10px; color: #0f766e; font-weight: 600; margin-top: 2px; }
        .meta-right { text-align: right; font-size: 9px; color: #475569; line-height: 1.7; }

        /* Summary chips */
        .summary { margin-bottom: 10px; }
        .chips-table { width: 100%; }
        .chip { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px;
                padding: 5px 8px; display: inline-block; min-width: 120px; }
        .chip-label { font-size: 8px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .chip-value { font-size: 13px; font-weight: 700; color: #065f46; margin-top: 1px; }
        .chip-sub { font-size: 8px; color: #374151; margin-top: 1px; }

        /* Main table */
        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.data thead th {
            background: #0f766e; color: #ffffff; font-size: 7.5px; font-weight: 700;
            padding: 4px 3px; text-align: left; white-space: nowrap;
        }
        table.data thead th.num { text-align: center; }
        table.data tbody td {
            padding: 3px 3px; font-size: 7.5px; border-bottom: 1px solid #e5e7eb; vertical-align: top;
        }
        table.data tbody tr:nth-child(even) { background: #f8fafc; }
        table.data tbody tr:last-child td { border-bottom: none; }
        .fw { font-weight: 700; }
        .muted { color: #6b7280; font-size: 7.5px; }
        .badge { display: inline-block; padding: 1px 5px; border-radius: 4px;
                 font-size: 7.5px; font-weight: 700; }
        .badge-cash    { background: #dcfce7; color: #166534; }
        .badge-upi     { background: #dbeafe; color: #1e40af; }
        .badge-online  { background: #e0f2fe; color: #075985; }
        .badge-cheque  { background: #fef9c3; color: #854d0e; }
        .badge-default { background: #f3f4f6; color: #374151; }
        .amount { text-align: right; font-weight: 700; color: #065f46; }
        .num { text-align: center; color: #6b7280; }

        /* Totals row */
        .total-row td { background: #ecfdf5; font-weight: 700; font-size: 9px;
                        border-top: 2px solid #0f766e; padding: 5px 4px; }

        /* Footer */
        .footer { margin-top: 8px; font-size: 8px; color: #9ca3af;
                  border-top: 1px solid #e5e7eb; padding-top: 5px;
                  display: flex; justify-content: space-between; }
        .footer-table { width: 100%; }
        .footer-table td:last-child { text-align: right; }
    </style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:60px; padding-right:10px;">
                <div class="logo-box">
                    @php
                        $logoPath = storage_path('app/public/' . $institute->image);
                    @endphp
                    @if(!empty($institute->image) && file_exists($logoPath))
                        <img src="{{ $logoPath }}" alt="Logo">
                    @else
                        {{ strtoupper(substr($institute->short_name ?: $institute->name, 0, 2)) }}
                    @endif
                </div>
            </td>
            <td>
                <div class="title">{{ $institute->name }}</div>
                <div class="subtitle">Fee Collection Report &mdash; {{ $center->name }}</div>
            </td>
            <td class="meta-right">
                <div>Session: <strong>{{ $sessionName }}</strong></div>
                <div>Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</div>
                <div>Generated: {{ now()->format('d M Y, h:i A') }}</div>
                <div>Total Records: {{ $invoices->count() }}</div>
            </td>
        </tr>
    </table>
</div>

{{-- Summary Chips --}}
@php
    $cashAmt   = $invoices->where('payment_mode', 'cash')->sum('paid_amount');
    $upiAmt    = $invoices->whereIn('payment_mode', ['upi', 'online'])->sum('paid_amount');
    $chequeAmt = $invoices->where('payment_mode', 'cheque')->sum('paid_amount');
    $otherAmt  = $invoices->whereNotIn('payment_mode', ['cash','upi','online','cheque'])->sum('paid_amount');
@endphp
<div class="summary">
    <table class="chips-table" cellpadding="0" cellspacing="4">
        <tr>
            <td>
                <div class="chip">
                    <div class="chip-label">Total Collected</div>
                    <div class="chip-value">Rs {{ number_format($totalAmt, 0) }}</div>
                    <div class="chip-sub">{{ $invoices->count() }} invoices</div>
                </div>
            </td>
            <td>
                <div class="chip">
                    <div class="chip-label">Cash</div>
                    <div class="chip-value">Rs {{ number_format($cashAmt, 0) }}</div>
                    <div class="chip-sub">{{ $invoices->where('payment_mode','cash')->count() }} receipts</div>
                </div>
            </td>
            <td>
                <div class="chip">
                    <div class="chip-label">UPI / Online</div>
                    <div class="chip-value">Rs {{ number_format($upiAmt, 0) }}</div>
                    <div class="chip-sub">{{ $invoices->whereIn('payment_mode',['upi','online'])->count() }} receipts</div>
                </div>
            </td>
            <td>
                <div class="chip">
                    <div class="chip-label">Cheque</div>
                    <div class="chip-value">Rs {{ number_format($chequeAmt, 0) }}</div>
                    <div class="chip-sub">{{ $invoices->where('payment_mode','cheque')->count() }} receipts</div>
                </div>
            </td>
            @if($otherAmt > 0)
            <td>
                <div class="chip">
                    <div class="chip-label">Other</div>
                    <div class="chip-value">Rs {{ number_format($otherAmt, 0) }}</div>
                </div>
            </td>
            @endif
        </tr>
    </table>
</div>

{{-- Data Table --}}
<table class="data" cellspacing="0" cellpadding="0">
    <thead>
        <tr>
            <th class="num" style="width:18px;">#</th>
            <th style="width:62px;">Invoice No</th>
            <th style="width:42px;">Date</th>
            <th style="width:72px;">Student Name</th>
            <th style="width:38px;">Roll No</th>
            <th style="width:58px;">UIN No</th>
            <th style="width:52px;">Father Name</th>
            <th style="width:52px;">Mother Name</th>
            <th style="width:50px;">Enroll No</th>
            <th style="width:48px;">Mobile</th>
            <th style="width:58px;">Course</th>
            <th style="width:42px;">Stream</th>
            <th style="width:20px;">Sem</th>
            <th style="width:30px;">Session</th>
            <th style="width:28px;">Mode</th>
            <th style="width:40px; text-align:right;">Total Fee</th>
            <th style="width:30px; text-align:right;">Fine</th>
            <th style="width:38px; text-align:right;">Discount</th>
            <th style="width:38px; text-align:right;">Due</th>
            <th style="width:55px;">Collected By</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoices as $i => $inv)
        @php
            $st        = $inv->student;
            $mode      = strtolower($inv->payment_mode ?? '');
            $fineTotal = $inv->items->sum('fine');
            $wallet    = $st?->wallets->firstWhere('academic_session_id', $inv->academic_session_id);
            $due       = $wallet && $wallet->main_b < 0 ? abs((float) $wallet->main_b) : 0;
            $badgeClass = match($mode) {
                'cash'   => 'badge-cash',
                'upi'    => 'badge-upi',
                'online' => 'badge-online',
                'cheque' => 'badge-cheque',
                default  => 'badge-default',
            };
        @endphp
        <tr>
            <td class="num">{{ $i + 1 }}</td>
            <td class="fw" style="font-size:7px;">{{ $inv->invoice_no }}</td>
            <td>{{ $inv->payment_date?->format('d M Y') }}</td>
            <td>
                <div class="fw">{{ $st?->name ?? '—' }}</div>
            </td>
            <td class="muted">{{ $st?->roll_no ?: '—' }}</td>
            <td class="muted">{{ $st?->student_uid ?? '—' }}</td>
            <td>{{ $st?->father_name ?? '—' }}</td>
            <td>{{ $st?->mother_name ?? '—' }}</td>
            <td class="muted">{{ $st?->enrollment_no ?: '—' }}</td>
            <td class="muted">{{ $st?->mobile ?? '' }}</td>
            <td>{{ $st?->stream?->course?->name ?? '—' }}</td>
            <td class="muted">{{ $st?->stream?->name ?? '—' }}</td>
            <td class="num muted">{{ $inv->semester ? 'S'.$inv->semester : '—' }}</td>
            <td class="muted">{{ $inv->session?->name ?? '—' }}</td>
            <td><span class="badge {{ $badgeClass }}">{{ strtoupper($mode) }}</span></td>
            <td class="amount">{{ number_format($inv->paid_amount, 0) }}</td>
            <td style="text-align:right; color:{{ $fineTotal > 0 ? '#dc2626' : '#9ca3af' }};">
                {{ $fineTotal > 0 ? number_format($fineTotal, 0) : '—' }}
            </td>
            <td style="text-align:right; color:{{ $inv->discount > 0 ? '#d97706' : '#9ca3af' }}; font-weight:{{ $inv->discount > 0 ? '700' : '400' }};">
                {{ $inv->discount > 0 ? '-'.number_format($inv->discount, 0) : '—' }}
            </td>
            <td style="text-align:right; color:{{ $due > 0 ? '#dc2626' : '#9ca3af' }}; font-weight:{{ $due > 0 ? '700' : '400' }};">
                {{ $due > 0 ? number_format($due, 0) : '—' }}
            </td>
            <td class="muted">{{ $inv->collected_by ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
    <tr class="total-row">
        <td colspan="19" style="text-align:right; padding-right:6px;">Grand Total</td>
        <td class="amount">{{ number_format($totalAmt, 0) }}</td>
    </tr>
</table>

{{-- Footer --}}
<div style="margin-top:8px;">
    <table class="footer-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="font-size:8px; color:#9ca3af;">
                {{ $institute->name }} &mdash; {{ $center->name }} | Fee Collection Report
            </td>
            <td style="text-align:right; font-size:8px; color:#9ca3af;">
                Generated on {{ now()->format('d M Y, h:i A') }}
            </td>
        </tr>
    </table>
</div>

</body>
</html>
