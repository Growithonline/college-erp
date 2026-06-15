@extends('institute.layout')
@section('title', isset($vendor) ? 'Edit Vendor' : 'Add Vendor')
@section('breadcrumb', 'Finance / Wallet / Vendors / ' . (isset($vendor) ? 'Edit' : 'Add'))

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('finance.wallet.expense-categories.index') }}">Categories</a></li>
            <li class="breadcrumb-item"><a href="{{ route('finance.wallet.expense-categories.sub.index', $expenseCategory) }}">{{ $expenseCategory->name }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('finance.wallet.expense-categories.sub.vendors.index', [$expenseCategory, $sub]) }}">{{ $sub->name }}</a></li>
            <li class="breadcrumb-item active">{{ isset($vendor) ? 'Edit' : 'Add' }}</li>
        </ol>
    </nav>
    <h4 class="mb-0 fw-bold">
        <i class="bi bi-person-plus me-2 text-primary"></i>
        {{ isset($vendor) ? 'Edit Vendor' : 'Add Vendor — ' . $sub->name }}
    </h4>
</div>

<div class="card border-0 shadow-sm" style="max-width: 600px">
    <div class="card-body p-4">
        <form method="POST" action="{{ isset($vendor)
            ? route('finance.wallet.expense-categories.sub.vendors.update', [$expenseCategory, $sub, $vendor])
            : route('finance.wallet.expense-categories.sub.vendors.store', [$expenseCategory, $sub]) }}">
            @csrf
            @if(isset($vendor)) @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">Vendor Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $vendor->name ?? '') }}"
                       placeholder="e.g. UPPCL, Tata Power, ABC Stationery">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">GST No <span class="text-muted small">(optional)</span></label>
                    <input type="text" name="gst_no" class="form-control"
                           value="{{ old('gst_no', $vendor->gst_no ?? '') }}" placeholder="22AAAAA0000A1Z5">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">PAN No <span class="text-muted small">(optional)</span></label>
                    <input type="text" name="pan_no" class="form-control"
                           value="{{ old('pan_no', $vendor->pan_no ?? '') }}" placeholder="AAAAA0000A">
                </div>
            </div>

            <hr class="my-3"><p class="fw-semibold small text-muted mb-3">Contact Details (optional)</p>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Contact Person</label>
                    <input type="text" name="contact_name" class="form-control"
                           value="{{ old('contact_name', $vendor->contact_name ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Phone</label>
                    <input type="text" name="contact_phone" class="form-control"
                           value="{{ old('contact_phone', $vendor->contact_phone ?? '') }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Email</label>
                <input type="email" name="contact_email" class="form-control"
                       value="{{ old('contact_email', $vendor->contact_email ?? '') }}">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Address</label>
                <textarea name="address" class="form-control" rows="2">{{ old('address', $vendor->address ?? '') }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Notes</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $vendor->notes ?? '') }}</textarea>
            </div>

            @if(isset($vendor))
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                           {{ old('is_active', $vendor->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="isActive">Active</label>
                </div>
            </div>
            @endif

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ isset($vendor) ? 'Update' : 'Add Vendor' }}</button>
                <a href="{{ route('finance.wallet.expense-categories.sub.vendors.index', [$expenseCategory, $sub]) }}"
                   class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
