@extends('institute.layout')
@section('title', isset($libraryStaff) ? 'Edit Library Staff' : 'Add Library Staff')
@section('breadcrumb', 'Library Management / Staff / ' . (isset($libraryStaff) ? 'Edit' : 'New'))
@section('content')

@php
    $isEdit   = isset($libraryStaff);
    $existing = $isEdit ? $libraryStaff : null;
    $currentPerms  = old('permissions', $existing?->permissionRecord?->permissions ?? []);
    $currentPreset = old('preset', $existing?->permissionRecord?->preset ?? 'full_librarian');
    $permGroups    = \App\Models\LibraryStaff::PERMISSION_GROUPS;
    $presetMap     = \App\Models\LibraryStaff::PRESETS;
    $presetLabels  = \App\Models\LibraryStaff::PRESET_LABELS;
@endphp

<style>
/* ── Error styles ─────────────────────────────────── */
.field-error { font-size:12px; color:#dc2626; margin-top:4px; display:flex; align-items:center; gap:4px; }
.field-error i { font-size:11px; }
.form-control.is-invalid, .form-select.is-invalid {
    border-color:#dc2626 !important;
    box-shadow:0 0 0 3px rgba(220,38,38,.12) !important;
    animation: shake .3s ease;
}
@keyframes shake {
    0%,100%{transform:translateX(0)}
    20%{transform:translateX(-4px)}
    40%{transform:translateX(4px)}
    60%{transform:translateX(-3px)}
    80%{transform:translateX(3px)}
}

/* ── Global error banner ─────────────────────────── */
.error-banner {
    background:#fef2f2; border:1px solid #fecaca; border-radius:10px;
    padding:14px 16px; margin-bottom:20px;
}
.error-banner .error-banner-title { color:#dc2626; font-weight:600; font-size:14px; display:flex; align-items:center; gap:6px; }
.error-banner .error-list { margin:8px 0 0; padding-left:18px; }
.error-banner .error-list li { font-size:13px; color:#b91c1c; margin-bottom:2px; }

/* ── Permission matrix ───────────────────────────── */
.perm-group-card { border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; }
.perm-group-header { background:#f8fafc; padding:10px 14px; font-size:13px; font-weight:600; color:#374151; border-bottom:1px solid #e2e8f0; }
.perm-item { display:flex; align-items:center; gap:10px; padding:9px 14px; border-bottom:1px solid #f1f5f9; cursor:pointer; transition:background .12s; }
.perm-item:last-child { border-bottom:none; }
.perm-item:hover { background:#f8fafc; }
.perm-item input[type=checkbox] { width:16px; height:16px; cursor:pointer; accent-color:#0ea5e9; }
.perm-item label { cursor:pointer; font-size:13px; color:#374151; margin:0; }

/* ── Preset pills ────────────────────────────────── */
.preset-pill {
    display:inline-flex; align-items:center; gap:6px; padding:7px 14px;
    border-radius:20px; font-size:13px; font-weight:500; cursor:pointer;
    border:2px solid #e2e8f0; color:#64748b; background:#fff;
    transition:all .15s ease; user-select:none;
}
.preset-pill:hover { border-color:#0ea5e9; color:#0ea5e9; }
.preset-pill.active { border-color:#0ea5e9; color:#0ea5e9; background:#f0f9ff; }
.preset-pill input { display:none; }
</style>

{{-- Global error banner --}}
@if($errors->any() || session('error'))
<div class="error-banner">
    <div class="error-banner-title">
        <i class="bi bi-exclamation-octagon-fill"></i>
        Please fix the errors below before saving.
    </div>
    @if(session('error'))
        <p class="mb-0 mt-2" style="font-size:13px;color:#b91c1c;">{{ session('error') }}</p>
    @endif
    @if($errors->any())
        <ul class="error-list mb-0">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    @endif
</div>
@endif

@if($isEdit)
<form method="POST" action="{{ route('library.staff.update', $libraryStaff) }}"
      id="staffForm" enctype="multipart/form-data">
    @method('PUT')
@else
<form method="POST" action="{{ route('library.staff.store') }}"
      id="staffForm" enctype="multipart/form-data">
@endif
@csrf

<div class="row g-4">

    {{-- ══ LEFT: Basic Info ══ --}}
    <div class="col-lg-5 col-xl-4">
        <div class="card border-0 shadow-sm" style="position:sticky;top:68px;">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-person-workspace me-2 text-primary"></i>
                    {{ $isEdit ? 'Edit: '.$libraryStaff->name : 'New Library Staff Member' }}
                </h6>
                @if($isEdit)
                <small class="text-muted">{{ $libraryStaff->employee_id }}</small>
                @endif
            </div>
            <div class="card-body p-3">

                {{-- Photo upload --}}
                <div class="mb-3 text-center">
                    @php
                        $photoUrl = $existing?->photo ? \Illuminate\Support\Facades\Storage::disk('public')->url($existing->photo) : null;
                    @endphp
                    <div class="position-relative d-inline-block mb-2">
                        <div id="photoPreview" class="rounded-circle overflow-hidden border border-2 border-light shadow-sm mx-auto"
                             style="width:72px;height:72px;background:#f0f9ff;display:flex;align-items:center;justify-content:center;">
                            @if($photoUrl)
                                <img src="{{ $photoUrl }}" alt="Photo" id="photoImg"
                                     style="width:100%;height:100%;object-fit:cover;">
                            @else
                                <span id="photoInitial"
                                      style="font-size:26px;font-weight:700;color:#0ea5e9;">
                                    {{ strtoupper(substr($existing?->name ?? '?', 0, 1)) }}
                                </span>
                            @endif
                        </div>
                        <label for="photoInput" class="position-absolute bottom-0 end-0 mb-0"
                               style="cursor:pointer;">
                            <span class="badge rounded-circle bg-primary p-1"
                                  style="width:22px;height:22px;display:flex;align-items:center;justify-content:center;">
                                <i class="bi bi-camera-fill" style="font-size:10px;"></i>
                            </span>
                        </label>
                    </div>
                    <input type="file" id="photoInput" name="photo" accept="image/*"
                           class="d-none @error('photo') is-invalid @enderror"
                           onchange="previewPhoto(this)">
                    @if($isEdit && $existing?->photo)
                        <div class="mt-1">
                            <label class="form-check-label small text-danger" style="cursor:pointer;">
                                <input type="checkbox" name="remove_photo" value="1" class="form-check-input me-1">
                                Remove current photo
                            </label>
                        </div>
                    @endif
                    @error('photo')
                        <div class="field-error justify-content-center mt-1">
                            <i class="bi bi-exclamation-circle"></i>{{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                           value="{{ old('name', $existing?->name) }}"
                           class="form-control form-control-sm @error('name') is-invalid @enderror"
                           placeholder="Enter full name">
                    @error('name')
                        <div class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email"
                           value="{{ old('email', $existing?->email) }}"
                           class="form-control form-control-sm @error('email') is-invalid @enderror"
                           placeholder="staff@email.com">
                    @error('email')
                        <div class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Mobile Number <span class="text-danger">*</span>
                        <span class="text-muted fw-normal">(used for portal login)</span>
                    </label>
                    <input type="text" name="phone"
                           value="{{ old('phone', $existing?->phone) }}"
                           class="form-control form-control-sm @error('phone') is-invalid @enderror"
                           placeholder="+91 99999 00000">
                    @error('phone')
                        <div class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Gender</label>
                        <select name="gender" class="form-select form-select-sm @error('gender') is-invalid @enderror">
                            <option value="">Select</option>
                            <option value="male"   {{ old('gender', $existing?->gender) === 'male'   ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $existing?->gender) === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other"  {{ old('gender', $existing?->gender) === 'other'  ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('gender')
                            <div class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Date of Birth</label>
                        <input type="date" name="date_of_birth"
                               value="{{ old('date_of_birth', $existing?->date_of_birth?->format('Y-m-d')) }}"
                               class="form-control form-control-sm @error('date_of_birth') is-invalid @enderror"
                               max="{{ date('Y-m-d') }}">
                        @error('date_of_birth')
                            <div class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Address</label>
                    <textarea name="address" rows="2"
                              class="form-control form-control-sm @error('address') is-invalid @enderror"
                              placeholder="Full address">{{ old('address', $existing?->address) }}</textarea>
                    @error('address')
                        <div class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Designation <span class="text-danger">*</span></label>
                    <select name="designation" class="form-select form-select-sm @error('designation') is-invalid @enderror">
                        <option value="">Select Designation</option>
                        @foreach(\App\Models\LibraryStaff::DESIGNATION_LABELS as $val => $label)
                            <option value="{{ $val }}" {{ old('designation', $existing?->designation) === $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('designation')
                        <div class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Joining Date</label>
                        <input type="date" name="joining_date"
                               value="{{ old('joining_date', $existing?->joining_date?->format('Y-m-d')) }}"
                               class="form-control form-control-sm @error('joining_date') is-invalid @enderror">
                        @error('joining_date')
                            <div class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Shift <span class="text-danger">*</span></label>
                        <select name="shift" class="form-select form-select-sm @error('shift') is-invalid @enderror">
                            @foreach(\App\Models\LibraryStaff::SHIFT_LABELS as $val => $label)
                                <option value="{{ $val }}" {{ old('shift', $existing?->shift ?? 'morning') === $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('shift')
                            <div class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Assigned Section</label>
                    <input type="text" name="assigned_section"
                           value="{{ old('assigned_section', $existing?->assigned_section) }}"
                           class="form-control form-control-sm @error('assigned_section') is-invalid @enderror"
                           placeholder="e.g. Books, Periodicals">
                    @error('assigned_section')
                        <div class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Qualification</label>
                    <input type="text" name="qualification"
                           value="{{ old('qualification', $existing?->qualification) }}"
                           class="form-control form-control-sm @error('qualification') is-invalid @enderror"
                           placeholder="e.g. BLib, MLib">
                    @error('qualification')
                        <div class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                    @enderror
                </div>

                {{-- Dual Role --}}
                <div class="mb-0">
                    <label class="form-label fw-semibold small">
                        Link to Existing Staff
                        <span class="text-muted fw-normal">(optional — for dual role)</span>
                    </label>
                    <select name="staff_member_id" class="form-select form-select-sm @error('staff_member_id') is-invalid @enderror">
                        <option value="">None (Library only)</option>
                        @foreach($staffMembers as $sm)
                            <option value="{{ $sm->id }}"
                                {{ old('staff_member_id', $existing?->staff_member_id) == $sm->id ? 'selected' : '' }}>
                                {{ $sm->name }} — {{ $sm->email }}
                            </option>
                        @endforeach
                    </select>
                    @error('staff_member_id')
                        <div class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                    @enderror
                    <div class="mt-1" style="font-size:11px;color:#64748b;">
                        <i class="bi bi-info-circle me-1"></i>
                        Dual-role members see a portal selection screen after login.
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ══ RIGHT: Permissions ══ --}}
    <div class="col-lg-7 col-xl-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-shield-check me-2 text-primary"></i>Access Permissions
                </h6>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" id="permCount">0 selected</span>
            </div>
            <div class="card-body p-3">

                {{-- Preset pills --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small d-block mb-2">Quick Preset</label>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($presetLabels as $key => $label)
                        <label class="preset-pill {{ $currentPreset === $key ? 'active' : '' }}" data-preset="{{ $key }}">
                            <input type="radio" name="preset" value="{{ $key }}"
                                   {{ $currentPreset === $key ? 'checked' : '' }}>
                            <i class="bi bi-{{ match($key) {
                                'full_librarian' => 'star-fill',
                                'attendant'      => 'person-check',
                                'data_entry'     => 'keyboard',
                                'read_only'      => 'eye',
                                default          => 'sliders'
                            } }}"></i>
                            {{ $label }}
                        </label>
                        @endforeach
                    </div>
                    @error('preset')
                        <div class="field-error mt-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                    @enderror
                </div>

                <hr class="my-3">

                {{-- Permission matrix --}}
                <div class="row g-3" id="permMatrix">
                    @foreach($permGroups as $groupName => $perms)
                    <div class="col-md-6">
                        <div class="perm-group-card">
                            <div class="perm-group-header">
                                <i class="bi bi-{{ match($groupName) {
                                    'Catalog'     => 'book',
                                    'Circulation' => 'arrow-left-right',
                                    'Members'     => 'person-vcard',
                                    default       => 'bar-chart-line'
                                } }} me-2 text-primary"></i>{{ $groupName }}
                            </div>
                            @foreach($perms as $permKey => $permLabel)
                            <div class="perm-item" onclick="togglePerm('{{ $permKey }}')">
                                <input type="checkbox" name="permissions[]"
                                       value="{{ $permKey }}"
                                       id="perm_{{ $permKey }}"
                                       {{ in_array($permKey, $currentPerms) ? 'checked' : '' }}
                                       onclick="event.stopPropagation()">
                                <label for="perm_{{ $permKey }}">{{ $permLabel }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
                @error('permissions')
                    <div class="field-error mt-2"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                @enderror

            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <a href="{{ route('library.staff.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-lg me-1"></i>
                {{ $isEdit ? 'Save Changes' : 'Create Staff Member' }}
            </button>
        </div>
    </div>

</div>
</form>

@push('scripts')
<script>
const presetMap = @json($presetMap);

// Preset pill selection
document.querySelectorAll('.preset-pill').forEach(function(pill) {
    pill.addEventListener('click', function() {
        document.querySelectorAll('.preset-pill').forEach(p => p.classList.remove('active'));
        pill.classList.add('active');
        const preset = pill.dataset.preset;
        applyPreset(preset);
    });
});

function applyPreset(preset) {
    const checkboxes = document.querySelectorAll('#permMatrix input[type=checkbox]');
    checkboxes.forEach(cb => cb.checked = false);

    if (preset !== 'custom' && presetMap[preset]) {
        presetMap[preset].forEach(function(key) {
            const cb = document.getElementById('perm_' + key);
            if (cb) cb.checked = true;
        });
    }
    updateCount();
}

function togglePerm(key) {
    const cb = document.getElementById('perm_' + key);
    if (cb) cb.checked = !cb.checked;

    // When manually changing, switch to custom
    document.querySelectorAll('.preset-pill').forEach(p => p.classList.remove('active'));
    const customPill = document.querySelector('.preset-pill[data-preset="custom"]');
    if (customPill) {
        customPill.classList.add('active');
        customPill.querySelector('input').checked = true;
    }
    updateCount();
}

function updateCount() {
    const checked = document.querySelectorAll('#permMatrix input[type=checkbox]:checked').length;
    document.getElementById('permCount').textContent = checked + ' selected';
}

// Init count
document.addEventListener('DOMContentLoaded', function() {
    updateCount();

    document.querySelectorAll('#permMatrix input[type=checkbox]').forEach(function(cb) {
        cb.addEventListener('change', updateCount);
    });
});

// Photo preview
function previewPhoto(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('photoPreview');
        preview.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;">';
    };
    reader.readAsDataURL(input.files[0]);
}

// Update initial letter when name changes
document.querySelector('input[name="name"]')?.addEventListener('input', function() {
    const initial = document.getElementById('photoInitial');
    if (initial) initial.textContent = (this.value[0] || '?').toUpperCase();
});
</script>
@endpush
@endsection
