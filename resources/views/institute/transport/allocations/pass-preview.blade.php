<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transport Pass — {{ $allocation->student?->name ?? 'Student' }}</title>
    <style>
        @include('institute.transport.allocations._pass-card-style')
        html, body { min-height: 100%; }
        body { background: #edf1f7; color: #1f2937; }
        .toolbar { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 18px 18px 8px; }
        .toolbar button, .toolbar a { border: 0; border-radius: 6px; padding: 9px 14px; font: 600 14px Arial, sans-serif; text-decoration: none; cursor: pointer; }
        .toolbar button { background: #153b86; color: #fff; }
        .toolbar a { background: #fff; color: #344054; border: 1px solid #d0d5dd; }
        .print-note { margin: 0 auto 16px; max-width: 620px; color: #475467; font: 13px Arial, sans-serif; text-align: center; }
        .preview-stage { display: flex; justify-content: center; padding: 8px 18px 32px; }
        .pass-preview { width: 486pt; height: 290pt; background: #fff; box-shadow: 0 8px 25px rgba(16, 24, 40, .18); }
        .pass-preview .card { transform: scale(2); transform-origin: top left; }
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            body { background: #fff; }
            .no-print { display: none !important; }
            .preview-stage { display: block; padding: 0; }
            .pass-preview { width: 486pt; height: 290pt; box-shadow: none; }
            .pass-preview .card { transform: scale(2); transform-origin: top left; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Print Pass</button>
        <a href="{{ route('transport.allocations.show', $allocation) }}">Back</a>
    </div>
    <p class="print-note no-print">Print settings: choose <strong>A4</strong>, use <strong>Default</strong> scale, and turn <strong>Headers and footers</strong> off under More settings.</p>
    <main class="preview-stage"><div class="pass-preview">@include('institute.transport.allocations._pass-card', ['browserPreview' => true])</div></main>
</body>
</html>