<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bank-wise Income Report {{ $filters['date_from'] }}</title>
@php
    // College logo (embedded so it also works in the PDF)
    $logoData = null;
    try {
        if (!empty($institute?->image) && \Illuminate\Support\Facades\Storage::disk('public')->exists($institute->image)) {
            $logoPath = \Illuminate\Support\Facades\Storage::disk('public')->path($institute->image);
            $logoData = 'data:' . (mime_content_type($logoPath) ?: 'image/png') . ';base64,' . base64_encode(file_get_contents($logoPath));
        }
    } catch (\Throwable $e) {
        $logoData = null;
    }
    $from = \Carbon\Carbon::parse($filters['date_from']);
    $to   = \Carbon\Carbon::parse($filters['date_to']);
    $period = $from->isSameDay($to) ? $from->format('d M Y') : $from->format('d M Y') . ' to ' . $to->format('d M Y');
    $bankFilter = $filters['bank'] === '' ? 'All banks'
        : ($filters['bank'] === 'cash' ? 'Cash only'
            : ($filters['bank'] === 'none' ? 'Bank not recorded'
                : ($bankList->firstWhere('id', (int) $filters['bank'])?->display_label ?: $bankList->firstWhere('id', (int) $filters['bank'])?->bank_name)));
@endphp
<style>
    @page { size: A4 {{ $orientation ?? 'portrait' }}; margin: 8mm; }
    * { box-sizing: border-box; }
    html, body { width: 100%; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #000; margin: 0; padding-right: 2px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .head h1 { margin: 0; font-size: 16px; }
    .head .meta { font-size: 10px; margin-top: 2px; }
    .bw-title { margin: 10px 0 4px; font-weight: 700; font-size: 11.5px; }
    /* Each cell draws its own right/bottom border (separate model) — a collapsed 1px right edge can vanish when printing */
    .bw-table { border-collapse: separate; border-spacing: 0; border-top: 1px solid #000; border-left: 1px solid #000; }
    .bw-table th { background: #e2e8f0; border: 0; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2.5px 4px; font-size: 9px; text-align: left; }
    .bw-table td { border: 0; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 4px; font-size: 9px; vertical-align: top; word-wrap: break-word; overflow-wrap: anywhere; }
    .bw-table th { word-wrap: break-word; }
    /* fixed column widths so a table can never grow wider than the page */
    .bw-source th:nth-child(1) { width: 70%; }
    .bw-summary th:first-child { width: 28%; }
    .bw-detail th:nth-child(1) { width: 3%; }  .bw-detail th:nth-child(2) { width: 7%; }
    .bw-detail th:nth-child(3) { width: 12%; } .bw-detail th:nth-child(4) { width: 17%; }
    .bw-detail th:nth-child(5) { width: 21%; } .bw-detail th:nth-child(6) { width: 6%; }
    .bw-detail th:nth-child(7) { width: 11%; } .bw-detail th:nth-child(8) { width: 14%; }
    .bw-detail th:nth-child(9) { width: 9%; }
    .bw-table .r { text-align: right; white-space: nowrap; }
    .bw-table .nw { white-space: normal; }
    .bw-table tfoot td { font-weight: 700; background: #f1f5f9; }
    .bw-sub { color: #000; font-size: 8.5px; }
    .bw-group { margin-top: 12px; page-break-inside: auto; }
    .bw-group-head { background: #e2e8f0; color: #000; border: 1px solid #000; border-bottom: 0; padding: 4px 6px; font-size: 10.5px; overflow: hidden; }
    .bw-group-head span { display: inline-block; }
    .bw-group-total { float: right; font-weight: 700; }
    .bw-detail thead { display: table-header-group; }
    .bw-detail tr { page-break-inside: avoid; }
    .bw-modes { font-weight: 400; font-size: 9px; margin-left: 8px; }
    .bw-empty { margin-top: 20px; color: #000; }
    .foot { margin-top: 10px; font-size: 8.5px; color: #000; }
</style>
</head>
<body>

<table class="head-row" style="width:100%;border-collapse:collapse;"><tr>
@if($logoData)
<td style="width:42px;vertical-align:middle;padding:0 6px 0 0;border:0;"><img src="{{ $logoData }}" alt="" style="width:34px;height:34px;object-fit:contain;"></td>
@endif
<td style="vertical-align:middle;padding:0;border:0;">
<div class="head">
    <h1>{{ $institute?->name }}</h1>
    <div class="meta"><strong>Bank-wise Income Report</strong> &nbsp;·&nbsp; Period: <strong>{{ $period }}</strong>
        &nbsp;·&nbsp; {{ $bankFilter }}@if($filters['mode'] !== '') &nbsp;·&nbsp; Mode: {{ $allModes[$filters['mode']] }}@endif @if($filters['source'] !== '') &nbsp;·&nbsp; Source: {{ $allSources[$filters['source']] }}@endif
    </div>
    <div class="meta">Printed: {{ $printedAt }}</div>
</div>
</td></tr></table>

@include('institute.reports._bank-wise-body')

<div class="foot">
    Includes approved, non-cancelled fee receipts, library fine payments and other (manual) income, by receipt date. Fee amounts are the cash/bank amount received (discount excluded). Other income has no bank or payment mode recorded, so it is shown under "Bank / mode not recorded".
</div>

@if(!empty($autoPrint))
<script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
