@extends('institute.layout')
@section('title', 'Transport Dashboard')
@section('breadcrumb', 'Transport / Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Transport Dashboard</h4>
        <small class="text-muted">Quick view of fleet, routes, and student allocations.</small>
    </div>
    <a href="{{ route('transport.allocations.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Allocation
    </a>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-sm-4 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="mb-2"><i class="bi bi-truck fs-4 text-primary"></i></div>
                <div class="fw-bold fs-5">{{ $summary['vehicles'] }}</div>
                <small class="text-muted">Vehicles</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="mb-2"><i class="bi bi-signpost-2 fs-4 text-info"></i></div>
                <div class="fw-bold fs-5">{{ $summary['routes'] }}</div>
                <small class="text-muted">Routes</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="mb-2"><i class="bi bi-person-badge fs-4 text-secondary"></i></div>
                <div class="fw-bold fs-5">{{ $summary['drivers'] }}</div>
                <small class="text-muted">Drivers</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="mb-2"><i class="bi bi-people fs-4 text-success"></i></div>
                <div class="fw-bold fs-5">{{ $summary['active_allocations'] }}</div>
                <small class="text-muted">Active Students</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-md-2">
        <div class="card border-0 shadow-sm border-start border-danger border-3 h-100">
            <div class="card-body text-center py-3">
                <div class="mb-2"><i class="bi bi-hourglass-split fs-4 text-danger"></i></div>
                <div class="fw-bold fs-5 text-danger">₹{{ number_format($summary['total_due'], 2) }}</div>
                <small class="text-muted">Total Pending</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-md-2">
        <div class="card border-0 shadow-sm border-start border-success border-3 h-100">
            <div class="card-body text-center py-3">
                <div class="mb-2"><i class="bi bi-check-circle fs-4 text-success"></i></div>
                <div class="fw-bold fs-5 text-success">₹{{ number_format($summary['total_collected'], 2) }}</div>
                <small class="text-muted">Total Collected</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Pending Balances --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-semibold">
                    <i class="bi bi-exclamation-circle text-danger me-1"></i>
                    Pending Transport Balances
                    @if($dueAllocations->count() > 0)
                        <span class="badge bg-danger ms-1">{{ $dueAllocations->count() }}</span>
                    @endif
                </span>
                <a href="{{ route('transport.allocations.index') }}" class="btn btn-sm btn-outline-secondary px-3">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="px-3 py-2 border-bottom bg-light">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="pendingSearch" class="form-control border-start-0 ps-0"
                               placeholder="Search student or route...">
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                    <table class="table table-hover table-sm mb-0 align-middle" id="pendingTable">
                        <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                            <tr>
                                <th class="ps-3">Student</th>
                                <th>Route / Stop</th>
                                <th class="text-end">Fee</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end pe-3">Due</th>
                            </tr>
                        </thead>
                        <tbody id="pendingBody">
                            @forelse($dueAllocations as $allocation)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold" style="font-size:13px;">{{ $allocation->student?->name ?? '—' }}</div>
                                        <small class="text-muted">{{ $allocation->student?->roll_no ?? '' }}</small>
                                    </td>
                                    <td>
                                        <div style="font-size:13px;">{{ $allocation->route?->name ?? '—' }}</div>
                                        @if($allocation->stop?->stop_name)
                                            <small class="text-muted">{{ $allocation->stop->stop_name }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap" style="font-size:13px;">₹{{ number_format((float) $allocation->fee_amount, 2) }}</td>
                                    <td class="text-end text-nowrap text-success" style="font-size:13px;">₹{{ number_format((float) $allocation->paid_amount, 2) }}</td>
                                    <td class="text-end text-nowrap pe-3">
                                        <span class="badge bg-danger rounded-pill">₹{{ number_format($allocation->balance, 2) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr id="emptyRow">
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-check-circle fs-2 d-block mb-2 text-success opacity-50"></i>
                                        No pending transport dues.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($dueAllocations->count() > 0)
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light">
                    <small class="text-muted" id="paginationInfo"></small>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-secondary px-2 py-1" id="prevPageBtn"
                                onclick="changePendingPage(-1)" disabled>
                            <i class="bi bi-chevron-left" style="font-size:11px;"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary px-2 py-1" id="nextPageBtn"
                                onclick="changePendingPage(1)">
                            <i class="bi bi-chevron-right" style="font-size:11px;"></i>
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-5 d-flex flex-column gap-4">
        {{-- Recent Payments --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-semibold">
                    <i class="bi bi-receipt-cutoff text-success me-1"></i>Recent Payments
                </span>
                @if($recentPayments->count() > 0)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $recentPayments->count() }}</span>
                @endif
            </div>
            <div style="max-height: 280px; overflow-y: auto;">
                <div class="list-group list-group-flush">
                    @forelse($recentPayments as $payment)
                        <div class="list-group-item border-0 px-3 py-2">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="flex-grow-1" style="min-width:0;">
                                    <div class="fw-semibold text-truncate" style="font-size:13px;">{{ $payment->student?->name ?? 'Student' }}</div>
                                    <small class="text-muted">{{ $payment->allocation?->route?->name ?? '—' }}</small>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <div class="fw-bold text-success">₹{{ number_format((float) $payment->amount, 2) }}</div>
                                    <small class="text-muted">{{ $payment->payment_date?->format('d M Y') }}</small>
                                </div>
                            </div>
                            @if($payment->payment_mode)
                                <span class="badge bg-light text-dark border mt-1" style="font-size:10px;">{{ ucfirst($payment->payment_mode) }}</span>
                            @endif
                        </div>
                    @empty
                        <div class="list-group-item text-center text-muted py-4">
                            <i class="bi bi-receipt fs-2 d-block mb-2 opacity-25"></i>
                            No payments recorded yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Expiring Vehicles --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-semibold">
                    <i class="bi bi-exclamation-triangle text-warning me-1"></i>Expiring Vehicles
                    @if($expiringVehicles->count() > 0)
                        <span class="badge bg-warning text-dark ms-1">{{ $expiringVehicles->count() }}</span>
                    @endif
                </span>
                <small class="text-muted">Next 30 days</small>
            </div>
            <div style="max-height: 240px; overflow-y: auto;">
                <div class="list-group list-group-flush">
                    @forelse($expiringVehicles as $vehicle)
                        <div class="list-group-item border-0 px-3 py-2">
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-truck text-warning mt-1 flex-shrink-0"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold" style="font-size:13px;">{{ $vehicle->vehicle_no }}</div>
                                    @if($vehicle->model)
                                        <small class="text-muted">{{ $vehicle->model }}</small>
                                    @endif
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        @if($vehicle->insurance_expiry && $vehicle->insurance_expiry <= now()->addDays(30)->toDateString())
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:10px;">
                                                Insurance: {{ \Carbon\Carbon::parse($vehicle->insurance_expiry)->format('d M Y') }}
                                            </span>
                                        @endif
                                        @if($vehicle->permit_expiry && $vehicle->permit_expiry <= now()->addDays(30)->toDateString())
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:10px;">
                                                Permit: {{ \Carbon\Carbon::parse($vehicle->permit_expiry)->format('d M Y') }}
                                            </span>
                                        @endif
                                        @if($vehicle->fitness_expiry && $vehicle->fitness_expiry <= now()->addDays(30)->toDateString())
                                            <span class="badge bg-warning-subtle text-dark border" style="font-size:10px;">
                                                Fitness: {{ \Carbon\Carbon::parse($vehicle->fitness_expiry)->format('d M Y') }}
                                            </span>
                                        @endif
                                        @if($vehicle->pollution_expiry && $vehicle->pollution_expiry <= now()->addDays(30)->toDateString())
                                            <span class="badge bg-secondary-subtle text-secondary border" style="font-size:10px;">
                                                PUC: {{ \Carbon\Carbon::parse($vehicle->pollution_expiry)->format('d M Y') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="list-group-item text-center text-muted py-4">
                            <i class="bi bi-shield-check fs-2 d-block mb-2 text-success opacity-50"></i>
                            No upcoming expiries in the next 30 days.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const ROWS_PER = 8;
    let page = 1;
    let query = '';

    const tbody = document.getElementById('pendingBody');
    if (!tbody) return;

    const allRows = [...tbody.querySelectorAll('tr:not(#emptyRow)')];
    if (!allRows.length) return;

    // Insert a "no results" row for search
    const noResultsRow = document.createElement('tr');
    noResultsRow.id = 'noResultsRow';
    noResultsRow.innerHTML = '<td colspan="5" class="text-center py-4 text-muted"><i class="bi bi-search me-1"></i>No matching records found.</td>';
    noResultsRow.style.display = 'none';
    tbody.appendChild(noResultsRow);

    function visibleRows() {
        return allRows.filter(r => !query || r.textContent.toLowerCase().includes(query));
    }

    function render() {
        const rows = visibleRows();
        const total = rows.length;
        const totalPages = Math.max(1, Math.ceil(total / ROWS_PER));
        if (page > totalPages) page = totalPages;

        allRows.forEach(r => r.style.display = 'none');
        rows.slice((page - 1) * ROWS_PER, page * ROWS_PER).forEach(r => r.style.display = '');

        noResultsRow.style.display = total === 0 ? '' : 'none';

        const info = document.getElementById('paginationInfo');
        const prev = document.getElementById('prevPageBtn');
        const next = document.getElementById('nextPageBtn');

        if (info) {
            info.textContent = total > 0
                ? `Showing ${Math.min((page - 1) * ROWS_PER + 1, total)}–${Math.min(page * ROWS_PER, total)} of ${total}`
                : 'No results';
        }
        if (prev) prev.disabled = page <= 1;
        if (next) next.disabled = page >= totalPages;
    }

    window.changePendingPage = function (dir) {
        page += dir;
        render();
    };

    const searchEl = document.getElementById('pendingSearch');
    if (searchEl) {
        searchEl.addEventListener('input', function () {
            query = this.value.toLowerCase().trim();
            page = 1;
            render();
        });
    }

    render();
})();
</script>
@endpush
@endsection
