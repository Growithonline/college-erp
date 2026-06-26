<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book Labels — {{ $book->title }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background:#f8fafc; }

        .label-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .book-label {
            background: #fff;
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
            margin-bottom: 5px;
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
            margin: 5px 0 3px;
            background: #fff;
        }

        .barcode-wrap svg {
            max-width: 100%;
            height: 54px;
        }

        .no-barcode-placeholder {
            text-align: center;
            padding: 8px 0;
            color: #94a3b8;
            font-size: 11px;
            border: 1px dashed #cbd5e1;
            border-radius: 4px;
            margin: 5px 0 3px;
        }

        @media print {
            @page { margin: 10mm; size: A4; }
            body { background: #fff !important; }
            .no-print { display: none !important; }
            .label-grid { gap: 8px; }
            .book-label { border-color: #333; background: #fff; }
            .barcode-wrap svg { height: 50px; }
        }
    </style>
</head>
<body>
<div class="container py-3">

    {{-- Header toolbar --}}
    <div class="d-flex justify-content-between align-items-start mb-3 no-print">
        <div>
            <h5 class="mb-0 fw-bold">Book Copy Labels</h5>
            <small class="text-muted">{{ $book->title }} &mdash; {{ $book->copies->count() }} copies</small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            @php $noBarcodeCount = $book->copies->filter(fn($c) => !$c->barcode)->count(); @endphp

            @if($noBarcodeCount > 0)
                <form method="POST" action="{{ route('library.books.generate-barcodes', $book) }}">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-upc-scan me-1"></i>Generate Barcodes
                        <span class="badge bg-white text-success ms-1">{{ $noBarcodeCount }}</span>
                    </button>
                </form>
            @else
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                    <i class="bi bi-check-circle me-1"></i>All barcodes assigned
                </span>
            @endif

            <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3 no-print" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('info'))
        <div class="alert alert-info alert-dismissible d-flex align-items-center gap-2 mb-3 no-print" role="alert">
            <i class="bi bi-info-circle-fill"></i>
            <span>{{ session('info') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Label grid --}}
    <div class="label-grid">
        @foreach($book->copies as $copy)
            @php $barcodeValue = $copy->barcode ?: null; @endphp
            <div class="book-label">
                <div class="book-title" title="{{ $book->title }}">{{ $book->title }}</div>
                <div class="book-author">{{ $book->authors->pluck('name')->implode(', ') ?: ($book->author_text ?: '—') }}</div>

                @if($barcodeValue)
                    <div class="barcode-wrap">
                        <svg class="barcode-svg" data-value="{{ $barcodeValue }}"></svg>
                    </div>
                @else
                    <div class="no-barcode-placeholder no-print">
                        <i class="bi bi-upc me-1"></i>No barcode — click Generate Barcodes
                    </div>
                @endif

                <div class="meta-row">
                    <span><strong>Acc No:</strong> {{ $copy->accession_no }}</span>
                    <span><strong>Rack:</strong> {{ $copy->rack->display_name ?? '—' }}</span>
                </div>
                <div class="meta-row">
                    <span><strong>Subject:</strong> {{ $book->subject->name ?? ($book->subject_name ?: '—') }}</span>
                    @if($barcodeValue)
                        <span><strong>BC:</strong> {{ $barcodeValue }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
document.querySelectorAll('.barcode-svg').forEach(function (svg) {
    var value = svg.getAttribute('data-value');
    try {
        JsBarcode(svg, value, {
            format:       'CODE128',
            width:        1.6,
            height:       50,
            displayValue: true,
            fontSize:     11,
            textMargin:   3,
            margin:       4,
            background:   '#ffffff',
            lineColor:    '#000000',
        });
    } catch (e) {
        svg.outerHTML = '<div style="text-align:center;font-family:monospace;font-size:13px;font-weight:700;letter-spacing:2px;padding:8px 0;">' + value + '</div>';
    }
});
</script>
</body>
</html>
