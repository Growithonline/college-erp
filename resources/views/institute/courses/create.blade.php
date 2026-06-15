@extends('institute.layout')

@section('content')

<h4>Add Course</h4>

<form method="POST" action="{{ route('courses.store') }}">
    @csrf

    <div class="mb-3">
        <label>Course Type</label>
        <select name="course_type_id" class="form-control" required>
            <option value="">Select Course Type</option>
            @foreach($courseTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label>Course Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Course Code</label>
        <input type="text" name="code" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Duration</label>
        <input type="number" name="duration" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Duration Type</label>
        <select name="duration_type" class="form-control" required>
            <option value="year">Year</option>
            <option value="month">Month</option>
        </select>
    </div>

    <div class="mb-3">
        <label>Structure Type</label>
        <select name="structure_type" class="form-control" required>
            <option value="semester">Semester</option>
            <option value="yearly">Yearly</option>
            <option value="modular">Modular</option>
        </select>
    </div>

    <div class="mb-3 form-check">
        <input type="checkbox" name="lateral_entry_allowed" class="form-check-input">
        <label class="form-check-label">Allow Lateral Entry</label>
    </div>

    <button type="submit" class="btn btn-success">
        Save Course
    </button>

</form>

@endsection