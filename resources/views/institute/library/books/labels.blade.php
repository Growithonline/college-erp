<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book Labels — {{ $book->title }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#fff; }

        .label-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .book-label {
            border: 1px dashed #555;
            border-radius: 4px;
            padding: 10px 12px;
            font-size: 12px;
            line-height: 1.5;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .book-label .book-title {
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 1px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .book-label .book-author {
            color: #555;
            font-size: 11px;
            margin-bottom: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .book-label .meta-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #333;
            margin-top: 2px;
        }

        .barcode-wrap {
            text-align: center;
            margin: 6px 0 4px;
        }

        .barcode-wrap svg {
            max-width: 100%;
            height: 52px;
        }

        @media print {
            @page { margin: 10mm; size: A4; }
            body { background: #fff !important; }
            .no-print { display: none !important; }
            .label-grid { gap: 8px; }
            .book-label { border-color: #333; }
            .barcode-wrap svg { height: 48px; }
        }
    </style>
</head>
<body>
<div class="container py-3">

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <div>
            <h5 class="mb-0 fw-bold">Book Copy Labels</h5>
            <small class="text-muted">{{ $book->title }} &mdash; {{ $book->copies->count() }} copies</small>
        </div>
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Print
        </button>
    </div>

    <div class="label-grid">
        @foreach($book->copies as $copy)
            @php
                $barcodeValue = $copy->barcode ?: $copy->accession_no;
            @endphp
            <div class="book-label">
                <div class="book-title" title="{{ $book->title }}">{{ $book->title }}</div>
                <div class="book-author">{{ $book->authors->pluck('name')->implode(', ') ?: ($book->author_text ?: '—') }}</div>

                <div class="barcode-wrap">
                    <svg class="barcode-svg"
                         data-value="{{ $barcodeValue }}"
                         data-accession="{{ $copy->accession_no }}">
                    </svg>
                </div>

                <div class="meta-row">
                    <span><strong>Acc No:</strong> {{ $copy->accession_no }}</span>
                    <span><strong>Rack:</strong> {{ $copy->rack->display_name ?? '—' }}</span>
                </div>
                <div class="meta-row">
                    <span><strong>Subject:</strong> {{ $book->subject->name ?? ($book->subject_name ?: '—') }}</span>
                    @if($copy->barcode && $copy->barcode !== $copy->accession_no)
                        <span><strong>BC:</strong> {{ $copy->barcode }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
document.querySelectorAll('.barcode-svg').forEach(function (svg) {
    var value = svg.getAttribute('data-value');
    try {
        JsBarcode(svg, value, {
            format:       'CODE128',
            width:        1.6,
            height:       48,
            displayValue: true,
            fontSize:     11,
            textMargin:   3,
            margin:       4,
            background:   '#ffffff',
            lineColor:    '#000000',
        });
    } catch (e) {
        // Fallback: show accession number as text if barcode generation fails
        svg.outerHTML = '<div style="text-align:center;font-family:monospace;font-size:14px;font-weight:700;letter-spacing:2px;padding:8px 0;">' +
                        svg.getAttribute('data-accession') + '</div>';
    }
});
</script>
</body>
</html>
