<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Balance Receipt — {{ $student->name }}</title>
    @php
        $isThermal   = $printMode === 'thermal';
        // Overall due = sirf last (current) session ka due — double count avoid
        $overallDue  = $balances->last()['due'] ?? 0;
        $overallPaid = $balances->sum('paid');
        $overallFine = $balances->sum('fine');
        $currentYearLabel = \App\Support\AcademicState::yearLabel(
            $student->stream?->course?->structure_type,
            $student->current_semester,
            $student->coursePart?->year_number
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
            padding: 5mm 2.6mm;
            font-family: Verdana, sans-serif;
            font-size: 10px;
            font-weight: 600;
            background: #fff;
            color: #000;
            line-height: 1.3;
        }
        @media print {
            @page { size: 80mm auto; margin: 0mm; }
            html, body {
                width: 80mm;
                max-width: 80mm;
                padding: 5mm 2.6mm;
                font-size: 10px;
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
        .divider { border-top: 1px dashed #000; margin: 4px 0; }
        .divider-solid { border-top: 2px solid #000; margin: 4px 0; }
        .inst-name  { font-size: {{ $isThermal ? '13px' : '18px' }}; font-weight: {{ $isThermal ? '700' : '900' }}; }
        .inst-addr  { font-size: {{ $isThermal ? '9px' : '11px' }}; font-weight: {{ $isThermal ? '600' : '500' }}; margin-top: 1px; }
        .rec-title  { font-size: {{ $isThermal ? '11px' : '14px' }}; font-weight: 800; margin: 3px 0; {{ $isThermal ? 'border:1px solid #000;padding:2px;text-align:center;' : 'letter-spacing:0.4px;' }} }
        .kv {
            display: flex;
            justify-content: space-between;
            padding: {{ $isThermal ? '1px 0' : '3px 0' }};
            font-size: {{ $isThermal ? '10px' : '11px' }};
            font-weight: {{ $isThermal ? '600' : 'normal' }};
            line-height: {{ $isThermal ? '1.3' : '1.45' }};
            margin-bottom: {{ $isThermal ? '2px' : '0' }};
        }
        .kv .lbl { {{ $isThermal ? 'white-space:nowrap;' : 'flex:1;' }} }
        .kv .val { text-align: right; max-width: {{ $isThermal ? '44mm' : '58%' }}; font-weight: {{ $isThermal ? '600' : '800' }}; word-break: break-word; }
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
            font-size: {{ $isThermal ? '13px' : '18px' }};
            font-weight: 900;
            text-align: center;
            padding: 4px 0 2px;
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

@php $copies = $isThermal ? [''] : ['Student Copy', 'Office Copy']; @endphp

@if(!$isThermal)<div class="page">@endif

@foreach($copies as $copy)
@if(!$isThermal)<div class="box">@endif
@if(!$isThermal)<div class="copy-lbl">— {{ $copy }} —</div>@endif

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

<div class="kv"><span class="lbl">Student ID:</span><span class="val">{{ $student->student_uid }}</span></div>
<div class="kv"><span class="lbl">Name:</span><span class="val">{{ $student->name }}</span></div>
<div class="kv"><span class="lbl">Father:</span><span class="val">{{ $student->father_name ?? '—' }}</span></div>
<div class="kv"><span class="lbl">Mother:</span><span class="val">{{ $student->mother_name ?? '—' }}</span></div>
<div class="kv"><span class="lbl">Roll No:</span><span class="val">{{ $student->roll_no ?? '—' }}</span></div>
<div class="kv"><span class="lbl">UIN No:</span><span class="val">{{ $student->enrollment_no ?? '—' }}</span></div>
@if($student->mobile ?? null)
<div class="kv"><span class="lbl">Mobile:</span><span class="val">{{ $student->mobile }}</span></div>
@endif
<div class="kv"><span class="lbl">Course:</span><span class="val">{{ $student->stream->course->name ?? '—' }}</span></div>
<div class="kv"><span class="lbl">Year:</span><span class="val">{{ $currentYearLabel }}</span></div>
<div class="kv"><span class="lbl">Session:</span><span class="val">{{ $student->session->name ?? '—' }}</span></div>

<div class="divider-solid"></div>
<div class="center bold" style="font-size:{{ $isThermal ? '12px' : '13px' }};">
    {{ $currentYearLabel }} ({{ $student->session->name ?? '' }})
</div>
<div class="divider"></div>

<table>
    <thead>
        <tr>
            <th>Session</th>
            <th class="tr">Paid</th>
            <th class="tr">Fine</th>
            <th class="tr">Disc</th>
            <th class="tr">Due</th>
        </tr>
    </thead>
    <tbody>
        @foreach($balances as $b)
        <tr>
            <td>{{ $b['session']->name }}</td>
            <td class="tr">{{ number_format($b['paid'], 0) }}</td>
            <td class="tr">{{ ($b['fine'] ?? 0) > 0 ? number_format($b['fine'], 0) : '—' }}</td>
            <td class="tr">{{ $b['discount'] > 0 ? number_format($b['discount'], 0) : '—' }}</td>
            <td class="tr">{{ $b['due'] > 0 ? number_format($b['due'], 0) : '—' }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="tfoot-row">
            <td colspan="2" class="bold">Total Balance:</td>
            <td class="tr bold">{{ $overallFine > 0 ? number_format($overallFine, 0) : '—' }}</td>
            <td></td>
            <td class="tr bold">{{ number_format($overallDue, 0) }}</td>
        </tr>
    </tfoot>
</table>

<div class="divider-solid"></div>
<div class="total-bal">Total Balance: {{ number_format($overallDue, 0) }}</div>
<div class="divider-solid"></div>
<div class="kv"><span class="lbl">Number of Books Issued:</span><span class="val">0</span></div>
<div class="kv"><span class="lbl">Print Date:</span><span class="val">{{ now()->setTimezone('Asia/Kolkata')->format('d-M-Y h:i A') }}</span></div>
@if(isset($printedBy) && $printedBy)
<div class="kv"><span class="lbl">Printed By:</span><span class="val">{{ $printedBy }}</span></div>
@endif

@if(isset($receiptUrl))
<div class="divider"></div>
<img id="qr_bal_{{ $loop->index }}" style="display:block;margin:4px auto;width:{{ $isThermal ? '72px' : '96px' }};height:{{ $isThermal ? '72px' : '96px' }};" alt="QR">
<div style="text-align:center;font-size:{{ $isThermal ? '8px' : '9px' }};color:#666;margin-bottom:2px;">Scan to verify receipt</div>
@endif

@if(!$isThermal)</div>@endif
@endforeach

@if(!$isThermal)</div>@endif

@if(isset($receiptUrl))
<script src="{{ asset('js/qrcode.min.js') }}"></script>
@endif
<script>
@if(isset($receiptUrl))
function renderQR(callback) {
    var targets = document.querySelectorAll('[id^="qr_bal_"]');
    var total = targets.length;
    if (total === 0) { if (callback) callback(); return; }
    var done = 0;
    var qrSize = {{ $isThermal ? 72 : 96 }};
    targets.forEach(function(targetImg) {
        var tmp = document.createElement('div');
        tmp.style.cssText = 'position:absolute;left:-9999px;top:-9999px;';
        document.body.appendChild(tmp);
        try {
            new QRCode(tmp, {
                text: '{!! addslashes($receiptUrl) !!}',
                width: qrSize, height: qrSize,
                correctLevel: QRCode.CorrectLevel.M
            });
            var canvas = tmp.querySelector('canvas');
            if (canvas) targetImg.src = canvas.toDataURL('image/png');
        } catch(e) {}
        document.body.removeChild(tmp);
        done++;
        if (done === total && callback) callback();
    });
}
@endif

@if($isThermal)
window.onload = function() {
    @if(isset($receiptUrl))
    renderQR(function() {
        applyThermalPage();
        @if($autoprint ?? true) setTimeout(printWithoutBrowserTitle, 100); @endif
    });
    @else
    applyThermalPage();
    @if($autoprint ?? true) setTimeout(printWithoutBrowserTitle, 300); @endif
    @endif
};
@else
window.onload = function() {
    @if(isset($receiptUrl))
    renderQR(function() {
        @if($autoprint ?? true) setTimeout(function(){ window.print(); }, 100); @endif
    });
    @else
    @if($autoprint ?? true) setTimeout(function(){ window.print(); }, 400); @endif
    @endif
};
@endif

function applyThermalPage() {
    var heightMm = Math.max(90, Math.ceil(document.body.scrollHeight * 25.4 / 96) + 10);
    var style = document.createElement('style');
    style.innerHTML = '@page { size: 80mm ' + heightMm + 'mm !important; margin: 0 !important; }'
        + '@media print { html, body { width:80mm !important; height:' + heightMm + 'mm !important; margin:0 !important; overflow:hidden !important; } }';
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
