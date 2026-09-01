@php
    $isStaff = auth()->guard('staff')->check();
    $layout = $isStaff ? 'staff.layout' : 'institute.layout';
    $admissionIndexRoute = $isStaff ? 'staff.admissions.index' : 'admissions.index';
    $admissionEditRoute = $isStaff ? 'staff.admissions.edit' : 'admissions.edit';
    $printRoute = $isStaff ? 'staff.admissions.print-all' : 'admissions.print-all';
    $feeCreateRoute = $isStaff ? 'staff.fee.create' : 'fee.create';
    $walletRoute = $isStaff ? 'staff.fee.wallet.student' : 'fee.wallet.student';
    $canViewFeeDetails = !$isStaff || auth()->guard('staff')->user()?->canViewFeeHistory();
    $canViewFeeWallet = !$isStaff || auth()->guard('staff')->user()?->canViewFeeWallet();
    $canEditStudent = !$isStaff
        || auth()->guard('staff')->user()?->hasPermission('student_edit')
        || auth()->guard('staff')->user()?->hasPermission('admission_edit');
    $isTerminalStudent = in_array($student->status, ['passed_out', 'backlog', 'failed', 'dropped']);
    $isPending = $student->status === 'pending';
    $approvalRoute = $isStaff ? 'staff.admissions.approvals.show' : 'admissions.approvals.show';
    // Only active students can have new fee collected — matches the server-side
    // enforcement in FeeCollectionController::store(). Every other status is
    // blocked; outstanding dues can still be cleared via the wallet.
    $canCollectFee = $student->status === 'active' && (!$isStaff || auth()->guard('staff')->user()?->canCollectFee());

    // Document upload button
    $canViewDocuments = !$isStaff || auth()->guard('staff')->user()?->hasPermission('document_upload')
        || auth()->guard('staff')->user()?->hasPermission('document_view')
        || auth()->guard('staff')->user()?->hasPermission('document_verify');
    $docUploadRoute = $isStaff ? 'staff.admissions.upload-documents' : 'admissions.upload-documents';
    $uploadedDocIds = \App\Models\AdmissionDocument::where('student_id', $student->id)
        ->pluck('document_type_id')->toArray();
    $courseId = $student->stream?->course_id;
    $pendingRequiredDocs = $courseId
        ? \App\Models\DocumentUploadRule::where('course_id', $courseId)
            ->where('requirement', 'required')
            ->whereNotIn('document_type_id', $uploadedDocIds ?: [-1])
            ->count()
        : 0;
@endphp
@extends($layout)
@section('title', 'Student Profile')
@section('breadcrumb', 'Admissions / Student Profile')

@section('content')

@php
    $sid        = $student->id;
    $profileRoute = $isStaff ? 'staff.admissions.show' : 'admissions.show';

    // Selected session identity se data lo, fallback student pe
    $courseName = $selectedIdentity?->courseStream?->course?->name
                ?? $student->stream?->course?->name ?? '—';
    $streamName = $selectedIdentity?->courseStream?->name
                ?? $student->stream?->name ?? '—';
    $partName   = $selectedIdentity?->coursePart?->year_label
                ?? $student->coursePart?->year_label ?? '—';
    $sessName   = $selectedIdentity?->session?->name
                ?? $student->session?->name ?? '—';
    $semNum     = ($selectedIdentity?->semester_at_time ?: null)
                ?? ($student->current_semester ?: null)
                ?? '—';

    // Office details: current session → live data, so ongoing edits (e.g. via the
    // Roll No / Form No Assignment page) show up here immediately; historical session
    // → frozen snapshot only, never fall back to live data (null snapshot = it was
    // blank at that time). Snapshot is virtually always non-null once captured, so
    // checking $isCurrentSession FIRST (not snapshot-first) is what actually matters.
    $srNo         = $isCurrentSession ? ($student->sr_no ?? '—') : ($selectedIdentity?->sr_no_snapshot ?? '—');
    $enrollmentNo = $isCurrentSession ? ($student->enrollment_no ?? '—') : ($selectedIdentity?->enrollment_no_snapshot ?? '—');
    $rollNo       = $isCurrentSession
        ? ($selectedIdentity?->roll_no ?? $student->roll_no ?? '—')
        : ($selectedIdentity?->roll_no_snapshot ?? $selectedIdentity?->roll_no ?? '—');
    $formNo       = $isCurrentSession ? ($student->institute_form_no ?? '—') : ($selectedIdentity?->institute_form_no_snapshot ?? '—');
    $examFormNo   = $isCurrentSession ? ($student->exam_form_no ?? '—') : ($selectedIdentity?->exam_form_no_snapshot ?? '—');
    $uinNo        = $isCurrentSession ? ($student->uin_no ?? '—') : ($selectedIdentity?->uin_no_snapshot ?? '—');
    $referenceNo  = $isCurrentSession ? ($student->reference_no ?? '—') : ($selectedIdentity?->reference_no_snapshot ?? '—');
    $submittedDate = $isCurrentSession
        ? ($student->submitted_date?->format('d-m-Y') ?? '—')
        : ($selectedIdentity?->submitted_date_snapshot?->format('d-m-Y') ?? '—');
    $admissionDate = $isCurrentSession
        ? ($student->admission_date?->format('d-m-Y') ?? '—')
        : ($selectedIdentity?->admission_date_snapshot?->format('d-m-Y') ?? '—');
    $studentUidParts = explode('/', (string) $student->student_uid);
    $serialNo = end($studentUidParts) ?: '—';

    // Profile snapshot shorthand — null means show live student data
    $ps = $profileSnapshot ?? null;
    // Helper: jab $ps set ho (snapshot available), use snapshot exclusively;
    // null field in snapshot = us waqt blank tha, show '—'.
    // jab $ps null ho (current session ya old record), live data use karo.
    $snapVal = fn($key, $live) => $ps !== null ? ($ps[$key] ?? '—') : ($live ?? '—');
