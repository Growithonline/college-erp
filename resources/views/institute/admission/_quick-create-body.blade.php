 @php
    // Quick preview "Edit" se aaye? View variable ya session se data lo
    $qd = $qd ?? session('quickPreviewData') ?? [];
    $qv = fn($key, $default='') => old($key, $qd[$key] ?? $default);
    $quickPhotoTemp = old('photo_temp', $qd['photo_temp'] ?? null);
    $quickPhotoTempUrl = $quickPhotoTemp ? \Illuminate\Support\Facades\Storage::url($quickPhotoTemp) : null;
    $fieldEnabled = fn($key) => (bool) (($formConfig[$key]['enabled'] ?? false) && ($formConfig[$key]['section_enabled'] ?? true));
    $fieldRequired = fn($key) => (bool) ($fieldEnabled($key) && ($formConfig[$key]['required'] ?? false));
    // Layout control: center portal passes $formInnerCols=4 (3-col), others default to 6 (2-col)
    $innerCol  = $formInnerCols ?? 6;
    $isCompact = $innerCol <= 4;
    $fc  = $isCompact ? 'form-control form-control-sm' : 'form-control';
    $fs  = $isCompact ? 'form-select form-select-sm'   : 'form-select';
    $gap = $isCompact ? 'g-2' : 'g-3';
    $cbp = $isCompact ? 'p-2' : 'p-3';
    // When staff has access to exactly 1 session (not the active one), use that session as default
    $defaultSession = (isset($admissibleSessions) && $admissibleSessions->count() === 1)
        ? $admissibleSessions->first()
        : $activeSession;
@endphp
<div class="row {{ isset($formColClass) && $formColClass === 'col-12' ? '' : 'justify-content-center' }}">
<div class="{{ $formColClass ?? 'col-md-8' }}">

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
            <span class="d-inline-flex align-items-center justify-content-center rounded-2"
                  style="width:32px;height:32px;background:#fbbf24;">
                <i class="bi bi-lightning-fill text-white" style="font-size:15px;"></i>
            </span>
            Quick Registration
        </h5>
        <small class="text-muted ms-1">Session: <strong class="text-primary">{{ $defaultSession?->name ?? '—' }}</strong></small>
    </div>
    <div class="d-flex gap-2">
        @if(!isset($fullFormRoute) || $fullFormRoute)
        <a href="{{ isset($fullFormRoute) ? $fullFormRoute : route('admissions.create') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-person me-1"></i> Full Form
        </a>
        @endif
        <a href="{{ isset($indexRoute) ? $indexRoute : route('admissions.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

@if(!$activeSession)
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        No active academic session found.
        <a href="{{ route('master.sessions.index') }}" class="alert-link">Activate a session</a>
    </div>
@else

