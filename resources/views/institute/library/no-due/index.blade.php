@extends($libraryLayout)
@section('title', 'Library No Dues')
@section('breadcrumb', 'Library / No Dues')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Library No-Dues Check</h4>
        <small class="text-muted">Exam form, TC aur clearance ke liye active issues aur pending fine verify karo.</small>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <form method="GET" class="row g-2">
            <div class="col-md-10">
                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Search student name, UID, enrollment, roll, mobile">
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Active Books</th>
                    <th>Pending Fine</th>
                    <th>Status</th>
                    <th>Print</th>
                </tr>
            </thead>
            <tbody>
            @forelse($students as $student)
                @php $summary = $summaries[$student->id] ?? ['active_issue_count' => 0, 'pending_fine' => 0, 'is_clear' => true]; @endphp
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $student->name }}</div>
                        <small class="text-muted">{{ $student->student_uid }} | {{ $student->enrollment_no ?: '-' }}</small>
                    </td>
                    <td>{{ $student->stream->course->name ?? '-' }}</td>
                    <td>{{ $summary['active_issue_count'] }}</td>
                    <td>Rs {{ number_format((float) $summary['pending_fine'], 2) }}</td>
                    <td>
                        <span class="badge {{ $summary['is_clear'] ? 'bg-success' : 'bg-danger' }}">
                            {{ $summary['is_clear'] ? 'Clear' : 'Pending' }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route($libraryRoutePrefix . '.no-due.print', $student) }}" class="btn btn-outline-primary btn-sm" target="_blank">
                            <i class="bi bi-printer me-1"></i>Print
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-5">Search karke students dekho.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
