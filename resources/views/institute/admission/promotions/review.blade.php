@extends(auth()->guard('staff')->check() ? 'staff.layout' : 'institute.layout')
@section('title', 'Promotion Review')
@section('breadcrumb', 'Admissions / Promotion / Review')

@section('content')
@php
    $_rp = auth()->guard('staff')->check() ? 'staff.admissions.promote.' : 'admissions.promote.';
    $totalExcess = collect($rows)->sum(fn($r) => $r['plan']['adjustable_total']);
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-clipboard-check me-2 text-primary"></i>Promotion Review</h4>
        <small class="text-muted">Excess fee paid must be set against pending fees before these students are promoted</small>
    </div>
    @if($backUrl)
    <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to promotion</a>
    @endif
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-warning py-2"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}</div>
@endif

<div class="alert alert-info py-2 small">
    <i class="bi bi-info-circle me-1"></i>
    These students paid more than one of their fees now costs, and another fee is still pending. Their promotion has been held back
    so the excess is not carried to the next session as advance while the full pending amount is carried as due. Adjusting moves
    the excess onto the pending fees (in the order One-time pay fills them) — no money moves in the wallet.
    Anything left over after that becomes an <strong>advance</strong> in the next session (or a <strong>refund</strong> for a leaving student).
</div>

@if(empty($rows))
<div class="alert alert-success"><i class="bi bi-check-circle me-1"></i> Nothing left to adjust — every selected student is clear. Go back and promote.</div>
@else
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">{{ count($rows) }} student(s) · ₹{{ number_format($totalExcess, 2) }} to adjust</span>
        <form method="POST" action="{{ route($_rp . 'review.adjust') }}" onsubmit="return confirm('Adjust excess for all {{ count($rows) }} student(s) listed?');">
            @csrf
            <button class="btn btn-primary btn-sm"><i class="bi bi-arrow-left-right me-1"></i> Adjust all ({{ count($rows) }})</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr><th>Student</th><th>Excess paid on</th><th>Pending fees</th><th>Will be adjusted</th></tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                <tr>
                    <td>{{ $r['student']->name }}<br><small class="text-muted">{{ $r['student']->student_uid }}</small></td>
                    <td class="small">
                        @foreach($r['plan']['excess'] as $label => $amt)
                            <div>{{ $label }}: <strong>₹{{ number_format($amt, 2) }}</strong></div>
                        @endforeach
                    </td>
                    <td class="small">
                        @foreach($r['plan']['pending'] as $label => $amt)
                            <div>{{ $label }}: ₹{{ number_format($amt, 2) }}</div>
                        @endforeach
                    </td>
                    <td class="small">
                        @foreach($r['plan']['moves'] as $mv)
                            <div><span class="badge bg-light text-dark border">{{ $mv['from'] }} → {{ $mv['to'] }}</span> ₹{{ number_format($mv['amount'], 2) }}</div>
                        @endforeach
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
