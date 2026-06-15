@extends('institute.layout')
@section('title', 'Certificate Settings')
@section('breadcrumb', 'Certificates / Settings')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-gear me-2 text-primary"></i>Certificate Settings</h4>
        <small class="text-muted">Logo, seal, signature aur theme ek baar setup karo — sab certificates mein automatically lagega</small>
    </div>
    <a href="{{ route('certificate.types.index') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-list-ul me-1"></i> Certificate Types
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="POST" action="{{ route('certificate.settings.update') }}" enctype="multipart/form-data">
@csrf
@method('PUT')

{{-- Header Lines --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-building me-2 text-primary"></i>Institute Header</h6>
        <small class="text-muted">Certificate ke upar jo text print hoga</small>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-12">
                <label class="form-label fw-semibold">Header Line 1 <span class="text-danger">*</span></label>
                <input type="text" name="header_line1" class="form-control" value="{{ old('header_line1', $settings->header_line1) }}" placeholder="e.g. Shri Govind Higher Secondary School">
                <div class="form-text">Aapke institute ka pura naam</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Header Line 2</label>
                <input type="text" name="header_line2" class="form-control" value="{{ old('header_line2', $settings->header_line2) }}" placeholder="e.g. Affiliated to XYZ University">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Header Line 3</label>
                <input type="text" name="header_line3" class="form-control" value="{{ old('header_line3', $settings->header_line3) }}" placeholder="e.g. Accredited by NAAC | Est. 1985">
            </div>
        </div>
    </div>
</div>

{{-- Signatories --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-pen me-2 text-primary"></i>Signatories</h6>
        <small class="text-muted">Certificate ke neeche signature aur naam</small>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="p-3 border rounded bg-light">
                    <h6 class="fw-semibold mb-3">Principal / Head</h6>
                    <div class="mb-3">
                        <label class="form-label">Naam</label>
                        <input type="text" name="principal_name" class="form-control" value="{{ old('principal_name', $settings->principal_name) }}" placeholder="e.g. Dr. Ramesh Kumar">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Designation</label>
                        <input type="text" name="principal_designation" class="form-control" value="{{ old('principal_designation', $settings->principal_designation) }}" placeholder="Principal">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Signature Image <span class="text-muted">(PNG/JPG, max 512KB)</span></label>
                        @if($settings->principal_signature)
                            <div class="mb-2 d-flex align-items-center gap-3">
                                <img src="{{ Storage::url($settings->principal_signature) }}" alt="Signature" style="max-height:60px; border:1px solid #dee2e6; padding:4px; background:#fff;">
                                <a href="{{ route('certificate.settings.remove-image', 'principal_signature') }}"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Signature remove karna chahte hain?')">
                                   <i class="bi bi-trash"></i> Remove
                                </a>
                            </div>
                        @endif
                        <input type="file" name="principal_signature" class="form-control" accept="image/*">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 border rounded bg-light">
                    <h6 class="fw-semibold mb-3">Registrar / Controller</h6>
                    <div class="mb-3">
                        <label class="form-label">Naam</label>
                        <input type="text" name="registrar_name" class="form-control" value="{{ old('registrar_name', $settings->registrar_name) }}" placeholder="e.g. Smt. Priya Sharma">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Designation</label>
                        <input type="text" name="registrar_designation" class="form-control" value="{{ old('registrar_designation', $settings->registrar_designation) }}" placeholder="Registrar">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Signature Image <span class="text-muted">(PNG/JPG, max 512KB)</span></label>
                        @if($settings->registrar_signature)
                            <div class="mb-2 d-flex align-items-center gap-3">
                                <img src="{{ Storage::url($settings->registrar_signature) }}" alt="Signature" style="max-height:60px; border:1px solid #dee2e6; padding:4px; background:#fff;">
                                <a href="{{ route('certificate.settings.remove-image', 'registrar_signature') }}"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Signature remove karna chahte hain?')">
                                   <i class="bi bi-trash"></i> Remove
                                </a>
                            </div>
                        @endif
                        <input type="file" name="registrar_signature" class="form-control" accept="image/*">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Logo & Seal --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-shield-fill me-2 text-primary"></i>Logo & Seal</h6>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Institute Logo <span class="text-muted">(PNG/JPG, max 1MB)</span></label>
                @if($settings->logo)
                    <div class="mb-2 d-flex align-items-center gap-3">
                        <img src="{{ Storage::url($settings->logo) }}" alt="Logo" style="max-height:70px; max-width:200px; border:1px solid #dee2e6; padding:4px; background:#fff;">
                        <a href="{{ route('certificate.settings.remove-image', 'logo') }}"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Logo remove karna chahte hain?')">
                           <i class="bi bi-trash"></i> Remove
                        </a>
                    </div>
                @endif
                <input type="file" name="logo" class="form-control" accept="image/*">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Institute Seal <span class="text-muted">(PNG transparent preferred, max 512KB)</span></label>
                @if($settings->seal_image)
                    <div class="mb-2 d-flex align-items-center gap-3">
                        <img src="{{ Storage::url($settings->seal_image) }}" alt="Seal" style="max-height:70px; max-width:200px; border:1px solid #dee2e6; padding:4px; background:#fff;">
                        <a href="{{ route('certificate.settings.remove-image', 'seal_image') }}"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Seal remove karna chahte hain?')">
                           <i class="bi bi-trash"></i> Remove
                        </a>
                    </div>
                @endif
                <input type="file" name="seal_image" class="form-control" accept="image/*">
            </div>
        </div>
    </div>
</div>

{{-- Theme & Color --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-palette me-2 text-primary"></i>Theme & Color</h6>
        <small class="text-muted">Certificate ka visual design choose karo</small>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-4">
            @foreach(['classic' => ['Classic Border', 'Bordered frame with centered header — traditional look'], 'colored' => ['Colored Header', 'Bold color header band — modern professional look'], 'minimal' => ['Minimal Clean', 'Clean lines, minimal decoration — simple & elegant']] as $themeKey => $themeInfo)
            <div class="col-md-4">
                <label class="d-block cursor-pointer">
                    <input type="radio" name="theme" value="{{ $themeKey }}" class="d-none theme-radio"
                           {{ old('theme', $settings->theme) === $themeKey ? 'checked' : '' }}>
                    <div class="card border-2 theme-card {{ old('theme', $settings->theme) === $themeKey ? 'border-primary' : 'border-light' }} h-100"
                         style="cursor:pointer; transition: all .2s;">
                        <div class="card-body p-3 text-center">
                            @if($themeKey === 'classic')
                                <div class="mb-2" style="border:2px solid #ccc; padding:8px; font-size:11px; background:#fff;">
                                    <div style="border-bottom:1px solid #999; padding-bottom:4px; font-weight:bold;">[ Logo ] Institute Name</div>
                                    <div style="margin:6px 0; font-weight:600;">CERTIFICATE</div>
                                    <div style="font-size:10px; color:#666;">This is to certify...</div>
                                    <div style="border-top:1px solid #999; margin-top:6px; padding-top:4px; font-size:10px;">[Seal] &nbsp;&nbsp;&nbsp;&nbsp; [Sign]</div>
                                </div>
                            @elseif($themeKey === 'colored')
                                <div class="mb-2" style="border:1px solid #ccc; font-size:11px; background:#fff; overflow:hidden;">
                                    <div style="background:#1e3a5f; color:#fff; padding:6px; font-weight:bold;">[ Logo ] Institute Name</div>
                                    <div style="padding:6px;">
                                        <div style="font-weight:600; margin-bottom:4px;">CERTIFICATE</div>
                                        <div style="font-size:10px; color:#666;">This is to certify...</div>
                                        <div style="margin-top:6px; font-size:10px;">[Seal] &nbsp;&nbsp;&nbsp;&nbsp; [Sign]</div>
                                    </div>
                                </div>
                            @else
                                <div class="mb-2" style="border:1px solid #eee; padding:8px; font-size:11px; background:#fff;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:4px; border-bottom:2px solid #1e3a5f;">
                                        <span style="font-weight:bold; font-size:12px;">Institute Name</span>
                                        <span>[Seal]</span>
                                    </div>
                                    <div style="text-align:center; margin:6px 0; font-weight:600;">CERTIFICATE</div>
                                    <div style="font-size:10px; color:#666;">This is to certify...</div>
                                    <div style="text-align:right; margin-top:6px; font-size:10px;">[Sign]</div>
                                </div>
                            @endif
                            <div class="fw-semibold small">{{ $themeInfo[0] }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ $themeInfo[1] }}</div>
                        </div>
                    </div>
                </label>
            </div>
            @endforeach
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Primary Color</label>
                <div class="input-group">
                    <input type="color" name="primary_color" class="form-control form-control-color" value="{{ old('primary_color', $settings->primary_color) }}" style="width:60px; padding:2px 4px;">
                    <input type="text" id="colorHex" class="form-control" value="{{ old('primary_color', $settings->primary_color) }}" placeholder="#1e3a5f" readonly>
                </div>
                <div class="form-text">Header, border aur accent color</div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg me-1"></i> Save Settings
    </button>
    <a href="{{ route('institute.dashboard') }}" class="btn btn-outline-secondary px-4">Back</a>
</div>

</form>

<script>
document.querySelectorAll('.theme-radio').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.theme-card').forEach(c => {
            c.classList.remove('border-primary');
            c.classList.add('border-light');
        });
        radio.closest('label').querySelector('.theme-card').classList.add('border-primary');
        radio.closest('label').querySelector('.theme-card').classList.remove('border-light');
    });
});

document.querySelector('input[type="color"]').addEventListener('input', function() {
    document.getElementById('colorHex').value = this.value;
});
</script>

@endsection
