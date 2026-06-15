@php
    $selectedCourseIds = collect($allowedCourseIds ?? [])->map(fn ($id) => (int) $id)->all();
    $selectedCourseIdsFromOld = array_keys((array) old('course_permissions', []));
    if (!empty($selectedCourseIdsFromOld)) {
        $selectedCourseIds = array_map('intval', $selectedCourseIdsFromOld);
    }

    $selectedFeeCollectionIds = isset($feeCollectionPermissions)
        ? collect($feeCollectionPermissions)->map(fn ($id) => (int) $id)->all()
        : [];
    $selectedFeeCollectionIdsFromOld = array_keys((array) old('fee_collection_allowed', []));
    if (!empty($selectedFeeCollectionIdsFromOld)) {
        $selectedFeeCollectionIds = array_map('intval', $selectedFeeCollectionIdsFromOld);
    }

    $selectedPaymentModes = old('payment_modes', $paymentPermission?->allowed_modes ?? ['cash']);
    $selectedPaymentBankIds = array_map('intval', old('payment_bank_ids', $paymentPermission?->allowed_bank_ids ?? []));
    $selectedPayrollCategories = old('payroll_scope_categories', $staffMember->payroll_scope_categories ?? []);
    $selectedSessionIds = array_map('intval', old('allowed_session_ids', $staffMember->allowed_session_ids ?? []));
    $selectedStudentVisibilityScope = old('student_visibility_scope', $staffMember->student_visibility_scope ?? 'role_based');
    $permissionOverridesMap = $permissionOverrides ?? collect();
    $selectedRolePermissionMap = collect($selectedRoleForAccess?->permissions ?? []);
@endphp

