<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transport Fee Receipt — TRP-{{ $transaction->id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f5f6fa; font-family: 'Segoe UI', sans-serif; }

        .receipt-wrap {
            max-width: 680px;
            margin: 30px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.10);
            overflow: hidden;
        }

        /* Header */
        .receipt-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
            color: #fff;
            padding: 28px 32px 22px;
        }
        .inst-logo {
            width: 54px; height: 54px;
            object-fit: contain;
            border-radius: 8px;
            background: rgba(255,255,255,0.15);
        }
        .inst-logo-placeholder {
            width: 54px; height: 54px;
            border-radius: 8px;
            background: rgba(255,255,255,0.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 26px; color: rgba(255,255,255,0.85);
        }
        .receipt-title {
            font-size: 11px; letter-spacing: 2px; text-transform: uppercase;
            color: rgba(255,255,255,0.65); margin-bottom: 2px;
        }
        .receipt-no {
            font-size: 20px; font-weight: 700; color: #fff; letter-spacing: 0.5px;
        }

        /* Status banner */
        .status-banner {
            padding: 10px 32px;
            font-size: 12px; font-weight: 600; letter-spacing: 0.5px;
        }
        .status-paid    { background: #f0fdf4; color: #15803d; border-bottom: 2px solid #86efac; }
        .status-partial { background: #fffbeb; color: #92400e; border-bottom: 2px solid #fcd34d; }

        /* Body */
        .receipt-body { padding: 28px 32px; }

        .section-label {
            font-size: 10px; font-weight: 700; letter-spacing: 1.5px;
            text-transform: uppercase; color: #94a3b8; margin-bottom: 10px;
        }

        .info-row {
            display: flex; justify-content: space-between;
            padding: 6px 0; border-bottom: 1px dashed #f1f5f9;
            font-size: 13.5px;
        }
        .info-row:last-child { border-bottom: none; }
        .info-key   { color: #64748b; }
        .info-value { font-weight: 500; color: #1e293b; text-align: right; }

        /* Amount box */
        .amount-box {
            background: #f8faff;
            border: 1px solid #e0e7ff;
            border-radius: 10px;
            padding: 18px 20px;
        }
        .amount-box .amt-row {
            display: flex; justify-content: space-between;
            font-size: 13.5px; padding: 4px 0;
        }
        .amount-box .amt-row.total {
            font-size: 16px; font-weight: 700; color: #1e293b;
            border-top: 1.5px solid #c7d2fe; margin-top: 8px; padding-top: 10px;
        }
        .amount-box .amt-row.balance { color: #dc2626; font-weight: 600; }
        .amount-box .amt-row.balance.paid { color: #16a34a; }

        /* Footer */
        .receipt-footer {
            padding: 20px 32px 28px;
            border-top: 1px dashed #e2e8f0;
            display: flex; justify-content: space-between; align-items: flex-end;
        }
        .sig-line { border-top: 1.5px solid #94a3b8; width: 160px; padding-top: 6px; font-size: 12px; color: #64748b; text-align: center; }

        /* Print */
        .no-print { }
        @media print {
            body { background: #fff; }
            .receipt-wrap { box-shadow: none; margin: 0; border-radius: 0; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

@php
    $inst        = auth()->user()->institute;
    $allocation  = $allocation ?? $transaction->transportAllocation;
    $student     = $transaction->student;
    $session     = $transaction->session;
    $totalFee    = (float) ($allocation?->fee_amount ?? 0);
    $paidNow     = (float) ($transaction->invoice?->paid_amount ?? ($transaction->credit ?: $transaction->debit));
    $totalPaid   = (float) ($allocation?->paid_amount ?? $paidNow);
    $balance     = round($totalFee - $totalPaid, 2);
    $isFullyPaid = $balance <= 0;
    $collector   = $transaction->by_user_id ? \App\Models\StaffMember::find($transaction->by_user_id)?->name : null;
@endphp

{{-- Toolbar --}}
<div class="no-print d-flex justify-content-center gap-2 py-3">
    <button onclick="window.print()" class="btn btn-primary btn-sm px-4">
        <i class="bi bi-printer me-1"></i> Print Receipt
    </button>
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm px-4">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="receipt-wrap">

    {{-- Header --}}
    <div class="receipt-header d-flex justify-content-between align-items-start gap-3">
        <div class="d-flex align-items-center gap-3">
            @if($inst->image)
                <img src="{{ asset('storage/' . $inst->image) }}" alt="{{ $inst->name }}" class="inst-logo">
            @else
                <div class="inst-logo-placeholder"><i class="bi bi-mortarboard-fill"></i></div>
            @endif
            <div>
                <div style="font-size:17px;font-weight:700;line-height:1.2;">{{ $inst->name }}</div>
                @if($inst->address ?? null)
                    <div style="font-size:11px;color:rgba(255,255,255,0.65);margin-top:3px;">{{ $inst->address }}</div>
                @endif
            </div>
        </div>
        <div class="text-end flex-shrink-0">
            <div class="receipt-title">Transport Fee Receipt</div>
            <div class="receipt-no">{{ $transaction->invoice?->invoice_no ?? ('TRP-' . str_pad($transaction->id, 5, '0', STR_PAD_LEFT)) }}</div>
            <div style="font-size:12px;color:rgba(255,255,255,0.65);margin-top:3px;">
                {{ $transaction->date?->format('d M Y') ?? now()->format('d M Y') }}
            </div>
        </div>
    </div>

    {{-- Status Banner --}}
    <div class="status-banner {{ $isFullyPaid ? 'status-paid' : 'status-partial' }}">
        <i class="bi bi-{{ $isFullyPaid ? 'check-circle-fill' : 'circle-half' }} me-2"></i>
        {{ $isFullyPaid ? 'FULLY PAID' : 'PARTIAL PAYMENT' }}
        &nbsp;—&nbsp; Transport Fee Receipt
    </div>

    <div class="receipt-body">

        {{-- Student Details --}}
        <div class="section-label">Student Information</div>
        <div class="mb-4">
            <div class="info-row">
                <span class="info-key">Student Name</span>
                <span class="info-value">{{ $student?->name ?? '—' }}</span>
            </div>
            @if($student?->roll_no)
            <div class="info-row">
                <span class="info-key">Roll No</span>
                <span class="info-value">{{ $student->roll_no }}</span>
            </div>
            @endif
            @if($student?->enrollment_no)
            <div class="info-row">
                <span class="info-key">Enroll No</span>
                <span class="info-value">{{ $student->enrollment_no }}</span>
            </div>
            @endif
            <div class="info-row">
                <span class="info-key">Academic Session</span>
                <span class="info-value">{{ $session?->name ?? '—' }}</span>
            </div>
        </div>

        {{-- Transport Details --}}
        <div class="section-label">Transport Details</div>
        <div class="mb-4">
            <div class="info-row">
                <span class="info-key">Route</span>
                <span class="info-value">{{ $allocation?->route?->name ?? '—' }}</span>
            </div>
            @if($allocation?->stop)
            <div class="info-row">
                <span class="info-key">Stop</span>
                <span class="info-value">{{ $allocation->stop->stop_name }}</span>
            </div>
            @endif
            @if($allocation?->vehicle)
            <div class="info-row">
                <span class="info-key">Vehicle</span>
                <span class="info-value">{{ $allocation->vehicle->vehicle_no }}</span>
            </div>
            @endif
            <div class="info-row">
                <span class="info-key">Fee Type</span>
                <span class="info-value">One Time</span>
            </div>
            <div class="info-row">
                <span class="info-key">Payment Mode</span>
                <span class="info-value"><i class="bi bi-wallet2 me-1 text-primary"></i>Wallet Deduction</span>
            </div>
            @if($collector)
            <div class="info-row">
                <span class="info-key">Collected By</span>
                <span class="info-value">{{ $collector }}</span>
            </div>
            @endif
        </div>

        {{-- Amount --}}
        <div class="section-label">Fee Summary</div>
        <div class="amount-box">
            <div class="amt-row">
                <span class="text-muted">Total Transport Fee</span>
                <span>₹{{ number_format($totalFee, 2) }}</span>
            </div>
            <div class="amt-row">
                <span class="text-muted">Previously Paid</span>
                <span>₹{{ number_format(max(0, $totalPaid - $paidNow), 2) }}</span>
            </div>
            <div class="amt-row total">
                <span>Collected Now</span>
                <span class="text-primary">₹{{ number_format($paidNow, 2) }}</span>
            </div>
            <div class="amt-row balance {{ $isFullyPaid ? 'paid' : '' }}">
                <span>Balance Remaining</span>
                <span>₹{{ number_format(max(0, $balance), 2) }}</span>
            </div>
        </div>

    </div>

    {{-- Footer --}}
    <div class="receipt-footer">
        <div>
            @if($transaction->invoice)
            <div class="text-muted" style="font-size:11px;">Invoice: {{ $transaction->invoice->invoice_no }}</div>
            @endif
            <div class="text-muted" style="font-size:11px;">Ref: TXN-{{ $transaction->id }}</div>
            <div class="text-muted" style="font-size:11px;">{{ $transaction->date?->format('d M Y') ?? now()->format('d M Y') }}</div>
        </div>
        <div class="sig-line">Authorised Signatory</div>
    </div>

</div>

<div class="no-print text-center py-3 text-muted" style="font-size:12px;">
    This is a system-generated receipt. No signature required if printed digitally.
</div>

</body>
</html>
