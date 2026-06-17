<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Collection Report</title>
    <style>
        @page { size: A4 landscape; margin: 7mm 6mm 7mm 6mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 7.5px; color: #1f2937; margin:0; padding:0; line-height:1.2; }

        /* ── Header ── */
        .hdr { display:table; width:100%; border-bottom:2px solid #1e3a5f; padding-bottom:4px; margin-bottom:4px; }
        .hdr-l, .hdr-m, .hdr-r { display:table-cell; vertical-align:middle; }
        .hdr-l { width:40px; padding-right:6px; }
        .logo-box {
            width:36px; height:36px; border:1px solid #cbd5e1; border-radius:5px;
            text-align:center; line-height:36px; font-size:13px; font-weight:800;
            color:#1d4ed8; overflow:hidden; background:#eff6ff;
        }
        .logo-box img { width:36px; height:36px; object-fit:cover; border-radius:5px; }
        .inst-name { font-size:13px; font-weight:800; color:#0f172a; }
        .inst-sub  { font-size:7px; color:#1d4ed8; font-weight:700; }
        .hdr-r { text-align:right; font-size:7px; color:#475569; white-space:nowrap; }
        .hdr-r div { margin-bottom:1px; }

        /* ── Summary row ── */
        .sum-bar { margin-bottom:4px; }
        .sum-bar span {
            display:inline-block; border-radius:3px; padding:2px 7px; margin-right:4px;
            font-size:7px; font-weight:700; white-space:nowrap;
        }
        .s-green  { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
        .s-blue   { background:#dbeafe; color:#1d4ed8; border:1px solid #bfdbfe; }
        .s-orange { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
        .s-red    { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }

        /* ── Mode breakdown ── */
        .mode-bar { margin-bottom:4px; }
        .mb {
            display:inline-block; border-radius:3px; padding:1px 5px; margin-right:3px;
            font-size:6.5px; font-weight:700; white-space:nowrap;
        }
        .m-cash   { background:#dcfce7; color:#15803d; }
        .m-upi    { background:#dbeafe; color:#1d4ed8; }
        .m-online { background:#cffafe; color:#0e7490; }
        .m-cheque { background:#fef9c3; color:#a16207; }
        .m-dd     { background:#f3f4f6; color:#374151; }
        .m-neft   { background:#f3e8ff; color:#7e22ce; }
        .m-rtgs   { background:#fce7f3; color:#be185d; }

        /* ── Table ── */
        table.t { width:100%; border-collapse:collapse; table-layout:fixed; }
        table.t thead th {
            background:#1e3a5f; color:#fff; font-size:6.5px; font-weight:700;
            padding:3px 3px; text-align:left; white-space:nowrap; overflow:hidden;
        }
        table.t thead th.r { text-align:right; }
        table.t thead th.c { text-align:center; }
        table.t tbody td {
            padding:2px 3px; font-size:7px; border-bottom:1px solid #e5e7eb;
            vertical-align:middle; overflow:hidden; white-space:nowrap;
        }
        table.t tbody tr:nth-child(even) { background:#f8fafc; }
        table.t tbody tr.cx { background:#fef2f2 !important; color:#b91c1c; }
        table.t tfoot td {
            background:#e2e8f0; font-weight:700; font-size:7px;
            padding:3px; border-top:1.5px solid #94a3b8;
        }
        .r  { text-align:right; }
        .c  { text-align:center; }
        .fw { font-weight:700; }
        .g  { color:#15803d; }
        .rd { color:#dc2626; }
        .or { color:#d97706; }
        .mu { color:#9ca3af; }

        /* ── Footer ── */
        .ftr { margin-top:4px; border-top:1px solid #e2e8f0; padding-top:3px;
               display:table; width:100%; }
        .ftr-l, .ftr-r { display:table-cell; font-size:6.5px; color:#94a3b8; }
        .ftr-r { text-align:right; }

        @media print {
            body { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        }
    </style>
</head>
<body>

{{-- ── HEADER ──────────────────────────────────────────────────── --}}
<div class="hdr">
    <div class="hdr-l">
        <div class="logo-box">
            @if(!empty($institute->image) && file_exists(public_path($institute->image)))
                <img src="{{ public_path($institute->image) }}" alt="Logo">
            @else
                {{ strtoupper(substr($institute->short_name ?: $institute->name, 0, 2)) }}
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

{{-- ── SUMMARY + MODE BAR ───────────────────────────────────────── --}}
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

{{-- ── TABLE ────────────────────────────────────────────────────── --}}
@php
    $activePaid = 0; $activeFine = 0; $activeDisc = 0;
@endphp
<table class="t" cellspacing="0" cellpadding="0">
    <thead>
        <tr>
            <th class="c" style="width:16px;">#</th>
            <th style="width:56px;">Invoice No</th>
            <th style="width:44px;">Date</th>
            <th style="width:90px;">Student</th>
            <th style="width:58px;">Student ID</th>
            <th style="width:80px;">Course / Year</th>
            <th style="width:52px;">Father Name</th>
            <th style="width:72px;">Fee Items</th>
            <th style="width:56px;">Txn Ref / Bank</th>
            <th style="width:52px;">Collected By</th>
            <th style="width:38px;">Mode</th>
            <th class="r" style="width:40px;">Collected</th>
            <th class="r" style="width:30px;">Fine</th>
            <th class="r" style="width:34px;">Discount</th>
            <th class="r" style="width:40px;">Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($invoices as $i => $inv)
        @php
            $student  = $inv->student;
            $fine     = $inv->items->sum('fine');
            $discount = $inv->discount ?? 0;
            $total    = $inv->paid_amount + $discount;
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
            }
        @endphp
        <tr class="{{ $inv->is_cancelled ? 'cx' : '' }}">
            <td class="c mu">{{ $i + 1 }}</td>
            <td style="font-size:6.5px; font-weight:700; color:#334155;">
                {{ $inv->invoice_no }}
                @if($inv->is_cancelled)<span style="color:#991b1b; font-size:6px;"> ✕</span>@endif
            </td>
            <td class="mu">{{ $inv->payment_date?->format('d/m/Y') }}</td>
            <td class="fw" style="overflow:hidden;">{{ Str::limit($student?->name ?? '—', 22) }}</td>
            <td class="mu" style="font-size:6.5px;">{{ $student?->student_uid ?? '—' }}</td>
            <td class="mu" style="font-size:6.5px; overflow:hidden;">{{ Str::limit($courseLine, 28) }}</td>
            <td class="mu" style="font-size:6.5px; overflow:hidden;">{{ Str::limit($student?->father_name ?? '—', 18) }}</td>
            <td style="font-size:6.5px; overflow:hidden;">{{ Str::limit($inv->items->pluck('fee_name')->implode(', '), 30) ?: '—' }}</td>
            <td class="mu" style="font-size:6.5px; overflow:hidden;">
                {{ Str::limit($inv->transaction_ref ?? '—', 16) }}
                @if($inv->bank_name) <span style="color:#94a3b8;">/{{ Str::limit($inv->bank_name, 10) }}</span>@endif
            </td>
            <td class="mu" style="font-size:6.5px; overflow:hidden;">{{ Str::limit($inv->collected_by ?? '—', 16) }}</td>
            <td>
                <span class="mb {{ $modeCssMap[$inv->payment_mode] ?? '' }}">{{ strtoupper($inv->payment_mode) }}</span>
            </td>
            <td class="r fw g">{{ number_format($inv->paid_amount, 0) }}</td>
            <td class="r {{ $fine > 0 ? 'fw rd' : 'mu' }}">{{ $fine > 0 ? number_format($fine, 0) : '—' }}</td>
            <td class="r {{ $discount > 0 ? 'fw or' : 'mu' }}">{{ $discount > 0 ? number_format($discount, 0) : '—' }}</td>
            <td class="r fw">{{ number_format($total, 0) }}</td>
        </tr>
        @empty
        <tr><td colspan="15" style="text-align:center; padding:10px; color:#9ca3af;">No records found.</td></tr>
        @endforelse
    </tbody>
    @if($invoices->count() > 0)
    <tfoot>
        <tr>
            <td colspan="11" class="r" style="color:#475569; font-size:6.5px;">
                TOTAL &mdash; {{ $activeInvoices->count() }} active invoice(s)
            </td>
            <td class="r g">{{ number_format($activePaid, 0) }}</td>
            <td class="r {{ $activeFine > 0 ? 'rd' : 'mu' }}">{{ $activeFine > 0 ? number_format($activeFine, 0) : '—' }}</td>
            <td class="r {{ $activeDisc > 0 ? 'or' : 'mu' }}">{{ $activeDisc > 0 ? number_format($activeDisc, 0) : '—' }}</td>
            <td class="r">{{ number_format($activePaid + $activeDisc, 0) }}</td>
        </tr>
    </tfoot>
    @endif
</table>

{{-- ── FOOTER ───────────────────────────────────────────────────── --}}
<div class="ftr">
    <div class="ftr-l">{{ $institute->name }} &mdash; Fee Collection Report &mdash; Confidential</div>
    <div class="ftr-r">Generated: {{ now()->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
</div>

<script>window.onload = function(){ window.print(); };</script>
</body>
</html>
