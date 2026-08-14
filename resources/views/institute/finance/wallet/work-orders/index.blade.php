@extends('institute.layout')
@section('title', 'Work Orders - ' . $vendor->name)
@section('breadcrumb', 'Finance / Wallet / ' . $expenseCategory->name . ' / ' . $sub->name . ' / ' . $vendor->name . ' / Work Orders')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="{{ route('finance.wallet.expense-categories.index') }}">Categories</a></li>
                <li class="breadcrumb-item"><a href="{{ route('finance.wallet.expense-categories.sub.index', $expenseCategory) }}">{{ $expenseCategory->name }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('finance.wallet.expense-categories.sub.vendors.index', [$expenseCategory, $sub]) }}">{{ $sub->name }}</a></li>
                <li class="breadcrumb-item active">{{ $vendor->name }} — Work Orders</li>
            </ol>
        </nav>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-clipboard-data me-2 text-primary"></i>
            {{ $vendor->name }} — Work Orders
        </h4>
    </div>
    <a href="{{ route('finance.wallet.expense-categories.sub.vendors.work-orders.create', [$expenseCategory, $sub, $vendor]) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Work Order
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th class="text-end">Total Budget</th>
                    <th class="text-end">Total Spent</th>
                    <th class="text-end">Remaining</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($workOrders as $wo)
                <tr>
                    <td class="text-muted">{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $wo->title }}</td>
                    <td>
                        <span class="badge {{ $wo->status === 'open' ? 'bg-success' : 'bg-secondary' }}">
                            {{ ucfirst($wo->status) }}
                        </span>
                    </td>
                    <td class="text-end">₹{{ number_format($wo->total_budget, 2) }}</td>
                    <td class="text-end">₹{{ number_format($wo->total_spent, 2) }}</td>
                    <td class="text-end">
                        @if($wo->isOverBudget())
                            <span class="text-danger fw-semibold">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                Over budget by ₹{{ number_format($wo->overBudgetAmount(), 2) }}
                            </span>
                        @else
                            <span class="text-success fw-semibold">₹{{ number_format($wo->remaining_amount, 2) }}</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('finance.wallet.expense-categories.sub.vendors.work-orders.ledger', [$expenseCategory, $sub, $vendor, $wo]) }}"
                           class="btn btn-sm btn-outline-secondary" title="Ledger">
                            <i class="bi bi-journal-text"></i>
                        </a>
                        @if($wo->status === 'open')
                        <button type="button" class="btn btn-sm btn-outline-success" title="Top-up Budget"
                                data-bs-toggle="modal" data-bs-target="#topupModal{{ $wo->id }}">
                            <i class="bi bi-plus-circle"></i>
                        </button>
                        @endif
                        <a href="{{ route('finance.wallet.expense-categories.sub.vendors.work-orders.edit', [$expenseCategory, $sub, $vendor, $wo]) }}"
                           class="btn btn-sm btn-outline-primary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @if($wo->status === 'open')
                        <form method="POST"
                              action="{{ route('finance.wallet.expense-categories.sub.vendors.work-orders.close', [$expenseCategory, $sub, $vendor, $wo]) }}"
                              class="d-inline" onsubmit="return confirm('Close this work order? This cannot be undone.')">
                            @csrf
                            <button class="btn btn-sm btn-outline-dark" title="Close"><i class="bi bi-x-circle"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>

                {{-- Top-up modal --}}
                @if($wo->status === 'open')
                <div class="modal fade" id="topupModal{{ $wo->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('finance.wallet.expense-categories.sub.vendors.work-orders.topup', [$expenseCategory, $sub, $vendor, $wo]) }}">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title">Top-up Budget — {{ $wo->title }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Amount <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="1" name="amount" class="form-control" required>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label fw-semibold">Note</label>
                                        <input type="text" name="note" class="form-control" placeholder="e.g. Additional budget approved">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Add Budget</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        No work orders yet.
                        <a href="{{ route('finance.wallet.expense-categories.sub.vendors.work-orders.create', [$expenseCategory, $sub, $vendor]) }}">Add the first work order.</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
