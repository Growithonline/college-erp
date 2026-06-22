@php
    $admissionRoutePrefix = auth()->guard('staff')->check()
        ? 'staff.admissions'
        : (auth()->guard('partner')->check() ? 'partner.admissions'
            : (auth()->guard('center')->check() ? 'center.admissions' : 'admissions'));
    $streamSubjectsUrl = route($admissionRoutePrefix . '.stream-subjects');
    $streamSeatsUrl = route($admissionRoutePrefix . '.stream-seats');
    $transportStopsUrl = route('transport.routes.stops', ['route' => '__ROUTE__']);
    // Preview edit se aaye hain — $pd view variable ya session se
    $pd = $pd ?? session('previewData') ?? [];
    // Helper: preview data ya old() se value lo
    $pv = fn($key, $default='') => old($key, $pd[$key] ?? $default);
    $selectedSubjectsForRestore = array_map('intval', old('selected_subjects', $pd['selected_subjects'] ?? []));
    $selectedMajorSubjectsForRestore = array_map('intval', old('selected_major_subjects', $pd['selected_major_subjects'] ?? []));
    $selectedMinorSubjectsForRestore = array_map('intval', old('selected_minor_subjects', $pd['selected_minor_subjects'] ?? []));
    $photoTemp = old('photo_temp', $pd['photo_temp'] ?? null);
    $photoTempUrl = $photoTemp ? \Illuminate\Support\Facades\Storage::url($photoTemp) : null;
    $fieldEnabled = fn($key) => (bool) (($formConfig[$key]['enabled'] ?? false) && ($formConfig[$key]['section_enabled'] ?? true));
    $fieldRequired = fn($key) => (bool) ($fieldEnabled($key) && ($formConfig[$key]['required'] ?? false));
@endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-person-plus me-2 text-primary"></i>New Admission</h4>
        <small class="text-muted">
            Session: <span class="fw-semibold text-primary">{{ $activeSession->name ?? 'No Active Session' }}</span>
        </small>
    </div>
    <a href="{{ isset($indexRoute) ? $indexRoute : route('admissions.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

@if(!$activeSession)
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    No active academic session found.
    <a href="{{ route('master.sessions.index') }}" class="alert-link">Activate a session</a>
</div>
@else

<form method="POST" action="{{ isset($previewRoute) ? $previewRoute : route('admissions.preview') }}" enctype="multipart/form-data" id="admissionForm">
@csrf

{{-- Server-side validation errors --}}
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-3" id="serverErrorBlock" role="alert">
    <strong><i class="bi bi-exclamation-triangle-fill me-1"></i> The form has {{ $errors->count() }} error(s) — please review below:</strong>
    <ul class="mb-0 mt-1">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- JS validation error banner (shown dynamically, sticky so visible while scrolling) --}}
<div id="jsValidationError" class="alert alert-danger alert-dismissible mb-3" role="alert"
     style="display:none!important; position:sticky; top:0; z-index:999; border-left:4px solid #dc3545;">
    <i class="bi bi-exclamation-triangle-fill me-1"></i>
    <span id="jsValidationErrorMsg">Some fields have errors — please check the highlighted fields below.</span>
    <button type="button" class="btn-close" onclick="document.getElementById('jsValidationError').style.setProperty('display','none','important')"></button>
</div>

{{-- ══ Session Selection ══════════════════════════════════════════════ --}}
@if(isset($admissibleSessions) && $admissibleSessions->count() > 1)
<div class="card border-0 shadow-sm mb-3" style="border-left:4px solid #6366f1!important;">
    <div class="card-body py-2 px-3 d-flex align-items-center gap-3">
        <i class="bi bi-calendar3 text-primary"></i>
        <label class="form-label fw-semibold small mb-0 text-nowrap">Admission Session</label>
        <select name="session_id" id="admissionSessionSelect" class="form-select form-select-sm" style="max-width:220px;">
            @foreach($admissibleSessions as $sess)
            <option value="{{ $sess->id }}"
                {{ old('session_id', $activeSession?->id) == $sess->id ? 'selected' : '' }}>
                {{ $sess->name }}{{ $sess->is_active ? ' (Current)' : '' }}
            </option>
            @endforeach
        </select>
        <small class="text-muted">Default is current session; select previous session if permitted.</small>
    </div>
</div>
@else
<input type="hidden" name="session_id" value="{{ $activeSession?->id }}">
@endif

