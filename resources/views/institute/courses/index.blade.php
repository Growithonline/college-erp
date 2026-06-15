@extends('institute.layout')

@section('content')

<h4>Courses</h4>

<a href="{{ route('courses.create') }}" class="btn btn-primary mb-3">
    Add Course
</a>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Name</th>
            <th>Code</th>
            <th>Duration</th>
            <th>Structure</th>
            <th>Manage</th>
        </tr>
    </thead>

    <tbody>
        @forelse($courses as $course)
        <tr>
            <td>{{ $course->name }}</td>
            <td>{{ $course->code }}</td>
            <td>{{ $course->duration }} {{ $course->duration_type }}</td>
            <td>{{ ucfirst($course->structure_type) }}</td>
            <td>
                <a href="{{ route('course.parts.index', $course->id) }}"
                   class="btn btn-sm btn-info">
                   Manage Parts
                </a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="text-center">
                No courses found.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

@endsection