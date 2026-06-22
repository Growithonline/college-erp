@php
    $isStaff = auth()->guard('staff')->check();
    $layout = $isStaff ? 'staff.layout' : 'institute.layout';
    $feeCreateRoute = $isStaff ? 'staff.fee.create' : 'fee.create';
    $showRoute = $isStaff ? 'staff.admissions.show' : 'admissions.show';
    $feeIndexRoute = $isStaff ? 'staff.fee.index' : 'fee.index';
    $walletRoute = $isStaff ? 'staff.fee.wallet.student' : 'fee.wallet.student';
    $receiptRoute = $isStaff ? 'staff.fee.receipt' : 'fee.receipt';
    $canCollectFee = !$isStaff || auth()->guard('staff')->user()?->canCollectFee();
@endphp
@extends($layout)
@section('title','Student Wallet')
@section('breadcrumb','Fee / Student Wallet')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Student Wallet</h4>
        <small class="text-muted">{{ $student->name }} — {{ $student->student_uid }}</small>
    </div>
    <div class="d-flex gap-2">
        @if($canCollectFee)
        <a href="{{ route($feeCreateRoute, ['student_id' => $student->id]) }}" class="btn btn-success btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Collect Fee
        </a>
        @endif
        <a href="{{ route($showRoute, $student->id) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-person me-1"></i>Profile
        </a>
        <a href="{{ route($feeIndexRoute) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

