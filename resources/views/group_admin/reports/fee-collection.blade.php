@extends('group_admin.layout')
@section('title', 'Fee Collection Report')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('group_admin.institutes.index') }}" class="text-decoration-none">Institutes</a></li>
    <li class="breadcrumb-item active">Fee Collection Report</li>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Fee Collection Report</h4>
        <small class="text-muted">{{ $sessionId ? ($sessionObj?->name ?? 'Session') : 'All Sessions' }} — Date-wise, mode-wise collection (read-only)</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-filetype-csv me-1"></i> CSV
        </a>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
            <div class="small text-muted">Total Collected</div>
            <div class="fw-bold fs-6 text-success">₹ {{ number_format($totalCollected) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
            <div class="small text-muted">Total Invoices</div>
            <div class="fw-bold fs-6">{{ number_format($totalInvoices) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
            <div class="small text-muted">Students</div>
            <div class="fw-bold fs-6">{{ number_format($totalStudents) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
            <div class="small text-muted">Total Discount</div>
            <div class="fw-bold fs-6 text-warning">₹ {{ number_format($totalDiscount) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
            <div class="small text-muted">Total Fine</div>
            <div class="fw-bold fs-6" style="color:#f59e0b;">₹ {{ number_format($totalFine) }}</div>
        </div></div>
    </div>
</div>

{{-- Mode-wise + Fee-type breakdown --}}
<div class="row g-3 mb-4">
    @if($modeWise->isNotEmpty())
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header py-2 bg-white border-bottom"><span class="fw-semibold small"><i class="bi bi-pie-chart me-1 text-primary"></i> Payment Mode Breakdown</span></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th class="ps-3">Mode</th><th class="text-center">Count</th><th class="text-end pe-3">Amount</th></tr></thead>
                    <tbody>
                        @foreach($modeWise as $m)
                        <tr>
                            <td class="ps-3"><span class="badge bg-secondary bg-opacity-75">{{ strtoupper($m->payment_mode) }}</span></td>
                            <td class="text-center small">{{ $m->cnt }}</td>
                            <td class="text-end pe-3 fw-semibold small">₹ {{ number_format($m->total) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-semibold">
                        <tr><td class="ps-3">Total</td><td class="text-center">{{ $modeWise->sum('cnt') }}</td><td class="text-end pe-3 text-success">₹ {{ number_format($modeWise->sum('total')) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if($feeTypeWise->isNotEmpty())
    <div class="col-md-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header py-2 bg-white border-bottom"><span class="fw-semibold small"><i class="bi bi-list-ul me-1 text-success"></i> Fee Type Breakdown</span></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:12px;">
                    <thead class="table-light">
                        <tr><th class="ps-3">Fee Type</th><th class="text-center">Cnt</th><th class="text-end">Charged</th><th class="text-end text-danger">Fine</th><th class="text-end text-success">Paid</th><th class="text-end" style="color:#7c3aed;">Disc</th><th class="text-end text-danger pe-3">Due</th></tr>
                    </thead>
                    <tbody>
                        @foreach($feeTypeWise as $f)
                        @php $fDue = max(0, ($f->charged_total ?? 0) + ($f->fine_total ?? 0) - ($f->paid_total ?? 0) - ($f->disc_total ?? 0)); @endphp
                        <tr>
                            <td class="ps-3 small">{{ $f->fee_name }}</td>
                            <td class="text-center small text-muted">{{ $f->cnt }}</td>
                            <td class="text-end small">₹ {{ number_format($f->charged_total ?? 0) }}</td>
                            <td class="text-end small text-danger">{{ ($f->fine_total ?? 0) > 0 ? '₹ '.number_format($f->fine_total) : '—' }}</td>
                            <td class="text-end small text-success fw-semibold">₹ {{ number_format($f->paid_total ?? 0) }}</td>
                            <td class="text-end small" style="color:#7c3aed;">{{ ($f->disc_total ?? 0) > 0 ? '₹ '.number_format($f->disc_total) : '—' }}</td>
                            <td class="text-end small pe-3 {{ $fDue > 0 ? 'text-danger fw-semibold' : 'text-success' }}">{{ $fDue > 0 ? '₹ '.number_format($fDue) : '✓' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- Collector-wise Breakdown --}}
@if(isset($collectorWise) && $collectorWise->isNotEmpty())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-2"><h6 class="mb-0 fw-semibold small"><i class="bi bi-person-badge me-2 text-primary"></i>Collector-wise Breakdown</h6></div>
    <div class="table-responsive">
        <table class="table table-sm mb-0" style="font-size:13px;">
            <thead class="table-light">
                <tr><th class="ps-3">Collected By</th><th class="text-end">Cash (₹)</th><th class="text-end">UPI (₹)</th><th class="text-end">Online (₹)</th><th class="text-end">Cheque (₹)</th><th class="text-end">DD (₹)</th><th class="text-end">Invoices</th><th class="text-end pe-3 fw-bold">Total (₹)</th></tr>
            </thead>
            <tbody>
                @foreach($collectorWise as $cw)
                <tr>
                    <td class="ps-3 fw-semibold">{{ $cw->collected_by ?? '— Direct —' }}</td>
                    <td class="text-end">{{ $cw->cash_amt > 0 ? number_format($cw->cash_amt,0) : '—' }}</td>
                    <td class="text-end">{{ $cw->upi_amt > 0 ? number_format($cw->upi_amt,0) : '—' }}</td>
                    <td class="text-end">{{ $cw->online_amt > 0 ? number_format($cw->online_amt,0) : '—' }}</td>
                    <td class="text-end">{{ $cw->cheque_amt > 0 ? number_format($cw->cheque_amt,0) : '—' }}</td>
                    <td class="text-end">{{ $cw->dd_amt > 0 ? number_format($cw->dd_amt,0) : '—' }}</td>
                    <td class="text-end text-muted small">{{ $cw->invoice_cnt }}</td>
                    <td class="text-end pe-3 fw-bold text-success">₹ {{ number_format($cw->total_amt,0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Bank-wise Breakdown --}}
@if(isset($bankWise) && $bankWise->isNotEmpty())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-2"><span class="fw-semibold small"><i class="bi bi-bank me-1 text-info"></i> Bank-wise Collection</span></div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th class="ps-3">Bank / Account</th><th class="text-center">Invoices</th><th class="text-end pe-3">Amount Collected</th></tr></thead>
            <tbody>
                @foreach($bankWise as $bw)
                <tr>
                    <td class="ps-3 fw-semibold"><i class="bi bi-bank2 me-1 text-info"></i>{{ $bw->bank_label }}</td>
                    <td class="text-center small text-muted">{{ $bw->cnt }}</td>
                    <td class="text-end pe-3 fw-semibold text-success small">₹ {{ number_format($bw->total) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light fw-semibold">
                <tr><td class="ps-3">Total</td><td class="text-center">{{ $bankWise->sum('cnt') }}</td><td class="text-end pe-3 text-success">₹ {{ number_format($bankWise->sum('total')) }}</td></tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ url()->current() }}">
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Session</label>
                    <select name="session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="" {{ !$sessionId ? 'selected':'' }}>All Sessions</option>
                        @foreach($sessions as $s)
                            <option value="{{ $s->id }}" {{ $sessionId==$s->id ? 'selected':'' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom ?? now()->toDateString() }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo ?? now()->toDateString() }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Mode</label>
                    <select name="payment_mode" class="form-select form-select-sm">
                        <option value="">All Modes</option>
                        @foreach(['cash'=>'Cash','upi'=>'UPI','online'=>'Online','cheque'=>'Cheque','dd'=>'DD'] as $v=>$l)
                            <option value="{{ $v }}" {{ request('payment_mode')==$v ? 'selected':'' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Course</label>
                    <select name="course_id" class="form-select form-select-sm">
                        <option value="">All Courses</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" {{ request('course_id')==$c->id ? 'selected':'' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Invoice, Name, UID...">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-4"><i class="bi bi-funnel me-1"></i> Filter</button>
                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i> Reset</a>
                </div>
            </div>
            <input type="hidden" name="per_page" value="{{ $perPage }}">
        </form>
    </div>
</div>

{{-- Invoices Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($invoices->isEmpty())
            <div class="text-center py-5 text-muted"><i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>No invoices found. Try adjusting the filters.</div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:12px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th><th>Invoice No</th><th>Date</th><th>Student</th><th>Roll No</th><th>Course</th>
                        <th class="text-center">Mode</th><th>Bank / Ref</th><th>Collected By</th>
                        <th class="text-end">Collection</th><th class="text-end text-danger">Fine</th>
                        <th class="text-end" style="color:#7c3aed;">Discount</th><th class="text-end text-success pe-2">Total Amt</th><th class="text-end text-danger pe-3">Due</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $i => $inv)
                    @php
                        $invRef = $inv->transaction_ref ?: ($inv->remarks ?: null);
                        $bankLabel = $inv->bankAccount?->display_label ?: ($inv->bank_name ?: null);
                        $invFine = $inv->items->sum('fine');
                        $invTotalCharged = $inv->items->sum(fn($item) => $item->total_fee ?? $item->amount);
                        $invDue = max(0, $invTotalCharged - $inv->paid_amount - ($inv->discount ?? 0));
                    @endphp
                    <tr>
                        <td class="ps-3 text-muted">{{ $invoices->firstItem() + $i }}</td>
                        <td class="fw-semibold">{{ $inv->invoice_no }}</td>
                        <td class="text-muted" style="white-space:nowrap;">{{ $inv->payment_date?->format('d M Y') }}</td>
                        <td>
                            <div class="fw-semibold">{{ $inv->student->name ?? '—' }}</div>
                            <div class="text-muted" style="font-size:10px;">{{ $inv->student->student_uid ?? '' }}</div>
                        </td>
                        <td style="font-size:11px;">{{ $inv->student->roll_no ?: '—' }}</td>
                        <td class="text-muted" style="font-size:11px;">{{ $inv->student?->stream?->course?->name ?? '—' }}</td>
                        <td class="text-center"><span class="badge bg-secondary bg-opacity-75" style="font-size:10px;">{{ strtoupper($inv->payment_mode) }}</span></td>
                        <td style="max-width:130px;">
                            @if($bankLabel)<div style="font-size:10px;" class="fw-semibold text-info">{{ $bankLabel }}</div>@endif
                            @if($invRef)<div class="text-muted" style="font-size:10px;">{{ strlen($invRef) > 18 ? substr($invRef, 0, 18).'…' : $invRef }}</div>@endif
                            @if(!$bankLabel && !$invRef)<span class="text-muted">—</span>@endif
                        </td>
                        <td style="font-size:11px;">{{ $inv->collected_by ?: '—' }}</td>
                        <td class="text-end fw-bold text-success">₹ {{ number_format($inv->paid_amount) }}</td>
                        <td class="text-end">{{ $invFine > 0 ? '₹'.number_format($invFine) : '—' }}</td>
                        <td class="text-end" style="color:#7c3aed;">{{ ($inv->discount ?? 0) > 0 ? '-₹'.number_format($inv->discount) : '—' }}</td>
                        <td class="text-end fw-bold pe-2">₹ {{ number_format($inv->paid_amount + ($inv->discount ?? 0)) }}</td>
                        <td class="text-end pe-3">{{ $invDue > 0 ? '₹'.number_format($invDue) : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-semibold">
                    @php
                        $pageFineTotal = $invoices->sum(fn($inv) => $inv->items->sum('fine'));
                        $pageDueTotal  = $invoices->sum(fn($inv) => max(0, $inv->items->sum(fn($item) => $item->total_fee ?? $item->amount) - $inv->paid_amount - ($inv->discount ?? 0)));
                    @endphp
                    <tr>
                        <td colspan="9" class="ps-3 text-muted small">This page total ({{ $invoices->count() }} invoices)</td>
                        <td class="text-end text-success">₹ {{ number_format($invoices->sum('paid_amount')) }}</td>
                        <td class="text-end text-danger">{{ $pageFineTotal > 0 ? '₹ '.number_format($pageFineTotal) : '—' }}</td>
                        <td class="text-end" style="color:#7c3aed;">{{ $invoices->sum('discount') > 0 ? '-₹ '.number_format($invoices->sum('discount')) : '—' }}</td>
                        <td class="text-end text-success pe-2 fw-bold">₹ {{ number_format($invoices->sum('paid_amount') + $invoices->sum('discount')) }}</td>
                        <td class="text-end pe-3 {{ $pageDueTotal > 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ $pageDueTotal > 0 ? '₹ '.number_format($pageDueTotal) : '—' }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="px-3 pb-3">
            @include('institute.components.pagination', ['paginator' => $invoices, 'perPage' => $perPage])
        </div>
        @endif
    </div>
</div>

@endsection