@endphp

{{-- Pending Admission Banner --}}
@if($isPending)
<div class="alert mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3"
     style="background:#fff3cd;border:1.5px solid #fd7e14;border-left:5px solid #fd7e14;">
    <div class="d-flex align-items-center gap-3">
        <i class="bi bi-hourglass-split fs-4" style="color:#fd7e14;"></i>
        <div>
            <div class="fw-bold" style="color:#fd7e14;">Admission Pending Approval</div>
            <div class="text-muted small">This student's admission has not been approved yet. Fee collection, transport, library, and certificate modules are restricted until approval.</div>
        </div>
    </div>
    <a href="{{ route($approvalRoute, $sid) }}" class="btn btn-warning btn-sm text-white flex-shrink-0">
        <i class="bi bi-shield-check me-1"></i> Review &amp; Approve
    </a>
</div>
@endif

{{-- Session Tabs --}}
@if($sessionIdentities->count() > 1)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="small text-muted fw-semibold me-1">Session:</span>
            @foreach($sessionIdentities as $identity)
            @php
                $isActive = (int) $identity->id === (int) ($selectedIdentity?->id ?? 0);
            @endphp
            <a href="{{ route($profileRoute, ['student' => $student->id, 'session_id' => $identity->academic_session_id, 'identity_id' => $identity->id]) }}"
               class="btn btn-sm {{ $isActive ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $identity->session?->name ?? '—' }}
                @if($identity->semester_at_time)
                    <span class="ms-1 opacity-75" style="font-size:10px;">Sem {{ $identity->semester_at_time }}</span>
                @endif
            </a>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Historical snapshot notice --}}
@if($profileSnapshot)
<div class="alert mb-3 d-flex align-items-center gap-2 py-2"
     style="background:#fff8e1;border:1.5px solid #f59e0b;border-left:5px solid #f59e0b;font-size:13px;">
    <i class="bi bi-clock-history text-warning fs-5"></i>
    <div>
        <strong>Historical View</strong> — Yeh data <strong>{{ $sessName }}{{ $semNum !== '—' ? ' · Sem '.$semNum : '' }}</strong>
        ke samay ka snapshot hai. Koi bhi changes current profile pe nahi dikhenge.
    </div>
</div>
@elseif(!($isCurrentSession ?? true))
<div class="alert mb-3 d-flex align-items-center gap-2 py-2"
     style="background:#f8f9fa;border:1.5px solid #adb5bd;border-left:5px solid #adb5bd;font-size:13px;">
    <i class="bi bi-info-circle text-secondary fs-5"></i>
    <div>
        <strong>Historical View</strong> — Profile snapshot is not available for this period (older record). Showing current profile data.
    </div>
</div>
@endif

