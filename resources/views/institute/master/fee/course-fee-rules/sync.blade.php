@extends('institute.layout')
@section('title', 'Sync Students to Fee Rules')
@section('breadcrumb', 'Master / Fee Structure / Course Fees / Sync Students')

@section('content')

@php
    $backParams = array_filter(['session_id' => $session?->id, 'course_id' => $course?->id], fn($v) => $v !== null);
    $formParams = array_filter([
        'session_id'      => $session?->id,
        'course_id'       => $course?->id,
        'from'            => $opts['from'],
        'include_new'     => $opts['include_new'] ? 1 : null,
        'include_removed' => $opts['include_removed'] ? 1 : null,
    ], fn($v) => $v !== null);
    $okRows = collect($report['rows'])->where('result', 'OK');
    $otherRows = collect($report['rows'])->where('result', '!=', 'OK');
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-arrow-repeat me-2 text-primary"></i>Sync Students to Fee Rules</h4>
        <small class="text-muted">
            Live session {{ $session?->name }}{{ $course ? ' · ' . $course->name : ' · all courses' }}
        </small>
    </div>
    <a href="{{ route('master.fee-structure.course-fees', $backParams) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Fee Rules
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

<div class="alert alert-info py-2 small">
    <i class="bi bi-info-circle me-1"></i>
    Editing a fee rule does <strong>not</strong> change what students were already charged. This page compares each active
    student's ledger with today's rules and shows the difference. Applying it posts a <em>Fee adjustment</em> entry to each
    student's wallet and ledger (history is never edited) and an adjusting journal entry. Payments already made are kept —
    only the due changes. Closed semesters, subject / practical / transport fees and bulk-imported students are not touched.
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body py-3">
        <div class="text-muted small">Students checked</div><div class="fs-4 fw-bold">{{ $report['checked'] }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body py-3">
        <div class="text-muted small">Adjustments</div><div class="fs-4 fw-bold">{{ $report['changed'] }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body py-3">
        <div class="text-muted small">Fee increase</div><div class="fs-4 fw-bold text-danger">+₹{{ number_format($report['increase'], 2) }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body py-3">
        <div class="text-muted small">Fee decrease</div><div class="fs-4 fw-bold text-success">−₹{{ number_format($report['decrease'], 2) }}</div>
    </div></div></div>
</div>

{{-- Options --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="session_id" value="{{ $session?->id }}">
            @if($course)<input type="hidden" name="course_id" value="{{ $course->id }}">@endif
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Only students charged exactly ₹ (old amount)</label>
                <input type="number" step="0.01" min="0" name="from" value="{{ $opts['from'] }}" class="form-control form-control-sm" placeholder="optional, e.g. 5000">
            </div>
            <div class="col-md-3">
                <div class="form-check"><input class="form-check-input" type="checkbox" name="include_new" value="1" id="incNew" {{ $opts['include_new'] ? 'checked' : '' }}>
                <label class="form-check-label small" for="incNew">Also charge fees never charged (rule added later)</label></div>
            </div>
            <div class="col-md-3">
                <div class="form-check"><input class="form-check-input" type="checkbox" name="include_removed" value="1" id="incRem" {{ $opts['include_removed'] ? 'checked' : '' }}>
                <label class="form-check-label small" for="incRem">Also reverse fees whose rule was removed</label></div>
            </div>
            <div class="col-md-3 text-end"><button class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i> Refresh preview</button></div>
        </form>
    </div>
</div>

@if($okRows->isEmpty() && $otherRows->isEmpty())
<div class="alert alert-success"><i class="bi bi-check-circle me-1"></i> Nothing to change — every student's ledger already matches the current fee rules.</div>
@else
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Preview</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr><th>Student</th><th>Fee</th><th class="text-end">Change</th><th>Result</th><th>Detail</th></tr>
            </thead>
            <tbody>
                @foreach($report['rows'] as $r)
                <tr class="{{ $r['result'] === 'OK' ? '' : 'table-warning' }}">
                    <td>{{ $r['name'] }} <small class="text-muted">{{ $r['uid'] }}</small></td>
                    <td>{{ $r['fee'] }}</td>
                    <td class="text-end {{ $r['diff'] > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $r['diff'] != 0.0 ? ($r['diff'] > 0 ? '+' : '−') . '₹' . number_format(abs($r['diff']), 2) : '' }}
                    </td>
                    <td><span class="badge {{ $r['result'] === 'OK' ? 'bg-primary' : 'bg-warning text-dark' }}">{{ $r['result'] }}</span></td>
                    <td class="small {{ str_contains($r['note'], 'excess') ? 'text-danger' : 'text-muted' }}">{{ $r['note'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if($okRows->isNotEmpty())
<div class="card border-0 shadow-sm border-start border-4 border-warning">
    <div class="card-body">
        <form method="POST" action="{{ route('master.fee-structure.course-fees.sync.apply') }}">
            @csrf
            <input type="hidden" name="session_id" value="{{ $session?->id }}">
            @if($course)<input type="hidden" name="course_id" value="{{ $course->id }}">@endif
            @if($opts['from'] !== null)<input type="hidden" name="from" value="{{ $opts['from'] }}">@endif
            @if($opts['include_new'])<input type="hidden" name="include_new" value="1">@endif
            @if($opts['include_removed'])<input type="hidden" name="include_removed" value="1">@endif
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="confirm" value="1" id="confirmApply" required>
                <label class="form-check-label" for="confirmApply">
                    I have checked the preview. Apply {{ $okRows->count() }} adjustment(s) now — this changes student wallets and posts accounting entries and cannot be undone from this page.
                </label>
            </div>
            <button class="btn btn-warning"><i class="bi bi-check2-circle me-1"></i> Apply to {{ $okRows->count() }} adjustment(s)</button>
        </form>
    </div>
</div>
@endif
@endif

@endsection
