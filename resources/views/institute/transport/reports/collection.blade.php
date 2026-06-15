@extends('institute.layout')
@section('title', 'Transport Collection Report')
@section('breadcrumb', 'Transport / Reports / Collection')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Transport Collection Report</h4>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">Print</button>
</div>

<form class="card border-0 shadow-sm p-3 mb-4" method="GET">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small">Session</label>
            <select name="session_id" class="form-select form-select-sm">
                @foreach($sessions as $s)
                    <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Route</label>
            <select name="route_id" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach($routes as $r)
                    <option value="{{ $r->id }}" {{ $routeId == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">From</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small">To</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
        </div>
        <div class="col-md-1">
            <button class="btn btn-primary btn-sm w-100">Go</button>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-auto">
        <div class="card border-0 shadow-sm px-4 py-3">
            <div class="fw-bold fs-5 text-success">₹{{ number_format($totalCollected, 2) }}</div>
            <small class="text-muted">Total Collected ({{ $payments->count() }} payments)</small>
        </div>
    </div>
    @foreach($byMode as $mode => $data)
    <div class="col-auto">
        <div class="card border-0 shadow-sm px-4 py-3">
            <div class="fw-bold">₹{{ number_format($data['amount'], 2) }}</div>
            <small class="text-muted">{{ ucfirst($mode) }} ({{ $data['count'] }})</small>
        </div>
    </div>
    @endforeach
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Student</th>
                    <th>Route</th>
                    <th>Stop</th>
                    <th>Mode</th>
                    <th>Source</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $i => $p)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $p->payment_date?->format('d M Y') }}</td>
                    <td>
                        <div class="fw-semibold">{{ $p->student?->name ?? '—' }}</div>
                        <small class="text-muted">{{ $p->student?->roll_no ?? '' }}</small>
                    </td>
                    <td>{{ $p->allocation?->route?->name ?? '—' }}</td>
                    <td>{{ $p->allocation?->stop?->stop_name ?? '—' }}</td>
                    <td>{{ ucfirst($p->payment_mode) }}</td>
                    <td>{{ $p->fee_invoice_id ? 'Fee Invoice' : 'Direct' }}</td>
                    <td class="text-end fw-semibold text-success">₹{{ number_format((float) $p->amount, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-4 text-muted">No payments in this range.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
