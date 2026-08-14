@extends('institute.layout')
@section('title', 'Ledger - ' . $workOrder->title)
@section('breadcrumb', 'Finance / Wallet / ' . $vendor->name . ' / Work Orders / ' . $workOrder->title . ' / Ledger')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="{{ route('finance.wallet.expense-categories.index') }}">Categories</a></li>
                <li class="breadcrumb-item"><a href="{{ route('finance.wallet.expense-categories.sub.index', $expenseCategory) }}">{{ $expenseCategory->name }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('finance.wallet.expense-categories.sub.vendors.index', [$expenseCategory, $sub]) }}">{{ $sub->name }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('finance.wallet.expense-categories.sub.vendors.work-orders.index', [$expenseCategory, $sub, $vendor]) }}">{{ $vendor->name }} — Work Orders</a></li>
                <li class="breadcrumb-item active">{{ $workOrder->title }}</li>
            </ol>
        </nav>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-journal-text me-2 text-primary"></i>
            {{ $workOrder->title }}
            <span class="badge {{ $workOrder->status === 'open' ? 'bg-success' : 'bg-secondary' }} align-middle">
                {{ ucfirst($workOrder->status) }}
            </span>
            @if($workOrder->isOverBudget())
                <span class="badge bg-danger align-middle">
                    <i class="bi bi-exclamation-triangle-fill"></i> Over budget
                </span>
            @endif
        </h4>
    </div>
    <a href="{{ route('finance.wallet.expense-categories.sub.vendors.work-orders.index', [$expenseCategory, $sub, $vendor]) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Work Orders
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small mb-1">Total Entries</div>
            <div class="fw-bold fs-5">{{ $totalEntries }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small mb-1">Total Budget (Credit)</div>
            <div class="fw-bold fs-5 text-success">₹{{ number_format($totalCredit, 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small mb-1">Total Spent (Debit)</div>
            <div class="fw-bold fs-5 text-danger">₹{{ number_format($totalDebit, 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small mb-1">Remaining</div>
            <div class="fw-bold fs-5 {{ $workOrder->isOverBudget() ? 'text-danger' : 'text-success' }}">
                ₹{{ number_format($workOrder->remaining_amount, 2) }}
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <span class="fw-bold"><i class="bi bi-tag me-1 text-primary"></i>{{ $vendor->name }} — {{ $workOrder->title }}</span>
        <small class="text-muted">{{ $totalEntries }} records</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Op. Bal</th>
                    <th class="text-end">Balance</th>
                    <th>Note</th>
                    <th>Linked Expense</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $i => $tx)
                @php
                    $opBal = $tx->type === 'credit'
                        ? (float) $tx->balance_after - (float) $tx->amount
                        : (float) $tx->balance_after + (float) $tx->amount;
                @endphp
                <tr>
                    <td class="text-muted">{{ $transactions->firstItem() + $i }}</td>
                    <td class="text-nowrap">{{ $tx->created_at->format('d-m-Y H:i') }}</td>
                    <td>
                        <span class="badge {{ $tx->type === 'credit' ? 'bg-success' : 'bg-danger' }} bg-opacity-75">
                            {{ ucfirst($tx->type) }}
                        </span>
                    </td>
                    <td class="text-end fw-semibold {{ $tx->type === 'credit' ? 'text-success' : 'text-danger' }}">
                        {{ $tx->type === 'credit' ? '+' : '-' }}₹{{ number_format($tx->amount, 2) }}
                    </td>
                    <td class="text-end text-muted">₹{{ number_format($opBal, 2) }}</td>
                    <td class="text-end fw-bold {{ (float) $tx->balance_after < 0 ? 'text-danger' : 'text-dark' }}">
                        ₹{{ number_format($tx->balance_after, 2) }}
                    </td>
                    <td class="text-muted">{{ $tx->note ?? '-' }}</td>
                    <td class="text-muted">{{ $tx->expense_id ? 'Expense #' . $tx->expense_id : '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        No transactions yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
    <div class="card-footer bg-white">
        {{ $transactions->links() }}
    </div>
    @endif
</div>
@endsection
