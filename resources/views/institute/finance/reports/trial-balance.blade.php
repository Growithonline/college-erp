@extends($layout ?? 'institute.layout')
@section('title', 'Trial Balance')
@section('breadcrumb', 'Finance / Trial Balance')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-table me-2 text-primary"></i>Trial Balance</h4>
        <small class="text-muted">Date tak ke all accounts ke net debit/credit balances</small>
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
                <label class="form-label small fw-semibold">As On Date</label>
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
                <div class="small text-muted">Total Debit</div>
                <div class="fw-bold fs-4 text-primary">Rs {{ number_format($totalDebit, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Total Credit</div>
                <div class="fw-bold fs-4 text-success">Rs {{ number_format($totalCredit, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Code</th>
                    <th>Account</th>
                    <th>Type</th>
                    <th class="text-end">Net Debit</th>
                    <th class="text-end pe-3">Net Credit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                <tr>
                    <td class="ps-3 fw-semibold">{{ $row['account']->code }}</td>
                    <td>
                        <div class="fw-semibold">{{ $row['account']->name }}</div>
                        <div class="small text-muted">Normal {{ ucfirst($row['account']->normal_side) }}</div>
                    </td>
                    <td class="text-muted small">{{ ucfirst($row['account']->type) }}</td>
                    <td class="text-end text-primary">{{ $row['debit_balance'] > 0 ? number_format($row['debit_balance'], 2) : '-' }}</td>
                    <td class="text-end pe-3 text-success">{{ $row['credit_balance'] > 0 ? number_format($row['credit_balance'], 2) : '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-5">
                        <i class="bi bi-table fs-2 d-block mb-2"></i>
                        Is date tak koi trial balance data nahi mila.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($rows->count() > 0)
            <tfoot class="table-dark">
                <tr>
                    <td class="ps-3 fw-bold" colspan="3">Total</td>
                    <td class="text-end fw-bold">{{ number_format($totalDebit, 2) }}</td>
                    <td class="text-end pe-3 fw-bold">{{ number_format($totalCredit, 2) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
