@extends('group_admin.layout')
@section('title', 'Wallet Ledger')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('group_admin.institutes.index') }}" class="text-decoration-none">Institutes</a></li>
    <li class="breadcrumb-item active">Wallet Ledger</li>
@endsection

@section('content')
<div class="mb-3">
    <h4 class="mb-0 fw-bold"><i class="bi bi-journal-text me-2 text-primary"></i>Wallet Ledger</h4>
    <small class="text-muted">Full transaction audit trail (read-only)</small>
</div>

{{-- Filter Form --}}
<form method="GET" id="ledgerFilterForm" action="{{ url()->current() }}" class="card border-0 shadow-sm mb-3">
<div class="card-body pb-2">
    <div class="row g-2 align-items-end mb-2">
        <div class="col-md-2">
            <label class="form-label fw-semibold small mb-1">Session</label>
            <select name="session_id" class="form-select form-select-sm">
                @foreach($sessions as $s)
                    <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>{{ $s->name }} {{ $s->is_active ? '(Active)' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small mb-1">Date Range</label>
            <div class="d-flex gap-2 align-items-center">
                <input type="date" name="from" id="fromDate" class="form-control form-control-sm" value="{{ $from }}">
                <span class="text-muted small">to</span>
                <input type="date" name="to" id="toDate" class="form-control form-control-sm" value="{{ $to }}">
            </div>
            <div class="mt-1 d-flex gap-1">
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="setPreset('today')" style="font-size:11px">Today</button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="setPreset('month')" style="font-size:11px">This Month</button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="setPreset('all')" style="font-size:11px">All</button>
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold small mb-1">Source Type</label>
            <select name="source_type" class="form-select form-select-sm">
                <option value="">-- All Sources --</option>
                @foreach($allSourceTypes as $src)
                    <option value="{{ $src }}" {{ $sourceType == $src ? 'selected' : '' }}>{{ $sourceLabels[$src] ?? $src }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold small mb-1">Flow</label>
            <select name="flow" class="form-select form-select-sm">
                <option value="">-- Income + Expense --</option>
                <option value="income" {{ ($flow ?? '') === 'income' ? 'selected' : '' }}>Income Only</option>
                <option value="expense" {{ ($flow ?? '') === 'expense' ? 'selected' : '' }}>Expense Only</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i> Filter</button>
        </div>
    </div>

    <div class="row g-2 align-items-end border-top pt-2">
        <div class="col-md-2">
            <label class="form-label fw-semibold small mb-1">Payment Type</label>
            <select name="payment_type" class="form-select form-select-sm">
                <option value="">-- Cash + Non-Cash --</option>
                <option value="cash" {{ ($paymentType ?? '') === 'cash' ? 'selected' : '' }}>Cash Only</option>
                <option value="non_cash" {{ ($paymentType ?? '') === 'non_cash' ? 'selected' : '' }}>Non-Cash Only</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold small mb-1">Bank Account</label>
            <select name="bank_account_id" class="form-select form-select-sm">
                <option value="">-- All Banks --</option>
                @foreach($bankAccounts as $ba)
                    <option value="{{ $ba->id }}" {{ $bankAccountId == $ba->id ? 'selected' : '' }}>{{ $ba->account_name ?? $ba->bank_name }} — {{ $ba->account_no }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold small mb-1">Amount From (₹)</label>
            <input type="number" name="amount_min" class="form-control form-control-sm" placeholder="0" value="{{ $amountMin ?? '' }}" min="0" step="0.01">
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold small mb-1">Amount To (₹)</label>
            <input type="number" name="amount_max" class="form-control form-control-sm" placeholder="Any" value="{{ $amountMax ?? '' }}" min="0" step="0.01">
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold small mb-1">Collected By</label>
            <select name="collector" class="form-select form-select-sm">
                <option value="">-- All Collectors --</option>
                @foreach($collectorOptions as $opt)
                    <option value="{{ $opt['key'] }}" {{ $collectorKey === $opt['key'] ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                @endforeach
            </select>
        </div>

        @if($paymentType || $bankAccountId || $flow || $amountMin || $amountMax || $collectorKey)
        <div class="col-md-1">
            <a href="{{ url()->current() }}?session_id={{ $sessionId }}&from={{ $from }}&to={{ $to }}&filtered=1" class="btn btn-outline-secondary btn-sm w-100 px-1" title="Clear Filters">
                <i class="bi bi-x-circle"></i>
            </a>
        </div>
        @endif
    </div>
</div>
</form>
<input type="hidden" name="filtered" form="ledgerFilterForm" value="1">

{{-- Opening / Closing Balance --}}
@if($from && $openingBalance !== null)
<div class="d-flex gap-3 mb-3">
    <div class="card border-0 shadow-sm px-4 py-2 d-flex flex-row align-items-center gap-3">
        <div>
            <div class="text-muted small">Opening Balance</div>
            <div class="fw-bold {{ $openingBalance >= 0 ? 'text-success' : 'text-danger' }}">₹{{ number_format($openingBalance, 2) }}</div>
            <div class="text-muted" style="font-size:10px">as of {{ \Carbon\Carbon::parse($from)->format('d M Y') }}</div>
        </div>
        @if($transactions->isNotEmpty())
        <div class="border-start ps-3">
            <div class="text-muted small">Closing Balance</div>
            <div class="fw-bold text-primary">₹{{ number_format($transactions->first()->cl_bal, 2) }}</div>
            <div class="text-muted" style="font-size:10px">latest transaction</div>
        </div>
        @endif
    </div>
</div>
@endif

{{-- Ledger Table --}}
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
            <thead class="table-dark">
                <tr>
                    <th>#</th><th>Session</th><th>Date</th><th>Remark</th><th>Category</th><th>Receipt No.</th>
                    <th>Ref. No.</th><th>Type</th><th>Bank Account</th>
                    <th class="text-end text-success">Income</th><th class="text-end text-danger">Expense</th>
                    <th class="text-end">Op. Bal</th><th class="text-end">Balance</th><th>User</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalIncome = 0; $totalExpense = 0; $prevDate = null;
                    $dayIncome = 0; $dayExpense = 0; $serial = 0;
                @endphp
                @forelse($transactions as $tx)
                @php
                    $serial++;
                    $totalIncome += $tx->credit;
                    $totalExpense += $tx->debit;
                    $sd = $sourceData[$tx->id] ?? [];
                    $currentDate = $tx->date->toDateString();
                @endphp

                @if($prevDate !== null && $currentDate !== $prevDate)
                <tr class="table-secondary border-0">
                    <td colspan="9" class="text-end text-muted py-1" style="font-size:11px">Day Total &nbsp; {{ \Carbon\Carbon::parse($prevDate)->format('d M Y') }}</td>
                    <td class="text-end text-success fw-semibold py-1" style="font-size:11px">{{ $dayIncome > 0 ? number_format($dayIncome, 2) : '-' }}</td>
                    <td class="text-end text-danger fw-semibold py-1" style="font-size:11px">{{ $dayExpense > 0 ? number_format($dayExpense, 2) : '-' }}</td>
                    <td colspan="3" class="py-1"></td>
                </tr>
                @php $dayIncome = 0; $dayExpense = 0; @endphp
                @endif

                @if($currentDate !== $prevDate)
                <tr class="table-light">
                    <td colspan="14" class="py-1 px-3 border-top-0">
                        <span class="fw-semibold text-primary" style="font-size:11px"><i class="bi bi-calendar3 me-1"></i>{{ $tx->date->format('d F Y, l') }}</span>
                    </td>
                </tr>
                @php $prevDate = $currentDate; @endphp
                @endif

                @php $dayIncome += $tx->credit; $dayExpense += $tx->debit; @endphp

                <tr>
                    <td class="text-muted ps-3">{{ ($transactions->currentPage() - 1) * $transactions->perPage() + $serial }}</td>
                    <td class="text-nowrap text-muted small">{{ $tx->session?->name ?? '-' }}</td>
                    <td class="text-nowrap">{{ $tx->date->format('d-m-Y') }}</td>
                    <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $tx->des }}{{ ($sd['fee_heads'] ?? '') ? ' | ' . $sd['fee_heads'] : '' }}">
                        {{ $tx->des }}
                        @if($sd['fee_heads'] ?? '')
                            <div class="text-muted" style="font-size:9px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $sd['fee_heads'] }}</div>
                        @endif
                    </td>
                    <td><span class="badge {{ str_starts_with($sd['category'] ?? '', 'INCOME') ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }} small">{{ $sd['category'] ?? '-' }}</span></td>
                    <td class="text-nowrap">{{ $sd['receipt_no'] ?? '-' }}</td>
                    <td class="text-muted text-nowrap small">{{ $sd['payment_ref'] ?? '-' }}</td>
                    <td>
                        @php $pt = $sd['pay_type'] ?? '-'; @endphp
                        @if($pt !== '-')<span class="badge bg-secondary bg-opacity-10 text-dark">{{ $pt }}</span>@else<span class="text-muted">-</span>@endif
                    </td>
                    <td class="small text-nowrap">
                        @if(($sd['bank_account'] ?? '-') !== '-')<span class="text-primary"><i class="bi bi-bank me-1"></i>{{ $sd['bank_account'] }}</span>@else<span class="text-muted">-</span>@endif
                    </td>
                    <td class="text-end text-success fw-semibold">{{ $tx->credit > 0 ? number_format($tx->credit, 2) : '-' }}</td>
                    <td class="text-end text-danger fw-semibold">{{ $tx->debit > 0 ? number_format($tx->debit, 2) : '-' }}</td>
                    <td class="text-end text-muted">{{ number_format($tx->op_bal, 2) }}</td>
                    <td class="text-end fw-bold {{ $tx->cl_bal >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($tx->cl_bal, 2) }}</td>
                    <td class="text-muted small">{{ $sd['user_name'] ?? '-' }}</td>
                </tr>

                @empty
                <tr><td colspan="14" class="text-center text-muted py-5"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No transactions found.</td></tr>
                @endforelse

                @if($transactions->isNotEmpty() && $prevDate)
                <tr class="table-secondary border-0">
                    <td colspan="9" class="text-end text-muted py-1" style="font-size:11px">Day Total &nbsp; {{ \Carbon\Carbon::parse($prevDate)->format('d M Y') }}</td>
                    <td class="text-end text-success fw-semibold py-1" style="font-size:11px">{{ $dayIncome > 0 ? number_format($dayIncome, 2) : '-' }}</td>
                    <td class="text-end text-danger fw-semibold py-1" style="font-size:11px">{{ $dayExpense > 0 ? number_format($dayExpense, 2) : '-' }}</td>
                    <td colspan="3" class="py-1"></td>
                </tr>
                @endif
            </tbody>
            @if($transactions->isNotEmpty())
            <tfoot class="table-dark">
                <tr>
                    <td colspan="9" class="fw-semibold">Page Total</td>
                    <td class="text-end text-success fw-bold">{{ number_format($totalIncome, 2) }}</td>
                    <td class="text-end text-danger fw-bold">{{ number_format($totalExpense, 2) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    <div class="p-3 border-top">
        @include('institute.components.pagination', ['paginator' => $transactions, 'perPage' => 50])
    </div>
</div>

@push('scripts')
<script>
const TODAY = '{{ now()->toDateString() }}';
const MONTH_START = '{{ now()->startOfMonth()->toDateString() }}';
function setPreset(preset) {
    const from = document.getElementById('fromDate');
    const to = document.getElementById('toDate');
    if (preset === 'today') { from.value = TODAY; to.value = TODAY; }
    else if (preset === 'month') { from.value = MONTH_START; to.value = TODAY; }
    else { from.value = ''; to.value = ''; }
    document.getElementById('ledgerFilterForm').submit();
}
</script>
@endpush
@endsection
