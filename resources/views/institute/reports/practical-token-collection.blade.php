@extends('institute.layout')
@section('title', 'Practical Token Collection Report')
@section('breadcrumb', 'Fee Collection > Fee Collection Report > Practical Token Collection Report')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-ticket-perforated text-purple me-2" style="color:#7c3aed"></i> Practical Token Collection</h4>
        <small class="text-muted">Token-wise paid, remaining and student detail</small>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 p-2" style="background:rgba(124,58,237,0.1)">
                        <i class="bi bi-ticket-perforated fs-5" style="color:#7c3aed"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Batches</div>
                        <div class="fw-bold fs-5">{{ $batches->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-success bg-opacity-10 p-2">
                        <i class="bi bi-currency-rupee text-success fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Collected</div>
                        <div class="fw-bold fs-5">₹{{ number_format($grandTotal, 2) }}</div>
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
                        <i class="bi bi-people text-primary fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Students</div>
                        <div class="fw-bold fs-5">{{ number_format($grandStudents) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end" id="filterForm">

            {{-- Session --}}
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    <option value="">All Sessions</option>
                    @foreach($sessions as $sess)
                        <option value="{{ $sess->id }}" {{ $sess->id == $sessionId ? 'selected' : '' }}>
                            {{ $sess->name }}{{ $sess->is_active ? ' (Active)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Course Type --}}
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Course Type</label>
                <select name="course_type_id" id="courseTypeFilter" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    @foreach($courseTypes as $ct)
                        <option value="{{ $ct->id }}" {{ request('course_type_id') == $ct->id ? 'selected' : '' }}>
                            {{ $ct->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Course --}}
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Course</label>
                <select name="course_id" id="courseFilter" class="form-select form-select-sm">
                    <option value="">All Courses</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}"
                                data-type="{{ $c->course_type_id }}"
                                {{ request('course_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Subject / Stream --}}
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Subject / Stream</label>
                <select name="subject_id" class="form-select form-select-sm">
                    <option value="">All Subjects</option>
                    @foreach($subjects as $sub)
                        <option value="{{ $sub->id }}" {{ request('subject_id') == $sub->id ? 'selected' : '' }}>
                            {{ $sub->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Year / Semester --}}
            <div class="col-md-1">
                <label class="form-label small fw-semibold mb-1">Semester</label>
                <select name="semester" class="form-select form-select-sm">
                    <option value="">All</option>
                    @for($i = 1; $i <= 8; $i++)
                        <option value="{{ $i }}" {{ request('semester') == $i ? 'selected' : '' }}>S{{ $i }}</option>
                    @endfor
                </select>
            </div>

            {{-- Buttons --}}
            <div class="col-auto d-flex gap-2 align-items-end">
                <button class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filter</button>
                <a href="{{ route('reports.fee-collection.practical-token') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>

            {{-- Export Controls --}}
            @if($batches->isNotEmpty())
            <div class="col-12 border-top pt-2 mt-1 d-flex align-items-end gap-2 flex-wrap">
                <div style="min-width:220px;">
                    <label class="form-label small fw-semibold mb-1">Export Batch</label>
                    <select name="batch_id" id="exportBatchId" class="form-select form-select-sm">
                        <option value="">All Batches</option>
                        @foreach($batches as $b)
                            <option value="{{ $b->id }}" {{ request('batch_id') == $b->id ? 'selected' : '' }}>
                                {{ $b->title ?? ('Token #' . $b->id) }} — {{ $b->course?->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex gap-1 align-items-end">
                    <button type="submit" name="export" value="csv"
                            class="btn btn-outline-success btn-sm" formnovalidate>
                        <i class="bi bi-filetype-csv me-1"></i>CSV
                    </button>
                    <button type="submit" name="export" value="excel"
                            class="btn btn-outline-primary btn-sm" formnovalidate>
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </button>
                    <button type="submit" name="export" value="pdf"
                            class="btn btn-outline-danger btn-sm" formnovalidate
                            onclick="this.form.target='_blank'">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </button>
                </div>
            </div>
            @endif
        </form>
    </div>
</div>

<script>
(function () {
    const typeSelect   = document.getElementById('courseTypeFilter');
    const courseSelect = document.getElementById('courseFilter');
    const allOptions   = Array.from(courseSelect.options);

    function filterCourses() {
        const typeId = typeSelect.value;
        const current = courseSelect.value;
        courseSelect.innerHTML = '';
        allOptions.forEach(opt => {
            if (!typeId || !opt.dataset.type || opt.dataset.type === typeId || opt.value === '') {
                courseSelect.appendChild(opt.cloneNode(true));
            }
        });
        courseSelect.value = current;
    }

    typeSelect.addEventListener('change', filterCourses);
    filterCourses();
})();
</script>

{{-- Batch Cards --}}
@forelse($batches as $batchIdx => $batch)
@php
    $batchTotal     = (float) $batch->entries->sum('amount');
    $batchFine      = (float) $batch->entries->sum('fine');
    $batchDiscount  = (float) $batch->entries->sum('discount');
    $tokenAmt       = (float) $batch->token_amount;
    $entryCount     = $batch->entries->count();
    $expectedTotal  = $tokenAmt * $entryCount;
    $remaining      = $expectedTotal - $batchTotal;
    $collapseId     = 'batchCollapse_' . $batch->id;
@endphp
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2"
         style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
         aria-expanded="{{ $batchIdx === 0 ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-chevron-down text-muted toggle-icon" style="font-size:12px;transition:transform .2s;"></i>
            <span class="fw-bold me-2" style="color:#7c3aed">
                <i class="bi bi-ticket-perforated me-1"></i>{{ $batch->title ?? ('Token #' . $batch->id) }}
            </span>
            <span class="badge bg-light text-dark border me-1">{{ $batch->course?->name ?? '-' }}</span>
            @if($batch->subject)
                <span class="badge bg-light text-dark border me-1">{{ $batch->subject->name }}</span>
            @endif
            <span class="badge bg-light text-dark border">Sem {{ $batch->semester }}</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <small class="text-muted">{{ $batch->collection_date?->format('d M Y') }}</small>
            <a href="{{ route('fee.practical-tokens.show', $batch) }}"
               class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:11px"
               onclick="event.stopPropagation()">
                Manage
            </a>
        </div>
    </div>

    <div class="collapse {{ $batchIdx === 0 ? 'show' : '' }}" id="{{ $collapseId }}">
        <div class="card-body">
            {{-- Mini stats --}}
            <div class="row g-2 mb-3">
                <div class="col-6 col-md-2">
                    <div class="bg-light rounded p-2 text-center">
                        <div class="small text-muted">Token Amount</div>
                        <div class="fw-bold">₹{{ number_format($tokenAmt, 2) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="bg-light rounded p-2 text-center">
                        <div class="small text-muted">Students</div>
                        <div class="fw-bold">{{ $entryCount }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="bg-success bg-opacity-10 rounded p-2 text-center">
                        <div class="small text-muted">Collected</div>
                        <div class="fw-bold text-success">₹{{ number_format($batchTotal, 2) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="bg-warning bg-opacity-10 rounded p-2 text-center">
                        <div class="small text-muted">Fine</div>
                        <div class="fw-bold text-warning">₹{{ number_format($batchFine, 2) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="bg-info bg-opacity-10 rounded p-2 text-center">
                        <div class="small text-muted">Discount</div>
                        <div class="fw-bold text-info">₹{{ number_format($batchDiscount, 2) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="{{ $remaining > 0 ? 'bg-danger bg-opacity-10' : 'bg-success bg-opacity-10' }} rounded p-2 text-center">
                        <div class="small text-muted">Remaining</div>
                        <div class="fw-bold {{ $remaining > 0 ? 'text-danger' : 'text-success' }}">
                            ₹{{ number_format(max(0, $remaining), 2) }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Entry table --}}
            @if($batch->entries->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0" style="font-size:12px">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>UID</th>
                            <th>Roll No</th>
                            <th>Father Name</th>
                            <th>Mother Name</th>
                            <th class="text-end">Amount (₹)</th>
                            <th class="text-end">Fine (₹)</th>
                            <th class="text-end">Discount (₹)</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Posted By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($batch->entries as $j => $entry)
                        <tr>
                            <td>{{ $j + 1 }}</td>
                            <td>{{ $entry->student?->name ?? '-' }}</td>
                            <td><code>{{ $entry->student?->student_uid ?? '-' }}</code></td>
                            <td>{{ $entry->student?->roll_no ?? '-' }}</td>
                            <td>{{ $entry->student?->father_name ?? '-' }}</td>
                            <td>{{ $entry->student?->mother_name ?? '-' }}</td>
                            <td class="text-end">{{ number_format((float)$entry->amount, 2) }}</td>
                            <td class="text-end">{{ number_format((float)$entry->fine, 2) }}</td>
                            <td class="text-end">{{ number_format((float)$entry->discount, 2) }}</td>
                            <td>
                                <span class="badge {{ $entry->status === 'posted' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                    {{ ucfirst($entry->status ?? 'pending') }}
                                </span>
                            </td>
                            <td>{{ $entry->posted_at?->format('d M Y') ?? '-' }}</td>
                            <td class="small text-muted">{{ $entry->entered_by_name }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="6" class="text-end small">Total:</td>
                            <td class="text-end small text-success">{{ number_format($batchTotal, 2) }}</td>
                            <td class="text-end small text-warning">{{ number_format($batchFine, 2) }}</td>
                            <td class="text-end small text-info">{{ number_format($batchDiscount, 2) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @else
                <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i> No entries in this batch.</p>
            @endif
        </div>
    </div>
</div>
@empty
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-ticket-perforated fs-3 d-block mb-2 opacity-25"></i>
        <div class="fw-semibold">No practical token batches found</div>
        <small>No token batch has been created for this session</small>
    </div>
</div>
@endforelse

<script>
document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function(header) {
    header.addEventListener('click', function() {
        var icon = this.querySelector('.toggle-icon');
        if (!icon) return;
        var target = document.querySelector(this.getAttribute('data-bs-target'));
        if (target) {
            target.addEventListener('shown.bs.collapse',  function() { icon.style.transform = 'rotate(0deg)'; }, { once: true });
            target.addEventListener('hidden.bs.collapse', function() { icon.style.transform = 'rotate(-90deg)'; }, { once: true });
        }
    });
});
// Init collapsed icons
document.querySelectorAll('.collapse:not(.show)').forEach(function(el) {
    var header = document.querySelector('[data-bs-target="#' + el.id + '"]');
    if (header) { var icon = header.querySelector('.toggle-icon'); if (icon) icon.style.transform = 'rotate(-90deg)'; }
});
</script>

@endsection
