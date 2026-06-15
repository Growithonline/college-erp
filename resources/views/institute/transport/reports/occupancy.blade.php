@extends('institute.layout')
@section('title', 'Vehicle Occupancy')
@section('breadcrumb', 'Transport / Reports / Occupancy')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Vehicle Occupancy</h4>
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
        <div class="col-md-2">
            <button class="btn btn-primary btn-sm w-100">Filter</button>
        </div>
    </div>
</form>

<div class="row g-3">
    @forelse($vehicles as $v)
    @php
        $pct   = $v->occupancy_pct;
        $color = $pct === null ? 'secondary' : ($pct >= 90 ? 'danger' : ($pct >= 70 ? 'warning' : 'success'));
    @endphp
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="fw-bold fs-6">{{ $v->vehicle_no }}</div>
                        <small class="text-muted">{{ $v->vehicleType?->name ?? ($v->model ?? 'Vehicle') }}</small>
                    </div>
                    <span class="badge bg-{{ $color }}">
                        {{ $pct !== null ? $pct . '%' : 'No capacity set' }}
                    </span>
                </div>
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>Students: <strong>{{ $v->active_students }}</strong></span>
                    <span>Capacity: <strong>{{ $v->capacity ?: '—' }}</strong></span>
                </div>
                @if($pct !== null)
                <div class="progress" style="height:6px;">
                    <div class="progress-bar bg-{{ $color }}" style="width:{{ $pct }}%"></div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 text-center py-5 text-muted">No active vehicles found.</div>
    @endforelse
</div>
@endsection
