@extends('institute.layout')
@section('title', 'Student Profile')
@section('breadcrumb', 'Admissions / Student Profile')

@section('content')

@php
    $sid         = $student->id;
    $courseName  = optional(optional($student->stream)->course)->name ?? '—';
    $streamName  = optional($student->stream)->name ?? '—';
    $sessionName = optional($student->session)->name ?? '—';
    $partName    = optional($student->coursePart)->year_label ?? '—';
    $studentUidParts = explode('/', (string) $student->student_uid);
    $serialNo = end($studentUidParts) ?: '—';
    $isPending = $student->status === 'pending';
    $approvalRoute = auth()->guard('staff')->check() ? 'staff.admissions.approvals.show' : 'admissions.approvals.show';
@endphp

{{-- Pending Admission Banner ---}}
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

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-person-badge me-2 text-primary"></i>Student Profile</h4>
        <small class="text-muted">{{ $sessionName }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admissions.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <a href="{{ route('admissions.print-all', ['student' => $sid]) }}" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-printer me-1"></i> Print Form
        </a>
        @if($isPending)
            <a href="{{ route($approvalRoute, $sid) }}" class="btn btn-warning btn-sm text-white">
                <i class="bi bi-shield-check me-1"></i> Approve Admission
            </a>
        @else
            <a href="{{ route('fee.create', ['student_id' => $sid]) }}" class="btn btn-success btn-sm">
                <i class="bi bi-cash me-1"></i> Collect Fee
            </a>
            <a href="{{ route('fee.wallet.student', $sid) }}" class="btn btn-outline-info btn-sm">
                <i class="bi bi-wallet me-1"></i> Wallet
            </a>
        @endif
        <a href="{{ route('admissions.edit', $student) }}" class="btn btn-warning btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('resendCredsModal').style.display='flex'">
            <i class="bi bi-key me-1"></i> Reset Portal Login
        </button>
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

{{-- Resend Credentials Confirm Modal --}}
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

{{-- Student Header Card --}}
<div class="card border-0 shadow-sm mb-4" style="background:linear-gradient(135deg,#1e293b,#0f4c81);color:white;">
    <div class="card-body p-4">
        <div class="d-flex gap-4 align-items-center">
            <div class="flex-shrink-0">
                @if($student->photo)
                    <img src="{{ asset('storage/' . $student->photo) }}"
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
                    <span class="badge {{ $student->status === 'pending' ? 'bg-warning text-dark' : ($student->status === 'active' ? 'bg-success' : 'bg-secondary') }}">
                        {{ ucfirst($student->status ?? 'active') }}
                    </span>
                </div>
                <div class="row g-2" style="font-size:13px;">
                    <div class="col-6 col-md-3">
                        <div class="opacity-75 small">Course</div>
                        <div class="fw-semibold">{{ $courseName }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="opacity-75 small">Stream</div>
                        <div class="fw-semibold">{{ $streamName }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="opacity-75 small">Year / Semester</div>
                        <div class="fw-semibold">{{ $partName }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="opacity-75 small">Session</div>
                        <div class="fw-semibold">{{ $sessionName }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="opacity-75 small">Mobile</div>
                        <div class="fw-semibold">{{ $student->mobile ?? '—' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="opacity-75 small">Admission Date</div>
                        <div class="fw-semibold">{{ $student->admission_date?->format('d M Y') ?? '—' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="opacity-75 small">Gender</div>
                        <div class="fw-semibold">{{ ucfirst($student->gender ?? '—') }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="opacity-75 small">DOB</div>
                        <div class="fw-semibold">{{ $student->dob?->format('d M Y') ?? '—' }}</div>
                    </div>
                    @if($student->feePlan)
                    <div class="col-12 col-md-6">
                        <div class="opacity-75 small">Fee Plan</div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="fw-semibold text-primary">{{ $student->feePlan->name }}</span>
                            @foreach($student->feePlan->installments as $inst)
                            <span class="badge bg-light text-dark border" style="font-size:11px;">
                                {{ $inst->label }}: {{ $inst->percentage }}%
                            </span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">

    {{-- Personal Details --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2" style="background:#1e293b;color:white;">
                <span class="fw-bold small"><i class="bi bi-person me-2"></i>Personal Details</span>
            </div>
            <div class="card-body p-0">
                @foreach([
                    'Category'         => strtoupper($student->category ?? '—'),
                    'Special Category' => strtoupper($student->special_category ?? '—'),
                    'Nationality'      => ucfirst($student->nationality ?? '—'),
                    'Religion'         => ucfirst($student->religion ?? '—'),
                    'Student Type'     => ucfirst($student->student_type ?? '—'),
                    'Marital Status'   => ucfirst($student->marital_status ?? '—'),
                    'Aadhar No.'       => $student->aadhar_no ?? '—',
                    'APAAR No.'        => $student->apaar_no ?? '—',
                    'Email'            => $student->email ?? '—',
                ] as $label => $value)
                <div class="d-flex border-bottom px-3 py-2" style="font-size:13px;">
                    <div class="text-muted" style="width:145px;flex-shrink:0;">{{ $label }}</div>
                    <div class="fw-semibold">{{ $value }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Parent Details --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2" style="background:#1e293b;color:white;">
                <span class="fw-bold small"><i class="bi bi-people me-2"></i>Parent Details</span>
            </div>
            <div class="card-body p-0">
                @foreach([
                    'Father Name'       => $student->father_name ?? '—',
                    'Father Mobile'     => $student->father_mobile ?? '—',
                    'Father Occupation' => $student->father_occupation ?? '—',
                    'Mother Name'       => $student->mother_name ?? '—',
                    'Mother Mobile'     => $student->mother_mobile ?? '—',
                    'Mother Occupation' => $student->mother_occupation ?? '—',
                    'Guardian Name'     => $student->guardian_name ?? '—',
                    'Guardian Mobile'   => $student->guardian_mobile ?? '—',
                    'Relation'          => $student->guardian_relation ?? '—',
                ] as $label => $value)
                <div class="d-flex border-bottom px-3 py-2" style="font-size:13px;">
                    <div class="text-muted" style="width:145px;flex-shrink:0;">{{ $label }}</div>
                    <div class="fw-semibold">{{ $value }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-md-6">

        {{-- Address Details --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2" style="background:#1e293b;color:white;">
                <span class="fw-bold small"><i class="bi bi-geo-alt me-2"></i>Address Details</span>
            </div>
            <div class="card-body p-0">
                @foreach([
                    'Village/City' => $student->perm_village ?? '—',
                    'Post'         => $student->perm_post ?? '—',
                    'Thana'        => $student->perm_thana ?? '—',
                    'District'     => $student->perm_district ?? '—',
                    'State'        => $student->perm_state ?? '—',
                    'Pin Code'     => $student->perm_pincode ?? '—',
                    'Comm. Address'=> ($student->comm_same_as_perm) ? 'Same as above' : ($student->comm_address ?? '—'),
                ] as $label => $value)
                <div class="d-flex border-bottom px-3 py-2" style="font-size:13px;">
                    <div class="text-muted" style="width:145px;flex-shrink:0;">{{ $label }}</div>
                    <div class="fw-semibold">{{ $value }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Office Details --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2" style="background:#1e293b;color:white;">
                <span class="fw-bold small"><i class="bi bi-briefcase me-2"></i>Office Details</span>
            </div>
            <div class="card-body p-0">
                @php
                    $admissionSourceDisplay = ucwords(str_replace('_', ' ', $student->admission_source ?? 'direct'));
                    if ($student->admission_source === 'center' && $student->admission_source_id) {
                        $srcN = \App\Models\Center::find($student->admission_source_id)?->name;
                        if ($srcN) $admissionSourceDisplay .= ' — ' . $srcN;
                    } elseif ($student->admission_source === 'channel_partner' && $student->admission_source_id) {
                        $srcN = \App\Models\ChannelPartner::find($student->admission_source_id)?->name;
                        if ($srcN) $admissionSourceDisplay .= ' — ' . $srcN;
                    }
                @endphp
                @foreach([
                    'Serial No.'       => $serialNo,
                    'Form No.'         => $student->currentAcademicIdentity?->form_no ?? $student->institute_form_no ?? '—',
                    'SR No.'           => $student->sr_no ?? '—',
                    'Enrollment No.'   => $student->enrollment_no ?? '—',
                    'Roll No.'         => $student->roll_no ?? '—',
                    'Exam Form No.'    => $student->exam_form_no ?? '—',
                    'UIN No.'          => $student->uin_no ?? '—',
                    'Reference No.'    => $student->reference_no ?? '—',
                    'Submitted Date'   => $student->submitted_date?->format('d-m-Y') ?? '—',
                    'Admission Type'   => ucfirst($student->admission_type ?? 'new'),
                    'Admission Source' => $admissionSourceDisplay,
                    'Gap Year'         => ($student->gap_year) ? 'Yes' : 'No',
                    'Admission Date'   => $student->admission_date?->format('d-m-Y') ?? '—',
                    'Academic Session' => $sessionName,
                ] as $label => $value)
                <div class="d-flex border-bottom px-3 py-2" style="font-size:13px;">
                    <div class="text-muted" style="width:145px;flex-shrink:0;">{{ $label }}</div>
                    <div class="fw-semibold">{{ $value }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Subjects --}}
        @if($student->studentSubjects && $student->studentSubjects->count())
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2" style="background:#1e293b;color:white;">
                <span class="fw-bold small"><i class="bi bi-list-check me-2"></i>Subjects</span>
            </div>
            <div class="card-body">
                @php
                    $subjectsByRole = $student->studentSubjects
                        ->where('academic_session_id', $student->academic_session_id)
                        ->groupBy('subject_role');
                @endphp
                @foreach(['compulsory'=>'success','major'=>'primary','minor'=>'info','optional'=>'secondary','both'=>'warning'] as $role => $color)
                @if(isset($subjectsByRole[$role]))
                <div class="mb-2">
                    <span class="small text-muted fw-semibold">{{ ucfirst($role) }}:</span>
                    @foreach($subjectsByRole[$role] as $ss)
                    <span class="badge bg-{{ $color }} ms-1">{{ $ss->subject?->name ?? '—' }}</span>
                    @endforeach
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Education Details --}}
@if($student->educationDetails && $student->educationDetails->count())
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b;color:white;">
        <span class="fw-bold small"><i class="bi bi-mortarboard me-2"></i>Education Details</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light" style="font-size:12px;">
                <tr><th>EXAM</th><th>STREAM</th><th>Institute</th><th>Board/University</th><th>Year</th><th>Division</th><th>%</th></tr>
            </thead>
            <tbody style="font-size:12px;">
                @foreach($student->educationDetails as $edu)
                <tr>
                    <td class="fw-semibold text-primary">{{ $edu->exam_name }}</td>
                    <td>{{ $edu->institute_name ?? '—' }}</td>
                    <td>{{ $edu->board_university ?? '—' }}</td>
                    <td>{{ $edu->passing_year ?? '—' }}</td>
                    <td>{{ $edu->division ?? '—' }}</td>
                    <td>@if($edu->percentage)<span class="fw-semibold text-success">{{ $edu->percentage }}%</span>@else —@endif</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
