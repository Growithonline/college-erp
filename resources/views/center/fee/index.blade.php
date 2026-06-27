@extends('center.layout')
@section('title','My Fee Collections')
@section('breadcrumb','Fee / My Collections')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">My Fee Collections</h4>
        <small class="text-muted">{{ $activeSession->name ?? '' }}</small>
    </div>
    <a href="{{ route('center.fee.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Collect Fee
    </a>
</div>

{{-- Stats --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">Total Collected</div>
                <div class="fw-bold fs-5 text-success">Rs {{ number_format($totalPaid, 0) }}</div>
                <div class="text-muted" style="font-size:11px;">{{ $totalInvoices }} invoices</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">Cash</div>
                <div class="fw-bold text-success">Rs {{ number_format($cashAmt, 0) }}</div>
                <div class="text-muted" style="font-size:11px;">{{ $cashCount }} receipts</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">UPI / Online</div>
                <div class="fw-bold text-primary">Rs {{ number_format($upiAmt + $onlineAmt, 0) }}</div>
                <div class="text-muted" style="font-size:11px;">{{ $upiCount + $onlineCount }} receipts</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">Cheque</div>
                <div class="fw-bold text-warning">Rs {{ number_format($chequeAmt, 0) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET">
            <div class="row g-2 mb-2">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Session</label>
                    <select name="session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Sessions</option>
                        @foreach($allowedSessions as $sess)
                        <option value="{{ $sess->id }}" {{ (int)$sessionId === $sess->id ? 'selected' : '' }}>
                            {{ $sess->name }}{{ $sess->is_active ? ' (Active)' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm"
                           value="{{ $dateFrom }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm"
                           value="{{ $dateTo }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Payment Mode</label>
                    <select name="payment_mode" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Modes</option>
                        @foreach(['cash'=>'Cash','upi'=>'UPI','online'=>'Online','cheque'=>'Cheque','dd'=>'DD','neft'=>'NEFT','rtgs'=>'RTGS'] as $v=>$l)
                        <option value="{{ $v }}" {{ request('payment_mode') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Per Page</label>
                    <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach([10,20,50,100] as $n)
                        <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control form-control-sm"
                           placeholder="Student name, mobile, invoice no...">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Search</button>
                </div>
                <div class="col-auto">
                    <a href="{{ route('center.fee.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
                <div class="col-auto ms-auto d-flex gap-1 align-items-end">
                    @php
                        $todayParams  = array_merge(request()->except(['date_from','date_to']), ['date_from'=>now()->toDateString(),'date_to'=>now()->toDateString()]);
                        $monthParams  = array_merge(request()->except(['date_from','date_to']), ['date_from'=>now()->startOfMonth()->toDateString(),'date_to'=>now()->toDateString()]);
                        $isToday = $dateFrom == now()->toDateString() && $dateTo == now()->toDateString();
                        $exportBase = array_merge(request()->only(['session_id','date_from','date_to','payment_mode']), []);
                    @endphp
                    <a href="{{ route('center.fee.index', $todayParams) }}"
                       class="btn btn-sm {{ $isToday ? 'btn-primary' : 'btn-outline-secondary' }}">Today</a>
                    <a href="{{ route('center.fee.index', $monthParams) }}"
                       class="btn btn-sm btn-outline-secondary">This Month</a>

                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-download me-1"></i> Export
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('center.fee.export', array_merge($exportBase, ['format'=>'csv'])) }}">
                                    <i class="bi bi-filetype-csv me-2 text-success"></i> CSV
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('center.fee.export', array_merge($exportBase, ['format'=>'excel'])) }}">
                                    <i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i> Excel (.xlsx)
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('center.fee.export', array_merge($exportBase, ['format'=>'pdf'])) }}" target="_blank">
                                    <i class="bi bi-filetype-pdf me-2 text-danger"></i> PDF
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<style>
    #fee-collection-table th, #fee-collection-table td { padding: 4px 5px !important; }
</style>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="fee-collection-table" class="table table-hover table-sm mb-0 align-middle" style="font-size:13px; min-width:2000px;">
                <thead class="table-light">
                    <tr>
                        <th style="white-space:nowrap;">Invoice No</th>
                        <th style="white-space:nowrap;">Student Name</th>
                        <th style="white-space:nowrap;">Roll No</th>
                        <th style="white-space:nowrap;">UIN No</th>
                        <th style="white-space:nowrap;">Father Name</th>
                        <th style="white-space:nowrap;">Mother Name</th>
                        <th style="white-space:nowrap;">Enroll No</th>
                        <th style="white-space:nowrap;">Mobile</th>
                        <th style="white-space:nowrap;">Course / Stream</th>
                        <th style="white-space:nowrap;">Session</th>
                        <th style="white-space:nowrap;">Mode</th>
                        <th class="text-end" style="white-space:nowrap;">Total Fee</th>
                        <th class="text-end" style="white-space:nowrap;">Fine</th>
                        <th class="text-end" style="white-space:nowrap;">Discount</th>
                        <th class="text-end" style="white-space:nowrap;">Due</th>
                        <th style="white-space:nowrap;">Collected By</th>
                        <th class="text-center" style="white-space:nowrap;">Receipt</th>
                    </tr>
                </thead>
                @php
                    $pageRunningDue = [];
                    $byStudent = $invoices->getCollection()->groupBy('student_id');
                    foreach ($byStudent as $_sid => $_stuInvs) {
                        $_st = $_stuInvs->first()->student;
                        $_currentDue = (float) ($_st?->wallets->sum(fn($w) => (float)$w->main_b < 0 ? abs((float)$w->main_b) : 0) ?? 0);
                        $_allPaid = (float) ($totalPaidByStudent[$_sid] ?? 0);
                        $_rd = $_allPaid + $_currentDue;
                        foreach ($_stuInvs->where('is_cancelled', false)->sortBy(fn($i) => $i->payment_date->timestamp * 1000000 + $i->id) as $_inv) {
                            $_rd -= (float) $_inv->paid_amount;
                            $pageRunningDue[$_inv->id] = max(0, round($_rd, 2));
                        }
                    }
                @endphp
                <tbody>
                    @forelse($invoices as $inv)
                    @php
                        $modeColors = ['cash'=>'success','upi'=>'primary','online'=>'info','cheque'=>'warning','dd'=>'secondary','neft'=>'dark','rtgs'=>'dark'];
                        $color = $modeColors[$inv->payment_mode] ?? 'secondary';
                        $st = $inv->student;
                        $fineTotal = $inv->items->sum('fine');
                        if ($inv->is_cancelled) {
                            $due = 0;
                        } elseif ($inv->remaining_due !== null) {
                            $due = (float) $inv->remaining_due;
                        } else {
                            $due = $pageRunningDue[$inv->id] ?? 0;
                        }
                    @endphp
                    <tr class="{{ $inv->is_cancelled ? 'table-danger opacity-75' : '' }}">
                        <td>
                            <span class="badge bg-light text-dark border fw-semibold" style="font-size:11px;">{{ $inv->invoice_no }}</span>
                            <div class="text-muted" style="font-size:10px;">
                                {{ $inv->payment_date?->format('d M Y') }}
                                @if($inv->payment_datetime && $inv->payment_mode !== 'cash')
                                    · {{ $inv->payment_datetime->setTimezone('Asia/Kolkata')->format('d M · h:i A') }}
                                @endif
                                @if($inv->is_cancelled)
                                    <span class="badge bg-danger ms-1" style="font-size:9px;">Cancelled</span>
                                @endif
                            </div>
                        </td>
                        <td class="fw-semibold">{{ $st?->name ?? '—' }}</td>
                        <td class="text-muted small">{{ $st?->roll_no ?: '—' }}</td>
                        <td class="text-muted small">{{ $st?->student_uid ?? '—' }}</td>
                        <td class="small">{{ $st?->father_name ?: '—' }}</td>
                        <td class="small">{{ $st?->mother_name ?: '—' }}</td>
                        <td class="text-muted small">{{ $st?->enrollment_no ?: '—' }}</td>
                        <td class="small text-muted">{{ $st?->mobile ?? '' }}</td>
                        <td>
                            <div class="small fw-semibold">{{ $st?->stream?->course?->name ?? '—' }}</div>
                            <div class="text-muted" style="font-size:11px;">
                                {{ $st?->stream?->name ?? '' }}
                                @if($inv->semester) · S{{ $inv->semester }} @endif
                            </div>
                        </td>
                        <td class="small text-muted">{{ $inv->session?->name ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }} border border-{{ $color }}">
                                {{ strtoupper($inv->payment_mode) }}
                            </span>
                        </td>
                        <td class="text-end fw-bold text-success">Rs {{ number_format($inv->paid_amount) }}</td>
                        <td class="text-end fw-bold {{ $fineTotal > 0 ? 'text-danger' : 'text-muted' }}">
                            {{ $fineTotal > 0 ? 'Rs '.number_format($fineTotal) : '—' }}
                        </td>
                        <td class="text-end fw-bold">
                            @if($inv->discount > 0)
                                <span style="color:#e67e22;">- Rs {{ number_format($inv->discount) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold {{ $due > 0 ? 'text-danger' : 'text-muted' }}">
                            {{ $due > 0 ? 'Rs '.number_format($due) : '—' }}
                        </td>
                        <td class="small">
                            @if($inv->collected_by_center_id && (int)$inv->collected_by_center_id === (int)$center->id)
                                <span class="badge bg-primary bg-opacity-10 text-primary border" style="font-size:10px;">You</span>
                            @else
                                <span class="text-muted">{{ $inv->collected_by ?: 'Institute' }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('center.fee.receipt', [$inv->student_id, $inv->id]) }}"
                               class="btn btn-sm btn-outline-primary" target="_blank" title="Print Receipt">
                                <i class="bi bi-printer"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="17" class="text-center py-5 text-muted">
                            <i class="bi bi-receipt fs-2 d-block mb-2"></i>
                            Koi fee collection nahi mili
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($invoices->hasPages())
    <div class="card-footer bg-white border-top-0">
        {{ $invoices->withQueryString()->links() }}
    </div>
    @endif
</div>

@endsection
