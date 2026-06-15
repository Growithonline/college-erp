@extends($layout ?? 'institute.layout')
@section('title', 'Bank Book')
@section('breadcrumb', 'Finance / Bank Book')

@section('content')
@php
    $formatBalance = function ($value, $normalSide) {
        $side = $value < 0 ? ($normalSide === 'debit' ? 'Cr' : 'Dr') : ($normalSide === 'debit' ? 'Dr' : 'Cr');
        return 'Rs ' . number_format(abs($value), 2) . ' ' . $side;
    };
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-bank2 me-2 text-primary"></i>Bank Book</h4>
        <small class="text-muted">Mapped bank account ka ledger-style movement aur balance</small>
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
                <label class="form-label small fw-semibold">Bank Account</label>
                <select name="bank_account_id" class="form-select form-select-sm">
                    <option value="">Select bank account</option>
                    @foreach($bankAccounts as $bankAccount)
                        <option value="{{ $bankAccount->id }}" {{ (string) request('bank_account_id') === (string) $bankAccount->id ? 'selected' : '' }}>
                            {{ $bankAccount->display_label ?: $bankAccount->bank_name }}
                        </option>
                    @endforeach
                </select>
            </div>
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

@if($selectedBankAccount && !$selectedGlAccount)
<div class="alert alert-warning border-0 shadow-sm">
    <div class="fw-semibold mb-1">Selected bank account ka GL mapping missing hai</div>
    <div class="small mb-0">Finance Settings ya Bank Account mapping me is bank ko ek GL account assign karo.</div>
</div>
@endif

@if($selectedGlAccount)
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Bank Account</div>
                <div class="fw-bold">{{ $selectedBankAccount->display_label ?: $selectedBankAccount->bank_name }}</div>
                <div class="small text-muted">{{ $selectedBankAccount->account_no }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Opening Balance</div>
                <div class="fw-bold fs-5 text-primary">{{ $formatBalance($openingBalance, $selectedGlAccount->normal_side) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Closing Balance</div>
                <div class="fw-bold fs-5 text-success">{{ $formatBalance($closingBalance, $selectedGlAccount->normal_side) }}</div>
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
                    <td class="text-end pe-3 fw-semibold">{{ $formatBalance($openingBalance, $selectedGlAccount->normal_side) }}</td>
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
                    <td class="text-end pe-3 fw-semibold">{{ $formatBalance($row['balance'], $selectedGlAccount->normal_side) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">Selected bank account me is period ka koi movement nahi mila.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@else
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-bank2 fs-1 d-block mb-2"></i>
        Bank Book dekhne ke liye ek mapped bank account select karo.
    </div>
</div>
@endif
@endsection
