@extends('institute.layout')

@section('title', 'New Session')
@section('breadcrumb', 'Master / Academic Session / New')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-calendar-plus me-2 text-primary"></i>New Academic Session</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('master.sessions.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Session Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="e.g. Sem 1 - 2025-26" maxlength="20">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">e.g. "Sem 1 - 2025-26" or "2025-26"</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Academic Year</label>
                        <input type="text" name="academic_year" value="{{ old('academic_year') }}"
                               class="form-control @error('academic_year') is-invalid @enderror"
                               placeholder="e.g. 2025-26" maxlength="10">
                        @error('academic_year')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Group sessions of the same year (used for transport yearly billing). e.g. "Sem 1 - 2025-26" and "Sem 2 - 2025-26" both get <strong>2025-26</strong>.</small>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" value="{{ old('start_date') }}"
                                   class="form-control @error('start_date') is-invalid @enderror">
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" value="{{ old('end_date') }}"
                                   class="form-control @error('end_date') is-invalid @enderror">
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="alert alert-info py-2 small">
                        <i class="bi bi-info-circle me-1"></i>
                        The first session will be automatically <strong>activated</strong>.
                        Subsequent sessions must be activated manually.
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i> Save Session
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
