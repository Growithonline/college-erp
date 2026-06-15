@extends($layout ?? 'institute.layout')
@section('title', 'Ledger Report')
@section('breadcrumb', 'Finance / Ledger')

@section('content')
@php
    $formatBalance = function ($value, $normalSide) {
        $side = $value < 0 ? ($normalSide === 'debit' ? 'Cr' : 'Dr') : ($normalSide === 'debit' ? 'Dr' : 'Cr');
        return 'Rs ' . number_format(abs($value), 2) . ' ' . $side;
    };
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-journal-text me-2 text-primary"></i>Ledger Report</h4>
        <small class="text-muted">Single account ka opening, transaction-wise movement aur closing balance dekho</small>
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
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Account</label>
                <select name="account_id" class="form-select form-select-sm">
                    <option value="">Select account</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ (string) request('account_id') === (string) $account->id ? 'selected' : '' }}>
                            {{ $account->code }} - {{ $account->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
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
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-2">
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

@if($selectedAccount)
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Selected Account</div>
                <div class="fw-bold">{{ $selectedAccount->code }} - {{ $selectedAccount->name }}</div>
                <div class="small text-muted">{{ ucfirst($selectedAccount->type) }} / Normal {{ ucfirst($selectedAccount->normal_side) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Opening Balance</div>
                <div class="fw-bold fs-5 text-primary">{{ $formatBalance($openingBalance, $selectedAccount->normal_side) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Closing Balance</div>
                <div class="fw-bold fs-5 text-success">{{ $formatBalance($closingBalance, $selectedAccount->normal_side) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Date</th>
                    <th>Narration</th>
                    <th>Reference</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
                    <th class="text-end pe-3">Running Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr class="table-secondary">
                    <td class="ps-3 fw-semibold">{{ \Illuminate\Support\Carbon::parse($dateFrom)->format('d M Y') }}</td>
                    <td class="fw-semibold">Opening Balance</td>
                    <td class="text-muted">Opening</td>
                    <td class="text-end">-</td>
                    <td class="text-end">-</td>
                    <td class="text-end pe-3 fw-semibold">{{ $formatBalance($openingBalance, $selectedAccount->normal_side) }}</td>
                </tr>
                @forelse($rows as $row)
                <tr>
                    <td class="ps-3 text-muted">{{ $row['date']?->format('d M Y') }}</td>
                    <td>
                        <div class="fw-semibold">{{ $row['narration'] ?: 'Journal Entry' }}</div>
                        <div class="small text-muted">{{ ucfirst(str_replace('_', ' ', $row['reference_type'] ?? 'manual')) }}</div>
                    </td>
                    <td class="small text-muted">{{ $row['reference_id'] ? '#' . $row['reference_id'] : '-' }}</td>
                    <td class="text-end text-primary">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '-' }}</td>
                    <td class="text-end text-danger">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '-' }}</td>
                    <td class="text-end pe-3 fw-semibold">{{ $formatBalance($row['balance'], $selectedAccount->normal_side) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                        Is period me koi journal movement nahi mila.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@else
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-journal-text fs-1 d-block mb-2"></i>
        Ledger dekhne ke liye ek account select karo.
    </div>
</div>
@endif
@endsection
