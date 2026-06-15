@extends('institute.layout')
@section('title', 'Expense Ledger Category Wise')
@section('breadcrumb', 'Finance / Wallet / Expense Category Ledger')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-grid-3x3-gap me-2 text-danger"></i>Expense Ledger Category Wise</h4>
        <small class="text-muted">Select a category to view all its expense transactions</small>
    </div>
    <a href="{{ route('finance.wallet.ledger') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Main Ledger
    </a>
</div>

{{-- Filter Form --}}
<form method="GET" class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>
                            {{ $s->name }} {{ $s->is_active ? '(Active)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Expense Category (L1) <span class="text-danger">*</span></label>
                <select name="l1_id" class="form-select form-select-sm" id="l1Select" required>
                    <option value="">-- Category Select Karo --</option>
                    @foreach($l1Categories as $l1)
                        <option value="{{ $l1->id }}" {{ $l1Id == $l1->id ? 'selected' : '' }}>
                            {{ $l1->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if($l2Options->isNotEmpty())
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Sub-Category (L2)</label>
                <select name="l2_id" class="form-select form-select-sm">
                    <option value="">-- Sab --</option>
                    @foreach($l2Options as $l2)
                        <option value="{{ $l2->id }}" {{ $l2Id == $l2->id ? 'selected' : '' }}>
                            {{ $l2->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @else
                <input type="hidden" name="l2_id" value="">
            @endif
            <div class="col-md-2">
                <label class="form-label fw-semibold small">From Date</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">To Date</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>
    </div>
</form>

@if(!$l1Id)
<div class="alert alert-info border-0 shadow-sm">
    <i class="bi bi-info-circle me-2"></i>
    Select an <strong>Expense Category</strong> above to view all its transactions.
</div>
@else

{{-- Summary Cards --}}
@if($rows->isNotEmpty())
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small mb-1">Total Entries</div>
            <div class="fw-bold fs-5">{{ $rows->count() }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small mb-1">Total Expense (Debit)</div>
            <div class="fw-bold fs-5 text-danger">₹{{ number_format($grandDebit, 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small mb-1">Total Reversal (Credit)</div>
            <div class="fw-bold fs-5 text-success">₹{{ number_format($grandCredit, 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small mb-1">Net Expense</div>
            @php $net = $grandDebit - $grandCredit; @endphp
            <div class="fw-bold fs-5 {{ $net >= 0 ? 'text-danger' : 'text-success' }}">
                ₹{{ number_format($net, 2) }}
            </div>
        </div>
    </div>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <div>
            <span class="fw-bold">
                <i class="bi bi-tag me-1 text-danger"></i>
                {{ $selectedL1?->name ?? 'Selected Category' }}
            </span>
            @if($l2Id && $l2Options->firstWhere('id', $l2Id))
                <span class="text-muted"> / {{ $l2Options->firstWhere('id', $l2Id)?->name }}</span>
            @endif
        </div>
        <small class="text-muted">{{ $rows->count() }} records</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
            <thead style="background:#fff3e0;">
                <tr>
                    <th>#</th>
                    <th>Session</th>
                    <th>Date</th>
                    <th>Remark</th>
                    <th>Category</th>
                    <th>Receipt No.</th>
                    <th>Type</th>
                    <th class="text-end text-success">Credit</th>
                    <th class="text-end text-danger">Debit</th>
                    <th class="text-end">Op. Bal</th>
                    <th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $i => $row)
                @php $exp = $row['expense']; @endphp
                <tr {{ $exp->is_reversed ? 'class=table-warning' : '' }}>
                    <td class="text-muted">{{ $i + 1 }}</td>
                    <td class="text-muted small text-nowrap">{{ $exp->session?->name ?? '-' }}</td>
                    <td class="text-nowrap">{{ $exp->expense_date->format('d-m-Y') }}</td>
                    <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                        title="{{ $exp->description }}">
                        {{ $exp->description ?: ($exp->vendor_name ?: '-') }}
                        @if($exp->is_reversed)
                            <span class="badge bg-warning text-dark ms-1">Reversed</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-danger bg-opacity-10 text-danger small">
                            {{ $row['category'] }}
                        </span>
                    </td>
                    <td>{{ $row['receipt_no'] }}</td>
                    <td>
                        @if($row['pay_type'] !== '-')
                            <span class="badge bg-secondary bg-opacity-10 text-dark">{{ $row['pay_type'] }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-end text-success fw-semibold">
                        {{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '-' }}
                    </td>
                    <td class="text-end text-danger fw-semibold">
                        {{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '-' }}
                    </td>
                    <td class="text-end text-muted">{{ number_format($row['op_bal'], 2) }}</td>
                    <td class="text-end fw-bold {{ $row['balance'] >= 0 ? 'text-dark' : 'text-success' }}">
                        {{ number_format($row['balance'], 2) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        No approved expenses found for this category.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($rows->isNotEmpty())
            <tfoot class="table-light fw-semibold">
                <tr>
                    <td colspan="7">Grand Total</td>
                    <td class="text-end text-success">{{ number_format($grandCredit, 2) }}</td>
                    <td class="text-end text-danger">{{ number_format($grandDebit, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endif

@push('scripts')
<script>
    // Auto-submit on L1 change to reload L2 options
    document.getElementById('l1Select')?.addEventListener('change', function() {
        this.closest('form').submit();
    });
</script>
@endpush
@endsection