{{-- Top Buttons --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-person-badge me-2 text-primary"></i>Student Profile</h4>
        <small class="text-muted">{{ $sessName }}</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route($admissionIndexRoute) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <a href="{{ route($printRoute, $sid) }}" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-printer me-1"></i> Print Form
        </a>
        @if($isPending)
        <a href="{{ route($approvalRoute, $sid) }}" class="btn btn-warning btn-sm text-white">
            <i class="bi bi-shield-check me-1"></i> Approve Admission
        </a>
        @else
        @if($canCollectFee)
        <a href="{{ route($feeCreateRoute, ['student_id' => $sid]) }}" class="btn btn-success btn-sm">
            <i class="bi bi-cash me-1"></i> Collect Fee
        </a>
        @endif
        @if($canViewFeeWallet)
        <a href="{{ route($walletRoute, $sid) }}" class="btn btn-outline-info btn-sm">
            <i class="bi bi-wallet me-1"></i> Wallet
        </a>
        @endif
        @endif
        @if($canViewDocuments)
        <a href="{{ route($docUploadRoute, $student) }}" class="btn btn-outline-secondary btn-sm position-relative">
            <i class="bi bi-paperclip me-1"></i> Documents
            @if($pendingRequiredDocs > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:9px;">
                {{ $pendingRequiredDocs }}
            </span>
            @endif
        </a>
        @endif
        @if($canEditStudent)
        <a href="{{ route($admissionEditRoute, $student ) }}" class="btn btn-warning btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <button type="button" class="btn btn-outline-dark btn-sm" onclick="document.getElementById('statusChangeModal').style.display='flex'">
            <i class="bi bi-person-gear me-1"></i> Change Status
        </button>
        @endif
        @if(!$isStaff)
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('resendCredsModal').style.display='flex'">
            <i class="bi bi-key me-1"></i> Reset Portal Login
        </button>
        @php
            $loginState = $student->login_blocked
                ? 'blocked'
                : (($student->suspended_until && now()->toDateString() <= $student->suspended_until->toDateString()) ? 'suspended' : 'allowed');
        @endphp
        <button type="button" class="btn btn-sm {{ $loginState === 'allowed' ? 'btn-outline-success' : 'btn-danger' }}" onclick="document.getElementById('loginAccessModal').style.display='flex'">
            <i class="bi bi-{{ $loginState === 'allowed' ? 'unlock-fill' : 'lock-fill' }} me-1"></i>
            {{ $loginState === 'allowed' ? 'Login: Allowed' : ($loginState === 'blocked' ? 'Login: Blocked' : 'Login: Suspended') }}
        </button>
        @endif
    </div>
</div>

{{-- Credentials Reset Flash --}}
@if(session('credentials_reset'))
@php $cr = session('credentials_reset'); @endphp
<div class="alert border-0 mb-4" style="background:#f0fdf4;border-left:4px solid #16a34a !important;border-left-style:solid !important;">
    <div class="fw-bold text-success mb-1"><i class="bi bi-check-circle-fill me-2"></i>Password Reset — {{ $cr['name'] }} ({{ $cr['uid'] }})</div>
    <div class="mb-2">{{ $cr['delivery'] }}</div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <span style="font-size:13px;color:#64748b;">New Temporary Password:</span>
        <code class="px-3 py-1 rounded" style="background:#e0f2fe;color:#0369a1;font-size:15px;font-weight:700;letter-spacing:2px;">{{ $cr['password'] }}</code>
        <span class="text-muted" style="font-size:12px;">(Note this down to share with student if needed)</span>
    </div>
</div>
@endif

{{-- Resend Credentials Modal --}}
<div id="resendCredsModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px 32px;max-width:420px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.18);">
        <h6 class="fw-bold mb-1"><i class="bi bi-key-fill text-warning me-2"></i>Reset Student Portal Login</h6>
        <p class="text-muted mb-3" style="font-size:13px;">
            A new temporary password will be generated and sent to the student.
            @if($student->email) <br><strong>Email:</strong> {{ $student->email }} @endif
            @if($student->mobile) <br><strong>Mobile:</strong> {{ $student->mobile }} @endif
            @if(!$student->email && !$student->mobile && !$student->father_mobile)
            <br><span class="text-danger">⚠️ No email or mobile found — password will be shown on screen only.</span>
            @endif
        </p>
        <form method="POST" action="{{ route('admissions.resend-credentials', $student) }}">
            @csrf
            <div class="d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('resendCredsModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-warning btn-sm text-white"><i class="bi bi-arrow-clockwise me-1"></i>Reset & Send</button>
            </div>
        </form>
    </div>
</div>

{{-- Login Access Modal --}}
@if(!$isStaff)
<div id="loginAccessModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px 32px;max-width:420px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.18);">
        <h6 class="fw-bold mb-1"><i class="bi bi-shield-lock text-primary me-2"></i>Manage Login Access</h6>
        <p class="text-muted mb-3" style="font-size:13px;">
            Blocking login does not affect this student's admission record, fee history, or their name appearing as an admission source elsewhere — only their ability to log in to the student portal.
        </p>
        <form method="POST" action="{{ route('admissions.login-access', $student) }}">
            @csrf
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="mode" id="studentModeAllowed" value="allowed" {{ $loginState === 'allowed' ? 'checked' : '' }}>
                <label class="form-check-label" for="studentModeAllowed">Allowed — normal login</label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="mode" id="studentModeBlocked" value="blocked" {{ $loginState === 'blocked' ? 'checked' : '' }}>
                <label class="form-check-label" for="studentModeBlocked">Blocked — until manually unblocked</label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="mode" id="studentModeSuspended" value="suspended" {{ $loginState === 'suspended' ? 'checked' : '' }}>
                <label class="form-check-label" for="studentModeSuspended">Suspended until a date — auto-restores after</label>
            </div>
            <input type="date" class="form-control mt-2" name="suspended_until" id="studentSuspendedUntilInput"
                   value="{{ $student->suspended_until?->format('Y-m-d') }}"
                   style="display:{{ $loginState === 'suspended' ? 'block' : 'none' }};">
            <div class="d-flex gap-2 justify-content-end mt-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('loginAccessModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Save</button>
            </div>
        </form>
    </div>
</div>
<script>
document.querySelectorAll('#loginAccessModal input[name="mode"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
        document.getElementById('studentSuspendedUntilInput').style.display = this.value === 'suspended' ? 'block' : 'none';
    });
});
</script>
@endif

