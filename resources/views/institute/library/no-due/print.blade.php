<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Library No Dues</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-white">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h3 class="mb-1">Library No-Dues Certificate</h3>
                <div class="text-muted">Generated on {{ now()->format('d-m-Y h:i A') }}</div>
            </div>
            <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print</button>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><strong>Name:</strong> {{ $student->name }}</div>
                    <div class="col-md-6"><strong>Student UID:</strong> {{ $student->student_uid }}</div>
                    <div class="col-md-6"><strong>Enrollment:</strong> {{ $student->enrollment_no ?: '-' }}</div>
                    <div class="col-md-6"><strong>Course:</strong> {{ $student->stream->course->name ?? '-' }}</div>
                </div>
            </div>
        </div>

        <div class="alert {{ $summary['is_clear'] ? 'alert-success' : 'alert-danger' }}">
            <strong>Status:</strong> {{ $summary['is_clear'] ? 'Library dues clear hain.' : 'Library clearance pending hai.' }}
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white"><strong>Active Issued Books</strong></div>
            <div class="card-body">
                @forelse($summary['active_issues'] as $transaction)
                    <div class="border rounded p-2 mb-2">
                        <div class="fw-semibold">{{ $transaction->copy->book->title ?? '-' }}</div>
                        <small class="text-muted">{{ $transaction->copy->accession_no ?? '-' }} | Due: {{ optional($transaction->due_on)->format('d-m-Y') }}</small>
                    </div>
                @empty
                    <div class="text-muted">No active issued books.</div>
                @endforelse
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <strong>Pending Fine:</strong> Rs {{ number_format((float) $summary['pending_fine'], 2) }}
            </div>
        </div>
    </div>
</body>
</html>
