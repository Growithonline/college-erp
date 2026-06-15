@php
    $isStaff = auth()->guard('staff')->check();
    $layout = $isStaff ? 'staff.layout' : 'institute.layout';
    $reportRoute = $isStaff ? 'staff.reports.cancelled-fee' : 'reports.cancelled-fee';
    $receiptRoute = $isStaff ? 'staff.fee.receipt' : 'fee.receipt';
    $historyRoute = $isStaff ? 'staff.fee.student-history' : 'fee.student-history';
@endphp
@extends($layout)
@section('title', 'Cancelled Fee Report')
@section('breadcrumb', 'Reports / Cancelled Fee')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Cancelled Fee Report</h4>
        <small class="text-muted">{{ $sessionObj?->name ?? '' }} - cancelled fee invoices with print access</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-download me-1"></i> Export CSV
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Cancelled Invoices</div>
                <div class="fw-bold fs-5 text-danger">{{ number_format($totalCancelledInvoices) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Cancelled Amount</div>
                <div class="fw-bold fs-5 text-danger">Rs {{ number_format($totalCancelledAmount) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Cancelled Discounts</div>
                <div class="fw-bold fs-5 text-warning">Rs {{ number_format($totalCancelledDiscount) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Students Impacted</div>
                <div class="fw-bold fs-5 text-dark">{{ number_format($totalStudents) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route($reportRoute) }}">
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Session</label>
                    <select name="session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($sessions as $s)
                            <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Course</label>
                    <select name="course_id" class="form-select form-select-sm">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Sem</label>
                    <select name="semester" class="form-select form-select-sm">
                        <option value="">All</option>
                        @for($i = 1; $i <= 8; $i++)
                            <option value="{{ $i }}" {{ request('semester') == $i ? 'selected' : '' }}>S{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Collected By</label>
                    <select name="collected_by" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($collectedByList as $cb)
                            <option value="{{ $cb }}" {{ request('collected_by') == $cb ? 'selected' : '' }}>{{ $cb }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Student, mobile, invoice no...">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Search</button>
                    <a href="{{ route($reportRoute) }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:13px;">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Invoice</th>
                    <th>Student</th>
                    <th>Course / Sem</th>
                    <th>Cancelled On</th>
                    <th>Reason</th>
                    <th>Collected By</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Discount</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                    <tr class="table-danger-subtle">
                        <td class="ps-3">
                            <div class="fw-semibold">{{ $inv->invoice_no }}</div>
                            <div class="text-muted small">{{ $inv->payment_date?->format('d M Y') }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $inv->student->name ?? '—' }}</div>
                            <div class="text-muted small">{{ $inv->student->student_uid ?? '—' }}</div>
                        </td>
                        <td class="small">
                            {{ $inv->student->stream->course->name ?? '—' }}
                            <div class="text-muted">{{ $inv->semester ? 'Sem ' . $inv->semester : '—' }}</div>
                        </td>
                        <td class="small">
                            {{ $inv->cancelled_at ? $inv->cancelled_at->format('d M Y, h:i A') : '—' }}
                        </td>
                        <td class="small text-muted">{{ $inv->cancel_reason ?? '—' }}</td>
                        <td class="small">{{ $inv->collected_by ?? '—' }}</td>
                        <td class="text-end fw-semibold text-danger">Rs {{ number_format($inv->paid_amount) }}</td>
                        <td class="text-end small">Rs {{ number_format($inv->discount ?? 0) }}</td>
                        <td class="text-center">
                            <a href="{{ route($receiptRoute, [$inv->student_id, $inv->id]) }}" class="btn btn-sm btn-outline-primary" target="_blank" title="Print Cancelled Receipt">
                                <i class="bi bi-printer"></i>
                            </a>
                            <a href="{{ route($historyRoute, $inv->student_id) }}" class="btn btn-sm btn-outline-secondary ms-1" title="History">
                                <i class="bi bi-clock-history"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-x-octagon fs-2 d-block mb-2"></i>No cancelled fee invoices found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-top-0">
        @include('institute.components.pagination', ['paginator' => $invoices, 'perPage' => $perPage ?? 20])
    </div>
</div>
@endsection
