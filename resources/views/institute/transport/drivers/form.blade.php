@php($driver = $driver ?? null)
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input class="form-control @error('name') is-invalid @enderror" name="name"
            value="{{ old('name', $driver->name ?? '') }}" required maxlength="120">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Mobile</label>
        <input class="form-control @error('mobile') is-invalid @enderror" name="mobile"
            value="{{ old('mobile', $driver->mobile ?? '') }}" maxlength="10" inputmode="numeric"
            pattern="\d{10}" placeholder="10-digit number">
        @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">License No</label>
        <input class="form-control @error('license_no') is-invalid @enderror" name="license_no"
            value="{{ old('license_no', $driver->license_no ?? '') }}" maxlength="80">
        @error('license_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">License Expiry</label>
        <input type="date" class="form-control @error('license_expiry') is-invalid @enderror" name="license_expiry"
            value="{{ old('license_expiry', optional($driver?->license_expiry)->format('Y-m-d')) }}">
        @error('license_expiry')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Helper Name</label>
        <input class="form-control @error('helper_name') is-invalid @enderror" name="helper_name"
            value="{{ old('helper_name', $driver->helper_name ?? '') }}" maxlength="120">
        @error('helper_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Helper Mobile</label>
        <input class="form-control @error('helper_mobile') is-invalid @enderror" name="helper_mobile"
            value="{{ old('helper_mobile', $driver->helper_mobile ?? '') }}" maxlength="10" inputmode="numeric"
            pattern="\d{10}" placeholder="10-digit number">
        @error('helper_mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check mb-1">
            <input class="form-check-input" type="checkbox" name="status" value="1"
                {{ old('status', $driver->status ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Active</label>
        </div>
    </div>

    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="3">{{ old('notes', $driver->notes ?? '') }}</textarea>
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
<div class="mt-4 d-flex justify-content-end">
    <button class="btn btn-primary">Save Driver</button>
</div>
