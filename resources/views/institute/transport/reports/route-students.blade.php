@extends('institute.layout')
@section('title', 'Route-wise Student List')
@section('breadcrumb', 'Transport / Reports / Route Students')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Route-wise Student List</h4>
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

@forelse($grouped as $routeName => $allocations)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-primary text-white fw-semibold d-flex justify-content-between">
        <span>{{ $routeName }}</span>
        <span>{{ $allocations->count() }} students</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Roll No</th>
                    <th>Mobile</th>
                    <th>Father</th>
                    <th>Stop</th>
                    <th>Vehicle</th>
                    <th>Fee</th>
                    <th>Paid</th>
                    <th>Due</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allocations->sortBy(fn($a) => $a->stop?->sequence ?? 999) as $i => $a)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="fw-semibold">{{ $a->student?->name ?? '—' }}</td>
                    <td>{{ $a->student?->roll_no ?? '—' }}</td>
                    <td>{{ $a->student?->mobile ?? '—' }}</td>
                    <td class="text-muted small">{{ $a->student?->father_name ?? '—' }}</td>
                    <td>{{ $a->stop?->stop_name ?? '—' }}</td>
                    <td>{{ $a->vehicle?->vehicle_no ?? '—' }}</td>
                    <td>₹{{ number_format((float) $a->fee_amount, 2) }}</td>
                    <td class="text-success">₹{{ number_format((float) $a->paid_amount, 2) }}</td>
                    <td class="{{ $a->balance > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                        ₹{{ number_format($a->balance, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@empty
<div class="text-center py-5 text-muted">No active allocations found.</div>
@endforelse
@endsection
