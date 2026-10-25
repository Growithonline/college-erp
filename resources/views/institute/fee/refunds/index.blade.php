@extends('institute.layout')
@section('title', 'Fee Refunds')
@section('breadcrumb', 'Fee Collection / Refunds')

@section('content')
@php
    $badge = ['pending' => 'bg-warning text-dark', 'approved' => 'bg-info text-dark', 'paid' => 'bg-success', 'rejected' => 'bg-secondary', 'cancelled' => 'bg-secondary'];
    $sum = fn($s) => (float) ($totals[$s]->total ?? 0);
    $cnt = fn($s) => (int) ($totals[$s]->n ?? 0);
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-cash-stack me-2 text-primary"></i>Fee Refunds</h4>
        <small class="text-muted">Excess fee owed back to students who have left — raised by the final promotion, approved and paid here</small>
    </div>
    <a href="{{ request()->fullUrlWithQuery(['export' => 1]) }}" class="btn btn-outline-success btn-sm">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($errors->any())
<div class="alert alert-danger py-2">@foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach</div>
@endif

<div class="row g-3 mb-3">
    @foreach(['pending' => 'Awaiting approval', 'approved' => 'Approved, to be paid', 'paid' => 'Paid'] as $st => $label)
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body py-3">
        <div class="text-muted small">{{ $label }} ({{ $cnt($st) }})</div>
        <div class="fs-4 fw-bold">₹{{ number_format($sum($st), 2) }}</div>
    </div></div></div>
    @endforeach
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach(['pending', 'approved', 'paid', 'rejected', 'cancelled'] as $st)
                    <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($sessions as $s)
                    <option value="{{ $s->id }}" {{ (int) request('session_id') === $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label small fw-semibold mb-1">Raised from</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="form-label small fw-semibold mb-1">to</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="form-label small fw-semibold mb-1">Student</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="name or ID"></div>
            <div class="col-md-2 text-end"><button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i> Filter</button></div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr><th>#</th><th>Student</th><th>Session</th><th class="text-end">Amount</th><th>Status</th><th>Raised</th><th>Paid</th><th class="text-end">Action</th></tr>
            </thead>
            <tbody>
            @forelse($refunds as $r)
                <tr>
                    <td>{{ $r->id }}</td>
                    <td>{{ $r->student?->name }}<br><small class="text-muted">{{ $r->student?->student_uid }}</small></td>
                    <td>{{ $r->session?->name }}</td>
                    <td class="text-end fw-semibold">₹{{ number_format((float) $r->amount, 2) }}</td>
                    <td><span class="badge {{ $badge[$r->status] ?? 'bg-secondary' }}">{{ ucfirst($r->status) }}</span>
                        @if($r->remarks)<div class="small text-muted">{{ $r->remarks }}</div>@endif</td>
                    <td class="small">{{ $r->created_at?->format('d M Y') }}</td>
                    <td class="small">
                        @if($r->status === 'paid')
                            {{ $r->paid_on?->format('d M Y') }}<br>{{ $modes[$r->payment_mode] ?? $r->payment_mode }}
                            <div class="text-muted">{{ $r->voucher_no }}</div>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        @if($r->status === 'pending')
                            <form method="POST" action="{{ route('fee.refunds.approve', $r->id) }}" class="d-inline" onsubmit="return confirm('Approve this refund of ₹{{ number_format((float) $r->amount, 2) }}?');">
                                @csrf <button class="btn btn-success btn-sm">Approve</button>
                            </form>
                        @endif
                        @if($r->status === 'approved')
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#payModal{{ $r->id }}">Record payment</button>
                        @endif
                        @if(in_array($r->status, ['pending', 'approved'], true))
                            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $r->id }}">Reject</button>
                        @endif
                        @if($r->status === 'paid')
                            <a href="{{ route('fee.refunds.voucher', $r->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> Voucher</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No refunds found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($refunds->hasPages())<div class="card-footer bg-white">{{ $refunds->links() }}</div>@endif
</div>

{{-- Modals --}}
@foreach($refunds as $r)
    @if($r->status === 'approved')
    <div class="modal fade" id="payModal{{ $r->id }}" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('fee.refunds.pay', $r->id) }}" class="modal-content">
        @csrf
        <div class="modal-header"><h6 class="modal-title">Record refund payment — {{ $r->student?->name }} · ₹{{ number_format((float) $r->amount, 2) }}</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-2"><label class="form-label small fw-semibold">Paid by</label>
                <select name="payment_mode" class="form-select form-select-sm" required>
                    @foreach($modes as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select></div>
            <div class="mb-2"><label class="form-label small fw-semibold">Bank account (not needed for cash)</label>
                <select name="bank_account_id" class="form-select form-select-sm">
                    <option value="">—</option>
                    @foreach($bankAccounts as $b)<option value="{{ $b->id }}">{{ $b->display_label ?: $b->bank_name . ' ' . $b->account_no }}</option>@endforeach
                </select></div>
            <div class="mb-2"><label class="form-label small fw-semibold">Reference / cheque no.</label>
                <input type="text" name="reference_no" class="form-control form-control-sm" maxlength="100"></div>
            <div class="mb-2"><label class="form-label small fw-semibold">Paid on</label>
                <input type="date" name="paid_on" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" class="form-control form-control-sm" required></div>
            <div class="form-text">This debits the student's wallet and the institute wallet and posts the accounting entry. It cannot be undone from here.</div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary btn-sm">Confirm payment</button></div>
    </form></div></div>
    @endif
    @if(in_array($r->status, ['pending', 'approved'], true))
    <div class="modal fade" id="rejectModal{{ $r->id }}" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('fee.refunds.reject', $r->id) }}" class="modal-content">
        @csrf
        <div class="modal-header"><h6 class="modal-title">Reject refund — {{ $r->student?->name }}</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <label class="form-label small fw-semibold">Reason</label>
            <input type="text" name="remarks" class="form-control form-control-sm" maxlength="255" required>
        </div>
        <div class="modal-footer"><button class="btn btn-danger btn-sm">Reject</button></div>
    </form></div></div>
    @endif
@endforeach

@endsection
