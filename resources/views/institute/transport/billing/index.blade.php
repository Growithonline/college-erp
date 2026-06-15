@extends('institute.layout')
@section('title', 'Monthly Billing')
@section('breadcrumb', 'Transport / Monthly Billing')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Transport Monthly Billing</h4>
        <small class="text-muted">Generate recurring transport charges for monthly/quarterly/semester routes.</small>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

{{-- Filter + Generate --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label">Session</label>
                <select name="session_id" class="form-select">
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Month</label>
                <input type="month" name="charge_month" class="form-control" value="{{ $chargeMonth }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-primary w-100">View</button>
            </div>
        </form>

        @if($pendingCount > 0)
        <form method="POST" action="{{ route('transport.billing.generate') }}"
            onsubmit="return confirm('Generate charges for {{ $pendingCount }} students for {{ $chargeMonth }}?')">
            @csrf
            <input type="hidden" name="charge_month" value="{{ $chargeMonth }}">
            <input type="hidden" name="academic_session_id" value="{{ $sessionId }}">
            <button class="btn btn-primary">
                Generate Charges for {{ $pendingCount }} Pending Students
            </button>
        </form>
        @else
            <div class="alert alert-success mb-0">All recurring allocations already billed for {{ $chargeMonth }}.</div>
        @endif
    </div>
</div>

{{-- Allocation List --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">
        Recurring Allocations — {{ $chargeMonth }}
        <span class="badge bg-secondary ms-2">{{ $allocations->count() }} total</span>
        @if($pendingCount > 0)
            <span class="badge bg-danger ms-1">{{ $pendingCount }} pending</span>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student</th>
                    <th>Route</th>
                    <th>Billing</th>
                    <th class="text-end">Amount</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($allocations as $a)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $a->student?->name ?? '—' }}</div>
                        <small class="text-muted">{{ $a->student?->roll_no ?? '' }}</small>
                    </td>
                    <td>{{ $a->route?->name ?? '—' }}</td>
                    <td>
                        <span class="badge bg-info text-dark">{{ ucfirst($a->route?->billing_frequency ?? '') }}</span>
                    </td>
                    <td class="text-end">₹{{ number_format((float) $a->fee_amount, 2) }}</td>
                    <td class="text-center">
                        @if($a->already_billed)
                            <span class="badge bg-success">Billed</span>
                        @else
                            <span class="badge bg-warning text-dark">Pending</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        No recurring transport allocations found.<br>
                        <small>Set <strong>Billing</strong> to Monthly/Quarterly/Semester on routes to use this feature.</small>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
