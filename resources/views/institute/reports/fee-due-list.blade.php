@php
    $isStaff = auth()->guard('staff')->check();
    $layout = $isStaff ? 'staff.layout' : 'institute.layout';
    $feeDueRoute = $isStaff ? 'staff.reports.fee-due-list' : 'reports.fee-due-list';
    $streamsRoute = $isStaff ? 'staff.reports.streams' : 'reports.streams';
    $feeCreateRoute = $isStaff ? 'staff.fee.create' : 'fee.create';
    $studentHistoryRoute = $isStaff ? 'staff.fee.student-history' : 'fee.student-history';
    $profileRoute = $isStaff ? 'staff.admissions.show' : 'admissions.show';
    $canCollectFee = !$isStaff || auth()->guard('staff')->user()?->canCollectFee();
@endphp
@extends($layout)
@section('title', 'Fee Due List')
@section('breadcrumb', 'Reports / Fee Due List')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Fee Due List</h4>
        <small class="text-muted">
            {{ $sessionObj?->name ?? '' }} — Students with pending fee dues
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" target="_blank"
           class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}"
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
           class="btn btn-outline-success btn-sm">
            <i class="bi bi-filetype-csv me-1"></i> CSV
        </a>
    </div>
</div>

{{-- Summary Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-2">
                        <i class="bi bi-people text-primary fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Students</div>
                        <div class="fw-bold fs-6">{{ number_format($summary['total_students']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-danger bg-opacity-10 p-2">
                        <i class="bi bi-exclamation-circle text-danger fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Zero Payment</div>
                        <div class="fw-bold fs-6 text-danger">{{ number_format($summary['unpaid_count']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-success bg-opacity-10 p-2">
                        <i class="bi bi-cash-stack text-success fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Collection</div>
                        <div class="fw-bold fs-6 text-success">₹ {{ number_format($summary['total_collected']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 p-2" style="background:rgba(124,58,237,0.1);">
                        <i class="bi bi-tag fs-5" style="color:#7c3aed;"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Discount</div>
                        <div class="fw-bold fs-6" style="color:#7c3aed;">₹ {{ number_format($totalDiscount) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-warning bg-opacity-10 p-2">
                        <i class="bi bi-exclamation-triangle text-warning fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Fine</div>
                        <div class="fw-bold fs-6 text-warning">₹ {{ number_format($totalFine) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-info bg-opacity-10 p-2">
                        <i class="bi bi-check2-circle text-info fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Paid</div>
                        <div class="fw-bold fs-6 text-info">₹ {{ number_format($totalPaid) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-danger bg-opacity-10 p-2">
                        <i class="bi bi-wallet2 text-danger fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Due (This Page)</div>
                        <div class="fw-bold fs-6 text-danger">₹ {{ number_format($totalDue) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-4" id="filterCard">
    <div class="card-body">
        <form method="GET" action="{{ route($feeDueRoute) }}" id="filterForm">
            <div class="row g-3">

                {{-- Session --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Session</label>
                    <select name="session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($sessions as $s)
                            <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Course Type --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Course Type</label>
                    <select name="course_type_id" id="courseTypeFilter" class="form-select form-select-sm">
                        <option value="">— All Types —</option>
                        @foreach($courseTypes as $ct)
                            <option value="{{ $ct->id }}" {{ request('course_type_id') == $ct->id ? 'selected' : '' }}>{{ $ct->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Course --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Course</label>
                    <select name="course_id" id="courseFilter" class="form-select form-select-sm">
                        <option value="">— All Courses —</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Stream --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Stream</label>
                    <select name="stream_id" id="streamFilter" class="form-select form-select-sm">
                        <option value="">— All Streams —</option>
                        @foreach($streams as $st)
                            <option value="{{ $st->id }}" {{ request('stream_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Semester --}}
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Semester</label>
                    <select name="semester" class="form-select form-select-sm">
                        <option value="0" {{ ($filterSemester ?? 0) == 0 ? 'selected' : '' }}>All</option>
                        @for($i=1;$i<=8;$i++)
                            <option value="{{ $i }}" {{ ($filterSemester ?? 0) == $i ? 'selected' : '' }}>S{{ $i }}</option>
                        @endfor
                    </select>
                </div>

                {{-- Source --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Source</label>
                    <select name="source" id="sourceFilter" class="form-select form-select-sm"
                            onchange="toggleSourceId(this.value)">
                        <option value="">— All Sources —</option>
                        <option value="direct"  {{ request('source') == 'direct'  ? 'selected' : '' }}>Direct</option>
                        <option value="center"  {{ request('source') == 'center'  ? 'selected' : '' }}>Center</option>
                        <option value="channel" {{ request('source') == 'channel' ? 'selected' : '' }}>Channel Partner</option>
                    </select>
                </div>

                {{-- Source Sub-dropdown --}}
                <div class="col-md-2" id="sourceIdWrapper"
                     style="{{ in_array(request('source'), ['center','channel']) ? '' : 'display:none;' }}">
                    <label class="form-label small fw-semibold" id="sourceIdLabel">
                        {{ request('source') == 'channel' ? 'Channel Partner' : 'Center' }}
                    </label>
                    <select name="source_id" id="sourceIdFilter" class="form-select form-select-sm">
                        <option value="">— All —</option>
                        @if(request('source') == 'center')
                            @foreach($centers as $c)
                                <option value="{{ $c->id }}" {{ request('source_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        @elseif(request('source') == 'channel')
                            @foreach($channelPartners as $cp)
                                <option value="{{ $cp->id }}" {{ request('source_id') == $cp->id ? 'selected' : '' }}>{{ $cp->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                {{-- Category --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Category</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">— All —</option>
                        <option value="general" {{ request('category') == 'general' ? 'selected' : '' }}>General</option>
                        <option value="obc"     {{ request('category') == 'obc'     ? 'selected' : '' }}>OBC</option>
                        <option value="sc"      {{ request('category') == 'sc'      ? 'selected' : '' }}>SC</option>
                        <option value="st"      {{ request('category') == 'st'      ? 'selected' : '' }}>ST</option>
                    </select>
                </div>

                {{-- Gender --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Gender</label>
                    <select name="gender" class="form-select form-select-sm">
                        <option value="">— All —</option>
                        <option value="male"   {{ request('gender') == 'male'   ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ request('gender') == 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other"  {{ request('gender') == 'other'  ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                {{-- Search --}}
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control form-control-sm"
                           placeholder="Name, mobile, UID, enrollment...">
                </div>

                {{-- Show All toggle --}}
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="show_all" value="1"
                               id="showAllToggle" {{ $showAll ? 'checked' : '' }}
                               onchange="this.form.submit()">
                        <label class="form-check-label small" for="showAllToggle">
                            Show all (including zero due)
                        </label>
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="{{ route($feeDueRoute) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-lg"></i> Reset
                    </a>
                </div>
            </div>
            <input type="hidden" name="per_page" value="{{ $perPage }}">
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm" id="reportTable">
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
                        <th>Mother Name</th>
                        <th>Course / Stream</th>
                        <th>Year</th>
                        <th class="text-end">Total Payable</th>
                        <th class="text-end text-success">Paid</th>
                        <th class="text-end" style="color:#7c3aed;">Discount</th>
                        <th class="text-end text-warning">Fine</th>
                        <th class="text-end" style="color:#0891b2;">Lib Fine</th>
                        <th class="text-end text-danger">Due</th>
                        <th class="text-center">Status</th>
                        <th class="text-center pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $i => $student)
                        @php
                            $d          = $dueData[$student->id] ?? ['payable' => 0, 'paid' => 0, 'due' => 0, 'discount' => 0, 'fine' => 0, 'library_fine' => 0];
                            $payable    = $d['payable'];
                            $paid       = $d['paid'];
                            $discount   = $d['discount'];
                            $fine       = $d['fine'];
                            $libFine    = $d['library_fine'] ?? 0;
                            $due        = $d['due'];
                            $pct        = $payable > 0 ? min(100, round(($paid / $payable) * 100)) : ($paid > 0 ? 100 : 0);
                            if (!$showAll && $due <= 0) continue;
                        @endphp
                        <tr>
                            <td class="ps-3 text-muted">{{ $students->firstItem() + $i }}</td>

                            <td>
                                <div class="fw-semibold">{{ $student->name }}</div>
                                <div class="text-muted" style="font-size:0.78rem;">
                                    {{ $student->student_uid }}
                                    @if($student->mobile) · {{ $student->mobile }} @endif
                                </div>
                            </td>

                            <td class="small text-muted">{{ $student->roll_no ?: '—' }}</td>
                            <td class="small">{{ $student->father_name ?: '—' }}</td>
                            <td class="small">{{ $student->mother_name ?: '—' }}</td>

                            <td>
                                <div>{{ $student->stream->course->name ?? '—' }}</div>
                                <div class="text-muted" style="font-size:0.78rem;">{{ $student->stream->name ?? '' }}</div>
                            </td>

                            <td class="text-center">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary fw-normal">
                                    Year {{ $student->coursePart?->year_number ?? '—' }}
                                </span>
                            </td>

                            <td class="text-end fw-semibold">
                                @if($payable > 0) ₹ {{ number_format($payable) }}
                                @else <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td class="text-end text-success fw-semibold">
                                @if($paid > 0) ₹ {{ number_format($paid) }}
                                @else <span class="text-muted">₹ 0</span>
                                @endif
                            </td>

                            <td class="text-end fw-semibold" style="color:#7c3aed;">
                                @if($discount > 0) ₹ {{ number_format($discount) }}
                                @else <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td class="text-end text-warning fw-semibold">
                                @if($fine > 0) ₹ {{ number_format($fine) }}
                                @else <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td class="text-end fw-semibold" style="color:#0891b2;">
                                @if($libFine > 0) ₹ {{ number_format($libFine) }}
                                @else <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td class="text-end">
                                @if($due > 0)
                                    <span class="fw-bold text-danger">₹ {{ number_format($due) }}</span>
                                @elseif($payable == 0)
                                    <span class="text-muted small">Fee not set</span>
                                @else
                                    <span class="text-success small"><i class="bi bi-check-circle-fill me-1"></i>Paid</span>
                                @endif
                            </td>

                            <td class="text-center" style="min-width:90px;">
                                @if($payable > 0)
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="progress flex-grow-1" style="height:5px;">
                                            <div class="progress-bar {{ $pct >= 100 ? 'bg-success' : ($pct > 0 ? 'bg-warning' : 'bg-danger') }}"
                                                 style="width:{{ $pct }}%"></div>
                                        </div>
                                        <small class="text-muted" style="min-width:28px;">{{ $pct }}%</small>
                                    </div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>

                            <td class="text-center pe-3">
                                <div class="d-flex gap-1 justify-content-center">
                                    @if($due > 0 && $canCollectFee)
                                        <a href="{{ route($feeCreateRoute, ['student_id' => $student->id]) }}"
                                           class="btn btn-danger btn-sm py-0 px-2" title="Collect Fee">
                                            <i class="bi bi-cash-coin"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route($studentHistoryRoute, $student->id) }}"
                                       class="btn btn-outline-secondary btn-sm py-0 px-2" title="Payment History">
                                        <i class="bi bi-clock-history"></i>
                                    </a>
                                    <a href="{{ route($profileRoute, $student->id) }}"
                                       class="btn btn-outline-primary btn-sm py-0 px-2" title="Profile">
                                        <i class="bi bi-person"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-semibold">
                    <tr>
                        <td colspan="7" class="ps-3 text-muted small">
                            Page total ({{ $students->count() }} students)
                        </td>
                        <td class="text-end fw-semibold">₹ {{ number_format($totalPayable) }}</td>
                        <td class="text-end text-success">₹ {{ number_format($totalPaid) }}</td>
                        <td class="text-end" style="color:#7c3aed;">₹ {{ number_format($totalDiscount) }}</td>
                        <td class="text-end text-warning">₹ {{ number_format($totalFine) }}</td>
                        <td class="text-end" style="color:#0891b2;">₹ {{ number_format($totalLibraryFine ?? 0) }}</td>
                        <td class="text-end text-danger">₹ {{ number_format($totalDue) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="px-3 pb-3">
            @include('institute.components.pagination', [
                'paginator' => $students,
                'perPage'   => $perPage,
            ])
        </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
const CENTERS_DATA   = @json($centers->map(fn($c) => ['id' => $c->id, 'name' => $c->name]));
const CHANNELS_DATA  = @json($channelPartners->map(fn($c) => ['id' => $c->id, 'name' => $c->name]));
const COURSES_ALL    = @json($courses->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'type_id' => $c->course_type_id]));

// Course Type → filter courses
document.getElementById('courseTypeFilter').addEventListener('change', function() {
    const typeId = this.value;
    const sel    = document.getElementById('courseFilter');
    sel.innerHTML = '<option value="">— All Courses —</option>';
    COURSES_ALL.filter(c => !typeId || String(c.type_id) === typeId).forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id; opt.textContent = c.name;
        sel.appendChild(opt);
    });
    document.getElementById('streamFilter').innerHTML = '<option value="">— All Streams —</option>';
});

// Course → fetch streams
document.getElementById('courseFilter').addEventListener('change', function() {
    const courseId = this.value;
    const streamSel = document.getElementById('streamFilter');
    streamSel.innerHTML = '<option value="">Loading...</option>';
    if (!courseId) { streamSel.innerHTML = '<option value="">— All Streams —</option>'; return; }
    fetch(`{{ route($streamsRoute) }}?course_id=${courseId}`)
        .then(r => r.json())
        .then(data => {
            streamSel.innerHTML = '<option value="">— All Streams —</option>';
            data.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id; opt.textContent = s.name;
                streamSel.appendChild(opt);
            });
        });
});

// Source → show/hide sub-dropdown
function toggleSourceId(source) {
    const wrapper = document.getElementById('sourceIdWrapper');
    const label   = document.getElementById('sourceIdLabel');
    const sel     = document.getElementById('sourceIdFilter');
    if (!source || source === 'direct') {
        wrapper.style.display = 'none';
        sel.innerHTML = '<option value="">— All —</option>';
        return;
    }
    wrapper.style.display = '';
    const data = source === 'center' ? CENTERS_DATA : CHANNELS_DATA;
    label.textContent = source === 'center' ? 'Center' : 'Channel Partner';
    sel.innerHTML = '<option value="">— All —</option>';
    data.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.id; opt.textContent = item.name;
        sel.appendChild(opt);
    });
}
</script>
@endpush
