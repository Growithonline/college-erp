@extends('institute.layout')
@section('title', 'Transport Reports')
@section('breadcrumb', 'Transport / Reports')

@section('content')
<h4 class="fw-bold mb-4">Transport Reports</h4>

<div class="row g-4">
    <div class="col-md-3">
        <a href="{{ route('transport.reports.route-students') }}" class="card border-0 shadow-sm text-decoration-none h-100">
            <div class="card-body text-center py-4">
                <div class="fs-2 mb-2">🚌</div>
                <div class="fw-semibold">Route-wise Student List</div>
                <small class="text-muted">Students grouped by route & stop</small>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('transport.reports.due') }}" class="card border-0 shadow-sm text-decoration-none h-100">
            <div class="card-body text-center py-4">
                <div class="fs-2 mb-2">⚠️</div>
                <div class="fw-semibold">Pending Due Report</div>
                <small class="text-muted">Students with transport fee dues</small>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('transport.reports.collection') }}" class="card border-0 shadow-sm text-decoration-none h-100">
            <div class="card-body text-center py-4">
                <div class="fs-2 mb-2">💰</div>
                <div class="fw-semibold">Collection Report</div>
                <small class="text-muted">Date-wise payment collection</small>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('transport.reports.occupancy') }}" class="card border-0 shadow-sm text-decoration-none h-100">
            <div class="card-body text-center py-4">
                <div class="fs-2 mb-2">🚗</div>
                <div class="fw-semibold">Vehicle Occupancy</div>
                <small class="text-muted">Capacity vs. allocated students</small>
            </div>
        </a>
    </div>
</div>
@endsection
