<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bank-wise Income Report {{ $filters['date_from'] }}</title>
@php
    $from = \Carbon\Carbon::parse($filters['date_from']);
    $to   = \Carbon\Carbon::parse($filters['date_to']);
    $period = $from->isSameDay($to) ? $from->format('d M Y') : $from->format('d M Y') . ' to ' . $to->format('d M Y');
    $bankFilter = $filters['bank'] === '' ? 'All banks'
        : ($filters['bank'] === 'cash' ? 'Cash only'
            : ($filters['bank'] === 'none' ? 'Bank not recorded'
                : ($bankList->firstWhere('id', (int) $filters['bank'])?->display_label ?: $bankList->firstWhere('id', (int) $filters['bank'])?->bank_name)));
@endphp
<style>
    @page { size: A4 landscape; margin: 8mm; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #000; margin: 0; }
    table { width: 100%; border-collapse: collapse; }
    .head h1 { margin: 0; font-size: 16px; }
    .head .meta { font-size: 10px; margin-top: 2px; }
    .bw-title { margin: 10px 0 4px; font-weight: 700; font-size: 11.5px; }
    .bw-table th { background: #e2e8f0; border: 1px solid #000; padding: 3px 5px; font-size: 9.5px; text-align: left; }
    .bw-table td { border: 1px solid #000; padding: 2.5px 5px; font-size: 9.5px; vertical-align: top; }
    .bw-table .r { text-align: right; white-space: nowrap; }
    .bw-table .nw { white-space: nowrap; }
    .bw-table tfoot td { font-weight: 700; background: #f1f5f9; }
    .bw-sub { color: #475569; font-size: 8.5px; }
    .bw-group { margin-top: 12px; page-break-inside: auto; }
    .bw-group-head { background: #1e293b; color: #fff; padding: 4px 6px; font-size: 10.5px; }
    .bw-group-head span { display: inline-block; }
    .bw-group-total { float: right; font-weight: 700; }
    .bw-detail thead { display: table-header-group; }
    .bw-detail tr { page-break-inside: avoid; }
    .bw-modes { font-weight: 400; font-size: 9px; margin-left: 8px; }
    .bw-empty { margin-top: 20px; color: #475569; }
    .foot { margin-top: 10px; font-size: 8.5px; color: #475569; }
</style>
</head>
<body>

<div class="head">
    <h1>{{ $institute?->name }}</h1>
    <div class="meta"><strong>Bank-wise Income Report</strong> &nbsp;·&nbsp; Period: <strong>{{ $period }}</strong>
        &nbsp;·&nbsp; {{ $bankFilter }}@if($filters['mode'] !== '') &nbsp;·&nbsp; Mode: {{ $allModes[$filters['mode']] }}@endif @if($filters['source'] !== '') &nbsp;·&nbsp; Source: {{ $allSources[$filters['source']] }}@endif
    </div>
    <div class="meta">Printed: {{ $printedAt }}</div>
</div>

@include('institute.reports._bank-wise-body')

<div class="foot">
    Includes approved, non-cancelled fee receipts, library fine payments and other (manual) income, by receipt date. Fee amounts are the cash/bank amount received (discount excluded). Other income has no bank or payment mode recorded, so it is shown under "Bank / mode not recorded".
</div>

@if(!empty($autoPrint))
<script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
