@extends('super_admin.layout')
@section('title', 'Login-ID Backfill')
@section('breadcrumb')
    <li class="breadcrumb-item active">Login-ID Backfill</li>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-bold"><i class="bi bi-upc-scan text-primary me-2"></i> Login-ID Backfill</h5>
</div>

<div class="alert alert-info small">
    Staff / Channel Partner / Center / Library Staff are moving from email-based login to a system-generated
    Login ID. This assigns a Login ID to every existing record that doesn't have one yet. Safe to run multiple
    times — it only ever touches records that are still missing a Login ID.
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pb-0 pt-3">
        <h6 class="fw-bold mb-0">Records still missing a Login ID</h6>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="border rounded-3 p-3 text-center">
                    <div class="text-muted small">Staff</div>
                    <div class="fw-bold fs-4 {{ $counts['staff'] > 0 ? 'text-warning' : 'text-success' }}">{{ $counts['staff'] }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded-3 p-3 text-center">
                    <div class="text-muted small">Channel Partners</div>
                    <div class="fw-bold fs-4 {{ $counts['partner'] > 0 ? 'text-warning' : 'text-success' }}">{{ $counts['partner'] }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded-3 p-3 text-center">
                    <div class="text-muted small">Centers</div>
                    <div class="fw-bold fs-4 {{ $counts['center'] > 0 ? 'text-warning' : 'text-success' }}">{{ $counts['center'] }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded-3 p-3 text-center">
                    <div class="text-muted small">Library Staff</div>
                    <div class="fw-bold fs-4 {{ $counts['library_staff'] > 0 ? 'text-warning' : 'text-success' }}">{{ $counts['library_staff'] }}</div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('super_admin.uid-backfill.run') }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-play-fill me-1"></i> Run Backfill
                </button>
            </form>
            <form method="POST" action="{{ route('super_admin.uid-backfill.reset-legacy') }}"
                  onsubmit="return confirm('Reset all 6-digit Staff/Partner/Center Login IDs so they regenerate in the current 4-digit format? You must click Run Backfill again right after.');">
                @csrf
                <button type="submit" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Legacy 6-digit IDs
                </button>
            </form>
        </div>
    </div>
</div>

@if($lastRun)
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pb-0 pt-3">
        <h6 class="fw-bold mb-0">Last Run Result (this session)</h6>
    </div>
    <div class="card-body">
        <ul class="mb-2 small">
            <li>Staff updated: {{ $lastRun['staff'] }}</li>
            <li>Channel Partners updated: {{ $lastRun['partner'] }}</li>
            <li>Centers updated: {{ $lastRun['center'] }}</li>
            <li>Library Staff updated: {{ $lastRun['library_staff'] }}</li>
        </ul>
        @if(!empty($lastRun['errors']))
            <div class="alert alert-danger small mb-0">
                <strong>{{ count($lastRun['errors']) }} error(s):</strong>
                <ul class="mb-0">
                    @foreach($lastRun['errors'] as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
@endif

@endsection
