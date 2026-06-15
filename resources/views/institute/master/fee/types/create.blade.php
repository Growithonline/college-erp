@extends('institute.layout')
@section('title', isset($feeType) ? 'Edit Fee Type' : 'Add Fee Type')
@section('breadcrumb','Master / Fee Types / ' . (isset($feeType) ? 'Edit' : 'New'))
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-tags me-2 text-primary"></i>{{ isset($feeType) ? 'Edit Fee Type' : 'Add Fee Type' }}</h5>
            </div>
            <div class="card-body p-4">
                @if(isset($feeType))
                    <form method="POST" action="{{ route('master.fee-types.update', $feeType) }}">@method('PUT')
                @else
                    <form method="POST" action="{{ route('master.fee-types.store') }}">
                @endif
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $feeType->name ?? '') }}"
                           class="form-control @error('name') is-invalid @enderror" placeholder="e.g. EXAM FEE"
                           style="text-transform:uppercase">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                    <select name="category" class="form-select @error('category') is-invalid @enderror">
                        <option value="">Select Category</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" {{ old('category', $feeType->category ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Description</label>
                    <input type="text" name="description" value="{{ old('description', $feeType->description ?? '') }}"
                           class="form-control" placeholder="Optional description">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Save</button>
                    <a href="{{ route('master.fee-types.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
