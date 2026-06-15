@extends($layout ?? 'institute.layout')
@section('title', 'New Notice')
@section('breadcrumb', 'Notices / New')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-megaphone me-2 text-primary"></i>New Notice</h4>
    </div>
    <a href="{{ route(($rp ?? 'notices') . '.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route(($rp ?? 'notices') . '.store') }}" enctype="multipart/form-data">
            @csrf
            @include('institute.notices._form')
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-megaphone me-1"></i> Post Notice
                </button>
                <a href="{{ route(($rp ?? 'notices') . '.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
