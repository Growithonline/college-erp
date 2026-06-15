@extends('institute.layout')
@section('title', 'Institute Wallet')
@section('breadcrumb', 'Finance / Wallet')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-wallet2 me-2 text-success"></i>Institute Wallet Dashboard</h4>
        <small class="text-muted">Session-wise income aur expense ka real-time overview</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('finance.wallet.manual-income.create') }}" class="btn btn-success btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Add Income
        </a>
        <a href="{{ route('finance.wallet.contra.index') }}" class="btn btn-info btn-sm text-white">
            <i class="bi bi-arrow-left-right me-1"></i> Contra
        </a>
        <a href="{{ route('finance.wallet.ledger') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-journal-text me-1"></i> Full Ledger
        </a>
    </div>
</div>

{{-- ── Session selector ─────────────────────────────────────────────── --}}
<div class="d-flex gap-3 align-items-center mb-4 flex-wrap">
    <form method="GET" class="d-flex gap-2 align-items-center">
        <label class="form-label mb-0 fw-semibold text-nowrap small">Session:</label>
        <select name="session_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            @foreach($sessions as $s)
                <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>
                    {{ $s->name }} {{ $s->is_active ? '(Active)' : '' }}
                </option>
            @endforeach
        </select>
    </form>

    <form method="POST" action="{{ route('finance.wallet.threshold.update') }}" class="d-flex gap-2 align-items-center">
        @csrf
        <label class="form-label mb-0 small text-muted text-nowrap">Low Balance Alert at:</label>
        <div class="input-group input-group-sm" style="width:200px">
            <span class="input-group-text">₹</span>
            <input type="number" name="wallet_low_balance_threshold" step="100" min="0"
                   class="form-control" value="{{ $threshold > 0 ? $threshold : '' }}" placeholder="0 = off">
            <button class="btn btn-outline-secondary" type="submit">Set</button>
        </div>
    </form>
</div>

{{-- ── Smart Alerts (Phase 7) ────────────────────────────────────────── --}}
@if($lowBalance)
<div class="alert alert-danger border-0 shadow-sm mb-3 d-flex align-items-center gap-3">
    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
    <div>
        <div class="fw-bold">Low Wallet Balance!</div>
        <div class="small">Balance <strong>₹{{ number_format($summary['balance'], 2) }}</strong> is below the alert threshold of ₹{{ number_format($threshold, 2) }}.</div>
    </div>
</div>
@endif

@if($pendingApprovals > 0)
<div class="alert alert-warning border-0 shadow-sm mb-3 d-flex justify-content-between align-items-center">
    <div><i class="bi bi-hourglass-split me-2"></i><strong>{{ $pendingApprovals }}</strong> expense(s) awaiting approval.</div>
    <a href="{{ route('finance.wallet.expense-approvals.index') }}" class="btn btn-warning btn-sm">Review Now</a>
</div>
@endif

@if($staleChequesCount > 0)
<div class="alert border-0 shadow-sm mb-3 d-flex justify-content-between align-items-center"
     style="background:#fff3cd;border-left:4px solid #ffc107 !important">
    <div>
        <i class="bi bi-clock-history me-2 text-warning"></i>
        <strong>{{ $staleChequesCount }}</strong> cheque(s) are more than 7 days old and still <strong>Pending</strong>.
    </div>
    <a href="{{ route('finance.wallet.cheques.index', ['status' => 'pending']) }}" class="btn btn-warning btn-sm">Dekho</a>
</div>
@endif

@if($bouncedThisMonth > 0)
<div class="alert alert-danger border-0 shadow-sm mb-3 d-flex justify-content-between align-items-center">
    <div>
        <i class="bi bi-x-circle me-2"></i>
        <strong>{{ $bouncedThisMonth }}</strong> cheque(s) bounced this month.
    </div>
    <a href="{{ route('finance.wallet.cheques.index', ['status' => 'bounced']) }}" class="btn btn-danger btn-sm">Dekho</a>
</div>
@endif

