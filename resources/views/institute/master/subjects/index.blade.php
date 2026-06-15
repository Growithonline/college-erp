@extends('institute.layout')
@section('title', 'Subjects')
@section('breadcrumb', 'Master / Subject')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Subjects</h4>
        <small class="text-muted">{{ $subjects->count() }} subject(s) configured</small>
    </div>
    <a href="{{ route('master.subjects.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Subject
    </a>
</div>

@if($errors->has('delete'))
<div class="alert alert-danger mb-3"><i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first('delete') }}</div>
@endif

@if($subjects->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <div class="card-body">
            <i class="bi bi-journal-text" style="font-size:3rem; color:#94a3b8;"></i>
            <h5 class="mt-3 text-muted">No Subjects Yet</h5>
            <a href="{{ route('master.subjects.create') }}" class="btn btn-primary mt-2">
                <i class="bi bi-plus-lg me-1"></i> Add First Subject
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
                        <th>Subject</th>
                        <th>Components</th>
                        <th>Theory Marks</th>
                        <th>Practical Marks</th>
                        <th>Credits</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subjects as $i => $subject)
                    <tr>
                        <td class="text-muted small">{{ $i+1 }}</td>
                        <td>
                            <div class="fw-semibold">{{ $subject->name }}</div>
                            @if($subject->code)
                                <small class="text-muted">{{ $subject->code }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-blue-subtle text-primary border border-primary-subtle me-1">
                                <i class="bi bi-book me-1"></i>Theory
                            </span>
                            @if($subject->has_practical)
                                <span class="badge bg-purple-subtle text-purple border" style="background:#f3e8ff; color:#7c3aed; border-color:#ddd6fe;">
                                    <i class="bi bi-flask me-1"></i>Practical
                                </span>
                            @endif
                        </td>
                        <td class="small">
                            @php $theory = $subject->components->where('component_type','theory')->first() @endphp
                            @if($theory)
                                Max: {{ $theory->max_marks }} / Pass: {{ $theory->pass_marks }}
                            @else — @endif
                        </td>
                        <td class="small">
                            @php $prac = $subject->components->where('component_type','practical')->first() @endphp
                            @if($prac)
                                Max: {{ $prac->max_marks }} / Pass: {{ $prac->pass_marks }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="small text-center">{{ $subject->credit ?? '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('master.subjects.toggle-status', $subject) }}">
                                @csrf
                                <button class="btn btn-sm {{ $subject->status ? 'btn-success' : 'btn-secondary' }}">
                                    <i class="bi bi-{{ $subject->status ? 'check-circle' : 'x-circle' }}"></i>
                                    {{ $subject->status ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('master.subjects.edit', $subject) }}"
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('master.subjects.destroy', $subject) }}"
                                      onsubmit="return confirm('Delete {{ $subject->name }}?')">
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
