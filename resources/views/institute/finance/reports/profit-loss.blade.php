@extends($layout ?? 'institute.layout')
@section('title', 'Profit & Loss')
@section('breadcrumb', 'Finance / Profit & Loss')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Profit & Loss</h4>
        <small class="text-muted">Selected period ka income, expense aur net result</small>
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
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Total Income</div>
                <div class="fw-bold fs-4 text-success">Rs {{ number_format($totalIncome, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Total Expense</div>
                <div class="fw-bold fs-4 text-danger">Rs {{ number_format($totalExpense, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Net Result</div>
                <div class="fw-bold fs-4 {{ $netResult >= 0 ? 'text-primary' : 'text-danger' }}">
                    Rs {{ number_format(abs($netResult), 2) }} {{ $netResult >= 0 ? 'Surplus' : 'Deficit' }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-semibold">Income</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Code</th>
                            <th>Account</th>
                            <th class="text-end pe-3">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groupedIncomeRows as $group)
                        <tr class="table-light">
                            <td class="ps-3 fw-bold" colspan="2">{{ $group['label'] }}</td>
                            <td class="text-end pe-3 fw-bold text-success">{{ number_format($group['total'], 2) }}</td>
                        </tr>
                            @foreach($group['rows'] as $row)
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $row['account']->code }}</td>
                                <td>{{ $row['account']->name }}</td>
                                <td class="text-end pe-3 text-success fw-semibold">{{ number_format($row['amount'], 2) }}</td>
                            </tr>
                            @endforeach
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">No income journal data found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td class="ps-3 fw-bold" colspan="2">Total Income</td>
                            <td class="text-end pe-3 fw-bold text-success">{{ number_format($totalIncome, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-semibold">Expenses</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Code</th>
                            <th>Account</th>
                            <th class="text-end pe-3">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groupedExpenseRows as $group)
                        <tr class="table-light">
                            <td class="ps-3 fw-bold" colspan="2">{{ $group['label'] }}</td>
                            <td class="text-end pe-3 fw-bold text-danger">{{ number_format($group['total'], 2) }}</td>
                        </tr>
                            @foreach($group['rows'] as $row)
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $row['account']->code }}</td>
                                <td>{{ $row['account']->name }}</td>
                                <td class="text-end pe-3 text-danger fw-semibold">{{ number_format($row['amount'], 2) }}</td>
                            </tr>
                            @endforeach
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">No expense journal data found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td class="ps-3 fw-bold" colspan="2">Total Expense</td>
                            <td class="text-end pe-3 fw-bold text-danger">{{ number_format($totalExpense, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
