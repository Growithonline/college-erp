@php
    $isStaff = auth()->guard('staff')->check();
    $layout = $isStaff ? 'staff.layout' : 'institute.layout';
    $showRoute = $isStaff ? 'staff.admissions.show' : 'admissions.show';
    $updateRoute = $isStaff ? 'staff.admissions.update' : 'admissions.update';
    $streamSubjectsRoute = $isStaff ? 'staff.admissions.stream-subjects' : 'admissions.stream-subjects';
    $feePreviewRoute = $isStaff ? 'staff.admissions.fee-preview' : 'admissions.fee-preview';
    $fieldEnabled = fn($key) => (bool) (($formConfig[$key]['enabled'] ?? false) && ($formConfig[$key]['section_enabled'] ?? true));
    $fieldRequired = fn($key) => (bool) ($fieldEnabled($key) && ($formConfig[$key]['required'] ?? false));
    $selectedCourseId = old('course_id', $student->stream?->course_id);
    $selectedStreamId = old('course_stream_id', $student->course_stream_id);
    $selectedPartId = old('course_part_id', $student->course_part_id);
    $currentSessionSubjects = $student->studentSubjects
        ->where('academic_session_id', $student->academic_session_id)
        ->values();
    $selectedSubjectIds = array_map('intval', old('selected_subjects', $currentSessionSubjects
        ->pluck('subject_id')
        ->all()));
    $selectedMajorIds = array_map('intval', old('selected_major_subjects', $currentSessionSubjects
        ->filter(fn($row) => in_array($row->subject_role, ['major', 'both'], true))
        ->pluck('subject_id')
        ->all()));
    $selectedMinorIds = array_map('intval', old('selected_minor_subjects', $currentSessionSubjects
        ->filter(fn($row) => in_array($row->subject_role, ['minor', 'optional'], true))
        ->pluck('subject_id')
        ->all()));
    $educationFieldMap = [
        'edu_10th' => '10TH',
        'edu_12th' => '12TH',
        'edu_graduation' => 'Graduation',
        'edu_other' => 'Other',
    ];
    $showEducation = collect(array_keys($educationFieldMap))
        ->contains(fn($key) => $formConfig[$key]['enabled'] ?? false)
        || $student->educationDetails->isNotEmpty()
        || !empty(old('education', []));
    $educationRows = $student->educationDetails
        ? $student->educationDetails->keyBy(fn($row) => strtolower(trim((string) $row->exam_name)))
        : collect();
    $showOffice = collect([
        'form_no', 'sr_no', 'enrollment_no', 'roll_no', 'exam_form_no', 'uin_no', 'reference_no',
        'admission_type', 'admission_source', 'gap_year', 'admission_date', 'submitted_date', 'academic_session',
    ])->contains(fn($key) => $fieldEnabled($key));
    $formNoValue = old('form_no',
        $student->currentAcademicIdentity?->form_no
        ?? (str_contains((string) $student->student_uid, '/') ? last(explode('/', (string) $student->student_uid)) : $student->student_uid)
    );
    $submittedDateValue = old('submitted_date', optional($student->submitted_date ?? $student->created_at)->format('Y-m-d'));
    $academicSessionValue = old('academic_session', $activeSession?->name ?? $student->session?->name ?? '');
    $currentAssignedSubjects = $currentSessionSubjects
        ->map(function ($row) {
            $role = (string) ($row->subject_role ?: 'minor');
            $sort = match ($role) {
                'compulsory' => 0,
                'major', 'both' => 1,
                'minor', 'optional' => 2,
                default => 3,
            };

            return [
                'id' => (int) $row->subject_id,
                'name' => $row->subject->name ?? 'Subject',
                'code' => $row->subject->code ?? null,
                'role_label' => match ($role) {
                    'compulsory' => 'Compulsory',
                    'major' => 'Major',
                    'minor', 'optional' => 'Minor',
                    'both' => 'Major & Minor',
                    default => ucfirst($role),
                },
                'is_auto' => (bool) $row->is_auto_included,
                'has_practical' => (bool) ($row->subject->has_practical ?? false),
                'sort' => $sort,
            ];
        })
        ->sortBy(['sort', 'name'])
        ->values();
