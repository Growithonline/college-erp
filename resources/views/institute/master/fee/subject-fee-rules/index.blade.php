@extends('institute.layout')
@section('title', 'Subject Fee Structure')
@section('breadcrumb', 'Master / Fee Structure / Subject Fees')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-book me-2 text-primary"></i>Subject Fee Structure
        </h4>
        <small class="text-muted">Set subject fees by course, year and semester</small>
    </div>
    <a href="{{ route('master.fee-structure.course-fees') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-currency-rupee me-1"></i> Course Fees
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show py-2">
    @foreach($errors->all() as $err)<div><i class="bi bi-exclamation-triangle me-1"></i>{{ $err }}</div>@endforeach
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ── Filter Bar ── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" id="filterForm" class="row g-2 align-items-end">

            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    @foreach($sessions as $s)
                    <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>
                        {{ $s->name }} {{ $s->is_active ? '(Active)' : '' }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Course</label>
                <select name="course_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Select Course --</option>
                    @foreach($courses as $c)
                    <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Year / Part</label>
                <select name="course_part" class="form-select form-select-sm">
                    <option value="">-- Select Year --</option>
                    @if($selectedCourse)
                        @for($i = 1; $i <= ($selectedCourse->duration ?? 3); $i++)
                        <option value="{{ $i }}" {{ request('course_part') == $i ? 'selected' : '' }}>
                            Year {{ $i }}
                            @if($i == 1) (1st) @elseif($i == 2) (2nd) @elseif($i == 3) (3rd) @else ({{ $i }}th) @endif
                        </option>
                        @endfor
                    @else
                        @for($i = 1; $i <= 6; $i++)
                        <option value="{{ $i }}" {{ request('course_part') == $i ? 'selected' : '' }}>Year {{ $i }}</option>
                        @endfor
                    @endif
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Semester</label>
                <select name="semester" class="form-select form-select-sm">
                    <option value="">-- Select Sem --</option>
                    @foreach(($selectedCourse ? $selectedCourse->semesterOptions() : [0=>'Both',1=>'Sem 1',2=>'Sem 2']) as $val => $lbl)
                    <option value="{{ $val }}" {{ request('semester') == $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-funnel me-1"></i> Load Subjects
                </button>
            </div>

        </form>
    </div>
</div>

@if($selectedCourse && $coursePart && $semester !== null)

{{-- ── Info Bar ── --}}
<div class="alert alert-info py-2 mb-3 d-flex align-items-center gap-2">
    <i class="bi bi-info-circle"></i>
    <span>
        <strong>{{ $selectedCourse->name }}</strong> —
        Year {{ $coursePart }},
        {{ $selectedCourse->semesterLabel($semester) }}
        &nbsp;|&nbsp;
        Session: <strong>{{ $sessions->find($sessionId)?->name ?? '' }}</strong>
        &nbsp;|&nbsp;
        <span class="text-primary fw-semibold">{{ $subjects->count() }} subjects</span> mapped
    </span>
</div>

@if($subjects->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-book fs-2 d-block mb-2"></i>
        <h6>No subjects mapped for Year {{ $coursePart }} of this course</h6>
        <p class="small">
            First map subjects via
            <a href="{{ route('master.courses.streams.index', $selectedCourse) }}">
                Master → Course → Streams → Subjects
            </a>
        </p>
    </div>
</div>
@else

{{-- ── Bulk Fee Entry Table ── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header py-2" style="background:#1e293b; color:white;">
        <div class="d-flex justify-content-between align-items-center">
            <span class="fw-bold small">
                <i class="bi bi-table me-2"></i>
                Subject Fees — {{ $selectedCourse->name }},
                Year {{ $coursePart }},
                {{ $selectedCourse->semesterLabel($semester) }}
            </span>
            <span class="badge bg-secondary">{{ $subjects->count() }} subjects</span>
        </div>
    </div>

    <form method="POST" action="{{ route('master.fee-structure.subject-fees.bulk') }}" novalidate>
        @csrf
        <input type="hidden" name="academic_session_id" value="{{ $sessionId }}">
        <input type="hidden" name="course_id"           value="{{ $selectedCourse->id }}">
        <input type="hidden" name="course_part"         value="{{ $coursePart }}">
        <input type="hidden" name="semester"            value="{{ $semester }}">

        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:13px;">
                <thead class="table-light">
                    <tr>
                        <th style="width:35px;">#</th>
                        <th>Subject Name</th>
                        <th style="width:100px;">Role</th>
                        <th style="width:80px;" class="text-center">Practical</th>
                        <th style="width:170px;">
                            Subject Fee ₹
                            <small class="text-muted d-block" style="font-size:10px;">(Theory)</small>
                        </th>
                        <th style="width:170px;">
                            Practical Fee ₹
                            <small class="text-muted d-block" style="font-size:10px;">(Lab)</small>
                        </th>
                        <th style="width:100px;" class="text-end">Total ₹</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $existingRules = $rules->keyBy('subject_id');
                        $grandTotal    = 0;
                    @endphp
                    @foreach($subjects as $i => $subject)
                    @php
                        $existing     = $existingRules[$subject->id] ?? null;
                        $subFee       = (float) ($existing?->subject_fee   ?? 0);
                        $pracFee      = (float) ($existing?->practical_fee ?? 0);
                        $rowTotal     = $subFee + $pracFee;
                        $grandTotal  += $rowTotal;
                        // Role from course_stream_subjects
                        $cssRole = \App\Models\CourseStreamSubject::where('subject_id', $subject->id)
                            ->whereIn('course_stream_id', function($q) use ($selectedCourse) {
                                $q->select('id')->from('course_streams')->where('course_id', $selectedCourse->id);
                            })
                            ->value('subject_role');
                    @endphp
                    <tr class="{{ $existing ? '' : 'table-warning bg-opacity-25' }}">
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td>
                            <div class="fw-semibold">
                                {{ $subject->name }}
                                @if($subject->code)
                                <span class="text-muted" style="font-size:11px;">({{ $subject->code }})</span>
                                @endif
                            </div>
                            @if(!$existing)
                            <small class="text-warning">⚠ Fee not set yet</small>
                            @endif
                        </td>
                        <td>
                            @if($cssRole)
                            <span class="badge {{ match($cssRole) {
                                'major'      => 'bg-primary',
                                'minor'      => 'bg-info text-dark',
                                'compulsory' => 'bg-success',
                                default      => 'bg-secondary'
                            } }}">
                                {{ ucfirst($cssRole) }}
                            </span>
                            @else
                            <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($subject->has_practical)
                            <span class="badge bg-warning text-dark">🔬 Yes</span>
                            @else
                            <span class="text-muted small">No</span>
                            @endif
                        </td>
                        <td>
                            <input type="hidden" name="fees[{{ $i }}][subject_id]" value="{{ $subject->id }}">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₹</span>
                                <input type="number"
                                       name="fees[{{ $i }}][subject_fee]"
                                       class="form-control subject-fee"
                                       id="sf-{{ $i }}"
                                       value="{{ $subFee }}"
                                       min="0" max="999999" step="1"
                                       oninput="updateRow({{ $i }})">
                            </div>
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₹</span>
                                <input type="number"
                                       name="fees[{{ $i }}][practical_fee]"
                                       class="form-control practical-fee {{ !$subject->has_practical ? 'bg-light text-muted' : '' }}"
                                       id="pf-{{ $i }}"
                                       value="{{ $pracFee }}"
                                       min="0" max="999999" step="1"
                                       {{ !$subject->has_practical ? 'readonly tabindex="-1"' : '' }}
                                       oninput="updateRow({{ $i }})">
                            </div>
                        </td>
                        <td class="text-end fw-semibold text-success" id="total-{{ $i }}">
                            ₹ {{ number_format($rowTotal, 0) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="6" class="text-end fw-bold">Grand Total:</td>
                        <td class="text-end fw-bold text-success fs-6" id="grand-total">
                            ₹ {{ number_format($grandTotal, 0) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="card-footer d-flex gap-2 justify-content-between align-items-center py-2">
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Yellow rows = Fee not set yet.
                Practical disabled = Subject has no practical.
            </small>
            <button type="submit" class="btn btn-success px-4">
                <i class="bi bi-check-lg me-1"></i> Save All Subject Fees
            </button>
        </div>
    </form>
</div>

@endif {{-- subjects not empty --}}

@elseif($selectedCourse && $coursePart)
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Course and year selected — please also select a <strong>Semester</strong>.
</div>
@elseif($selectedCourse)
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Course selected — please also select a <strong>Year</strong> and <strong>Semester</strong>.
</div>
@else
<div class="text-center text-muted py-5">
    <i class="bi bi-arrow-up-circle fs-2 d-block mb-3 text-primary"></i>
    <h6>Select Course, Year and Semester</h6>
    <p class="small">Use the filter above to select and click Load</p>
</div>
@endif

@endsection

@push('scripts')
<script>
function updateRow(i) {
    const sf = parseFloat(document.getElementById('sf-' + i).value) || 0;
    const pf = parseFloat(document.getElementById('pf-' + i).value) || 0;
    const total = sf + pf;
    document.getElementById('total-' + i).textContent = '₹ ' + total.toLocaleString('en-IN', { maximumFractionDigits: 0 });
    updateGrandTotal();
}

function updateGrandTotal() {
    let grand = 0;
    document.querySelectorAll('[id^="total-"]').forEach(el => {
        const val = el.textContent.replace('₹ ', '').replace(/,/g, '');
        grand += parseFloat(val) || 0;
    });
    document.getElementById('grand-total').textContent = '₹ ' + grand.toLocaleString('en-IN', { maximumFractionDigits: 0 });
}
</script>
@endpush