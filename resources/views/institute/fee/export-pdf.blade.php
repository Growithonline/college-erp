<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Collection Report</title>
    <style>
        @page { size: A4 landscape; margin: 7mm 6mm 7mm 6mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 7.5px; color: #000; margin:0; padding:0; line-height:1.2; }

        /* ── Header ── */
        .hdr { display:table; width:100%; border-bottom:2px solid #000; padding-bottom:4px; margin-bottom:4px; }
        .hdr-l, .hdr-m, .hdr-r { display:table-cell; vertical-align:middle; }
        .hdr-l { width:42px; padding-right:6px; }
        .logo-box {
            width:36px; height:36px; border:1.5px solid #000; border-radius:4px;
            text-align:center; line-height:36px; font-size:14px; font-weight:900;
            color:#000; overflow:hidden; background:#f0f0f0;
        }
        .logo-box img { width:36px; height:36px; object-fit:cover; border-radius:4px; display:block; }
        .inst-name { font-size:14px; font-weight:900; color:#000; }
        .inst-sub  { font-size:7.5px; color:#000; font-weight:700; }
        .hdr-r { text-align:right; font-size:7px; color:#000; font-weight:600; white-space:nowrap; }
        .hdr-r div { margin-bottom:1px; }

        /* ── Summary row ── */
        .sum-bar { margin-bottom:4px; }
        .sum-bar span {
            display:inline-block; border-radius:3px; padding:2px 7px; margin-right:4px;
            font-size:7px; font-weight:800; white-space:nowrap; border:1px solid #000; color:#000;
        }
        .s-green  { background:#c8f5d8; }
        .s-blue   { background:#c8e0ff; }
        .s-orange { background:#ffe5c0; }
        .s-red    { background:#ffd0d0; }

        /* ── Mode breakdown inline badges ── */
        .mb {
            display:inline-block; border-radius:3px; padding:1px 5px; margin-right:3px;
            font-size:6.5px; font-weight:800; white-space:nowrap; border:1px solid #aaa; color:#000;
        }
        .m-cash   { background:#c8f5d8; }
        .m-upi    { background:#c8e0ff; }
        .m-online { background:#c0f5f5; }
        .m-cheque { background:#fff3c0; }
        .m-dd     { background:#e8e8e8; }
        .m-neft   { background:#e8d8ff; }
        .m-rtgs   { background:#ffd8f0; }

        /* ── Table ── */
        table.t { width:100%; border-collapse:collapse; table-layout:fixed; }
        table.t thead th {
            background:#1e3a5f; color:#fff; font-size:6.5px; font-weight:800;
            padding:2px 2px; text-align:left; white-space:nowrap; overflow:hidden;
        }
        table.t thead th.r { text-align:right; }
        table.t thead th.c { text-align:center; }
        table.t tbody td {
            padding:2px 2px; font-size:6.5px; font-weight:600; color:#000;
            border-bottom:1px solid #bbb; vertical-align:middle;
            overflow:hidden; white-space:nowrap;
        }
        table.t tbody tr:nth-child(even) { background:#f0f0f0; }
        table.t tbody tr.cx { background:#ffe0e0 !important; }
        table.t tfoot td {
            background:#d0d8e8; font-weight:800; font-size:7.5px; color:#000;
            padding:3px; border-top:2px solid #555;
        }
        .r   { text-align:right; }
        .c   { text-align:center; }
        .fw  { font-weight:800; }
        .g   { color:#000; font-weight:800; }
        .rd  { color:#000; font-weight:800; }
        .or  { color:#000; font-weight:800; }

        /* ── Footer ── */
        .ftr { margin-top:4px; border-top:1px solid #000; padding-top:3px;
               display:table; width:100%; }
        .ftr-l, .ftr-r { display:table-cell; font-size:6.5px; color:#000; font-weight:600; }
        .ftr-r { text-align:right; }

        @media print {
            body { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        }
    </style>
</head>
<body>

@php
    /* Logo: try storage/ prefix first (Laravel disk pattern), then direct public path */
    $logoUrl = null;
    if (!empty($institute->image)) {
        if (file_exists(public_path('storage/' . $institute->image))) {
            $logoUrl = asset('storage/' . $institute->image);
        } elseif (file_exists(public_path($institute->image))) {
            $logoUrl = asset($institute->image);
        }
    }
    $initials = strtoupper(substr($institute->short_name ?: $institute->name, 0, 2));
@endphp

{{-- ── HEADER ─────────────────────────────────────────────────── --}}
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
        <div class="inst-name">{{ $institute->name }}</div>
        <div class="inst-sub">Fee Collection Report</div>
    </div>
    <div class="hdr-r">
        <div>Session: <strong>{{ $sessionObj?->name ?? 'All Sessions' }}</strong></div>
        <div>Period: <strong>{{ $dateRange }}</strong></div>
        <div>Invoices: <strong>{{ $totalInvoices }}</strong> &nbsp;|&nbsp; Generated: <strong>{{ now()->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</strong></div>
    </div>
</div>

{{-- ── SUMMARY + MODE BAR ──────────────────────────────────────── --}}
@php
    $activeInvoices = $invoices->where('is_cancelled', false);
    $totalFine      = $activeInvoices->sum(fn($i) => $i->items->sum('fine'));
    $totalDiscount  = $activeInvoices->sum('discount');
    $modeCssMap = ['cash'=>'m-cash','upi'=>'m-upi','online'=>'m-online','cheque'=>'m-cheque','dd'=>'m-dd','neft'=>'m-neft','rtgs'=>'m-rtgs'];
@endphp
<div class="sum-bar">
    <span class="s-green">&#x25CF; Collected: Rs {{ number_format($totalPaid, 0) }}</span>
    <span class="s-blue">Active: {{ $activeInvoices->count() }}</span>
    @if($cancelledCount > 0)<span class="s-red">Cancelled: {{ $cancelledCount }}</span>@endif
    @if($totalDiscount > 0)<span class="s-orange">Discount: Rs {{ number_format($totalDiscount, 0) }}</span>@endif
    @if($totalFine > 0)<span class="s-orange">Fine: Rs {{ number_format($totalFine, 0) }}</span>@endif
    @if($modeWise->isNotEmpty())
        &nbsp;&nbsp;
        @foreach($modeWise as $mode => $data)
            <span class="mb {{ $modeCssMap[$mode] ?? '' }}">{{ strtoupper($mode) }}: {{ $data['count'] }} &mdash; Rs {{ number_format($data['amount'], 0) }}</span>
        @endforeach
    @endif
</div>

{{-- ── TABLE ───────────────────────────────────────────────────── --}}
@php
    $activePaid = 0; $activeFine = 0; $activeDisc = 0; $activeDue = 0;
@endphp
<table class="t" cellspacing="0" cellpadding="0">
    <thead>
        <tr>
            <th class="c" style="width:13px;">#</th>
            <th style="width:46px;">Invoice No</th>
            <th style="width:33px;">Date</th>
            <th style="width:86px;">Student</th>
            <th style="width:50px;">Student ID</th>
            <th style="width:66px;">Course / Year</th>
            <th style="width:40px;">Father Name</th>
            <th style="width:56px;">Fee Items</th>
            <th style="width:44px;">Txn Ref / Bank</th>
            <th style="width:40px;">Collected By</th>
            <th style="width:28px;">Mode</th>
            <th class="r" style="width:33px;">Collected</th>
            <th class="r" style="width:22px;">Fine</th>
            <th class="r" style="width:26px;">Discount</th>
            <th class="r" style="width:26px;">Due</th>
            <th class="r" style="width:33px;">Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($invoices as $i => $inv)
        @php
            $student  = $inv->student;
            $fine     = $inv->items->sum('fine');
            $discount = $inv->discount ?? 0;
            $total    = $inv->paid_amount + $discount;
            $due      = max(0, $inv->items->sum('total_fee') - $inv->paid_amount - $discount);
            $courseLine = implode(' · ', array_filter([
                $student?->stream?->course?->name,
                $student?->stream?->name,
                $student?->coursePart?->year_label,
                $inv->semester ? 'S'.$inv->semester : null,
            ]));
            if (!$inv->is_cancelled) {
                $activePaid += $inv->paid_amount;
                $activeFine += $fine;
                $activeDisc += $discount;
                $activeDue  += $due;
            }
        @endphp
        <tr class="{{ $inv->is_cancelled ? 'cx' : '' }}">
            <td class="c" style="color:#000;">{{ $i + 1 }}</td>
            <td style="font-weight:800; color:#000;">
                {{ $inv->invoice_no }}@if($inv->is_cancelled) <span style="font-size:5.5px;">[X]</span>@endif
            </td>
            <td style="color:#000;">{{ $inv->payment_date?->format('d/m/Y') }}</td>
            <td class="fw">{{ Str::limit($student?->name ?? '—', 24) }}</td>
            <td style="color:#000;">{{ $student?->student_uid ?? '—' }}</td>
            <td style="color:#000;">{{ Str::limit($courseLine, 25) }}</td>
            <td style="color:#000;">{{ Str::limit($student?->father_name ?? '—', 15) }}</td>
            <td style="color:#000;">{{ Str::limit($inv->items->pluck('fee_name')->implode(', '), 26) ?: '—' }}</td>
            <td style="color:#000;">
                {{ Str::limit($inv->transaction_ref ?? '—', 12) }}@if($inv->bank_name) /{{ Str::limit($inv->bank_name, 6) }}@endif
            </td>
            <td style="color:#000;">{{ Str::limit($inv->collected_by ?? '—', 14) }}</td>
            <td>
                <span class="mb {{ $modeCssMap[$inv->payment_mode] ?? '' }}">{{ strtoupper($inv->payment_mode) }}</span>
            </td>
            <td class="r fw">{{ number_format($inv->paid_amount, 0) }}</td>
            <td class="r fw">{{ $fine > 0 ? number_format($fine, 0) : '—' }}</td>
            <td class="r fw">{{ $discount > 0 ? number_format($discount, 0) : '—' }}</td>
            <td class="r fw" style="{{ $due > 0 ? 'color:#c0392b;' : '' }}">{{ $due > 0 ? number_format($due, 0) : '—' }}</td>
            <td class="r fw">{{ number_format($total, 0) }}</td>
        </tr>
        @empty
        <tr><td colspan="16" style="text-align:center; padding:10px; color:#000; font-weight:600;">No records found.</td></tr>
        @endforelse
    </tbody>
    @if($invoices->count() > 0)
    <tfoot>
        <tr>
            <td colspan="11" class="r" style="font-size:7px; color:#000;">
                TOTAL &mdash; {{ $activeInvoices->count() }} active invoice(s)
            </td>
            <td class="r fw">{{ number_format($activePaid, 0) }}</td>
            <td class="r fw">{{ $activeFine > 0 ? number_format($activeFine, 0) : '—' }}</td>
            <td class="r fw">{{ $activeDisc > 0 ? number_format($activeDisc, 0) : '—' }}</td>
            <td class="r fw" style="{{ $activeDue > 0 ? 'color:#c0392b;' : '' }}">{{ $activeDue > 0 ? number_format($activeDue, 0) : '—' }}</td>
            <td class="r fw">{{ number_format($activePaid + $activeDisc, 0) }}</td>
        </tr>
    </tfoot>
    @endif
</table>

{{-- ── FOOTER ──────────────────────────────────────────────────── --}}
<div class="ftr">
    <div class="ftr-l">{{ $institute->name }} &mdash; Fee Collection Report &mdash; Confidential</div>
    <div class="ftr-r">Generated: {{ now()->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
</div>

<script>window.onload = function(){ window.print(); };</script>
</body>
</html>
