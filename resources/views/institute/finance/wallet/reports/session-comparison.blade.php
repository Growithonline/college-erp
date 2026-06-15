@extends('institute.layout')
@section('title', 'Session-wise Comparison')
@section('breadcrumb', 'Finance / Wallet / Session Comparison')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-table me-2 text-primary"></i>Session-wise Comparison</h4>
        <small class="text-muted">Har academic session ka Income vs Expense vs Balance</small>
    </div>
    <a href="{{ route('finance.wallet.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Dashboard
    </a>
</div>

{{-- Summary bar chart --}}
@if($comparison->isNotEmpty())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-semibold">Income vs Expense — All Sessions</h6>
    </div>
    <div class="card-body">
        <canvas id="comparisonChart" height="80"></canvas>
    </div>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Session</th>
                    <th>Status</th>
                    <th class="text-end text-success">Total Income</th>
                    <th class="text-end text-danger">Total Expense</th>
                    <th class="text-end">Surplus / Deficit</th>
                    <th class="text-end">Current Balance</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($comparison as $row)
                @php
                    $s = $row['session'];
                    $net = $row['surplus'];
                @endphp
                <tr>
                    <td class="fw-semibold">{{ $s->name }}</td>
                    <td>
                        <span class="badge {{ $s->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $s->is_active ? 'Active' : 'Closed' }}
                        </span>
                    </td>
                    <td class="text-end text-success fw-semibold">
                        Rs {{ number_format($row['total_income'], 2) }}
                    </td>
                    <td class="text-end text-danger fw-semibold">
                        Rs {{ number_format($row['total_expense'], 2) }}
                    </td>
                    <td class="text-end fw-bold {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $net >= 0 ? '+' : '' }}Rs {{ number_format($net, 2) }}
                    </td>
                    <td class="text-end fw-bold {{ $row['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                        Rs {{ number_format($row['balance'], 2) }}
                    </td>
                    <td>
                        <a href="{{ route('finance.wallet.dashboard', ['session_id' => $s->id]) }}"
                           class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">Koi session data nahi hai.</td>
                </tr>
                @endforelse
            </tbody>
            @if($comparison->isNotEmpty())
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="2">Grand Total</td>
                    <td class="text-end text-success">Rs {{ number_format($comparison->sum('total_income'), 2) }}</td>
                    <td class="text-end text-danger">Rs {{ number_format($comparison->sum('total_expense'), 2) }}</td>
                    @php $netTotal = $comparison->sum('total_income') - $comparison->sum('total_expense'); @endphp
                    <td class="text-end {{ $netTotal >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $netTotal >= 0 ? '+' : '' }}Rs {{ number_format($netTotal, 2) }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection

@push('scripts')
@if($comparison->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('comparisonChart'), {
    type: 'bar',
    data: {
        labels: @json($comparison->pluck('session')->pluck('name')),
        datasets: [
            {
                label: 'Income',
                data: @json($comparison->pluck('total_income')->map(fn($v) => round((float)$v, 2))),
                backgroundColor: 'rgba(25, 135, 84, 0.7)',
                borderColor: 'rgba(25, 135, 84, 1)',
                borderWidth: 1,
            },
            {
                label: 'Expense',
                data: @json($comparison->pluck('total_expense')->map(fn($v) => round((float)$v, 2))),
                backgroundColor: 'rgba(220, 53, 69, 0.7)',
                borderColor: 'rgba(220, 53, 69, 1)',
                borderWidth: 1,
            },
            {
                label: 'Balance',
                data: @json($comparison->pluck('balance')->map(fn($v) => round((float)$v, 2))),
                type: 'line',
                borderColor: 'rgba(13, 110, 253, 1)',
                backgroundColor: 'transparent',
                tension: 0.3,
                pointRadius: 5,
                yAxisID: 'y',
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': Rs ' + ctx.parsed.y.toLocaleString('en-IN', {minimumFractionDigits:2}) } }
        },
        scales: {
            y: {
                beginAtZero: false,
                ticks: { callback: v => 'Rs ' + v.toLocaleString('en-IN') }
            }
        }
    }
});
</script>
@endif
@endpush
