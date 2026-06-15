@extends('institute.layout')
@section('title', 'Transport Due Report')
@section('breadcrumb', 'Transport / Reports / Pending Dues')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Transport Pending Dues</h4>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">Print</button>
</div>

<form class="card border-0 shadow-sm p-3 mb-4" method="GET">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small">Session</label>
            <select name="session_id" class="form-select form-select-sm">
                @foreach($sessions as $s)
                    <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small">Route</label>
            <select name="route_id" class="form-select form-select-sm">
                <option value="">All Routes</option>
                @foreach($routes as $r)
                    <option value="{{ $r->id }}" {{ $routeId == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary btn-sm w-100">Filter</button>
        </div>
    </div>
</form>

<div class="alert alert-danger d-inline-block mb-3 fw-semibold">
    Total Pending: ₹{{ number_format($totalDue, 2) }} ({{ $allocations->count() }} students)
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Mobile</th>
                    <th>Route</th>
                    <th>Stop</th>
                    <th class="text-end">Fee</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end text-danger">Due</th>
                </tr>
            </thead>
            <tbody>
                @forelse($allocations as $i => $a)
                @php $due = max(0, (float) $a->fee_amount - (float) $a->paid_amount); @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        <div class="fw-semibold">{{ $a->student?->name ?? '—' }}</div>
                        <small class="text-muted">{{ $a->student?->roll_no ?? '' }}</small>
                    </td>
                    <td>{{ $a->student?->mobile ?? '—' }}</td>
                    <td>{{ $a->route?->name ?? '—' }}</td>
                    <td>{{ $a->stop?->stop_name ?? '—' }}</td>
                    <td class="text-end">₹{{ number_format((float) $a->fee_amount, 2) }}</td>
                    <td class="text-end text-success">₹{{ number_format((float) $a->paid_amount, 2) }}</td>
                    <td class="text-end text-danger fw-bold">₹{{ number_format($due, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-4 text-muted">No pending dues.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