<div class="card border-0 bg-light rounded-3 p-3 mb-4">
    <div class="fw-semibold small mb-3 text-dark">
        <i class="bi bi-eye me-1 text-primary"></i>Student Visibility & Session Scope
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Student / Admission Data Visibility</label>
            <select name="student_visibility_scope" class="form-select form-select-sm @error('student_visibility_scope') is-invalid @enderror">
                @foreach(($studentVisibilityOptions ?? ['role_based' => 'Use role default', 'self' => 'Own students only', 'all' => 'All accessible students']) as $scopeKey => $scopeLabel)
                    <option value="{{ $scopeKey }}" @selected($selectedStudentVisibilityScope === $scopeKey)>{{ $scopeLabel }}</option>
                @endforeach
            </select>
            @error('student_visibility_scope')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted d-block mt-2">
                Own students only = sirf is staff ke admitted students. All accessible students = allowed course/session ke sab students.
            </small>
        </div>
        <div class="col-md-6">
            <div class="form-check form-switch mt-4 pt-1">
                <input class="form-check-input" type="checkbox" name="restrict_session_access" id="restrictSessionAccess" value="1"
                       {{ old('restrict_session_access', $staffMember->restrict_session_access ?? false) ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="restrictSessionAccess">Restrict to selected academic sessions only</label>
            </div>
            <small class="text-muted d-block mt-2">
                Off rahega to staff sabhi sessions ka data dekh sakega. On rahega to niche selected sessions tak hi access milega.
            </small>
        </div>
    </div>

    @if(isset($sessions) && $sessions->isNotEmpty())
    <div class="border-top pt-3 mt-3">
        <label class="form-label small fw-semibold">Allowed Academic Sessions</label>
        <div class="d-flex flex-wrap gap-2">
            @foreach($sessions as $session)
            <label class="border rounded px-2 py-1 small bg-white">
                <input type="checkbox" class="form-check-input me-1" name="allowed_session_ids[]" value="{{ $session->id }}"
                       {{ in_array((int) $session->id, $selectedSessionIds, true) ? 'checked' : '' }}>
                {{ $session->name }}
                @if($session->is_active)
                    <span class="badge bg-success-subtle text-success border ms-1">Active</span>
                @endif
            </label>
            @endforeach
        </div>
        @error('allowed_session_ids')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
        @error('allowed_session_ids.*')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>
    @endif
</div>

<div class="card border-0 bg-light rounded-3 p-3 mb-4">
    <div class="fw-semibold small mb-3 text-dark">
        <i class="bi bi-diagram-3 me-1 text-primary"></i>Course Access Scope
    </div>
    <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" name="restrict_course_access" id="restrictCourseAccess" value="1"
               {{ old('restrict_course_access', $staffMember->restrict_course_access ?? false) ? 'checked' : '' }}>
        <label class="form-check-label fw-semibold" for="restrictCourseAccess">Restrict to selected courses only</label>
        <small class="text-muted d-block">Off rahega to staff sabhi courses ke admissions aur related student access use kar sakega.</small>
    </div>

    @if(isset($courses) && $courses->isNotEmpty())
    @if(isset($courseTypes) && $courseTypes->isNotEmpty())
    <div class="mb-2 d-flex flex-wrap gap-1" id="staffCourseTypeFilters">
        <button type="button" class="btn btn-sm btn-primary staff-ct-btn active" data-type-id="">All</button>
        @foreach($courseTypes as $ct)
        <button type="button" class="btn btn-sm btn-outline-primary staff-ct-btn" data-type-id="{{ $ct->id }}">{{ $ct->name }}</button>
        @endforeach
    </div>
    @endif
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0 align-middle" style="font-size:13px;" id="staffCourseTable">
            <thead class="table-light">
                <tr>
                    <th>Course</th>
                    <th class="text-center" style="width:140px;">Allowed?</th>
                </tr>
            </thead>
            <tbody>
                @foreach($courses as $course)
                <tr class="staff-course-row" data-type-id="{{ $course->course_type_id }}">
                    <td>{{ $course->name }}</td>
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input" name="course_permissions[{{ $course->id }}]" value="1"
                               {{ in_array((int) $course->id, $selectedCourseIds, true) ? 'checked' : '' }}>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<div class="card border-0 bg-light rounded-3 p-3 mb-4">
    <div class="fw-semibold small mb-3 text-dark">
        <i class="bi bi-people me-1 text-secondary"></i>Attendance & Payroll Scope
    </div>
    <label class="form-label small fw-semibold">Allowed Staff Categories</label>
    <div class="d-flex flex-wrap gap-2">
        @foreach(($payrollCategories ?? ['Teaching', 'Office', 'Non-Teaching', 'Guest']) as $category)
        <label class="border rounded px-2 py-1 small bg-white">
            <input type="checkbox" class="form-check-input me-1" name="payroll_scope_categories[]" value="{{ $category }}"
                   {{ in_array($category, $selectedPayrollCategories, true) ? 'checked' : '' }}>
            {{ $category }}
        </label>
        @endforeach
    </div>
    <small class="text-muted d-block mt-2">Kuch bhi select nahi karoge to payroll/attendance module me sabhi staff categories accessible rahengi.</small>
</div>


@if(!empty($permissionLabels ?? []))
<div class="card border-0 bg-light rounded-3 p-3 mb-4">
    <div class="fw-semibold small mb-3 text-dark">
        <i class="bi bi-sliders me-1 text-danger"></i>Role + Individual Overrides
    </div>
    <div class="alert alert-white border small mb-3">
        <div class="fw-semibold mb-1">How this works</div>
        <div>Role Access = selected role se aane wali default permission.</div>
        <div>Override = is specific staff ke liye extra allow/deny.</div>
        <div>Effective Access = abhi final me system kya apply karega.</div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0" style="font-size:13px;">
            <thead class="table-light">
                <tr>
                    <th>Permission</th>
                    <th style="width:120px;">Role Access</th>
                    <th style="width:160px;">Override</th>
                    <th style="width:130px;">Effective Access</th>
                    <th style="width:150px;">Valid Till</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                @foreach($permissionLabels as $permissionKey => $permissionLabel)
                @php
                    $savedOverride = $permissionOverridesMap[$permissionKey] ?? null;
                    $effect = old("permission_overrides.$permissionKey.effect", $savedOverride?->effect);
                    $expiresAt = old("permission_overrides.$permissionKey.expires_at", $savedOverride?->expires_at?->format('Y-m-d'));
                    $note = old("permission_overrides.$permissionKey.note", $savedOverride?->note);
                    $roleAllows = (bool) ($selectedRolePermissionMap[$permissionKey] ?? false);
                    $isOverrideActive = $savedOverride ? $savedOverride->isActive() : false;
                    $effectiveAllows = $effect === 'allow'
                        ? true
                        : ($effect === 'deny' ? false : $roleAllows);
                @endphp
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $permissionLabel }}</div>
                        <div class="text-muted small">{{ $permissionKey }}</div>
                    </td>
                    <td>
                        <span class="badge {{ $roleAllows ? 'bg-success-subtle text-success border' : 'bg-secondary-subtle text-secondary border' }}">
                            {{ $roleAllows ? 'Allowed' : 'Blocked' }}
                        </span>
                    </td>
                    <td>
                        <select name="permission_overrides[{{ $permissionKey }}][effect]" class="form-select form-select-sm">
                            <option value="">Role Default</option>
                            <option value="allow" @selected($effect === 'allow')>Force Allow</option>
                            <option value="deny" @selected($effect === 'deny')>Force Deny</option>
                        </select>
                    </td>
                    <td>
                        <span class="badge {{ $effectiveAllows ? 'bg-success-subtle text-success border' : 'bg-danger-subtle text-danger border' }}">
                            {{ $effectiveAllows ? 'Allowed' : 'Blocked' }}
                        </span>
                        @if($savedOverride && $isOverrideActive)
                            <div class="text-muted small mt-1">By override</div>
                        @elseif($effect)
                            <div class="text-muted small mt-1">Pending save</div>
                        @else
                            <div class="text-muted small mt-1">Role default</div>
                        @endif
                    </td>
                    <td>
                        <input type="date" name="permission_overrides[{{ $permissionKey }}][expires_at]"
                               value="{{ $expiresAt }}"
                               class="form-control form-control-sm">
                        @if($savedOverride?->expires_at)
                            <div class="text-muted small mt-1">
                                {{ $savedOverride->isActive() ? 'Active till '.$savedOverride->expires_at->format('d M Y') : 'Expired on '.$savedOverride->expires_at->format('d M Y') }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <input type="text" name="permission_overrides[{{ $permissionKey }}][note]"
                               value="{{ $note }}"
                               maxlength="255"
                               class="form-control form-control-sm"
                               placeholder="Temporary duty / exam season / leave cover">
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <small class="text-muted d-block mt-2">Role default base rahega. Yahan se per-staff allow/deny aur temporary expiry dono manage kar sakte ho.</small>
</div>
@endif

{{-- Document Upload Settings --}}
@php
    $staffDocFull  = old('doc_full_form_upload',  $staffMember->doc_full_form_upload  ?? 'skip');
    $staffDocQuick = old('doc_quick_form_upload', $staffMember->doc_quick_form_upload ?? 'skip');
    $docOptions = [
        'skip'     => ['label' => 'Skip',     'icon' => 'bi-dash-circle',       'color' => 'secondary'],
        'optional' => ['label' => 'Optional', 'icon' => 'bi-info-circle',       'color' => 'warning'],
        'required' => ['label' => 'Required', 'icon' => 'bi-exclamation-circle','color' => 'danger'],
    ];
@endphp
<div class="card border-0 bg-light rounded-3 p-3 mb-4">
    <div class="fw-semibold small mb-3 text-dark">
        <i class="bi bi-paperclip me-1 text-primary"></i>Document Upload Settings
    </div>
    <small class="text-muted d-block mb-3">Admission submit ke baad document upload step show hoga ya nahi — full form aur quick form ke liye alag-alag.</small>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label small fw-semibold"><i class="bi bi-file-earmark-person me-1"></i>Full Form</label>
            <div class="d-flex gap-2 flex-wrap">
                @foreach($docOptions as $val => $opt)
                <div>
                    <input type="radio" class="btn-check" name="doc_full_form_upload"
                           id="sm_full_{{ $val }}" value="{{ $val }}"
                           @checked($staffDocFull === $val)>
                    <label class="btn btn-sm btn-outline-{{ $opt['color'] }}" for="sm_full_{{ $val }}">
                        <i class="bi {{ $opt['icon'] }} me-1"></i>{{ $opt['label'] }}
                    </label>
                </div>
                @endforeach
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold"><i class="bi bi-lightning me-1"></i>Quick Form</label>
            <div class="d-flex gap-2 flex-wrap">
                @foreach($docOptions as $val => $opt)
                <div>
                    <input type="radio" class="btn-check" name="doc_quick_form_upload"
                           id="sm_quick_{{ $val }}" value="{{ $val }}"
                           @checked($staffDocQuick === $val)>
                    <label class="btn btn-sm btn-outline-{{ $opt['color'] }}" for="sm_quick_{{ $val }}">
                        <i class="bi {{ $opt['icon'] }} me-1"></i>{{ $opt['label'] }}
                    </label>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="card border-0 bg-light rounded-3 p-3 mb-4">
    <div class="fw-semibold small mb-3 text-dark">
        <i class="bi bi-tags me-1 text-warning"></i>Fee Type Collection Access
    </div>
    <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" name="restrict_fee_collection_types" id="restrictFeeTypes" value="1"
               {{ old('restrict_fee_collection_types', $staffMember->restrict_fee_collection_types ?? false) ? 'checked' : '' }}>
        <label class="form-check-label fw-semibold" for="restrictFeeTypes">Restrict fee collection to selected fee items</label>
        <small class="text-muted d-block">On rahega to staff sirf selected fee items hi collect kar paayega.</small>
    </div>

    @if(isset($feeTypes) && $feeTypes->isNotEmpty())
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0 align-middle" style="font-size:13px;">
            <thead class="table-light">
                <tr>
                    <th>Fee Item</th>
                    <th class="text-center" style="width:140px;">Collect Allowed?</th>
                </tr>
            </thead>
            <tbody>
                @foreach($feeTypes as $ft)
                <tr>
                    <td>{{ $ft->name }}</td>
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input" name="fee_collection_allowed[{{ $ft->id }}]" value="1"
                               {{ in_array((int) $ft->id, $selectedFeeCollectionIds, true) ? 'checked' : '' }}>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
