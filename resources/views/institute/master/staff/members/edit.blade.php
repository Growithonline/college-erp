@extends('institute.layout')
@section('title', isset($staffMember) ? 'Edit Staff' : 'Add Staff')
@section('breadcrumb', 'Master / Staff / ' . (isset($staffMember) ? 'Edit' : 'New'))
@section('content')

@php
    $selectedCourseIds = collect($allowedCourseIds ?? [])->map(fn ($id) => (int) $id)->all();
    $selectedCourseIdsFromOld = array_keys((array) old('course_permissions', []));
    if (!empty($selectedCourseIdsFromOld)) { $selectedCourseIds = array_map('intval', $selectedCourseIdsFromOld); }

    $selectedFeeCollectionIds = isset($feeCollectionPermissions) ? collect($feeCollectionPermissions)->map(fn ($id) => (int) $id)->all() : [];
    $selectedFeeCollectionIdsFromOld = array_keys((array) old('fee_collection_allowed', []));
    if (!empty($selectedFeeCollectionIdsFromOld)) { $selectedFeeCollectionIds = array_map('intval', $selectedFeeCollectionIdsFromOld); }

    $selectedPaymentModes       = old('payment_modes', $paymentPermission?->allowed_modes ?? ['cash']);
    $selectedPaymentBankIds     = array_map('intval', old('payment_bank_ids', $paymentPermission?->allowed_bank_ids ?? []));
    $selectedPayrollCategories  = old('payroll_scope_categories', $staffMember->payroll_scope_categories ?? []);
    $selectedSessionIds         = array_map('intval', old('allowed_session_ids', $staffMember->allowed_session_ids ?? []));
    $selectedStudentVisibility  = old('student_visibility_scope', $staffMember->student_visibility_scope ?? 'role_based');
    $permissionOverridesMap     = $permissionOverrides ?? collect();
    $selectedRolePermissionMap  = collect($selectedRoleForAccess?->permissions ?? []);
    $visibilityOptions          = $studentVisibilityOptions ?? ['role_based' => 'Use role default', 'self' => 'Own students only', 'all' => 'All accessible students'];
@endphp

@if(isset($staffMember))
<form method="POST" action="{{ route('master.staff-members.update', $staffMember) }}" id="staffForm">
    @method('PUT')
@else
<form method="POST" action="{{ route('master.staff-members.store') }}" id="staffForm">
@endif
@csrf

