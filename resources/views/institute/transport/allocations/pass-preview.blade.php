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
        .toolbar { display: flex; justify-content: center; gap: 8px; padding: 18px; }
        .toolbar button, .toolbar a { border: 0; border-radius: 6px; padding: 9px 14px; font: 600 14px Arial, sans-serif; text-decoration: none; cursor: pointer; }
        .toolbar button { background: #153b86; color: #fff; }
        .toolbar a { background: #fff; color: #344054; border: 1px solid #d0d5dd; }
        .preview-stage { display: flex; justify-content: center; padding: 8px 18px 32px; }
        .pass-preview { background: #fff; box-shadow: 0 8px 25px rgba(16, 24, 40, .18); }
        @media print {
            @page { size: 85.6mm 54mm; margin: 0; }
            body { background: #fff; }
            .no-print { display: none !important; }
            .preview-stage { display: block; padding: 0; }
            .pass-preview { box-shadow: none; }
            .card { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Print Pass</button>
        <a href="{{ route('transport.allocations.show', $allocation) }}">Back</a>
    </div>
    <main class="preview-stage">
        <div class="pass-preview">
            @include('institute.transport.allocations._pass-card')
        </div>
    </main>
</body>
</html>