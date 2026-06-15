@extends('institute.layout')
@section('title', 'Subject Fee Rules — Summary')
@section('breadcrumb', 'Master / Fee Structure / Subject Fees / Summary')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-list-check me-2 text-success"></i>Subject Fee Rules
        </h4>
        <small class="text-muted">View, edit and delete all subject fee rules</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('master.fee-structure.subject-fees') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Add / Edit Fees
        </a>
        <a href="{{ route('master.fee-structure.course-fees') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-currency-rupee me-1"></i> Course Fees
        </a>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-3 fw-bold text-primary">{{ $totalRules }}</div>
            <div class="small text-muted">Total Rules</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-3 fw-bold text-success">₹ {{ number_format($totalSubjectFee) }}</div>
            <div class="small text-muted">Total Subject Fees</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-3 fw-bold text-warning">₹ {{ number_format($totalPracticalFee) }}</div>
            <div class="small text-muted">Total Practical Fees</div>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Session</label>
                <select name="session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($sessions as $s)
                    <option value="{{ $s->id }}" {{ request('session_id', $sessionId) == $s->id ? 'selected' : '' }}>
                        {{ $s->name }} {{ $s->is_active ? '(Active)' : '' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Course</label>
                <select name="course_id" class="form-select form-select-sm">
                    <option value="">All Courses</option>
                    @foreach($courses as $c)
                    <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Year</label>
                <select name="course_part" class="form-select form-select-sm">
                    <option value="">All Years</option>
                    @for($i = 1; $i <= 6; $i++)
                    <option value="{{ $i }}" {{ request('course_part') == $i ? 'selected' : '' }}>Year {{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Semester</label>
                <select name="semester" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="0" {{ request('semester')==='0' ? 'selected' : '' }}>Annual</option>
                    <option value="1" {{ request('semester')==='1' ? 'selected' : '' }}>Sem 1</option>
                    <option value="2" {{ request('semester')==='2' ? 'selected' : '' }}>Sem 2</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Rules Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header py-2" style="background:#1e293b; color:white;">
        <div class="d-flex justify-content-between align-items-center">
            <span class="fw-bold small">
                <i class="bi bi-table me-2"></i>Subject Fee Rules
                &nbsp;|&nbsp; Session: {{ $sessions->find($sessionId)?->name ?? '' }}
            </span>
            <span class="badge bg-secondary">{{ $totalRules }} rules</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0" style="font-size:12px;">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Sem</th>
                    <th>Subject</th>
                    <th class="text-center">Practical</th>
                    <th class="text-end">Subject Fee</th>
                    <th class="text-end">Practical Fee</th>
                    <th class="text-end">Total</th>
                    <th class="text-center" style="width:80px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($allRules as $i => $rule)
                <tr>
                    <td class="text-muted">{{ $i + 1 }}</td>
                    <td class="fw-semibold">{{ $rule->course->name ?? '—' }}</td>
                    <td>
                        Year {{ $rule->course_part }}
                    </td>
                    <td>
                        @if($rule->semester == 0) <span class="badge bg-secondary">Annual</span>
                        @else Sem {{ $rule->semester }}
                        @endif
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $rule->subject->name ?? '—' }}</div>
                        @if($rule->subject?->code)
                        <small class="text-muted">({{ $rule->subject->code }})</small>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($rule->subject?->has_practical)
                        <span class="badge bg-warning text-dark">🔬 Yes</span>
                        @else
                        <span class="text-muted small">No</span>
                        @endif
                    </td>
                    <td class="text-end fw-semibold">₹ {{ number_format($rule->subject_fee) }}</td>
                    <td class="text-end {{ $rule->practical_fee > 0 ? 'fw-semibold text-warning' : 'text-muted' }}">
                        ₹ {{ number_format($rule->practical_fee) }}
                    </td>
                    <td class="text-end fw-bold text-success">
                        ₹ {{ number_format($rule->subject_fee + $rule->practical_fee) }}
                    </td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <button type="button"
                                    class="btn btn-outline-primary btn-sm py-0 px-2"
                                    onclick="openEditModal({{ $rule->id }}, {{ $rule->subject_fee }}, {{ $rule->practical_fee }}, '{{ addslashes($rule->subject?->name ?? '') }}')"
                                    title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST"
                                  action="{{ route('master.fee-structure.subject-fees.destroy', $rule) }}"
                                  onsubmit="return confirm('Delete this rule?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                        No subject fee rules found
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($allRules->total())
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="6" class="text-end">Total:</td>
                    <td class="text-end">₹ {{ number_format($totalSubjectFee) }}</td>
                    <td class="text-end text-warning">₹ {{ number_format($totalPracticalFee) }}</td>
                    <td class="text-end text-success">₹ {{ number_format($totalSubjectFee + $totalPracticalFee) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    <div class="px-3 pb-3">
        @include('institute.components.pagination', ['paginator' => $allRules, 'perPage' => $perPage ?? 20])
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-semibold">Edit Subject Fee — <span id="edit_subject_name"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm" method="POST">
                @csrf @method('PATCH')
                <div class="modal-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Subject Fee ₹ <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₹</span>
                                <input type="number" name="subject_fee" id="edit_subject_fee"
                                       class="form-control" min="0" max="999999" step="1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Practical Fee ₹</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₹</span>
                                <input type="number" name="practical_fee" id="edit_practical_fee"
                                       class="form-control" min="0" max="999999" step="1">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(ruleId, subjectFee, practicalFee, subjectName) {
    document.getElementById('editForm').action =
        '{{ url("master/fee-structure/subject-fees") }}/' + ruleId;
    document.getElementById('edit_subject_fee').value   = subjectFee;
    document.getElementById('edit_practical_fee').value = practicalFee;
    document.getElementById('edit_subject_name').textContent = subjectName;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

@endsection