@extends('institute.layout')
@section('title', 'Branding')
@section('breadcrumb', 'Settings / Branding')

@section('content')

<div class="mb-4">
    <h4 class="mb-0 fw-bold">Branding</h4>
    <small class="text-muted">Set the brand color used on your public admission enquiry/application pages</small>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <span class="fw-semibold"><i class="bi bi-palette text-primary me-2"></i>Brand Color</span>
            </div>
            <div class="card-body p-4">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @error('primary_color')
                    <div class="alert alert-danger">{{ $message }}</div>
                @enderror

                <form method="POST" action="{{ route('master.settings.branding.save') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Color</label>
                        <input type="color" name="primary_color" class="form-control form-control-color"
                               value="{{ $institute->primary_color ?? '#2563EB' }}">
                        <small class="text-muted d-block mt-1">Used for buttons and highlights on your public enquiry and application forms. Your logo is managed by the platform team — contact support to change it.</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check2 me-1"></i> Save
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
