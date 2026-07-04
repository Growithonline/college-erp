@php
    $c = $center ?? null;
    $edit = $isEdit ?? false;
@endphp

{{-- Server-side global errors (edit form / non-AJAX) --}}
@if($errors->any())
<div class="alert alert-danger alert-dismissible mb-3">
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Please fix the following errors:</div>
    <ul class="mb-0 ps-3">@foreach($errors->all() as $err)<li class="small">{{ $err }}</li>@endforeach</ul>
</div>
@endif

{{-- AJAX global error (create form) --}}
<div id="centerGlobalError" class="alert alert-danger alert-dismissible mb-3 d-none">
    <button type="button" class="btn-close" onclick="document.getElementById('centerGlobalError').classList.add('d-none')"></button>
    <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Please fix the following errors:</div>
    <ul id="centerGlobalErrorList" class="mb-0 ps-3"></ul>
</div>

<div class="row g-4">

    {{-- ── Left: sticky info + save ───────────────────────────────────── --}}
    <div class="col-md-4 col-xl-3">
        <div style="position:sticky;top:68px;" class="d-flex flex-column gap-3">

            {{-- Basic Info Card --}}
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <h6 class="fw-semibold small text-muted text-uppercase mb-3">
                        <i class="bi bi-building me-1"></i>Center Info
                    </h6>

                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Center Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $c->name ?? '') }}"
                               class="form-control form-control-sm @error('name') is-invalid @enderror"
                               placeholder="e.g. BMC STUDY CENTER"
                               style="text-transform:uppercase" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-7">
                            <label class="form-label fw-semibold small">Mobile</label>
                            <input type="text" name="mobile" value="{{ old('mobile', $c->mobile ?? '') }}"
                                   class="form-control form-control-sm @error('mobile') is-invalid @enderror"
                                   maxlength="10" inputmode="numeric" pattern="[0-9]{10}"
                                   placeholder="10-digit number">
                            @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold small">Code</label>
                            <input type="text" name="code" value="{{ old('code', $c->code ?? '') }}"
                                   class="form-control form-control-sm" placeholder="BMC"
                                   style="text-transform:uppercase" maxlength="20">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $c->email ?? '') }}"
                               class="form-control form-control-sm @error('email') is-invalid @enderror"
                               {{ $edit ? 'readonly' : '' }}>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if($edit) <small class="text-muted">Email cannot be changed</small> @endif
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Address</label>
                        <input type="text" name="address" value="{{ old('address', $c->address ?? '') }}"
                               class="form-control form-control-sm" style="text-transform:uppercase">
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">City</label>
                            <input type="text" name="city" value="{{ old('city', $c->city ?? '') }}"
                                   class="form-control form-control-sm" style="text-transform:uppercase">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">State</label>
                            <select name="state" id="centerStateSelect" class="form-select form-select-sm"
                                    data-saved="{{ old('state', $c->state ?? '') }}">
                                <option value="">— Select State —</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Password Reset (edit only) --}}
            @if($edit)
            <div class="card border-0 bg-light">
                <div class="card-body py-3">
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="reset_password"
                               id="resetPwd" value="1">
                        <label class="form-check-label fw-semibold small" for="resetPwd">
                            <i class="bi bi-key me-1 text-warning"></i>Reset Password
                        </label>
                    </div>
                    <small class="text-muted d-block ms-4">A new password will be auto-generated and emailed.</small>
                </div>
            </div>
            @else
            <div class="alert alert-info py-2 small mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Login credentials will be auto-generated and emailed on save.
            </div>
            @endif

            {{-- Save / Cancel --}}
            <div class="d-grid gap-2">
                <button type="submit" id="centerSubmitBtn" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>
                    {{ $edit ? 'Update Center' : 'Save Center' }}
                </button>
                <a href="{{ route('master.centers.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </div>
    </div>

    {{-- ── Right: permissions tabs ─────────────────────────────────────── --}}
    <div class="col-md-8 col-xl-9">

        {{-- Tab nav --}}
        <ul class="nav nav-tabs mb-3" id="centerPermTabs">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-features" type="button">
                    <i class="bi bi-toggles me-1"></i>Features
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-admission" type="button">
                    <i class="bi bi-person-plus me-1"></i>Admission
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-fee" type="button">
                    <i class="bi bi-cash-stack me-1"></i>Fee
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-scope" type="button">
                    <i class="bi bi-funnel me-1"></i>Access Scope
                </button>
            </li>
        </ul>

        <div class="tab-content">

            {{-- ── Tab 1: Feature flags ──────────────────────────────── --}}
            <div class="tab-pane fade show active" id="tab-features">
                <div class="row g-3">

                    <div class="col-md-4">
                        <div class="card border-0 h-100" style="background:#eff6ff;border:1px solid #bfdbfe!important;">
                            <div class="card-body">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="can_add_admission"
                                           id="canAdmission" value="1"
                                           {{ old('can_add_admission', $c->can_add_admission ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="canAdmission">
                                        <i class="bi bi-person-plus me-1 text-primary"></i>Add Admission
                                    </label>
                                </div>
                                <small class="text-muted">Can fill and submit student admission forms</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border-0 h-100" style="background:#f0fdf4;border:1px solid #bbf7d0!important;">
                            <div class="card-body">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="can_view_students"
                                           id="canView" value="1"
                                           {{ old('can_view_students', $c->can_view_students ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="canView">
                                        <i class="bi bi-eye me-1 text-success"></i>View Students
                                    </label>
                                </div>
                                <small class="text-muted">Can view the student list and profiles</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border-0 h-100" style="background:#fffbeb;border:1px solid #fde68a!important;">
                            <div class="card-body">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="can_collect_fee"
                                           id="canFee" value="1"
                                           {{ old('can_collect_fee', $c->can_collect_fee ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="canFee">
                                        <i class="bi bi-cash me-1 text-warning"></i>Collect Fee
                                    </label>
                                </div>
                                <small class="text-muted">Can collect fees and generate receipts</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border-0 h-100" style="background:#f5f3ff;border:1px solid #ddd6fe!important;">
                            <div class="card-body">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="can_download_reports"
                                           id="canReports" value="1"
                                           {{ old('can_download_reports', $c->can_download_reports ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="canReports">
                                        <i class="bi bi-file-earmark-arrow-down me-1 text-purple"></i>Download Reports
                                    </label>
                                </div>
                                <small class="text-muted">Can download admission and fee reports</small>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ── Tab 2: Admission controls ─────────────────────────── --}}
            <div class="tab-pane fade" id="tab-admission">

                {{-- Form type --}}
                <div class="card border-0 bg-light mb-3">
                    <div class="card-body">
                        <label class="form-label fw-semibold">Admission Form Type</label>
                        <div class="d-flex gap-3 flex-wrap">
                            @foreach(['both' => 'Both Forms', 'quick' => 'Quick Form Only', 'full' => 'Full Form Only'] as $val => $lbl)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="admission_form_type"
                                       id="fmt_{{ $val }}" value="{{ $val }}"
                                       {{ old('admission_form_type', $c->admission_form_type ?? 'both') === $val ? 'checked' : '' }}>
                                <label class="form-check-label" for="fmt_{{ $val }}">{{ $lbl }}</label>
                            </div>
                            @endforeach
                        </div>
                        <small class="text-muted">Controls which admission form this center can access</small>
                    </div>
                </div>

                {{-- Document Upload Settings --}}
                @include('institute.master._doc-upload-settings', [
                    'model'       => $c,
                    'idPrefix'    => 'c',
                    'formTypeField' => 'admission_form_type',
                ])

                {{-- Allowed Courses --}}
                <div class="card border-0 bg-light mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">Allowed Courses</label>
                            <span class="badge bg-secondary-subtle text-secondary border">
                                Leave empty = All courses
                            </span>
                        </div>
                        @if($courses->isEmpty())
                            <small class="text-muted fst-italic">No courses found for this institute.</small>
                        @else
                        @if($courseTypes->isNotEmpty())
                        <div class="mb-2 d-flex flex-wrap gap-1" id="courseTypeFilters">
                            <button type="button" class="btn btn-sm btn-primary course-type-btn active" data-type-id="">All</button>
                            @foreach($courseTypes as $ct)
                            <button type="button" class="btn btn-sm btn-outline-primary course-type-btn" data-type-id="{{ $ct->id }}">{{ $ct->name }}</button>
                            @endforeach
                        </div>
                        @endif
                        <div class="row g-2" id="courseCheckboxes">
                            @foreach($courses as $course)
                            <div class="col-md-4 col-sm-6 course-item" data-type-id="{{ $course->course_type_id }}">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           name="allowed_courses[]" value="{{ $course->id }}"
                                           id="course_{{ $course->id }}"
                                           {{ in_array($course->id, old('allowed_courses', $c->allowed_courses ?? [])) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="course_{{ $course->id }}">
                                        {{ $course->name }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="mt-2 d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="centerSelectVisible()">Select Visible</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                    onclick="uncheckAll('course_')">Clear All</button>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Allowed Sessions (per-session permissions) --}}
                @php
                    $rawPerms = old('session_perms');
                    if ($rawPerms !== null) {
                        // Repopulate from old() after validation failure
                        $sessionPermsMap = [];
                        foreach ($rawPerms as $sid => $perms) {
                            if (!empty($perms['enabled'])) {
                                $sessionPermsMap[(int)$sid] = [
                                    'admission'     => !empty($perms['admission']),
                                    'fee'           => !empty($perms['fee']),
                                    'view'          => !empty($perms['view']),
                                    'student_scope' => ($perms['student_scope'] ?? 'own') === 'all' ? 'all' : 'own',
                                    'fee_scope'     => ($perms['fee_scope'] ?? 'own') === 'all' ? 'all' : 'own',
                                ];
                            }
                        }
                    } else {
                        $sessionPermsMap = isset($c) ? ($c->sessionPermsMap() ?? null) : null;
                    }
                @endphp
                <div class="card border-0 bg-light">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">Allowed Sessions</label>
                            <span class="badge bg-secondary-subtle text-secondary border">
                                Leave empty = All sessions unrestricted
                            </span>
                        </div>
                        @if($sessions->isEmpty())
                            <small class="text-muted fst-italic">No sessions found.</small>
                        @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-2 align-middle" style="font-size:12.5px;">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:22%;">Session</th>
                                        <th class="text-center" style="width:10%;">View</th>
                                        <th class="text-center" style="width:10%;">Admission</th>
                                        <th class="text-center" style="width:10%;">Fee</th>
                                        <th class="text-center" style="width:24%;">Student Scope</th>
                                        <th class="text-center" style="width:24%;">Fee Scope</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sessions as $session)
                                    @php
                                        $enabled       = $sessionPermsMap !== null && isset($sessionPermsMap[$session->id]);
                                        $viewChk       = $enabled && ($sessionPermsMap[$session->id]['view'] ?? false);
                                        $admitChk      = $enabled && ($sessionPermsMap[$session->id]['admission'] ?? false);
                                        $feeChk        = $enabled && ($sessionPermsMap[$session->id]['fee'] ?? false);
                                        $stuScope      = $enabled ? ($sessionPermsMap[$session->id]['student_scope'] ?? 'own') : 'own';
                                        $feeScope      = $enabled ? ($sessionPermsMap[$session->id]['fee_scope'] ?? 'own') : 'own';
                                    @endphp
                                    <tr id="sess_row_{{ $session->id }}">
                                        <td>
                                            <div class="form-check mb-0">
                                                <input class="form-check-input" type="checkbox"
                                                       name="session_perms[{{ $session->id }}][enabled]"
                                                       value="1"
                                                       id="sess_{{ $session->id }}"
                                                       {{ $enabled ? 'checked' : '' }}
                                                       onchange="toggleCenterSessionRow(this, {{ $session->id }})">
                                                <label class="form-check-label fw-semibold" for="sess_{{ $session->id }}">
                                                    {{ $session->name }}
                                                    @if($session->is_active)
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size:9px;">Active</span>
                                                    @endif
                                                </label>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <input class="form-check-input" type="checkbox"
                                                   name="session_perms[{{ $session->id }}][view]"
                                                   value="1"
                                                   id="sess_{{ $session->id }}_view"
                                                   {{ $viewChk ? 'checked' : '' }}
                                                   {{ !$enabled ? 'disabled' : '' }}>
                                        </td>
                                        <td class="text-center">
                                            <input class="form-check-input" type="checkbox"
                                                   name="session_perms[{{ $session->id }}][admission]"
                                                   value="1"
                                                   id="sess_{{ $session->id }}_adm"
                                                   {{ $admitChk ? 'checked' : '' }}
                                                   {{ !$enabled ? 'disabled' : '' }}>
                                        </td>
                                        <td class="text-center">
                                            <input class="form-check-input" type="checkbox"
                                                   name="session_perms[{{ $session->id }}][fee]"
                                                   value="1"
                                                   id="sess_{{ $session->id }}_fee"
                                                   {{ $feeChk ? 'checked' : '' }}
                                                   {{ !$enabled ? 'disabled' : '' }}>
                                        </td>
                                        <td>
                                            <select name="session_perms[{{ $session->id }}][student_scope]"
                                                    id="sess_{{ $session->id }}_stu_scope"
                                                    class="form-select form-select-sm"
                                                    {{ !$enabled ? 'disabled' : '' }}>
                                                <option value="own" {{ $stuScope === 'own' ? 'selected' : '' }}>Own Only</option>
                                                <option value="all" {{ $stuScope === 'all' ? 'selected' : '' }}>All Students</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="session_perms[{{ $session->id }}][fee_scope]"
                                                    id="sess_{{ $session->id }}_fee_scope"
                                                    class="form-select form-select-sm"
                                                    {{ !$enabled ? 'disabled' : '' }}>
                                                <option value="own" {{ $feeScope === 'own' ? 'selected' : '' }}>Own Only</option>
                                                <option value="all" {{ $feeScope === 'all' ? 'selected' : '' }}>All Students</option>
                                            </select>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Check a session to restrict it — then choose what actions are allowed. Unchecked sessions = unrestricted.</small>
                        @endif
                    </div>
                </div>
                <script>
                function toggleCenterSessionRow(cb, sessionId) {
                    var view     = document.getElementById('sess_' + sessionId + '_view');
                    var adm      = document.getElementById('sess_' + sessionId + '_adm');
                    var fee      = document.getElementById('sess_' + sessionId + '_fee');
                    var stuScope = document.getElementById('sess_' + sessionId + '_stu_scope');
                    var feeScope = document.getElementById('sess_' + sessionId + '_fee_scope');
                    var els = [view, adm, fee, stuScope, feeScope];
                    if (cb.checked) {
                        els.forEach(function(el) { if (el) el.disabled = false; });
                    } else {
                        els.forEach(function(el) {
                            if (!el) return;
                            el.disabled = true;
                            if (el.type === 'checkbox') el.checked = false;
                        });
                    }
                }
                </script>

            </div>

            {{-- ── Tab 3: Fee controls ───────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-fee">

                {{-- Discount --}}
                <div class="card border-0 bg-light mb-3">
                    <div class="card-body">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="canDiscount"
                                   name="can_give_discount" value="1"
                                   {{ old('can_give_discount', $c->can_give_discount ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="canDiscount">
                                <i class="bi bi-percent me-1 text-success"></i>Can Give Discount
                            </label>
                        </div>
                        <div id="discountPctRow" class="{{ old('can_give_discount', $c->can_give_discount ?? false) ? '' : 'd-none' }}">
                            <label class="form-label small fw-semibold">Maximum Discount %</label>
                            <div class="input-group mb-3" style="max-width:180px;">
                                <input type="number" name="max_discount_pct" min="0" max="100" step="0.5"
                                       value="{{ old('max_discount_pct', $c->max_discount_pct ?? 0) }}"
                                       class="form-control form-control-sm">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted d-block mb-2">Center cannot exceed this discount while collecting fees</small>

                            {{-- Per-item discount permissions --}}
                            @if(isset($feeTypes) && $feeTypes->isNotEmpty())
                            @php
                                $savedDiscountIds = old('fee_discount_allowed')
                                    ? array_keys(array_filter((array) old('fee_discount_allowed')))
                                    : ($c?->feeDiscountPermissions?->pluck('fee_type_id')->toArray() ?? []);
                            @endphp
                            <div class="mt-2">
                                <label class="form-label small fw-semibold">
                                    <i class="bi bi-tag me-1 text-warning"></i>Discountable Fee Items
                                    <span class="text-muted fw-normal">(none selected = all items)</span>
                                </label>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($feeTypes as $ft)
                                    <label class="border rounded px-2 py-1 small bg-white d-flex align-items-center gap-1 cursor-pointer">
                                        <input type="checkbox" class="form-check-input"
                                               name="fee_discount_allowed[{{ $ft->id }}]" value="1"
                                               {{ in_array($ft->id, $savedDiscountIds, false) ? 'checked' : '' }}>
                                        {{ $ft->name }}
                                    </label>
                                    @endforeach
                                </div>
                                <small class="text-muted d-block mt-1">Only checked items will allow discount. Leave all unchecked to allow discount on all items.</small>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Fee type restriction --}}
                <div class="card border-0 bg-light mb-3">
                    <div class="card-body">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="restrictFeeTypes"
                                   name="restrict_fee_collection_types" value="1"
                                   {{ old('restrict_fee_collection_types', $c->restrict_fee_collection_types ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="restrictFeeTypes">
                                <i class="bi bi-tags me-1 text-warning"></i>Allow only selected fee types
                            </label>
                        </div>
                        <div id="feeTypeRestrictRow" class="{{ old('restrict_fee_collection_types', $c->restrict_fee_collection_types ?? false) ? '' : 'd-none' }}">
                            @if(isset($feeTypes) && $feeTypes->isNotEmpty())
                            @php
                                $savedCollectIds = old('fee_collection_allowed')
                                    ? array_keys(array_filter((array) old('fee_collection_allowed')))
                                    : ($c?->feeCollectionPermissions?->pluck('fee_type_id')->toArray() ?? []);
                            @endphp
                            <div class="d-flex flex-wrap gap-2 mt-1">
                                @foreach($feeTypes as $ft)
                                <label class="border rounded px-2 py-1 small bg-white d-flex align-items-center gap-1 cursor-pointer">
                                    <input type="checkbox" class="form-check-input"
                                           name="fee_collection_allowed[{{ $ft->id }}]" value="1"
                                           {{ in_array($ft->id, $savedCollectIds, false) ? 'checked' : '' }}>
                                    {{ $ft->name }}
                                </label>
                                @endforeach
                            </div>
                            <small class="text-muted d-block mt-1">The center can only collect checked fee types. All unchecked = all allowed (keep toggle OFF).</small>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

            {{-- ── Tab 4: Access Scope ───────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-scope">

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-people me-1 text-primary"></i>Student Visibility
                                </label>
                                <div class="d-flex flex-column gap-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="student_scope"
                                               id="ss_own" value="own"
                                               {{ old('student_scope', $c->student_scope ?? 'own') === 'own' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="ss_own">
                                            <span class="fw-semibold">Own Students Only</span><br>
                                            <small class="text-muted">Can only see students admitted through this center</small>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="student_scope"
                                               id="ss_all" value="all"
                                               {{ old('student_scope', $c->student_scope ?? 'own') === 'all' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="ss_all">
                                            <span class="fw-semibold">All Institute Students</span><br>
                                            <small class="text-muted">Can see all students of the institute</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-cash me-1 text-warning"></i>Fee Collection Scope
                                </label>
                                <div class="d-flex flex-column gap-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="fee_scope"
                                               id="fs_own" value="own"
                                               {{ old('fee_scope', $c->fee_scope ?? 'own') === 'own' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="fs_own">
                                            <span class="fw-semibold">Own Students Only</span><br>
                                            <small class="text-muted">Only own students appear in fee search</small>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="fee_scope"
                                               id="fs_all" value="all"
                                               {{ old('fee_scope', $c->fee_scope ?? 'own') === 'all' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="fs_all">
                                            <span class="fw-semibold">All Institute Students</span><br>
                                            <small class="text-muted">Can collect fees for any student</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>{{-- end tab-content --}}
    </div>

</div>{{-- end row --}}
