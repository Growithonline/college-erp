@extends('institute.layout')
@section('title', isset($incomeCategory) ? 'Edit Income Category' : 'New Income Category')
@section('breadcrumb', 'Finance / Wallet / Income Categories / ' . (isset($incomeCategory) ? 'Edit' : 'New'))

@section('content')
<div class="mb-4">
    <h4 class="mb-0 fw-bold">
        <i class="bi bi-tag me-2 text-info"></i>
        {{ isset($incomeCategory) ? 'Edit Category' : 'New Income Category' }}
    </h4>
</div>

<div class="card border-0 shadow-sm" style="max-width: 500px">
    <div class="card-body p-4">
        <form method="POST" action="{{ isset($incomeCategory)
            ? route('finance.wallet.income-categories.update', $incomeCategory)
            : route('finance.wallet.income-categories.store') }}">
            @csrf
            @if(isset($incomeCategory)) @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $incomeCategory->name ?? '') }}"
                       placeholder="e.g. Marksheet Fee, CSIR Grant, Scholarship">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <input type="text" name="description" class="form-control"
                       value="{{ old('description', $incomeCategory->description ?? '') }}"
                       placeholder="Optional short description">
            </div>

            @if(isset($incomeCategory))
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                           {{ old('is_active', $incomeCategory->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="isActive">Active</label>
                </div>
            </div>
            @endif

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    {{ isset($incomeCategory) ? 'Update' : 'Create' }}
                </button>
                <a href="{{ route('finance.wallet.income-categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
