<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Library Receipt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-white">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h3 class="mb-1">Library Transaction Receipt</h3>
            <small class="text-muted">Txn #{{ $transaction->id }}</small>
        </div>
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print</button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><strong>Member:</strong> {{ $transaction->member->name ?? '-' }}</div>
                <div class="col-md-6"><strong>Member Code:</strong> {{ $transaction->member->member_code ?? '-' }}</div>
                <div class="col-md-6"><strong>Book:</strong> {{ $transaction->copy->book->title ?? '-' }}</div>
                <div class="col-md-6"><strong>Copy:</strong> {{ $transaction->copy->accession_no ?? '-' }}</div>
                <div class="col-md-6"><strong>Issued On:</strong> {{ optional($transaction->issued_on)->format('d-m-Y') }}</div>
                <div class="col-md-6"><strong>Due On:</strong> {{ optional($transaction->due_on)->format('d-m-Y') }}</div>
                <div class="col-md-6"><strong>Returned On:</strong> {{ optional($transaction->returned_on)->format('d-m-Y') ?: '-' }}</div>
                <div class="col-md-6"><strong>Status:</strong> {{ ucfirst($transaction->current_status) }}</div>
                <div class="col-md-6"><strong>Fine:</strong> Rs {{ number_format((float) $transaction->fine_amount, 2) }}</div>
                <div class="col-md-6"><strong>Fine Paid:</strong> Rs {{ number_format((float) $transaction->fine_paid, 2) }}</div>
                <div class="col-12"><strong>Remarks:</strong> {{ $transaction->remarks ?: '-' }}</div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