@if($isMonthEnd)
<div class="alert border-0 shadow-sm mb-3 d-flex justify-content-between align-items-center"
     style="background:#e0f2fe;border-left:4px solid #0284c7 !important">
    <div>
        <i class="bi bi-calendar-check me-2 text-info"></i>
        <strong>Month-end!</strong> Please perform bank reconciliation — verify cash in hand and bank balances.
    </div>
    <a href="{{ route('finance.wallet.contra.index') }}" class="btn btn-sm btn-outline-info">Contra Entries</a>
</div>
@endif

@if($largeTxnToday > 0)
<div class="alert border-0 shadow-sm mb-3 d-flex justify-content-between align-items-center"
     style="background:#fdf4ff;border-left:4px solid #9333ea !important">
    <div>
        <i class="bi bi-lightning-charge me-2" style="color:#9333ea"></i>
        <strong>{{ $largeTxnToday }}</strong> large transaction(s) today (above ₹1,00,000).
    </div>
    <a href="{{ route('finance.wallet.ledger', ['from' => now()->toDateString(), 'to' => now()->toDateString(), 'filtered' => 1]) }}"
       class="btn btn-sm" style="background:#9333ea;color:#fff">Dekho</a>
</div>
@endif

{{-- ── Row 1: Main Summary Cards ──────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid {{ $summary['balance'] >= 0 ? '#198754' : '#dc3545' }} !important">
            <div class="card-body">
                <div class="small text-muted mb-1">Wallet Balance</div>
                <div class="fw-bold fs-3 {{ $summary['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                    ₹{{ number_format($summary['balance'], 2) }}
                </div>
                @if($threshold > 0)
                <div class="small text-muted mt-1">Alert at: ₹{{ number_format($threshold, 2) }}</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #198754 !important">
            <div class="card-body">
                <div class="small text-muted mb-1">Total Income (Session)</div>
                <div class="fw-bold fs-5 text-success">₹{{ number_format($summary['total_income'], 2) }}</div>
                <div class="small text-muted mt-1">Today: +₹{{ number_format($summary['today_income'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #dc3545 !important">
            <div class="card-body">
                <div class="small text-muted mb-1">Total Expense (Session)</div>
                <div class="fw-bold fs-5 text-danger">₹{{ number_format($summary['total_expense'], 2) }}</div>
                <div class="small text-muted mt-1">Today: -₹{{ number_format($summary['today_expense'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #6366f1 !important">
            <div class="card-body">
                <div class="small text-muted mb-1">Net Surplus / Deficit</div>
                @php $net = $summary['total_income'] - $summary['total_expense']; @endphp
                <div class="fw-bold fs-5 {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $net >= 0 ? '+' : '' }}₹{{ number_format($net, 2) }}
                </div>
                <a href="{{ route('finance.wallet.reports.session-comparison') }}" class="small text-primary mt-1 d-block">
                    All sessions <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ── Row 2: Cash & Bank Widgets (Phase 7) ───────────────────────────── --}}
<div class="row g-3 mb-4">
    {{-- Cash in hand --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                <span class="fw-semibold small"><i class="bi bi-cash-stack me-2 text-success"></i>Cash in Hand</span>
                <a href="{{ route('finance.wallet.contra.index') }}" class="btn btn-outline-info btn-sm py-0 px-2" style="font-size:11px">
                    + Deposit to Bank
                </a>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="fw-bold" style="font-size:1.8rem;color:{{ $cashInHand >= 0 ? '#198754' : '#dc3545' }}">
                        ₹{{ number_format($cashInHand, 2) }}
                    </div>
                    <div class="text-muted small">Estimated cash in hand</div>
                </div>
                <div class="row g-2 text-center border-top pt-2">
                    <div class="col-4">
                        <div style="font-size:10px" class="text-muted">Cash Received</div>
                        <div class="text-success fw-semibold small">₹{{ number_format($cashIncome, 0) }}</div>
                    </div>
                    <div class="col-4">
                        <div style="font-size:10px" class="text-muted">Cash Spent</div>
                        <div class="text-danger fw-semibold small">₹{{ number_format($cashExpenses, 0) }}</div>
                    </div>
                    <div class="col-4">
                        <div style="font-size:10px" class="text-muted">Deposited</div>
                        <div class="text-info fw-semibold small">₹{{ number_format($contraTotal, 0) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pending Cheques widget --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                <span class="fw-semibold small">
                    <i class="bi bi-card-checklist me-2 text-warning"></i>Pending Cheques
                    @if($pendingChequesCount > 0)
                        <span class="badge bg-warning text-dark ms-1">{{ $pendingChequesCount }}</span>
                    @endif
                </span>
                <a href="{{ route('finance.wallet.cheques.index') }}" class="btn btn-outline-warning btn-sm py-0 px-2" style="font-size:11px">
                    Manage
                </a>
            </div>
            <div class="card-body">
                @if($pendingChequesCount > 0)
                <div class="text-center mb-3">
                    <div class="fw-bold text-warning" style="font-size:1.8rem">
                        ₹{{ number_format($pendingChequesTotal, 2) }}
                    </div>
                    <div class="text-muted small">Total pending clearance</div>
                </div>
                <div class="row g-2 text-center border-top pt-2">
                    <div class="col-6">
                        <div style="font-size:10px" class="text-muted">Pending Count</div>
                        <div class="text-warning fw-semibold">{{ $pendingChequesCount }}</div>
                    </div>
                    <div class="col-6">
                        <div style="font-size:10px" class="text-muted">Stale (>7 days)</div>
                        <div class="{{ $staleChequesCount > 0 ? 'text-danger' : 'text-muted' }} fw-semibold">{{ $staleChequesCount }}</div>
                    </div>
                </div>
                @else
                <div class="text-center py-3 text-muted small">
                    <i class="bi bi-check-circle text-success fs-3 d-block mb-2"></i>
                    No pending cheques.
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-2">
                <span class="fw-semibold small"><i class="bi bi-lightning me-2 text-primary"></i>Quick Actions</span>
            </div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="{{ route('finance.wallet.ledger', ['from' => now()->toDateString(), 'to' => now()->toDateString(), 'filtered' => 1]) }}"
                   class="btn btn-outline-secondary btn-sm text-start">
                    <i class="bi bi-calendar-day me-2"></i>Today's Transactions
                </a>
                <a href="{{ route('finance.wallet.cheques.index', ['status' => 'pending']) }}"
                   class="btn btn-outline-warning btn-sm text-start">
                    <i class="bi bi-card-checklist me-2"></i>Pending Cheques
                    @if($pendingChequesCount > 0)
                        <span class="badge bg-warning text-dark ms-1">{{ $pendingChequesCount }}</span>
                    @endif
                </a>
                <a href="{{ route('finance.wallet.contra.index') }}"
                   class="btn btn-outline-info btn-sm text-start">
                    <i class="bi bi-arrow-left-right me-2"></i>Cash → Bank Deposit
                </a>
                <a href="{{ route('finance.wallet.reports.income', ['session_id' => $sessionId]) }}"
                   class="btn btn-outline-success btn-sm text-start">
                    <i class="bi bi-bar-chart me-2"></i>Income Report
                </a>
                <a href="{{ route('finance.wallet.reports.expense', ['session_id' => $sessionId]) }}"
                   class="btn btn-outline-danger btn-sm text-start">
                    <i class="bi bi-pie-chart me-2"></i>Expense Report
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ── Bank-wise Balance (Phase 7) ────────────────────────────────────── --}}
@if($bankBalances->isNotEmpty())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-bank me-2 text-primary"></i>Bank-wise Balance (Session)</h6>
        <small class="text-muted">Non-cash income + Cash deposited via Contra</small>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach($bankBalances as $b)
            <div class="col-md-3 col-6">
                <div class="card border shadow-sm h-100">
                    <div class="card-body py-2 px-3">
                        <div class="fw-semibold small text-primary">
                            <i class="bi bi-bank me-1"></i>{{ $b['bank']->account_name ?? $b['bank']->bank_name }}
                        </div>
                        <div class="text-muted" style="font-size:10px">A/c: {{ $b['bank']->account_no }}</div>
                        <div class="fw-bold text-success mt-1">₹{{ number_format($b['balance'], 2) }}</div>
                        <div class="d-flex gap-2 mt-1">
                            <span style="font-size:10px" class="text-muted">
                                Non-cash: ₹{{ number_format($b['income'], 0) }}
                            </span>
                            @if($b['contra_in'] > 0)
                            <span style="font-size:10px" class="text-info">
                                + Contra: ₹{{ number_format($b['contra_in'], 0) }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ── Month-wise Chart ──────────────────────────────────────────────── --}}
@if(!empty($monthlyData['labels']))
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-graph-up me-2 text-primary"></i>Month-wise Income vs Expense</h6>
    </div>
    <div class="card-body">
        <canvas id="monthChart" height="70"></canvas>
    </div>
</div>
@endif

{{-- ── Income by Source ────────────────────────────────────────────── --}}
@if(!empty($summary['by_source']))
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">Income by Source</h6>
        <a href="{{ route('finance.wallet.reports.income', ['session_id' => $sessionId]) }}"
           class="btn btn-sm btn-outline-primary">Full Report</a>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach($summary['by_source'] as $source => $amount)
            <div class="col-md-3 col-6">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-success bg-opacity-10 p-2 flex-shrink-0">
                        @if($source === 'fee_invoice') <i class="bi bi-receipt text-success"></i>
                        @elseif($source === 'library_fine') <i class="bi bi-book text-warning"></i>
                        @elseif($source === 'manual_income') <i class="bi bi-pencil-square text-info"></i>
                        @else <i class="bi bi-cash text-secondary"></i>
                        @endif
                    </div>
                    <div>
                        <div class="small text-muted">{{ $sourceLabels[$source] ?? $source }}</div>
                        <div class="fw-semibold">₹{{ number_format($amount, 2) }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ── Recent Transactions ─────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">Recent Transactions</h6>
        <a href="{{ route('finance.wallet.ledger', ['session_id' => $sessionId, 'from' => now()->toDateString(), 'to' => now()->toDateString(), 'filtered' => 1]) }}"
           class="btn btn-sm btn-outline-secondary">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Source</th>
                    <th class="text-end text-success">Income</th>
                    <th class="text-end text-danger">Expense</th>
                    <th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentTransactions as $tx)
                <tr class="{{ ($tx->credit > 100000 || $tx->debit > 100000) ? 'table-warning' : '' }}">
                    <td class="text-nowrap">{{ $tx->date->format('d M Y') }}</td>
                    <td>{{ Str::limit($tx->des, 55) }}
                        @if($tx->credit > 100000 || $tx->debit > 100000)
                            <span class="badge" style="background:#9333ea;font-size:9px">Large</span>
                        @endif
                    </td>
                    <td>
                        @php $src = $tx->source_type ?? 'fee_invoice'; @endphp
                        <span class="badge bg-secondary bg-opacity-10 text-dark">
                            {{ $sourceLabels[$src] ?? $src }}
                        </span>
                    </td>
                    <td class="text-end text-success">{{ $tx->credit > 0 ? '₹'.number_format($tx->credit, 2) : '-' }}</td>
                    <td class="text-end text-danger">{{ $tx->debit > 0 ? '₹'.number_format($tx->debit, 2) : '-' }}</td>
                    <td class="text-end fw-semibold {{ $tx->cl_bal >= 0 ? 'text-success' : 'text-danger' }}">
                        ₹{{ number_format($tx->cl_bal, 2) }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No transactions found for this session.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
@if(!empty($monthlyData['labels']))
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('monthChart'), {
    type: 'bar',
    data: {
        labels: @json($monthlyData['labels']),
        datasets: [
            {
                label: 'Income',
                data: @json($monthlyData['income']),
                backgroundColor: 'rgba(25,135,84,0.7)',
                borderColor: 'rgba(25,135,84,1)',
                borderWidth: 1,
            },
            {
                label: 'Expense',
                data: @json($monthlyData['expense']),
                backgroundColor: 'rgba(220,53,69,0.7)',
                borderColor: 'rgba(220,53,69,1)',
                borderWidth: 1,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: ctx => '₹' + ctx.parsed.y.toLocaleString('en-IN', { minimumFractionDigits: 2 })
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: val => '₹' + val.toLocaleString('en-IN') }
            }
        }
    }
});
</script>
@endif
@endpush
