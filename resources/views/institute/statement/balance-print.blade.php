<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Balance Receipt â€” {{ $student->name }}</title>
    @php
        $isThermal   = $printMode === 'thermal';
        // Overall due = sirf last (current) session ka due â€” double count avoid
        $overallDue  = $balances->last()['due'] ?? 0;
        $overallPaid = $balances->sum('paid');
        $overallFine = $balances->sum('fine');
        $currentYearLabel = \App\Support\AcademicState::yearLabel(
            $student->stream?->course?->structure_type,
            $student->current_semester,
            $student->coursePart?->year_number,
            $student->stream?->course?->effectiveSemestersPerYear() ?? 0
        );
        $instituteAddress = trim(collect([
            $institute->address ?? null,
            $institute->city ?? null,
            $institute->state ?? null,
            $institute->pincode ?? null,
        ])->filter()->implode(', '));
    @endphp
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        @if($isThermal)
        @page {
            size: 80mm auto;
            margin: 0mm;
        }
        html, body {
            width: 80mm;
            max-width: 80mm;
            margin: 0;
            padding: 0 3mm;
            font-family: Verdana, sans-serif;
            font-size: 9px;
            font-weight: 600;
            background: #fff;
            color: #000;
            line-height: 1.18;
        }
        #thermal-receipt {
            padding-top: 1mm;
            padding-bottom: 0.5mm;
            break-inside: avoid;
            page-break-inside: avoid;
            page-break-after: avoid;
        }
        @media print {
            @page { size: 80mm auto; margin: 0mm; }
            html, body {
                width: 80mm;
                max-width: 80mm;
                min-height: 0 !important;
                height: auto !important;
                padding: 0 3mm;
                overflow: visible !important;
                font-size: 9px;
                font-weight: 600;
            }
        }
        @else
        @page {
            size: A4 portrait;
            margin: 10mm 12mm;
        }
        html, body {
            width: 210mm;
            margin: 0;
            padding: 8mm 12mm;
            font-family: Arial, 'Segoe UI', sans-serif;
            font-size: 12px;
            background: #fff;
            color: #000;
        }
        @media print {
            @page { size: A4 portrait; margin: 10mm 12mm; }
        }
        @endif

        .center { text-align: center; }
        .right   { text-align: right; }
        .bold    { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 2px 0; }
        .divider-solid { border-top: 1.5px solid #000; margin: 2px 0; }
        .inst-name  { font-size: {{ $isThermal ? '11.5px' : '18px' }}; font-weight: {{ $isThermal ? '700' : '900' }}; }
        .inst-addr  { font-size: {{ $isThermal ? '7.5px' : '11px' }}; font-weight: {{ $isThermal ? '600' : '500' }}; margin-top: 0; }
        .rec-title  { font-size: {{ $isThermal ? '9.5px' : '14px' }}; font-weight: 800; margin: {{ $isThermal ? '2px 0' : '3px 0' }}; {{ $isThermal ? 'border:1px solid #000;padding:1px 2px;text-align:center;' : 'letter-spacing:0.4px;' }} }
        .kv {
            display: flex;
            justify-content: space-between;
            padding: {{ $isThermal ? '0.5px 0' : '3px 0' }};
            font-size: {{ $isThermal ? '8.8px' : '11px' }};
            font-weight: {{ $isThermal ? '700' : 'normal' }};
            line-height: {{ $isThermal ? '1.18' : '1.45' }};
            margin-bottom: {{ $isThermal ? '1px' : '0' }};
        }
        .kv .lbl { {{ $isThermal ? 'white-space:nowrap;min-width:24mm;flex:0 0 24mm;' : 'flex:1;' }} }
        .kv .val { text-align: right; max-width: {{ $isThermal ? '43mm' : '58%' }}; font-weight: {{ $isThermal ? '700' : '800' }}; word-break: normal; overflow-wrap: anywhere; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
            font-size: {{ $isThermal ? '9px' : '11px' }};
            font-weight: {{ $isThermal ? '600' : 'normal' }};
            table-layout: fixed;
        }
        th {
            border-bottom: 2px solid #000;
            font-weight: 800;
            padding: {{ $isThermal ? '2px 2px' : '4px 3px' }};
            text-align: left;
        }
        td { padding: {{ $isThermal ? '2px 2px' : '3px 3px' }}; }
        .tr { text-align: right; }
        .tfoot-row td { border-top: 2px solid #000; font-weight: 800; padding-top: 3px; }
        .total-bal {
            font-size: {{ $isThermal ? '11.5px' : '18px' }};
            font-weight: 900;
            text-align: center;
            padding: {{ $isThermal ? '2px 0 1px' : '4px 0 2px' }};
        }
        .qr-code {
            display: block;
            width: {{ $isThermal ? '28mm' : '32mm' }};
            height: {{ $isThermal ? '28mm' : '32mm' }};
            margin: {{ $isThermal ? '2mm auto 0' : '4mm auto 0' }};
            image-rendering: pixelated;
            image-rendering: crisp-edges;
        }
        .qr-caption {
            text-align: center;
            font-size: {{ $isThermal ? '8px' : '9px' }};
            color: #000;
            margin-top: 1mm;
        }

        @if(!$isThermal)
        .page { display: grid; grid-template-columns: 1fr 1fr; gap: 10mm; }
        .box  { border: 1px solid #ccc; padding: 6mm; }
        .copy-lbl {
            font-size: 9px;
            text-align: center;
            color: #666;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 3px;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        @endif
    </style>
</head>
<body>

@if($isThermal)<div id="thermal-receipt">@endif

@php $copies = $isThermal ? [''] : ['Student Copy', 'Office Copy']; @endphp

@if(!$isThermal)<div class="page">@endif

@foreach($copies as $copy)
@if(!$isThermal)<div class="box">@endif
@if(!$isThermal)<div class="copy-lbl">â€” {{ $copy }} â€”</div>@endif

<div class="center">
    <div class="inst-name">{{ $institute->name ?? 'Institute' }}</div>
    @if($instituteAddress !== '')
        <div class="inst-addr">{{ $instituteAddress }}</div>
    @endif
    @if($institute->mobile ?? null)
        <div class="inst-addr">Ph: {{ $institute->mobile }}</div>
    @endif
    <div class="divider-solid"></div>
    <div class="rec-title">Fee Balance Receipt ({{ $student->session->name ?? '' }})</div>
    <div class="divider-solid"></div>
</div>

@php
    $identity = $student->currentAcademicIdentity ?? null;
    $formNo = $identity?->institute_form_no_snapshot ?? $identity?->form_no ?? $student->institute_form_no ?? null;
    $rollNo = $identity?->roll_no_snapshot ?? $identity?->roll_no ?? $student->roll_no ?? null;
    $uinNo = $identity?->uin_no_snapshot ?? $student->uin_no ?? null;
    $enrollNo = $identity?->enrollment_no_snapshot ?? $student->enrollment_no ?? null;
@endphp

@if($formNo)<div class="kv"><span class="lbl">Form No:</span><span class="val">{{ $formNo }}</span></div>@endif
<div class="kv"><span class="lbl">Application No:</span><span class="val">{{ $student->student_uid }}</span></div>
@if($rollNo)<div class="kv"><span class="lbl">Roll No:</span><span class="val">{{ $rollNo }}</span></div>@endif
@if($uinNo)<div class="kv"><span class="lbl">UIN:</span><span class="val">{{ $uinNo }}</span></div>@endif
@if($enrollNo)<div class="kv"><span class="lbl">Enroll No:</span><span class="val">{{ $enrollNo }}</span></div>@endif
<div class="kv"><span class="lbl">Student Name:</span><span class="val">{{ $student->name }}</span></div>
<div class="kv"><span class="lbl">Father Name:</span><span class="val">{{ $student->father_name ?? '—' }}</span></div>
<div class="kv"><span class="lbl">Course:</span><span class="val">{{ $student->stream->course->name ?? '—' }}</span></div>
<div class="kv"><span class="lbl">Year:</span><span class="val">{{ $currentYearLabel }}</span></div>
<div class="kv"><span class="lbl">Session:</span><span class="val">{{ $student->session->name ?? '—' }}</span></div>

<div class="divider-solid"></div>
<div class="total-bal">Total Balance: {{ number_format($overallDue, 0) }}</div>
<div class="divider-solid"></div>
<div class="kv"><span class="lbl">Print Date:</span><span class="val">{{ now()->setTimezone('Asia/Kolkata')->format('d-M-Y h:i A') }}</span></div>
@if(isset($printedBy) && $printedBy)
<div class="kv"><span class="lbl">Printed By:</span><span class="val">{{ $printedBy }}</span></div>
@endif
<div class="divider-solid"></div>

@if(!$isThermal)</div>@endif
@endforeach

@if(!$isThermal)</div>@endif
@if($isThermal)</div>@endif

<script>

@if($isThermal)
window.onload = function() {
    applyThermalPage();
    @if($autoprint ?? true) setTimeout(printWithoutBrowserTitle, 300); @endif
};
@else
window.onload = function() {
    @if($autoprint ?? true) setTimeout(function(){ window.print(); }, 400); @endif
};
@endif

function applyThermalPage() {
    var old = document.getElementById('thermal-page-style');
    if (old) old.remove();

    var probe = document.createElement('div');
    probe.style.cssText = 'position:absolute;left:-9999px;top:0;width:10mm;height:10mm;visibility:hidden;';
    document.body.appendChild(probe);
    var pxPerMm = probe.getBoundingClientRect().height / 10;
    probe.remove();

    var receipt = document.getElementById('thermal-receipt');
    var contentPx = receipt ? receipt.getBoundingClientRect().height : document.body.scrollHeight;
    var heightMm = Math.max(40, Math.ceil(contentPx / pxPerMm) + 6);

    var style = document.createElement('style');
    style.id = 'thermal-page-style';
    style.innerHTML =
        '@page { size: 80mm ' + heightMm + 'mm !important; margin: 0 !important; }' +
        '@media print {' +
        '  html, body {' +
        '    width: 80mm !important;' +
        '    height: auto !important;' +
        '    min-height: 0 !important;' +
        '    margin: 0 !important;' +
        '    overflow: visible !important;' +
        '    break-after: avoid !important;' +
        '    page-break-after: avoid !important;' +
        '  }' +
        '  #thermal-receipt {' +
        '    height: auto !important;' +
        '    min-height: 0 !important;' +
        '    margin: 0 !important;' +
        '    overflow: visible !important;' +
        '    break-inside: avoid !important;' +
        '    page-break-inside: avoid !important;' +
        '    page-break-after: avoid !important;' +
        '  }' +
        '}';
    document.head.appendChild(style);
}

function printWithoutBrowserTitle() {
    var oldTitle = document.title;
    document.title = '';
    window.addEventListener('afterprint', function restoreTitle() {
        document.title = oldTitle;
        window.removeEventListener('afterprint', restoreTitle);
    });
    window.print();
}
</script>
</body>
</html>
