@extends('institute.layout')
@section('title', 'Transport Billing')
@section('breadcrumb', 'Transport / Billing')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Transport Billing</h4>
        <small class="text-muted">Generate recurring transport charges (Monthly / Quarterly / Semester).</small>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
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
                <label class="form-label">Billing Period</label>
                <input type="month" name="charge_month" class="form-control" value="{{ $chargeMonth }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Frequency</label>
                <select name="freq" class="form-select">
                    <option value="all" {{ ($freqFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Recurring</option>
                    <option value="monthly" {{ ($freqFilter ?? '') === 'monthly' ? 'selected' : '' }}>Monthly Only</option>
                    <option value="quarterly" {{ ($freqFilter ?? '') === 'quarterly' ? 'selected' : '' }}>Quarterly Only</option>
                    <option value="semester" {{ ($freqFilter ?? '') === 'semester' ? 'selected' : '' }}>Semester Only</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-primary w-100">View</button>
            </div>
        </form>

        @if(in_array($freqFilter ?? 'all', ['all', 'quarterly']))
        <div class="alert alert-info mb-3 py-2 small">
            <i class="bi bi-info-circle me-1"></i>
            Current quarter: <strong>{{ $quarterLabel ?? '' }}</strong>.
            Quarterly routes show <em>Pending</em> only once per quarter — remaining months in same quarter show as <em>Billed</em>.
        </div>
        @endif

        @if($pendingCount > 0)
        <form method="POST" action="{{ route('transport.billing.generate') }}"
            onsubmit="return confirm('Generate charges for {{ (int) $pendingCount }} pending students for ' + {{ json_encode($chargeMonth) }} + '?')">
            @csrf
            <input type="hidden" name="charge_month" value="{{ $chargeMonth }}">
            <input type="hidden" name="academic_session_id" value="{{ $sessionId }}">
            <button class="btn btn-primary">
                <i class="bi bi-lightning me-1"></i>Generate {{ $pendingCount }} Pending Charges
            </button>
        </form>
        @else
            <div class="alert alert-success mb-0">
                <i class="bi bi-check-circle me-1"></i>All recurring allocations already billed for this period.
            </div>
        @endif
    </div>
</div>

{{-- Allocation List --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>
            Recurring Allocations — {{ $chargeMonth }}
            <span class="badge bg-secondary ms-2">{{ $allocations->count() }} total</span>
            @if($pendingCount > 0)
                <span class="badge bg-danger ms-1">{{ $pendingCount }} pending</span>
            @endif
        </span>
        <div class="d-flex gap-2 flex-wrap">
            @foreach(['all' => 'All', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'semester' => 'Semester'] as $f => $lbl)
                <a href="{{ request()->fullUrlWithQuery(['freq' => $f]) }}"
                   class="btn btn-sm {{ ($freqFilter ?? 'all') === $f ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ $lbl }}
                    <span class="badge ms-1 {{ ($freqFilter ?? 'all') === $f ? 'bg-light text-dark' : 'bg-secondary' }}">
                        {{ $allocations->filter(fn($a) => $f === 'all' || $a->route?->billing_frequency === $f)->count() }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student</th>
                    <th>Route</th>
                    <th>Frequency</th>
                    <th class="text-end">Fee Amount</th>
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
                        @php
                            $freq  = $a->route?->billing_frequency ?? '';
                            $bgCls = match($freq) {
                                'monthly'   => 'bg-primary',
                                'quarterly' => 'bg-warning text-dark',
                                'semester'  => 'bg-info text-dark',
                                default     => 'bg-secondary',
                            };
                            $freqLabel = match($freq) {
                                'monthly'   => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'semester'  => 'Per Semester',
                                default     => ucfirst($freq),
                            };
                        @endphp
                        <span class="badge {{ $bgCls }}">{{ $freqLabel }}</span>
                    </td>
                    <td class="text-end">₹{{ number_format((float) $a->fee_amount, 2) }}</td>
                    <td class="text-center">
                        @if($a->already_billed)
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Billed</span>
                        @else
                            <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Pending</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        No recurring transport allocations found.<br>
                        <small>Assign <strong>Monthly / Quarterly / Semester</strong> billing on routes to use this feature.</small>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── One Time Allocations ── --}}
@if($oneTimeAllocations->count())
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-1-circle text-warning me-2"></i>One Time Allocations
            <span class="badge bg-secondary ms-2">{{ $oneTimeAllocations->count() }} total</span>
            @php $otPending = $oneTimeAllocations->filter(fn($a) => $a->status !== 'paid')->count(); @endphp
            @if($otPending)
                <span class="badge bg-danger ms-1">{{ $otPending }} pending</span>
            @else
                <span class="badge bg-success ms-1">All collected</span>
            @endif
        </span>
        <small class="text-muted" style="font-size:12px;">One-time fees are collected individually from student wallet</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:14px;">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Route</th>
                    <th class="text-end">Fee</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Balance</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($oneTimeAllocations as $i => $a)
                @php
                    $fee     = (float) $a->fee_amount;
                    $paid    = (float) $a->paid_amount;
                    $balance = round($fee - $paid, 2);
                @endphp
                <tr>
                    <td class="text-muted">{{ $i + 1 }}</td>
                    <td>
                        <div class="fw-semibold">{{ $a->student?->name ?? '—' }}</div>
                        <small class="text-muted">{{ $a->student?->roll_no ?? '' }}</small>
                    </td>
                    <td>{{ $a->route?->name ?? '—' }}</td>
                    <td class="text-end">₹{{ number_format($fee, 2) }}</td>
                    <td class="text-end {{ $paid > 0 ? 'text-success' : 'text-muted' }}">
                        ₹{{ number_format($paid, 2) }}
                    </td>
                    <td class="text-end {{ $balance > 0 ? 'text-danger fw-semibold' : 'text-success' }}">
                        ₹{{ number_format($balance, 2) }}
                    </td>
                    <td class="text-center">
                        @if($a->status === 'paid')
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Paid</span>
                        @elseif($a->status === 'partial')
                            <span class="badge bg-warning text-dark"><i class="bi bi-half me-1"></i>Partial</span>
                        @else
                            <span class="badge bg-danger"><i class="bi bi-clock me-1"></i>Pending</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($balance > 0)
                            <form method="POST"
                                action="{{ route('transport.billing.collect-one-time', $a) }}"
                                onsubmit="return confirm('Collect ₹{{ number_format($balance, 2) }} from {{ addslashes($a->student?->name ?? '') }} wallet?')">
                                @csrf
                                <button class="btn btn-sm btn-primary px-3">
                                    <i class="bi bi-wallet2 me-1"></i>Collect
                                </button>
                            </form>
                        @else
                            <span class="text-success"><i class="bi bi-check-lg"></i> Done</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