{{-- Change Status Modal --}}
@if($canEditStudent)
@php $statusChangeRoute = $isStaff ? 'staff.admissions.status.update' : 'admissions.status.update'; @endphp
<div id="statusChangeModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px 32px;max-width:440px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.18);">
        <h6 class="fw-bold mb-1"><i class="bi bi-person-gear text-primary me-2"></i>Change Student Status</h6>
        <p class="text-muted mb-3" style="font-size:13px;">
            Current status: <strong>{{ ucwords(str_replace('_', ' ', $student->status ?? 'active')) }}</strong>.
            Dropped/Transferred students are treated as terminal — fee collection and other modules get restricted for them.
        </p>
        <form method="POST" action="{{ route($statusChangeRoute, $student) }}">
            @csrf
            <label class="form-label small fw-semibold">New Status</label>
            <select name="status" id="statusSelect" class="form-select form-select-sm mb-2"
                    onchange="document.getElementById('statusReasonBox').style.display = this.value === 'active' ? 'none' : 'block';">
                @foreach(['active' => 'Active', 'dropped' => 'Dropped', 'detained' => 'Detained', 'transferred' => 'Transferred', 'inactive' => 'Inactive'] as $val => $label)
                <option value="{{ $val }}" {{ ($student->status ?? 'active') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <div id="statusReasonBox" style="display:{{ ($student->status ?? 'active') === 'active' ? 'none' : 'block' }};">
                <label class="form-label small fw-semibold">Reason <span class="text-danger">*</span></label>
                <textarea name="reason" class="form-control form-control-sm" rows="2" placeholder="Why is the status changing?">{{ $student->status_reason }}</textarea>
            </div>
            <div class="d-flex gap-2 justify-content-end mt-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('statusChangeModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Save</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Header Card --}}
<div class="card border-0 shadow-sm mb-4" style="background:linear-gradient(135deg,#1e293b,#0f4c81);color:white;">
    <div class="card-body p-4">
        <div class="d-flex gap-4 align-items-center">
            <div class="flex-shrink-0">
                @if($student->photo)
                    <img src="{{ asset('storage/'.$student->photo) }}"
                         style="width:90px;height:100px;object-fit:cover;border-radius:8px;border:3px solid rgba(255,255,255,0.3);">
                @else
                    <div style="width:90px;height:100px;border-radius:8px;background:rgba(255,255,255,0.15);
                                display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-person" style="font-size:2.5rem;opacity:.6;"></i>
                    </div>
                @endif
            </div>
            <div class="flex-fill">
                <div class="d-flex align-items-center gap-3 mb-2 flex-wrap">
                    <h4 class="fw-bold mb-0 text-white">{{ $student->name }}</h4>
                    <span class="badge bg-primary px-3" style="font-size:12px;">{{ $student->student_uid }}</span>
                    @php
                        $statusBadge = match($student->status ?? 'active') {
                            'active'     => 'bg-success',
                            'pending'    => 'bg-warning text-dark',
                            'passed_out' => 'bg-info text-dark',
                            'backlog'    => 'bg-warning text-dark',
                            'failed'     => 'bg-danger',
                            'dropped'    => 'bg-secondary',
                            'detained'   => 'bg-danger',
                            'transferred'=> 'bg-secondary',
                            'cancelled'  => 'bg-secondary',
                            default      => 'bg-secondary',
                        };
                    @endphp
                    <span class="badge {{ $statusBadge }}">
                        {{ ucwords(str_replace('_', ' ', $student->status ?? 'active')) }}
                    </span>
                </div>
                <div class="row g-2" style="font-size:13px;">
                    <div class="col-6 col-md-3"><div class="opacity-75 small">Father Name</div><div class="fw-semibold">{{ $snapVal('father_name', $student->father_name) }}</div></div>
                    <div class="col-6 col-md-3"><div class="opacity-75 small">Mother Name</div><div class="fw-semibold">{{ $snapVal('mother_name', $student->mother_name) }}</div></div>
                    <div class="col-6 col-md-3"><div class="opacity-75 small">Course</div><div class="fw-semibold">{{ $courseName }}</div></div>
                    <div class="col-6 col-md-3"><div class="opacity-75 small">Stream</div><div class="fw-semibold">{{ $streamName }}</div></div>
                    <div class="col-6 col-md-3"><div class="opacity-75 small">Semester</div><div class="fw-semibold">{{ $partName }}@if($semNum !== '—') <span class="badge bg-primary text-white ms-1" style="font-size:10px;">Sem {{ $semNum }}</span>@endif</div></div>
                    <div class="col-6 col-md-3"><div class="opacity-75 small">Session</div><div class="fw-semibold">{{ $sessName }}</div></div>
                    <div class="col-6 col-md-3"><div class="opacity-75 small">Mobile</div><div class="fw-semibold">{{ $student->mobile ?? '—' }}</div></div>
                    <div class="col-6 col-md-3"><div class="opacity-75 small">Admission Date</div><div class="fw-semibold">{{ $student->admission_date?->format('d M Y') ?? '—' }}</div></div>
                    <div class="col-6 col-md-3"><div class="opacity-75 small">Gender</div><div class="fw-semibold">{{ ucfirst($student->gender ?? '—') }}</div></div>
                    <div class="col-6 col-md-3"><div class="opacity-75 small">DOB</div><div class="fw-semibold">{{ $student->dob?->format('d M Y') ?? '—' }}</div></div>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $transport = $student->activeTransportAllocation;
    $allTransport = $student->transportAllocations->sortByDesc('id');
    $hasFeeRecords = $canViewFeeDetails && (($feeSummary['total_charged'] ?? 0) > 0 || ($feeSummary['total_paid'] ?? 0) > 0);
    $feeIsClear = $feeSummary['is_clear'] ?? true;
    $feeDue = $feeSummary['total_due'] ?? 0;
    $hasScholarship = $ps ? !empty($ps['has_scholarship']) : $student->has_scholarship;
    $eduRows = $ps ? ($ps['education'] ?? []) : ($student->educationDetails?->map(fn($e) => (array) $e->toArray())->all() ?? []);
    $hasHistory = ($academicChangeLogs ?? collect())->isNotEmpty();
    $docCanVerify = !$isStaff || auth()->guard('staff')->user()?->hasPermission('document_verify');
    $docCanUpload = !$isStaff || auth()->guard('staff')->user()?->hasPermission('document_upload');
    $docCanDelete = !$isStaff || auth()->guard('staff')->user()?->hasPermission('document_delete');
@endphp

{{-- Fee Due Alert --}}
@if($isTerminalStudent)
<div class="alert border-0 shadow-sm mb-3 {{ !$feeIsClear ? 'alert-danger' : 'alert-success' }}">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <i class="bi bi-{{ !$feeIsClear ? 'exclamation-triangle-fill' : 'check-circle-fill' }} me-2"></i>
            <strong>{{ ucwords(str_replace('_', ' ', $student->status)) }}</strong> —
            @if(!$feeIsClear)
                This student has an outstanding due of <strong>₹{{ number_format($feeDue, 2) }}</strong>. Fee collection is blocked until the due is cleared.
            @else
                This student has no outstanding dues.
            @endif
        </div>
        @if(!$feeIsClear)
        <a href="{{ route($walletRoute, ['student' => $student->id, 'session_id' => $selectedSessionId]) }}"
           class="btn btn-sm btn-outline-danger fw-semibold">
            <i class="bi bi-wallet me-1"></i> View Due Details
        </a>
        @endif
    </div>
</div>
@endif

{{-- 1. Office Details --}}
@php
    $admissionSource = $selectedIdentity?->admission_source_snapshot ?? $student->admission_source ?? 'direct';
    $admissionSourceId = $selectedIdentity?->admission_source_id_snapshot ?? $student->admission_source_id;
    $admSrcDisplay = ucwords(str_replace('_', ' ', $admissionSource));
    if ($admissionSource === 'center' && $admissionSourceId) {
        $n = \App\Models\Center::find($admissionSourceId)?->name;
        if ($n) $admSrcDisplay .= ' — ' . $n;
    } elseif ($admissionSource === 'channel_partner' && $admissionSourceId) {
        $n = \App\Models\ChannelPartner::find($admissionSourceId)?->name;
        if ($n) $admSrcDisplay .= ' — ' . $n;
    }
    $officeFields = [
        'Serial No.'       => $serialNo,
        'Form No.'         => $formNo,
        'SR No.'           => $srNo,
        'Enrollment No.'   => $enrollmentNo,
        'Roll No.'         => $rollNo,
        'Exam Form No.'    => $examFormNo,
        'UIN No.'          => $uinNo,
        'Reference No.'    => $referenceNo,
        'Submitted Date'   => $submittedDate,
        'Admission Type'   => ucfirst($selectedIdentity?->admission_type ?? $student->admission_type ?? 'new'),
        'Admission Source' => $admSrcDisplay,
        'Gap Year'         => $student->gap_year ? 'Yes' : 'No',
        'Admission Date'   => $admissionDate,
        'Academic Session' => $sessName,
    ];
@endphp
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b;color:white;">
        <span class="fw-bold small"><i class="bi bi-briefcase me-2"></i>Office Details</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light" style="font-size:12px;">
                <tr>
                    @foreach(array_keys($officeFields) as $lbl)
                    <th class="text-nowrap">{{ $lbl }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody style="font-size:12px;">
                <tr>
                    @foreach($officeFields as $val)
                    <td class="fw-semibold text-nowrap">{{ $val }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- 2. Personal Details (name → father → mother upfront) --}}
@php
    $personalFields = [
        'Student Name'      => $student->name,
        'Father Name'       => $snapVal('father_name', $student->father_name),
        'Mother Name'       => $snapVal('mother_name', $student->mother_name),
        'Father Mobile'     => $snapVal('father_mobile', $student->father_mobile),
        'Mother Mobile'     => $snapVal('mother_mobile', $student->mother_mobile),
        'Category'          => strtoupper($snapVal('category', $student->category)),
        'Special Category'  => strtoupper($snapVal('special_category', $student->special_category)),
        'Nationality'       => ucfirst($snapVal('nationality', $student->nationality)),
        'Religion'          => ucfirst($snapVal('religion', $student->religion)),
        'Student Type'      => ucfirst($snapVal('student_type', $student->student_type)),
        'Marital Status'    => ucfirst($snapVal('marital_status', $student->marital_status)),
        'Aadhar No.'        => $snapVal('aadhar_no', $student->aadhar_no),
        'APAAR No.'         => $snapVal('apaar_no', $student->apaar_no),
        'Email'             => $snapVal('email', $student->email),
        'Father Occupation' => $snapVal('father_occupation', $student->father_occupation),
        'Guardian Name'     => $snapVal('guardian_name', $student->guardian_name),
        'Guardian Mobile'   => $snapVal('guardian_mobile', $student->guardian_mobile),
    ];
@endphp
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b;color:white;">
        <span class="fw-bold small"><i class="bi bi-person me-2"></i>Personal Details</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light" style="font-size:12px;">
                <tr>
                    @foreach(array_keys($personalFields) as $lbl)
                    <th class="text-nowrap">{{ $lbl }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody style="font-size:12px;">
                <tr>
                    @foreach($personalFields as $val)
                    <td class="fw-semibold text-nowrap">{{ $val }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- 3. Address (Permanent + Present rows) --}}
@php
    $commSameAsPerm = (bool)($ps !== null ? ($ps['comm_same_as_perm'] ?? false) : $student->comm_same_as_perm);
@endphp
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b;color:white;">
        <span class="fw-bold small"><i class="bi bi-geo-alt me-2"></i>Address Details</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light" style="font-size:12px;">
                <tr>
                    <th class="text-nowrap">Type</th>
                    <th class="text-nowrap">Village/City</th>
                    <th class="text-nowrap">Post</th>
                    <th class="text-nowrap">Thana</th>
                    <th class="text-nowrap">District</th>
                    <th class="text-nowrap">State</th>
                    <th class="text-nowrap">Pin Code</th>
                </tr>
            </thead>
            <tbody style="font-size:12px;">
                <tr>
                    <td class="fw-bold text-nowrap">Permanent</td>
                    <td class="text-nowrap">{{ $snapVal('perm_village', $student->perm_village) }}</td>
                    <td class="text-nowrap">{{ $snapVal('perm_post', $student->perm_post) }}</td>
                    <td class="text-nowrap">{{ $snapVal('perm_thana', $student->perm_thana) }}</td>
                    <td class="text-nowrap">{{ $snapVal('perm_district', $student->perm_district) }}</td>
                    <td class="text-nowrap">{{ $snapVal('perm_state', $student->perm_state) }}</td>
                    <td class="text-nowrap">{{ $snapVal('perm_pincode', $student->perm_pincode) }}</td>
                </tr>
                @if($commSameAsPerm)
                <tr>
                    <td class="fw-bold text-nowrap">Present</td>
                    <td colspan="6">Same as Permanent Address</td>
                </tr>
                @else
                <tr>
                    <td class="fw-bold text-nowrap">Present</td>
                    <td class="text-nowrap">{{ $snapVal('comm_city', $student->comm_city) }}</td>
                    <td class="text-nowrap">{{ $snapVal('comm_post', $student->comm_post) }}</td>
                    <td class="text-nowrap">{{ $snapVal('comm_thana', $student->comm_thana) }}</td>
                    <td class="text-nowrap">{{ $snapVal('comm_district', $student->comm_district) }}</td>
                    <td class="text-nowrap">{{ $snapVal('comm_state', $student->comm_state) }}</td>
                    <td class="text-nowrap">{{ $snapVal('comm_pincode', $student->comm_pincode) }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- 4. Academic Details --}}
@if(($selectedSubjects ?? collect())->count())
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b;color:white;">
        <span class="fw-bold small"><i class="bi bi-list-check me-2"></i>Subjects</span>
    </div>
    <div class="card-body">
        @php
            $byRole = collect($selectedSubjects)->groupBy('subject_role');
        @endphp
        @foreach(['compulsory'=>'success','major'=>'primary','minor'=>'info','optional'=>'secondary','recorded'=>'dark'] as $role => $color)
        @if($byRole->has($role))
        <div class="mb-2">
            <span class="small text-muted fw-semibold">{{ ucfirst(str_replace('_', ' ', $role)) }}:</span>
            @foreach($byRole[$role] as $ss)
            <span class="badge bg-{{ $color }} ms-1">{{ $ss->name ?? '—' }}</span>
            @endforeach
        </div>
        @endif
        @endforeach
    </div>
</div>
@endif

@if(count($eduRows))
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b;color:white;">
        <span class="fw-bold small"><i class="bi bi-mortarboard me-2"></i>Education Details</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light" style="font-size:12px;">
                <tr>
                    <th>Exam</th>
                    <th>Stream</th>
                    <th>Institute</th>
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
            <tbody style="font-size:12px;">
                @foreach($eduRows as $edu)
                @php $edu = (array) $edu; @endphp
                <tr>
                    <td class="fw-semibold text-primary">{{ strtoupper($edu['exam_name'] ?? '') }}</td>
                    <td>{{ isset($edu['education_stream']) && $edu['education_stream'] ? strtoupper($edu['education_stream']) : '—' }}</td>
                    <td>{{ $edu['institute_name'] ?? '—' }}</td>
                    <td>{{ $edu['roll_number'] ?? '—' }}</td>
                    <td>{{ $edu['passing_year'] ?? '—' }}</td>
                    <td>{{ $edu['district'] ?? '—' }}</td>
                    <td>{{ isset($edu['division']) && $edu['division'] ? strtoupper($edu['division']) : '—' }}</td>
                    <td>{{ $edu['board_university'] ?? '—' }}</td>
                    <td>{{ $edu['obtained_marks'] ?? '—' }}</td>
                    <td>{{ $edu['max_marks'] ?? '—' }}</td>
                    <td>{{ isset($edu['percentage']) && $edu['percentage'] ? $edu['percentage'].'%' : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($hasScholarship)
@php
    $scholarshipFields = [
        'Scholarship Name'      => $snapVal('scholarship_name', $student->scholarship_name),
        'Scholarship Type'      => ucfirst($snapVal('scholarship_type', $student->scholarship_type)),
        'Authority'             => $snapVal('scholarship_authority', $student->scholarship_authority),
        'Reference No.'         => $snapVal('scholarship_ref_no', $student->scholarship_ref_no),
        'Applied Date'          => $ps !== null
            ? (isset($ps['scholarship_applied_date']) ? \Carbon\Carbon::parse($ps['scholarship_applied_date'])->format('d-m-Y') : '—')
            : ($student->scholarship_applied_date?->format('d-m-Y') ?? '—'),
        'Scholarship Amount'    => $ps !== null
            ? (($ps['scholarship_amount'] ?? null) ? '₹'.number_format($ps['scholarship_amount'], 2) : '—')
            : ($student->scholarship_amount ? '₹'.number_format($student->scholarship_amount, 2) : '—'),
    ];
@endphp
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b;color:white;">
        <span class="fw-bold small"><i class="bi bi-award me-2"></i>Scholarship Details</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light" style="font-size:12px;">
                <tr>
                    @foreach(array_keys($scholarshipFields) as $lbl)
                    <th class="text-nowrap">{{ $lbl }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody style="font-size:12px;">
                <tr>
                    @foreach($scholarshipFields as $val)
                    <td class="fw-semibold text-nowrap">{{ $val }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- 5. Academic Change History --}}
@if($hasHistory)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b;color:white;">
        <span class="fw-bold small"><i class="bi bi-arrow-left-right me-2"></i>Academic Change History</span>
    </div>
    <div class="card-body">
        <div class="d-flex flex-column gap-3">
            @foreach($academicChangeLogs as $log)
            @php
                $oldSnapshot = $log->old_snapshot ?? [];
                $newSnapshot = $log->new_snapshot ?? [];
                $oldSubjects = $oldSnapshot['subject_names'] ?? [];
                $newSubjects = $newSnapshot['subject_names'] ?? [];
            @endphp
            <div class="border rounded-3 p-3" style="background:#fafafa;">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                    <div>
                        <div class="fw-semibold small">{{ $log->reason ?: 'Academic correction' }}</div>
                        <div class="text-muted small">
                            {{ $log->created_at?->format('d M Y h:i A') ?? '—' }}
                            @if($log->actor_name)
                                • By {{ $log->actor_name }}
                            @endif
                            @if($log->actor_type)
                                <span class="text-uppercase">({{ $log->actor_type }})</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted">Fee Adjustment</div>
                        <div class="fw-bold {{ (float) $log->fee_delta > 0 ? 'text-danger' : ((float) $log->fee_delta < 0 ? 'text-success' : 'text-muted') }}">
                            {{ (float) $log->fee_delta > 0 ? '+' : ((float) $log->fee_delta < 0 ? '-' : '') }}₹{{ number_format(abs((float) $log->fee_delta), 2) }}
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-2">
                    <div class="col-md-6">
                        <div class="small text-muted fw-semibold mb-1">Before</div>
                        <div class="small"><span class="text-muted">Course:</span> <span class="fw-semibold">{{ $oldSnapshot['course_name'] ?? '—' }}</span></div>
                        <div class="small"><span class="text-muted">Stream:</span> <span class="fw-semibold">{{ $oldSnapshot['stream_name'] ?? '—' }}</span></div>
                        <div class="small"><span class="text-muted">Part:</span> <span class="fw-semibold">{{ $oldSnapshot['course_part_name'] ?? '—' }}</span></div>
                        <div class="small"><span class="text-muted">Academic Fee:</span> <span class="fw-semibold text-danger">₹{{ number_format((float) $log->old_academic_fee, 2) }}</span></div>
                        @if(!empty($oldSubjects))
                        <div class="mt-2">
                            @foreach($oldSubjects as $subjectName)
                                <span class="badge bg-light text-dark border me-1 mb-1">{{ $subjectName }}</span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted fw-semibold mb-1">After</div>
                        <div class="small"><span class="text-muted">Course:</span> <span class="fw-semibold">{{ $newSnapshot['course_name'] ?? '—' }}</span></div>
                        <div class="small"><span class="text-muted">Stream:</span> <span class="fw-semibold">{{ $newSnapshot['stream_name'] ?? '—' }}</span></div>
                        <div class="small"><span class="text-muted">Part:</span> <span class="fw-semibold">{{ $newSnapshot['course_part_name'] ?? '—' }}</span></div>
                        <div class="small"><span class="text-muted">Academic Fee:</span> <span class="fw-semibold text-primary">₹{{ number_format((float) $log->new_academic_fee, 2) }}</span></div>
                        @if(!empty($newSubjects))
                        <div class="mt-2">
                            @foreach($newSubjects as $subjectName)
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary me-1 mb-1">{{ $subjectName }}</span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>

                <div class="d-flex justify-content-between flex-wrap gap-2 small border-top pt-2">
                    <div><span class="text-muted">Wallet After:</span> <span class="fw-semibold {{ (float) $log->wallet_balance_after >= 0 ? 'text-success' : 'text-danger' }}">₹{{ number_format(abs((float) $log->wallet_balance_after), 2) }} {{ (float) $log->wallet_balance_after >= 0 ? 'Advance' : 'Due' }}</span></div>
                    @if($log->notes)
                    <div class="text-muted">{{ $log->notes }}</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- 6. Fee & History --}}
@if($hasFeeRecords || $isTerminalStudent)
    @if($hasFeeRecords)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background:#1e293b;color:white;">
            <span class="fw-bold small"><i class="bi bi-wallet me-2"></i>Fee Summary — {{ $sessName }}</span>
            <a href="{{ route($walletRoute, ['student' => $student->id, 'session_id' => $selectedSessionId]) }}"
               class="btn btn-sm btn-outline-light py-0 px-2" style="font-size:11px;">
                Full History <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="row g-0 text-center">
                <div class="col border-end py-3">
                    <div class="small text-muted mb-1">Total Charged</div>
                    <div class="fw-bold text-danger">₹{{ number_format($feeSummary['total_charged'], 2) }}</div>
                </div>
                <div class="col border-end py-3">
                    <div class="small text-muted mb-1">Total Paid</div>
                    <div class="fw-bold text-success">₹{{ number_format($feeSummary['total_paid'], 2) }}</div>
                </div>
                @if(($feeSummary['total_fine'] ?? 0) > 0)
                <div class="col border-end py-3">
                    <div class="small text-muted mb-1">Total Fine</div>
                    <div class="fw-bold text-warning">₹{{ number_format($feeSummary['total_fine'], 2) }}</div>
                </div>
                @endif
                @if(($feeSummary['total_discount'] ?? 0) > 0)
                <div class="col border-end py-3">
                    <div class="small text-muted mb-1">Total Discount</div>
                    <div class="fw-bold text-purple" style="color:#7c3aed;">₹{{ number_format($feeSummary['total_discount'], 2) }}</div>
                </div>
                @endif
                <div class="col py-3">
                    <div class="small text-muted mb-1">Pending Due</div>
                    <div class="fw-bold {{ $feeIsClear ? 'text-success' : 'text-warning' }}">
                        @if($feeIsClear)
                            <i class="bi bi-check-circle me-1"></i>Clear
                        @else
                            ₹{{ number_format($feeDue, 2) }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body text-center text-muted small py-4">
            <i class="bi bi-wallet2 me-1"></i> No fee records for this session yet.
        </div>
    </div>
    @endif
@endif

{{-- 7. Transport --}}
<div class="card border-0 shadow-sm {{ $allTransport->count() >= 1 ? 'mb-3' : '' }}">
    <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background:#1e293b;color:white;">
        <span class="fw-bold small"><i class="bi bi-bus-front me-2"></i>Transport Details</span>
        @if($transport)
            <span class="badge {{ $transport->is_active ? 'bg-success' : 'bg-secondary' }}">
                {{ $transport->is_active ? 'Active' : 'Inactive' }}
            </span>
        @endif
    </div>
    @if($transport)
    @php
        $transportFields = [
            'Route'          => $transport->route?->name ?? '—',
            'Stop'           => $transport->stop?->stop_name ?? '—',
            'Vehicle'        => $transport->vehicle ? ($transport->vehicle->vehicle_no ?? $transport->vehicle->name ?? '—') : '—',
            'Driver'         => $transport->driver?->name ?? '—',
            'Fee Amount'     => $transport->fee_amount ? '₹' . number_format($transport->fee_amount, 2) : '—',
            'Paid Amount'    => $transport->paid_amount ? '₹' . number_format($transport->paid_amount, 2) : '₹0.00',
            'Balance Due'    => ($transport->balance > 0 ? '₹' . number_format($transport->balance, 2) : 'Clear'),
            'Start Date'     => $transport->start_date?->format('d-m-Y') ?? '—',
            'End Date'       => $transport->end_date?->format('d-m-Y') ?? '—',
            'Remarks'        => $transport->remarks ?? '—',
        ];
    @endphp
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light" style="font-size:12px;">
                <tr>
                    @foreach(array_keys($transportFields) as $lbl)
                    <th class="text-nowrap">{{ $lbl }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody style="font-size:12px;">
                <tr>
                    @foreach($transportFields as $lbl => $val)
                    <td class="fw-semibold text-nowrap {{ $lbl === 'Balance Due' && $transport->balance > 0 ? 'text-danger' : ($lbl === 'Balance Due' ? 'text-success' : '') }}">{{ $val }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
    @else
    <div class="card-body py-3 text-center text-muted small">
        <i class="bi bi-bus-front me-1"></i> No transport allocation for this student.
    </div>
    @endif
</div>

@if($allTransport->count() >= 1)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background:#1e293b;color:white;">
        <span class="fw-bold small"><i class="bi bi-arrow-left-right me-2"></i>Transport History</span>
        <span class="badge bg-secondary">{{ $allTransport->count() }} record{{ $allTransport->count() > 1 ? 's' : '' }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:12px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Route</th>
                        <th>Stop</th>
                        <th class="text-end">Fee</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allTransport as $i => $ta)
                    <tr class="{{ $ta->is_active ? 'table-success' : '' }}">
                        <td class="ps-3 text-muted">{{ $allTransport->count() - $i }}</td>
                        <td class="fw-semibold">{{ $ta->route?->name ?? '—' }}</td>
                        <td class="text-muted">{{ $ta->stop?->stop_name ?? '—' }}</td>
                        <td class="text-end">₹{{ number_format((float)$ta->fee_amount, 2) }}</td>
                        <td class="text-end text-success">₹{{ number_format((float)$ta->paid_amount, 2) }}</td>
                        <td class="text-end {{ $ta->balance > 0 ? 'text-danger fw-semibold' : 'text-success' }}">
                            {{ $ta->balance > 0 ? '₹'.number_format($ta->balance,2) : '✓ Clear' }}
                        </td>
                        <td>{{ $ta->start_date?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $ta->end_date?->format('d M Y') ?? '—' }}</td>
                        <td>
                            @if($ta->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Closed</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- 8. Documents --}}
@include('institute.admission._documents-verify', [
    'student'   => $student,
    'canVerify' => $docCanVerify,
    'canUpload' => $docCanUpload,
    'canDelete' => $docCanDelete,
])

@endsection
