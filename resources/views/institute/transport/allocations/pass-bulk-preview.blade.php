<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transport Passes</title>
    <style>
        @include('institute.transport.allocations._pass-card-style')
        html, body { min-height: 100%; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { background: #edf1f7; }
        .toolbar { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 18px 18px 8px; }
        .toolbar button, .toolbar a { border: 0; border-radius: 6px; padding: 9px 14px; font: 600 14px Arial, sans-serif; text-decoration: none; cursor: pointer; }
        .toolbar button { background: #153b86; color: #fff; }
        .toolbar a { background: #fff; color: #344054; border: 1px solid #d0d5dd; }
        .print-note { margin: 0 auto 18px; max-width: 660px; color: #475467; font: 13px Arial, sans-serif; text-align: center; }
        .bulk-stage { display: flex; flex-wrap: wrap; justify-content: center; gap: 24px; padding: 12px 24px 36px; }
        .bulk-pass { width: 292pt; height: 174pt; background: #fff; box-shadow: 0 8px 25px rgba(16, 24, 40, .18); }
        .bulk-pass .card { transform: scale(1.2); transform-origin: top left; }
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            body { background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .bulk-stage { display: block; padding: 0; }
            .bulk-sheet { height: 277mm; display: flex; flex-direction: column; align-items: center; justify-content: space-around; page-break-after: always; }
            .bulk-sheet:last-child { page-break-after: auto; }
            .bulk-pass { width: 401pt; height: 239pt; box-shadow: none; }
            .bulk-pass .card { transform: scale(1.65); transform-origin: top left; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Print All Passes</button>
        <a href="{{ route('transport.allocations.index') }}">Back</a>
    </div>
    <p class="print-note no-print">Print settings: choose <strong>A4</strong>, use <strong>Default</strong> scale, turn <strong>Headers and footers</strong> off, and enable <strong>Background graphics</strong>.</p>
    <main class="bulk-stage">
        @foreach($passes->chunk(2) as $sheet)
            <section class="bulk-sheet">
                @foreach($sheet as $pass)
                    @php($allocation = $pass['allocation'])
                    @php($qrSvg = $pass['qrSvg'])
                    <div class="bulk-pass">@include('institute.transport.allocations._pass-card', ['browserPreview' => true])</div>
                @endforeach
            </section>
        @endforeach
    </main>
</body>
</html>