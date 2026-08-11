@extends('group_admin.layout')
@section('title', 'Daily Register')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('group_admin.institutes.index') }}" class="text-decoration-none">Institutes</a></li>
    <li class="breadcrumb-item active">Daily Register</li>
@endsection

@section('content')
<div class="mb-4">
    <h4 class="mb-0 fw-bold"><i class="bi bi-journal-richtext me-2 text-primary"></i>Daily Register</h4>
    <small class="text-muted">Day-wise income &amp; expense register (read-only)</small>
</div>

{{-- Filter Form --}}
<form method="GET" action="{{ url()->current() }}" class="card border-0 shadow-sm mb-4">
    <div class="card-body pb-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold small mb-1">Date</label>
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" onchange="this.form.submit()">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small mb-1">Session</label>
                <select name="session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>{{ $s->name }}{{ $s->is_active ? ' (Active)' : '' }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</form>

{{-- Receipt mode reference --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><i class="bi bi-pencil-square text-secondary me-2"></i>Cash <small class="text-muted d-block">(reference only)</small></div>
                <div class="text-end">
                    <div class="fw-bold">₹{{ number_format($receiptModeSplit['by_hand']['amount'], 2) }}</div>
                    <small class="text-muted">{{ $receiptModeSplit['by_hand']['count'] }} receipts</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center {{ $receiptModeSplit['bank_wise']->isNotEmpty() ? 'mb-2' : '' }}">
                    <div><i class="bi bi-laptop text-secondary me-2"></i>Online <small class="text-muted">(reference only)</small></div>
                    <div class="text-end">
                        <div class="fw-bold">₹{{ number_format($receiptModeSplit['computerized']['amount'], 2) }}</div>
                        <small class="text-muted">{{ $receiptModeSplit['computerized']['count'] }} receipts</small>
                    </div>
                </div>
                @if($receiptModeSplit['bank_wise']->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-sm mb-0 small">
                        <tbody>
                            @foreach($receiptModeSplit['bank_wise'] as $bank)
                            <tr data-bs-toggle="collapse" data-bs-target="#drBank{{ $loop->index }}" style="cursor:pointer">
                                <td><i class="bi bi-chevron-down small me-1 text-muted"></i>{{ $bank['bank_name'] }}</td>
                                <td class="text-end text-muted">{{ $bank['count'] }} txns</td>
                                <td class="text-end fw-semibold">₹{{ number_format($bank['amount'], 2) }}</td>
                            </tr>
                            <tr class="collapse" id="drBank{{ $loop->index }}">
                                <td colspan="3" class="p-0">
                                    <div class="px-3 py-2" style="background:#f8fafc">
                                        @foreach($bank['modes'] as $m)
                                            <span class="badge bg-light text-dark border me-1">{{ $m['mode'] }}: {{ $m['count'] }} (₹{{ number_format($m['amount'], 0) }})</span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Income table --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3"><h6 class="mb-0 fw-semibold text-success"><i class="bi bi-cash-coin me-2"></i>Income</h6></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>#</th><th>Particular</th><th class="text-end">Count</th>
                    @foreach($yearLabels as $label)<th>{{ $label }}</th>@endforeach
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($incomeRows as $i => $row)
                <tr>
                    <td class="text-muted">{{ $i + 1 }}</td>
                    <td class="fw-semibold">{{ $row['name'] }}</td>
                    <td class="text-end">{{ $row['count'] }}</td>
                    @foreach($yearLabels as $y => $label)
                        <td class="text-muted">
                            @if(!empty($row['year_data'][$y]))
                                @foreach($row['year_data'][$y]['sub_columns'] as $sub)
                                    <span class="badge bg-light text-dark border me-1">{{ $sub['label'] }}: {{ $sub['count'] }} (₹{{ number_format($sub['amount'], 0) }})</span>
                                @endforeach
                            @else
                                —
                            @endif
                        </td>
                    @endforeach
                    <td class="text-end fw-semibold text-success">₹{{ number_format($row['amount'], 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="{{ 4 + count($yearLabels) }}" class="text-center text-muted py-3">No income particulars configured.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr><td colspan="{{ 3 + count($yearLabels) }}">Total (A)</td><td class="text-end text-success">₹{{ number_format($totalIncome, 2) }}</td></tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- Expense table --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3"><h6 class="mb-0 fw-semibold text-danger"><i class="bi bi-cash-stack me-2"></i>Expenses</h6></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light"><tr><th>#</th><th>Particular</th><th class="text-end">Count</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                @forelse($expenseRows as $i => $row)
                <tr>
                    <td class="text-muted">{{ $i + 1 }}</td>
                    <td class="fw-semibold">{{ $row['name'] }}</td>
                    <td class="text-end">{{ $row['count'] }}</td>
                    <td class="text-end fw-semibold text-danger">₹{{ number_format($row['amount'], 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-3">No expense particulars configured.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr><td colspan="3">Total (B)</td><td class="text-end text-danger">₹{{ number_format($totalExpense, 2) }}</td></tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- Totals block --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row text-center g-3">
            <div class="col-md-3">
                <small class="text-muted d-block">Last Balance</small>
                <div class="fw-bold fs-5">₹{{ number_format($lastBalance, 2) }}</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Total Income (A)</small>
                <div class="fw-bold fs-5 text-success">+ ₹{{ number_format($totalIncome, 2) }}</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Total Expense (B)</small>
                <div class="fw-bold fs-5 text-danger">− ₹{{ number_format($totalExpense, 2) }}</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Grand Total</small>
                <div class="fw-bold fs-4 text-primary">₹{{ number_format($grandTotal, 2) }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
