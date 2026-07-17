<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment — {{ $institute->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f0f4f8; min-height: 100vh; }
        .payment-card { max-width: 620px; margin: 40px auto; }
        .institute-logo { max-height: 64px; max-width: 200px; object-fit: contain; }
        .due-amount { font-size: 32px; font-weight: 800; color: #2563EB; }
        .qr-box { text-align: center; padding: 16px; border: 1px solid #e2e8f0; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="container payment-card">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    @if($institute->image)
                        <img src="{{ asset('storage/' . $institute->image) }}" alt="{{ $institute->name }}" class="institute-logo mb-2 d-block mx-auto">
                    @endif
                    <h4 class="fw-bold mb-0">{{ $institute->name }}</h4>
                    <div class="text-muted small">{{ $student->name }} — {{ $student->student_uid }}</div>
                </div>

                <div class="text-center mb-4">
                    <div class="small text-muted">Amount due to confirm admission</div>
                    <div class="due-amount">₹{{ number_format($dueAmount, 2) }}</div>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                @if($latestClaim && !$latestClaim->isRejected())
                    <div class="alert alert-{{ $latestClaim->isApproved() ? 'success' : 'warning' }}">
                        @if($latestClaim->isApproved())
                            <i class="bi bi-check-circle me-1"></i> Payment verified. Amount confirmed: ₹{{ number_format($latestClaim->amount_claimed, 2) }}.
                        @else
                            <i class="bi bi-hourglass-split me-1"></i> Your payment claim (₹{{ number_format($latestClaim->amount_claimed, 2) }}, {{ $latestClaim->payment_mode === 'pay_at_institute' ? 'Pay at Institute' : 'UPI/NEFT' }}) is awaiting staff verification.
                        @endif
                    </div>
                @else
                    @if($latestClaim && $latestClaim->isRejected())
                        <div class="alert alert-danger">
                            <i class="bi bi-x-circle me-1"></i> Your previous claim was rejected: {{ $latestClaim->rejection_reason }}. Please resubmit below.
                        </div>
                    @endif

                    @if($bankAccount)
                        <div class="qr-box mb-3">
                            <img src="{{ $qrDataUri }}" alt="UPI QR Code" style="max-width:220px;">
                            <div class="small text-muted mt-2">Scan to pay via any UPI app</div>
                            <div class="small fw-semibold">{{ $bankAccount->upi_id }}</div>
                        </div>
                        <div class="mb-4 small">
                            <div class="fw-semibold mb-1">Bank Details</div>
                            <div>Account Name: {{ $bankAccount->account_name }}</div>
                            <div>Account No: {{ $bankAccount->account_no }}</div>
                            <div>IFSC: {{ $bankAccount->ifsc_code }}</div>
                            <div>Bank: {{ $bankAccount->bank_name }} @if($bankAccount->branch), {{ $bankAccount->branch }}@endif</div>
                        </div>
                    @endif

                    <form method="POST" action="{{ url()->full() }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">How will you pay? *</label>
                            <select name="payment_mode" id="paymentModeSelect" class="form-select" required>
                                @if($bankAccount)
                                    <option value="upi_neft" {{ old('payment_mode', 'upi_neft') === 'upi_neft' ? 'selected' : '' }}>I've paid via UPI / NEFT</option>
                                @endif
                                <option value="pay_at_institute" {{ old('payment_mode') === 'pay_at_institute' ? 'selected' : '' }}>I'll pay at the institute</option>
                            </select>
                        </div>

                        <div id="upiFields" class="@if(!$bankAccount) d-none @endif">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Amount Paid *</label>
                                <input type="number" step="0.01" name="amount_claimed" class="form-control" value="{{ old('amount_claimed', $dueAmount) }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Transaction ID / UTR / Reference No. *</label>
                                <input type="text" name="transaction_ref" class="form-control" value="{{ old('transaction_ref') }}" maxlength="100">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Payment Screenshot *</label>
                                <input type="file" name="screenshot" class="form-control" accept="image/*,.pdf">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Submit</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <script>
        const modeSelect = document.getElementById('paymentModeSelect');
        const upiFields = document.getElementById('upiFields');
        if (modeSelect && upiFields) {
            function toggleUpiFields() {
                const isUpi = modeSelect.value === 'upi_neft';
                upiFields.classList.toggle('d-none', !isUpi);
                upiFields.querySelectorAll('input').forEach(el => { el.required = isUpi; });
            }
            modeSelect.addEventListener('change', toggleUpiFields);
            toggleUpiFields();
        }
    </script>
</body>
</html>
