<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book Labels — {{ $book->title }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { background: #f1f5f9; font-family: Arial, sans-serif; }

        /* ── Screen toolbar ── */
        .toolbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        /* ── Label sheet ── */
        .label-sheet {
            width: 210mm;
            margin: 16px auto;
            background: #fff;
            padding: 8mm;
            box-shadow: 0 2px 12px rgba(0,0,0,.1);
        }

        .label-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0;
        }

        /* ── Single label: 60mm × 45mm ── */
        .book-label {
            width: 60mm;
            height: 45mm;
            border: 0.4mm dashed #aaa;
            padding: 2mm 2.5mm 1.5mm;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .label-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1mm;
            margin-bottom: 1mm;
        }

        .label-title {
            font-size: 7.5pt;
            font-weight: 700;
            line-height: 1.2;
            color: #000;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            flex: 1;
        }

        .label-accno {
            font-size: 6.5pt;
            font-weight: 700;
            color: #0c4a6e;
            background: #e0f2fe;
            border: 0.3mm solid #7dd3fc;
            border-radius: 1mm;
            padding: 0.5mm 1.5mm;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .barcode-area {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0.5mm 0;
        }

        .barcode-area svg {
            width: 100%;
            height: 22mm;
            display: block;
        }

        .no-barcode-placeholder {
            width: 100%;
            height: 22mm;
            border: 0.4mm dashed #cbd5e1;
            border-radius: 1mm;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 6pt;
        }

        .label-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 0.3mm solid #e2e8f0;
            padding-top: 1mm;
            margin-top: auto;
        }

        .label-rack {
            font-size: 6pt;
            color: #333;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 55%;
        }

        .label-subject {
            font-size: 5.5pt;
            color: #555;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 44%;
            text-align: right;
        }

        /* ── Print ── */
        @media print {
            @page { size: A4; margin: 8mm; }
            body  { background: #fff !important; }
            .toolbar { display: none !important; }
            .label-sheet { margin: 0; padding: 0; box-shadow: none; width: 100%; }
            .book-label { border-color: #999; }
            .no-barcode-placeholder { display: none; }
        }
    </style>
</head>
<body>

{{-- Toolbar (hidden on print) --}}
<div class="toolbar no-print">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('library.books.show', $book) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
        <div>
            <strong>Book Copy Labels</strong>
            <span class="text-muted ms-2" style="font-size:13px;">{{ $book->title }} &mdash; {{ $book->copies->count() }} copies</span>
        </div>
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
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2" style="font-size:12px;">
                <i class="bi bi-check-circle me-1"></i>All barcodes assigned
            </span>
        @endif

        <button class="btn btn-primary btn-sm" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Print Labels
        </button>
    </div>
</div>

{{-- Flash messages --}}
<div class="container-fluid px-4 pt-2 no-print">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-2" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('info'))
        <div class="alert alert-info alert-dismissible d-flex align-items-center gap-2 mb-2" role="alert">
            <i class="bi bi-info-circle-fill"></i>
            <span>{{ session('info') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif
</div>

{{-- A4 label sheet --}}
<div class="label-sheet">
    <div class="label-grid">
        @foreach($book->copies as $copy)
            <div class="book-label">

                <div class="label-header">
                    <div class="label-title">{{ $book->title }}</div>
                    <div class="label-accno">{{ $copy->accession_no }}</div>
                </div>

                <div class="barcode-area">
                    @if($copy->barcode)
                        <svg class="barcode-svg" data-value="{{ $copy->barcode }}"></svg>
                    @else
                        <div class="no-barcode-placeholder">
                            <i class="bi bi-upc me-1"></i> No barcode
                        </div>
                    @endif
                </div>

                <div class="label-footer">
                    <span class="label-rack"><i class="bi bi-bookshelf" style="font-size:5pt;"></i> {{ $copy->rack->display_name ?? '—' }}</span>
                    <span class="label-subject">{{ $book->subject->name ?? ($book->subject_name ?: '—') }}</span>
                </div>

            </div>
        @endforeach
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
document.querySelectorAll('.barcode-svg').forEach(function (svg) {
    try {
        JsBarcode(svg, svg.getAttribute('data-value'), {
            format:       'CODE128',
            width:        2.2,
            height:       58,
            displayValue: true,
            fontSize:     9,
            textMargin:   2,
            margin:       3,
            background:   '#ffffff',
            lineColor:    '#000000',
        });
    } catch (e) {
        svg.outerHTML = '<div style="font-family:monospace;font-size:9pt;font-weight:700;text-align:center;padding:4mm 0;">' +
                        svg.getAttribute('data-value') + '</div>';
    }
});
</script>
</body>
</html>
