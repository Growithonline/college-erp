@extends('institute.layout')
@section('title', 'Edit Vehicle')
@section('breadcrumb', 'Transport / Vehicles / Edit')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex align-items-center gap-2 py-3">
        <i class="bi bi-pencil-square text-warning"></i>
        <span class="fw-semibold">Edit Vehicle — {{ $vehicle->vehicle_no }}</span>
    </div>
    <div class="card-body p-4">
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show py-2 mb-4" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach($errors->all() as $e)<li style="font-size:13px;">{{ $e }}</li>@endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        <form method="POST" action="{{ route('transport.vehicles.update', $vehicle) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            @include('institute.transport.vehicles.form', ['vehicle' => $vehicle, 'existingDocuments' => $existingDocuments, 'documentTypes' => $documentTypes])
        </form>
    </div>
</div>
@endsection
