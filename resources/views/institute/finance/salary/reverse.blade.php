@extends('institute.layout')
@section('title', 'Reverse Salary Payment')
@section('breadcrumb', 'Finance / Salary / Reverse')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-arrow-counterclockwise me-2 text-danger"></i>Reverse Salary Payment</h4>
        <small class="text-muted">Paid salary ko reverse mark karke accounting reversal journal create karo</small>
    </div>
    <a href="{{ route('finance.salary.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="alert alert-danger border-0 shadow-sm">
            <div class="fw-semibold mb-1">Important</div>
            <div class="small mb-0">Salary payment delete nahi hogi. Record reversed state me chali jayegi aur posted payment ka reversal journal banega agar original entry mili.</div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="small text-muted">Staff</div>
                        <div class="fw-semibold">{{ $salaryRecord->staffMember?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Salary Period</div>
                        <div class="fw-semibold">{{ \Carbon\Carbon::createFromDate($salaryRecord->salary_year, $salaryRecord->salary_month, 1)->format('F Y') }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Paid Amount</div>
                        <div class="fw-semibold text-danger">Rs {{ number_format($salaryRecord->paid_amount, 2) }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Expense Head</div>
                        <div class="fw-semibold">{{ $salaryRecord->expenseAccount?->code }} - {{ $salaryRecord->expenseAccount?->name }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Payment Details</div>
                        <div class="fw-semibold">
                            {{ strtoupper($salaryRecord->payment_mode ?? '-') }}
                            @if($salaryRecord->payment_date)
                                / {{ $salaryRecord->payment_date->format('d M Y') }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('finance.salary.reverse.store', $salaryRecord) }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reversal Reason <span class="text-danger">*</span></label>
                        <textarea name="reversal_reason" rows="4" class="form-control" required
                                  placeholder="Salary reverse kyun kar rahe ho, short reason likho">{{ old('reversal_reason') }}</textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger px-4">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Confirm Reverse
                        </button>
                        <a href="{{ route('finance.salary.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
