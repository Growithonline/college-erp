@extends('super_admin.layout')
@section('title', 'Edit ' . $institute->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('super_admin.institutes.show', $institute->id) }}" class="text-decoration-none">{{ $institute->name }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')

<form method="POST" action="{{ route('super_admin.institutes.update', $institute->id) }}" enctype="multipart/form-data">
@csrf
@method('PUT')

{{-- Institute Info --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3 pb-0">
        <h6 class="fw-bold mb-0"><i class="bi bi-building text-primary me-2"></i>Institute Information</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label fw-semibold small">Institute Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required value="{{ old('name', $institute->name) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">
                    Short Name
                    <span class="text-muted d-block" style="font-size:10px;font-weight:400;">Cannot be changed — used in Login IDs</span>
                </label>
                <input type="text" class="form-control" value="{{ $institute->short_name }}" disabled readonly>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Mobile <span class="text-danger">*</span></label>
                <input type="text" name="mobile" class="form-control" required value="{{ old('mobile', $institute->mobile) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" required value="{{ old('email', $institute->email) }}">
            </div>
            <div class="col-md-12">
                <label class="form-label fw-semibold small">Address</label>
                <textarea name="address" class="form-control" rows="2">{{ old('address', $institute->address) }}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small">City</label>
                <input type="text" name="city" class="form-control" value="{{ old('city', $institute->city) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small">State</label>
                <input type="text" name="state" class="form-control" value="{{ old('state', $institute->state) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Pincode</label>
                <input type="text" name="pincode" class="form-control" value="{{ old('pincode', $institute->pincode) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Student Limit</label>
                <input type="number" name="student_limit" class="form-control" value="{{ old('student_limit', $institute->student_limit) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Institute Logo</label>
                @if($institute->image)
                    <div class="mb-1"><img src="{{ asset('storage/' . $institute->image) }}" style="height:36px;object-fit:contain;"></div>
                @endif
                <input type="file" name="image" class="form-control form-control-sm" accept="image/*">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Brand Color</label>
                <input type="color" name="primary_color" class="form-control form-control-sm form-control-color" value="{{ old('primary_color', $institute->primary_color ?? '#2563EB') }}">
            </div>
        </div>
    </div>
</div>

{{-- Owner Info --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3 pb-0">
        <h6 class="fw-bold mb-0"><i class="bi bi-person text-success me-2"></i>Owner / Admin Details</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Owner Name <span class="text-danger">*</span></label>
                <input type="text" name="owner_name" class="form-control" required value="{{ old('owner_name', $institute->owner_name) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Owner Email <span class="text-danger">*</span></label>
                <input type="email" name="owner_email" class="form-control" required value="{{ old('owner_email', $institute->owner_email) }}">
                <small class="text-muted">This is the institute-owner's login ID — changing it changes their login too.</small>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Owner Mobile <span class="text-danger">*</span></label>
                <input type="text" name="owner_mobile" class="form-control" required value="{{ old('owner_mobile', $institute->owner_mobile) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small">WhatsApp</label>
                <input type="text" name="owner_whatsapp" class="form-control" value="{{ old('owner_whatsapp', $institute->owner_whatsapp) }}">
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold small">Owner Address</label>
                <input type="text" name="owner_address" class="form-control" value="{{ old('owner_address', $institute->owner_address) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Identity Proof</label>
                @if($institute->owner_identity_proof)
                    <div class="mb-1"><a href="{{ asset('storage/' . $institute->owner_identity_proof) }}" target="_blank" class="small">View current file</a></div>
                @endif
                <input type="file" name="owner_identity_proof" class="form-control form-control-sm">
            </div>
        </div>
    </div>
</div>

{{-- Subscription --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3 pb-0">
        <h6 class="fw-bold mb-0"><i class="bi bi-calendar-check text-warning me-2"></i>Subscription</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Start Date</label>
                <input type="date" name="subscription_start" class="form-control"
                       value="{{ old('subscription_start', optional($institute->subscription_start)->format('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small">End Date</label>
                <input type="date" name="subscription_end" class="form-control"
                       value="{{ old('subscription_end', optional($institute->subscription_end)->format('Y-m-d')) }}">
                <small class="text-muted">Leave blank for lifetime access.</small>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg me-1"></i> Save Changes
    </button>
    <a href="{{ route('super_admin.institutes.show', $institute->id) }}" class="btn btn-outline-secondary">Cancel</a>
</div>

</form>

@endsection
