@extends('institute.layout')
@section('title', isset($stream) ? 'Edit Stream' : 'Add Stream')
@section('breadcrumb', 'Master / Course / '.$course->name.' / '.(isset($stream) ? 'Edit Stream' : 'Add Stream'))

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="bi bi-diagram-3 me-2 text-primary"></i>
                    {{ isset($stream) ? 'Edit Stream' : 'Add Stream' }} — {{ $course->name }}
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-info py-2 small mb-4">
                    <i class="bi bi-lightbulb me-1"></i>
                    Stream = major subject. e.g. "English", "Hindi", "Geography" for BA course.
                    Year rules will be auto-generated based on the course duration.
                </div>

                <form method="POST" action="{{ isset($stream) ? route('master.streams.update', $stream) : route('master.courses.streams.store', $course) }}">
                    @csrf
                    @if(isset($stream)) @method('PUT') @endif

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Stream Name (Major Subject) <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $stream->name ?? '') }}"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="e.g. ENGLISH, HINDI, MATHEMATICS"
                               style="text-transform:uppercase">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Stream Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $stream->code ?? '') }}"
                               class="form-control @error('code') is-invalid @enderror"
                               placeholder="e.g. BA-ENG" style="text-transform:uppercase">
                        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="text-muted">Short unique code for this stream</small>
                    </div>

                    <div class="alert alert-info py-2 small border-0">
                        <i class="bi bi-people me-1"></i>
                        <strong>Seat Limit</strong> — After saving, use the <strong>"Set Seat Limit"</strong>
                        button on the Streams list to configure per-session seat limits.
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i>
                            {{ isset($stream) ? 'Update Stream' : 'Save Stream' }}
                        </button>
                        <a href="{{ route('master.courses.streams.index', $course) }}"
                           class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection