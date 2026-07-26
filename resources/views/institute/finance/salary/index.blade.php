@extends($layout ?? 'institute.layout')
@section('title', 'Salary Book')
@section('breadcrumb', 'Finance / Salary')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-person-workspace me-2 text-primary"></i>Salary Book</h4>
        <small class="text-muted">Teaching/Office staff (Payroll) aur Transport/Support staff (Employees) ki saari salary ek hi jagah</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('employees.index') }}" class="btn btn-outline-primary btn-sm px-3">
            <i class="bi bi-people me-1"></i> Employees
        </a>
        <a href="{{ route('finance.salary.create') }}" class="btn btn-primary btn-sm px-3">
            <i class="bi bi-plus-lg me-1"></i> Add Staff Salary
        </a>
    </div>
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
                    <th>Type</th>
                    <th>Name</th>
                    <th>Expense Head</th>
                    <th class="text-end">Gross</th>
                    <th class="text-end">Deductions</th>
                    <th class="text-end">Net Paid</th>
                    <th>Mode</th>
                    <th>Status</th>
                    <th>Journal</th>
                    <th class="pe-3">Actions</th>
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
                        @if($record->type === 'staff')
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Staff</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Employee</span>
                        @endif
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $record->name }}</div>
                        <div class="small text-muted">{{ $record->sub_label }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $record->expense_head?->name ?? '—' }}</div>
                        <div class="small text-muted">{{ $record->expense_head?->code ?? '' }}</div>
                    </td>
                    <td class="text-end">₹{{ number_format($record->gross, 2) }}</td>
                    <td class="text-end {{ $record->deductions > 0 ? 'text-danger' : 'text-muted' }}">
                        {{ $record->deductions > 0 ? '−₹' . number_format($record->deductions, 2) : '—' }}
                    </td>
                    <td class="text-end fw-semibold text-success">₹{{ number_format($record->net, 2) }}</td>
                    <td>
                        <span class="badge text-bg-light text-dark border" style="font-size:11px;">
                            {{ strtoupper($record->payment_mode ?? '—') }}
                        </span>
                        @if($record->bank_account)
                            <div class="small text-muted">{{ $record->bank_account->bank_name }}</div>
                        @endif
                    </td>
                    <td>
                        @if($record->status === 'reversed')
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Reversed</span>
                        @elseif($record->status === 'paid')
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                {{ $record->journal_entry_id ? 'Paid & Posted' : 'Paid' }}
                            </span>
                        @elseif($record->status === 'approved')
                            <span class="badge bg-info-subtle text-info border border-info-subtle">Approved</span>
                        @elseif($record->status === 'draft')
                            <span class="badge bg-light text-dark border">Draft</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending</span>
                        @endif
                    </td>
                    <td>
                        @if($record->status === 'reversed')
                            <span class="small text-muted">
                                {{ $record->reversal_journal_entry_id ? 'Rev JE #' . $record->reversal_journal_entry_id : 'Reversed' }}
                            </span>
                        @elseif($record->journal_entry_id)
                            <span class="small text-muted">JE #{{ $record->journal_entry_id }}</span>
                        @else
                            <span class="small text-muted">—</span>
                        @endif
                    </td>
                    <td class="pe-3">
                        @if($record->pay_route)
                            <a href="{{ $record->pay_route }}" class="btn btn-sm btn-outline-primary">Pay</a>
                        @endif
                        @if($record->reverse_route)
                            <a href="{{ $record->reverse_route }}" class="btn btn-sm btn-outline-danger">Reverse</a>
                        @endif
                        @if($record->profile_route)
                            <a href="{{ $record->profile_route }}" class="btn btn-sm btn-outline-secondary">View</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center text-muted py-5">
                        <i class="bi bi-person-workspace fs-2 d-block mb-2 opacity-25"></i>
                        Koi salary record nahi mila. <a href="{{ route('finance.salary.create') }}">Staff salary add karo</a>
                        ya <a href="{{ route('employees.index') }}">Employees</a> se transport/support staff ki salary disburse karo.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