@endphp
@extends($layout)
@section('title', 'Edit Student Profile')
@section('breadcrumb', 'Admissions / Edit Profile')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-pencil me-2 text-warning"></i>Edit Student Profile</h4>
        <small class="text-muted">{{ $student->name }} — {{ $student->student_uid }}</small>
    </div>
    <a href="{{ route($showRoute, $student->id) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Profile
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm">
    <i class="bi bi-exclamation-circle me-2"></i>
    <ul class="mb-0 mt-1 ps-3">
        @foreach($errors->all() as $err)
            <li class="small">{{ $err }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form method="POST" action="{{ route($updateRoute, $student->id) }}" enctype="multipart/form-data" id="editAdmissionForm">
@csrf
@method('PATCH')

{{-- ── OFFICE DETAILS ── --}}
@if($showOffice)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2 bg-white border-bottom">
        <span class="fw-semibold small"><i class="bi bi-briefcase me-2 text-secondary"></i>Office Details</span>
    </div>
    <div class="card-body p-3">
        <div class="row g-3">
            @if($fieldEnabled('form_no'))
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Serial No.</label>
                <input type="text" class="form-control form-control-sm bg-light" value="{{ $formNoValue }}" readonly>
            </div>
            @endif

            {{-- Manual Form No. filled by institute --}}
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Form No.</label>
                <input type="text" name="institute_form_no" class="form-control form-control-sm"
                       value="{{ old('institute_form_no', $student->institute_form_no) }}"
                       placeholder="e.g. 2026/001">
            </div>

            @foreach([
                'sr_no' => 'SR No.',
                'enrollment_no' => 'Enrollment No.',
                'roll_no' => 'Roll No.',
                'exam_form_no' => 'Exam Form No.',
                'uin_no' => 'UIN No.',
                'reference_no' => 'Reference No.',
            ] as $key => $label)
                @if($fieldEnabled($key))
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">{{ $label }} @if($fieldRequired($key))<span class="text-danger">*</span>@endif</label>
                    <input type="text" name="{{ $key }}" class="form-control form-control-sm"
                           value="{{ old($key, $student->{$key}) }}" {{ $fieldRequired($key) ? 'required' : '' }}>
                </div>
                @endif
            @endforeach

            @if($fieldEnabled('submitted_date'))
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Submitted Date</label>
                <input type="date" class="form-control form-control-sm bg-light" value="{{ $submittedDateValue }}" readonly>
            </div>
            @endif

            @if($fieldEnabled('admission_type'))
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Admission Type @if($fieldRequired('admission_type'))<span class="text-danger">*</span>@endif</label>
                <select name="admission_type" class="form-select form-select-sm" {{ $fieldRequired('admission_type') ? 'required' : '' }}>
                    @foreach(['new' => 'New', 'lateral' => 'Lateral Entry', 'transfer' => 'Transfer', 're_admission' => 'Re-Admission'] as $v => $l)
                    <option value="{{ $v }}" {{ old('admission_type', $student->admission_type) == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            @if($fieldEnabled('admission_source'))
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Admission Source @if($fieldRequired('admission_source'))<span class="text-danger">*</span>@endif</label>
                <select name="admission_source" id="srcEdit" class="form-select form-select-sm"
                        onchange="toggleSrcEdit(this.value)" {{ $fieldRequired('admission_source') ? 'required' : '' }}>
                    @foreach(['direct' => 'Direct', 'center' => 'Center', 'channel_partner' => 'Channel Partner'] as $v => $l)
                    <option value="{{ $v }}" {{ old('admission_source', $student->admission_source) == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <div id="editCenterField" class="mt-2" style="display:none;">
                    <select name="admission_source_id" id="editCenterSelect" class="form-select form-select-sm">
                        <option value="">Select Center</option>
                        @foreach($centers as $c)
                        <option value="{{ $c->id }}" {{ old('admission_source', $student->admission_source) === 'center' && (string) old('admission_source_id', $student->admission_source_id) === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="editPartnerField" class="mt-2" style="display:none;">
                    <select name="admission_source_id" id="editPartnerSelect" class="form-select form-select-sm">
                        <option value="">Select Partner</option>
                        @foreach($partners as $p)
                        <option value="{{ $p->id }}" {{ old('admission_source', $student->admission_source) === 'channel_partner' && (string) old('admission_source_id', $student->admission_source_id) === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif

            @if($fieldEnabled('gap_year'))
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Gap Year @if($fieldRequired('gap_year'))<span class="text-danger">*</span>@endif</label>
                <div class="d-flex gap-3 mt-1">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gap_year" id="gapYearNo" value="0"
                               {{ (string) old('gap_year', $student->gap_year ? '1' : '0') !== '1' ? 'checked' : '' }}>
                        <label class="form-check-label small" for="gapYearNo">No</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gap_year" id="gapYearYes" value="1"
                               {{ (string) old('gap_year', $student->gap_year ? '1' : '0') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label small" for="gapYearYes">Yes</label>
                    </div>
                </div>
            </div>
            @endif

            @if($fieldEnabled('admission_date'))
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Admission Date @if($fieldRequired('admission_date'))<span class="text-danger">*</span>@endif</label>
                <input type="date" name="admission_date" class="form-control form-control-sm"
                       value="{{ old('admission_date', $student->admission_date?->format('Y-m-d')) }}" {{ $fieldRequired('admission_date') ? 'required' : '' }}>
            </div>
            @endif

            @if($fieldEnabled('academic_session'))
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Academic Session</label>
                <input type="text" class="form-control form-control-sm bg-light" value="{{ $academicSessionValue }}" readonly>
            </div>
            @endif
        </div>
    </div>
</div>
@endif

@php
    $courseStreamsData = $courses->mapWithKeys(function ($course) {
        return [$course->id => [
            'id' => $course->id,
            'name' => $course->name,
            'streams' => $course->streams->map(fn($stream) => [
                'id' => $stream->id,
                'name' => $stream->name,
            ])->values(),
            'parts' => $course->parts->sortBy([
                ['year_number', 'asc'],
                ['part_number', 'asc'],
            ])->map(fn($part) => [
                'id' => $part->id,
                'name' => $part->year_label ?: ('Year ' . $part->year_number),
                'year' => $part->year_number,
            ])->values(),
        ]];
    });
@endphp
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2 bg-white border-bottom">
        <span class="fw-semibold small"><i class="bi bi-journal-check me-2 text-success"></i>Academic Details</span>
    </div>
    <div class="card-body p-3">
        @php $selectedCourseTypeId = old('course_type_id', $student->course_type_id ?? $student->stream?->course?->course_type_id); @endphp
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Course Type <span class="text-danger">*</span></label>
                <select name="course_type_id" id="editCourseTypeSelect" class="form-select form-select-sm" required
                        onchange="filterEditCoursesByType(this.value)">
                    <option value="">— Select Type —</option>
                    @foreach($courseTypes as $ct)
                        <option value="{{ $ct->id }}" {{ (string) $selectedCourseTypeId === (string) $ct->id ? 'selected' : '' }}>
                            {{ $ct->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Course</label>
                <select name="course_id" id="editCourseSelect" class="form-select form-select-sm">
                    <option value="">Select Course</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}"
                                data-type-id="{{ $course->course_type_id }}"
                                {{ (string) $selectedCourseId === (string) $course->id ? 'selected' : '' }}>
                            {{ $course->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Stream <span class="text-danger">*</span></label>
                <select name="course_stream_id" id="editStreamSelect" class="form-select form-select-sm">
                    <option value="">Select Stream</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Year / Part</label>
                <select name="course_part_id" id="editPartSelect" class="form-select form-select-sm">
                    <option value="">Select Part</option>
                </select>
            </div>
        </div>

        @if($currentAssignedSubjects->isNotEmpty())
        <div class="mt-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="small fw-semibold">Currently Assigned Subjects</div>
                <div class="small text-muted">Currently assigned to this student</div>
            </div>
            <div class="subject-choice-grid">
                @foreach($currentAssignedSubjects as $assigned)
                <div class="subject-choice-card" style="background:#f8fafc;">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="small fw-semibold">{{ $assigned['name'] }}</div>
                            @if($assigned['code'])
                            <div class="small text-muted">{{ $assigned['code'] }}</div>
                            @endif
                        </div>
                        <span class="badge {{ $assigned['role_label'] === 'Major' ? 'bg-primary' : ($assigned['role_label'] === 'Minor' ? 'bg-info text-dark' : ($assigned['role_label'] === 'Major & Minor' ? 'bg-purple text-white' : 'bg-success')) }}" style="{{ $assigned['role_label'] === 'Major & Minor' ? 'background:#6f42c1 !important;' : '' }}">
                            {{ $assigned['role_label'] }}
                        </span>
                    </div>
                    <div class="mt-2 d-flex flex-wrap gap-2">
                        @if($assigned['is_auto'])
                        <span class="badge bg-light text-dark border">Auto assigned</span>
                        @endif
                        @if($assigned['has_practical'])
                        <span class="badge bg-warning text-dark">Practical auto</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="mt-3 card border-0 shadow-sm" id="editSubjectSection" style="display:none;">
            <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background:#0f4c81; color:white;">
                <span class="fw-bold small"><i class="bi bi-list-check me-2"></i>Subject Selection</span>
                <small id="subjectRuleHint" class="opacity-75"></small>
            </div>
            <div class="card-body p-3">
            <div class="small text-muted mb-3">Change major/minor subjects here. Practical fee will be included automatically when a practical subject is selected.</div>
            <div id="editSubjectLoading" class="text-center text-muted py-3" style="display:none;">
                <div class="spinner-border spinner-border-sm me-2"></div> Loading subjects...
            </div>
            <div id="editSubjectContent"></div>
            @error('selected_subjects')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            @error('selected_major_subjects')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            @error('selected_minor_subjects')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>{{-- card-body --}}
        </div>{{-- card --}}

        @php
            $currentFeeSummary = $currentSnapshot['fee_data']['summary'] ?? [];
            $currentSubjectFee = (float) ($currentFeeSummary['subject_fee'] ?? 0);
            $currentPracticalFee = (float) ($currentFeeSummary['practical_fee'] ?? 0);
            $currentSubjectPracticalTotal = $currentSubjectFee + $currentPracticalFee;
        @endphp
        <div class="alert alert-light border mt-3 mb-0" id="editFeeImpactBox">
            <div class="d-flex justify-content-between flex-wrap gap-3">
                <div>
                    <div class="small text-muted">Current academic fee</div>
                    <div class="fw-bold" id="currentAcademicFee">Rs. {{ number_format((float) ($currentSnapshot['recalculable_total'] ?? 0), 2) }}</div>
                </div>
                <div>
                    <div class="small text-muted">Already paid</div>
                    <div class="fw-bold">Rs. {{ number_format((float) ($feeSummary['total_paid'] ?? 0), 2) }}</div>
                </div>
                <div>
                    <div class="small text-muted">Current wallet</div>
                    <div class="fw-bold {{ ($feeSummary['balance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                        Rs. {{ number_format(abs((float) ($feeSummary['balance'] ?? 0)), 2) }}
                        {{ ($feeSummary['balance'] ?? 0) >= 0 ? 'Advance' : 'Due' }}
                    </div>
                </div>
                <div>
                    <div class="small text-muted">Projected academic fee</div>
                    <div class="fw-bold" id="projectedAcademicFee">Rs. {{ number_format((float) ($currentSnapshot['recalculable_total'] ?? 0), 2) }}</div>
                </div>
                <div>
                    <div class="small text-muted">Adjustment on save</div>
                    <div class="fw-bold" id="projectedAdjustment">Rs. 0.00</div>
                </div>
                <div>
                    <div class="small text-muted">Projected wallet</div>
                    <div class="fw-bold" id="projectedWalletStatus">
                        Rs. {{ number_format(abs((float) ($feeSummary['balance'] ?? 0)), 2) }}
                        {{ ($feeSummary['balance'] ?? 0) >= 0 ? 'Advance' : 'Due' }}
                    </div>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <div class="small text-muted">Subject fee</div>
                    <div class="d-flex justify-content-between gap-2 flex-wrap">
                        <span class="small">Current: <strong id="currentSubjectFeeValue">Rs. {{ number_format($currentSubjectFee, 2) }}</strong></span>
                        <span class="small">Projected: <strong id="projectedSubjectFeeValue">Rs. {{ number_format($currentSubjectFee, 2) }}</strong></span>
                        <span class="small" id="subjectFeeDelta">No change</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted">Practical fee</div>
                    <div class="d-flex justify-content-between gap-2 flex-wrap">
                        <span class="small">Current: <strong id="currentPracticalFeeValue">Rs. {{ number_format($currentPracticalFee, 2) }}</strong></span>
                        <span class="small">Projected: <strong id="projectedPracticalFeeValue">Rs. {{ number_format($currentPracticalFee, 2) }}</strong></span>
                        <span class="small" id="practicalFeeDelta">No change</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted">Subject + practical total</div>
                    <div class="d-flex justify-content-between gap-2 flex-wrap">
                        <span class="small">Current: <strong id="currentSubjectPracticalValue">Rs. {{ number_format($currentSubjectPracticalTotal, 2) }}</strong></span>
                        <span class="small">Projected: <strong id="projectedSubjectPracticalValue">Rs. {{ number_format($currentSubjectPracticalTotal, 2) }}</strong></span>
                        <span class="small" id="subjectPracticalImpact">No change</span>
                    </div>
                    <div class="small text-muted">Subject add/remove par yahi combined fee impact dikhega.</div>
                </div>
            </div>
            <div class="small text-muted mt-2">
                Fee differences will be auto-adjusted based on academic fee rules. Paid amounts will be retained; extra amount will become due and excess will move to wallet advance.
            </div>
        </div>
    </div>
</div>

{{-- ── PERSONAL DETAILS ── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2 bg-white border-bottom">
        <span class="fw-semibold small"><i class="bi bi-person me-2 text-primary"></i>Personal Details</span>
    </div>
    <div class="card-body p-3">
        <div class="row g-3">

            {{-- Photo --}}
            @if($formConfig['photo']['enabled'] ?? false)
            <div class="col-12 d-flex align-items-center gap-3 mb-1">
                <div onclick="document.getElementById('editPhoto').click()" style="cursor:pointer;">
                    @if($student->photo)
                        <img id="photoPreview" src="{{ asset('storage/'.$student->photo) }}"
                             style="width:70px;height:80px;object-fit:cover;border-radius:8px;border:2px solid #e5e7eb;">
                    @else
                        <div id="photoPreview" style="width:70px;height:80px;border-radius:8px;background:#f3f4f6;border:2px dashed #d1d5db;display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-camera text-muted fs-4"></i>
                        </div>
                    @endif
                </div>
                <div>
                    <div class="small fw-semibold">Photo</div>
                    <div class="small text-muted">Click image to change</div>
                    <input type="file" name="photo" id="editPhoto" class="d-none" accept="image/*"
                           onchange="previewEditPhoto(this)">
                    <div class="form-text small mt-2">Photo auto-optimize hogi before update.</div>
                    <div id="editPhotoUploadError" class="text-danger small mt-1" style="display:none;"></div>
                </div>
            </div>
            @endif

            {{-- Name --}}
            <div class="{{ ($formConfig['gender']['enabled'] ?? false) ? 'col-md-8' : 'col-12' }}">
                <label class="form-label small fw-semibold">Student Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $student->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Gender --}}
            @if($formConfig['gender']['enabled'] ?? false)
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Gender</label>
                <select name="gender" class="form-select form-select-sm">
                    <option value="">Select</option>
                    @foreach(['male'=>'Male','female'=>'Female','other'=>'Other'] as $v=>$l)
                        <option value="{{ $v }}" {{ old('gender',$student->gender)==$v ? 'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Mobile --}}
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Mobile <span class="text-danger">*</span></label>
                <input type="text" name="mobile" class="form-control form-control-sm @error('mobile') is-invalid @enderror"
                       value="{{ old('mobile', $student->mobile) }}" required maxlength="15">
                @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- DOB --}}
            @if($formConfig['dob']['enabled'] ?? false)
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Date of Birth</label>
                <input type="date" name="dob" class="form-control form-control-sm"
                       value="{{ old('dob', $student->dob?->format('Y-m-d')) }}">
            </div>
            @endif

            {{-- Email --}}
            @if($formConfig['email']['enabled'] ?? false)
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Email</label>
                <input type="email" name="email" class="form-control form-control-sm"
                       value="{{ old('email', $student->email) }}">
            </div>
            @endif

            {{-- Father Name --}}
            @if($formConfig['father_name']['enabled'] ?? false)
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Father Name</label>
                <input type="text" name="father_name" class="form-control form-control-sm"
                       value="{{ old('father_name', $student->father_name) }}">
            </div>
            @endif

            {{-- Father Mobile --}}
            @if($formConfig['father_mobile']['enabled'] ?? false)
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Father Mobile</label>
                <input type="text" name="father_mobile" class="form-control form-control-sm"
                       value="{{ old('father_mobile', $student->father_mobile) }}" maxlength="15">
            </div>
            @endif

            {{-- Mother Name --}}
            @if($formConfig['mother_name']['enabled'] ?? false)
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Mother Name</label>
                <input type="text" name="mother_name" class="form-control form-control-sm"
                       value="{{ old('mother_name', $student->mother_name) }}">
            </div>
            @endif

            {{-- Mother Mobile --}}
            @if($formConfig['mother_mobile']['enabled'] ?? false)
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Mother Mobile</label>
                <input type="text" name="mother_mobile" class="form-control form-control-sm"
                       value="{{ old('mother_mobile', $student->mother_mobile) }}" maxlength="15">
            </div>
            @endif

            {{-- Father Occupation --}}
            @if($formConfig['father_occupation']['enabled'] ?? false)
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Father Occupation</label>
                <input type="text" name="father_occupation" class="form-control form-control-sm"
                       value="{{ old('father_occupation', $student->father_occupation) }}" maxlength="100">
            </div>
            @endif

            {{-- Mother Occupation --}}
            @if($formConfig['mother_occupation']['enabled'] ?? false)
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Mother Occupation</label>
                <input type="text" name="mother_occupation" class="form-control form-control-sm"
                       value="{{ old('mother_occupation', $student->mother_occupation) }}">
            </div>
            @endif

            {{-- Guardian Mobile --}}
            @if($formConfig['guardian_mobile']['enabled'] ?? false)
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Guardian Mobile</label>
                <input type="text" name="guardian_mobile" class="form-control form-control-sm"
                       value="{{ old('guardian_mobile', $student->guardian_mobile) }}" maxlength="15">
            </div>
            @endif

            {{-- Religion --}}
            @if($formConfig['religion']['enabled'] ?? false)
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Religion</label>
                <select name="religion" class="form-select form-select-sm">
                    <option value="">Select</option>
                    @foreach(['hindu','muslim','sikh','christian','jain','buddhist','others'] as $r)
                        <option value="{{ $r }}" {{ old('religion',$student->religion)==$r ? 'selected':'' }}>{{ ucfirst($r) }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Category --}}
            @if($formConfig['category']['enabled'] ?? false)
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">Select</option>
                    @foreach(['gen'=>'GEN','obc'=>'OBC','sc'=>'SC','st'=>'ST','ews'=>'EWS'] as $v=>$l)
                        <option value="{{ $v }}" {{ old('category',$student->category)==$v ? 'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Special Category --}}
            @if($formConfig['special_category']['enabled'] ?? false)
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Special Category</label>
                <select name="special_category" class="form-select form-select-sm">
                    <option value="">Select</option>
                    <option value="none" {{ (old('special_category',$student->special_category) ?: 'none')=='none' ? 'selected':'' }}>None / NA</option>
                    @foreach(['pwd','ex_serviceman','sports','ncc','others'] as $s)
                        <option value="{{ $s }}" {{ old('special_category',$student->special_category)==$s ? 'selected':'' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Nationality --}}
            @if($formConfig['nationality']['enabled'] ?? false)
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Nationality</label>
                <input type="text" name="nationality" class="form-control form-control-sm"
                       value="{{ old('nationality', $student->nationality ?? 'Indian') }}">
            </div>
            @endif

            {{-- Aadhar --}}
            @if($formConfig['aadhar_no']['enabled'] ?? false)
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Aadhar No.</label>
                <input type="text" name="aadhar_no" class="form-control form-control-sm"
                       value="{{ old('aadhar_no', $student->aadhar_no) }}" maxlength="12">
            </div>
            @endif

            {{-- APAAR --}}
            @if($formConfig['apaar_no']['enabled'] ?? false)
            <div class="col-md-4">
                <label class="form-label small fw-semibold">APAAR No.</label>
                <input type="text" name="apaar_no" class="form-control form-control-sm"
                       value="{{ old('apaar_no', $student->apaar_no) }}">
            </div>
            @endif

            {{-- Student Type --}}
            @if($formConfig['student_type']['enabled'] ?? false)
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Student Type</label>
                <select name="student_type" class="form-select form-select-sm">
                    @foreach($studentTypes as $st)
                        <option value="{{ $st->slug }}" {{ old('student_type', $student->student_type) == $st->slug ? 'selected' : '' }}>{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Marital Status --}}
            @if($formConfig['marital_status']['enabled'] ?? false)
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Marital Status</label>
                <select name="marital_status" class="form-select form-select-sm">
                    @foreach(['single'=>'Single','married'=>'Married','divorced'=>'Divorced','widowed'=>'Widowed'] as $v=>$l)
                        <option value="{{ $v }}" {{ old('marital_status',$student->marital_status)==$v ? 'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            @endif

        </div>
    </div>
</div>

{{-- ── ADDRESS ── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2 bg-white border-bottom">
        <span class="fw-semibold small"><i class="bi bi-award me-2 text-warning"></i>Scholarship Details</span>
    </div>
    <div class="card-body p-3">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label small fw-semibold d-block">Scholarship</label>
                <div class="d-flex gap-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="has_scholarship" id="schNo" value="0"
                               {{ (string) old('has_scholarship', $student->has_scholarship ? '1' : '0') !== '1' ? 'checked' : '' }}>
                        <label class="form-check-label small" for="schNo">No</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="has_scholarship" id="schYes" value="1"
                               {{ (string) old('has_scholarship', $student->has_scholarship ? '1' : '0') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label small" for="schYes">Yes</label>
                    </div>
                </div>
            </div>
        </div>

        <div id="editScholarshipDetails" style="display:{{ (string) old('has_scholarship', $student->has_scholarship ? '1' : '0') === '1' ? 'block' : 'none' }};">
            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Scholarship Name</label>
                    <input type="text" name="scholarship_name" class="form-control form-control-sm"
                           value="{{ old('scholarship_name', $student->scholarship_name) }}" placeholder="e.g. NSP, State Merit">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Scholarship Type</label>
                    <select name="scholarship_type" class="form-select form-select-sm">
                        <option value="">Select</option>
                        @foreach(['govt_central' => 'Central Govt.', 'govt_state' => 'State Govt.', 'university' => 'University', 'institute' => 'Institute', 'private' => 'Private / NGO', 'other' => 'Other'] as $v => $l)
                            <option value="{{ $v }}" {{ old('scholarship_type', $student->scholarship_type) == $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Authority</label>
                    <input type="text" name="scholarship_authority" class="form-control form-control-sm"
                           value="{{ old('scholarship_authority', $student->scholarship_authority) }}" placeholder="e.g. UGC, State Dept.">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Applied Date</label>
                    <input type="date" name="scholarship_applied_date" class="form-control form-control-sm"
                           value="{{ old('scholarship_applied_date', $student->scholarship_applied_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Scholarship Amount</label>
                    <input type="number" name="scholarship_amount" class="form-control form-control-sm"
                           value="{{ old('scholarship_amount', $student->scholarship_amount) }}" min="0" step="0.01">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Reference No.</label>
                    <input type="text" name="scholarship_ref_no" class="form-control form-control-sm"
                           value="{{ old('scholarship_ref_no', $student->scholarship_ref_no) }}" placeholder="Application No.">
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $showAddr = ($formConfig['perm_village']['enabled'] ?? false)
             || ($formConfig['perm_district']['enabled'] ?? false)
             || ($formConfig['perm_state']['enabled'] ?? false);
@endphp
@if($showAddr)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2 bg-white border-bottom">
        <span class="fw-semibold small"><i class="bi bi-geo-alt me-2 text-danger"></i>Address Details</span>
    </div>
    <div class="card-body p-3">
        <div class="row g-3">
            @if($formConfig['perm_village']['enabled'] ?? false)
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Village / City</label>
                <input type="text" name="perm_village" class="form-control form-control-sm"
                       value="{{ old('perm_village', $student->perm_village) }}">
            </div>
            @endif
            @if($formConfig['perm_post']['enabled'] ?? false)
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Post</label>
                <input type="text" name="perm_post" class="form-control form-control-sm"
                       value="{{ old('perm_post', $student->perm_post) }}">
            </div>
            @endif
            @if($formConfig['perm_district']['enabled'] ?? false)
            <div class="col-md-3">
                <label class="form-label small fw-semibold">District</label>
                <input type="text" name="perm_district" class="form-control form-control-sm"
                       value="{{ old('perm_district', $student->perm_district) }}">
            </div>
            @endif
            @if($formConfig['perm_state']['enabled'] ?? false)
            <div class="col-md-3">
                <label class="form-label small fw-semibold">State</label>
                <input type="text" name="perm_state" class="form-control form-control-sm"
                       value="{{ old('perm_state', $student->perm_state) }}">
            </div>
            @endif
            @if($formConfig['perm_pincode']['enabled'] ?? false)
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Pin Code</label>
                <input type="text" name="perm_pincode" class="form-control form-control-sm"
                       value="{{ old('perm_pincode', $student->perm_pincode) }}" maxlength="6">
            </div>
            @endif

            {{-- Communication Address --}}
            @php
                $showComm = ($formConfig['comm_address']['enabled'] ?? false)
                         || ($formConfig['comm_district']['enabled'] ?? false)
                         || ($formConfig['comm_state']['enabled'] ?? false);
            @endphp
            @if($showComm)
            <div class="col-12 mt-2">
                <div class="small fw-semibold text-muted border-bottom pb-1 mb-2">Communication Address</div>
            </div>
            @if($formConfig['comm_address']['enabled'] ?? false)
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Address</label>
                <input type="text" name="comm_address" class="form-control form-control-sm"
                       value="{{ old('comm_address', $student->comm_address) }}">
            </div>
            @endif
            @if($formConfig['perm_village']['enabled'] ?? false)
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Village / City</label>
                <input type="text" name="comm_city" class="form-control form-control-sm"
                       value="{{ old('comm_city', $student->comm_city) }}">
            </div>
            @endif
            @if($formConfig['perm_post']['enabled'] ?? false)
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Thana</label>
                <input type="text" name="comm_thana" class="form-control form-control-sm"
                       value="{{ old('comm_thana', $student->comm_thana) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Post</label>
                <input type="text" name="comm_post" class="form-control form-control-sm"
                       value="{{ old('comm_post', $student->comm_post) }}">
            </div>
            @endif
            @if($formConfig['perm_district']['enabled'] ?? false)
            <div class="col-md-3">
                <label class="form-label small fw-semibold">District</label>
                <input type="text" name="comm_district" class="form-control form-control-sm"
                       value="{{ old('comm_district', $student->comm_district) }}">
            </div>
            @endif
            @if($formConfig['perm_state']['enabled'] ?? false)
            <div class="col-md-3">
                <label class="form-label small fw-semibold">State</label>
                <input type="text" name="comm_state" class="form-control form-control-sm"
                       value="{{ old('comm_state', $student->comm_state) }}">
            </div>
            @endif
            @if($formConfig['perm_pincode']['enabled'] ?? false)
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Pin Code</label>
                <input type="text" name="comm_pincode" class="form-control form-control-sm"
                       value="{{ old('comm_pincode', $student->comm_pincode) }}" maxlength="6">
            </div>
            @endif
            @endif
        </div>
    </div>
</div>
@endif

{{-- ── SUBMIT ── --}}
@if($showEducation)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2 bg-white border-bottom">
        <span class="fw-semibold small"><i class="bi bi-mortarboard me-2 text-primary"></i>Education Details</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light" style="font-size:11px;">
                    <tr>
                        <th>EXAM</th>
                        <th>STREAM</th>
                        <th>Institute Name</th>
                        <th>Roll No.</th>
                        <th>Year</th>
                        <th>District</th>
                        <th>Division</th>
                        <th>Board/University</th>
                        <th>Marks</th>
                        <th>Max</th>
                        <th>%</th>
                    </tr>
                </thead>
                <tbody>
                    @php $oldEducation = old('education', []); @endphp
                    @foreach($educationFieldMap as $fieldKey => $examName)
                    @php
                        $existingEdu = $educationRows->get(strtolower($examName));
                        $shouldShowRow = ($formConfig[$fieldKey]['enabled'] ?? false)
                            || !empty($oldEducation[$loop->index] ?? null)
                            || !is_null($existingEdu);
                    @endphp
                    @if($shouldShowRow)
                    @php
                        $eduRow = $oldEducation[$loop->index] ?? [
                            'exam_name' => $existingEdu?->exam_name ?? $examName,
                            'education_stream' => $existingEdu?->education_stream ?? '',
                            'institute_name' => $existingEdu?->institute_name ?? '',
                            'roll_number' => $existingEdu?->roll_number ?? '',
                            'passing_year' => $existingEdu?->passing_year ?? '',
                            'district' => $existingEdu?->district ?? '',
                            'division' => $existingEdu?->division ?? '',
                            'board_university' => $existingEdu?->board_university ?? '',
                            'obtained_marks' => $existingEdu?->obtained_marks ?? '',
                            'max_marks' => $existingEdu?->max_marks ?? '',
                            'percentage' => $existingEdu?->percentage ?? '',
                        ];
                    @endphp
                    <tr>
                        <td class="small fw-semibold text-primary">{{ strtoupper($examName) }}</td>
                        <td>
                            @if(strtoupper($examName) === '12TH')
                                <select name="education[{{ $loop->index }}][education_stream]" class="form-select form-select-sm" style="min-width:110px;">
                                    <option value="">-</option>
                                    @foreach(['MATHS','BIO','COMMERCE','ARTS','OTHER'] as $streamOption)
                                    <option value="{{ $streamOption }}" {{ ($eduRow['education_stream'] ?? '') === $streamOption ? 'selected' : '' }}>{{ $streamOption }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" name="education[{{ $loop->index }}][education_stream]" class="form-control form-control-sm" style="min-width:110px; text-transform:uppercase;"
                                       value="{{ $eduRow['education_stream'] ?? '' }}" oninput="this.value=this.value.toUpperCase()">
                            @endif
                        </td>
                        <td>
                            <input type="text" name="education[{{ $loop->index }}][institute_name]" class="form-control form-control-sm" style="min-width:120px;"
                                   value="{{ $eduRow['institute_name'] ?? '' }}">
                            <input type="hidden" name="education[{{ $loop->index }}][exam_name]" value="{{ $eduRow['exam_name'] ?? $examName }}">
                        </td>
                        <td><input type="text" name="education[{{ $loop->index }}][roll_number]" class="form-control form-control-sm" style="width:80px;" value="{{ $eduRow['roll_number'] ?? '' }}"></td>
                        <td><input type="text" name="education[{{ $loop->index }}][passing_year]" class="form-control form-control-sm" style="width:60px;" maxlength="4" value="{{ $eduRow['passing_year'] ?? '' }}"></td>
                        <td><input type="text" name="education[{{ $loop->index }}][district]" class="form-control form-control-sm" style="width:90px;" value="{{ $eduRow['district'] ?? '' }}"></td>
                        <td>
                            <select name="education[{{ $loop->index }}][division]" class="form-select form-select-sm" style="width:70px;">
                                <option value="">-</option>
                                @foreach(['I','II','III','pass','fail'] as $division)
                                <option value="{{ $division }}" {{ ($eduRow['division'] ?? '') === $division ? 'selected' : '' }}>{{ strtoupper($division) }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="text" name="education[{{ $loop->index }}][board_university]" class="form-control form-control-sm" style="min-width:110px;" value="{{ $eduRow['board_university'] ?? '' }}"></td>
                        <td><input type="number" name="education[{{ $loop->index }}][obtained_marks]" class="form-control form-control-sm edu-obtained" style="width:65px;" oninput="calcPercent(this)" value="{{ $eduRow['obtained_marks'] ?? '' }}" min="0" step="0.01"></td>
                        <td><input type="number" name="education[{{ $loop->index }}][max_marks]" class="form-control form-control-sm edu-max" style="width:65px;" oninput="calcPercent(this)" value="{{ $eduRow['max_marks'] ?? '' }}" min="0" step="0.01"></td>
                        <td><input type="text" name="education[{{ $loop->index }}][percentage]" class="form-control form-control-sm edu-percent bg-light" style="width:60px;" readonly placeholder="Auto" value="{{ $eduRow['percentage'] ?? '' }}"></td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="d-flex gap-3 mt-2 mb-4">
    <button type="submit" class="btn btn-primary px-5 fw-semibold">
        <i class="bi bi-check-lg me-2"></i> Save Changes
    </button>
    <a href="{{ route($showRoute, $student->id) }}" class="btn btn-outline-secondary px-4">
        Cancel
    </a>
</div>

</form>

@push('scripts')
{{-- TomSelect — tag-style multi-select dropdown --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
@include('institute.admission._live-validation-script')
<style>
.ts-wrapper.multi .ts-control { min-height: 38px; border-radius: 6px; }
.ts-wrapper.focus .ts-control  { border-color: #86b7fe; box-shadow: 0 0 0 .25rem rgba(13,110,253,.25); }
.ts-dropdown { z-index: 9999; }
.subject-choice-card {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 12px;
    background: #fff;
}
.subject-choice-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 10px;
}
</style>
<script>
const editCourseStreams = @json($courseStreamsData);
const initialCourseId = @json((string) $selectedCourseId);
const initialStreamId = @json((string) $selectedStreamId);
const initialPartId = @json((string) $selectedPartId);
const preselectedMajorIds = @json($selectedMajorIds);
const preselectedMinorIds = @json($selectedMinorIds);
const currentAcademicFee = Number(@json((float) ($currentSnapshot['recalculable_total'] ?? 0)));
const currentSubjectFee = Number(@json($currentSubjectFee));
const currentPracticalFee = Number(@json($currentPracticalFee));
const currentWalletBalance = Number(@json((float) ($feeSummary['balance'] ?? 0)));
const currentSessionId = Number(@json((int) ($student->academic_session_id ?? 0)));
const currentSemester = Number(@json((int) ($student->current_semester ?? 1)));
// Fallback values for fields that may not be rendered in form (hidden by formConfig)
const studentDefaults = {
    student_type: @json($student->student_type ?? 'regular'),
    admission_source: @json($student->admission_source ?? 'direct'),
    category: @json($student->category ?? 'general'),
    gender: @json($student->gender ?? 'other'),
};

function previewEditPhoto(input) {
    const errorBox = document.getElementById('editPhotoUploadError');
    if (errorBox) {
        errorBox.style.display = 'none';
        errorBox.textContent = '';
    }

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const preview = document.getElementById('photoPreview');
            if (preview.tagName === 'IMG') {
                preview.src = e.target.result;
            } else {
                const img = document.createElement('img');
                img.id = 'photoPreview';
                img.src = e.target.result;
                img.style = 'width:70px;height:80px;object-fit:cover;border-radius:8px;border:2px solid #e5e7eb;';
                preview.replaceWith(img);
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

async function compressEditImageFile(file, {
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

async function prepareEditPhotoForSubmit(form) {
    const input = form.querySelector('#editPhoto');
    const errorBox = document.getElementById('editPhotoUploadError');

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
        const optimizedFile = await compressEditImageFile(originalFile);
        const dt = new DataTransfer();
        dt.items.add(optimizedFile);
        input.files = dt.files;
        previewEditPhoto(input);
        return true;
    } catch (error) {
        if (errorBox) {
            errorBox.textContent = error.message || 'Photo could not be optimized.';
            errorBox.style.display = 'block';
        }
        return false;
    }
}

function toggleSrcEdit(val) {
    const centerField = document.getElementById('editCenterField');
    const partnerField = document.getElementById('editPartnerField');
    const centerSelect = document.getElementById('editCenterSelect');
    const partnerSelect = document.getElementById('editPartnerSelect');

    if (centerField) centerField.style.display = val === 'center' ? 'block' : 'none';
    if (partnerField) partnerField.style.display = val === 'channel_partner' ? 'block' : 'none';
    if (centerSelect) centerSelect.disabled = val !== 'center';
    if (partnerSelect) partnerSelect.disabled = val !== 'channel_partner';
}

function toggleScholarshipEdit() {
    const hasScholarship = document.querySelector('input[name="has_scholarship"]:checked')?.value === '1';
    const box = document.getElementById('editScholarshipDetails');
    if (box) box.style.display = hasScholarship ? 'block' : 'none';
}

function calcPercent(input) {
    const row = input.closest('tr');
    if (!row) return;

    const obtained = parseFloat(row.querySelector('.edu-obtained')?.value) || 0;
    const max = parseFloat(row.querySelector('.edu-max')?.value) || 0;
    const pct = row.querySelector('.edu-percent');
    if (!pct) return;

    pct.value = max > 0 ? ((obtained / max) * 100).toFixed(2) : '';
}

function formatMoney(amount) {
    return `Rs. ${Math.abs(Number(amount || 0)).toFixed(2)}`;
}

function setMetricValue(id, amount) {
    const el = document.getElementById(id);
    if (el) el.textContent = formatMoney(amount);
}

function setDeltaText(id, delta) {
    const el = document.getElementById(id);
    if (!el) return;

    el.classList.remove('text-success', 'text-danger', 'text-muted');
    if (Math.abs(delta) < 0.01) {
        el.textContent = 'No change';
        el.classList.add('text-muted');
        return;
    }

    el.textContent = `${delta > 0 ? '+' : '-'} ${formatMoney(delta)}`;
    el.classList.add(delta > 0 ? 'text-danger' : 'text-success');
}

function setProjectedWallet(balance) {
    const el = document.getElementById('projectedWalletStatus');
    if (!el) return;

    const advance = Number(balance) >= 0;
    el.textContent = `${formatMoney(balance)} ${advance ? 'Advance' : 'Due'}`;
    el.classList.remove('text-success', 'text-danger');
    el.classList.add(advance ? 'text-success' : 'text-danger');
}

function updateFeeProjection(projectedFee, summary = {}) {
    const fee = Number(projectedFee || 0);
    const projectedSubjectFee = Number(summary.subject_fee || 0);
    const projectedPracticalFee = Number(summary.practical_fee || 0);
    const projectedSubjectPracticalFee = projectedSubjectFee + projectedPracticalFee;
    const delta = fee - currentAcademicFee;
    const subjectDelta = projectedSubjectFee - currentSubjectFee;
    const practicalDelta = projectedPracticalFee - currentPracticalFee;
    const combinedDelta = subjectDelta + practicalDelta;
    const walletAfter = currentWalletBalance - delta;
    const adjustmentEl = document.getElementById('projectedAdjustment');

    document.getElementById('projectedAcademicFee').textContent = formatMoney(fee);
    adjustmentEl.textContent = `${delta >= 0 ? '+' : '-'} ${formatMoney(delta)}`;
    adjustmentEl.classList.remove('text-success', 'text-danger');
    if (delta > 0) adjustmentEl.classList.add('text-danger');
    if (delta < 0) adjustmentEl.classList.add('text-success');

    setMetricValue('projectedSubjectFeeValue', projectedSubjectFee);
    setMetricValue('projectedPracticalFeeValue', projectedPracticalFee);
    setMetricValue('projectedSubjectPracticalValue', projectedSubjectPracticalFee);
    setDeltaText('subjectFeeDelta', subjectDelta);
    setDeltaText('practicalFeeDelta', practicalDelta);
    setDeltaText('subjectPracticalImpact', combinedDelta);

    setProjectedWallet(walletAfter);
}

function hideSubjects() {
    if (editMajorTS) { editMajorTS.destroy(); editMajorTS = null; }
    if (editMinorTS) { editMinorTS.destroy(); editMinorTS = null; }
    document.getElementById('editSubjectSection').style.display = 'none';
    document.getElementById('editSubjectContent').innerHTML = '';
    document.getElementById('subjectRuleHint').textContent = '';
    updateFeeProjection(currentAcademicFee, {
        subject_fee: currentSubjectFee,
        practical_fee: currentPracticalFee,
    });
}

function populateStreams(courseId) {
    const streamSelect = document.getElementById('editStreamSelect');
    streamSelect.innerHTML = '<option value="">Select Stream</option>';

    if (!courseId || !editCourseStreams[courseId]) return;

    editCourseStreams[courseId].streams.forEach(stream => {
        streamSelect.innerHTML += `<option value="${stream.id}">${stream.name}</option>`;
    });
}

function populateParts(courseId) {
    const partSelect = document.getElementById('editPartSelect');
    partSelect.innerHTML = '<option value="">Select Part</option>';

    if (!courseId || !editCourseStreams[courseId]) return;

    editCourseStreams[courseId].parts.forEach(part => {
        partSelect.innerHTML += `<option value="${part.id}" data-year="${part.year}">${part.name}</option>`;
    });
}

// TomSelect instances
let editMajorTS = null;
let editMinorTS = null;
let editMajorMax = 99, editMajorMin = 0, editMinorMax = 99, editMinorMin = 0;

function selectedSubjectIdsForPreview() {
    const ids = [];
    // Compulsory hidden inputs
    document.querySelectorAll('#editSubjectContent input[type="hidden"][name="selected_subjects[]"]').forEach(inp => {
        const v = Number(inp.value);
        if (v) ids.push(v);
    });
    // TomSelect major
    if (editMajorTS) {
        editMajorTS.getValue().forEach(v => { const n = Number(v); if (n) ids.push(n); });
    }
    // TomSelect minor
    if (editMinorTS) {
        editMinorTS.getValue().forEach(v => { const n = Number(v); if (n) ids.push(n); });
    }
    return [...new Set(ids)];
}

function syncEditMajorMinorExclusion() {
    if (!editMajorTS || !editMinorTS) return;
    const selectedMajors = editMajorTS.getValue();

    editMinorTS.getValue().forEach(val => {
        if (selectedMajors.includes(val)) editMinorTS.removeItem(val, true);
    });

    Object.keys(editMinorTS.options).forEach(val => {
        const opt = { ...editMinorTS.options[val] };
        if (selectedMajors.includes(val)) {
            editMinorTS.updateOption(val, { ...opt, disabled: true });
        } else {
            editMinorTS.updateOption(val, { ...opt, disabled: false });
        }
    });
    editMinorTS.refreshOptions(false);
}

function updateEditMinorCount() {
    const badge = document.getElementById('editMinorCountBadge');
    if (!badge) return;
    const count = editMinorTS ? editMinorTS.getValue().length : 0;
    const max   = editMinorMax > 0 && editMinorMax < 99 ? editMinorMax : null;
    badge.innerHTML = max
        ? `<span class="badge ${count >= max ? 'bg-warning text-dark' : 'bg-info text-dark'}">${count} / ${max} minor subjects selected</span>`
        : '';
}

function renderSubjects(data) {
    // Destroy existing TomSelect instances
    if (editMajorTS) { editMajorTS.destroy(); editMajorTS = null; }
    if (editMinorTS) { editMinorTS.destroy(); editMinorTS = null; }

    const subjects = data.subjects || [];
    const rule = data.year_rule || {};
    editMajorMin = rule.major_min ?? 0;
    editMajorMax = rule.major_max ?? 99;
    editMinorMin = rule.minor_min ?? 0;
    editMinorMax = rule.minor_max ?? 99;

    const compulsory    = subjects.filter(s => s.role === 'compulsory' || !s.is_chooseable);
    const majorSubjects = subjects.filter(s => ['major', 'both'].includes(s.role));
    const minorSubjects = subjects.filter(s => ['minor', 'optional', 'both'].includes(s.role));

    // Restore selections only when stream/part unchanged
    const streamUnchanged = document.getElementById('editStreamSelect')?.value == initialStreamId;
    const partUnchanged   = document.getElementById('editPartSelect')?.value == initialPartId;
    const usePreselected  = streamUnchanged && partUnchanged;
    const restoreMajors   = usePreselected ? preselectedMajorIds : [];
    const restoreMinors   = usePreselected ? preselectedMinorIds.filter(id => !preselectedMajorIds.includes(id)) : [];

    let html = '';

    // Compulsory
    if (compulsory.length) {
        html += `<div class="mb-3">
            <div class="small fw-semibold text-success mb-2"><i class="bi bi-check-circle-fill me-1"></i>Compulsory Subjects (Auto-included)</div>
            <div class="d-flex flex-wrap gap-2">`;
        compulsory.forEach(s => {
            html += `<div class="d-flex align-items-center gap-2 px-3 py-2 rounded border bg-success bg-opacity-10">
                <input type="hidden" name="selected_subjects[]" value="${s.id}">
                <span class="small fw-semibold">${s.name}</span>
                ${s.code ? `<span class="text-muted small"> (${s.code})</span>` : ''}
                ${s.has_practical ? '<span class="badge bg-warning text-dark ms-1" style="font-size:9px;">Practical</span>' : ''}
            </div>`;
        });
        html += `</div></div>`;
    }

    // Major TomSelect
    if (majorSubjects.length) {
        const majorInfo = editMajorMax < 99 ? `Min ${editMajorMin}, Max ${editMajorMax}` : 'Choose major subjects';
        html += `<div class="mb-3">
            <label class="form-label small fw-semibold text-primary">
                <i class="bi bi-star-fill me-1"></i>Select Major Subject(s)
                <span class="fw-normal text-muted">(${majorInfo})</span>
            </label>
            <select id="editMajorSelect" name="selected_major_subjects[]" multiple placeholder="Select major subject...">`;
        majorSubjects.forEach(s => {
            html += `<option value="${s.id}">${s.name}${s.code ? ' (' + s.code + ')' : ''}${s.has_practical ? ' 🔬' : ''}</option>`;
        });
        html += `</select></div>`;
    }

    // Minor TomSelect
    if (minorSubjects.length) {
        const minorInfo = editMinorMax < 99 ? `Max ${editMinorMax} minor` : 'Optional';
        html += `<div class="mb-2">
            <label class="form-label small fw-semibold text-info">
                <i class="bi bi-list-check me-1"></i>Select Minor Subject(s)
                <span class="fw-normal text-muted">(${minorInfo})</span>
            </label>
            <div id="editMinorCountBadge" class="mb-1"></div>
            <select id="editMinorSelect" name="selected_minor_subjects[]" multiple placeholder="Select minor subject...">`;
        minorSubjects.forEach(s => {
            html += `<option value="${s.id}">${s.name}${s.code ? ' (' + s.code + ')' : ''}${s.has_practical ? ' 🔬' : ''}</option>`;
        });
        html += `</select></div>`;
    }

    if (!html) {
        html = '<div class="small text-muted">No subjects configured for this stream/year.</div>';
    }

    document.getElementById('editSubjectContent').innerHTML = html;
    document.getElementById('subjectRuleHint').textContent = rule.major_min != null
        ? `Major ${editMajorMin}-${editMajorMax}, Minor ${editMinorMin}-${editMinorMax}`
        : '';

    // Init TomSelect — Major
    const majorEl = document.getElementById('editMajorSelect');
    if (majorEl) {
        editMajorTS = new TomSelect('#editMajorSelect', {
            plugins: ['remove_button'],
            maxItems: editMajorMax < 99 ? editMajorMax : null,
            maxOptions: 100,
            placeholder: 'Select major subject...',
            onItemAdd:    () => { syncEditMajorMinorExclusion(); refreshFeePreview(); },
            onItemRemove: () => { syncEditMajorMinorExclusion(); refreshFeePreview(); },
        });
        if (restoreMajors.length) {
            const avail = majorSubjects.map(s => String(s.id));
            const toSel = restoreMajors.filter(id => avail.includes(String(id)));
            // silent=true suppresses onItemAdd callbacks during initial restore
            if (toSel.length) editMajorTS.setValue(toSel.map(String), true);
        }
    }

    // Init TomSelect — Minor
    const minorEl = document.getElementById('editMinorSelect');
    if (minorEl) {
        editMinorTS = new TomSelect('#editMinorSelect', {
            plugins: ['remove_button'],
            maxItems: editMinorMax < 99 ? editMinorMax : null,
            maxOptions: 100,
            placeholder: 'Select minor subject...',
            onItemAdd:    () => { updateEditMinorCount(); refreshFeePreview(); },
            onItemRemove: () => { updateEditMinorCount(); refreshFeePreview(); },
        });
        if (restoreMinors.length) {
            const avail = minorSubjects.map(s => String(s.id));
            const toSel = restoreMinors.filter(id => avail.includes(String(id)));
            // silent=true suppresses onItemAdd callbacks during initial restore
            if (toSel.length) editMinorTS.setValue(toSel.map(String), true);
        }
    }

    // After both TomSelects are initialised with their pre-selected values,
    // sync exclusions (disable major subjects in minor dropdown) and
    // trigger a single fee preview refresh.
    updateEditMinorCount();
    if (editMajorTS && editMinorTS) syncEditMajorMinorExclusion();
    refreshFeePreview();
}

function loadSubjectsForSelection() {
    const streamId = document.getElementById('editStreamSelect')?.value;
    const partSelect = document.getElementById('editPartSelect');
    const selectedPart = partSelect?.options?.[partSelect.selectedIndex];
    const yearNumber = selectedPart?.dataset?.year;

    if (!streamId || !yearNumber) {
        hideSubjects();
        return;
    }

    document.getElementById('editSubjectSection').style.display = 'block';
    document.getElementById('editSubjectLoading').style.display = 'block';
    document.getElementById('editSubjectContent').innerHTML = '';

    fetch(`{{ route($streamSubjectsRoute) }}?stream_id=${streamId}&year_number=${yearNumber}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(response => response.json())
        .then(data => {
            document.getElementById('editSubjectLoading').style.display = 'none';
            if (!data.success) {
                hideSubjects();
                return;
            }

            renderSubjects(data); // renderSubjects calls refreshFeePreview() internally after init
        })
        .catch(() => {
            document.getElementById('editSubjectLoading').style.display = 'none';
            hideSubjects();
        });
}

function refreshFeePreview() {
    const streamId = document.getElementById('editStreamSelect')?.value;
    const partId = document.getElementById('editPartSelect')?.value;

    if (!streamId) {
        updateFeeProjection(currentAcademicFee, {
            subject_fee: currentSubjectFee,
            practical_fee: currentPracticalFee,
        });
        return;
    }

    const params = new URLSearchParams();
    params.append('stream_id', streamId);
    if (partId) params.append('course_part_id', partId);
    params.append('academic_session_id', String(currentSessionId));
    params.append('semester', String(currentSemester));
    params.append('student_type', document.querySelector('select[name="student_type"]')?.value || studentDefaults.student_type);
    params.append('admission_source', document.querySelector('select[name="admission_source"]')?.value || studentDefaults.admission_source);
    params.append('category', document.querySelector('select[name="category"]')?.value || studentDefaults.category);
    params.append('gender', document.querySelector('select[name="gender"]')?.value || studentDefaults.gender);
    selectedSubjectIdsForPreview().forEach(subjectId => params.append('subject_ids[]', String(subjectId)));
    params.append('_token', document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value || '');

    fetch(`{{ route($feePreviewRoute) }}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: params.toString(),
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateFeeProjection(
                    Number(data.fee_data?.total || 0),
                    data.fee_data?.summary || {}
                );
            }
        })
        .catch(() => updateFeeProjection(currentAcademicFee, {
            subject_fee: currentSubjectFee,
            practical_fee: currentPracticalFee,
        }));
}

function filterEditCoursesByType(typeId) {
    const courseSel = document.getElementById('editCourseSelect');
    const currentVal = courseSel.value;

    Array.from(courseSel.options).forEach(opt => {
        if (!opt.value) return;
        const matches = !typeId || opt.dataset.typeId === String(typeId);
        opt.hidden   = !matches;
        opt.disabled = !matches;
    });

    if (currentVal && courseSel.options[courseSel.selectedIndex]?.dataset.typeId !== String(typeId)) {
        courseSel.value = '';
    }
}

window.addEventListener('DOMContentLoaded', function() {
    const editAdmissionForm = document.getElementById('editAdmissionForm');
    if (editAdmissionForm) {
        let isSubmitting = false;
        window.admissionLiveValidation?.initForm(editAdmissionForm);

        editAdmissionForm.addEventListener('submit', async function(event) {
            if (isSubmitting) {
                return;
            }

            event.preventDefault();
            if (!window.admissionLiveValidation?.validateForm(editAdmissionForm, { report: true })) {
                return;
            }
            const canContinue = await prepareEditPhotoForSubmit(editAdmissionForm);
            if (!canContinue) {
                return;
            }

            isSubmitting = true;
            editAdmissionForm.submit();
        });
    }

    // Apply course type filter on load so course dropdown is pre-filtered
    const savedCourseType = document.getElementById('editCourseTypeSelect')?.value;
    if (savedCourseType) filterEditCoursesByType(savedCourseType);

    const src = document.getElementById('srcEdit');
    if (src) {
        toggleSrcEdit(src.value);
        src.addEventListener('change', () => {
            toggleSrcEdit(src.value);
            refreshFeePreview();
        });
    }

    document.querySelectorAll('input[name="has_scholarship"]').forEach(input => {
        input.addEventListener('change', toggleScholarshipEdit);
    });
    toggleScholarshipEdit();

    const courseSelect = document.getElementById('editCourseSelect');
    const streamSelect = document.getElementById('editStreamSelect');
    const partSelect = document.getElementById('editPartSelect');

    populateStreams(courseSelect.value || initialCourseId);
    populateParts(courseSelect.value || initialCourseId);
    if (initialStreamId) streamSelect.value = initialStreamId;
    if (initialPartId) partSelect.value = initialPartId;

    courseSelect?.addEventListener('change', function() {
        populateStreams(this.value);
        populateParts(this.value);
        loadSubjectsForSelection();
        refreshFeePreview();
    });

    streamSelect?.addEventListener('change', function() {
        loadSubjectsForSelection();
        refreshFeePreview();
    });

    partSelect?.addEventListener('change', function() {
        loadSubjectsForSelection();
        refreshFeePreview();
    });

    ['student_type', 'category', 'gender'].forEach(name => {
        document.querySelector(`[name="${name}"]`)?.addEventListener('change', refreshFeePreview);
    });

    document.querySelectorAll('.edu-obtained, .edu-max').forEach(input => calcPercent(input));

    loadSubjectsForSelection();
    setProjectedWallet(currentWalletBalance);
});
</script>
@endpush
@endsection
