@extends($layout ?? 'institute.layout')
@section('title', 'Finance Reconciliation')
@section('breadcrumb', 'Finance / Reconciliation')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-check2-square me-2 text-primary"></i>Finance Reconciliation</h4>
        <small class="text-muted">Operational records aur journal postings ke beech mismatch ko yahan track karo</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-filetype-csv me-1"></i> Export CSV
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i> Print / PDF
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    <option value="">All Sessions</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ (string) $sessionId === (string) $session->id ? 'selected' : '' }}>
                            {{ $session->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search me-1"></i> Apply
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Overall Difference</div>
                <div class="fw-bold fs-4 {{ $overallDifference == 0.0 ? 'text-success' : 'text-danger' }}">
                    Rs {{ number_format(abs($overallDifference), 2) }}{{ $overallDifference == 0.0 ? '' : ' Mismatch' }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Missing Journal Samples</div>
                <div class="fw-bold fs-4 {{ $totalMissing > 0 ? 'text-warning' : 'text-success' }}">{{ number_format($totalMissing) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Module</th>
                    <th class="text-end">Operational Total</th>
                    <th class="text-end">Journal Total</th>
                    <th class="text-end">Difference</th>
                    <th class="text-end pe-3">Missing Journals</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sections as $section)
                <tr>
                    <td class="ps-3 fw-semibold">{{ $section['label'] }}</td>
                    <td class="text-end">{{ number_format($section['operational_total'], 2) }}</td>
                    <td class="text-end">{{ number_format($section['journal_total'], 2) }}</td>
                    <td class="text-end {{ $section['difference'] == 0.0 ? 'text-success' : 'text-danger fw-semibold' }}">
                        {{ number_format($section['difference'], 2) }}
                    </td>
                    <td class="text-end pe-3 {{ $section['missing_count'] > 0 ? 'text-warning fw-semibold' : 'text-success' }}">
                        {{ number_format($section['missing_count']) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-semibold">Fee Collection Missing Journals</h6>
            </div>
            <div class="card-body">
                @forelse($missingFeeInvoices as $invoice)
                    <div class="border rounded p-2 mb-2">
                        <div class="fw-semibold">{{ $invoice->invoice_no }}</div>
                        <div class="small text-muted">{{ $invoice->student?->name ?? 'Student' }}</div>
                        <div class="small text-muted">{{ $invoice->payment_date?->format('d M Y') ?: '-' }}</div>
                    </div>
                @empty
                    <div class="text-muted small">Fee collection side par koi sample mismatch nahi mila.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-semibold">Expense Missing Journals</h6>
            </div>
            <div class="card-body">
                @forelse($missingExpenses as $expense)
                    <div class="border rounded p-2 mb-2">
                        <div class="fw-semibold">{{ $expense->expenseAccount?->name ?? 'Expense' }}</div>
                        <div class="small text-muted">{{ $expense->vendor_name ?: 'Internal expense' }}</div>
                        <div class="small text-muted">{{ $expense->expense_date?->format('d M Y') ?: '-' }} / Rs {{ number_format($expense->amount, 2) }}</div>
                    </div>
                @empty
                    <div class="text-muted small">Expense side par koi sample mismatch nahi mila.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-semibold">Salary Missing Journals</h6>
            </div>
            <div class="card-body">
                @forelse($missingSalaries as $record)
                    <div class="border rounded p-2 mb-2">
                        <div class="fw-semibold">{{ $record->staffMember?->name ?? 'Staff' }}</div>
                        <div class="small text-muted">{{ \Carbon\Carbon::createFromDate($record->salary_year, $record->salary_month, 1)->format('M Y') }}</div>
                        <div class="small text-muted">{{ $record->payment_date?->format('d M Y') ?: '-' }} / Rs {{ number_format($record->paid_amount, 2) }}</div>
                    </div>
                @empty
                    <div class="text-muted small">Salary side par koi sample mismatch nahi mila.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
