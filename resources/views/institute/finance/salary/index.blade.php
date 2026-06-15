@extends($layout ?? 'institute.layout')
@section('title', 'Salary Book')
@section('breadcrumb', 'Finance / Salary')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-person-workspace me-2 text-primary"></i>Salary Book</h4>
        <small class="text-muted">Staff salary records, payment posting aur reversal status yahan manage karo</small>
    </div>
    @if(Route::has(($rp ?? 'finance') . '.salary.create'))
    <a href="{{ route(($rp ?? 'finance') . '.salary.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Salary Record
    </a>
    @endif
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Total Payable</div>
                <div class="fw-bold fs-4 text-primary">Rs {{ number_format($totalPayable, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Total Paid</div>
                <div class="fw-bold fs-4 text-success">Rs {{ number_format($totalPaid, 2) }}</div>
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
                    <th>Staff</th>
                    <th>Expense Head</th>
                    <th class="text-end">Net Payable</th>
                    <th class="text-end">Paid</th>
                    <th>Status</th>
                    <th class="pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salaryRecords as $record)
                <tr>
                    <td class="ps-3">
                        <div class="fw-semibold">{{ \Carbon\Carbon::createFromDate($record->salary_year, $record->salary_month, 1)->format('M Y') }}</div>
                        <div class="small text-muted">{{ $record->payment_date?->format('d M Y') ?: 'Unpaid' }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $record->staffMember?->name ?? '-' }}</div>
                        <div class="small text-muted">{{ $record->staffMember?->role?->name ?? 'Staff' }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $record->expenseAccount?->name ?? '-' }}</div>
                        <div class="small text-muted">{{ $record->expenseAccount?->code ?? '-' }}</div>
                    </td>
                    <td class="text-end fw-semibold">{{ number_format($record->net_payable, 2) }}</td>
                    <td class="text-end {{ $record->status === \App\Models\SalaryRecord::STATUS_PAID ? 'text-success fw-semibold' : 'text-muted' }}">
                        {{ number_format($record->paid_amount, 2) }}
                    </td>
                    <td>
                        @if($record->status === \App\Models\SalaryRecord::STATUS_REVERSED)
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Reversed</span>
                        @elseif($record->status === \App\Models\SalaryRecord::STATUS_PAID)
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                {{ $record->journal_entry_id ? 'Paid & Posted' : 'Paid' }}
                            </span>
                        @elseif($record->status === \App\Models\SalaryRecord::STATUS_APPROVED)
                            <span class="badge bg-info-subtle text-info border border-info-subtle">Approved</span>
                        @elseif($record->status === \App\Models\SalaryRecord::STATUS_DRAFT)
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Draft</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending</span>
                        @endif
                    </td>
                    <td class="pe-3">
                        @if(in_array($record->status, [\App\Models\SalaryRecord::STATUS_PENDING, \App\Models\SalaryRecord::STATUS_APPROVED], true))
                            @if(Route::has(($rp ?? 'finance') . '.salary.pay'))
                            <a href="{{ route(($rp ?? 'finance') . '.salary.pay', $record) }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-cash-coin me-1"></i> Pay
                            </a>
                            @else
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending Payment</span>
                            @endif
                        @elseif($record->status === \App\Models\SalaryRecord::STATUS_DRAFT)
                            <a href="{{ route(($rp ?? 'finance') . '.payroll.draft-view', ['year' => $record->salary_year, 'month' => $record->salary_month]) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-card-checklist me-1"></i> Review Draft
                            </a>
                        @elseif($record->status === \App\Models\SalaryRecord::STATUS_REVERSED)
                            <div class="small text-muted">Reason: {{ $record->reversal_reason ?: '-' }}</div>
                            @if($record->reversal_journal_entry_id)
                                <div class="small text-muted">Rev JE #{{ $record->reversal_journal_entry_id }}</div>
                            @endif
                        @else
                            <div class="d-flex flex-column gap-1">
                                @if(Route::has(($rp ?? 'finance') . '.salary.reverse'))
                                <a href="{{ route(($rp ?? 'finance') . '.salary.reverse', $record) }}" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reverse
                                </a>
                                @endif
                                <span class="small text-muted">{{ strtoupper($record->payment_mode ?? '-') }}</span>
                                @if($record->bankAccount)
                                    <div class="small text-muted">{{ $record->bankAccount->display_label ?: $record->bankAccount->bank_name }}</div>
                                @endif
                            </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-person-workspace fs-2 d-block mb-2"></i>
                        Abhi tak koi salary record create nahi hua hai.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
