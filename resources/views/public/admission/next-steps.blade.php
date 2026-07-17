<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Next Steps — {{ $institute->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f0f4f8; min-height: 100vh; }
        .steps-card { max-width: 680px; margin: 40px auto; }
        .institute-logo { max-height: 64px; max-width: 200px; object-fit: contain; }
        .step-row { border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; margin-bottom: 14px; }
    </style>
</head>
<body>
    <div class="container steps-card">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    @if($institute->image)
                        <img src="{{ asset('storage/' . $institute->image) }}" alt="{{ $institute->name }}" class="institute-logo mb-2 d-block mx-auto">
                    @endif
                    <h4 class="fw-bold mb-0">{{ $institute->name }}</h4>
                    <div class="text-muted small">{{ $student->name }} — {{ $student->student_uid }}</div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <p class="text-muted small">Your application has been received and is <strong>pending review</strong>. Complete the steps below — you can do them in any order, and come back to this page anytime.</p>

                <div class="step-row d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold"><i class="bi bi-file-earmark-text me-1"></i> Upload Documents</div>
                        <div class="small text-muted">{{ $uploadedDocsCount }} of {{ $requiredDocsCount }} required documents uploaded</div>
                    </div>
                    <div class="text-end">
                        @if($requiredDocsCount === 0)
                            <span class="badge bg-secondary-subtle text-secondary">Not required</span>
                        @elseif($documentsComplete)
                            <span class="badge bg-success-subtle text-success d-block mb-2">Complete</span>
                            <a href="{{ $documentsUrl }}" class="btn btn-outline-primary btn-sm">View</a>
                        @else
                            <a href="{{ $documentsUrl }}" class="btn btn-primary btn-sm">Upload</a>
                        @endif
                    </div>
                </div>

                <div class="step-row d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold"><i class="bi bi-credit-card me-1"></i> Payment</div>
                        @if($dueAmount > 0)
                            <div class="small text-muted">Amount due now: ₹{{ number_format($dueAmount, 2) }}</div>
                        @else
                            <div class="small text-muted">No payment required at this stage</div>
                        @endif
                    </div>
                    <div class="text-end">
                        @if($dueAmount <= 0)
                            <span class="badge bg-secondary-subtle text-secondary">Not required</span>
                        @elseif($latestClaim && $latestClaim->isApproved())
                            <span class="badge bg-success-subtle text-success">Verified</span>
                        @elseif($latestClaim && $latestClaim->isPending())
                            <span class="badge bg-warning-subtle text-warning d-block mb-2">Awaiting Verification</span>
                            <a href="{{ $paymentUrl }}" class="btn btn-outline-primary btn-sm">View</a>
                        @else
                            @if($latestClaim && $latestClaim->isRejected())
                                <span class="badge bg-danger-subtle text-danger d-block mb-2">Rejected — resubmit</span>
                            @endif
                            <a href="{{ $paymentUrl }}" class="btn btn-primary btn-sm">Pay Now</a>
                        @endif
                    </div>
                </div>

                <p class="text-muted small mb-0 mt-3">Once both steps are complete, the institute will review and approve your admission. You'll receive your student ID and login details by email once approved.</p>
            </div>
        </div>
    </div>
</body>
</html>
