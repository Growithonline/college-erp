@extends('institute.layout')
@section('title', 'Channel Partner Collection Report')
@section('breadcrumb', 'Fee Collection > Fee Collection Report > Channel Partner Collection Report')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-people text-warning me-2"></i> Channel Partner Collection Report</h4>
        <small class="text-muted">Fee collected by each channel partner</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
           class="btn btn-outline-success btn-sm">
            <i class="bi bi-filetype-csv me-1"></i> CSV
        </a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}"
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}"
           target="_blank" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </a>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-success bg-opacity-10 p-2">
                        <i class="bi bi-currency-rupee text-success fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Collection</div>
                        <div class="fw-bold fs-5">₹{{ number_format($grandTotal, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-2">
                        <i class="bi bi-receipt text-primary fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Receipts</div>
                        <div class="fw-bold fs-5">{{ number_format($grandCount) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-warning bg-opacity-10 p-2">
                        <i class="bi bi-people text-warning fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Partners</div>
                        <div class="fw-bold fs-5">{{ $partnerData->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    @foreach($sessions as $sess)
                        <option value="{{ $sess->id }}" {{ $sess->id == $sessionId ? 'selected' : '' }}>{{ $sess->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Channel Partner</label>
                <select name="partner_id" class="form-select form-select-sm">
                    <option value="">All Partners</option>
                    @foreach($partners as $p)
                        <option value="{{ $p->id }}" {{ request('partner_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Date From</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Date To</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
            </div>
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('reports.fee-collection.channel-partner') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Channel Partner</th>
                        <th class="text-center">Receipts</th>
                        <th class="text-end">Cash</th>
                        <th class="text-end">UPI</th>
                        <th class="text-end">Online</th>
                        <th class="text-end pe-3">Total (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($partnerData as $i => $row)
                    <tr>
                        <td class="ps-3 text-muted">{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $row['partner']?->name ?? 'Unknown Partner' }}</td>
                        <td class="text-center">
                            @if($row['partner'])
                            <a href="{{ route('reports.fee-collection.channel-partner.detail', $row['partner']->id) }}?date_from={{ $dateFrom }}&date_to={{ $dateTo }}&session_id={{ $sessionId }}"
                               class="badge bg-warning-subtle text-warning text-decoration-none">
                                {{ $row['count'] }}
                            </a>
                            @else
                            <span class="badge bg-warning-subtle text-warning">{{ $row['count'] }}</span>
                            @endif
                        </td>
                        <td class="text-end">₹{{ number_format($row['cash'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($row['upi'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($row['online'], 2) }}</td>
                        <td class="text-end pe-3 fw-bold text-success">₹{{ number_format($row['total'], 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No channel partner collections found for this date range.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($partnerData->isNotEmpty())
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="2" class="ps-3">Grand Total</td>
                        <td class="text-center">{{ $grandCount }}</td>
                        <td class="text-end">₹{{ number_format($partnerData->sum('cash'), 2) }}</td>
                        <td class="text-end">₹{{ number_format($partnerData->sum('upi'), 2) }}</td>
                        <td class="text-end">₹{{ number_format($partnerData->sum('online'), 2) }}</td>
                        <td class="text-end pe-3 text-success">₹{{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

@endsection