{{-- ═══════════════════════════════════════ --}}
{{-- 1. COURSE DETAILS (SABSE PEHLE)         --}}
{{-- ═══════════════════════════════════════ --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b; color:white;">
        <span class="fw-bold small"><i class="bi bi-book me-2"></i>Course Details</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- Course Type --}}
            @php $savedCourseTypeId = $pv('course_type_id'); @endphp
            <div class="col-md-4">
                <label class="form-label small fw-semibold">
                    Course Type <span class="text-danger">*</span>
                </label>
                <select name="course_type_id" id="courseTypeSelect"
                        class="form-select form-select-sm @error('course_type_id') is-invalid @enderror"
                        required onchange="filterCoursesByType(this.value)">
                    <option value="">— Select Course Type —</option>
                    @foreach($courseTypes as $ct)
                        <option value="{{ $ct->id }}" {{ $savedCourseTypeId == $ct->id ? 'selected' : '' }}>
                            {{ $ct->name }}
                        </option>
                    @endforeach
                </select>
                @error('course_type_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Course --}}
            <div class="col-md-4">
                <label class="form-label small fw-semibold">
                    Course <span class="text-danger">*</span>
                </label>
                <select name="course_id" id="courseSelect" class="form-select form-select-sm"
                        onchange="loadStreams(this.value)">
                    <option value="">Select Course</option>
                    @foreach($courses as $course)
                    <option value="{{ $course->id }}"
                            data-type-id="{{ $course->course_type_id }}"
                            {{ ($pv('course_id') ?: '') == $course->id ? 'selected' : '' }}>
                        {{ $course->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">
                    Stream / Major Subject <span class="text-danger">*</span>
                </label>
                <select name="course_stream_id" id="streamSelect"
                        class="form-select form-select-sm @error('course_stream_id') is-invalid @enderror"
                        onchange="onStreamChange(this.value)" required>
                    <option value="">Select Course First</option>
                </select>
                <div id="seatInfo" class="mt-1" style="font-size:12px;"></div>
                @error('course_stream_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">
                    Year / Part <span class="text-danger">*</span>
                </label>
                <select name="course_part_id" id="partSelect" class="form-select form-select-sm"
                        onchange="onPartChange()">
                    <option value="">— Select Stream First —</option>
                </select>
                <div class="form-text small text-muted" id="partHint" style="display:none;">
                    <i class="bi bi-arrow-up-circle me-1 text-primary"></i>Select Year/Part to load subjects
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════ --}}
{{-- 2. SUBJECT SELECTION (dynamic)          --}}
{{-- ═══════════════════════════════════════ --}}
<div id="subjectSection" class="card border-0 shadow-sm mb-3" style="display:none;">
    <div class="card-header py-2" style="background:#0f4c81; color:white;">
        <div class="d-flex justify-content-between align-items-center">
            <span class="fw-bold small"><i class="bi bi-list-check me-2"></i>Subject Selection</span>
            <small id="minorCountInfo" class="opacity-75"></small>
        </div>
    </div>
    <div class="card-body p-3">
        <div id="subjectErrorFeedback" class="alert alert-danger py-2 mb-2 d-none" role="alert" style="font-size:13px;">
            <i class="bi bi-exclamation-circle me-1"></i><span id="subjectErrorText"></span>
        </div>
        <div id="subjectLoading" class="text-center text-muted py-3" style="display:none;">
            <div class="spinner-border spinner-border-sm me-2"></div> Loading subjects...
        </div>
        <div id="subjectContent"></div>
    </div>
</div>
@if($errors->has('selected_minor_subjects') || $errors->has('selected_major_subjects'))
<script>window.__subjectBackendError = @json($errors->first('selected_minor_subjects') ?: $errors->first('selected_major_subjects'));</script>
@endif

{{-- ═══════════════════════════════════════ --}}
{{-- Fee Preview create form se hata diya — Preview page pe dikhega --}}

{{-- ═══════════════════════════════════════ --}}
{{-- 4. OFFICE DETAILS                       --}}
{{-- ═══════════════════════════════════════ --}}
<!-- form no work as serial no -->
@php
    $showOffice = collect([
        'form_no', 'institute_form_no', 'sr_no', 'enrollment_no', 'roll_no', 'exam_form_no', 'uin_no', 'reference_no',
        'admission_type', 'admission_source', 'gap_year', 'admission_date', 'submitted_date', 'academic_session'
    ])->contains(fn($key) => $fieldEnabled($key));
@endphp
@if($showOffice)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b; color:white;">
        <span class="fw-bold small"><i class="bi bi-briefcase me-2"></i>Office Details</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @if($fieldEnabled('form_no'))
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Serial No.</label>
                <input type="text" class="form-control form-control-sm bg-light" value="Auto" readonly>
            </div>
            @endif

            @if($fieldEnabled('institute_form_no'))
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Form No. @if($fieldRequired('institute_form_no'))<span class="text-danger">*</span>@endif</label>
                <input type="text" name="institute_form_no" class="form-control form-control-sm" value="{{ $pv('institute_form_no') }}" {{ $fieldRequired('institute_form_no') ? 'required' : '' }} placeholder="e.g. 2026/001">
            </div>
            @endif

            @foreach(['sr_no' => 'Student Registration No.', 'enrollment_no' => 'Enrollment No.', 'roll_no' => 'Roll No.', 'exam_form_no' => 'Exam Form No.', 'uin_no' => 'UIN No.', 'reference_no' => 'Reference No.'] as $key => $label)
                @if($fieldEnabled($key))
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">{{ $label }} @if($fieldRequired($key))<span class="text-danger">*</span>@endif</label>
                    <input type="text" name="{{ $key }}" class="form-control form-control-sm" value="{{ $pv($key) }}" {{ $fieldRequired($key) ? 'required' : '' }}>
                </div>
                @endif
            @endforeach

            @if($fieldEnabled('submitted_date'))
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Submitted Date</label>
                <input type="date" name="submitted_date" class="form-control form-control-sm bg-light" value="{{ date('Y-m-d') }}" readonly>
            </div>
            @endif

            @if($fieldEnabled('admission_type'))
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Admission Type @if($fieldRequired('admission_type'))<span class="text-danger">*</span>@endif</label>
                <select name="admission_type" class="form-select form-select-sm fee-param-field" {{ $fieldRequired('admission_type') ? 'required' : '' }}>
                    <option value="new" {{ $pv('admission_type','new')=='new' ? 'selected':'' }}>New</option>
                    <option value="lateral" {{ $pv('admission_type','new')=='lateral' ? 'selected':'' }}>Lateral Entry</option>
                    <option value="transfer" {{ $pv('admission_type','new')=='transfer' ? 'selected':'' }}>Transfer</option>
                    <option value="re_admission" {{ $pv('admission_type','new')=='re_admission' ? 'selected':'' }}>Re-Admission</option>
                </select>
            </div>
            @endif

            @if($fieldEnabled('admission_source'))
            @if(!empty($admissionSourceLocked))
            {{-- Portal: source pre-locked, read-only --}}
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Admission Source</label>
                <input type="text" class="form-control form-control-sm bg-light"
                       value="{{ ucwords(str_replace('_', ' ', $admissionSourceLocked)) }}" readonly>
                <input type="hidden" name="admission_source" value="{{ $admissionSourceLocked }}">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">{{ $admissionSourceLocked === 'center' ? 'Center' : 'Channel Partner' }}</label>
                <input type="text" class="form-control form-control-sm bg-light"
                       value="{{ $admissionSourceLockedName ?? '' }}" readonly>
                <input type="hidden" name="admission_source_id" value="{{ $admissionSourceLockedId ?? '' }}">
            </div>
            @else
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Admission Source @if($fieldRequired('admission_source'))<span class="text-danger">*</span>@endif</label>
                <div class="d-flex gap-3 mt-1">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="admission_source" value="direct" id="srcDirect" {{ $pv('admission_source', 'direct') === 'direct' ? 'checked' : '' }} onchange="toggleSourceSelect(this.value)">
                        <label class="form-check-label small" for="srcDirect">Direct</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="admission_source" value="center" id="srcCenter" {{ $pv('admission_source') === 'center' ? 'checked' : '' }} onchange="toggleSourceSelect(this.value)">
                        <label class="form-check-label small" for="srcCenter">Center</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="admission_source" value="channel_partner" id="srcPartner" {{ $pv('admission_source') === 'channel_partner' ? 'checked' : '' }} onchange="toggleSourceSelect(this.value)">
                        <label class="form-check-label small" for="srcPartner">Channel Partner</label>
                    </div>
                </div>
                <div id="centerSelect" class="mt-2 d-none">
                    <select name="admission_source_id" class="form-select form-select-sm" disabled>
                        <option value="">Select Center</option>
                        @foreach($centers as $c)
                        <option value="{{ $c->id }}" {{ (string) $pv('admission_source_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="partnerSelect" class="mt-2 d-none">
                    <select name="admission_source_id" class="form-select form-select-sm" disabled>
                        <option value="">Select Partner</option>
                        @foreach($partners as $p)
                        <option value="{{ $p->id }}" {{ (string) $pv('admission_source_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif
            @endif

            @if($fieldEnabled('gap_year'))
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Gap Year @if($fieldRequired('gap_year'))<span class="text-danger">*</span>@endif</label>
                <div class="d-flex gap-3 mt-1">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gap_year" value="0" {{ $pv('gap_year', '0') != '1' ? 'checked' : '' }}>
                        <label class="form-check-label small">No</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gap_year" value="1" {{ $pv('gap_year') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label small">Yes</label>
                    </div>
                </div>
            </div>
            @endif

            @if($fieldEnabled('admission_date'))
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Admission Date @if($fieldRequired('admission_date'))<span class="text-danger">*</span>@endif</label>
                <input type="date" name="admission_date" class="form-control form-control-sm" value="{{ $pv('admission_date', date('Y-m-d')) }}" {{ $fieldRequired('admission_date') ? 'required' : '' }}>
            </div>
            @endif

            @if($fieldEnabled('academic_session'))
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Academic Session</label>
                <input type="text" class="form-control form-control-sm bg-light" value="{{ $activeSession->name }}" readonly>
            </div>
            @endif

            @if(isset($feePlans) && $feePlans->isNotEmpty())
            <div class="col-md-4">
                <label class="form-label small fw-semibold">
                    <i class="bi bi-layers me-1 text-primary"></i>Fee Plan
                    <span class="text-muted fw-normal">(optional)</span>
                </label>
                <select name="fee_plan_id" id="feePlanSelect" class="form-select form-select-sm">
                    <option value="">— No Plan (Full Payment) —</option>
                    @foreach($feePlans as $fp)
                    <option value="{{ $fp->id }}"
                        data-course="{{ $fp->course_id ?? '' }}"
                        {{ $pv('fee_plan_id') == $fp->id ? 'selected' : '' }}>
                        {{ $fp->name }}
                        @if($fp->course_id) ({{ $fp->course->name ?? '' }}) @else (All Courses) @endif
                        — {{ $fp->installment_count }} installment{{ $fp->installment_count > 1 ? 's' : '' }}
                    </option>
                    @endforeach
                </select>
                <small class="text-muted">Student will pay in installments as per selected plan.</small>
            </div>
            @endif
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════ --}}
@php
    $transportSelected = (bool) old('transport_use', $pd['transport_use'] ?? false);
@endphp
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#334155; color:white;">
        <span class="fw-bold small"><i class="bi bi-bus-front me-2"></i>Transport Allocation</span>
    </div>
    <div class="card-body">
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="transport_use" id="transportUseToggle" value="1" {{ $transportSelected ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="transportUseToggle">Allocate transport for this student</label>
        </div>
        <div id="transportFields" class="{{ $transportSelected ? '' : 'd-none' }}">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Route @if($transportSelected)<span class="text-danger">*</span>@endif</label>
                    <select name="transport_route_id" id="transportRouteSelect" class="form-select form-select-sm" {{ $transportSelected ? 'required' : '' }}>
                        <option value="">Select Route</option>
                        @foreach($transportRoutes as $route)
                            <option value="{{ $route->id }}" {{ (string) old('transport_route_id', $pd['transport_route_id'] ?? '') === (string) $route->id ? 'selected' : '' }}>
                                {{ $route->name }} (₹{{ number_format((float) $route->fee_amount, 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Stop</label>
                    <select name="transport_route_stop_id" id="transportStopSelect" class="form-select form-select-sm">
                        <option value="">Select Stop</option>
                        @foreach($transportStops as $stop)
                            <option value="{{ $stop->id }}" {{ (string) old('transport_route_stop_id', $pd['transport_route_stop_id'] ?? '') === (string) $stop->id ? 'selected' : '' }}>
                                {{ $stop->route->name ?? 'Route' }} - {{ $stop->stop_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Vehicle</label>
                    <select name="transport_vehicle_id" class="form-select form-select-sm">
                        <option value="">Auto / Optional</option>
                        @foreach($transportVehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" {{ (string) old('transport_vehicle_id', $pd['transport_vehicle_id'] ?? '') === (string) $vehicle->id ? 'selected' : '' }}>
                                {{ $vehicle->vehicle_no }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Driver</label>
                    <select name="transport_driver_id" class="form-select form-select-sm">
                        <option value="">Auto / Optional</option>
                        @foreach($transportDrivers as $driver)
                            <option value="{{ $driver->id }}" {{ (string) old('transport_driver_id', $pd['transport_driver_id'] ?? '') === (string) $driver->id ? 'selected' : '' }}>
                                {{ $driver->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Transport Fee</label>
                    <input type="number" step="0.01" min="0" name="transport_fee_amount" class="form-control form-control-sm" value="{{ old('transport_fee_amount', $pd['transport_fee_amount'] ?? '') }}" placeholder="Auto from route">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Start Date</label>
                    <input type="date" name="transport_start_date" class="form-control form-control-sm" value="{{ old('transport_start_date', $pd['transport_start_date'] ?? date('Y-m-d')) }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="transport_charge_now" id="transportChargeNow" value="1" {{ old('transport_charge_now', $pd['transport_charge_now'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="transportChargeNow">Charge wallet now</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Transport Remarks</label>
                    <textarea name="transport_remarks" class="form-control form-control-sm" rows="2">{{ old('transport_remarks', $pd['transport_remarks'] ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- 5. PERSONAL DETAILS                     --}}
{{-- ═══════════════════════════════════════ --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b; color:white;">
        <span class="fw-bold small"><i class="bi bi-person me-2"></i>Personal Details</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @if($fieldEnabled('photo'))
            <div class="col-md-2 text-center">
                <label class="form-label small fw-semibold">Photo</label>
                <div class="border rounded p-2 text-center" style="height:90px; background:#f8fafc; cursor:pointer;"
                     onclick="document.getElementById('photoInput').click()">
                    <img id="photoPreview" src="{{ $photoTempUrl ?? '' }}" alt="" style="max-height:70px; {{ $photoTempUrl ? '' : 'display:none;' }}">
                    <div id="photoPlaceholder" style="{{ $photoTempUrl ? 'display:none;' : '' }}">
                        <i class="bi bi-camera text-muted" style="font-size:1.5rem;"></i>
                        <div class="text-muted" style="font-size:10px;">Click to upload</div>
                    </div>
                </div>
                @if($photoTemp)
                    <input type="hidden" name="photo_temp" value="{{ $photoTemp }}">
                @endif
                <input type="file" name="photo" id="photoInput" class="d-none"
                       accept="image/*" onchange="previewPhoto(this)">
                <div class="form-text small mt-2">
                    Photo auto-optimize hogi before preview submit.
                
                </div>
                <div id="photoUploadError" class="text-danger small mt-1" style="display:none;"></div>
            </div>
            <div class="col-md-10">
            @else
            <div class="col-12">
            @endif
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Student Name @if($fieldRequired('name'))<span class="text-danger">*</span>@endif</label>
                    <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror"
                           value="{{ $pv('name') }}" {{ $fieldRequired('name') ? 'required' : '' }}>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                @if($fieldEnabled('father_name'))
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Father Name @if($fieldRequired('father_name'))<span class="text-danger">*</span>@endif</label>
                    <input type="text" name="father_name" class="form-control form-control-sm" value="{{ $pv('father_name') }}" {{ $fieldRequired('father_name') ? 'required' : '' }}>
                </div>
                @endif
                @if($fieldEnabled('father_mobile'))
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Father Mobile @if($fieldRequired('father_mobile'))<span class="text-danger">*</span>@endif</label>
                    <input type="text" name="father_mobile" class="form-control form-control-sm" value="{{ $pv('father_mobile') }}" maxlength="15" {{ $fieldRequired('father_mobile') ? 'required' : '' }}>
                </div>
                @endif
                @if($fieldEnabled('mother_name'))
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Mother Name @if($fieldRequired('mother_name'))<span class="text-danger">*</span>@endif</label>
                    <input type="text" name="mother_name" class="form-control form-control-sm" value="{{ $pv('mother_name') }}" {{ $fieldRequired('mother_name') ? 'required' : '' }}>
                </div>
                @endif
                @if($fieldEnabled('mother_mobile'))
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Mother Mobile @if($fieldRequired('mother_mobile'))<span class="text-danger">*</span>@endif</label>
                    <input type="text" name="mother_mobile" class="form-control form-control-sm" value="{{ $pv('mother_mobile') }}" maxlength="15" {{ $fieldRequired('mother_mobile') ? 'required' : '' }}>
                </div>
                @endif
                @if($fieldEnabled('father_occupation'))
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Father Occupation @if($fieldRequired('father_occupation'))<span class="text-danger">*</span>@endif</label>
                    <input type="text" name="father_occupation" class="form-control form-control-sm" value="{{ $pv('father_occupation') }}" maxlength="100" {{ $fieldRequired('father_occupation') ? 'required' : '' }}>
                </div>
                @endif
                @if($fieldEnabled('mother_occupation'))
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Mother Occupation @if($fieldRequired('mother_occupation'))<span class="text-danger">*</span>@endif</label>
                    <input type="text" name="mother_occupation" class="form-control form-control-sm" value="{{ $pv('mother_occupation') }}" maxlength="100" {{ $fieldRequired('mother_occupation') ? 'required' : '' }}>
                </div>
                @endif
                @if($fieldEnabled('dob'))
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Date of Birth @if($fieldRequired('dob'))<span class="text-danger">*</span>@endif</label>
                    <input type="date" name="dob" class="form-control form-control-sm" value="{{ $pv('dob') }}" {{ $fieldRequired('dob') ? 'required' : '' }}>
                </div>
                @endif
                @if($fieldEnabled('gender'))
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Gender @if($fieldRequired('gender'))<span class="text-danger">*</span>@endif</label>
                    <select name="gender" class="form-select form-select-sm fee-param-field" {{ $fieldRequired('gender') ? 'required' : '' }}>
                        <option value="">Select</option>
                        <option value="male"   {{ $pv('gender')=='male'   ? 'selected':'' }}>Male</option>
                        <option value="female" {{ $pv('gender')=='female' ? 'selected':'' }}>Female</option>
                        <option value="other"  {{ $pv('gender')=='other'  ? 'selected':'' }}>Other</option>
                    </select>
                </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Mobile @if($fieldRequired('mobile'))<span class="text-danger">*</span>@endif</label>
                    <input type="text" name="mobile" class="form-control form-control-sm @error('mobile') is-invalid @enderror"
                           value="{{ $pv('mobile') }}" {{ $fieldRequired('mobile') ? 'required' : '' }} maxlength="15">
                    @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                @if($fieldEnabled('email'))
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Email @if($fieldRequired('email'))<span class="text-danger">*</span>@endif</label>
                    <input type="email" name="email" class="form-control form-control-sm" value="{{ $pv('email') }}" {{ $fieldRequired('email') ? 'required' : '' }}>
                </div>
                @endif
                @if($fieldEnabled('guardian_mobile'))
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Guardian Mobile @if($fieldRequired('guardian_mobile'))<span class="text-danger">*</span>@endif</label>
                    <input type="text" name="guardian_mobile" class="form-control form-control-sm" value="{{ $pv('guardian_mobile') }}" maxlength="15" {{ $fieldRequired('guardian_mobile') ? 'required' : '' }}>
                </div>
                @endif
                @if($fieldEnabled('guardian_name'))
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Guardian Name @if($fieldRequired('guardian_name'))<span class="text-danger">*</span>@endif</label>
                    <input type="text" name="guardian_name" class="form-control form-control-sm" value="{{ $pv('guardian_name') }}" maxlength="100" {{ $fieldRequired('guardian_name') ? 'required' : '' }}>
                </div>
                @endif
                @if($fieldEnabled('guardian_relation'))
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Guardian Relation @if($fieldRequired('guardian_relation'))<span class="text-danger">*</span>@endif</label>
                    <select name="guardian_relation" class="form-select form-select-sm" {{ $fieldRequired('guardian_relation') ? 'required' : '' }}>
                        <option value="">Select</option>
                        @foreach(['father','mother','uncle','aunt','brother','sister','grandfather','grandmother','others'] as $rel)
                        <option value="{{ $rel }}" {{ $pv('guardian_relation') == $rel ? 'selected' : '' }}>{{ ucfirst($rel) }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if($fieldEnabled('religion'))
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Religion @if($fieldRequired('religion'))<span class="text-danger">*</span>@endif</label>
                    <select name="religion" class="form-select form-select-sm" {{ $fieldRequired('religion') ? 'required' : '' }}>
                        <option value="">Select</option>
                        @foreach(['hindu','muslim','sikh','christian','jain','parsi','buddhist','others'] as $r)
                        <option value="{{ $r }}" {{ $pv('religion') == $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if($fieldEnabled('category'))
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Category @if($fieldRequired('category'))<span class="text-danger">*</span>@endif</label>
                    <select name="category" class="form-select form-select-sm fee-param-field" {{ $fieldRequired('category') ? 'required' : '' }}>
                        <option value="">Select</option>
                        @foreach(['gen','obc','sc','st','ews','others'] as $c)
                        <option value="{{ $c }}" {{ $pv('category')==$c ? 'selected':'' }}>{{ strtoupper($c) }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if($fieldEnabled('special_category'))
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Special Category @if($fieldRequired('special_category'))<span class="text-danger">*</span>@endif</label>
                    <select name="special_category" class="form-select form-select-sm" {{ $fieldRequired('special_category') ? 'required' : '' }}>
                        <option value="">Select</option>
                        <option value="none" {{ ($pv('special_category') ?: 'none')=='none' ? 'selected':'' }}>None / NA</option>
                        @foreach(['pwd','ex_serviceman','sports','ncc','others'] as $s)
                        <option value="{{ $s }}" {{ $pv('special_category')==$s ? 'selected':'' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if($fieldEnabled('nationality'))
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Nationality @if($fieldRequired('nationality'))<span class="text-danger">*</span>@endif</label>
                    <input type="text" name="nationality" class="form-control form-control-sm" value="{{ $pv('nationality', 'Indian') }}" {{ $fieldRequired('nationality') ? 'required' : '' }}>
                </div>
                @endif
                @if($fieldEnabled('aadhar_no'))
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Aadhar No. @if($fieldRequired('aadhar_no'))<span class="text-danger">*</span>@endif</label>
                    <input type="text" name="aadhar_no" class="form-control form-control-sm" maxlength="12" value="{{ $pv('aadhar_no') }}" {{ $fieldRequired('aadhar_no') ? 'required' : '' }}>
                </div>
                @endif
                @if($fieldEnabled('apaar_no'))
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">APAAR No. @if($fieldRequired('apaar_no'))<span class="text-danger">*</span>@endif</label>
                    <input type="text" name="apaar_no" class="form-control form-control-sm" value="{{ $pv('apaar_no') }}" {{ $fieldRequired('apaar_no') ? 'required' : '' }}>
                </div>
                @endif
                @if($fieldEnabled('student_type'))
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Student Type @if($fieldRequired('student_type'))<span class="text-danger">*</span>@endif</label>
                    <select name="student_type" class="form-select form-select-sm fee-param-field" {{ $fieldRequired('student_type') ? 'required' : '' }}>
                        @foreach($studentTypes as $st)
                        <option value="{{ $st->slug }}" {{ $pv('student_type', $studentTypes->first()?->slug) == $st->slug ? 'selected' : '' }}>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if($fieldEnabled('marital_status'))
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Marital Status @if($fieldRequired('marital_status'))<span class="text-danger">*</span>@endif</label>
                    <select name="marital_status" class="form-select form-select-sm" {{ $fieldRequired('marital_status') ? 'required' : '' }}>
                        <option value="single"   {{ $pv('marital_status','single')=='single'   ? 'selected' : '' }}>Single</option>
                        <option value="married"  {{ $pv('marital_status')=='married'           ? 'selected' : '' }}>Married</option>
                        <option value="divorced" {{ $pv('marital_status')=='divorced'          ? 'selected' : '' }}>Divorced</option>
                        <option value="widowed"  {{ $pv('marital_status')=='widowed'           ? 'selected' : '' }}>Widowed</option>
                    </select>
                </div>
                @endif

                {{-- SCHOLARSHIP --}}
                <div class="col-12">
                    <div class="border rounded p-3 bg-light">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <span class="fw-semibold small"><i class="bi bi-award me-1 text-warning"></i>Scholarship</span>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="has_scholarship"
                                           value="0" id="scholNo"
                                           {{ ($pv('has_scholarship','0') ?: '0') != '1' ? 'checked' : '' }}
                                           onchange="document.getElementById('scholDetails').style.display='none'">
                                    <label class="form-check-label small" for="scholNo">No</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="has_scholarship"
                                           value="1" id="scholYes"
                                           {{ $pv('has_scholarship','0')=='1' ? 'checked' : '' }}
                                           onchange="document.getElementById('scholDetails').style.display='block'">
                                    <label class="form-check-label small" for="scholYes">Yes</label>
                                </div>
                            </div>
                        </div>
                        <div id="scholDetails" style="display:{{ $pv('has_scholarship','0')=='1' ? 'block' : 'none' }};">
                            <div class="row g-2">
                                <div class="col-md-4"><label class="form-label small fw-semibold">Scholarship Name</label>
                                    <input type="text" name="scholarship_name" class="form-control form-control-sm" value="{{ $pv('scholarship_name') }}" placeholder="e.g. NSP, State Merit"></div>
                                <div class="col-md-3"><label class="form-label small fw-semibold">Type</label>
                                    <select name="scholarship_type" class="form-select form-select-sm">
                                        <option value="">— Select —</option>
                                        <option value="govt_central" {{ $pv('scholarship_type')=='govt_central' ? 'selected':'' }}>Central Govt.</option>
                                        <option value="govt_state"   {{ $pv('scholarship_type')=='govt_state'   ? 'selected':'' }}>State Govt.</option>
                                        <option value="university"   {{ $pv('scholarship_type')=='university'   ? 'selected':'' }}>University</option>
                                        <option value="institute"    {{ $pv('scholarship_type')=='institute'    ? 'selected':'' }}>Institute</option>
                                        <option value="private"      {{ $pv('scholarship_type')=='private'      ? 'selected':'' }}>Private / NGO</option>
                                        <option value="other"        {{ $pv('scholarship_type')=='other'        ? 'selected':'' }}>Other</option>
                                    </select></div>
                                <div class="col-md-5"><label class="form-label small fw-semibold">Awarding Authority</label>
                                    <input type="text" name="scholarship_authority" class="form-control form-control-sm" value="{{ $pv('scholarship_authority') }}" placeholder="e.g. UGC, State Welfare Dept."></div>
                                <div class="col-md-3"><label class="form-label small fw-semibold">Applied Date</label>
                                    <input type="date" name="scholarship_applied_date" class="form-control form-control-sm" value="{{ $pv('scholarship_applied_date') }}"></div>
                                <div class="col-md-3"><label class="form-label small fw-semibold">Amount (₹)</label>
                                    <input type="number" name="scholarship_amount" class="form-control form-control-sm" value="{{ $pv('scholarship_amount') }}" step="0.01" min="0"></div>
                                <div class="col-md-3"><label class="form-label small fw-semibold">Reference / App. No.</label>
                                    <input type="text" name="scholarship_ref_no" class="form-control form-control-sm" value="{{ $pv('scholarship_ref_no') }}" placeholder="Application No."></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════ --}}
{{-- 6. ADDRESS DETAILS                      --}}
{{-- ═══════════════════════════════════════ --}}
@php
    $showAddress = collect(['perm_village','perm_post','perm_thana','perm_district','perm_state','perm_pincode','comm_address'])
        ->contains(fn($k) => $fieldEnabled($k));
@endphp
@if($showAddress)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b; color:white;">
        <span class="fw-bold small"><i class="bi bi-geo-alt me-2"></i>Address Details</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @if($fieldEnabled('perm_village'))
            <div class="col-md-3"><label class="form-label small fw-semibold">Village/City @if($fieldRequired('perm_village'))<span class="text-danger">*</span>@endif</label>
            <input type="text" name="perm_village" class="form-control form-control-sm" value="{{ $pv('perm_village') }}" {{ $fieldRequired('perm_village') ? 'required' : '' }} oninput="onPermAddressChange()"></div>
            @endif
            @if($fieldEnabled('perm_post'))
            <div class="col-md-2"><label class="form-label small fw-semibold">Post @if($fieldRequired('perm_post'))<span class="text-danger">*</span>@endif</label>
            <input type="text" name="perm_post" class="form-control form-control-sm" value="{{ $pv('perm_post') }}" {{ $fieldRequired('perm_post') ? 'required' : '' }} oninput="onPermAddressChange()"></div>
            @endif
            @if($fieldEnabled('perm_thana'))
            <div class="col-md-2"><label class="form-label small fw-semibold">Thana @if($fieldRequired('perm_thana'))<span class="text-danger">*</span>@endif</label>
            <input type="text" name="perm_thana" class="form-control form-control-sm" value="{{ $pv('perm_thana') }}" {{ $fieldRequired('perm_thana') ? 'required' : '' }} oninput="onPermAddressChange()"></div>
            @endif
            @if($fieldEnabled('perm_state'))
            <div class="col-md-3"><label class="form-label small fw-semibold">State @if($fieldRequired('perm_state'))<span class="text-danger">*</span>@endif</label>
            <select name="perm_state" id="permStateSelect" class="form-select form-select-sm" {{ $fieldRequired('perm_state') ? 'required' : '' }} data-saved="{{ $pv('perm_state') }}">
                <option value="">— Select State —</option>
            </select></div>
            @endif
            @if($fieldEnabled('perm_district'))
            <div class="col-md-3"><label class="form-label small fw-semibold">District @if($fieldRequired('perm_district'))<span class="text-danger">*</span>@endif</label>
            <select name="perm_district" id="permDistrictSelect" class="form-select form-select-sm" {{ $fieldRequired('perm_district') ? 'required' : '' }} data-saved="{{ $pv('perm_district') }}">
                <option value="">— Select District —</option>
            </select></div>
            @endif
            @if($fieldEnabled('perm_pincode'))
            <div class="col-md-2"><label class="form-label small fw-semibold">Pin Code @if($fieldRequired('perm_pincode'))<span class="text-danger">*</span>@endif</label>
            <input type="text" name="perm_pincode" class="form-control form-control-sm" maxlength="6" value="{{ $pv('perm_pincode') }}" {{ $fieldRequired('perm_pincode') ? 'required' : '' }} oninput="onPermAddressChange()"></div>
            @endif
            @if($fieldEnabled('comm_address'))
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="comm_same_as_perm"
                           id="sameAddress" value="1"
                           {{ $pv('comm_same_as_perm', '1') ? 'checked' : '' }}
                           onchange="toggleCommAddress(this.checked)">
                    <label class="form-check-label small fw-semibold" for="sameAddress">
                        Communication address — same as above
                    </label>
                </div>
            </div>
            <div class="col-12" id="commAddressBox">
                <label class="form-label small fw-semibold">Communication Address @if($fieldRequired('comm_address'))<span class="text-danger">*</span>@endif</label>
                <textarea name="comm_address" id="commAddressField" rows="2" class="form-control form-control-sm" {{ $fieldRequired('comm_address') ? 'required' : '' }}>{{ $pv('comm_address') }}</textarea>
            </div>
            @endif
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════ --}}
{{-- 7. EDUCATION DETAILS                    --}}
{{-- ═══════════════════════════════════════ --}}
@php
    $eduFields = ['edu_10th','edu_12th','edu_graduation','edu_other'];
    $showEdu = collect($eduFields)
        ->contains(fn($k) => $fieldEnabled($k));
@endphp
@if($showEdu)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b; color:white;">
        <span class="fw-bold small"><i class="bi bi-mortarboard me-2"></i>Passed Exam Details</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light" style="font-size:11px;">
                    <tr>
                        <th>EXAM</th><th>STREAM</th><th>Institute Name</th><th>Roll No.</th><th>Year</th>
                        <th>District</th><th>Division</th><th>Board/University</th>
                        <th>Marks</th><th>Max</th><th>%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(['edu_10th'=>'10TH','edu_12th'=>'12TH','edu_graduation'=>'GRADUATION','edu_other'=>'OTHER'] as $fKey=>$examName)
                    @if($fieldEnabled($fKey))
                    @php
                        $oldEdu = old('education', []);
                        $eduRow = $oldEdu[$loop->index] ?? ($pd['education'][$loop->index] ?? []);
                    @endphp
                    <tr data-edu-key="{{ str_replace('edu_', '', $fKey) }}">
                        <td style="font-size:11px;font-weight:600;color:#1e40af;">{{ $examName }}</td>
                        <td>
                            @if($examName === '12TH')
                                <select name="education[{{ $loop->index }}][education_stream]" class="form-select form-select-sm" style="min-width:115px;">
                                    <option value="">—</option>
                                    @foreach(['MATHS','BIO','COMMERCE','ARTS','OTHER'] as $streamOption)
                                    <option value="{{ $streamOption }}" {{ ($eduRow['education_stream'] ?? '') === $streamOption ? 'selected' : '' }}>{{ $streamOption }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" name="education[{{ $loop->index }}][education_stream]" class="form-control form-control-sm" style="min-width:115px; text-transform:uppercase;" value="{{ $eduRow['education_stream'] ?? '' }}" {{ $fieldRequired($fKey) ? 'required' : '' }} oninput="this.value=this.value.toUpperCase()">
                            @endif
                        </td>
                        <td><input type="text" name="education[{{ $loop->index }}][institute_name]" class="form-control form-control-sm" style="min-width:120px; text-transform:uppercase;" value="{{ $eduRow['institute_name'] ?? '' }}" {{ $fieldRequired($fKey) ? 'required' : '' }} oninput="this.value=this.value.toUpperCase()">
                            <input type="hidden" name="education[{{ $loop->index }}][exam_name]" value="{{ $examName }}"></td>
                        <td><input type="text" name="education[{{ $loop->index }}][roll_number]" class="form-control form-control-sm" style="width:80px; text-transform:uppercase;" value="{{ $eduRow['roll_number'] ?? '' }}" {{ $fieldRequired($fKey) ? 'required' : '' }} oninput="this.value=this.value.toUpperCase()"></td>
                        <td><input type="text" name="education[{{ $loop->index }}][passing_year]" class="form-control form-control-sm" style="width:60px; text-transform:uppercase;" maxlength="4" value="{{ $eduRow['passing_year'] ?? '' }}" {{ $fieldRequired($fKey) ? 'required' : '' }} oninput="this.value=this.value.toUpperCase()"></td>
                        <td><input type="text" name="education[{{ $loop->index }}][district]" class="form-control form-control-sm" style="width:90px; text-transform:uppercase;" value="{{ $eduRow['district'] ?? '' }}" {{ $fieldRequired($fKey) ? 'required' : '' }} oninput="this.value=this.value.toUpperCase()"></td>
                        <td><select name="education[{{ $loop->index }}][division]" class="form-select form-select-sm" style="width:70px;">
                            <option value="">—</option>
                            @foreach(['I','II','III','pass','fail'] as $div)
                            <option value="{{ $div }}" {{ ($eduRow['division'] ?? '') == $div ? 'selected' : '' }}>{{ strtoupper($div) }}</option>
                            @endforeach
                        </select></td>
                        <td><input type="text" name="education[{{ $loop->index }}][board_university]" class="form-control form-control-sm" style="min-width:100px; text-transform:uppercase;" value="{{ $eduRow['board_university'] ?? '' }}" {{ $fieldRequired($fKey) ? 'required' : '' }} oninput="this.value=this.value.toUpperCase()"></td>
                        <td><input type="number" name="education[{{ $loop->index }}][obtained_marks]" class="form-control form-control-sm edu-obtained" style="width:65px;" min="0" oninput="calcPercent(this)" value="{{ $eduRow['obtained_marks'] ?? '' }}" {{ $fieldRequired($fKey) ? 'required' : '' }}></td>
                        <td><input type="number" name="education[{{ $loop->index }}][max_marks]" class="form-control form-control-sm edu-max" style="width:65px;" min="0" oninput="calcPercent(this)" value="{{ $eduRow['max_marks'] ?? '' }}" {{ $fieldRequired($fKey) ? 'required' : '' }}></td>
                        <td><input type="text" name="education[{{ $loop->index }}][percentage]" class="form-control form-control-sm edu-percent bg-light" style="width:55px;" readonly placeholder="Auto" value="{{ $eduRow['percentage'] ?? '' }}"></td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Submit --}}
<div class="d-flex gap-2 mb-5">
    <button type="submit" class="btn btn-primary px-5">
        <i class="bi bi-check-lg me-1"></i> Submit Admission
    </button>
    <a href="{{ isset($indexRoute) ? $indexRoute : route('admissions.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
</div>

</form>
@endif

@include('partials._india-geo')

@push('scripts')
{{-- TomSelect — tag-style multi-select dropdown --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
@include('institute.admission._live-validation-script')
<style>
.ts-wrapper.multi .ts-control { min-height: 38px; border-radius: 6px; }
.ts-wrapper.focus .ts-control  { border-color: #86b7fe; box-shadow: 0 0 0 .25rem rgba(13,110,253,.25); }
.ts-dropdown { z-index: 9999; }
</style>
<script>
// ── Course data ──────────────────────────────────────────────────────
@php
$courseStreamsData = $courses->mapWithKeys(function($c) {
    $parts = $c->parts
        ->sortBy('year_number')
        ->unique('year_number')
        ->values();

    return [$c->id => [
        'id'      => $c->id,
        'streams' => $c->streams->map(function($s) { return ['id' => $s->id, 'name' => $s->name]; })->values(),
        'parts'   => $parts->map(function($p) { return ['id' => $p->id, 'name' => $p->year_label, 'year' => $p->year_number]; })->values(),
    ]];
});
@endphp
const courseStreams = @json($courseStreamsData);
const courseTypeLevels = @json($courseTypes->pluck('education_level', 'id'));
const EDU_LEVEL_ROWS = {
    ug:          ['10th','12th','other'],
    pg:          ['10th','12th','graduation','other'],
    diploma:     ['10th','12th','other'],
    certificate: ['10th','other'],
    phd:         ['10th','12th','graduation','other'],
    other:       ['10th','12th','graduation','other'],
};
const ALL_EDU_ROWS = ['10th','12th','graduation','other'];

function updateEduRows(typeId) {
    const level = typeId ? courseTypeLevels[typeId] : null;
    const visible = (level && EDU_LEVEL_ROWS[level]) ? EDU_LEVEL_ROWS[level] : ALL_EDU_ROWS;
    document.querySelectorAll('tr[data-edu-key]').forEach(tr => {
        const show = visible.includes(tr.dataset.eduKey);
        tr.style.display = show ? '' : 'none';
        tr.querySelectorAll('input, select').forEach(el => {
            if (!show) {
                if (el.hasAttribute('required')) el.dataset.wasRequired = 'true';
                el.removeAttribute('required');
                el.setCustomValidity('');
                el.classList.remove('is-invalid', 'is-valid');
            } else if (el.dataset.wasRequired === 'true') {
                el.setAttribute('required', '');
            }
        });
    });
}

let currentStreamId   = null;
let currentYearNumber = 1;
let minorMin = 0, minorMax = 99;
let majorMin = 1, majorMax = 99;
let selectedMinors = new Set();
let feeTimer = null;

// ── Stream load ──────────────────────────────────────────────────────
function filterCoursesByType(typeId) {
    const courseSel = document.getElementById('courseSelect');
    const currentVal = courseSel.value;

    Array.from(courseSel.options).forEach(opt => {
        if (!opt.value) return;
        const matches = !typeId || opt.dataset.typeId === String(typeId);
        opt.hidden   = !matches;
        opt.disabled = !matches;
    });

    // Reset course if it no longer matches the selected type
    if (currentVal && courseSel.options[courseSel.selectedIndex]?.dataset.typeId !== String(typeId)) {
        courseSel.value = '';
        loadStreams('');
    }
    updateEduRows(typeId);
}

function loadStreams(courseId) {
    const streamSel = document.getElementById('streamSelect');
    const partSel   = document.getElementById('partSelect');

    streamSel.innerHTML = '<option value="">Select Stream</option>';
    partSel.innerHTML   = '<option value="">Select Stream First</option>';
    hideSubjectSection();
    hideFeePreview();

    if (!courseId || !courseStreams[courseId]) return;

    courseStreams[courseId].streams.forEach(s => {
        streamSel.innerHTML += `<option value="${s.id}">${s.name}</option>`;
    });
}

// ── Stream change — Change #2: stream select hone pe hi year/part show karo ──
function onStreamChange(streamId) {
    currentStreamId = streamId;
    const courseId  = document.getElementById('courseSelect').value;
    const partSel   = document.getElementById('partSelect');
    const partHint  = document.getElementById('partHint');

    partSel.innerHTML = '<option value="">— Select Year/Part —</option>';
    hideSubjectSection();
    hideFeePreview();

    // Seat check
    checkStreamSeats(streamId);

    if (!courseId || !courseStreams[courseId] || !streamId) return;

    const parts = [...courseStreams[courseId].parts]
        .sort((a, b) => (a.year || 0) - (b.year || 0))
        .slice(0, 1);
    partSel.innerHTML = '';
    parts.forEach(p => {
        partSel.innerHTML += `<option value="${p.id}" data-year="${p.year}">${p.name}</option>`;
    });

    // New admission by default hamesha 1st year/part me start hoga
    if (parts.length > 0) {
        partSel.value = parts[0].id;
        if (partHint) partHint.style.display = 'none';
        loadSubjectsAndFees();
    }
}

// ── Part/Year change — Change #3 ─────────────────────────────────────
function onPartChange() {
    const partHint = document.getElementById('partHint');
    if (partHint) partHint.style.display = 'none';
    loadSubjectsAndFees();
}

// ── Load subjects when part changes ──────────────────────────────────
function loadSubjectsAndFees() {
    const streamId = document.getElementById('streamSelect').value;
    const partSel  = document.getElementById('partSelect');
    if (!streamId) return;

    const selectedOpt = partSel.options[partSel.selectedIndex];
    currentYearNumber = parseInt(selectedOpt?.dataset?.year || 1);

    if (!currentYearNumber || !partSel.value) return;

    loadSubjects(streamId, currentYearNumber);
}

// Previously selected subjects (edit mode restore)
const preSelectedSubjects = @json($selectedSubjectsForRestore);
const preSelectedMajorSubjects = @json($selectedMajorSubjectsForRestore);
const preSelectedMinorSubjects = @json($selectedMinorSubjectsForRestore);

function loadSubjects(streamId, yearNumber) {
    document.getElementById('subjectSection').style.display = 'block';
    document.getElementById('subjectLoading').style.display = 'block';
    document.getElementById('subjectContent').innerHTML = '';
    hideFeePreview();

    fetch(`{{ $streamSubjectsUrl }}?stream_id=${streamId}&year_number=${yearNumber}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('subjectLoading').style.display = 'none';
        if (!data.success) return;

        minorMin = data.year_rule?.minor_min ?? 0;
        minorMax = data.year_rule?.minor_max ?? 99;
        majorMin = data.year_rule?.major_min ?? 1;
        majorMax = data.year_rule?.major_max ?? 99;
        selectedMinors = new Set();

        renderSubjects(data.subjects);
        triggerFeePreview();
    })
    .catch(() => {
        document.getElementById('subjectLoading').style.display = 'none';
    });
}

// ── Render subjects — Change #4: dropdowns with multi-select ─────────
function renderSubjects(subjects) {
    // Destroy existing TomSelect instances before re-rendering
    if (window.majorTS)  { window.majorTS.destroy();  window.majorTS  = null; }
    if (window.minorTS)  { window.minorTS.destroy();  window.minorTS  = null; }

    const compulsory    = subjects.filter(s => s.role === 'compulsory' || !s.is_chooseable);
    const majorSubjects = subjects.filter(s => s.role === 'major' || s.role === 'both');
    const minorSubjects = subjects.filter(s => s.role === 'minor' || s.role === 'optional' || s.role === 'both');
    const effectiveMinorPreselected = preSelectedMinorSubjects.filter(id => !preSelectedMajorSubjects.includes(id));

    let html = '';

    // ── Compulsory — hidden inputs + badge display ────────────────────
    if (compulsory.length) {
        html += `<div class="mb-3">
            <div class="fw-semibold small text-success mb-2">
                <i class="bi bi-check-circle-fill me-1"></i>Compulsory Subjects (Auto-included)
            </div>
            <div class="d-flex flex-wrap gap-2">`;
        compulsory.forEach(s => {
            html += `
            <div class="d-flex align-items-center gap-2 px-3 py-2 rounded border bg-success bg-opacity-10">
                <input type="hidden" name="selected_subjects[]" value="${s.id}">
                <div>
                    <span class="fw-semibold small">${s.name}</span>
                    ${s.code ? `<span class="text-muted small"> (${s.code})</span>` : ''}
                    <span class="badge bg-success ms-1" style="font-size:9px;">Compulsory</span>
                    ${s.has_practical ? '<span class="badge bg-warning text-dark ms-1" style="font-size:9px;">🔬 Practical</span>' : ''}
                </div>
            </div>`;
        });
        html += `</div></div>`;
    }

    // ── Major — TomSelect tag dropdown ───────────────────────────────
    if (majorSubjects.length) {
        const majorInfo = majorMax < 99 ? `Min ${majorMin}, Max ${majorMax}` : 'Choose major subjects';
        html += `<div class="mb-3">
            <label class="form-label small fw-semibold text-primary">
                <i class="bi bi-star-fill me-1"></i>Select Major Subject(s)
                <span class="fw-normal text-muted">(${majorInfo})</span>
            </label>
            <select id="majorSelect" name="selected_major_subjects[]" multiple placeholder="Select major subject...">`;
        majorSubjects.forEach(s => {
            const sel = (!preSelectedMajorSubjects.length && s.role === 'major') ? 'selected' : '';
            html += `<option value="${s.id}" ${sel}>${s.name}${s.code ? ' (' + s.code + ')' : ''}${s.has_practical ? ' 🔬' : ''}</option>`;
        });
        html += `</select></div>`;
    }

    // ── Minor / Optional — TomSelect tag dropdown ─────────────────────
    if (minorSubjects.length) {
        const minorInfo = minorMax > 0 ? `Max ${minorMax} minor` : 'Optional';
        html += `<div class="mb-2">
            <label class="form-label small fw-semibold text-info">
                <i class="bi bi-list-check me-1"></i>Select Minor Subject(s)
                <span class="fw-normal text-muted">(${minorInfo})</span>
            </label>
            <div id="minorCountBadge" class="mb-1"></div>
            <select id="minorSelect" name="selected_minor_subjects[]" multiple placeholder="Select minor subject...">`;
        minorSubjects.forEach(s => {
            html += `<option value="${s.id}">${s.name}${s.code ? ' (' + s.code + ')' : ''}${s.has_practical ? ' 🔬' : ''}</option>`;
        });
        html += `</select></div>`;

        document.getElementById('minorCountInfo').textContent = minorInfo;
    }

    document.getElementById('subjectContent').innerHTML = html;

    // ── TomSelect initialize — Major ──────────────────────────────────
    const majorEl = document.getElementById('majorSelect');
    if (majorEl) {
        window.majorTS = new TomSelect('#majorSelect', {
            plugins: ['remove_button'],
            maxItems: majorMax < 99 ? majorMax : null,
            maxOptions: 100,
            placeholder: 'Select major subject...',
            onItemAdd:    () => { clearSubjectError(); triggerFeePreview(); syncMajorMinorExclusion(); },
            onItemRemove: () => { triggerFeePreview(); syncMajorMinorExclusion(); },
            render: {
                option: (data, escape) => `<div>${escape(data.text)}</div>`,
                item:   (data, escape) => `<div>${escape(data.text)}</div>`,
            }
        });
        // Restore previously selected major subjects (edit mode)
        if (preSelectedMajorSubjects.length) {
            const majorOptions = majorSubjects.map(s => String(s.id));
            const toSelect = preSelectedMajorSubjects.filter(id => majorOptions.includes(String(id)));
            if (toSelect.length) window.majorTS.setValue(toSelect.map(String));
        }
    }

    // ── TomSelect initialize — Minor ──────────────────────────────────
    const minorEl = document.getElementById('minorSelect');
    if (minorEl) {
        window.minorTS = new TomSelect('#minorSelect', {
            plugins: ['remove_button'],
            maxItems: minorMax > 0 ? minorMax : null,
            maxOptions: 100,
            placeholder: 'Select minor subject...',
            onItemAdd: () => {
                selectedMinors = new Set([...window.minorTS.getValue()].map(Number));
                updateMinorCount();
                clearSubjectError();
                triggerFeePreview();
            },
            onItemRemove: () => {
                selectedMinors = new Set([...window.minorTS.getValue()].map(Number));
                updateMinorCount();
                triggerFeePreview();
            },
        });
        // Restore previously selected minor subjects (edit mode)
        if (effectiveMinorPreselected.length) {
            const minorOptions = minorSubjects.map(s => String(s.id));
            const toSelect = effectiveMinorPreselected.filter(id => minorOptions.includes(String(id)));
            if (toSelect.length) {
                window.minorTS.setValue(toSelect.map(String));
                selectedMinors = new Set(toSelect);
                updateMinorCount();
            }
        }
    }

    updateMinorCount();
    if (window.majorTS && window.minorTS) syncMajorMinorExclusion();

    // Show backend subject validation error (page reload after server rejection)
    if (window.__subjectBackendError) {
        showSubjectError(window.__subjectBackendError);
    }
}

// ── Minor dropdown change ─────────────────────────────────────────────
function onMinorSelectChange(selectEl) {
    const chosen = [...selectEl.selectedOptions].map(o => parseInt(o.value));

    // Max limit enforce karo
    if (minorMax > 0 && chosen.length > minorMax) {
        // Naya selected deselect karo (last selected)
        [...selectEl.options].forEach(opt => {
            if (opt.selected && !selectedMinors.has(parseInt(opt.value))) {
                opt.selected = false;
            }
        });
        // Recalculate
        selectedMinors = new Set([...selectEl.selectedOptions].map(o => parseInt(o.value)));
    } else {
        selectedMinors = new Set(chosen);
    }

    updateMinorCount();
    triggerFeePreview();
}

// ── Minor subject change (checkbox fallback) ──────────────────────────
function onMinorChange(checkbox) {
    if (checkbox.checked) {
        selectedMinors.add(parseInt(checkbox.value));
    } else {
        selectedMinors.delete(parseInt(checkbox.value));
    }
    updateMinorCount();
    triggerFeePreview();
}

// Major mein selected subjects Minor se exclude karo
function syncMajorMinorExclusion() {
    if (!window.majorTS || !window.minorTS) return;
    const selectedMajors = window.majorTS.getValue();

    window.minorTS.getValue().forEach(val => {
        if (selectedMajors.includes(val)) {
            window.minorTS.removeItem(val, true);
        }
    });

    Object.keys(window.minorTS.options).forEach(val => {
        const opt = { ...window.minorTS.options[val] };
        if (selectedMajors.includes(val)) {
            window.minorTS.updateOption(val, { ...opt, disabled: true });
        } else {
            window.minorTS.updateOption(val, { ...opt, disabled: false });
        }
    });
    window.minorTS.refreshOptions(false);
    selectedMinors = new Set([...window.minorTS.getValue()].map(Number));
    updateMinorCount();
}

function updateMinorCount() {
    const badge = document.getElementById('minorCountBadge');
    if (!badge) return;

    const count   = window.minorTS ? window.minorTS.getValue().length : selectedMinors.size;
    const tooFew  = minorMin > 0 && count < minorMin;
    const tooMany = minorMax > 0 && minorMax < 99 && count > minorMax;
    const color   = (tooFew || tooMany) ? (tooMany ? 'bg-danger' : 'bg-warning text-dark') : 'bg-success';
    const minNote = minorMin > 0 ? ` (min ${minorMin} required)` : '';

    badge.innerHTML = `<span class="badge ${color}">${count} / ${minorMax > 0 && minorMax < 99 ? minorMax : '∞'} minor selected${minNote}</span>`;
}

function showSubjectError(msg) {
    const box  = document.getElementById('subjectErrorFeedback');
    const text = document.getElementById('subjectErrorText');
    if (!box || !text) return;
    text.textContent = msg;
    box.classList.remove('d-none');
    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function clearSubjectError() {
    const box = document.getElementById('subjectErrorFeedback');
    if (box) box.classList.add('d-none');
}

// ── Fee Preview — disabled in create form (shown on Preview page instead) ──
function triggerFeePreview() { /* Fee preview moved to preview page */ }
function fetchFeePreview()   { /* Fee preview moved to preview page */ }
function renderFeePreview()  { /* Fee preview moved to preview page */ }
function hideFeePreview()    {
    const s = document.getElementById('feePreviewSection');
    if (s) s.style.display = 'none';
}

// ── Seat availability check ──────────────────────────────────────────
function checkStreamSeats(streamId) {
    const infoDiv   = document.getElementById('seatInfo');
    const submitBtn = document.querySelector('button[type="submit"]');
    if (!streamId) { if (infoDiv) infoDiv.innerHTML = ''; return; }

    fetch(`{{ $streamSeatsUrl }}?stream_id=${streamId}`)
        .then(r => r.json())
        .then(data => {
            if (!infoDiv) return;
            if (!data.limit) {
                infoDiv.innerHTML = '<span class="text-muted">Unlimited seats</span>';
                if (submitBtn) submitBtn.disabled = false;
                return;
            }
            const pct   = Math.min(100, Math.round((data.filled / data.limit) * 100));
            const color = data.remaining <= 0 ? 'danger' : (data.remaining <= 5 ? 'warning' : 'success');
            infoDiv.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-fill" style="height:6px;">
                        <div class="progress-bar bg-${color}" style="width:${pct}%"></div>
                    </div>
                    <span class="text-${color} fw-semibold">
                        ${data.remaining <= 0
                            ? '⛔ Seats Full!'
                            : `${data.remaining} / ${data.limit} seats remaining`}
                    </span>
                </div>`;
            if (submitBtn) submitBtn.disabled = data.remaining <= 0;
        })
        .catch(() => { if (infoDiv) infoDiv.innerHTML = ''; });
}

function hideSubjectSection() {
    document.getElementById('subjectSection').style.display = 'none';
    document.getElementById('subjectContent').innerHTML = '';
}

// ── Admission Source toggle ──────────────────────────────────────────
function toggleSourceSelect(val) {
    const centerDiv  = document.getElementById('centerSelect');
    const partnerDiv = document.getElementById('partnerSelect');
    const centerSel  = centerDiv?.querySelector('select');
    const partnerSel = partnerDiv?.querySelector('select');

    centerDiv?.classList.add('d-none');
    partnerDiv?.classList.add('d-none');
    if (centerSel)  centerSel.disabled  = true;
    if (partnerSel) partnerSel.disabled = true;

    if (val === 'center') {
        centerDiv?.classList.remove('d-none');
        if (centerSel) centerSel.disabled = false;
    } else if (val === 'channel_partner') {
        partnerDiv?.classList.remove('d-none');
        if (partnerSel) partnerSel.disabled = false;
    }
}

// ── Education % auto-calc ────────────────────────────────────────────
function toggleTransportFields() {
    const toggle = document.getElementById('transportUseToggle');
    const box = document.getElementById('transportFields');
    const routeSelect = document.getElementById('transportRouteSelect');

    if (!toggle || !box) return;

    if (toggle.checked) {
        box.classList.remove('d-none');
        if (routeSelect) routeSelect.required = true;
    } else {
        box.classList.add('d-none');
        if (routeSelect) routeSelect.required = false;
    }
}

document.getElementById('transportUseToggle')?.addEventListener('change', toggleTransportFields);
document.getElementById('transportRouteSelect')?.addEventListener('change', function () {
    loadTransportStops(this.value, document.getElementById('transportStopSelect')?.value || '');
});
document.addEventListener('DOMContentLoaded', toggleTransportFields);

async function loadTransportStops(routeId, selectedStopId = '') {
    const stopSelect = document.getElementById('transportStopSelect');
    if (!stopSelect) return;

    stopSelect.innerHTML = '<option value="">Loading stops...</option>';

    if (!routeId) {
        stopSelect.innerHTML = '<option value="">Select Stop</option>';
        return;
    }

    try {
        const url = `{{ $transportStopsUrl }}`.replace('__ROUTE__', routeId);
        const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const payload = await response.json();
        const stops = Array.isArray(payload?.stops) ? payload.stops : [];

        stopSelect.innerHTML = ['<option value="">Select Stop</option>']
            .concat(stops.map((stop) => {
                const label = stop.landmark ? `${stop.stop_name} - ${stop.landmark}` : stop.stop_name;
                const selected = String(selectedStopId) === String(stop.id) ? ' selected' : '';
                return `<option value="${stop.id}"${selected}>${label}</option>`;
            }))
            .join('');
    } catch (error) {
        stopSelect.innerHTML = '<option value="">Failed to load stops</option>';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const routeSelect = document.getElementById('transportRouteSelect');
    if (routeSelect?.value) {
        loadTransportStops(routeSelect.value, document.getElementById('transportStopSelect')?.value || '');
    }
});

function calcPercent(input) {
    if (parseFloat(input.value) < 0) input.value = 0;
    const row      = input.closest('tr');
    const obtained = Math.max(0, parseFloat(row.querySelector('.edu-obtained')?.value) || 0);
    const max      = Math.max(0, parseFloat(row.querySelector('.edu-max')?.value) || 0);
    const pct      = row.querySelector('.edu-percent');
    if (obtained && max && pct) pct.value = max > 0 ? ((obtained / max) * 100).toFixed(2) : '';
}

// ── Photo preview ────────────────────────────────────────────────────
function previewPhoto(input) {
    const errorBox = document.getElementById('photoUploadError');
    if (errorBox) {
        errorBox.style.display = 'none';
        errorBox.textContent = '';
    }

    if (input.files?.[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('photoPreview').src = e.target.result;
            document.getElementById('photoPreview').style.display = 'block';
            document.getElementById('photoPlaceholder').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

async function compressImageFile(file, {
    maxWidth = 1200,
    maxHeight = 1200,
    targetBytes = 350 * 1024,
    maxBytes = 900 * 1024,
} = {}) {
    const dataUrl = await new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = () => reject(new Error('Image read failed'));
        reader.readAsDataURL(file);
    });

    const image = await new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = () => reject(new Error('Image load failed'));
        img.src = dataUrl;
    });

    let { width, height } = image;
    const ratio = Math.min(maxWidth / width, maxHeight / height, 1);
    width = Math.round(width * ratio);
    height = Math.round(height * ratio);

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;

    const ctx = canvas.getContext('2d', { alpha: false });
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, width, height);
    ctx.drawImage(image, 0, 0, width, height);

    let quality = 0.85;
    let blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', quality));

    while (blob && blob.size > targetBytes && quality > 0.4) {
        quality -= 0.1;
        blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', quality));
    }

    if (!blob) {
        throw new Error('Image compression failed');
    }

    if (blob.size > maxBytes) {
        throw new Error('Selected image is still too large after optimization. Please choose a smaller photo.');
    }

    return new File(
        [blob],
        (file.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg',
        { type: 'image/jpeg', lastModified: Date.now() }
    );
}

async function prepareAdmissionPhotoForSubmit(form) {
    const input = form.querySelector('#photoInput');
    const errorBox = document.getElementById('photoUploadError');

    if (!input?.files?.length) {
        return true;
    }

    const originalFile = input.files[0];

    if (!originalFile.type.startsWith('image/')) {
        return true;
    }

    if (originalFile.size <= 350 * 1024) {
        return true;
    }

    try {
        const optimizedFile = await compressImageFile(originalFile);
        const dt = new DataTransfer();
        dt.items.add(optimizedFile);
        input.files = dt.files;
        previewPhoto(input);
        return true;
    } catch (error) {
        if (errorBox) {
            errorBox.textContent = error.message || 'Photo could not be optimized.';
            errorBox.style.display = 'block';
        }
        return false;
    }
}

// ── Communication address checkbox ───────────────────────────────────
function buildPermAddress() {
    const village  = document.querySelector('[name="perm_village"]')?.value?.trim()  || '';
    const post     = document.querySelector('[name="perm_post"]')?.value?.trim()     || '';
    const thana    = document.querySelector('[name="perm_thana"]')?.value?.trim()    || '';
    const district = document.querySelector('[name="perm_district"]')?.value?.trim() || '';
    const state    = document.querySelector('[name="perm_state"]')?.value?.trim()    || '';
    const pincode  = document.querySelector('[name="perm_pincode"]')?.value?.trim()  || '';

    return [village, post, thana, district, state, pincode]
        .filter(Boolean)
        .join(', ');
}

function toggleCommAddress(checked) {
    const textarea = document.getElementById('commAddressField');
    if (!textarea) return;

    if (checked) {
        textarea.value    = buildPermAddress();
        textarea.readOnly = true;
        textarea.classList.add('bg-light');
    } else {
        textarea.readOnly = false;
        textarea.classList.remove('bg-light');
        textarea.value = '';
        textarea.focus();
    }
}

// Permanent address fields change hone pe comm_address auto-update karo (jab checkbox checked ho)
function onPermAddressChange() {
    const cb = document.getElementById('sameAddress');
    if (cb && cb.checked) toggleCommAddress(true);
}

// ── Part/Year change ─────────────────────────────────────────────────
document.getElementById('partSelect').addEventListener('change', onPartChange);

// ── Restore course/stream/part/subjects on edit (previewData) ────────
window.addEventListener('DOMContentLoaded', function() {
    const admissionForm = document.getElementById('admissionForm');
    if (admissionForm) {
        let isSubmitting = false;
        window.admissionLiveValidation?.initForm(admissionForm);

        admissionForm.addEventListener('submit', async function(event) {
            if (isSubmitting) {
                return;
            }

            event.preventDefault();
            hideJsError();

            const _validationResult = window.admissionLiveValidation?.validateForm(admissionForm, { report: true });
            if (!_validationResult) {
                // All invalid fields (including hidden) — for label extraction
                const allInvalid = [...admissionForm.querySelectorAll('.is-invalid, :invalid')]
                    .filter(el => el.tagName !== 'FORM');
                // Only visible invalid fields — for scroll target
                const visibleInvalid = allInvalid.filter(el => el.offsetParent !== null);

                const fieldNames = [];
                allInvalid.forEach(el => {
                    // Strategy 1: native labels API (most accurate)
                    let labelText = (el.labels && el.labels.length) ? el.labels[0].innerText : '';
                    // Strategy 2: label[for=id]
                    if (!labelText && el.id) {
                        const lbl = admissionForm.querySelector(`label[for="${el.id}"]`);
                        if (lbl) labelText = lbl.innerText;
                    }
                    // Strategy 3: nearest sibling / parent label
                    if (!labelText) {
                        const col = el.closest('[class*="col-"]') || el.parentElement;
                        const lbl = col ? col.querySelector('label') : null;
                        if (lbl) labelText = lbl.innerText;
                    }
                    // Strategy 4: name attribute as last resort
                    if (!labelText) labelText = (el.getAttribute('name') || '').replace(/[\[\]]/g, ' ');

                    const raw = labelText.replace(/\s*\*\s*/g, '').replace(/\s+/g, ' ').trim();
                    if (raw && !fieldNames.includes(raw)) fieldNames.push(raw);
                });

                const msg = fieldNames.length
                    ? 'Please fill: ' + fieldNames.slice(0, 5).join(', ') + (fieldNames.length > 5 ? ` (+${fieldNames.length - 5} more)` : '')
                    : 'Some required fields are missing — please scroll down and check all highlighted fields.';

                showJsError(msg, visibleInvalid[0]);

                // Native browser fallback — agar scroll target nahi mila toh browser khud dikhayega
                if (!visibleInvalid.length) {
                    setTimeout(() => { try { admissionForm.reportValidity(); } catch(e) {} }, 150);
                }
                return;
            }

            if (window.majorTS && majorMin > 0) {
                const majorCount = window.majorTS.getValue().length;
                if (majorCount < majorMin) {
                    showSubjectError(`At least ${majorMin} major subject(s) must be selected. (Currently ${majorCount} selected)`);
                    showJsError(`Please select at least ${majorMin} major subject(s).`);
                    return;
                }
            }

            if (window.minorTS && minorMin > 0) {
                const minorCount = window.minorTS.getValue().length;
                if (minorCount < minorMin) {
                    updateMinorCount();
                    showSubjectError(`At least ${minorMin} minor subject(s) must be selected. (Currently ${minorCount} selected)`);
                    showJsError(`Please select at least ${minorMin} minor subject(s).`);
                    return;
                }
            }

            clearSubjectError();

            const canContinue = await prepareAdmissionPhotoForSubmit(admissionForm);
            if (!canContinue) {
                showJsError('Photo upload failed — please check the photo section.');
                return;
            }

            isSubmitting = true;
            admissionForm.submit();
        });
    }

    // Comm address checkbox initial state
    const sameAddrCb = document.getElementById('sameAddress');
    if (sameAddrCb) toggleCommAddress(sameAddrCb.checked);

    // Restore course type filter first
    @if(old('course_type_id', $pd['course_type_id'] ?? null))
    filterCoursesByType(@json(old('course_type_id', $pd['course_type_id'] ?? '')));
    @endif

    @if(old('course_id', $pd['course_id'] ?? null))
    const oldCourse = @json(old('course_id', $pd['course_id'] ?? ''));
    const oldStream = @json(old('course_stream_id', $pd['course_stream_id'] ?? ''));
    const oldPart   = @json(old('course_part_id', $pd['course_part_id'] ?? ''));

    if (oldCourse) {
        const courseEl = document.getElementById('courseSelect');
        if (courseEl) courseEl.value = oldCourse;
        loadStreams(oldCourse);

        setTimeout(function() {
            const streamEl = document.getElementById('streamSelect');
            if (streamEl && oldStream) {
                streamEl.value = oldStream;
                onStreamChange(oldStream);
            }
            setTimeout(function() {
                const partEl = document.getElementById('partSelect');
                if (partEl && oldPart) {
                    partEl.value = oldPart;
                    loadSubjectsAndFees();
                }
            }, 400);
        }, 300);
    }

    @endif

    // Restore admission source — always run so dropdown visibility matches checked radio
    const oldSource = @json(old('admission_source', $pd['admission_source'] ?? 'direct'));
    if (oldSource && oldSource !== 'direct') {
        const radios = document.querySelectorAll('input[name="admission_source"]');
        radios.forEach(r => { if (r.value === oldSource) r.checked = true; });
        toggleSourceSelect(oldSource);
    } else {
        toggleSourceSelect('direct');
    }

    // ── Auto-scroll to server errors on page load ─────────────────────
    const serverErr = document.getElementById('serverErrorBlock');
    if (serverErr) {
        serverErr.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
});

// ── JS validation error helpers ───────────────────────────────────────
function showJsError(msg, scrollTarget) {
    const banner = document.getElementById('jsValidationError');
    const msgEl  = document.getElementById('jsValidationErrorMsg');
    if (!banner) return;
    if (msgEl) msgEl.textContent = msg || 'Some fields have errors — please check the highlighted fields.';
    banner.style.setProperty('display', 'block', 'important');
    // Scroll to the invalid field (banner is sticky so stays visible at top while scrolling)
    const target = scrollTarget
        || document.querySelector('#admissionForm .is-invalid')
        || document.querySelector('#admissionForm :invalid:not(form)');
    if (target) {
        setTimeout(function () {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            // Flash a red outline so user clearly sees the field
            target.style.outline = '2px solid #dc3545';
            setTimeout(() => { target.style.outline = ''; }, 2500);
        }, 80);
    }
}

function hideJsError() {
    const banner = document.getElementById('jsValidationError');
    if (banner) banner.style.setProperty('display', 'none', 'important');
}

// ── Form draft — sessionStorage pe save karo, refresh pe restore ─────
(function () {
    const DRAFT_KEY = 'adm_full_{{ auth()->id() ?? 0 }}';
    const HAS_PHP_DATA = @json(!empty(old()) || !empty($pd));
    const CASCADE = new Set(['course_type_id','course_id','course_stream_id','course_part_id']);

    function saveDraft() {
        const form = document.getElementById('admissionForm');
        if (!form) return;
        const data = {};
        form.querySelectorAll('input:not([type="file"]):not([type="hidden"]), select, textarea').forEach(el => {
            if (!el.name || el.name === '_token' || el.readOnly) return;
            if (el.type === 'radio' || el.type === 'checkbox') {
                if (el.checked) data[el.name] = el.value;
            } else if (data[el.name] === undefined) {
                data[el.name] = el.value;
            }
        });
        try { sessionStorage.setItem(DRAFT_KEY, JSON.stringify(data)); } catch(_) {}
    }

    function clearDraft() { try { sessionStorage.removeItem(DRAFT_KEY); } catch(_) {} }

    function restoreDraft() {
        if (HAS_PHP_DATA) return;
        // Only restore on browser reload (F5), not on fresh navigation
        const navType = (performance.getEntriesByType?.('navigation') ?? [])[0]?.type;
        if (navType !== 'reload') { clearDraft(); return; }
        let data;
        try { data = JSON.parse(sessionStorage.getItem(DRAFT_KEY) || 'null'); } catch(_) { return; }
        if (!data || !Object.keys(data).length) return;

        const form = document.getElementById('admissionForm');
        if (!form) return;

        // Restore all simple (non-cascade) fields
        form.querySelectorAll('input:not([type="file"]):not([type="hidden"]), select, textarea').forEach(el => {
            if (!el.name || CASCADE.has(el.name) || el.name === '_token' || el.readOnly) return;
            if (data[el.name] === undefined) return;
            if (el.type === 'radio' || el.type === 'checkbox') {
                el.checked = (el.value === data[el.name]);
            } else {
                el.value = data[el.name];
            }
        });

        // Re-trigger visibility toggles
        const src = data['admission_source'];
        if (src && typeof toggleSourceSelect === 'function') toggleSourceSelect(src);
        const schEl = form.querySelector('[name="has_scholarship"]');
        if (schEl && schEl.checked && typeof toggleScholarship === 'function') toggleScholarship(true);
        const transportEl = form.querySelector('[name="transport_use"]');
        if (transportEl) transportEl.dispatchEvent(new Event('change'));

        // Cascade: course type → course → stream → part
        const typeId  = data['course_type_id'];
        const courseId = data['course_id'];
        const streamId = data['course_stream_id'];
        const partId   = data['course_part_id'];

        if (typeId) {
            const el = form.querySelector('[name="course_type_id"]');
            if (el) { el.value = typeId; filterCoursesByType(typeId); }
        }
        if (courseId) {
            const el = form.querySelector('[name="course_id"]');
            if (el) { el.value = courseId; loadStreams(courseId); }
            setTimeout(() => {
                if (streamId) {
                    const el = form.querySelector('[name="course_stream_id"]');
                    if (el) { el.value = streamId; onStreamChange(streamId); }
                }
                setTimeout(() => {
                    if (partId) {
                        const el = form.querySelector('[name="course_part_id"]');
                        if (el) { el.value = partId; loadSubjectsAndFees(); }
                    }
                }, 400);
            }, 300);
        }
    }

    let draftTimer = null;
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(restoreDraft, 80);
        const form = document.getElementById('admissionForm');
        if (!form) return;
        form.addEventListener('input',  () => { clearTimeout(draftTimer); draftTimer = setTimeout(saveDraft, 600); }, true);
        form.addEventListener('change', () => { clearTimeout(draftTimer); draftTimer = setTimeout(saveDraft, 600); }, true);
        form.addEventListener('submit', () => { clearTimeout(draftTimer); clearDraft(); }, { once: true });
    });
})();

// ── Auto-uppercase all text inputs ───────────────────────────────────
(function () {
    const skipFields = ['email','aadhar_no','apaar_no','mobile','father_mobile','mother_mobile','guardian_mobile','photo_temp'];
    const form = document.getElementById('admissionForm');
    if (!form) return;
    form.querySelectorAll('input[type="text"]').forEach(function(el) {
        const n = el.name || '';
        if (skipFields.some(s => n === s || n.endsWith('[' + s + ']'))) return;
        el.style.textTransform = 'uppercase';
        if (el.value) el.value = el.value.toUpperCase();
        el.addEventListener('input', function() { this.value = this.value.toUpperCase(); });
    });
    const commAddr = form.querySelector('textarea[name="comm_address"]');
    if (commAddr) {
        commAddr.style.textTransform = 'uppercase';
        if (commAddr.value) commAddr.value = commAddr.value.toUpperCase();
        commAddr.addEventListener('input', function() { this.value = this.value.toUpperCase(); });
    }
})();
</script>
@endpush
