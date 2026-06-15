@extends('institute.layout')
@section('title', 'Reverse Expense')
@section('breadcrumb', 'Finance / Expenses / Reverse')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-arrow-counterclockwise me-2 text-danger"></i>Reverse Expense</h4>
        <small class="text-muted">Mark the expense as reversed on both the operational and accounting side</small>
        
    </div>
    <a href="{{ route('finance.expenses.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="alert alert-danger border-0 shadow-sm">
            <div class="fw-semibold mb-1">Important</div>
            <div class="small mb-0">This action will not delete the record. The entry will remain but be marked as reversed, and a journal reversal will be created if the original posting is available.</div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="small text-muted">Expense Date</div>
                        <div class="fw-semibold">{{ $expense->expense_date?->format('d M Y') ?: '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Amount</div>
                        <div class="fw-semibold text-danger">Rs {{ number_format($expense->amount, 2) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Posting Status</div>
                        <div class="fw-semibold">{{ $expense->journal_entry_id ? 'Posted' : 'Pending Posting' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Expense Head</div>
                        <div class="fw-semibold">{{ $expense->expenseAccount?->code }} - {{ $expense->expenseAccount?->name }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Vendor</div>
                        <div class="fw-semibold">{{ $expense->vendor_name ?: 'Internal expense' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="small text-muted">Description</div>
                        <div class="fw-semibold">{{ $expense->description }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('finance.expenses.reverse.store', $expense) }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reversal Reason <span class="text-danger">*</span></label>
                        <textarea name="reversal_reason" rows="4" class="form-control" required
                                  placeholder="Briefly explain the reason for reversing this expense">{{ old('reversal_reason') }}</textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger px-4">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Confirm Reverse
                        </button>
                        <a href="{{ route('finance.expenses.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
