@extends('group_admin.layout')
@section('title', 'Add Institute')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('group_admin.institutes.index') }}" class="text-decoration-none">Institutes</a></li>
    <li class="breadcrumb-item active">Add Institute</li>
@endsection

@section('content')

<div class="alert alert-info small">
    @if($remaining === null)
        Unlimited institute slots remaining in your group.
    @else
        <strong>{{ $remaining }}</strong> of <strong>{{ $group->institute_quota }}</strong> institute slot(s) remaining in your group.
    @endif
    Every institute you create gets a student limit of <strong>{{ $group->per_institute_student_limit }}</strong>
    and
    @if($group->institute_subscription_type === 'lifetime')
        <strong>lifetime access</strong>
    @elseif($group->institute_subscription_type === 'fixed')
        access until <strong>{{ $group->institute_subscription_end?->format('d M Y') }}</strong>
    @else
        <strong class="text-danger">no subscription policy set yet</strong>
    @endif
    (set by the platform administrator).
</div>

@if($remaining === 0)
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Your group has reached its institute quota. Contact the platform administrator to increase it before adding another institute.
</div>
@else

<form method="POST" action="{{ route('group_admin.institutes.store') }}" enctype="multipart/form-data">
@csrf

{{-- Institute Info --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3 pb-0">
        <h6 class="fw-bold mb-0"><i class="bi bi-building text-primary me-2"></i>Institute Information</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label fw-semibold small">Institute Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required value="{{ old('name') }}"
                       oninput="autoShortName(this.value)">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">
                    Short Name <span class="text-danger">*</span>
                    <span class="text-muted d-block" style="font-size:10px;font-weight:400;">Student ID prefix</span>
                </label>
                <input type="text" name="short_name" id="shortNameField"
                       class="form-control" required maxlength="10"
                       placeholder="e.g. BBA"
                       style="text-transform:uppercase"
                       value="{{ old('short_name') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Mobile <span class="text-danger">*</span></label>
                <input type="text" name="mobile" class="form-control" required value="{{ old('mobile') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" required value="{{ old('email') }}">
            </div>
            <div class="col-md-12">
                <label class="form-label fw-semibold small">Address</label>
                <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small">City</label>
                <input type="text" name="city" class="form-control" value="{{ old('city') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small">State</label>
                <input type="text" name="state" class="form-control" value="{{ old('state') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Pincode</label>
                <input type="text" name="pincode" class="form-control" value="{{ old('pincode') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Student Limit</label>
                <input type="text" class="form-control" value="{{ $group->per_institute_student_limit }}" disabled readonly>
                {{-- Fixed by the platform administrator, not editable here — actual value is re-applied server-side regardless of this field --}}
                <input type="hidden" name="student_limit" value="{{ $group->per_institute_student_limit }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Institute Logo</label>
                <input type="file" name="image" class="form-control form-control-sm" accept="image/*">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Brand Color</label>
                <input type="color" name="primary_color" class="form-control form-control-sm form-control-color" value="{{ old('primary_color', '#2563EB') }}">
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
                <input type="text" name="owner_name" class="form-control" required value="{{ old('owner_name') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Owner Email <span class="text-danger">*</span></label>
                <input type="email" name="owner_email" class="form-control" required value="{{ old('owner_email') }}">
                <small class="text-muted">Login credentials will be sent here. Can be the same email across your institutes.</small>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Owner Mobile <span class="text-danger">*</span></label>
                <input type="text" name="owner_mobile" class="form-control" required value="{{ old('owner_mobile') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small">WhatsApp</label>
                <input type="text" name="owner_whatsapp" class="form-control" value="{{ old('owner_whatsapp') }}">
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold small">Owner Address</label>
                <input type="text" name="owner_address" class="form-control" value="{{ old('owner_address') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Identity Proof</label>
                <input type="file" name="owner_identity_proof" class="form-control form-control-sm">
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg me-1"></i> Create Institute & Send Credentials
    </button>
    <a href="{{ route('group_admin.institutes.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

</form>

<script>
function autoShortName(name) {
    var field = document.getElementById('shortNameField');
    if (field.dataset.manuallyEdited === 'true') return;
    var words = name.trim().split(/\s+/).filter(w => w.length > 0);
    var short = words.length === 1 ? words[0].substring(0, 4) : words.map(w => w[0]).join('').substring(0, 8);
    field.value = short.toUpperCase();
}
document.addEventListener('DOMContentLoaded', function() {
    var field = document.getElementById('shortNameField');
    if (field) {
        field.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
            this.dataset.manuallyEdited = 'true';
        });
    }
});
</script>

@endif

@endsection
