@extends('institute.layout')

@section('content')

<h4>Add Part - {{ $course->name }}</h4>

<form method="POST" 
      action="{{ route('course.parts.store', $course->id) }}">
    @csrf

    <div class="mb-3">
        <label>Part Name</label>
        <input type="text" name="part_name" 
               class="form-control" required>
    </div>

    <button type="submit" class="btn btn-success">
        Save Part
    </button>
</form>

@endsection