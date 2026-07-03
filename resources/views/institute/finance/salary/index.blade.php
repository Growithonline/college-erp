@extends($layout ?? 'institute.layout')
@section('title', 'Salary Book')
@section('breadcrumb', 'Finance / Salary')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-person-workspace me-2 text-primary"></i>Salary Book</h4>
        <small class="text-muted">All employee salary disbursements — manage from Employees section</small>
    </div>
    <a href="{{ route('employees.index') }}" class="btn btn-outline-primary btn-sm px-3">
        <i class="bi bi-people me-1"></i> Go to Employees
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Total Gross Payable</div>
                <div class="fw-bold fs-4 text-primary">₹{{ number_format($totalPayable, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Total Net Paid</div>
                <div class="fw-bold fs-4 text-success">₹{{ number_format($totalPaid, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Pending Records</div>
                <div class="fw-bold fs-4 text-warning">{{ number_format($pendingCount) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Period</th>
                    <th>Employee</th>
                    <th>Expense Head</th>
                    <th class="text-end">Gross</th>
                    <th class="text-end">Deductions</th>
                    <th class="text-end">Net Paid</th>
                    <th>Mode</th>
                    <th>Status</th>
                    <th class="pe-3">Journal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salaryRecords as $record)
                <tr>
                    <td class="ps-3">
                        <div class="fw-semibold">{{ date('M Y', mktime(0, 0, 0, $record->month, 1, $record->year)) }}</div>
                        <div class="small text-muted">{{ $record->payment_date?->format('d M Y') ?? '—' }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $record->employee?->name ?? '—' }}</div>
                        <div class="small text-muted">
                            {{ $record->employee?->department?->name ?? '' }}
                            @if($record->employee?->designation) · {{ $record->employee->designation->name }} @endif
                        </div>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $record->expenseAccount?->name ?? '—' }}</div>
                        <div class="small text-muted">{{ $record->expenseAccount?->code ?? '' }}</div>
                    </td>
                    <td class="text-end">₹{{ number_format($record->gross_salary, 2) }}</td>
                    <td class="text-end {{ (float) $record->deductions > 0 ? 'text-danger' : 'text-muted' }}">
                        {{ (float) $record->deductions > 0 ? '−₹' . number_format($record->deductions, 2) : '—' }}
                    </td>
                    <td class="text-end fw-semibold text-success">₹{{ number_format($record->net_salary, 2) }}</td>
                    <td>
                        <span class="badge text-bg-light text-dark border" style="font-size:11px;">
                            {{ strtoupper($record->payment_mode ?? '—') }}
                        </span>
                        @if($record->bankAccount)
                            <div class="small text-muted">{{ $record->bankAccount->bank_name }}</div>
                        @endif
                    </td>
                    <td>
                        @if($record->status === 'paid')
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                {{ $record->journal_entry_id ? 'Paid & Posted' : 'Paid' }}
                            </span>
                        @else
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending</span>
                        @endif
                    </td>
                    <td class="pe-3">
                        @if($record->journal_entry_id)
                            <span class="small text-muted">JE #{{ $record->journal_entry_id }}</span>
                        @else
                            <span class="small text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-5">
                        <i class="bi bi-person-workspace fs-2 d-block mb-2 opacity-25"></i>
                        No salary disbursements yet. Go to
                        <a href="{{ route('employees.index') }}">Employees</a>
                        to disburse salaries.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
