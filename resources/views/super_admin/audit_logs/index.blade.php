@extends('super_admin.layout')
@section('title', 'Audit Log')
@section('breadcrumb')
    <li class="breadcrumb-item active">Audit Log</li>
@endsection
@section('content')

<style>
.pill { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600; }
.pill-module  { background:#ede9fe; color:#6d28d9; border:1px solid #ddd6fe; }
.pill-actor   { background:#dbeafe; color:#1d4ed8; border:1px solid #bfdbfe; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-shield-check me-2 text-primary"></i>Audit Log</h4>
        <small class="text-muted">Sensitive actions across the whole platform.</small>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('super_admin.audit-logs.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold small mb-1">Module</label>
                <select name="module" class="form-select form-select-sm">
                    <option value="">All Modules</option>
                    @foreach($modules as $module)
                        <option value="{{ $module }}" {{ request('module') === $module ? 'selected' : '' }}>{{ ucfirst($module) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small mb-1">Actor Type</label>
                <select name="actor_type" class="form-select form-select-sm">
                    <option value="">All Actors</option>
                    @foreach($actorTypes as $actorType)
                        <option value="{{ $actorType }}" {{ request('actor_type') === $actorType ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $actorType)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small mb-1">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" max="{{ date('Y-m-d') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small mb-1">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" max="{{ date('Y-m-d') }}">
            </div>
            <div class="col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                @if(request()->hasAny(['module', 'actor_type', 'date_from', 'date_to']))
                <a href="{{ route('super_admin.audit-logs.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    @if($logs->isEmpty())
    <div class="card-body text-center py-5">
        <i class="bi bi-shield-check" style="font-size:3rem;color:#94a3b8;"></i>
        <h6 class="mt-3 text-muted">No audit log entries found for the selected filters.</h6>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="font-size:12px;">When</th>
                    <th style="font-size:12px;">Module / Action</th>
                    <th style="font-size:12px;">Actor</th>
                    <th style="font-size:12px;">Institute</th>
                    <th style="font-size:12px;">Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td class="text-muted small">
                        {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y, h:i A') }}
                        <div style="font-size:11px;">{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</div>
                    </td>
                    <td>
                        <span class="pill pill-module">{{ ucfirst($log->module) }}</span>
                        <div class="text-muted mt-1" style="font-size:11px;">{{ $log->action }}</div>
                    </td>
                    <td>
                        <span class="pill pill-actor">{{ ucfirst(str_replace('_', ' ', $log->actor_type ?? 'system')) }}</span>
                        <div class="fw-semibold small mt-1">{{ $log->actor_name }}</div>
                    </td>
                    <td class="small">{{ $log->institute_name ?? '—' }}</td>
                    <td class="text-muted small" style="max-width:280px;">{{ $log->description ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="p-3 border-top d-flex align-items-center justify-content-between">
        <small class="text-muted">Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} entries</small>
        {{ $logs->links() }}
    </div>
    @endif
    @endif
</div>
@endsection
