{{-- Shared partial for staff / centre / partner receipt detail pages --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="{{ $backRoute }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <h5 class="mb-0 fw-bold">
                <i class="bi {{ $entityIcon }} text-{{ $entityColor }} me-2"></i>{{ $entityName }}
            </h5>
        </div>
        @if($entitySubtitle)
        <small class="text-muted ms-1">{{ $entitySubtitle }}</small>
        @endif
    </div>
    <div class="d-flex gap-2">
        <a href="{{ $detailRoute }}?{{ http_build_query(array_merge(request()->query(), ['export' => 'csv'])) }}"
           class="btn btn-outline-success btn-sm">
            <i class="bi bi-filetype-csv me-1"></i> CSV
        </a>
        <a href="{{ $detailRoute }}?{{ http_build_query(array_merge(request()->query(), ['export' => 'excel'])) }}"
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </a>
        <a href="{{ $detailRoute }}?{{ http_build_query(array_merge(request()->query(), ['export' => 'pdf'])) }}"
           target="_blank" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </a>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-success bg-opacity-10 p-2">
                        <i class="bi bi-currency-rupee text-success fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Collection</div>
                        <div class="fw-bold fs-5">₹{{ number_format($totalAmount, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-2">
                        <i class="bi bi-receipt text-primary fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Receipts</div>
                        <div class="fw-bold fs-5">{{ $totalReceipts }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">Cash</div>
                <div class="fw-bold">₹{{ number_format($cashTotal, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">UPI</div>
                <div class="fw-bold">₹{{ number_format($upiTotal, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">Online</div>
                <div class="fw-bold">₹{{ number_format($onlineTotal, 2) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    @foreach($sessions as $sess)
                        <option value="{{ $sess->id }}" {{ $sess->id == $sessionId ? 'selected' : '' }}>{{ $sess->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Date From</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Date To</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
            </div>
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ $detailRoute }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

{{-- Receipts Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Invoice No</th>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Roll No</th>
                        <th>Father Name</th>
                        <th>Mother Name</th>
                        <th>Course</th>
                        <th>Fee Items</th>
                        <th class="text-center">Mode</th>
                        <th class="text-end">Amount (₹)</th>
                        <th class="text-center">Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $i => $inv)
                    @php
                        $modeColor = match(strtolower($inv->payment_mode ?? '')) {
                            'cash'   => 'success',
                            'upi'    => 'primary',
                            'online' => 'info',
                            'cheque' => 'warning',
                            default  => 'secondary',
                        };
                    @endphp
                    <tr>
                        <td class="ps-3 text-muted">{{ $i + 1 }}</td>
                        <td class="fw-semibold font-monospace small">{{ $inv->invoice_no }}</td>
                        <td class="text-muted small">{{ $inv->payment_date?->format('d M Y') }}</td>
                        <td>
                            <div class="fw-semibold small">{{ $inv->student->name ?? '—' }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ $inv->student->student_uid ?? '' }}</div>
                        </td>
                        <td class="small text-muted">{{ $inv->student->roll_no ?? '—' }}</td>
                        <td class="small">{{ $inv->student->father_name ?? '—' }}</td>
                        <td class="small">{{ $inv->student->mother_name ?? '—' }}</td>
                        <td class="small text-muted">{{ $inv->student->stream->course->name ?? '—' }}</td>
                        <td class="small text-muted">{{ $inv->items->pluck('fee_name')->implode(', ') }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ $modeColor }}-subtle text-{{ $modeColor }}" style="font-size:10px;">
                                {{ strtoupper($inv->payment_mode ?? '') }}
                            </span>
                        </td>
                        <td class="text-end fw-bold text-success">₹{{ number_format($inv->paid_amount, 2) }}</td>
                        <td class="text-center">
                            <a href="{{ route('fee.receipt', [$inv->student_id, $inv->id]) }}"
                               target="_blank"
                               class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2"
                               title="View Receipt">
                                <i class="bi bi-printer" style="font-size:11px;"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No receipts found for this date range.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($invoices->isNotEmpty())
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="10" class="ps-3 text-end">Total ({{ $totalReceipts }} receipts):</td>
                        <td class="text-end text-success">₹{{ number_format($totalAmount, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
