@extends('group_admin.layout')
@section('title', 'Fee Due List')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('group_admin.institutes.index') }}" class="text-decoration-none">Institutes</a></li>
    <li class="breadcrumb-item active">Fee Due List</li>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Fee Due List</h4>
        <small class="text-muted">{{ $sessionObj?->name ?? '' }} — Students with pending fee dues (read-only)</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" target="_blank" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-filetype-csv me-1"></i> CSV
        </a>
    </div>
</div>

{{-- Summary Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
            <div class="small text-muted">Total Students</div>
            <div class="fw-bold fs-6">{{ number_format($summary['total_students']) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
            <div class="small text-muted">Zero Payment</div>
            <div class="fw-bold fs-6 text-danger">{{ number_format($summary['unpaid_count']) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
            <div class="small text-muted">Total Collection</div>
            <div class="fw-bold fs-6 text-success">₹ {{ number_format($summary['total_collected']) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
            <div class="small text-muted">Total Discount</div>
            <div class="fw-bold fs-6" style="color:#7c3aed;">₹ {{ number_format($totalDiscount) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
            <div class="small text-muted">Total Fine</div>
            <div class="fw-bold fs-6 text-warning">₹ {{ number_format($totalFine) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
            <div class="small text-muted">Due (This Page)</div>
            <div class="fw-bold fs-6 text-danger">₹ {{ number_format($totalDue) }}</div>
        </div></div>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ url()->current() }}">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Session</label>
                    <select name="session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($sessions as $s)
                            <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Course Type</label>
                    <select name="course_type_id" class="form-select form-select-sm">
                        <option value="">— All Types —</option>
                        @foreach($courseTypes as $ct)
                            <option value="{{ $ct->id }}" {{ request('course_type_id') == $ct->id ? 'selected' : '' }}>{{ $ct->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Course</label>
                    <select name="course_id" class="form-select form-select-sm">
                        <option value="">— All Courses —</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Stream</label>
                    <select name="stream_id" class="form-select form-select-sm">
                        <option value="">— All Streams —</option>
                        @foreach($streams as $st)
                            <option value="{{ $st->id }}" {{ request('stream_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Sem</label>
                    <select name="semester" class="form-select form-select-sm">
                        <option value="0" {{ ($filterSemester ?? 0) == 0 ? 'selected' : '' }}>All</option>
                        @for($i=1;$i<=8;$i++)
                            <option value="{{ $i }}" {{ ($filterSemester ?? 0) == $i ? 'selected' : '' }}>S{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Name, mobile, UID, enrollment...">
                </div>

                <div class="col-md-3 d-flex align-items-end gap-2">
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="show_all" value="1" id="showAllToggle" {{ $showAll ? 'checked' : '' }} onchange="this.form.submit()">
                        <label class="form-check-label small" for="showAllToggle">Show all (including zero due)</label>
                    </div>
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

{{-- Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($students->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-check-circle fs-1 text-success opacity-50"></i>
                <div class="mt-2">No dues found. Try changing the filters.</div>
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Student</th>
                        <th>Roll No</th>
                        <th>Father Name</th>
                        <th>Course / Stream</th>
                        <th>Year</th>
                        <th class="text-end">Total Payable</th>
                        <th class="text-end text-success">Paid</th>
                        <th class="text-end" style="color:#7c3aed;">Discount</th>
                        <th class="text-end text-warning">Fine</th>
                        <th class="text-end" style="color:#0891b2;">Lib Fine</th>
                        <th class="text-end text-danger pe-3">Due</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $i => $student)
                        @php
                            $d = $dueData[$student->id] ?? ['payable' => 0, 'paid' => 0, 'due' => 0, 'discount' => 0, 'fine' => 0, 'library_fine' => 0];
                            if (!$showAll && $d['due'] <= 0) continue;
                        @endphp
                        <tr>
                            <td class="ps-3 text-muted">{{ $students->firstItem() + $i }}</td>
                            <td>
                                <div class="fw-semibold">{{ $student->name }}</div>
                                <div class="text-muted" style="font-size:0.78rem;">{{ $student->student_uid }}@if($student->mobile) · {{ $student->mobile }} @endif</div>
                            </td>
                            <td class="small text-muted">{{ $student->roll_no ?: '—' }}</td>
                            <td class="small">{{ $student->father_name ?: '—' }}</td>
                            <td>
                                <div>{{ $student->stream->course->name ?? '—' }}</div>
                                <div class="text-muted" style="font-size:0.78rem;">{{ $student->stream->name ?? '' }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary fw-normal">Year {{ $student->coursePart?->year_number ?? '—' }}</span>
                            </td>
                            <td class="text-end fw-semibold">{{ $d['payable'] > 0 ? '₹ '.number_format($d['payable']) : '—' }}</td>
                            <td class="text-end text-success fw-semibold">₹ {{ number_format($d['paid']) }}</td>
                            <td class="text-end fw-semibold" style="color:#7c3aed;">{{ $d['discount'] > 0 ? '₹ '.number_format($d['discount']) : '—' }}</td>
                            <td class="text-end text-warning fw-semibold">{{ $d['fine'] > 0 ? '₹ '.number_format($d['fine']) : '—' }}</td>
                            <td class="text-end fw-semibold" style="color:#0891b2;">{{ ($d['library_fine'] ?? 0) > 0 ? '₹ '.number_format($d['library_fine']) : '—' }}</td>
                            <td class="text-end pe-3">
                                @if($d['due'] > 0)
                                    <span class="fw-bold text-danger">₹ {{ number_format($d['due']) }}</span>
                                @elseif($d['payable'] == 0)
                                    <span class="text-muted small">Fee not set</span>
                                @else
                                    <span class="text-success small"><i class="bi bi-check-circle-fill me-1"></i>Paid</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-semibold">
                    <tr>
                        <td colspan="6" class="ps-3 text-muted small">Page total ({{ $students->count() }} students)</td>
                        <td class="text-end fw-semibold">₹ {{ number_format($totalPayable) }}</td>
                        <td class="text-end text-success">₹ {{ number_format($totalPaid) }}</td>
                        <td class="text-end" style="color:#7c3aed;">₹ {{ number_format($totalDiscount) }}</td>
                        <td class="text-end text-warning">₹ {{ number_format($totalFine) }}</td>
                        <td class="text-end" style="color:#0891b2;">₹ {{ number_format($totalLibraryFine ?? 0) }}</td>
                        <td class="text-end text-danger pe-3">₹ {{ number_format($totalDue) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="px-3 pb-3">
            @include('institute.components.pagination', ['paginator' => $students, 'perPage' => $perPage])
        </div>
        @endif
    </div>
</div>

@endsection
