@extends('institute.layout')

@section('content')

<h4>{{ $course->name }} - Parts</h4>

<a href="{{ route('course.parts.create', $course->id) }}" 
   class="btn btn-primary mb-3">
   Add Part
</a>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Part Name</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach($parts as $part)
        <tr>
            <td>{{ $part->part_number }}</td>
            <td>{{ $part->part_name }}</td>
            <td>
                <form method="POST" 
                      action="{{ route('course.parts.destroy', [$course->id, $part->id]) }}">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm">
                        Delete
                    </button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

@endsection