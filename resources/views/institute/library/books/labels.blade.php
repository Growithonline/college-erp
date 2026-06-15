<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book Labels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .label-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:12px; }
        .book-label { border:1px dashed #333; padding:12px; min-height:120px; }
    </style>
</head>
<body class="bg-white">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h3 class="mb-1">Book Copy Labels</h3>
            <small class="text-muted">{{ $book->title }}</small>
        </div>
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print</button>
    </div>

    <div class="label-grid">
        @foreach($book->copies as $copy)
            <div class="book-label">
                <div class="fw-bold">{{ $book->title }}</div>
                <div class="small text-muted mb-1">{{ $book->authors->pluck('name')->implode(', ') ?: ($book->author_text ?: '-') }}</div>
                <div><strong>Access No:</strong> {{ $copy->accession_no }}</div>
                <div><strong>Barcode:</strong> {{ $copy->barcode ?: '-' }}</div>
                <div><strong>Rack:</strong> {{ $copy->rack->display_name ?? '-' }}</div>
                <div><strong>Subject:</strong> {{ $book->subject->name ?? ($book->subject_name ?: '-') }}</div>
            </div>
        @endforeach
    </div>
</div>
</body>
</html>
