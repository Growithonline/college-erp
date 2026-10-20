@extends('institute.layout')
@section('title', 'Bank-wise Collection')
@section('breadcrumb', 'Reports / Bank-wise Collection')

@section('content')
@php
    $query = request()->query();
    $q = fn(array $extra = []) => array_merge($filters ? [
        'date_from' => $filters['date_from'], 'date_to' => $filters['date_to'],
        'bank_account_id' => $filters['bank'], 'payment_mode' => $filters['mode'], 'source' => $filters['source'],
    ] : [], $extra);
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Bank-wise Income Report</h4>
        <small class="text-muted">All income (fee receipts, library fine, other income) grouped by bank, with payment-mode split and the source of every amount — for the CA's daily report</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('reports.bank-wise.print', $q()) }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i> Print</a>
        <a href="{{ route('reports.bank-wise.pdf', $q()) }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a>
        <a href="{{ route('reports.bank-wise.export', $q(['format' => 'xlsx'])) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
        <a href="{{ route('reports.bank-wise.export', $q(['format' => 'csv'])) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-filetype-csv me-1"></i> CSV</a>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.bank-wise') }}" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">To</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Bank</label>
                <select name="bank_account_id" class="form-select form-select-sm">
                    <option value="">All banks</option>
                    @foreach($bankList as $bank)
                        <option value="{{ $bank->id }}" {{ (string) $filters['bank'] === (string) $bank->id ? 'selected' : '' }}>
                            {{ $bank->display_label ?: $bank->bank_name }}{{ $bank->account_no ? ' — ' . $bank->account_no : '' }}
                        </option>
                    @endforeach
                    <option value="cash" {{ $filters['bank'] === 'cash' ? 'selected' : '' }}>Cash (no bank)</option>
                    <option value="none" {{ $filters['bank'] === 'none' ? 'selected' : '' }}>Bank / mode not recorded</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Income source</label>
                <select name="source" class="form-select form-select-sm">
                    <option value="">All sources</option>
                    @foreach($allSources as $sourceKey => $sourceLabel)
                        <option value="{{ $sourceKey }}" {{ $filters['source'] === $sourceKey ? 'selected' : '' }}>{{ $sourceLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Payment mode</label>
                <select name="payment_mode" class="form-select form-select-sm">
                    <option value="">All modes</option>
                    @foreach($allModes as $modeKey => $modeLabel)
                        <option value="{{ $modeKey }}" {{ $filters['mode'] === $modeKey ? 'selected' : '' }}>{{ $modeLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i> Show</button>
                <a href="{{ route('reports.bank-wise') }}" class="btn btn-outline-secondary btn-sm">Today</a>
            </div>
        </form>
    </div>
</div>

{{-- Total cards --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm"><div class="card-body py-2">
            <div class="small text-muted">Total collected</div>
            <div class="fs-5 fw-bold text-success">₹{{ number_format($grand_total, 2) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm"><div class="card-body py-2">
            <div class="small text-muted">Receipts / entries</div>
            <div class="fs-5 fw-bold">{{ $grand_count }}</div>
        </div></div>
    </div>
    @foreach($modes as $modeKey => $modeLabel)
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm"><div class="card-body py-2">
            <div class="small text-muted">{{ $modeLabel }}</div>
            <div class="fw-bold">₹{{ number_format($mode_totals[$modeKey] ?? 0, 2) }}</div>
        </div></div>
    </div>
    @endforeach
</div>

<style>
    .bw-wrap .bw-title { margin: 6px 0 6px; font-weight: 700; }
    .bw-wrap .bw-table { width: 100%; border-collapse: collapse; background: #fff; font-size: 13px; }
    .bw-wrap .bw-table th { background: #e2e8f0; border: 1px solid #9ca3af; padding: 5px 8px; text-align: left; }
    .bw-wrap .bw-table td { border: 1px solid #9ca3af; padding: 4px 8px; vertical-align: top; }
    .bw-wrap .bw-table .r { text-align: right; white-space: nowrap; }
    .bw-wrap .bw-table .nw { white-space: nowrap; }
    .bw-wrap .bw-table tfoot td { font-weight: 700; background: #f1f5f9; }
    .bw-wrap .bw-sub { color: #64748b; font-size: 11px; }
    .bw-wrap .bw-group { margin-top: 18px; }
    .bw-wrap .bw-group-head { background: #1e293b; color: #fff; padding: 6px 10px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 6px; }
    .bw-wrap .bw-modes { font-weight: 400; font-size: 12px; margin-left: 10px; }
    .bw-wrap .bw-empty { color: #64748b; padding: 24px 0; text-align: center; }
</style>
<div class="card border-0 shadow-sm">
    <div class="card-body bw-wrap table-responsive">
        @include('institute.reports._bank-wise-body')
    </div>
</div>
@endsection
