@extends('institute.layout')
@section('title', 'Document Upload Rules')
@section('breadcrumb', 'Master / Documents / Upload Rules')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Document Upload Rules</h4>
        <small class="text-muted">Define which document types are required, optional, or skipped for each course and user type</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('master.document-categories.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-folder2 me-1"></i>Categories
        </a>
        <a href="{{ route('master.document-types.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-earmark-text me-1"></i>Types
        </a>
        <a href="{{ route('master.document-rules.notification-settings') }}" class="btn btn-outline-info btn-sm">
            <i class="bi bi-bell me-1"></i>Notification Settings
        </a>
    </div>
</div>

@if($courses->isEmpty())
<div class="card border-0 shadow-sm text-center py-5">
    <div class="card-body">
        <i class="bi bi-mortarboard" style="font-size:3rem;color:#94a3b8;"></i>
        <h5 class="mt-3 text-muted">No Active Courses</h5>
        <p class="text-muted small">Add courses first from the <a href="{{ route('master.courses.index') }}">courses page</a>.</p>
    </div>
</div>
@else
<div class="row g-3">
    @foreach($courses as $course)
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column gap-2">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-mortarboard text-primary mt-1"></i>
                    <div>
                        <div class="fw-semibold">{{ $course->name }}</div>
                        @if($course->code)
                        <div class="text-muted small">{{ $course->code }}</div>
                        @endif
                    </div>
                </div>
                <a href="{{ route('master.document-rules.show', $course) }}"
                   class="btn btn-outline-primary btn-sm mt-auto">
                    <i class="bi bi-sliders me-1"></i>Configure Rules
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

@endsection
