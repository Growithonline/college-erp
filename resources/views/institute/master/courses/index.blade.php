@extends('institute.layout')
@section('title', 'Courses')
@section('breadcrumb', 'Master / Course')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Courses</h4>
        <small class="text-muted">{{ $courses->count() }} course(s) configured</small>
    </div>
    <a href="{{ route('master.courses.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Course
    </a>
</div>

@if($errors->has('delete'))
<div class="alert alert-danger mb-3"><i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first('delete') }}</div>
@endif

@if($courses->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <div class="card-body">
            <i class="bi bi-book" style="font-size:3rem; color:#94a3b8;"></i>
            <h5 class="mt-3 text-muted">No Courses Yet</h5>
            <a href="{{ route('master.courses.create') }}" class="btn btn-primary mt-2">
                <i class="bi bi-plus-lg me-1"></i> Add First Course
            </a>
        </div>
    </div>
@else
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Course</th>
                        <th>Type</th>
                        <th>Duration</th>
                        <th>Structure</th>
                        <th>Streams</th>
                        <th>ATKT</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($courses as $i => $course)
                    <tr>
                        <td class="text-muted small">{{ $i + 1 }}</td>
                        <td>
                            <div class="fw-semibold">{{ $course->name }}</div>
                            <small class="text-muted">{{ $course->code }}</small>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                {{ $course->type->name ?? '—' }}
                            </span>
                        </td>
                        <td class="small">
                            {{ $course->duration }}
                            {{ $course->duration_type == 'year' ? 'Year(s)' : 'Month(s)' }}
                        </td>
                        <td>
                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                {{ ucfirst($course->structure_type) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('master.courses.streams.index', $course) }}"
                               class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-diagram-3 me-1"></i>
                                {{ $course->streams->count() }} Streams
                            </a>
                        </td>
                        <td class="small text-center">{{ $course->max_atkt_allowed }}</td>
                        <td>
                            <form method="POST" action="{{ route('master.courses.toggle-status', $course) }}">
                                @csrf
                                <button class="btn btn-sm {{ $course->status ? 'btn-success' : 'btn-secondary' }}"
                                        title="{{ $course->status ? 'Click to Deactivate' : 'Click to Activate' }}">
                                    <i class="bi bi-{{ $course->status ? 'check-circle' : 'x-circle' }}"></i>
                                    {{ $course->status ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('master.courses.edit', $course) }}"
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('master.courses.destroy', $course) }}"
                                      onsubmit="return confirm('Delete {{ $course->name }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