@if($errors->any() || session('error'))
<div class="alert alert-danger alert-dismissible mb-3">
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    @if(session('error'))
        <div class="fw-semibold"><i class="bi bi-exclamation-octagon me-1"></i>{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Please fix the following errors</div>
        <ul class="mb-0 ps-3">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
    @endif
</div>
@endif

<div class="row g-4 align-items-start">

    {{-- ══════════════════════════════════════════════════════
         LEFT — Basic Info (sticky)
    ══════════════════════════════════════════════════════ --}}
    <div class="col-md-4 col-xl-3">
        <div class="card border-0 shadow-sm" style="position:sticky;top:68px;">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-person-badge me-2 text-primary"></i>
                    {{ isset($staffMember) ? 'Edit: '.$staffMember->name : 'New Staff Member' }}
                </h6>
            </div>
            <div class="card-body p-3">

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $staffMember->name ?? '') }}"
                           class="form-control form-control-sm @error('name') is-invalid @enderror">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Role <span class="text-danger">*</span></label>
                    <select name="staff_role_id" id="staffRoleId"
                            class="form-select form-select-sm @error('staff_role_id') is-invalid @enderror">
                        <option value="">Select Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}"
                                data-permissions="{{ json_encode($role->permissions ?? []) }}"
                                {{ old('staff_role_id', $staffMember->staff_role_id ?? '') == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('staff_role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $staffMember->email ?? '') }}"
                           class="form-control form-control-sm @error('email') is-invalid @enderror"
                           {{ isset($staffMember) ? 'readonly' : '' }}>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Mobile <span class="text-danger">*</span></label>
                    <input type="text" name="mobile" value="{{ old('mobile', $staffMember->mobile ?? '') }}"
                           class="form-control form-control-sm @error('mobile') is-invalid @enderror">
                    @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Joining Date</label>
                        <input type="date" name="joining_date"
                               value="{{ old('joining_date', isset($staffMember) ? $staffMember->joining_date?->format('Y-m-d') : '') }}"
                               class="form-control form-control-sm">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Staff Type <span class="text-danger">*</span></label>
                        <select name="staff_category"
                                class="form-select form-select-sm @error('staff_category') is-invalid @enderror">
                            <option value="">Select</option>
                            @foreach(['Teaching','Office','Non-Teaching','Guest'] as $cat)
                                <option value="{{ $cat }}" @selected(old('staff_category', $staffMember->staff_category ?? '') === $cat)>{{ $cat }}</option>
                            @endforeach
                        </select>
                        @error('staff_category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Address</label>
                    <input type="text" name="address" value="{{ old('address', $staffMember->address ?? '') }}"
                           class="form-control form-control-sm">
                </div>

                @if(isset($staffMember))
                <div class="border-top pt-3 mt-1">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="reset_password" id="resetPass" value="1">
                        <label class="form-check-label small fw-semibold" for="resetPass">
                            <i class="bi bi-key me-1 text-warning"></i>Reset Password
                        </label>
                    </div>
                    <small class="text-muted d-block ms-4 mt-1">A new password will be sent to the staff's email.</small>
                </div>
                @else
                <div class="alert alert-info py-2 px-3 small mb-0 mt-2">
                    <i class="bi bi-info-circle me-1"></i>A password will be auto-generated and sent via email.
                </div>
                @endif

            </div>
            <div class="card-footer bg-white border-top py-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="bi bi-check-lg me-1"></i>{{ isset($staffMember) ? 'Update Staff' : 'Save Staff' }}
                </button>
                <a href="{{ route('master.staff-members.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         RIGHT — Tabbed sections
    ══════════════════════════════════════════════════════ --}}
    <div class="col-md-8 col-xl-9">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom p-0">
                <ul class="nav nav-tabs border-0 px-3 pt-2" id="staffTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-payroll"
                                type="button" role="tab">
                            <i class="bi bi-cash-coin me-1"></i>Payroll
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-access"
                                type="button" role="tab">
                            <i class="bi bi-shield-check me-1"></i>Access Scope
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-fee"
                                type="button" role="tab">
                            <i class="bi bi-tags me-1"></i>Fee & Payment
                        </button>
                    </li>
                    @if(!empty($permissionLabels ?? []))
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-perms"
                                type="button" role="tab">
                            <i class="bi bi-sliders me-1"></i>Permissions
                        </button>
                    </li>
                    @endif
                </ul>
            </div>
            <div class="card-body p-4">
                <div class="tab-content">

                    {{-- ─── TAB 1: Payroll ─────────────────────────────────── --}}
                    <div class="tab-pane fade show active" id="tab-payroll" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small">Payroll Type <span class="text-danger">*</span></label>
                                <select name="payroll_type"
                                        class="form-select form-select-sm @error('payroll_type') is-invalid @enderror">
                                    <option value="monthly" @selected(old('payroll_type', $staffMember->payroll_type ?? 'monthly') === 'monthly')>Monthly Salary</option>
                                    <option value="daily"   @selected(old('payroll_type', $staffMember->payroll_type ?? '') === 'daily')>Daily Wage</option>
                                </select>
                                @error('payroll_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small">Monthly Salary</label>
                                <input type="number" name="monthly_salary" min="0" step="0.01"
                                       value="{{ old('monthly_salary', $staffMember->monthly_salary ?? $staffMember->salary ?? '') }}"
                                       class="form-control form-control-sm @error('monthly_salary') is-invalid @enderror"
                                       placeholder="e.g. 15000">
                                @error('monthly_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small">Daily Wage</label>
                                <input type="number" name="daily_wage" min="0" step="0.01"
                                       value="{{ old('daily_wage', $staffMember->daily_wage ?? '') }}"
                                       class="form-control form-control-sm @error('daily_wage') is-invalid @enderror"
                                       placeholder="e.g. 600">
                                @error('daily_wage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small">Salary Expense Head</label>
                                <select name="salary_expense_head_id" class="form-select form-select-sm">
                                    <option value="">Auto Select by Staff Type</option>
                                    @foreach($expenseAccounts as $ea)
                                        <option value="{{ $ea->id }}" @selected((string) old('salary_expense_head_id', $staffMember->salary_expense_head_id ?? '') === (string) $ea->id)>
                                            {{ $ea->code }} – {{ $ea->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small">Leave Policy Group</label>
                                <input type="text" name="leave_policy_group"
                                       value="{{ old('leave_policy_group', $staffMember->leave_policy_group ?? '') }}"
                                       class="form-control form-control-sm"
                                       placeholder="e.g. Standard Staff Policy">
                            </div>
                        </div>

                        <hr class="my-4">
                        <p class="fw-semibold small mb-3 text-dark">
                            <i class="bi bi-bank me-1 text-primary"></i>Bank Details
                        </p>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small">Account Number</label>
                                <input type="text" name="bank_account_number"
                                       value="{{ old('bank_account_number', $staffMember->bank_account_number ?? '') }}"
                                       class="form-control form-control-sm @error('bank_account_number') is-invalid @enderror">
                                @error('bank_account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small">Account Holder Name</label>
                                <input type="text" name="bank_account_holder"
                                       value="{{ old('bank_account_holder', $staffMember->bank_account_holder ?? '') }}"
                                       class="form-control form-control-sm @error('bank_account_holder') is-invalid @enderror">
                                @error('bank_account_holder')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small">Bank Name</label>
                                <input type="text" name="bank_name"
                                       value="{{ old('bank_name', $staffMember->bank_name ?? '') }}"
                                       class="form-control form-control-sm @error('bank_name') is-invalid @enderror">
                                @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small">IFSC</label>
                                <input type="text" name="bank_ifsc"
                                       value="{{ old('bank_ifsc', $staffMember->bank_ifsc ?? '') }}"
                                       class="form-control form-control-sm @error('bank_ifsc') is-invalid @enderror">
                                @error('bank_ifsc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <input type="hidden" name="salary" value="{{ old('salary', $staffMember->salary ?? '') }}">
                    </div>

                    {{-- ─── TAB 2: Access Scope ─────────────────────────────── --}}
                    <div class="tab-pane fade" id="tab-access" role="tabpanel">

                        {{-- Row 1: Student Visibility + Admission Form --}}
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small">
                                    <i class="bi bi-eye me-1 text-primary"></i>Student Data Visibility
                                </label>
                                <select name="student_visibility_scope"
                                        class="form-select form-select-sm @error('student_visibility_scope') is-invalid @enderror">
                                    @foreach($visibilityOptions as $scopeKey => $scopeLabel)
                                        <option value="{{ $scopeKey }}" @selected($selectedStudentVisibility === $scopeKey)>{{ $scopeLabel }}</option>
                                    @endforeach
                                </select>
                                @error('student_visibility_scope')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted d-block mt-1">Own = sirf apne admitted students. All = allowed course/session ke sab.</small>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small">
                                    <i class="bi bi-ui-checks me-1 text-info"></i>Admission Form Access
                                </label>
                                @php $selectedAdmForms = old('allowed_admission_forms', $staffMember->allowed_admission_forms ?? 'both'); @endphp
                                <div class="d-flex gap-2 flex-wrap">
                                    @foreach(['both' => 'Both (Full & Quick)', 'full' => 'Full Form Only', 'quick' => 'Quick Form Only'] as $val => $lbl)
                                    <label class="border rounded px-2 py-2 small bg-white d-flex align-items-center gap-1 cursor-pointer">
                                        <input type="radio" class="form-check-input" name="allowed_admission_forms"
                                               value="{{ $val }}" {{ $selectedAdmForms === $val ? 'checked' : '' }}>
                                        {{ $lbl }}
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- Academic Sessions --}}
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-semibold small text-dark"><i class="bi bi-calendar3 me-1 text-primary"></i>Academic Sessions</span>
                                <div class="d-flex align-items-center gap-2">
                                    <small class="text-muted" id="sessionScopeLabel">{{ old('restrict_session_access', $staffMember->restrict_session_access ?? false) ? 'Selected only' : 'All sessions' }}</small>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" name="restrict_session_access"
                                               id="restrictSessionAccess" value="1"
                                               {{ old('restrict_session_access', $staffMember->restrict_session_access ?? false) ? 'checked' : '' }}>
                                    </div>
                                </div>
                            </div>
                            @if(isset($sessions) && $sessions->isNotEmpty())
                            <div id="sessionChoices" class="{{ old('restrict_session_access', $staffMember->restrict_session_access ?? false) ? 'd-flex' : 'd-none' }} flex-wrap gap-2 ps-1">
                                @foreach($sessions as $session)
                                <label class="border rounded px-2 py-1 small bg-white d-flex align-items-center gap-1 cursor-pointer">
                                    <input type="checkbox" class="form-check-input" name="allowed_session_ids[]"
                                           value="{{ $session->id }}"
                                           {{ in_array((int) $session->id, $selectedSessionIds, true) ? 'checked' : '' }}>
                                    {{ $session->name }}
                                    @if($session->is_active)<span class="badge bg-success-subtle text-success border ms-1">Current</span>@endif
                                </label>
                                @endforeach
                            </div>
                            @error('allowed_session_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            @error('allowed_session_ids.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            @endif
                        </div>

                        {{-- Courses --}}
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-semibold small text-dark"><i class="bi bi-diagram-3 me-1 text-primary"></i>Courses</span>
                                <div class="d-flex align-items-center gap-2">
                                    <small class="text-muted" id="courseScopeLabel">{{ old('restrict_course_access', $staffMember->restrict_course_access ?? false) ? 'Selected only' : 'All courses' }}</small>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" name="restrict_course_access"
                                               id="restrictCourseAccess" value="1"
                                               {{ old('restrict_course_access', $staffMember->restrict_course_access ?? false) ? 'checked' : '' }}>
                                    </div>
                                </div>
                            </div>
                            @if(isset($courses) && $courses->isNotEmpty())
                            <div id="courseChoices" class="{{ old('restrict_course_access', $staffMember->restrict_course_access ?? false) ? 'd-flex' : 'd-none' }} flex-wrap gap-2 ps-1">
                                @foreach($courses as $course)
                                <label class="border rounded px-2 py-1 small bg-white d-flex align-items-center gap-1 cursor-pointer">
                                    <input type="checkbox" class="form-check-input"
                                           name="course_permissions[{{ $course->id }}]" value="1"
                                           {{ in_array((int) $course->id, $selectedCourseIds, true) ? 'checked' : '' }}>
                                    {{ $course->name }}
                                </label>
                                @endforeach
                            </div>
                            @endif
                        </div>

                        <hr class="my-3">

                        {{-- Attendance & Payroll --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold small">
                                <i class="bi bi-people me-1 text-secondary"></i>Attendance & Payroll — Staff Categories
                            </label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach(($payrollCategories ?? ['Teaching','Office','Non-Teaching','Guest']) as $cat)
                                <label class="border rounded px-2 py-1 small bg-white d-flex align-items-center gap-1 cursor-pointer">
                                    <input type="checkbox" class="form-check-input" name="payroll_scope_categories[]"
                                           value="{{ $cat }}"
                                           {{ in_array($cat, $selectedPayrollCategories, true) ? 'checked' : '' }}>
                                    {{ $cat }}
                                </label>
                                @endforeach
                            </div>
                            <small class="text-muted d-block mt-1">Nothing selected = all categories accessible.</small>
                        </div>

                        <hr class="my-3">

                        {{-- Notice Management --}}
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-semibold small"><i class="bi bi-megaphone me-1 text-primary"></i>Notice Management</div>
                                <small class="text-muted">Off = sirf view kar sakta hai.</small>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="can_manage_notices"
                                       id="canManageNotices" value="1"
                                       {{ old('can_manage_notices', $staffMember->can_manage_notices ?? false) ? 'checked' : '' }}>
                            </div>
                        </div>

                    </div>

                    {{-- ─── TAB 3: Fee & Payment ────────────────────────────── --}}
                    <div class="tab-pane fade" id="tab-fee" role="tabpanel">

                        {{-- Fee Collection Types --}}
                        <div class="mb-4">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="restrict_fee_collection_types"
                                           id="restrictFeeTypes" value="1"
                                           {{ old('restrict_fee_collection_types', $staffMember->restrict_fee_collection_types ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold small" for="restrictFeeTypes">
                                        <i class="bi bi-tags me-1 text-warning"></i>Allow only selected fee types
                                    </label>
                                </div>
                                <small class="text-muted">Off = all fee types collectable</small>
                            </div>
                            @if(isset($feeTypes) && $feeTypes->isNotEmpty())
                            <div class="d-flex flex-wrap gap-2 ms-2">
                                @foreach($feeTypes as $ft)
                                <label class="border rounded px-2 py-1 small bg-white d-flex align-items-center gap-1 cursor-pointer">
                                    <input type="checkbox" class="form-check-input"
                                           name="fee_collection_allowed[{{ $ft->id }}]" value="1"
                                           {{ in_array((int) $ft->id, $selectedFeeCollectionIds, true) ? 'checked' : '' }}>
                                    {{ $ft->name }}
                                </label>
                                @endforeach
                            </div>
                            @endif
                        </div>

                        <hr class="my-3">

                        {{-- Discount Limit --}}
                        <div class="mb-4">
                            <p class="fw-semibold small mb-2 text-dark">
                                <i class="bi bi-percent me-1 text-warning"></i>Fee Discount Limit
                            </p>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div style="max-width:140px;">
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="max_discount_percent" min="0" max="100"
                                               value="{{ old('max_discount_percent', $staffMember->max_discount_percent ?? 100) }}"
                                               class="form-control form-control-sm @error('max_discount_percent') is-invalid @enderror"
                                               placeholder="0–100">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    @error('max_discount_percent')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <small class="text-muted">100% = no limit. Default applies to items without a specific permission set.</small>
                            </div>

                            @if(isset($feeTypes) && $feeTypes->isNotEmpty())
                            <style>
                                .discount-tbl tbody tr:has(input:checked) { background-color:#d1fae5!important; }
                                .discount-tbl tbody tr:has(input:checked) td:first-child { font-weight:600;color:#065f46; }
                                .discount-tbl tbody tr:has(input:not(:checked)) td:first-child { color:#9ca3af; }
                            </style>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0 align-middle discount-tbl" style="font-size:13px;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Fee Item</th>
                                            <th class="text-center" style="width:160px;">Discount Allowed?</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($feeTypes as $ft)
                                    @php
                                        $isAllowed = old("fee_discount_allowed.{$ft->id}",
                                            isset($feeDiscountPermissions) && in_array($ft->id, $feeDiscountPermissions->toArray()) ? '1' : null
                                        );
                                    @endphp
                                    <tr>
                                        <td>{{ $ft->name }}</td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex align-items-center justify-content-center gap-2 mb-0">
                                                <input type="checkbox" class="form-check-input discount-toggle" role="switch"
                                                       name="fee_discount_allowed[{{ $ft->id }}]" value="1"
                                                       {{ $isAllowed ? 'checked' : '' }}>
                                                <span class="small {{ $isAllowed ? 'text-success fw-semibold' : 'text-danger' }}">
                                                    {{ $isAllowed ? 'Yes' : 'No' }}
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted d-block mt-2">
                                Toggle ON = discount allowed up to the global limit. OFF = no discount at all.
                            </small>
                            @endif
                        </div>

                        <hr class="my-3">

                        {{-- Payment Modes & Bank Accounts --}}
                        <div class="row g-4">
                            <div class="col-sm-6">
                                <p class="fw-semibold small mb-2 text-dark">
                                    <i class="bi bi-credit-card me-1 text-success"></i>Allowed Payment Modes
                                </p>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($paymentModes as $modeKey => $modeLabel)
                                    <label class="border rounded px-2 py-1 small bg-white d-flex align-items-center gap-1 cursor-pointer">
                                        <input type="checkbox" class="form-check-input" name="payment_modes[]"
                                               value="{{ $modeKey }}"
                                               {{ in_array($modeKey, $selectedPaymentModes, true) ? 'checked' : '' }}>
                                        {{ $modeLabel }}
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <p class="fw-semibold small mb-2 text-dark">
                                    <i class="bi bi-bank me-1 text-success"></i>Allowed Bank Accounts
                                </p>
                                <div class="d-flex flex-column gap-2">
                                    @forelse($bankAccounts as $ba)
                                    <label class="border rounded px-2 py-1 small bg-white d-flex align-items-center gap-1 cursor-pointer">
                                        <input type="checkbox" class="form-check-input" name="payment_bank_ids[]"
                                               value="{{ $ba->id }}"
                                               {{ in_array((int) $ba->id, $selectedPaymentBankIds, true) ? 'checked' : '' }}>
                                        {{ $ba->display_label ?: $ba->bank_name }} – {{ $ba->account_no }}
                                    </label>
                                    @empty
                                    <span class="text-muted small">Koi active bank account nahi.</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-3">Nothing saved = cash-only mode applies by default.</small>

                    </div>

                    {{-- ─── TAB 4: Permissions ──────────────────────────────── --}}
                    @if(!empty($permissionLabels ?? []))
                    <div class="tab-pane fade" id="tab-perms" role="tabpanel">
                        <div class="d-flex gap-3 align-items-center mb-3" style="font-size:12px;color:#6b7280;">
                            <span><span class="badge bg-secondary-subtle text-secondary border">Blocked</span> Not in role</span>
                            <span><span class="badge bg-success-subtle text-success border">Allowed</span> Granted by role</span>
                            <span class="ms-auto text-muted">Set Allow or Deny to reveal Valid Till and Note fields.</span>
                        </div>
                        <div style="max-height:400px;overflow-y:auto;border:1px solid #dee2e6;border-radius:6px;">
                        <table class="table table-sm table-bordered align-middle mb-0" style="font-size:13px;border:none;">
                            <thead class="table-light" style="position:sticky;top:0;z-index:1;">
                                <tr>
                                    <th>Permission</th>
                                    <th class="text-center" style="width:85px;">Role</th>
                                    <th style="width:190px;">Override</th>
                                    <th class="text-center" style="width:85px;">Effective</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($permissionLabels as $permissionKey => $permissionLabel)
                            @php
                                $savedOverride   = $permissionOverridesMap[$permissionKey] ?? null;
                                $effect          = old("permission_overrides.$permissionKey.effect", $savedOverride?->effect);
                                $expiresAt       = old("permission_overrides.$permissionKey.expires_at", $savedOverride?->expires_at?->format('Y-m-d'));
                                $note            = old("permission_overrides.$permissionKey.note", $savedOverride?->note);
                                $roleAllows      = (bool) ($selectedRolePermissionMap[$permissionKey] ?? false);
                                $overrideActive  = $savedOverride ? $savedOverride->isActive() : false;
                                $effectiveAllows = $effect === 'allow' ? true : ($effect === 'deny' ? false : $roleAllows);
                                $hasOverride     = !empty($effect);
                                $safeKey         = Str::slug($permissionKey, '_');
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $permissionLabel }}</div>
                                    <div class="text-muted" style="font-size:11px;">{{ $permissionKey }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $roleAllows ? 'bg-success-subtle text-success border' : 'bg-secondary-subtle text-secondary border' }}">
                                        {{ $roleAllows ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td>
                                    <input type="hidden" name="permission_overrides[{{ $permissionKey }}][effect]"
                                           id="perm-val-{{ $safeKey }}" value="{{ $effect }}">
                                    <div class="btn-group btn-group-sm w-100" role="group">
                                        <button type="button"
                                                class="btn perm-btn {{ empty($effect) ? 'btn-secondary' : 'btn-outline-secondary' }}"
                                                data-pkey="{{ $safeKey }}" data-val="">Default</button>
                                        <button type="button"
                                                class="btn perm-btn {{ $effect === 'allow' ? 'btn-success' : 'btn-outline-success' }}"
                                                data-pkey="{{ $safeKey }}" data-val="allow">Allow</button>
                                        <button type="button"
                                                class="btn perm-btn {{ $effect === 'deny' ? 'btn-danger' : 'btn-outline-danger' }}"
                                                data-pkey="{{ $safeKey }}" data-val="deny">Deny</button>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge perm-effective-{{ $safeKey }} {{ $effectiveAllows ? 'bg-success-subtle text-success border' : 'bg-danger-subtle text-danger border' }}">
                                        {{ $effectiveAllows ? 'Allowed' : 'Blocked' }}
                                    </span>
                                    <div class="text-muted mt-1" style="font-size:10px;">
                                        @if($savedOverride && $overrideActive) override
                                        @elseif($effect) pending
                                        @else role @endif
                                    </div>
                                </td>
                            </tr>
                            <tr class="perm-detail-row" id="perm-detail-{{ $safeKey }}"
                                style="{{ $hasOverride ? '' : 'display:none;' }}">
                                <td colspan="4" class="bg-light py-2 px-3">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-sm-4">
                                            <label class="form-label small fw-semibold mb-1">Valid Till</label>
                                            <input type="date" name="permission_overrides[{{ $permissionKey }}][expires_at]"
                                                   value="{{ $expiresAt }}" class="form-control form-control-sm">
                                            @if($savedOverride?->expires_at)
                                            <div class="text-muted mt-1" style="font-size:10px;">
                                                {{ $savedOverride->isActive()
                                                    ? 'Active till '.$savedOverride->expires_at->format('d M Y')
                                                    : 'Expired '.$savedOverride->expires_at->format('d M Y') }}
                                            </div>
                                            @endif
                                        </div>
                                        <div class="col-sm-8">
                                            <label class="form-label small fw-semibold mb-1">Note</label>
                                            <input type="text" name="permission_overrides[{{ $permissionKey }}][note]"
                                                   value="{{ $note }}" maxlength="255"
                                                   class="form-control form-control-sm"
                                                   placeholder="e.g. Exam duty cover, temporary access">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                        </div>
                        <small class="text-muted d-block mt-2">
                            Role is the base. Set Allow or Deny to reveal the Valid Till and Note fields.
                        </small>
                    </div>
                    @endif

                </div>{{-- .tab-content --}}
            </div>{{-- .card-body --}}
        </div>{{-- .card --}}
    </div>{{-- col right --}}

</div>{{-- .row --}}
</form>

@push('scripts')
<script>
// Discount toggle label update
document.querySelectorAll('.discount-toggle').forEach(function (cb) {
    cb.addEventListener('change', function () {
        var span = this.closest('.form-check').querySelector('span');
        if (!span) return;
        span.textContent = this.checked ? 'Yes' : 'No';
        span.className   = 'small ' + (this.checked ? 'text-success fw-semibold' : 'text-danger');
    });
});

// Permission override 3-button toggle
document.querySelectorAll('.perm-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var pkey = this.dataset.pkey;
        var val  = this.dataset.val;

        // Update hidden input
        var input = document.getElementById('perm-val-' + pkey);
        if (input) input.value = val;

        // Reset all buttons in group
        var group = this.closest('.btn-group');
        group.querySelectorAll('.perm-btn').forEach(function (b) {
            var bval = b.dataset.val;
            b.className = 'btn perm-btn btn-sm ' + (
                bval === ''      ? 'btn-outline-secondary' :
                bval === 'allow' ? 'btn-outline-success'   : 'btn-outline-danger'
            );
        });

        // Activate clicked button
        this.className = 'btn perm-btn btn-sm ' + (
            val === ''      ? 'btn-secondary' :
            val === 'allow' ? 'btn-success'   : 'btn-danger'
        );

        // Show/hide detail row
        var detail = document.getElementById('perm-detail-' + pkey);
        if (detail) detail.style.display = val ? '' : 'none';

        // Update effective badge
        var roleBadge = this.closest('tr').querySelector('td:nth-child(2) .badge');
        var roleAllows = roleBadge ? roleBadge.classList.contains('text-success') : false;
        var effectiveAllows = val === 'allow' ? true : (val === 'deny' ? false : roleAllows);
        var effBadge = document.querySelector('.perm-effective-' + pkey);
        if (effBadge) {
            effBadge.textContent = effectiveAllows ? 'Allowed' : 'Blocked';
            effBadge.className = 'badge perm-effective-' + pkey + ' ' + (
                effectiveAllows ? 'bg-success-subtle text-success border' : 'bg-danger-subtle text-danger border'
            );
        }
    });
});

// Auto-jump to tab containing validation errors
(function () {
    var tabFieldMap = {
        'tab-payroll': ['payroll_type','monthly_salary','daily_wage','bank_account_number','bank_account_holder','bank_name','bank_ifsc'],
        'tab-access':  ['student_visibility_scope','restrict_session_access','allowed_session_ids','restrict_course_access'],
        'tab-fee':     ['restrict_fee_collection_types','max_discount_percent','payment_modes','payment_bank_ids'],
        'tab-perms':   ['permission_overrides'],
    };
    for (var tabId in tabFieldMap) {
        var found = tabFieldMap[tabId].some(function (field) {
            return !!document.querySelector('[name^="' + field + '"].is-invalid');
        });
        if (found) {
            var btn = document.querySelector('[data-bs-target="#' + tabId + '"]');
            if (btn) bootstrap.Tab.getOrCreateInstance(btn).show();
            break;
        }
    }
})();

// Session + Course scope toggle — show/hide choices
(function () {
    function scopeToggle(toggleId, choicesId, labelId, onText, offText) {
        var t = document.getElementById(toggleId);
        var c = document.getElementById(choicesId);
        var l = document.getElementById(labelId);
        if (!t || !c) return;
        t.addEventListener('change', function () {
            c.classList.remove('d-none', 'd-flex');
            c.classList.add(this.checked ? 'd-flex' : 'd-none');
            if (l) l.textContent = this.checked ? onText : offText;
        });
    }
    scopeToggle('restrictSessionAccess', 'sessionChoices', 'sessionScopeLabel', 'Selected only', 'All sessions');
    scopeToggle('restrictCourseAccess',  'courseChoices',  'courseScopeLabel',  'Selected only', 'All courses');
})();
</script>
@endpush
@endsection
