@extends($layout ?? 'institute.layout')

@section('title', 'Student Attendance — Daily')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Student Attendance — Daily</h1>

    {{-- Filters --}}
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-2">
            <input type="date" name="date" class="form-control" value="{{ $date->toDateString() }}" max="{{ now()->toDateString() }}">
        </div>
        <div class="col-md-3">
            <select name="session_id" class="form-control">
                <option value="">All Sessions</option>
                @foreach($sessions as $sess)
                    <option value="{{ $sess->id }}" @selected((int)$sessionId === $sess->id)>
                        {{ $sess->name }} {{ $sess->is_active ? '(Active)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
        <div class="col-md-5 text-end">
            <button type="button" class="btn btn-success me-2" onclick="bulkMark('Present')">
                <i class="fas fa-check-double me-1"></i> All Present
            </button>
            <button type="button" class="btn btn-danger" onclick="bulkMark('Absent')">
                <i class="fas fa-times-circle me-1"></i> All Absent
            </button>
        </div>
    </form>

    <div class="card">
        <div class="card-header bg-light">
            <strong>{{ $date->format('l, d F Y') }}</strong>
            <span class="text-muted ms-2">— {{ $students->count() }} students</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:30px"><input type="checkbox" id="selectAll" onchange="toggleAll(this)"></th>
                        <th>Student Name</th>
                        <th>Roll No</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        @php $att = $attendance->get($student->id); @endphp
                        <tr>
                            <td><input type="checkbox" class="student-cb" value="{{ $student->id }}"></td>
                            <td>{{ $student->name }}</td>
                            <td>{{ $student->roll_no ?? '—' }}</td>
                            <td style="min-width:160px">
                                <select class="form-select form-select-sm status-select"
                                        data-student="{{ $student->id }}"
                                        onchange="markAttendance({{ $student->id }}, this.value, '{{ $date->toDateString() }}')"
                                >
                                    <option value="">Not Marked</option>
                                    @foreach(\App\Models\StudentAttendance::STATUSES as $val => $label)
                                        <option value="{{ $val }}" @selected($att && $att->status === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <span class="text-muted small">{{ $att?->remarks ?? '' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Is session mein koi active student nahi mila.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const CSRF  = document.querySelector('meta[name="csrf-token"]').content;
const DATE  = '{{ $date->toDateString() }}';
const STORE = '{{ route("finance.payroll.student-attendance.store") }}';
const BULK  = '{{ route("finance.payroll.student-attendance.bulk-mark") }}';

function markAttendance(studentId, status, date) {
    if (!status) return;
    fetch(STORE, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ student_id: studentId, date, status })
    }).then(r => r.json()).then(d => {
        if (!d.success) alert('Error: ' + d.message);
    });
}

function toggleAll(cb) {
    document.querySelectorAll('.student-cb').forEach(c => c.checked = cb.checked);
}

function bulkMark(status) {
    const ids = [...document.querySelectorAll('.student-cb:checked')].map(c => parseInt(c.value));
    if (ids.length === 0) {
        const allIds = [...document.querySelectorAll('.student-cb')].map(c => parseInt(c.value));
        if (!confirm(`Saare ${allIds.length} students ko ${status} mark karein?`)) return;
        _doBulk(allIds, status);
        return;
    }
    if (!confirm(`${ids.length} selected students ko ${status} mark karein?`)) return;
    _doBulk(ids, status);
}

function _doBulk(ids, status) {
    fetch(BULK, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ date: DATE, student_ids: ids, status })
    }).then(r => r.json()).then(d => {
        if (d.success) {
            // Update selects
            ids.forEach(id => {
                const sel = document.querySelector(`.status-select[data-student="${id}"]`);
                if (sel) sel.value = status;
            });
        } else {
            alert('Error: ' + d.message);
        }
    });
}
</script>
@endsection
