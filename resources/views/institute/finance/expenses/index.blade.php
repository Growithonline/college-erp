@extends($layout ?? 'institute.layout')
@section('title', 'Expenses')
@section('breadcrumb', 'Finance / Expenses')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-receipt-cutoff me-2 text-danger"></i>Expense Book</h4>
        <small class="text-muted">College ke paid expenses aur accounting posting status yahan track karo</small>
    </div>
    <div class="d-flex gap-2">
        @if(isset($pendingApprovalCount) && $pendingApprovalCount > 0)
        <a href="{{ route('finance.wallet.expense-approvals.index') }}" class="btn btn-warning btn-sm">
            <i class="bi bi-hourglass-split me-1"></i> Pending Approvals
            <span class="badge bg-dark ms-1">{{ $pendingApprovalCount }}</span>
        </a>
        @endif
        <a href="{{ route(($rp ?? 'finance') . '.expenses.create') }}" class="btn btn-danger btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Add Expense
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Today Expense</div>
                <div class="fw-bold fs-4 text-danger">Rs {{ number_format($expenseToday, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">This Month Expense</div>
                <div class="fw-bold fs-4 text-primary">Rs {{ number_format($expenseThisMonth, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Pending GL Post</div>
                <div class="fw-bold fs-4 {{ $pendingPostingCount > 0 ? 'text-warning' : 'text-success' }}">
                    {{ number_format($pendingPostingCount) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Pending Approval</div>
                <div class="fw-bold fs-4 {{ ($pendingApprovalCount ?? 0) > 0 ? 'text-warning' : 'text-success' }}">
                    {{ number_format($pendingApprovalCount ?? 0) }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">Recent Expenses</h6>
        <small class="text-muted">Latest 50 entries</small>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Date</th>
                    <th>Expense Head</th>
                    <th>Vendor / Description</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th class="text-end">Amount</th>
                    <th class="pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $expense)
                <tr>
                    <td class="ps-3 text-muted">{{ $expense->expense_date?->format('d M Y') }}</td>
                    <td>
                        <div class="fw-semibold">{{ $expense->expenseAccount?->name ?? '-' }}</div>
                        <div class="small text-muted">{{ $expense->expenseAccount?->code ?? '-' }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $expense->vendor_name ?: 'Internal expense' }}</div>
                        <div class="small text-muted">{{ \Illuminate\Support\Str::limit($expense->description, 70) }}</div>
                        @if($expense->bill_no)
                            <div class="small text-muted">Bill: {{ $expense->bill_no }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="text-uppercase fw-semibold">{{ $expense->payment_mode }}</div>
                        <div class="small text-muted">
                            {{ $expense->bankAccount?->display_label ?: ($expense->paymentAccount?->name ?? 'Cash Account') }}
                        </div>
                    </td>
                    <td>
                        @php $status = $expense->approval_status ?? 'auto_approved'; @endphp
                        @if($expense->is_reversed)
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Reversed</span>
                        @elseif($status === 'pending')
                            <span class="badge bg-warning text-dark">Approval Pending</span>
                        @elseif($status === 'rejected')
                            <span class="badge bg-danger">Rejected</span>
                        @elseif($expense->journal_entry_id)
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                <i class="bi bi-check2"></i> Posted
                            </span>
                        @else
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">GL Pending</span>
                        @endif
                        @if($expense->wallet_debited ?? false)
                            <br><span class="badge bg-info bg-opacity-10 text-info small mt-1">Wallet Debited</span>
                        @endif
                    </td>
                    <td class="text-end fw-semibold text-danger">Rs {{ number_format($expense->amount, 2) }}</td>
                    <td class="pe-3">
                        @if($expense->is_reversed)
                            <div class="small text-muted">Reason: {{ $expense->reversal_reason ?: '-' }}</div>
                            @if($expense->reversal_journal_entry_id)
                                <div class="small text-muted">Rev JE #{{ $expense->reversal_journal_entry_id }}</div>
                            @endif
                        @else
                            @if(Route::has(($rp ?? 'finance') . '.expenses.reverse'))
                            <a href="{{ route(($rp ?? 'finance') . '.expenses.reverse', $expense) }}" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reverse
                            </a>
                            @else
                            <span class="text-muted small">Posted</span>
                            @endif
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-receipt-cutoff fs-2 d-block mb-2"></i>
                        Abhi tak koi expense entry nahi bani hai.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