{{-- ── Session Tabs ── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="small text-muted fw-semibold me-1">Session:</span>
            @foreach($sessionBalances as $sb)
            @php
                $isActive  = $sb['session']->id == $selectedSessionId;
                $bal       = $sb['balance'];
                $hasData   = $bal !== null;
            @endphp
            <a href="{{ route($walletRoute, ['student' => $student->id, 'session_id' => $sb['session']->id]) }}"
               class="btn btn-sm {{ $isActive ? 'btn-primary' : 'btn-outline-secondary' }} position-relative">
                {{ $sb['session']->name }}
                @if($hasData)
                    <span class="ms-1 badge {{ $bal < 0 ? 'bg-danger' : 'bg-success' }}" style="font-size:9px;">
                        {{ $bal < 0 ? '-₹'.number_format(abs($bal)) : '✓' }}
                    </span>
                @endif
            </a>
            @endforeach
        </div>
    </div>
</div>

{{-- ── Summary Cards ── --}}
<div class="row g-2 mb-4">
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid {{ $summary['balance'] < 0 ? '#dc2626' : '#16a34a' }} !important;">
            <div class="card-body p-3">
                <div class="small text-muted mb-1">{{ $selectedSession->name ?? '' }} Balance</div>
                <div class="fs-5 fw-bold {{ $summary['balance'] < 0 ? 'text-danger' : 'text-success' }}">
                    ₹ {{ number_format(abs($summary['balance']), 2) }}
                </div>
                <div class="mt-1">
                    @if($summary['balance'] < 0)
                        <span class="badge bg-danger">Due</span>
                    @elseif($summary['balance'] > 0)
                        <span class="badge bg-success">Advance</span>
                    @else
                        <span class="badge bg-secondary">Clear ✓</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="small text-muted mb-1">Total Charged</div>
                <div class="fs-5 fw-bold text-danger">₹ {{ number_format($summary['total_charged'], 2) }}</div>
                <div class="small text-muted">Is session mein</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="small text-muted mb-1">Total Paid</div>
                <div class="fs-5 fw-bold text-success">₹ {{ number_format($summary['total_paid'], 2) }}</div>
                <div class="small text-muted">Cash / UPI</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #7c3aed !important;">
            <div class="card-body p-3">
                <div class="small text-muted mb-1">Total Discount</div>
                <div class="fs-5 fw-bold" style="color:#7c3aed;">₹ {{ number_format($summary['total_discount'], 2) }}</div>
                <div class="small text-muted">Is session mein</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #f59e0b !important;">
            <div class="card-body p-3">
                <div class="small text-muted mb-1">Total Fine</div>
                <div class="fs-5 fw-bold text-warning">₹ {{ number_format($summary['total_fine'] ?? 0, 2) }}</div>
                <div class="small text-muted">Is session mein</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="small text-muted mb-1">Pending Due</div>
                <div class="fs-5 fw-bold {{ $summary['total_due'] > 0 ? 'text-danger' : 'text-success' }}">
                    ₹ {{ number_format($summary['total_due'], 2) }}
                </div>
                <div class="small text-muted">{{ $summary['is_clear'] ? '✓ No dues' : 'Baki hai' }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Fee Plan Installment Progress ── --}}
@if(isset($feePlanInfo) && $feePlanInfo)
@php
    $fp            = $feePlanInfo['plan'];
    $instAmts      = $feePlanInfo['installmentAmounts'];
    $totalFeeP     = $feePlanInfo['totalFee'];
    $totalPaidP    = $feePlanInfo['totalPaid'];
    $totalDueSoFar = $feePlanInfo['totalDueSoFar'];
    $nextDueInst   = $feePlanInfo['nextDueInst'];
    $nextDueAmount = $feePlanInfo['nextDueAmount'];
    $isOverdue     = $feePlanInfo['overdue'];
    $cumulative    = 0;
@endphp
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-semibold">
            <i class="bi bi-layers me-2 text-primary"></i>
            Fee Plan: <span class="text-primary">{{ $fp->name }}</span>
        </h6>
        <span class="small text-muted">Total: ₹ {{ number_format($totalFeeP, 0) }}</span>
    </div>
    <div class="card-body py-3">
        {{-- Next due alert --}}
        @if($nextDueInst)
        <div class="alert {{ $isOverdue ? 'alert-danger' : 'alert-warning' }} py-2 mb-3 d-flex align-items-center justify-content-between gap-2">
            <div>
                <i class="bi bi-exclamation-circle me-1"></i>
                <strong>Next Due:</strong> {{ $nextDueInst->label }}
                &nbsp;—&nbsp; <span class="fw-bold">₹ {{ number_format($nextDueAmount, 0) }}</span>
                <span class="text-muted small ms-2">({{ $nextDueInst->dueTriggerLabel() }})</span>
                @if($isOverdue)<span class="badge bg-danger ms-2">Overdue</span>@endif
            </div>
            @if($canCollectFee)
            <a href="{{ route($feeCreateRoute, ['student_id' => $student->id]) }}"
               class="btn btn-sm btn-dark fw-semibold">
                <i class="bi bi-lightning-fill me-1"></i>Collect ₹ {{ number_format($nextDueAmount, 0) }}
            </a>
            @endif
        </div>
        @elseif($totalFeeP > 0 && $totalPaidP >= $totalFeeP - 0.5)
        <div class="alert alert-success py-2 mb-3">
            <i class="bi bi-check-circle me-1"></i>
            <strong>All installments paid.</strong> Fee fully cleared.
        </div>
        @endif

        {{-- Installment badges --}}
        <div class="d-flex flex-wrap gap-2 mb-3">
            @foreach($fp->installments as $inst)
            @php
                $amt        = (float) ($instAmts[$inst->installment_number] ?? 0);
                $cumulative += $amt;
                $isPaid     = $totalPaidP >= $cumulative - 0.5;
                $isDue      = $inst->isDue($student);
                $isNext     = $nextDueInst && $inst->installment_number === $nextDueInst->installment_number;
            @endphp
            <span class="badge border {{ $isPaid ? 'bg-success text-white' : ($isNext ? 'bg-warning text-dark border-warning' : ($isDue ? 'bg-danger bg-opacity-10 text-danger border-danger' : 'bg-light text-muted border-secondary')) }}"
                  style="font-size:12px; padding:6px 10px;">
                @if($isPaid)<i class="bi bi-check-circle me-1"></i>
                @elseif($isNext)<i class="bi bi-clock me-1"></i>
                @elseif(!$isDue)<i class="bi bi-lock me-1"></i>
                @endif
                {{ $inst->label }}: ₹ {{ number_format($amt, 0) }}
                @if(!$isDue)<small class="opacity-75">(not due yet)</small>@endif
            </span>
            @endforeach
        </div>

        {{-- Summary row --}}
        <div class="d-flex gap-4" style="font-size:13px;">
            <span>Paid: <strong class="text-success">₹ {{ number_format($totalPaidP, 0) }}</strong></span>
            <span>Due now: <strong class="text-warning">₹ {{ number_format(max(0, $totalDueSoFar - $totalPaidP), 0) }}</strong></span>
            <span>Remaining: <strong class="text-danger">₹ {{ number_format(max(0, $totalFeeP - $totalPaidP), 0) }}</strong></span>
        </div>
    </div>
</div>
@endif

{{-- ── Pending Fees Breakup ── --}}
@if($pendingFees->where('pending', '>', 0)->isNotEmpty())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-warning bg-opacity-10 border-bottom py-3">
        <h6 class="mb-0 fw-semibold text-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Pending Fees — {{ $selectedSession->name ?? '' }}
        </h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Fee Type</th>
                    <th class="text-end">Charged</th>
                    <th class="text-end text-primary">Cash Paid</th>
                    <th class="text-end" style="color:#7c3aed;">Discount</th>
                    <th class="text-end text-warning">Fine</th>
                    <th class="text-end text-danger">Pending</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingFees as $fee)
                <tr>
                    <td class="fw-semibold small">{{ $fee['name'] }}</td>
                    <td class="text-end small">₹ {{ number_format($fee['charged'], 2) }}</td>
                    <td class="text-end small text-primary">₹ {{ number_format($fee['collection'] ?? 0, 2) }}</td>
                    <td class="text-end small" style="color:#7c3aed;">
                        {{ ($fee['discount'] ?? 0) > 0 ? '₹ '.number_format($fee['discount'], 2) : '—' }}
                    </td>
                    <td class="text-end small text-warning fw-semibold">
                        {{ ($fee['fine'] ?? 0) > 0 ? '₹ '.number_format($fee['fine'], 2) : '—' }}
                    </td>
                    <td class="text-end small fw-bold {{ $fee['pending'] > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $fee['pending'] > 0 ? '₹ '.number_format($fee['pending'],2) : '✓ Paid' }}
                    </td>
                    <td class="text-end">
                        @if($fee['pending'] > 0)
                        @if($canCollectFee)
                        <a href="{{ route($feeCreateRoute, ['student_id' => $student->id]) }}"
                           class="btn btn-outline-success btn-sm py-0" style="font-size:11px;">Collect</a>
                        @endif
                        @else
                        <span class="badge bg-success">✓</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── Transaction Ledger ── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between">
        <h6 class="mb-0 fw-semibold">
            <i class="bi bi-journal-text me-2 text-primary"></i>
            Ledger — {{ $selectedSession->name ?? 'Session' }}
        </h6>
        <span class="badge bg-primary">{{ $transactions->count() }} entries</span>
    </div>
    <div class="card-body p-0">
        @if($transactions->isEmpty())
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
            Is session mein koi transaction nahi
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th class="text-end">Debit (₹)</th>
                        <th class="text-end">Credit (₹)</th>
                        <th class="text-end">Op. Bal</th>
                        <th class="text-end">Cl. Bal</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        // Ledger running balance — FeeCalculatorService total se start karo
                        // stored op_bal/cl_bal stale hain (admission ke time wrong amount debit tha)
                        // Correct start = -(total_charged) = 0 se sabhi debits minus
                        $runningBal = 0;
                    @endphp
                    @foreach($transactions as $i => $txn)
                    @php
                        $opBal = $runningBal;
                        if ($txn->type == 1) { // Debit
                            // Use FeeCalculatorService-based charged total se adjust
                            // Individual debit transactions ke amounts trust karo EXCEPT
                            // last debit entry me agar mismatch ho
                            $runningBal -= (float) $txn->debit;
                        } else { // Credit
                            $runningBal += (float) $txn->credit;
                        }
                        $clBal = $runningBal;
                    @endphp
                    <tr>
                        <td class="small text-muted">{{ $i + 1 }}</td>
                        <td class="small">{{ $txn->date->format('d M Y') }}</td>
                        <td class="small">
                            {{ $txn->des }}
                            @if($txn->fee_invoice_id)
                            <a href="{{ route($receiptRoute, ['student' => $student->id, 'invoice' => $txn->fee_invoice_id]) }}"
                               target="_blank" class="ms-1" title="Receipt">
                                <i class="bi bi-receipt text-primary" style="font-size:11px;"></i>
                            </a>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $txn->type == 1 ? 'bg-danger' : 'bg-success' }}">
                                {{ $txn->type == 1 ? 'Debit' : 'Credit' }}
                            </span>
                        </td>
                        <td class="text-end small fw-semibold text-danger">
                            {{ $txn->debit > 0 ? '₹ '.number_format($txn->debit,2) : '—' }}
                        </td>
                        <td class="text-end small fw-semibold text-success">
                            {{ $txn->credit > 0 ? '₹ '.number_format($txn->credit,2) : '—' }}
                        </td>
                        <td class="text-end small {{ $opBal < 0 ? 'text-danger' : 'text-success' }}">
                            ₹ {{ number_format($opBal, 2) }}
                        </td>
                        <td class="text-end small fw-bold {{ $clBal < 0 ? 'text-danger' : 'text-success' }}">
                            ₹ {{ number_format($clBal, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="4" class="text-end small">Total:</td>
                        <td class="text-end small text-danger">₹ {{ number_format($summary['total_charged'],2) }}</td>
                        <td class="text-end small text-success">₹ {{ number_format($summary['total_paid'],2) }}</td>
                        <td></td>
                        <td class="text-end small {{ $summary['balance'] < 0 ? 'text-danger' : 'text-success' }}">
                            ₹ {{ number_format($summary['balance'],2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
