@extends('institute.layout')

@section('title', 'Edit Session')
@section('breadcrumb', 'Master / Academic Session / Edit')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-pencil me-2 text-primary"></i>Edit Session — {{ $session->name }}</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('master.sessions.update', $session) }}">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Session Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $session->name) }}"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="e.g. 2025-26" maxlength="20">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date"
                                   value="{{ old('start_date', $session->start_date->format('Y-m-d')) }}"
                                   class="form-control @error('start_date') is-invalid @enderror">
                            @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date"
                                   value="{{ old('end_date', $session->end_date->format('Y-m-d')) }}"
                                   class="form-control @error('end_date') is-invalid @enderror">
                            @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i> Update Session
                        </button>
                        <a href="{{ route('master.sessions.index') }}" class="btn btn-outline-secondary px-4">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
