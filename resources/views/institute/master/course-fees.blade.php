@extends('institute.layout')
@section('title', 'Course Fee Structure')
@section('breadcrumb', 'Master / Fee Structure / Course Fees')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-currency-rupee me-2 text-primary"></i>Course Fee Structure</h4>
        <small class="text-muted">Set fee rules by Course, Year, Student Type, and Session</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('master.fee-structure.subject-fees') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-book me-1"></i> Subject Fees
        </a>
    </div>
</div>

{{-- Filter --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>
                            {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Course</label>
                <select name="course_id" class="form-select form-select-sm">
                    <option value="">-- Select Course --</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-funnel me-1"></i> Load
                </button>
            </div>
        </form>
    </div>
</div>

@if($selectedCourse)

{{-- Add Rule Form --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header py-2" style="background:#1e293b; color:white;">
        <span class="fw-bold small"><i class="bi bi-plus-circle me-2"></i>Add Fee Rule — {{ $selectedCourse->name }}</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('master.fee-structure.course-fees.store') }}">
            @csrf
            <input type="hidden" name="course_id" value="{{ $selectedCourse->id }}">
            <input type="hidden" name="academic_session_id" value="{{ $sessionId }}">

            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Fee Type</label>
                    <select name="fee_type_id" class="form-select form-select-sm" required>
                        <option value="">Select</option>
                        @foreach($feeTypes as $ft)
                            <option value="{{ $ft->id }}">{{ $ft->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Year</label>
                    <select name="course_part" class="form-select form-select-sm" required>
                        <option value="0">All</option>
                        @for($i = 1; $i <= ($selectedCourse->duration_years ?? 3); $i++)
                            <option value="{{ $i }}">{{ $i }}{{ ['st','nd','rd'][$i-1] ?? 'th' }} Year</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Semester</label>
                    <select name="semester" class="form-select form-select-sm" required>
                        @foreach($selectedCourse->semesterOptions() as $val => $lbl)
                        <option value="{{ $val }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Student Type</label>
                    <select name="student_type" class="form-select form-select-sm" required>
                        <option value="all">All</option>
                        <option value="regular">Regular</option>
                        <option value="ex_student">Ex-Student</option>
                        <option value="lateral">Lateral Entry</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Source</label>
                    <select name="admission_source" class="form-select form-select-sm" required>
                        <option value="all">All Sources</option>
                        <option value="direct">Direct</option>
                        <option value="center">Center</option>
                        <option value="channel_partner">Channel Partner</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Category</label>
                    <select name="category" class="form-select form-select-sm" required>
                        <option value="all">All</option>
                        <option value="general">General</option>
                        <option value="obc">OBC</option>
                        <option value="sc">SC</option>
                        <option value="st">ST</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Gender</label>
                    <select name="gender" class="form-select form-select-sm" required>
                        <option value="all">All</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Amount ₹</label>
                    <input type="number" name="amount" class="form-control form-control-sm"
                           min="0" step="0.01" required placeholder="0">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="bi bi-plus-lg"></i> Add
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Rules Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header py-2" style="background:#1e293b; color:white;">
        <span class="fw-bold small">
            <i class="bi bi-table me-2"></i>Fee Rules — {{ $selectedCourse->name }}
            <span class="badge bg-secondary ms-2">{{ $rules->count() }} rules</span>
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light" style="font-size:12px;">
                <tr>
                    <th>Fee Type</th>
                    <th>Year</th>
                    <th>Semester</th>
                    <th>Student Type</th>
                    <th>Source</th>
                    <th>Category</th>
                    <th>Gender</th>
                    <th class="text-end">Amount</th>
                    <th></th>
                </tr>
            </thead>
            <tbody style="font-size:12px;">
                @forelse($rules as $rule)
                <tr>
                    <td class="fw-semibold">{{ $rule->feeType->name ?? '—' }}</td>
                    <td>
                        @if($rule->course_part == 0) <span class="badge bg-secondary">All Years</span>
                        @else {{ $rule->course_part }}{{ ['st','nd','rd'][$rule->course_part-1] ?? 'th' }} Year
                        @endif
                    </td>
                    <td>
                        @if($rule->semester == 0)
                            <span class="badge bg-secondary">{{ $selectedCourse->semesterOptions()[0] }}</span>
                        @else
                            {{ $selectedCourse->semesterLabel($rule->semester) }}
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $rule->student_type == 'all' ? 'bg-secondary' : 'bg-primary' }}">
                            {{ $rule->student_type_label }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $rule->admission_source == 'all' ? 'bg-secondary' : 'bg-info text-dark' }}">
                            {{ $rule->admission_source_label }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $rule->category == 'all' ? 'bg-secondary' : 'bg-warning text-dark' }}">
                            {{ strtoupper($rule->category) }}
                        </span>
                    </td>
                    <td>{{ ucfirst($rule->gender) }}</td>
                    <td class="text-end fw-bold text-success">₹ {{ number_format($rule->amount, 2) }}</td>
                    <td>
                        <form method="POST"
                              action="{{ route('master.fee-structure.course-fees.destroy', $rule) }}"
                              onsubmit="return confirm('Delete this rule?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                        No fee rules yet — add one using the form above
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@else
<div class="text-center text-muted py-5">
    <i class="bi bi-arrow-up-circle fs-2 d-block mb-2"></i>
    Select a course above to view fee rules
</div>
@endif

@endsection
