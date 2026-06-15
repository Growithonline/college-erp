@php
    $isStaff = auth()->guard('staff')->check();
    $layout = $isStaff ? 'staff.layout' : 'institute.layout';
@endphp
@extends($layout)
@section('title','Semester Wise Report')
@section('breadcrumb','Reports / Semester Wise')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Semester Wise Collection</h4>
        <small class="text-muted">Semester aur course wise fee breakdown</small>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <a href="{{ request()->fullUrlWithQuery(['export'=>'csv']) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-download me-1"></i> Export CSV
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    @foreach($sessions as $s)
                    <option value="{{ $s->id }}" {{ $s->id == $sessionId ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search me-1"></i> Apply
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Summary --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="small text-muted mb-1">Total Collection ({{ $sessionObj?->name }})</div>
        <div class="fw-bold fs-4 text-primary">₹ {{ number_format($totalCollected, 0) }}</div>
    </div>
</div>

<div class="row g-4">
    {{-- Semester wise --}}
    <div class="col-md-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2">
                <h6 class="mb-0 fw-semibold small">
                    <i class="bi bi-layers me-2 text-primary"></i>Semester Wise Summary
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Semester</th>
                            <th class="text-end">Collection</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end" style="color:#f59e0b;">Fine</th>
                            <th class="text-end">Students</th>
                            <th class="text-end pe-3">Invoices</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($semWise as $row)
                        @php $rowFine = (float)($fineGrouped[$row->semester] ?? 0); @endphp
                        <tr>
                            <td class="ps-3">
                                @if($row->semester)
                                    <span class="badge bg-primary bg-opacity-10 text-primary border">
                                        Semester {{ $row->semester }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border">Untagged</span>
                                @endif
                            </td>
                            <td class="text-end small fw-semibold text-primary">₹ {{ number_format($row->collected, 0) }}</td>
                            <td class="text-end small text-warning">{{ $row->discount > 0 ? '₹ '.number_format($row->discount, 0) : '—' }}</td>
                            <td class="text-end small fw-semibold" style="color:#f59e0b;">{{ $rowFine > 0 ? '₹ '.number_format($rowFine, 0) : '—' }}</td>
                            <td class="text-end small">{{ $row->students }}</td>
                            <td class="text-end pe-3 small text-muted">{{ $row->invoices }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4 small">
                            <i class="bi bi-inbox d-block fs-3 mb-2"></i>Koi data nahi
                        </td></tr>
                        @endforelse
                    </tbody>
                    @if($semWise->count() > 0)
                    <tfoot class="table-dark">
                        <tr>
                            <td class="ps-3 fw-bold">Total</td>
                            <td class="text-end fw-bold">₹ {{ number_format($totalCollected, 0) }}</td>
                            <td class="text-end fw-bold">₹ {{ number_format($semWise->sum('discount'), 0) }}</td>
                            <td class="text-end fw-bold" style="color:#fcd34d;">{{ $totalFine > 0 ? '₹ '.number_format($totalFine, 0) : '—' }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Course + Semester wise --}}
    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2">
                <h6 class="mb-0 fw-semibold small">
                    <i class="bi bi-book me-2 text-success"></i>Course + Semester Wise
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Course</th>
                            <th class="text-center">Semester</th>
                            <th class="text-end">Collection</th>
                            <th class="text-end pe-3">Students</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $prevCourse = ''; @endphp
                        @forelse($courseWise as $row)
                        <tr class="{{ $row->course_name !== $prevCourse ? 'table-light' : '' }}">
                            <td class="ps-3 small fw-semibold">
                                {{ $row->course_name !== $prevCourse ? $row->course_name : '' }}
                                @php $prevCourse = $row->course_name; @endphp
                            </td>
                            <td class="text-center">
                                @if($row->semester)
                                    <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:10px;">S{{ $row->semester }}</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-end small text-primary fw-semibold">₹ {{ number_format($row->collected, 0) }}</td>
                            <td class="text-end pe-3 small text-muted">{{ $row->students }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4 small">Koi data nahi</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