{{-- Validation errors --}}
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm">
    <i class="bi bi-exclamation-circle me-2"></i>
    <strong>Some fields have errors:</strong>
    <ul class="mb-0 mt-1 ps-3">
        @foreach($errors->all() as $err)
            <li class="small">{{ $err }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@php
    $paymentModeLabels = [
        'cash' => 'Cash',
        'upi' => 'UPI',
        'online' => 'Online Transfer',
        'cheque' => 'Cheque',
        'dd' => 'DD',
        'neft' => 'NEFT',
        'rtgs' => 'RTGS',
    ];
    $allowedModes = $allowedPaymentModes ?? array_keys($paymentModeLabels);
    $bankAccounts = $bankAccounts ?? collect();
    $defaultPaymentDate = now()->toDateString();
    $lockPaymentDate = auth()->guard('staff')->check()
        || auth()->guard('center')->check()
        || auth()->guard('partner')->check();
@endphp
{{-- Legacy payment block kept disabled; active payment section now renders inside the form below. --}}
{{--
<div class="card border-0 shadow-sm mb-3 border-top border-success border-3" id="quickPaymentSection">
    <div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:#f0fdf4;">
        <div>
            <span class="fw-semibold small text-success-emphasis">
                <i class="bi bi-cash-coin me-1 text-success"></i> Fee Payment
            </span>
            <div class="small text-muted">Admission will be saved once payment is collected.</div>
        </div>
        <div class="text-end">
            <div class="small text-muted">Total Selected</div>
            <div class="fw-bold fs-5 text-success" id="quickGrandTotal">Rs 0</div>
        </div>
    </div>
    <div class="card-body p-3">
        <div id="quickFeeEmpty" class="alert alert-warning mb-3">
            Fee items will load here once you select a course, stream and subjects.
        </div>

        <div id="quickFeePanel" style="display:none;">
            <div class="table-responsive mb-3">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:34px;" class="text-center">✓</th>
                            <th>Fee Item</th>
                            <th class="text-end">Assigned</th>
                            <th class="text-end">Collect</th>
                            <th class="text-end">Fine</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody id="quickFeeRows"></tbody>
                </table>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-3 col-6">
                    <div class="rounded-4 border-0 h-100 p-3 text-center" style="background:#eff6ff;">
                        <div style="font-size:10px;color:#64748b;">Total Collected</div>
                        <div class="fw-bold fs-5 text-primary" id="quickSummaryCollected">Rs 0.00</div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="rounded-4 border-0 h-100 p-3 text-center" style="background:#fef2f2;">
                        <div style="font-size:10px;color:#b91c1c;">Total Fine</div>
                        <div class="fw-bold fs-5 text-danger" id="quickSummaryFine">Rs 0.00</div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="rounded-4 border-0 h-100 p-3 text-center" style="background:#fffbeb;">
                        <div style="font-size:10px;color:#92400e;">Total Discount</div>
                        <div class="fw-bold fs-5 text-warning" id="quickSummaryDiscount">Rs 0.00</div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="rounded-4 border-0 h-100 p-3 text-center" style="background:#ecfeff;">
                        <div style="font-size:10px;color:#0f766e;">Total Balance</div>
                        <div class="fw-bold fs-5" style="color:#0f766e;" id="quickSummaryBalance">Rs 0.00</div>
                    </div>
                </div>
                <div class="col-md-3 col-12">
                    <div class="rounded-4 border-0 h-100 p-3 text-center text-white" style="background:#1e293b;">
                        <div style="font-size:12px;opacity:.75;">Total Due</div>
                        <div class="fw-bold" style="font-size:2rem;line-height:1;" id="quickSummaryNet">Rs 0.00</div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Semester</label>
                    <input type="hidden" name="semester" value="1">
                    <input type="text" class="form-control bg-light" value="Semester 1" readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control"
                           value="{{ old('payment_date', $defaultPaymentDate) }}"
                           {{ $lockPaymentDate ? 'readonly' : '' }}>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Payment Mode <span class="text-danger">*</span></label>
                    <select name="payment_mode" id="quickPaymentMode" class="form-select" onchange="toggleQuickBankAccount()">
                        @foreach($allowedModes as $mode)
                            <option value="{{ $mode }}" {{ old('payment_mode', 'cash') === $mode ? 'selected' : '' }}>
                                {{ $paymentModeLabels[$mode] ?? strtoupper($mode) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6" id="quickBankWrap" style="display:none;">
                    <label class="form-label small fw-semibold">Bank Account</label>
                    <select name="bank_account_id" id="quickBankAccount" class="form-select">
                        <option value="">Select Bank Account</option>
                        @foreach($bankAccounts as $bankAccount)
                            <option value="{{ $bankAccount->id }}"
                                    data-modes="{{ $bankModeOverride ?? ($bankAccount->allowed_payment_modes ?? 'cash,upi,online,cheque,dd,neft,rtgs') }}"
                                    {{ (string) old('bank_account_id') === (string) $bankAccount->id ? 'selected' : '' }}>
                                {{ $bankAccount->account_name }} - {{ $bankAccount->bank_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6" id="quickTransactionRefWrap">
                    <label class="form-label small fw-semibold" id="quickTransactionRefLabel">Transaction Ref</label>
                    <input type="text" name="transaction_ref" id="quickTransactionRef" class="form-control"
                           value="{{ old('transaction_ref') }}" placeholder="Txn / UTR / Cheque no.">
                </div>

                <div class="col-md-6" id="quickPaymentTimeWrap" style="display:none;">
                    <label class="form-label small fw-semibold">
                        Payment Date & Time <span class="text-danger">*</span>
                        <span class="text-muted fw-normal" style="font-size:11px;">(Actual payment ka time)</span>
                    </label>
                    <input type="datetime-local" name="payment_datetime" id="quickPaymentDatetime" class="form-control"
                           value="{{ old('payment_datetime') }}">
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold">Remarks</label>
                    <input type="text" name="remarks" class="form-control"
                           value="{{ old('remarks') }}" placeholder="Optional note">
                </div>
            </div>
        </div>
    </div>
</div>
--}}

<form method="POST" action="{{ isset($storeRoute) ? $storeRoute : route('admissions.quick-store') }}" enctype="multipart/form-data" id="quickForm">
@csrf

{{-- ══ Session Selection ═══════════════════════════════════════════════ --}}
@if(isset($admissibleSessions) && $admissibleSessions->count() > 1)
<div class="card border-0 shadow-sm mb-2" style="border-left:4px solid #6366f1!important;">
    <div class="card-body py-2 px-3 d-flex align-items-center gap-3">
        <i class="bi bi-calendar3 text-primary"></i>
        <label class="form-label fw-semibold small mb-0 text-nowrap">Admission Session</label>
        <select name="session_id" id="quickSessionSelect" class="form-select form-select-sm" style="max-width:220px;">
            @foreach($admissibleSessions as $sess)
            <option value="{{ $sess->id }}"
                {{ old('session_id', $defaultSession?->id) == $sess->id ? 'selected' : '' }}>
                {{ $sess->name }}{{ $sess->is_active ? ' (Current)' : '' }}
            </option>
            @endforeach
        </select>
        <small class="text-muted">Default is current session.</small>
    </div>
</div>
@else
<input type="hidden" name="session_id" value="{{ $defaultSession?->id }}">
@endif

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- STEP 1: COURSE SELECTION — ALWAYS FIRST                   --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<div class="card border-0 shadow-sm mb-2">
    <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background:#1e293b; color:white;">
        <span class="fw-semibold small">
            <i class="bi bi-book me-1"></i> Course Selection
        </span>
        <span class="badge" style="background:#fbbf24; color:#1e293b; font-size:10px;">
            Session: {{ $defaultSession->name }}
        </span>
    </div>
    <div class="card-body {{ $cbp }}">
        <div class="row {{ $gap }}">

            {{-- Course Type --}}
            @php $savedCourseTypeId = old('course_type_id', $qd['course_type_id'] ?? null); @endphp
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Course Type <span class="text-danger">*</span></label>
                <select name="course_type_id" id="courseTypeSelect" class="{{ $fs }} @error('course_type_id') is-invalid @enderror"
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
                <label class="form-label small fw-semibold mb-1">Course <span class="text-danger">*</span></label>
                <select name="course_id" id="courseSelect" class="{{ $fs }}" onchange="loadStreams(this.value)">
                    <option value="">— Select Course —</option>
                    @foreach($courses as $course)
                        @php
                            $savedCourseId = $qd['course_id'] ?? null;
                            if (!$savedCourseId && !empty($qd['course_stream_id'])) {
                                $savedCourseId = $course->streams->where('id', $qd['course_stream_id'])->first()?->course_id;
                            }
                        @endphp
                        <option value="{{ $course->id }}"
                                data-type-id="{{ $course->course_type_id }}"
                            {{ (old('course_id', $savedCourseId) == $course->id) ? 'selected' : '' }}>
                            {{ $course->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Stream --}}
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Stream <span class="text-danger">*</span></label>
                <select name="course_stream_id" id="streamSelect"
                        class="{{ $fs }} @error('course_stream_id') is-invalid @enderror"
                        required onchange="checkStreamSeats(this.value)">
                    <option value="">— Select Stream —</option>
                </select>
                <div id="seatInfo" class="mt-1" style="font-size:12px;"></div>
                @error('course_stream_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Year/Part --}}
            <div class="col-md-4" id="partWrap" style="display:none;">
                <label class="form-label small fw-semibold mb-1">Year / Part</label>
                <select name="course_part_id" id="partSelect" class="{{ $fs }}">
                    <option value="">— Select Year —</option>
                </select>
            </div>

            {{-- Fee Plan --}}
            @if(isset($feePlans) && $feePlans->isNotEmpty())
            <div class="col-md-4" id="feePlanWrap">
                <label class="form-label small fw-semibold mb-1">Fee Plan
                    <i class="bi bi-info-circle text-muted" title="Number of installments for fee payment"></i>
                </label>
                <select name="fee_plan_id" id="feePlanSelect" class="{{ $fs }}"
                        onchange="showFeePlanPreview(this.value)">
                    <option value="">— No Plan (Full Payment) —</option>
                    @foreach($feePlans as $fp)
                    <option value="{{ $fp->id }}"
                            data-course-id="{{ $fp->course_id ?? '' }}"
                            {{ old('fee_plan_id') == $fp->id ? 'selected' : '' }}>
                        {{ $fp->name }}
                        @if($fp->course_id) ({{ $fp->course->name ?? '' }}) @endif
                    </option>
                    @endforeach
                </select>
                {{-- Installment preview --}}
                <div id="feePlanPreview" class="mt-1" style="font-size:11px; color:#6b7280;"></div>
            </div>
            @endif

        </div>
    </div>
</div>


{{-- ══════════════════════════════════════════════════════════ --}}
{{-- STEP 2: SUBJECT SELECTION — stream select hone pe load    --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<div id="subjectSection" class="card border-0 shadow-sm mb-2" style="display:none;">
    <div class="card-header py-2 d-flex justify-content-between align-items-center"
         style="background:#1e293b; color:white;">
        <span class="fw-semibold small"><i class="bi bi-list-check me-2"></i>Subject Selection</span>
        <small id="minorCountInfo" class="opacity-75"></small>
    </div>
    <div class="card-body p-3">
        <div class="small text-muted mb-3">
            Subject selection is optional for quick registration. If skipped, only course-level fee will appear in payment. Subjects can be added later from the student edit page.
        </div>
        <div id="subjectLoading" class="text-center text-muted py-3" style="display:none;">
            <div class="spinner-border spinner-border-sm me-2"></div> Loading subjects...
        </div>
        <div id="subjectContent"></div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- ══════════════════════════════════════════════════════════ --}}
{{-- STEP 3: OFFICE DETAILS (admission source, type etc)              --}}
{{-- ══════════════════════════════════════════════════════════ --}}
@php
    $officeFields = ['form_no', 'institute_form_no', 'sr_no', 'enrollment_no', 'roll_no', 'exam_form_no', 'uin_no', 'reference_no',
                     'admission_type', 'admission_source', 'gap_year', 'admission_date', 'submitted_date', 'academic_session'];
    $officeSectionEnabled = collect($officeFields)->contains(fn($k) => $formConfig[$k]['section_enabled'] ?? false);
    $showOffice = $officeSectionEnabled && collect($officeFields)->contains(fn($k) => $fieldEnabled($k));
@endphp
@if($showOffice)
<div class="card border-0 shadow-sm mb-2">
    <div class="card-header py-2" style="background:#1e293b; color:white;">
        <span class="fw-semibold small"><i class="bi bi-briefcase me-1"></i> Office Details</span>
    </div>
    <div class="card-body {{ $cbp }}">
        <div class="row {{ $gap }}">

            @if($fieldEnabled('form_no'))
            <div class="col-md-{{ $isCompact ? 2 : 3 }}">
                <label class="form-label small fw-semibold mb-1">Serial No.</label>
                <input type="text" class="{{ $fc }}" value="Auto" readonly>
            </div>
            @endif

            @if($fieldEnabled('institute_form_no'))
            <div class="col-md-{{ $isCompact ? 2 : 3 }}">
                <label class="form-label small fw-semibold mb-1">Form No. @if($fieldRequired('institute_form_no'))<span class="text-danger">*</span>@endif</label>
                <input type="text" name="institute_form_no" class="{{ $fc }}" value="{{ $qv('institute_form_no') }}" {{ $fieldRequired('institute_form_no') ? 'required' : '' }} placeholder="e.g. 2026/001">
            </div>
            @endif

            @foreach(['sr_no' => 'Student Registration No.', 'enrollment_no' => 'Enrollment No.', 'roll_no' => 'Roll No.', 'exam_form_no' => 'Exam Form No.', 'uin_no' => 'UIN No.', 'reference_no' => 'Reference No.'] as $key => $label)
                @if($fieldEnabled($key))
                <div class="col-md-{{ $isCompact ? 2 : 3 }}">
                    <label class="form-label small fw-semibold mb-1">{{ $label }} @if($fieldRequired($key))<span class="text-danger">*</span>@endif</label>
                    <input type="text" name="{{ $key }}" class="{{ $fc }}" value="{{ $qv($key) }}" {{ $fieldRequired($key) ? 'required' : '' }}>
                </div>
                @endif
            @endforeach

            @if($fieldEnabled('admission_type'))
            <div class="col-md-{{ $isCompact ? 3 : 4 }}">
                <label class="form-label small fw-semibold mb-1">Admission Type @if($fieldRequired('admission_type'))<span class="text-danger">*</span>@endif</label>
                <select name="admission_type" class="{{ $fs }}" {{ $fieldRequired('admission_type') ? 'required' : '' }}>
                    <option value="new" {{ $qv('admission_type','new')=='new' ? 'selected':'' }}>New</option>
                    <option value="lateral" {{ $qv('admission_type','new')=='lateral' ? 'selected':'' }}>Lateral Entry</option>
                    <option value="transfer" {{ $qv('admission_type','new')=='transfer' ? 'selected':'' }}>Transfer</option>
                    <option value="re_admission" {{ $qv('admission_type','new')=='re_admission' ? 'selected':'' }}>Re-Admission</option>
                </select>
            </div>
            @endif

            @if($fieldEnabled('admission_source'))
            @if(!empty($admissionSourceLocked))
            {{-- Portal portal: source pre-locked, read-only --}}
            <div class="col-md-{{ $isCompact ? 3 : 4 }}">
                <label class="form-label small fw-semibold mb-1">Admission Source</label>
                <input type="text" class="{{ $fc }} bg-light"
                       value="{{ ucwords(str_replace('_', ' ', $admissionSourceLocked)) }}" readonly>
                <input type="hidden" name="admission_source" value="{{ $admissionSourceLocked }}">
            </div>
            <div class="col-md-{{ $isCompact ? 3 : 4 }}">
                <label class="form-label small fw-semibold mb-1">{{ $admissionSourceLocked === 'center' ? 'Center' : 'Channel Partner' }}</label>
                <input type="text" class="{{ $fc }} bg-light"
                       value="{{ $admissionSourceLockedName ?? '' }}" readonly>
                <input type="hidden" name="admission_source_id" value="{{ $admissionSourceLockedId ?? '' }}">
            </div>
            @else
            <div class="col-md-{{ $isCompact ? 3 : 4 }}">
                <label class="form-label small fw-semibold mb-1">Admission Source @if($fieldRequired('admission_source'))<span class="text-danger">*</span>@endif</label>
                <select name="admission_source" id="sourceSelect" class="{{ $fs }}" onchange="toggleSourceFields(this.value)" {{ $fieldRequired('admission_source') ? 'required' : '' }}>
                    <option value="direct" {{ $qv('admission_source','direct')=='direct' ? 'selected':'' }}>Direct</option>
                    <option value="center" {{ $qv('admission_source','direct')=='center' ? 'selected':'' }}>Center</option>
                    <option value="channel_partner" {{ $qv('admission_source','direct')=='channel_partner' ? 'selected':'' }}>Channel Partner</option>
                </select>
            </div>
            <div class="col-md-{{ $isCompact ? 3 : 4 }}" id="centerField" style="display:none;">
                <label class="form-label small fw-semibold mb-1">Center</label>
                <select name="admission_source_id" class="{{ $fs }}">
                    <option value="">Select Center</option>
                    @foreach($centers ?? [] as $c)
                        <option value="{{ $c->id }}" {{ (string) $qv('admission_source_id') === (string) $c->id ? 'selected':'' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-{{ $isCompact ? 3 : 4 }}" id="partnerField" style="display:none;">
                <label class="form-label small fw-semibold mb-1">Channel Partner</label>
                <select name="admission_source_id" class="{{ $fs }}">
                    <option value="">Select Partner</option>
                    @foreach($partners ?? [] as $p)
                        <option value="{{ $p->id }}" {{ (string) $qv('admission_source_id') === (string) $p->id ? 'selected':'' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            @endif

            @if($fieldEnabled('gap_year'))
            <div class="col-md-{{ $isCompact ? 2 : 4 }}">
                <label class="form-label small fw-semibold mb-1">Gap Year @if($fieldRequired('gap_year'))<span class="text-danger">*</span>@endif</label>
                <div class="d-flex gap-3 pt-1">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gap_year" value="0" {{ $qv('gap_year', '0') != '1' ? 'checked' : '' }}>
                        <label class="form-check-label small">No</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gap_year" value="1" {{ $qv('gap_year') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label small">Yes</label>
                    </div>
                </div>
            </div>
            @endif

            @if($fieldEnabled('admission_date'))
            <div class="col-md-{{ $isCompact ? 2 : 4 }}">
                <label class="form-label small fw-semibold mb-1">Admission Date @if($fieldRequired('admission_date'))<span class="text-danger">*</span>@endif</label>
                <input type="date" name="admission_date" class="{{ $fc }}" value="{{ $qv('admission_date', now()->toDateString()) }}" {{ $fieldRequired('admission_date') ? 'required' : '' }}>
            </div>
            @endif

            @if($fieldEnabled('submitted_date'))
            <div class="col-md-{{ $isCompact ? 2 : 4 }}">
                <label class="form-label small fw-semibold mb-1">Submitted Date</label>
                <input type="date" name="submitted_date" class="{{ $fc }} bg-light" value="{{ now()->toDateString() }}" readonly>
            </div>
            @endif

            @if($fieldEnabled('academic_session'))
            <div class="col-md-{{ $isCompact ? 2 : 4 }}">
                <label class="form-label small fw-semibold mb-1">Academic Session</label>
                <input type="text" class="{{ $fc }}" value="{{ $activeSession->name }}" readonly>
            </div>
            @endif

        </div>
    </div>
</div>
@endif

{{-- STEP 4: STUDENT DETAILS — form builder fields             --}}
{{-- ══════════════════════════════════════════════════════════ --}}
@php
    $basicFields = ['photo','name','father_name','father_mobile','mother_name','dob','gender','mobile','email','guardian_mobile','religion','category','special_category','nationality','aadhar_no','apaar_no','student_type','marital_status'];
    $basicSectionEnabled = collect($basicFields)->contains(fn($k) => $formConfig[$k]['section_enabled'] ?? false);
    $showBasic = $basicSectionEnabled && collect($basicFields)->contains(fn($k) => $fieldEnabled($k));
@endphp
@if($showBasic)
<div class="card border-0 shadow-sm mb-2">
    <div class="card-header py-2" style="background:#1e293b; color:white;">
        <span class="fw-semibold small"><i class="bi bi-person me-1"></i> Student Details</span>
    </div>
    <div class="card-body {{ $cbp }}">
        <div class="row {{ $gap }}">

            {{-- PHOTO — left-aligned box (like full form) --}}
            @if($fieldEnabled('photo'))
            <div class="col-md-2 text-center">
                <label class="form-label small fw-semibold mb-1">Photo</label>
                <div class="border rounded p-2 text-center"
                     style="height:90px;background:#f8fafc;cursor:pointer;"
                     onclick="document.getElementById('photoInput').click()">
                    <img id="photoPreview" src="{{ $quickPhotoTempUrl ?? '' }}" alt=""
                         style="max-height:70px;max-width:100%;object-fit:cover;{{ $quickPhotoTempUrl ? '' : 'display:none;' }}">
                    <div id="photoPlaceholder" style="{{ $quickPhotoTempUrl ? 'display:none;' : '' }}">
                        <i class="bi bi-camera text-muted" style="font-size:1.5rem;"></i>
                        <div class="text-muted" style="font-size:10px;">Click to upload</div>
                    </div>
                </div>
                @if($quickPhotoTemp)
                    <input type="hidden" name="photo_temp" value="{{ $quickPhotoTemp }}">
                @endif
                <input type="file" name="photo" id="photoInput" class="d-none" accept="image/*" onchange="previewPhoto(this)">
                <div class="form-text" style="font-size:10px;">Photo auto-optimize hogi before save.</div>
                <div id="quickPhotoUploadError" class="text-danger small mt-1" style="display:none;"></div>
            </div>
            <div class="col-md-10"><div class="row {{ $gap }}">
            @else
            <div class="col-12"><div class="row {{ $gap }}">
            @endif

            {{-- NAME --}}
            <div class="col-md-{{ $isCompact ? 4 : 6 }}">
                <label class="form-label small fw-semibold mb-1">Student Name @if($fieldRequired('name'))<span class="text-danger">*</span>@endif</label>
                <input type="text" name="name"
                       class="{{ $fc }} @error('name') is-invalid @enderror"
                       placeholder="Full name" value="{{ $qv('name') }}" {{ $fieldRequired('name') ? 'required' : '' }} autofocus>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- FATHER NAME --}}
            @if($fieldEnabled('father_name'))
            <div class="col-md-{{ $isCompact ? 3 : 6 }}">
                <label class="form-label small fw-semibold mb-1">Father Name @if($fieldRequired('father_name'))<span class="text-danger">*</span>@endif</label>
                <input type="text" name="father_name" class="{{ $fc }}"
                       value="{{ $qv('father_name') }}" {{ $fieldRequired('father_name') ? 'required' : '' }}>
            </div>
            @endif

            {{-- FATHER MOBILE --}}
            @if($fieldEnabled('father_mobile'))
            <div class="col-md-{{ $isCompact ? 3 : 6 }}">
                <label class="form-label small fw-semibold mb-1">Father Mobile @if($fieldRequired('father_mobile'))<span class="text-danger">*</span>@endif</label>
                <input type="text" name="father_mobile" class="{{ $fc }}"
                       value="{{ $qv('father_mobile') }}" maxlength="15" {{ $fieldRequired('father_mobile') ? 'required' : '' }}>
            </div>
            @endif

            {{-- MOTHER NAME --}}
            @if($fieldEnabled('mother_name'))
            <div class="col-md-{{ $isCompact ? 3 : 6 }}">
                <label class="form-label small fw-semibold mb-1">Mother Name @if($fieldRequired('mother_name'))<span class="text-danger">*</span>@endif</label>
                <input type="text" name="mother_name" class="{{ $fc }}"
                       value="{{ $qv('mother_name') }}" {{ $fieldRequired('mother_name') ? 'required' : '' }}>
            </div>
            @endif

            {{-- DOB --}}
            @if($fieldEnabled('dob'))
            <div class="col-md-{{ $isCompact ? 2 : 6 }}">
                <label class="form-label small fw-semibold mb-1">Date of Birth @if($fieldRequired('dob'))<span class="text-danger">*</span>@endif</label>
                <input type="date" name="dob" class="{{ $fc }}"
                       value="{{ $qv('dob') }}" {{ $fieldRequired('dob') ? 'required' : '' }}>
            </div>
            @endif

            {{-- GENDER --}}
            @if($fieldEnabled('gender'))
            <div class="col-md-{{ $isCompact ? 2 : 4 }}">
                <label class="form-label small fw-semibold mb-1">Gender @if($fieldRequired('gender'))<span class="text-danger">*</span>@endif</label>
                <select name="gender" class="{{ $fs }}" {{ $fieldRequired('gender') ? 'required' : '' }}>
                    <option value="">Select</option>
                    <option value="male"   {{ $qv('gender')=='male'   ? 'selected':'' }}>Male</option>
                    <option value="female" {{ $qv('gender')=='female' ? 'selected':'' }}>Female</option>
                    <option value="other"  {{ $qv('gender')=='other'  ? 'selected':'' }}>Other</option>
                </select>
            </div>
            @endif

            {{-- MOBILE --}}
            <div class="col-md-{{ $isCompact ? 3 : 6 }}">
                <label class="form-label small fw-semibold mb-1">Mobile @if($fieldRequired('mobile'))<span class="text-danger">*</span>@endif</label>
                <input type="text" name="mobile"
                       class="{{ $fc }} @error('mobile') is-invalid @enderror"
                       placeholder="10-digit mobile" value="{{ $qv('mobile') }}" {{ $fieldRequired('mobile') ? 'required' : '' }} maxlength="15">
                @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- EMAIL --}}
            @if($fieldEnabled('email'))
            <div class="col-md-{{ $isCompact ? 3 : 6 }}">
                <label class="form-label small fw-semibold mb-1">Email @if($fieldRequired('email'))<span class="text-danger">*</span>@endif</label>
                <input type="email" name="email" class="{{ $fc }}"
                       value="{{ $qv('email') }}" {{ $fieldRequired('email') ? 'required' : '' }}>
            </div>
            @endif

            {{-- GUARDIAN MOBILE --}}
            @if($fieldEnabled('guardian_mobile'))
            <div class="col-md-{{ $isCompact ? 3 : 6 }}">
                <label class="form-label small fw-semibold mb-1">Guardian Mobile @if($fieldRequired('guardian_mobile'))<span class="text-danger">*</span>@endif</label>
                <input type="text" name="guardian_mobile" class="{{ $fc }}"
                       value="{{ $qv('guardian_mobile') }}" maxlength="15" {{ $fieldRequired('guardian_mobile') ? 'required' : '' }}>
            </div>
            @endif

            {{-- RELIGION --}}
            @if($fieldEnabled('religion'))
            <div class="col-md-{{ $isCompact ? 2 : 4 }}">
                <label class="form-label small fw-semibold mb-1">Religion @if($fieldRequired('religion'))<span class="text-danger">*</span>@endif</label>
                <select name="religion" class="{{ $fs }}" {{ $fieldRequired('religion') ? 'required' : '' }}>
                    <option value="">Select</option>
                    @foreach(['hindu','muslim','sikh','christian','jain','buddhist','others'] as $r)
                        <option value="{{ $r }}" {{ $qv('religion')==$r ? 'selected':'' }}>{{ ucfirst($r) }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- CATEGORY --}}
            @if($fieldEnabled('category'))
            <div class="col-md-{{ $isCompact ? 2 : 4 }}">
                <label class="form-label small fw-semibold mb-1">Category @if($fieldRequired('category'))<span class="text-danger">*</span>@endif</label>
                <select name="category" class="{{ $fs }}" {{ $fieldRequired('category') ? 'required' : '' }}>
                    <option value="">Select</option>
                    @foreach(['gen'=>'GEN','obc'=>'OBC','sc'=>'SC','st'=>'ST','ews'=>'EWS'] as $v=>$l)
                        <option value="{{ $v }}" {{ $qv('category')==$v ? 'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- SPECIAL CATEGORY --}}
            @if($fieldEnabled('special_category'))
            <div class="col-md-{{ $isCompact ? 2 : 4 }}">
                <label class="form-label small fw-semibold mb-1">Special Category @if($fieldRequired('special_category'))<span class="text-danger">*</span>@endif</label>
                <select name="special_category" class="{{ $fs }}" {{ $fieldRequired('special_category') ? 'required' : '' }}>
                    <option value="">Select</option>
                    <option value="none" {{ ($qv('special_category') ?: 'none')=='none' ? 'selected':'' }}>None / NA</option>
                    @foreach(['pwd','ex_serviceman','sports','ncc','others'] as $s)
                        <option value="{{ $s }}" {{ $qv('special_category')==$s ? 'selected':'' }}>
                            {{ ucwords(str_replace('_',' ',$s)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- NATIONALITY --}}
            @if($fieldEnabled('nationality'))
            <div class="col-md-{{ $isCompact ? 2 : 4 }}">
                <label class="form-label small fw-semibold mb-1">Nationality @if($fieldRequired('nationality'))<span class="text-danger">*</span>@endif</label>
                <input type="text" name="nationality" class="{{ $fc }}"
                       value="{{ $qv('nationality','Indian') }}" {{ $fieldRequired('nationality') ? 'required' : '' }}>
            </div>
            @endif

            {{-- AADHAR --}}
            @if($fieldEnabled('aadhar_no'))
            <div class="col-md-{{ $isCompact ? 3 : 4 }}">
                <label class="form-label small fw-semibold mb-1">Aadhar No. @if($fieldRequired('aadhar_no'))<span class="text-danger">*</span>@endif</label>
                <input type="text" name="aadhar_no" class="{{ $fc }}"
                       value="{{ $qv('aadhar_no') }}" maxlength="12" {{ $fieldRequired('aadhar_no') ? 'required' : '' }}>
            </div>
            @endif

            {{-- APAAR --}}
            @if($fieldEnabled('apaar_no'))
            <div class="col-md-{{ $isCompact ? 3 : 4 }}">
                <label class="form-label small fw-semibold mb-1">APAAR No. @if($fieldRequired('apaar_no'))<span class="text-danger">*</span>@endif</label>
                <input type="text" name="apaar_no" class="{{ $fc }}"
                       value="{{ $qv('apaar_no') }}" {{ $fieldRequired('apaar_no') ? 'required' : '' }}>
            </div>
            @endif

            {{-- STUDENT TYPE --}}
            @if($fieldEnabled('student_type'))
            <div class="col-md-{{ $isCompact ? 2 : 4 }}">
                <label class="form-label small fw-semibold mb-1">Student Type @if($fieldRequired('student_type'))<span class="text-danger">*</span>@endif</label>
                <select name="student_type" class="{{ $fs }}" {{ $fieldRequired('student_type') ? 'required' : '' }}>
                    @foreach($studentTypes as $st)
                    <option value="{{ $st->slug }}" {{ $qv('student_type', $studentTypes->first()?->slug) == $st->slug ? 'selected' : '' }}>{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- MARITAL STATUS --}}
            @if($fieldEnabled('marital_status'))
            <div class="col-md-{{ $isCompact ? 2 : 4 }}">
                <label class="form-label small fw-semibold mb-1">Marital Status @if($fieldRequired('marital_status'))<span class="text-danger">*</span>@endif</label>
                <select name="marital_status" class="{{ $fs }}" {{ $fieldRequired('marital_status') ? 'required' : '' }}>
                    <option value="single"  {{ $qv('marital_status','single')=='single'  ? 'selected':'' }}>Single</option>
                    <option value="married" {{ $qv('marital_status','single')=='married' ? 'selected':'' }}>Married</option>
                </select>
            </div>
            @endif

            </div></div>{{-- close inner row + photo wrapper col --}}

            {{-- SCHOLARSHIP --}}
            <div class="col-12">
                <div class="border rounded p-3 mt-1" style="background:#f8fafc;">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <label class="fw-semibold small mb-0">
                            <i class="bi bi-award me-1 text-warning"></i> Scholarship
                        </label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="has_scholarship"
                                       value="0" id="scholNo"
                                       {{ ($qv('has_scholarship','0') ?: '0') != '1' ? 'checked' : '' }}
                                       onchange="toggleScholarship(false)">
                                <label class="form-check-label small" for="scholNo">No</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="has_scholarship"
                                       value="1" id="scholYes"
                                       {{ $qv('has_scholarship','0')=='1' ? 'checked' : '' }}
                                       onchange="toggleScholarship(true)">
                                <label class="form-check-label small" for="scholYes">Yes</label>
                            </div>
                        </div>
                    </div>
                    <div id="scholarshipDetails" style="display:{{ $qv('has_scholarship','0')=='1' ? 'block' : 'none' }};">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Scholarship Name</label>
                                <input type="text" name="scholarship_name" class="form-control form-control-sm"
                                       value="{{ $qv('scholarship_name') }}" placeholder="e.g. NSP, State Merit">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Type</label>
                                <select name="scholarship_type" class="form-select form-select-sm">
                                    <option value="">— Select —</option>
                                    <option value="govt_central" {{ $qv('scholarship_type')=='govt_central' ? 'selected':'' }}>Central Govt.</option>
                                    <option value="govt_state"   {{ $qv('scholarship_type')=='govt_state'   ? 'selected':'' }}>State Govt.</option>
                                    <option value="university"   {{ $qv('scholarship_type')=='university'   ? 'selected':'' }}>University</option>
                                    <option value="institute"    {{ $qv('scholarship_type')=='institute'    ? 'selected':'' }}>Institute</option>
                                    <option value="private"      {{ $qv('scholarship_type')=='private'      ? 'selected':'' }}>Private / NGO</option>
                                    <option value="other"        {{ $qv('scholarship_type')=='other'        ? 'selected':'' }}>Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Awarding Authority</label>
                                <input type="text" name="scholarship_authority" class="form-control form-control-sm"
                                       value="{{ $qv('scholarship_authority') }}" placeholder="e.g. UGC, State Welfare Dept.">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Applied Date</label>
                                <input type="date" name="scholarship_applied_date" class="form-control form-control-sm"
                                       value="{{ $qv('scholarship_applied_date') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Amount (₹)</label>
                                <input type="number" name="scholarship_amount" class="form-control form-control-sm"
                                       value="{{ $qv('scholarship_amount') }}" placeholder="0.00" step="0.01" min="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Reference / App. No.</label>
                                <input type="text" name="scholarship_ref_no" class="form-control form-control-sm"
                                       value="{{ $qv('scholarship_ref_no') }}" placeholder="Application No.">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- /row basic --}}

    </div>
</div>
@endif

@php
    $addressFields = ['perm_village','perm_post','perm_thana','perm_district','perm_state','perm_pincode','comm_address'];
    $addressSectionEnabled = collect($addressFields)->contains(fn($k) => $formConfig[$k]['section_enabled'] ?? false);
    $showAddress = $addressSectionEnabled && collect($addressFields)->contains(fn($k) => $fieldEnabled($k));
@endphp
@if($showAddress)
<div class="card border-0 shadow-sm mb-2">
    <div class="card-header py-2" style="background:#1e293b; color:white;">
        <span class="fw-semibold small"><i class="bi bi-geo-alt me-1"></i> Address Details</span>
    </div>
    <div class="card-body {{ $cbp }}">
        <div class="row {{ $gap }}">
            @if($fieldEnabled('perm_village'))
            <div class="col-md-{{ $isCompact ? 2 : 4 }}">
                <label class="form-label small fw-semibold mb-1">Village/City @if($fieldRequired('perm_village'))<span class="text-danger">*</span>@endif</label>
                <input type="text" name="perm_village" id="quickPermVillage" class="{{ $fc }}" value="{{ $qv('perm_village') }}" {{ $fieldRequired('perm_village') ? 'required' : '' }}>
            </div>
            @endif
            @if($fieldEnabled('perm_post'))
            <div class="col-md-{{ $isCompact ? 2 : 4 }}">
                <label class="form-label small fw-semibold mb-1">Post @if($fieldRequired('perm_post'))<span class="text-danger">*</span>@endif</label>
                <input type="text" name="perm_post" id="quickPermPost" class="{{ $fc }}" value="{{ $qv('perm_post') }}" {{ $fieldRequired('perm_post') ? 'required' : '' }}>
            </div>
            @endif
            @if($fieldEnabled('perm_thana'))
            <div class="col-md-{{ $isCompact ? 2 : 4 }}">
                <label class="form-label small fw-semibold mb-1">Thana @if($fieldRequired('perm_thana'))<span class="text-danger">*</span>@endif</label>
                <input type="text" name="perm_thana" id="quickPermThana" class="{{ $fc }}" value="{{ $qv('perm_thana') }}" {{ $fieldRequired('perm_thana') ? 'required' : '' }}>
            </div>
            @endif
            @if($fieldEnabled('perm_state'))
            <div class="col-md-{{ $isCompact ? 2 : 4 }}">
                <label class="form-label small fw-semibold mb-1">State @if($fieldRequired('perm_state'))<span class="text-danger">*</span>@endif</label>
                <select name="perm_state" id="quickPermState" class="{{ $fc }}" {{ $fieldRequired('perm_state') ? 'required' : '' }} data-saved="{{ $qv('perm_state') }}">
                    <option value="">— Select State —</option>
                </select>
            </div>
            @endif
            @if($fieldEnabled('perm_district'))
            <div class="col-md-{{ $isCompact ? 2 : 4 }}">
                <label class="form-label small fw-semibold mb-1">District @if($fieldRequired('perm_district'))<span class="text-danger">*</span>@endif</label>
                <select name="perm_district" id="quickPermDistrict" class="{{ $fc }}" {{ $fieldRequired('perm_district') ? 'required' : '' }} data-saved="{{ $qv('perm_district') }}">
                    <option value="">— Select District —</option>
                </select>
            </div>
            @endif
            @if($fieldEnabled('perm_pincode'))
            <div class="col-md-{{ $isCompact ? 2 : 4 }}">
                <label class="form-label small fw-semibold mb-1">Pin Code @if($fieldRequired('perm_pincode'))<span class="text-danger">*</span>@endif</label>
                <input type="text" name="perm_pincode" id="quickPermPincode" class="{{ $fc }}" value="{{ $qv('perm_pincode') }}" maxlength="6" {{ $fieldRequired('perm_pincode') ? 'required' : '' }}>
            </div>
            @endif
            @if($fieldEnabled('comm_address'))
            <div class="col-12">
                <div class="form-check mb-1">
                    <input class="form-check-input" type="checkbox" id="quickCommSameAsPerm" name="comm_same_as_perm" value="1" {{ old('comm_same_as_perm', $qv('comm_same_as_perm')) ? 'checked' : '' }}>
                    <label class="form-check-label small fw-semibold" for="quickCommSameAsPerm">Same as permanent address</label>
                </div>
                <label class="form-label small fw-semibold mb-1">Communication Address @if($fieldRequired('comm_address'))<span class="text-danger">*</span>@endif</label>
                <textarea name="comm_address" id="quickCommAddress" rows="2" class="{{ $fc }}" {{ $fieldRequired('comm_address') ? 'required' : '' }}>{{ $qv('comm_address') }}</textarea>
            </div>
            @endif
        </div>
    </div>
</div>
@endif

{{-- STEP 5: ACADEMIC DETAILS / ACADEMIC DETAILS                     --}}
{{-- ══════════════════════════════════════════════════════════ --}}
@php
    $eduFields = ['q_edu_10th','q_edu_12th','q_edu_graduation','q_edu_other'];
    $eduLabels = ['q_edu_10th'=>'10TH','q_edu_12th'=>'12TH','q_edu_graduation'=>'GRADUATION','q_edu_other'=>'OTHER'];
    // also check section_enabled — if section is OFF, hide education fields
    $eduSectionEnabled = collect($eduFields)->contains(fn($k) => $formConfig[$k]['section_enabled'] ?? false);
    $showEdu = $eduSectionEnabled &&
               collect($eduFields)->contains(fn($k) => $fieldEnabled($k));
@endphp
@if($showEdu)
<div class="card border-0 shadow-sm mb-2">
    <div class="card-header py-2" style="background:#1e293b; color:white;">
        <span class="fw-semibold small"><i class="bi bi-mortarboard me-1"></i> Academic / Education Details</span>
    </div>
    <div class="card-body p-3">
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0" style="font-size:11px;">
                <thead class="table-light">
                    <tr>
                        <th style="min-width:80px;">EXAM</th>
                        <th style="min-width:110px;">STREAM</th>
                        <th style="min-width:130px;">Institute Name</th>
                        <th style="min-width:80px;">Roll No.</th>
                        <th style="width:55px;">Year</th>
                        <th style="min-width:90px;">District</th>
                        <th style="min-width:80px;">Division</th>
                        <th style="min-width:120px;">Board/University</th>
                        <th style="width:65px;">Marks</th>
                        <th style="width:65px;">Max</th>
                        <th style="width:60px;">%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($eduFields as $i => $fKey)
                    @if($fieldEnabled($fKey))
                    <tr data-edu-key="{{ str_replace('q_edu_', '', $fKey) }}">
                        <td class="fw-semibold text-primary" style="font-size:11px;">{{ $eduLabels[$fKey] }}
                            <input type="hidden" name="education[{{ $i }}][exam_name]" value="{{ $eduLabels[$fKey] }}">
                        </td>
                        @php $eduRow = $qd['education'][$i] ?? []; @endphp
                        <td>
                            @if($eduLabels[$fKey] === '12TH')
                                <select name="education[{{ $i }}][education_stream]" class="form-select form-select-sm" style="min-width:110px;">
                                    <option value="">—</option>
                                    @foreach(['MATHS','BIO','COMMERCE','ARTS','OTHER'] as $streamOption)
                                    <option value="{{ $streamOption }}" {{ ($eduRow['education_stream'] ?? '') == $streamOption ? 'selected' : '' }}>{{ $streamOption }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" name="education[{{ $i }}][education_stream]" class="form-control form-control-sm" style="min-width:110px; text-transform:uppercase;" value="{{ $eduRow['education_stream'] ?? '' }}" {{ $fieldRequired($fKey) ? 'required' : '' }} oninput="this.value=this.value.toUpperCase()">
                            @endif
                        </td>
                        <td><input type="text" name="education[{{ $i }}][institute_name]" class="form-control form-control-sm" style="min-width:120px;" value="{{ $eduRow['institute_name'] ?? '' }}" {{ $fieldRequired($fKey) ? 'required' : '' }}></td>
                        <td><input type="text" name="education[{{ $i }}][roll_number]" class="form-control form-control-sm" style="width:75px;" value="{{ $eduRow['roll_number'] ?? '' }}" {{ $fieldRequired($fKey) ? 'required' : '' }}></td>
                        <td><input type="text" name="education[{{ $i }}][passing_year]" class="form-control form-control-sm" maxlength="4" style="width:55px;" value="{{ $eduRow['passing_year'] ?? '' }}" {{ $fieldRequired($fKey) ? 'required' : '' }}></td>
                        <td><input type="text" name="education[{{ $i }}][district]" class="form-control form-control-sm" style="min-width:85px;" value="{{ $eduRow['district'] ?? '' }}" {{ $fieldRequired($fKey) ? 'required' : '' }}></td>
                        <td>
                            <select name="education[{{ $i }}][division]" class="form-select form-select-sm" style="min-width:75px;">
                                <option value="">—</option>
                                @foreach(['I','II','III','pass','fail'] as $div)
                                <option value="{{ $div }}" {{ ($eduRow['division'] ?? '') == $div ? 'selected' : '' }}>{{ strtoupper($div) }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="text" name="education[{{ $i }}][board_university]" class="form-control form-control-sm" style="min-width:110px;" value="{{ $eduRow['board_university'] ?? '' }}" {{ $fieldRequired($fKey) ? 'required' : '' }}></td>
                        <td><input type="number" name="education[{{ $i }}][obtained_marks]" class="form-control form-control-sm edu-obtained" style="width:60px;" oninput="calcPct(this)" value="{{ $eduRow['obtained_marks'] ?? '' }}" {{ $fieldRequired($fKey) ? 'required' : '' }}></td>
                        <td><input type="number" name="education[{{ $i }}][max_marks]" class="form-control form-control-sm edu-max" style="width:60px;" oninput="calcPct(this)" value="{{ $eduRow['max_marks'] ?? '' }}" {{ $fieldRequired($fKey) ? 'required' : '' }}></td>
                        <td><input type="text" name="education[{{ $i }}][percentage]" class="form-control form-control-sm bg-light edu-percent" style="width:55px;" readonly placeholder="Auto" value="{{ $eduRow['percentage'] ?? '' }}"></td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- ══════════════════════════════════════════════════════════ --}}
{{-- SUBMIT BUTTON                                            --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<div class="card border-0 shadow-sm mb-2" id="quickPaymentSection">
    <div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:#064e3b; color:white;">
        <div>
            <span class="fw-semibold small">
                <i class="bi bi-cash-coin me-1"></i> Fee Payment
            </span>
            <div class="small opacity-75">Admission will be saved once payment is collected.</div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <small class="opacity-75">One-time pay:</small>
            <div class="input-group input-group-sm" style="width:150px;">
                <span class="input-group-text fw-bold" style="background:#fbbf24;color:#1e293b;border:0;">Rs</span>
                <input type="number" id="quickOneTimePay" class="form-control form-control-sm" placeholder="Amount..." min="0" step="0.01">
            </div>
            <button type="button" class="btn btn-sm fw-semibold" style="background:#fbbf24;color:#1e293b;" onclick="applyQuickOneTimePay()">
                <i class="bi bi-lightning-fill me-1"></i>Fill
            </button>
            <button type="button" class="btn btn-outline-light btn-sm fw-semibold" onclick="clearAllQuickFields()" title="Clear all fields">
                <i class="bi bi-x-circle me-1"></i>Clear
            </button>
            <div class="text-end ms-md-2">
                <div class="small opacity-75">Total Selected</div>
                <div class="fw-bold fs-5" style="color:#4ade80;" id="quickGrandTotal">Rs 0</div>
            </div>
        </div>
    </div>
    <div class="card-body {{ $cbp }}">
        <div id="quickFeeEmpty" class="alert alert-warning mb-3">
            Fee items will load here once you select a course, stream and subjects.
        </div>

        <div id="quickFeePanel" style="display:none;">
            <div class="table-responsive mb-3">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:34px;" class="text-center">✓</th>
                            <th>Fee Item</th>
                            <th class="text-end">Assigned</th>
                            <th class="text-end">Collect</th>
                            <th class="text-end">Fine</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody id="quickFeeRows"></tbody>
                </table>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-3 col-6">
                    <div class="rounded-4 border-0 h-100 p-3 text-center" style="background:#eff6ff;">
                        <div style="font-size:10px;color:#64748b;">Total Collected</div>
                        <div class="fw-bold fs-5 text-primary" id="quickSummaryCollected">Rs 0.00</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="rounded-4 border-0 h-100 p-3 text-center" style="background:#fef2f2;">
                        <div style="font-size:10px;color:#b91c1c;">Total Fine</div>
                        <div class="fw-bold fs-5 text-danger" id="quickSummaryFine">Rs 0.00</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="rounded-4 border-0 h-100 p-3 text-center" style="background:#fffbeb;">
                        <div style="font-size:10px;color:#92400e;">Total Discount</div>
                        <div class="fw-bold fs-5 text-warning" id="quickSummaryDiscount">Rs 0.00</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="rounded-4 border-0 h-100 p-3 text-center text-white" style="background:#1e293b;">
                        <div style="font-size:12px;opacity:.75;">Total Due</div>
                        <div class="fw-bold" style="font-size:2rem;line-height:1;" id="quickSummaryNet">Rs 0.00</div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Semester <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap gap-3 pt-1" id="semesterRadioContainer">
                        @php $selectedSem = (int) old('semester', 1); @endphp
                        {{-- Populated dynamically by updateSemesterOptions() when course changes --}}
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="semester" id="quick_sem_1" value="1"
                                   {{ $selectedSem === 1 ? 'checked' : '' }} onchange="refreshQuickFeePreview()">
                            <label class="form-check-label small" for="quick_sem_1">Semester 1</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="semester" id="quick_sem_2" value="2"
                                   {{ $selectedSem === 2 ? 'checked' : '' }} onchange="refreshQuickFeePreview()">
                            <label class="form-check-label small" for="quick_sem_2">Semester 2</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control"
                           value="{{ old('payment_date', $defaultPaymentDate) }}"
                           {{ $lockPaymentDate ? 'readonly' : '' }}>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Payment Mode <span class="text-danger">*</span></label>
                    <select name="payment_mode" id="quickPaymentMode" class="form-select" onchange="toggleQuickBankAccount()">
                        @foreach($allowedModes as $mode)
                            <option value="{{ $mode }}" {{ old('payment_mode', 'cash') === $mode ? 'selected' : '' }}>
                                {{ $paymentModeLabels[$mode] ?? strtoupper($mode) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6" id="quickBankWrap" style="display:none;">
                    <label class="form-label small fw-semibold">Bank Account</label>
                    <select name="bank_account_id" id="quickBankAccount" class="form-select">
                        <option value="">Select Bank Account</option>
                        @foreach($bankAccounts as $bankAccount)
                            <option value="{{ $bankAccount->id }}"
                                    data-modes="{{ $bankModeOverride ?? ($bankAccount->allowed_payment_modes ?? 'cash,upi,online,cheque,dd,neft,rtgs') }}"
                                    {{ (string) old('bank_account_id') === (string) $bankAccount->id ? 'selected' : '' }}>
                                {{ $bankAccount->account_name }} - {{ $bankAccount->bank_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6" id="quickTransactionRefWrap">
                    <label class="form-label small fw-semibold" id="quickTransactionRefLabel">Transaction Ref</label>
                    <input type="text" name="transaction_ref" id="quickTransactionRef" class="form-control"
                           value="{{ old('transaction_ref') }}" placeholder="Txn / UTR / Cheque no.">
                </div>

                <div class="col-md-6" id="quickPaymentTimeWrap" style="display:none;">
                    <label class="form-label small fw-semibold">
                        Payment Date & Time <span class="text-danger">*</span>
                        <span class="text-muted fw-normal" style="font-size:11px;">(Actual payment  time)</span>
                    </label>
                    <input type="datetime-local" name="payment_datetime" id="quickPaymentDatetime" class="form-control"
                           value="{{ old('payment_datetime') }}">
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold">Remarks</label>
                    <input type="text" name="remarks" class="form-control"
                           value="{{ old('remarks') }}" placeholder="Optional note">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-grid mt-3">
    <button type="submit" class="btn fw-bold py-2" id="quickSubmitBtn"
            style="background:#1e293b; color:white; font-size:14px; letter-spacing:.3px;">
        <i class="bi bi-eye me-2"></i> Preview & Verify — Before Saving
    </button>
</div>
<div class="text-center mt-2 mb-3">
    <small class="text-muted">
        <i class="bi bi-info-circle me-1"></i>
        Confirm the preview, then Save → Payment → Receipt
    </small>
</div>

</form>
@endif

</div>
</div>

@include('partials._india-geo')

@push('scripts')
{{-- TomSelect for subject dropdowns --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<style>
#quickForm input[type="text"]:not([readonly]),
#quickForm textarea:not([readonly]) {
    text-transform: uppercase;
}
</style>
@include('institute.admission._live-validation-script')
@php
$courseDataRaw = $courses->map(function($c) {
    $parts = $c->parts
        ->sortBy('year_number')
        ->unique('year_number')
        ->values();

    return [
        'id'                 => $c->id,
        'semesters_per_year' => $c->effectiveSemestersPerYear(),
        'structure_type'     => $c->structure_type,
        'streams' => $c->streams->map(function($s) { return ['id' => $s->id, 'name' => $s->name]; })->values(),
        'parts'   => $parts->map(function($p) { return ['id' => $p->id, 'name' => $p->year_label, 'year' => $p->year_number]; })->values(),
    ];
})->keyBy('id');
@endphp
@php
$feePlansDataRaw = isset($feePlans) ? $feePlans->map(function($p) {
    return [
        'id'           => $p->id,
        'name'         => $p->name,
        'course_id'    => $p->course_id,
        'installments' => $p->installments->map(function($i) {
            return [
                'number'     => $i->installment_number,
                'label'      => $i->label,
                'percentage' => (float) $i->percentage,
                'trigger'    => $i->due_trigger,
            ];
        })->values()->all(),
    ];
})->values()->all() : [];
@endphp
<script>
const courseData = @json($courseDataRaw);
const feePlansData = @json($feePlansDataRaw);

// Dynamic URLs — different routes for staff/center/partner portals
const SEATS_URL    = "{{ isset($seatsUrl)    ? $seatsUrl    : route('admissions.stream-seats') }}";
const SUBJECTS_URL = "{{ isset($subjectsUrl) ? $subjectsUrl : route('admissions.stream-subjects') }}";
const FEE_PREVIEW_URL = "{{ isset($feePreviewUrl) ? $feePreviewUrl : route('admissions.fee-preview') }}";
const OLD_FEE_ITEMS = @json(old('fee_items', []));
const STAFF_MAX_DISCOUNT = {{ isset($staffMaxDiscount) && $staffMaxDiscount !== null ? (int)$staffMaxDiscount : 'null' }};
const STAFF_FEE_ALLOWED_TYPES = @json($staffFeeAllowedTypes ?? null);

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

function filterCoursesByType(typeId) {
    const courseSel = document.getElementById('courseSelect');
    const currentVal = courseSel.value;
    let firstVisible = null;

    Array.from(courseSel.options).forEach(opt => {
        if (!opt.value) return; // keep "Select Course" option
        const matches = !typeId || opt.dataset.typeId === String(typeId);
        opt.hidden   = !matches;
        opt.disabled = !matches;
        if (matches && !firstVisible) firstVisible = opt.value;
    });

    // Reset course if current selection no longer matches
    if (currentVal && courseSel.options[courseSel.selectedIndex]?.dataset.typeId !== String(typeId)) {
        courseSel.value = '';
        loadStreams('');
    }
    updateEduRows(typeId);
}

function showFeePlanPreview(planId) {
    const preview = document.getElementById('feePlanPreview');
    if (!preview) return;
    if (!planId) { preview.innerHTML = ''; return; }

    const plan = feePlansData.find(p => p.id == planId);
    if (!plan || !plan.installments.length) { preview.innerHTML = ''; return; }

    const parts = plan.installments.map(i =>
        `<span class="badge bg-light text-dark border me-1">${i.label}: ${i.percentage}%</span>`
    ).join('');
    preview.innerHTML = parts;
}

// Filter fee plan options when course changes — hide course-specific plans of other courses
function filterFeePlans(courseId) {
    const sel = document.getElementById('feePlanSelect');
    if (!sel) return;
    Array.from(sel.options).forEach(opt => {
        const optCourse = opt.dataset.courseId;
        // hide if plan belongs to a different course
        opt.hidden = optCourse && optCourse != courseId;
    });
    // re-trigger preview for currently selected
    showFeePlanPreview(sel.value);
}

function updateSemesterOptions(courseId) {
    const container = document.getElementById('semesterRadioContainer');
    if (!container) return;

    const course = courseData[courseId];
    const spy    = course ? (course.semesters_per_year || 2) : 2;
    const isTriSem = course && course.structure_type === 'trimester';
    const label  = isTriSem ? 'Trimester' : 'Semester';

    container.innerHTML = '';
    const currentVal = parseInt(document.querySelector('input[name="semester"]:checked')?.value || '1');
    for (let i = 1; i <= spy; i++) {
        const id = 'quick_sem_' + i;
        container.innerHTML += `<div class="form-check">
            <input class="form-check-input" type="radio" name="semester" id="${id}" value="${i}"
                   ${i === (currentVal <= spy ? currentVal : 1) ? 'checked' : ''}
                   onchange="refreshQuickFeePreview()">
            <label class="form-check-label small" for="${id}">${label} ${i}</label>
        </div>`;
    }
}

function loadStreams(courseId) {
    const streamSel = document.getElementById('streamSelect');
    const partSel   = document.getElementById('partSelect');
    const partWrap  = document.getElementById('partWrap');

    streamSel.innerHTML = '<option value="">— Select Stream —</option>';
    partSel.innerHTML   = '<option value="">— Select Year —</option>';
    partWrap.style.display = 'none';

    updateSemesterOptions(courseId);
    filterFeePlans(courseId);

    if (!courseId || !courseData[courseId]) return;

    courseData[courseId].streams.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = s.name;
        streamSel.appendChild(opt);
    });

    // Auto-select if only one stream available
    if (courseData[courseId].streams.length === 1) {
        streamSel.value = courseData[courseId].streams[0].id;
        loadParts(courseId);
    }
}

function loadParts(courseId) {
    courseId = courseId || document.getElementById('courseSelect').value;
    const partSel  = document.getElementById('partSelect');
    const partWrap = document.getElementById('partWrap');

    const parts = [...(courseData[courseId]?.parts ?? [])]
        .sort((a, b) => (a.year || 0) - (b.year || 0))
        .slice(0, 1);
    partSel.innerHTML = '<option value="">— Select Year —</option>';

    if (parts.length === 0) {
        partWrap.style.display = 'none';
        return;
    }

    partSel.innerHTML = '';

    parts.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.dataset.year = p.year;
        opt.textContent = p.name;
        partSel.appendChild(opt);
    });

    partWrap.style.display = 'block';

    // New admission by default 1st year/part se hi start hoga
    partSel.value = parts[0].id;
    const streamId = document.getElementById('streamSelect').value;
    if (streamId) loadSubjects(streamId, parts[0].year);
}

// On stream selection, also load parts + subjects
document.getElementById('streamSelect').addEventListener('change', function() {
    const courseId = document.getElementById('courseSelect').value;
    if (courseId) loadParts(courseId);
    // Reset subjects on stream change
    hideSubjectSection();
    if (courseId) loadParts(courseId);
});

// Load subjects when a part is selected
document.getElementById('partSelect').addEventListener('change', function() {
    const streamId = document.getElementById('streamSelect').value;
    if (streamId && this.value) {
        const year = this.options[this.selectedIndex]?.dataset?.year || 1;
        loadSubjects(streamId, parseInt(year));
    } else {
        hideSubjectSection();
    }
});

// Admission source toggle
function toggleSourceFields(val) {
    const centerField   = document.getElementById('centerField');
    const partnerField  = document.getElementById('partnerField');
    const centerSelect  = centerField  ? centerField.querySelector('select')  : null;
    const partnerSelect = partnerField ? partnerField.querySelector('select') : null;

    if (centerField)  centerField.style.display  = val === 'center'          ? 'block' : 'none';
    if (partnerField) partnerField.style.display = val === 'channel_partner' ? 'block' : 'none';

    if (centerSelect)  centerSelect.disabled  = val !== 'center';
    if (partnerSelect) partnerSelect.disabled = val !== 'channel_partner';
}

// Restore values on page load (validation fail ya preview edit pe)
window.addEventListener('DOMContentLoaded', function() {
    const oldCourse = '{{ old('course_id', $qd['course_id'] ?? '') }}';
    const oldStream = '{{ old('course_stream_id', $qd['course_stream_id'] ?? '') }}';
    const oldPart   = '{{ old('course_part_id', $qd['course_part_id'] ?? '') }}';
    const oldSource = '{{ old('admission_source', $qd['admission_source'] ?? 'direct') }}';

    if (oldCourse) {
        loadStreams(oldCourse);
        setTimeout(() => {
            const streamSel = document.getElementById('streamSelect');
            if (oldStream) {
                streamSel.value = oldStream;
                checkStreamSeats(oldStream); // seat info bhi restore
            }
            loadParts(oldCourse);
            setTimeout(() => {
                const partSel = document.getElementById('partSelect');
                if (oldPart) {
                    partSel.value = oldPart;
                    // Restore subjects
                    const selOpt = partSel.options[partSel.selectedIndex];
                    const yr = parseInt(selOpt?.dataset?.year || 1);
                    if (oldStream) loadSubjects(oldStream, yr);
                }
            }, 400);
        }, 300);
    }

    toggleSourceFields(oldSource);
    toggleQuickBankAccount();
});

// ── Seat availability check ──────────────────────────────────────────
function checkStreamSeats(streamId) {
    const infoDiv = document.getElementById('seatInfo');
    const submitBtn = document.querySelector('button[type="submit"]');
    if (!streamId) { infoDiv.innerHTML = ''; return; }

    fetch(`${SEATS_URL}?stream_id=${streamId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.limit) {
                infoDiv.innerHTML = '<span class="text-muted">Unlimited seats</span>';
                if (submitBtn) submitBtn.disabled = false;
                return;
            }
            const pct = Math.min(100, Math.round((data.filled / data.limit) * 100));
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
        .catch(() => { infoDiv.innerHTML = ''; });
}


let minorMin = 0, minorMax = 99;
let majorMin = 1, majorMax = 99;
let selectedMinors = new Set();

function hideSubjectSection() {
    document.getElementById('subjectSection').style.display = 'none';
    document.getElementById('subjectContent').innerHTML = '';
    if (window.majorTS) { window.majorTS.destroy(); window.majorTS = null; }
    if (window.minorTS) { window.minorTS.destroy(); window.minorTS = null; }
    renderQuickFeeRows([]);
}

// Previously selected subjects (edit mode restore)
const preSelectedSubjects = @json(array_map('intval', $qd['selected_subjects'] ?? []));

function loadSubjects(streamId, yearNumber) {
    document.getElementById('subjectSection').style.display = 'block';
    document.getElementById('subjectLoading').style.display = 'block';
    document.getElementById('subjectContent').innerHTML = '';

    fetch(`${SUBJECTS_URL}?stream_id=${streamId}&year_number=${yearNumber}`, {
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
        refreshQuickFeePreview();
    })
    .catch(() => {
        document.getElementById('subjectLoading').style.display = 'none';
        renderQuickFeeRows([]);
    });
}

function renderSubjects(subjects) {
    if (window.majorTS) { window.majorTS.destroy(); window.majorTS = null; }
    if (window.minorTS) { window.minorTS.destroy(); window.minorTS = null; }

    const compulsory    = subjects.filter(s => s.role === 'compulsory' || !s.is_chooseable);
    const majorSubjects = subjects.filter(s => s.role === 'major' || s.role === 'both');
    const minorSubjects = subjects.filter(s => s.role === 'minor' || s.role === 'optional' || s.role === 'both');

    let html = '';

    if (compulsory.length) {
        html += `<div class="mb-3">
            <div class="fw-semibold small text-success mb-2">
                <i class="bi bi-check-circle-fill me-1"></i>Compulsory Subjects
                <span class="fw-normal text-muted">(Quick registration me inhe bhi skip kar sakte ho)</span>
            </div>
            <div class="d-flex flex-wrap gap-2">`;
        compulsory.forEach(s => {
            const checked = preSelectedSubjects.includes(Number(s.id)) ? 'checked' : '';
            html += `<label class="d-flex align-items-center gap-2 px-3 py-2 rounded border bg-success bg-opacity-10" style="cursor:pointer;">
                <input type="checkbox" class="form-check-input mt-0" name="selected_subjects[]" value="${s.id}" ${checked}
                       onchange="refreshQuickFeePreview()">
                <span class="fw-semibold small">${s.name}</span>
                ${s.code ? `<span class="text-muted small">(${s.code})</span>` : ''}
                <span class="badge bg-success ms-1" style="font-size:9px;">Compulsory</span>
                ${s.has_practical ? '<span class="badge bg-warning text-dark ms-1" style="font-size:9px;">Practical</span>' : ''}
            </label>`;
        });
        html += `</div></div>`;
    }

    if (majorSubjects.length) {
        const majorInfo = majorMax < 99 ? `Optional now, later rule ${majorMin}-${majorMax}` : 'Optional';
        html += `<div class="mb-3">
            <label class="form-label small fw-semibold text-primary">
                <i class="bi bi-star-fill me-1"></i>Select Major Subject(s)
                <span class="fw-normal text-muted">(${majorInfo})</span>
            </label>
            <select id="majorSelect" name="selected_subjects[]" multiple placeholder="Select major subject...">`;
        majorSubjects.forEach(s => {
            html += `<option value="${s.id}">${s.name}${s.code ? ' (' + s.code + ')' : ''}${s.has_practical ? ' (Practical)' : ''}</option>`;
        });
        html += `</select></div>`;
    }

    if (minorSubjects.length) {
        const minorInfo = minorMax > 0 && minorMax < 99 ? `Optional now, later max ${minorMax}` : 'Optional';
        html += `<div class="mb-2">
            <label class="form-label small fw-semibold text-info">
                <i class="bi bi-list-check me-1"></i>Select Minor Subject(s)
                <span class="fw-normal text-muted">(${minorInfo})</span>
            </label>
            <div id="minorCountBadge" class="mb-1"></div>
            <select id="minorSelect" name="selected_subjects[]" multiple placeholder="Select minor subject...">`;
        minorSubjects.forEach(s => {
            html += `<option value="${s.id}">${s.name}${s.code ? ' (' + s.code + ')' : ''}${s.has_practical ? ' (Practical)' : ''}</option>`;
        });
        html += `</select></div>`;
        document.getElementById('minorCountInfo').textContent = minorInfo;
    }

    document.getElementById('subjectContent').innerHTML = html;

    const majorEl = document.getElementById('majorSelect');
    if (majorEl) {
        window.majorTS = new TomSelect('#majorSelect', {
            plugins: ['remove_button'],
            maxItems: majorMax < 99 ? majorMax : null,
            maxOptions: 100,
            placeholder: 'Select major subject...',
            onItemAdd: function() { syncMajorMinorExclusion(); refreshQuickFeePreview(); },
            onItemRemove: function() { syncMajorMinorExclusion(); refreshQuickFeePreview(); },
        });
        // Restore previously selected major subjects (edit mode)
        if (preSelectedSubjects.length) {
            const majorOptions = majorSubjects.map(s => String(s.id));
            const toSelect = preSelectedSubjects.filter(id => majorOptions.includes(String(id)));
            if (toSelect.length) window.majorTS.setValue(toSelect.map(String));
        }
    }

    const minorEl = document.getElementById('minorSelect');
    if (minorEl) {
        window.minorTS = new TomSelect('#minorSelect', {
            plugins: ['remove_button'],
            maxItems: minorMax > 0 ? minorMax : null,
            maxOptions: 100,
            placeholder: 'Select minor subject...',
            onItemAdd: function() { refreshQuickFeePreview(); },
            onItemRemove: function() { refreshQuickFeePreview(); },
        });
        // Restore previously selected minor subjects (edit mode)
        if (preSelectedSubjects.length) {
            const minorOptions = minorSubjects.map(s => String(s.id));
            const toSelect = preSelectedSubjects.filter(id => minorOptions.includes(String(id)));
            if (toSelect.length) window.minorTS.setValue(toSelect.map(String));
        }
    }

    // Initial sync
    if (window.majorTS && window.minorTS) syncMajorMinorExclusion();
}

// Disable subjects selected in Major from Minor options
function syncMajorMinorExclusion() {
    if (!window.majorTS || !window.minorTS) return;
    const selectedMajors = window.majorTS.getValue(); // array of selected values
    // Check all Minor options
    Object.keys(window.minorTS.options).forEach(val => {
        if (selectedMajors.includes(val)) {
            window.minorTS.updateOption(val, { ...window.minorTS.options[val], disabled: true });
        } else {
            window.minorTS.updateOption(val, { ...window.minorTS.options[val], disabled: false });
        }
    });
    window.minorTS.refreshOptions(false);
}

function toggleScholarship(show) {
    const d = document.getElementById('scholarshipDetails');
    if (d) d.style.display = show ? 'block' : 'none';
}

function previewPhoto(input) {
    const errorBox = document.getElementById('quickPhotoUploadError');
    if (errorBox) {
        errorBox.style.display = 'none';
        errorBox.textContent = '';
    }

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('photoPreview').src = e.target.result;
            document.getElementById('photoPreview').style.display = 'block';
            document.getElementById('photoPlaceholder').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

async function compressQuickImageFile(file, {
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

async function prepareQuickPhotoForSubmit(form) {
    const input = form.querySelector('#photoInput');
    const errorBox = document.getElementById('quickPhotoUploadError');

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
        const optimizedFile = await compressQuickImageFile(originalFile);
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

function calcPct(input) {
    const row = input.closest('tr');
    const obt = parseFloat(row.querySelector('.edu-obtained')?.value) || 0;
    const max = parseFloat(row.querySelector('.edu-max')?.value) || 0;
    const pct = row.querySelector('.edu-percent');
    if (pct && max > 0) pct.value = ((obt / max) * 100).toFixed(2);
}

function getSelectedSubjectIds() {
    const ids = new Set();

    if (window.majorTS) {
        const majorValues = window.majorTS.getValue();
        (Array.isArray(majorValues) ? majorValues : [majorValues]).forEach(value => {
            if (value) ids.add(String(value));
        });
    }

    if (window.minorTS) {
        const minorValues = window.minorTS.getValue();
        (Array.isArray(minorValues) ? minorValues : [minorValues]).forEach(value => {
            if (value) ids.add(String(value));
        });
    }

    document.querySelectorAll('input[name="selected_subjects[]"]').forEach(input => {
        if (input.type === 'checkbox' && !input.checked) return;
        if (input.value) ids.add(String(input.value));
    });

    document.querySelectorAll('select[name="selected_subjects[]"]').forEach(select => {
        Array.from(select.selectedOptions).forEach(option => {
            if (option.value) ids.add(String(option.value));
        });
    });
    return Array.from(ids);
}

function getOldFeeItemMap() {
    const map = {};
    (OLD_FEE_ITEMS || []).forEach(item => {
        if (item && item.fee_name) {
            map[item.fee_name] = item;
        }
    });
    return map;
}

function quickNumber(value) {
    return parseFloat(value) || 0;
}

let quickRedistributing = false;
let quickOneTimePayLocked = false;
let quickFeePreviewRequestId = 0;

function getCurrentFeeItemMap() {
    const map = {};
    document.querySelectorAll('#quickFeeRows tr').forEach((row, index) => {
        const feeName = row.querySelector(`input[name="fee_items[${index}][fee_name]"]`)?.value;
        if (!feeName) return;

        map[feeName] = {
            checked: row.querySelector(`input[name="fee_items[${index}][checked]"]`)?.checked ? 1 : 0,
            amount: row.querySelector(`input[name="fee_items[${index}][amount]"]`)?.value ?? '',
            fine: row.querySelector(`input[name="fee_items[${index}][fine]"]`)?.value ?? '',
            discount: row.querySelector(`input[name="fee_items[${index}][discount]"]`)?.value ?? '',
        };
    });
    return map;
}

function quickRowIndexes() {
    return Array.from(document.querySelectorAll('.quick-fee-check')).map((_, index) => index);
}

function getQuickRowState(index) {
    const checkbox = document.querySelector(`input[name="fee_items[${index}][checked]"]`);
    const amountInput = document.getElementById(`quick_amt_${index}`);
    const fineInput = document.getElementById(`quick_fine_${index}`);
    const discountInput = document.getElementById(`quick_disc_${index}`);
    const assigned = quickNumber(amountInput?.dataset.assigned);

    return {
        checkbox,
        amountInput,
        fineInput,
        discountInput,
        assigned,
        amount: quickNumber(amountInput?.value),
        fine: quickNumber(fineInput?.value),
        discount: quickNumber(discountInput?.value),
    };
}

function refreshQuickFeePreview() {
    const streamId = document.getElementById('streamSelect')?.value;
    if (!streamId) {
        renderQuickFeeRows([]);
        return;
    }

    const preservedMap = getCurrentFeeItemMap();
    const requestId = ++quickFeePreviewRequestId;

    const selectedSemester = parseInt(document.querySelector('input[name="semester"]:checked')?.value || 1);

    const payload = {
        stream_id: streamId,
        course_part_id: document.getElementById('partSelect')?.value || null,
        subject_ids: getSelectedSubjectIds(),
        semester: selectedSemester,
        student_type: document.querySelector('[name="student_type"]')?.value || 'regular',
        admission_source: document.getElementById('sourceSelect')?.value || document.querySelector('input[name="admission_source"]')?.value || 'direct',
        category: document.querySelector('[name="category"]')?.value || 'general',
        gender: document.querySelector('[name="gender"]')?.value || 'other',
    };

    fetch(FEE_PREVIEW_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || '',
        },
        body: JSON.stringify(payload),
    })
        .then(response => {
            if (!response.ok) {
                return response.text().then(() => Promise.reject(response.status));
            }
            return response.json();
        })
        .then(data => {
            if (requestId !== quickFeePreviewRequestId) return;
            renderQuickFeeRows(data?.fee_data?.items || [], preservedMap);
        })
        .catch(() => {
            // On error, preserve existing rows — do not silently clear them
        });
}

function renderQuickFeeRows(items, preservedMap = null) {
    const panel = document.getElementById('quickFeePanel');
    const empty = document.getElementById('quickFeeEmpty');
    const rows = document.getElementById('quickFeeRows');
    if (!rows || !panel || !empty) return;

    if (!items.length) {
        rows.innerHTML = '';
        panel.style.display = 'none';
        empty.style.display = 'block';
        updateQuickGrandTotal();
        return;
    }

    const oldMap = getOldFeeItemMap();
    const currentMap = preservedMap || getCurrentFeeItemMap();
    rows.innerHTML = items.map((item, index) => {
        const feeName = item.label || `Fee ${index + 1}`;
        const savedItem = currentMap[feeName] || oldMap[feeName] || {};
        const assigned = Number(item.amount || 0);
        const checked = savedItem.checked !== undefined ? Boolean(Number(savedItem.checked)) : true;
        const amount = savedItem.amount !== undefined && savedItem.amount !== '' ? Number(savedItem.amount) : 0;
        const fine = savedItem.fine !== undefined && savedItem.fine !== '' ? Number(savedItem.fine) : 0;
        const feeTypeId = item.fee_type_id ? Number(item.fee_type_id) : null;
        const discAllowed = STAFF_FEE_ALLOWED_TYPES === null || (feeTypeId && STAFF_FEE_ALLOWED_TYPES.includes(feeTypeId));
        const discount = discAllowed ? (savedItem.discount !== undefined && savedItem.discount !== '' ? Number(savedItem.discount) : 0) : 0;
        const net = Math.max(0, assigned + fine - discount - (checked ? amount : 0));

        return `
            <tr>
                <td class="text-center">
                    <input type="checkbox" class="form-check-input quick-fee-check"
                           name="fee_items[${index}][checked]" value="1" ${checked ? 'checked' : ''}
                           onchange="toggleQuickFeeRow(${index})">
                </td>
                <td>
                    <input type="hidden" name="fee_items[${index}][item_key]" value="${item.item_key ?? ''}">
                    <input type="hidden" name="fee_items[${index}][fee_type_id]" value="${item.fee_type_id ?? ''}">
                    <input type="hidden" name="fee_items[${index}][fee_name]" value="${feeName}">
                    <input type="hidden" name="fee_items[${index}][total_fee]" value="${assigned.toFixed(2)}">
                    <span class="small fw-semibold">${feeName}</span>
                </td>
                <td class="text-end small text-muted">${assigned.toFixed(2)}</td>
                <td>
                    <input type="number" min="0" step="0.01" class="form-control form-control-sm text-end quick-fee-input"
                           name="fee_items[${index}][amount]" id="quick_amt_${index}" value="${amount.toFixed(2)}" data-assigned="${assigned.toFixed(2)}"
                           oninput="updateQuickFeeNet(${index})"
                           onchange="updateQuickAmount(${index})">
                </td>
                <td>
                    <input type="number" min="0" step="0.01" class="form-control form-control-sm text-end quick-fee-input"
                           name="fee_items[${index}][fine]" id="quick_fine_${index}" value="${fine.toFixed(2)}"
                           oninput="updateQuickFeeNet(${index})"
                           onchange="updateQuickFine(${index})">
                </td>
                <td>
                    <input type="number" min="0" step="0.01" class="form-control form-control-sm text-end quick-fee-input"
                           name="fee_items[${index}][discount]" id="quick_disc_${index}" value="${discount.toFixed(2)}"
                           data-max-disc="${discAllowed ? (STAFF_MAX_DISCOUNT ?? 100) : 0}"
                           ${!discAllowed ? 'disabled' : ''}
                           oninput="updateQuickFeeNet(${index})"
                           onchange="updateQuickAdjustments(${index})">
                </td>
                <td class="text-end fw-semibold" id="quick_net_${index}">${net.toFixed(2)}</td>
            </tr>
        `;
    }).join('');

    panel.style.display = 'block';
    empty.style.display = 'none';
    quickRowIndexes().forEach(toggleQuickFeeRow);
    toggleQuickBankAccount();
    updateQuickGrandTotal();
}

function toggleQuickFeeRow(index) {
    const state = getQuickRowState(index);
    const checked = Boolean(state.checkbox?.checked);
    [state.amountInput, state.fineInput].forEach(input => {
        if (input) input.disabled = !checked;
    });
    // Discount respects staff permission limit — only enable if row checked AND discount is allowed
    if (state.discountInput) {
        const maxDisc = parseFloat(state.discountInput.dataset.maxDisc ?? 100);
        state.discountInput.disabled = !checked || maxDisc <= 0;
    }
    const net = Math.max(
        0,
        state.assigned
            + quickNumber(state.fineInput?.value)
            - quickNumber(state.discountInput?.value)
            - (checked ? quickNumber(state.amountInput?.value) : 0)
    );
    const netCell = document.getElementById(`quick_net_${index}`);
    if (netCell) netCell.textContent = net.toFixed(2);
    if (!quickRedistributing && quickOneTimePayLocked && quickHasOneTimePay()) {
        redistributeQuickOneTimePay();
        return;
    }
    updateQuickGrandTotal();
}

function quickHasOneTimePay() {
    return quickNumber(document.getElementById('quickOneTimePay')?.value) > 0;
}

function updateQuickFeeNet(index) {
    const state = getQuickRowState(index);
    if (!state.amountInput || !state.fineInput || !state.discountInput) return;

    if (state.fine < 0) {
        state.fineInput.value = '0.00';
    }

    const payable = Math.max(0, state.assigned + quickNumber(state.fineInput.value));
    const maxDiscPct = parseFloat(state.discountInput.dataset.maxDisc ?? 100);
    const maxDiscAmt = maxDiscPct <= 0 ? 0 : Math.min(payable, payable * maxDiscPct / 100);
    if (maxDiscPct <= 0) {
        state.discountInput.value = '0.00';
    } else if (quickNumber(state.discountInput.value) > maxDiscAmt) {
        state.discountInput.value = maxDiscAmt.toFixed(2);
    } else if (quickNumber(state.discountInput.value) > payable) {
        state.discountInput.value = payable.toFixed(2);
    }

    const amount = quickNumber(state.amountInput.value);
    const fine = quickNumber(state.fineInput.value);
    const discount = quickNumber(state.discountInput.value);
    const net = Math.max(0, state.assigned + fine - discount - amount);
    const netCell = document.getElementById(`quick_net_${index}`);
    if (netCell) netCell.textContent = net.toFixed(2);
    updateQuickGrandTotal();
}

function updateQuickAmount(index) {
    const state = getQuickRowState(index);
    if (!state.amountInput) return;

    const payable = Math.max(0, state.assigned + quickNumber(state.fineInput?.value) - quickNumber(state.discountInput?.value));
    if (quickNumber(state.amountInput.value) > payable) {
        state.amountInput.value = payable.toFixed(2);
    }

    updateQuickFeeNet(index);
}

function updateQuickAdjustments(index) {
    const state = getQuickRowState(index);
    if (!state.amountInput || !state.fineInput || !state.discountInput) return;

    if (state.fine < 0) {
        state.fineInput.value = '0.00';
    }

    const payable = Math.max(0, state.assigned + quickNumber(state.fineInput.value));
    const maxDiscPct = parseFloat(state.discountInput.dataset.maxDisc ?? 100);
    const maxDiscAmt = maxDiscPct <= 0 ? 0 : Math.min(payable, payable * maxDiscPct / 100);
    if (maxDiscPct <= 0) {
        state.discountInput.value = '0.00';
    } else if (quickNumber(state.discountInput.value) > maxDiscAmt) {
        state.discountInput.value = maxDiscAmt.toFixed(2);
    } else if (quickNumber(state.discountInput.value) > payable) {
        state.discountInput.value = payable.toFixed(2);
    }

    if (quickOneTimePayLocked && quickHasOneTimePay() && !quickRedistributing) {
        // One-time pay is active — redistribute across checked rows
        redistributeQuickOneTimePay();
        return;
    }

    // Manual mode: only clamp collection if it now exceeds new payable, don't auto-fill
    const newPayable = Math.max(0, state.assigned + quickNumber(state.fineInput.value) - quickNumber(state.discountInput.value));
    if (quickNumber(state.amountInput.value) > newPayable) {
        state.amountInput.value = newPayable.toFixed(2);
    }

    updateQuickFeeNet(index);
}

function updateQuickGrandTotal() {
    let total = 0;
    let fineTotal = 0;
    let discountTotal = 0;
    let balanceTotal = 0;
    let netTotal = 0;
    document.querySelectorAll('.quick-fee-check').forEach((checkbox, index) => {
        const amount = quickNumber(document.getElementById(`quick_amt_${index}`)?.value);
        const fine = quickNumber(document.getElementById(`quick_fine_${index}`)?.value);
        const discount = quickNumber(document.getElementById(`quick_disc_${index}`)?.value);
        const assigned = quickNumber(document.getElementById(`quick_amt_${index}`)?.dataset.assigned);
        const checked = Boolean(checkbox.checked);
        total += checked ? amount : 0;
        fineTotal += fine;
        discountTotal += discount;
        netTotal += checked ? Math.max(0, amount + fine) : 0;
        balanceTotal += Math.max(0, assigned + fine - discount - (checked ? amount : 0));
    });
    const totalEl = document.getElementById('quickGrandTotal');
    if (totalEl) totalEl.textContent = `Rs ${total.toFixed(2)}`;
    const collectedEl = document.getElementById('quickSummaryCollected');
    if (collectedEl) collectedEl.textContent = `Rs ${total.toFixed(2)}`;
    const fineEl = document.getElementById('quickSummaryFine');
    if (fineEl) fineEl.textContent = `Rs ${fineTotal.toFixed(2)}`;
    const discountEl = document.getElementById('quickSummaryDiscount');
    if (discountEl) discountEl.textContent = `Rs ${discountTotal.toFixed(2)}`;
    const balanceEl = document.getElementById('quickSummaryBalance');
    if (balanceEl) balanceEl.textContent = `Rs ${balanceTotal.toFixed(2)}`;
    const netEl = document.getElementById('quickSummaryNet');
    if (netEl) netEl.textContent = `Rs ${balanceTotal.toFixed(2)}`;

    const submitBtn = document.getElementById('quickSubmitBtn');
    if (submitBtn) {
        submitBtn.disabled = total <= 0;
    }
}

function clearAllQuickFields() {
    const oneTimePay = document.getElementById('quickOneTimePay');
    if (oneTimePay) oneTimePay.value = '';
    quickOneTimePayLocked = false;

    quickRowIndexes().forEach(index => {
        const state = getQuickRowState(index);
        if (!state.checkbox) return;
        state.checkbox.checked = false;
        [state.amountInput, state.fineInput, state.discountInput].forEach(inp => {
            if (inp) { inp.disabled = true; inp.value = '0.00'; }
        });
        const netCell = document.getElementById(`quick_net_${index}`);
        if (netCell) netCell.textContent = state.assigned.toFixed(2);
    });
    updateQuickGrandTotal();
}

function applyQuickOneTimePay() {
    const input = document.getElementById('quickOneTimePay');
    if (!input) return;

    let remaining = quickNumber(input.value);
    if (remaining <= 0) return;

    quickRedistributing = true;
    quickOneTimePayLocked = true;

    quickRowIndexes().forEach(index => {
        const state = getQuickRowState(index);
        if (!state.checkbox || !state.amountInput) return;
        state.checkbox.checked = false;
        state.amountInput.disabled = true;
        state.fineInput.disabled = true;
        state.discountInput.disabled = true;
        state.amountInput.value = '0.00';
        const netCell = document.getElementById(`quick_net_${index}`);
        if (netCell) {
            netCell.textContent = Math.max(
                0,
                state.assigned + quickNumber(state.fineInput?.value) - quickNumber(state.discountInput?.value)
            ).toFixed(2);
        }
    });

    quickRowIndexes().forEach(index => {
        if (remaining <= 0) return;

        const state = getQuickRowState(index);
        if (!state.checkbox || !state.amountInput) return;

        const rowCap = Math.max(0, state.assigned + quickNumber(state.fineInput?.value) - quickNumber(state.discountInput?.value));
        if (rowCap <= 0) return;

        const collect = Math.min(remaining, rowCap);
        state.checkbox.checked = true;
        state.amountInput.disabled = false;
        state.fineInput.disabled = false;
        state.discountInput.disabled = false;
        state.amountInput.value = collect.toFixed(2);
        const netCell = document.getElementById(`quick_net_${index}`);
        if (netCell) {
            netCell.textContent = Math.max(
                0,
                state.assigned + quickNumber(state.fineInput?.value) - quickNumber(state.discountInput?.value) - collect
            ).toFixed(2);
        }
        remaining -= collect;
    });

    quickRedistributing = false;
    updateQuickGrandTotal();
}

function redistributeQuickOneTimePay() {
    const input = document.getElementById('quickOneTimePay');
    if (!quickOneTimePayLocked || !input || quickNumber(input.value) <= 0) return;
    applyQuickOneTimePay();
}

function updateQuickFine(index) {
    updateQuickFeeNet(index);
    updateQuickGrandTotal();
}

function toggleQuickBankAccount() {
    const mode = document.getElementById('quickPaymentMode')?.value || 'cash';
    const isCash = mode === 'cash';

    // Bank account show/hide
    const wrap = document.getElementById('quickBankWrap');
    const select = document.getElementById('quickBankAccount');
    if (wrap && select) {
        wrap.style.display = isCash ? 'none' : 'block';
        select.required = !isCash;
        Array.from(select.options).forEach((option, index) => {
            if (index === 0) return;
            const modes = String(option.dataset.modes || '').split(',').map(v => v.trim());
            option.hidden = !isCash && modes.length && !modes.includes(mode);
        });
    }

    // Transaction Ref — mandatory when not cash
    const txnRef = document.getElementById('quickTransactionRef');
    const txnLabel = document.getElementById('quickTransactionRefLabel');
    if (txnRef) {
        txnRef.required = !isCash;
        if (txnLabel) {
            txnLabel.innerHTML = isCash
                ? 'Transaction Ref'
                : 'Transaction Ref <span class="text-danger">*</span>';
        }
    }

    // Payment Date & Time — show only when not cash
    const timeWrap = document.getElementById('quickPaymentTimeWrap');
    const timeInput = document.getElementById('quickPaymentDatetime');
    if (timeWrap && timeInput) {
        timeWrap.style.display = isCash ? 'none' : '';
        timeInput.required = !isCash;
        // Pre-fill with current LOCAL datetime if empty; always cap max to current time
        if (!isCash) {
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            const today = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}`;
            const curr  = `${pad(now.getHours())}:${pad(now.getMinutes())}`;
            if (!timeInput.value) {
                timeInput.value = `${today}T${curr}`;
            }
            // Admin: any past date allowed; staff/center/partner: today's date only
            if ({{ $lockPaymentDate ? 'true' : 'false' }}) {
                timeInput.min = `${today}T00:00`;
            } else {
                timeInput.removeAttribute('min');
            }
            timeInput.max = `${today}T${curr}`;
        }
    }
}

function buildQuickPermanentAddress() {
    const parts = [
        document.getElementById('quickPermVillage')?.value.trim(),
        document.getElementById('quickPermPost')?.value.trim(),
        document.getElementById('quickPermThana')?.value.trim(),
        document.getElementById('quickPermDistrict')?.value.trim(),
        document.getElementById('quickPermState')?.value.trim(),
        document.getElementById('quickPermPincode')?.value.trim(),
    ].filter(Boolean);

    return parts.join(', ');
}

function syncQuickCommunicationAddress() {
    const sameAsCheckbox = document.getElementById('quickCommSameAsPerm');
    const commAddress = document.getElementById('quickCommAddress');

    if (!sameAsCheckbox || !commAddress || !sameAsCheckbox.checked) {
        return;
    }

    commAddress.value = buildQuickPermanentAddress();
}

function bindQuickAddressSync() {
    const sameAsCheckbox = document.getElementById('quickCommSameAsPerm');
    const commAddress = document.getElementById('quickCommAddress');

    if (!sameAsCheckbox || !commAddress) {
        return;
    }

    ['quickPermVillage', 'quickPermPost', 'quickPermThana', 'quickPermDistrict', 'quickPermState', 'quickPermPincode']
        .forEach((id) => {
            const input = document.getElementById(id);
            if (!input) return;
            input.addEventListener('input', syncQuickCommunicationAddress);
        });

    sameAsCheckbox.addEventListener('change', function () {
        if (sameAsCheckbox.checked) {
            syncQuickCommunicationAddress();
            return;
        }

        commAddress.focus();
    });

    if (sameAsCheckbox.checked && !commAddress.value.trim()) {
        syncQuickCommunicationAddress();
    }
}

function bindQuickFeeWatchers() {
    ['sourceSelect', 'quickPaymentMode'].forEach(id => {
        const el = document.getElementById(id);
        if (!el || el.dataset.boundFeeWatcher === '1') return;
        el.addEventListener('change', () => {
            if (id === 'sourceSelect') refreshQuickFeePreview();
            if (id === 'quickPaymentMode') toggleQuickBankAccount();
        });
        el.dataset.boundFeeWatcher = '1';
    });

    ['student_type', 'category', 'gender'].forEach(name => {
        const el = document.querySelector(`[name="${name}"]`);
        if (!el || el.dataset.boundFeeWatcher === '1') return;
        el.addEventListener('change', refreshQuickFeePreview);
        el.dataset.boundFeeWatcher = '1';
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const quickForm = document.getElementById('quickForm');
    if (quickForm) {
        let isSubmitting = false;
        window.admissionLiveValidation?.initForm(quickForm);

        quickForm.addEventListener('submit', async function(event) {
            if (isSubmitting) {
                return;
            }

            event.preventDefault();
            if (!window.admissionLiveValidation?.validateForm(quickForm, { report: true })) {
                return;
            }
            const canContinue = await prepareQuickPhotoForSubmit(quickForm);
            if (!canContinue) {
                return;
            }

            isSubmitting = true;
            quickForm.submit();
        });
    }

    bindQuickFeeWatchers();
    toggleQuickBankAccount();
    bindQuickAddressSync();
    const submitBtn = document.getElementById('quickSubmitBtn');
    if (submitBtn) {
        submitBtn.innerHTML = '<i class="bi bi-check2-circle me-2"></i>Save Admission & Print Receipt';
    }

    const oneTimeInput = document.getElementById('quickOneTimePay');
    if (oneTimeInput) {
        oneTimeInput.addEventListener('input', function () {
            if (quickNumber(this.value) < 0) {
                this.value = '0';
            }
            quickOneTimePayLocked = false;
        });
    }

    setTimeout(refreshQuickFeePreview, 900);

    // Initialize education rows based on pre-selected course type
    const initTypeId = document.getElementById('courseTypeSelect')?.value;
    if (initTypeId) updateEduRows(initTypeId);

    // Auto-uppercase all text inputs (except email fields)
    document.getElementById('quickForm')?.addEventListener('input', function(e) {
        const el = e.target;
        if (el.type !== 'text' || el.readOnly) return;
        const pos = el.selectionStart;
        el.value = el.value.toUpperCase();
        try { el.setSelectionRange(pos, pos); } catch(_) {}
    }, true);

    // Initialize draft persistence
    initQuickFormDraft();
});

// ── Form draft — save to sessionStorage on change, restore on refresh ─────
(function () {
    const DRAFT_KEY = 'adm_quick_{{ auth()->id() ?? 0 }}';
    const HAS_PHP_DATA = @json(!empty(old()) || !empty($qd ?? []));
    const CASCADE = new Set(['course_type_id','course_id','course_stream_id','course_part_id']);
    let draftTimer = null;

    function saveDraft() {
        const form = document.getElementById('quickForm');
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

        const form = document.getElementById('quickForm');
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

        // Re-trigger scholarship visibility
        const schEl = form.querySelector('[name="has_scholarship"]');
        if (schEl && schEl.checked && typeof toggleScholarship === 'function') toggleScholarship(true);

        // Cascade: course type → course → stream → part
        const typeId   = data['course_type_id'];
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
                    if (el) { el.value = streamId; el.dispatchEvent(new Event('change')); }
                }
                setTimeout(() => {
                    if (partId) {
                        const el = form.querySelector('[name="course_part_id"]');
                        if (el) el.value = partId;
                    }
                }, 400);
            }, 300);
        }
    }

    window.initQuickFormDraft = function () {
        restoreDraft();
        const form = document.getElementById('quickForm');
        if (!form) return;
        form.addEventListener('input',  () => { clearTimeout(draftTimer); draftTimer = setTimeout(saveDraft, 600); }, true);
        form.addEventListener('change', () => { clearTimeout(draftTimer); draftTimer = setTimeout(saveDraft, 600); }, true);
        form.addEventListener('submit', () => { clearTimeout(draftTimer); clearDraft(); }, { once: true });
    };
})();
</script>
@endpush
