@extends('institute.layout')
@section('title', isset($category) ? 'Edit Expense Category' : 'New Expense Category')
@section('breadcrumb', 'Finance / Wallet / Expense Categories / ' . (isset($category) ? 'Edit' : 'New'))

@section('content')
<div class="mb-4">
    <h4 class="mb-0 fw-bold">
        <i class="bi bi-folder-plus me-2 text-warning"></i>
        {{ isset($category) ? 'Edit Category' : 'New Expense Category (L1)' }}
    </h4>
</div>

<div class="card border-0 shadow-sm" style="max-width: 500px">
    <div class="card-body p-4">
        <form method="POST" action="{{ isset($category)
            ? route('finance.wallet.expense-categories.update', $category)
            : route('finance.wallet.expense-categories.store') }}">
            @csrf
            @if(isset($category)) @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $category->name ?? '') }}"
                       placeholder="e.g. Maintenance, IT, Academic, Administration">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <input type="text" name="description" class="form-control"
                       value="{{ old('description', $category->description ?? '') }}" placeholder="Optional">
            </div>

            @if(isset($category))
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                           {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="isActive">Active</label>
                </div>
            </div>
            @endif

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-warning">{{ isset($category) ? 'Update' : 'Create' }}</button>
                <a href="{{ route('finance.wallet.expense-categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
