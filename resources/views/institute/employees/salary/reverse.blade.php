@extends('institute.layout')
@section('title', 'Reverse Salary Disbursement')
@section('breadcrumb', 'Employees / ' . $employee->name . ' / Salary / Reverse')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-arrow-counterclockwise me-2 text-danger"></i>Reverse Salary Disbursement</h4>
        <small class="text-muted">{{ $employee->name }}</small>
    </div>
    <a href="{{ route('employees.salary.disbursements', $employee) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="alert alert-danger border-0 shadow-sm">
            <div class="fw-semibold mb-1">Important</div>
            <div class="small mb-0">
                The disbursement will not be deleted — the record will be marked as <strong>reversed</strong>.
                If a journal entry was posted, a reversal journal will be created automatically
                and the net salary will be credited back to the institute wallet.
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="small text-muted">Employee</div>
                        <div class="fw-semibold">{{ $employee->name }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Salary Period</div>
                        <div class="fw-semibold">{{ $disbursement->month_name }} {{ $disbursement->year }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Net Salary Paid</div>
                        <div class="fw-semibold text-danger">₹{{ number_format($disbursement->net_salary, 2) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Gross Salary</div>
                        <div class="fw-semibold">₹{{ number_format($disbursement->gross_salary, 2) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Payment Mode</div>
                        <div class="fw-semibold">{{ strtoupper($disbursement->payment_mode ?? '—') }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Paid On</div>
                        <div class="fw-semibold">{{ $disbursement->payment_date?->format('d M Y') ?? '—' }}</div>
                    </div>
                    @if($disbursement->journal_entry_id)
                    <div class="col-12">
                        <div class="small text-muted">Posted Journal Entry</div>
                        <div class="fw-semibold">JE #{{ $disbursement->journal_entry_id }} — will be automatically reversed</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('employees.salary.reverse', [$employee, $disbursement]) }}">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Reversal Reason <span class="text-danger">*</span></label>
                        <textarea name="reversal_reason" rows="4" class="form-control @error('reversal_reason') is-invalid @enderror"
                                  required maxlength="300"
                                  placeholder="Reason for reversing this salary disbursement...">{{ old('reversal_reason') }}</textarea>
                        @error('reversal_reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger px-4">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Confirm Reverse
                        </button>
                        <a href="{{ route('employees.salary.disbursements', $employee) }}" class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
