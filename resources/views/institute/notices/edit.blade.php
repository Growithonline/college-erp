@extends($layout ?? 'institute.layout')
@section('title', 'Edit Notice')
@section('breadcrumb', 'Notices / Edit')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-pencil me-2 text-primary"></i>Edit Notice</h4>
    </div>
    <a href="{{ route(($rp ?? 'notices') . '.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route(($rp ?? 'notices') . '.update', $notice) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            @include('institute.notices._form', ['notice' => $notice])
            <div class="mt-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                           {{ old('is_active', $notice->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active (visible to users)</label>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i> Update Notice
                </button>
                <a href="{{ route(($rp ?? 'notices') . '.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
