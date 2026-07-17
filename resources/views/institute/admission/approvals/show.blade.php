@php
    $isStaff = auth()->guard('staff')->check();
    $layout = $isStaff ? 'staff.layout' : 'institute.layout';
    $indexRoute = $isStaff ? 'staff.admissions.approvals.index' : 'admissions.approvals.index';
    $approveRoute = $isStaff ? 'staff.admissions.approvals.approve' : 'admissions.approvals.approve';
    $statusRoute = $isStaff ? 'staff.admissions.approvals.status' : 'admissions.approvals.status';
    $admissionRoute = $isStaff ? 'staff.admissions.show' : 'admissions.show';
    $feeCreateRoute = $isStaff ? 'staff.fee.create' : 'fee.create';
    $printReceiptRoute = $isStaff ? 'staff.admissions.print-all-receipt' : 'admissions.print-all-receipt';
    $feeStatusMap = [
        'not_paid' => ['label' => 'Not Paid', 'class' => 'bg-danger'],
        'partial'  => ['label' => 'Partially Paid', 'class' => 'bg-warning text-dark'],
        'paid'     => ['label' => 'Paid / Cleared', 'class' => 'bg-success'],
    ];
    $feeBadge = $feeStatusMap[$feeStatus] ?? ['label' => ucfirst($feeStatus), 'class' => 'bg-secondary'];
    $canVerifyDocs = $isStaff ? auth()->guard('staff')->user()->hasPermission('document_verify') : true;
    $docVerifyRoute = $isStaff ? 'staff.admission.documents.verify' : 'admission.documents.verify';
    $docRejectRoute = $isStaff ? 'staff.admission.documents.reject' : 'admission.documents.reject';
@endphp
@extends($layout)
@section('title', 'Admission Review')
@section('breadcrumb', 'Admissions / Review')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Admission Review</h4>
        <small class="text-muted">Review student details and approve or change status.</small>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route($indexRoute) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Queue
        </a>
        <a href="{{ route($admissionRoute, $student) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-person-vcard me-1"></i>Full Profile
        </a>
        <a href="{{ route($feeCreateRoute, ['student_id' => $student->id]) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-cash-coin me-1"></i>Collect Fee
        </a>
    </div>
</div>

{{-- Hero Banner --}}
<div class="card border-0 shadow-sm mb-4" style="background:linear-gradient(135deg,#1e293b,#0f766e);color:#fff;">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <h4 class="mb-0 text-white fw-bold">{{ $student->name }}</h4>
                    <span class="badge bg-primary">{{ $student->student_uid }}</span>
                    @php
                        $heroBadgeClass = match($student->status) {
                            'active'    => 'bg-success',
                            'cancelled' => 'bg-danger',
                            'inactive'  => 'bg-secondary',
                            default     => 'bg-warning text-dark',
                        };
                    @endphp
                    <span class="badge {{ $heroBadgeClass }}">{{ ucwords(str_replace('_',' ',$student->status ?? 'pending')) }}</span>
                </div>
                <div class="row g-2 small">
                    <div class="col-md-4"><span class="opacity-75">Course:</span> {{ $student->stream?->course?->name ?? '-' }}</div>
                    <div class="col-md-4"><span class="opacity-75">Stream:</span> {{ $student->stream?->name ?? '-' }}</div>
                    <div class="col-md-4"><span class="opacity-75">Session:</span> {{ $student->session?->name ?? '-' }}</div>
                    <div class="col-md-4"><span class="opacity-75">Year/Part:</span> {{ $student->coursePart?->year_label ?? '-' }}</div>
                    <div class="col-md-4"><span class="opacity-75">Semester:</span> {{ $student->current_semester ? 'Sem '.$student->current_semester : '-' }}</div>
                    <div class="col-md-4"><span class="opacity-75">Admission Date:</span> {{ $student->admission_date?->format('d M Y') ?? '-' }}</div>
                </div>
            </div>
            <div class="text-md-end">
                <div class="small opacity-75">Fee Status</div>
                <div class="mt-1"><span class="badge {{ $feeBadge['class'] }}">{{ $feeBadge['label'] }}</span></div>
                @if($student->approved_at)
                    <div class="small mt-3 opacity-75">Approved By</div>
                    <div>{{ $student->approved_by_name ?? ($student->approvedByStaff?->name ?? '-') }}</div>
                    <div class="small">{{ $student->approved_at->format('d M Y, h:i A') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-3">

    {{-- LEFT COLUMN: Detail sections --}}
    <div class="col-lg-8">

        {{-- 1. Office & Registration Details --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2 d-flex align-items-center gap-2">
                <i class="bi bi-building text-primary"></i>
                <h6 class="mb-0 fw-semibold">Office & Registration Details</h6>
            </div>
            <div class="card-body py-3">
                <div class="row g-2 small">
                    <div class="col-md-4"><span class="text-muted">Student UID:</span> <span class="fw-semibold">{{ $student->student_uid ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Form No:</span> <span class="fw-semibold">{{ $student->institute_form_no ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">SR No:</span> <span class="fw-semibold">{{ $student->sr_no ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Enrollment No:</span> <span class="fw-semibold">{{ $student->enrollment_no ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Roll No:</span> <span class="fw-semibold">{{ $student->roll_no ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Exam Form No:</span> <span class="fw-semibold">{{ $student->exam_form_no ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">UIN No:</span> <span class="fw-semibold">{{ $student->uin_no ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Reference No:</span> <span class="fw-semibold">{{ $student->reference_no ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Admitted By:</span>
                        <span class="fw-semibold">
                            @php $admittedByType = $student->admitted_by_type ?? 'admin'; @endphp
                            @if($admittedByType === 'staff') Staff: {{ $student->admittedBy?->name ?? 'Staff' }}
                            @elseif($admittedByType === 'center') Center
                            @elseif($admittedByType === 'channel_partner') Partner
                            @else Admin
                            @endif
                            @if($admissionSourceName) — {{ $admissionSourceName }} @endif
                        </span>
                    </div>
                    <div class="col-md-4"><span class="text-muted">Admission Type:</span> <span class="fw-semibold">{{ ucfirst($student->admission_type ?? 'new') }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Admission Date:</span> <span class="fw-semibold">{{ $student->admission_date?->format('d M Y') ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Submitted Date:</span> <span class="fw-semibold">{{ $student->submitted_date?->format('d M Y') ?? '-' }}</span></div>
                </div>
            </div>
        </div>

        {{-- 2. Course Details --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2 d-flex align-items-center gap-2">
                <i class="bi bi-mortarboard text-info"></i>
                <h6 class="mb-0 fw-semibold">Course Details</h6>
            </div>
            <div class="card-body py-3">
                <div class="row g-2 small">
                    <div class="col-md-4"><span class="text-muted">Course Type:</span> <span class="fw-semibold">{{ $student->stream?->course?->type?->name ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Course:</span> <span class="fw-semibold">{{ $student->stream?->course?->name ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Stream / Specialization:</span> <span class="fw-semibold">{{ $student->stream?->name ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Session:</span> <span class="fw-semibold">{{ $student->session?->name ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Year / Part:</span> <span class="fw-semibold">{{ $student->coursePart?->year_label ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Current Semester:</span> <span class="fw-semibold">{{ $student->current_semester ? 'Semester '.$student->current_semester : '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Student Type:</span> <span class="fw-semibold">{{ ucfirst($student->student_type ?? '-') }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Gap Year:</span> <span class="fw-semibold">{{ $student->gap_year ? 'Yes' : 'No' }}</span></div>
                </div>
            </div>
        </div>

        {{-- 3. Personal Details --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2 d-flex align-items-center gap-2">
                <i class="bi bi-person text-success"></i>
                <h6 class="mb-0 fw-semibold">Personal Details</h6>
            </div>
            <div class="card-body py-3">
                <div class="row g-2 small">
                    <div class="col-md-4"><span class="text-muted">Date of Birth:</span> <span class="fw-semibold">{{ $student->dob?->format('d M Y') ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Gender:</span> <span class="fw-semibold">{{ ucfirst($student->gender ?? '-') }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Marital Status:</span> <span class="fw-semibold">{{ ucfirst($student->marital_status ?? '-') }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Category:</span> <span class="fw-semibold">{{ strtoupper($student->category ?? '-') }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Special Category:</span> <span class="fw-semibold">{{ $student->special_category ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Religion:</span> <span class="fw-semibold">{{ ucfirst($student->religion ?? '-') }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Nationality:</span> <span class="fw-semibold">{{ ucfirst($student->nationality ?? '-') }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Aadhar No:</span> <span class="fw-semibold">{{ $student->aadhar_no ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">APAAR No:</span> <span class="fw-semibold">{{ $student->apaar_no ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Mobile:</span> <span class="fw-semibold">{{ $student->mobile ?? '-' }}</span></div>
                    <div class="col-md-8"><span class="text-muted">Email:</span> <span class="fw-semibold">{{ $student->email ?? '-' }}</span></div>
                </div>
            </div>
        </div>

        {{-- 4. Parent & Contact Details --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2 d-flex align-items-center gap-2">
                <i class="bi bi-people text-warning"></i>
                <h6 class="mb-0 fw-semibold">Parent & Contact Details</h6>
            </div>
            <div class="card-body py-3">
                <div class="row g-2 small">
                    <div class="col-md-4"><span class="text-muted">Father Name:</span> <span class="fw-semibold">{{ $student->father_name ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Father Mobile:</span> <span class="fw-semibold">{{ $student->father_mobile ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Father Occupation:</span> <span class="fw-semibold">{{ $student->father_occupation ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Mother Name:</span> <span class="fw-semibold">{{ $student->mother_name ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Mother Mobile:</span> <span class="fw-semibold">{{ $student->mother_mobile ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Mother Occupation:</span> <span class="fw-semibold">{{ $student->mother_occupation ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Guardian Name:</span> <span class="fw-semibold">{{ $student->guardian_name ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Guardian Mobile:</span> <span class="fw-semibold">{{ $student->guardian_mobile ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Guardian Relation:</span> <span class="fw-semibold">{{ $student->guardian_relation ?? '-' }}</span></div>
                </div>
            </div>
        </div>

        {{-- 5. Address Details --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2 d-flex align-items-center gap-2">
                <i class="bi bi-geo-alt text-danger"></i>
                <h6 class="mb-0 fw-semibold">Address Details</h6>
            </div>
            <div class="card-body py-3">
                <div class="row g-3 small">
                    <div class="col-md-6">
                        <div class="fw-semibold text-muted mb-1">Permanent Address</div>
                        <div>{{ $student->perm_address ?? '-' }}</div>
                        <div>{{ implode(', ', array_filter([$student->perm_village, $student->perm_post, $student->perm_thana])) ?: '-' }}</div>
                        <div>{{ implode(', ', array_filter([$student->perm_district, $student->perm_state, $student->perm_pincode])) ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="fw-semibold text-muted mb-1">Communication Address
                            @if($student->comm_same_as_perm)
                                <span class="badge bg-secondary bg-opacity-50 ms-1" style="font-size:0.7rem;">Same as Permanent</span>
                            @endif
                        </div>
                        @if($student->comm_same_as_perm)
                            <div class="text-muted small fst-italic">Same as permanent address above.</div>
                        @else
                            <div>{{ $student->comm_address ?? '-' }}</div>
                            <div>{{ implode(', ', array_filter([$student->comm_post, $student->comm_thana, $student->comm_city])) ?: '-' }}</div>
                            <div>{{ implode(', ', array_filter([$student->comm_district, $student->comm_state, $student->comm_pincode])) ?: '-' }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- 6. Subjects & Education --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2 d-flex align-items-center gap-2">
                <i class="bi bi-book text-primary"></i>
                <h6 class="mb-0 fw-semibold">Subjects & Education</h6>
            </div>
            <div class="card-body py-3">
                <div class="mb-3">
                    <div class="small text-muted fw-semibold mb-2">Selected Subjects</div>
                    @forelse($student->studentSubjects as $studentSubject)
                        <span class="badge bg-light text-dark border me-1 mb-1">
                            {{ $studentSubject->subject?->name ?? '-' }}
                            <span class="text-muted">({{ ucfirst($studentSubject->subject_role ?? 'compulsory') }})</span>
                        </span>
                    @empty
                        <div class="text-muted small">No subject mapped.</div>
                    @endforelse
                </div>

                <div>
                    <div class="small text-muted fw-semibold mb-2">Education Records</div>
                    @if($student->educationDetails->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Exam</th>
                                        <th>Institute</th>
                                        <th>Stream</th>
                                        <th>Board / University</th>
                                        <th>Roll No</th>
                                        <th>Year</th>
                                        <th>District</th>
                                        <th>Division</th>
                                        <th>Obtained</th>
                                        <th>Max</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($student->educationDetails as $edu)
                                        <tr>
                                            <td>{{ $edu->exam_name ?? '-' }}</td>
                                            <td>{{ $edu->institute_name ?? '-' }}</td>
                                            <td>{{ $edu->education_stream ?? '-' }}</td>
                                            <td>{{ $edu->board_university ?? '-' }}</td>
                                            <td>{{ $edu->roll_number ?? '-' }}</td>
                                            <td>{{ $edu->passing_year ?? '-' }}</td>
                                            <td>{{ $edu->district ?? '-' }}</td>
                                            <td>{{ $edu->division ?? '-' }}</td>
                                            <td>{{ $edu->obtained_marks ?? '-' }}</td>
                                            <td>{{ $edu->max_marks ?? '-' }}</td>
                                            <td>{{ $edu->percentage ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-muted small">No education records available.</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- 7. Scholarship --}}
        @if($student->has_scholarship)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2 d-flex align-items-center gap-2">
                <i class="bi bi-award text-warning"></i>
                <h6 class="mb-0 fw-semibold">Scholarship Details</h6>
            </div>
            <div class="card-body py-3">
                <div class="row g-2 small">
                    <div class="col-md-4"><span class="text-muted">Scholarship Name:</span> <span class="fw-semibold">{{ $student->scholarship_name ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Type:</span> <span class="fw-semibold">{{ ucfirst($student->scholarship_type ?? '-') }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Authority:</span> <span class="fw-semibold">{{ $student->scholarship_authority ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Applied Date:</span> <span class="fw-semibold">{{ $student->scholarship_applied_date?->format('d M Y') ?? '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Amount:</span> <span class="fw-semibold">{{ $student->scholarship_amount ? 'Rs '.number_format((float)$student->scholarship_amount, 2) : '-' }}</span></div>
                    <div class="col-md-4"><span class="text-muted">Reference No:</span> <span class="fw-semibold">{{ $student->scholarship_ref_no ?? '-' }}</span></div>
                </div>
            </div>
        </div>
        @endif

        {{-- 8. Uploaded Documents --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2 d-flex align-items-center gap-2">
                <i class="bi bi-paperclip text-secondary"></i>
                <h6 class="mb-0 fw-semibold">Uploaded Documents</h6>
                <span class="badge bg-secondary ms-auto">{{ $student->admissionDocuments->count() }}</span>
            </div>
            <div class="card-body py-3">
                @if($student->admissionDocuments->isNotEmpty())
                    <div class="row g-3">
                        @foreach($student->admissionDocuments as $doc)
                            @php $fileUrl = $doc->file_url; @endphp
                            <div class="col-md-4 col-sm-6">
                                <div class="border rounded p-2 h-100 d-flex flex-column">
                                    <div class="small fw-semibold mb-1 text-truncate" title="{{ $doc->documentType?->name ?? $doc->original_name }}">
                                        {{ $doc->documentType?->name ?? 'Document' }}
                                    </div>
                                    @if($doc->isImage())
                                        <a href="{{ $fileUrl }}" target="_blank" class="d-block mb-2">
                                            <img src="{{ $fileUrl }}" alt="{{ $doc->original_name }}" class="img-fluid rounded" style="max-height:100px;object-fit:cover;width:100%;">
                                        </a>
                                    @elseif($doc->isPdf())
                                        <a href="{{ $fileUrl }}" target="_blank" class="d-flex align-items-center gap-2 mb-2 text-danger text-decoration-none">
                                            <i class="bi bi-file-earmark-pdf fs-3"></i>
                                            <span class="small text-truncate">{{ $doc->original_name }}</span>
                                        </a>
                                    @else
                                        <a href="{{ $fileUrl }}" target="_blank" class="d-flex align-items-center gap-2 mb-2 text-muted text-decoration-none">
                                            <i class="bi bi-file-earmark fs-3"></i>
                                            <span class="small text-truncate">{{ $doc->original_name }}</span>
                                        </a>
                                    @endif
                                    <div class="mt-auto d-flex gap-1">
                                        <a href="{{ $fileUrl }}" target="_blank" class="btn btn-outline-secondary btn-sm flex-fill">
                                            <i class="bi bi-eye me-1"></i>View
                                        </a>
                                        <a href="{{ $fileUrl }}" download="{{ $doc->original_name }}" class="btn btn-outline-primary btn-sm flex-fill">
                                            <i class="bi bi-download me-1"></i>Download
                                        </a>
                                    </div>
                                    @if($doc->verification_status === 'approved')
                                        <div class="small text-success mt-1"><i class="bi bi-check-circle me-1"></i>Verified</div>
                                    @elseif($doc->verification_status === 'rejected')
                                        <div class="small text-danger mt-1 mb-1"><i class="bi bi-x-circle me-1"></i>Rejected</div>
                                        @if($doc->rejection_reason)
                                            <div class="small text-muted mb-1">{{ $doc->rejection_reason }}</div>
                                        @endif
                                    @else
                                        <div class="small text-warning mt-1 mb-1"><i class="bi bi-clock me-1"></i>Pending verification</div>
                                    @endif

                                    @if($canVerifyDocs && $doc->verification_status !== 'approved')
                                        <div class="d-flex gap-1 mt-1">
                                            <form method="POST" action="{{ route($docVerifyRoute, $doc) }}" class="flex-fill">
                                                @csrf
                                                <button class="btn btn-success btn-sm w-100 py-0" style="font-size:11px;">Verify</button>
                                            </form>
                                            <button type="button" class="btn btn-outline-danger btn-sm flex-fill py-0" style="font-size:11px;"
                                                    onclick="document.getElementById('rejectDocForm{{ $doc->id }}').classList.toggle('d-none')">
                                                Reject
                                            </button>
                                        </div>
                                        <form id="rejectDocForm{{ $doc->id }}" method="POST" action="{{ route($docRejectRoute, $doc) }}" class="d-none mt-1">
                                            @csrf
                                            <input type="text" name="rejection_reason" class="form-control form-control-sm mb-1" placeholder="Reason" required maxlength="500">
                                            <button class="btn btn-outline-danger btn-sm w-100 py-0" style="font-size:11px;">Confirm Reject</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-folder2-open d-block fs-2 mb-2 opacity-25"></i>
                        No documents uploaded during admission.
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- RIGHT COLUMN: Fee + Approval Action --}}
    <div class="col-lg-4">

        {{-- Fee Verification --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2 d-flex align-items-center gap-2">
                <i class="bi bi-cash-stack text-success"></i>
                <h6 class="mb-0 fw-semibold">Fee Verification</h6>
            </div>
            <div class="card-body py-3">
                <div class="d-flex justify-content-between mb-2 small">
                    <span class="text-muted">Fee Status</span>
                    <span class="badge {{ $feeBadge['class'] }}">{{ $feeBadge['label'] }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2 small">
                    <span class="text-muted">Total Fee</span>
                    <span class="fw-semibold">Rs {{ number_format((float) ($feeSummary['total_charged'] ?? 0), 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2 small">
                    <span class="text-muted">Total Collected</span>
                    <span class="fw-semibold text-success">Rs {{ number_format((float) ($feeSummary['total_collection'] ?? 0), 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2 small">
                    <span class="text-muted">Total Fine</span>
                    <span class="fw-semibold text-warning">Rs {{ number_format((float) ($feeSummary['total_fine'] ?? 0), 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2 small">
                    <span class="text-muted">Total Discount</span>
                    <span class="fw-semibold text-info">Rs {{ number_format((float) ($feeSummary['total_discount'] ?? 0), 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-3 small border-top pt-2">
                    <span class="text-muted fw-semibold">Balance Due</span>
                    <span class="fw-bold {{ (float) ($feeSummary['total_due'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                        Rs {{ number_format((float) ($feeSummary['total_due'] ?? 0), 2) }}
                    </span>
                </div>

                @if($recentInvoices->isNotEmpty())
                    <div class="small text-muted fw-semibold mb-2">Fee Receipts</div>
                    @foreach($recentInvoices as $invoice)
                        <div class="border rounded p-2 mb-2 small">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="fw-semibold">{{ $invoice->invoice_no ?? ('Receipt #'.$invoice->id) }}</span>
                                <span class="text-muted">{{ $invoice->payment_date ? \Carbon\Carbon::parse($invoice->payment_date)->format('d M Y') : '-' }}</span>
                            </div>
                            <div class="text-muted mb-2">Paid: <strong class="text-success">Rs {{ number_format((float) ($invoice->paid_amount ?? 0), 2) }}</strong></div>
                            <div class="d-flex gap-1">
                                <a href="{{ route($printReceiptRoute, [$student, $invoice]) }}" target="_blank" class="btn btn-outline-secondary btn-sm flex-fill">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                                <a href="{{ route($printReceiptRoute, [$student, $invoice]) }}" target="_blank" class="btn btn-outline-primary btn-sm flex-fill">
                                    <i class="bi bi-download me-1"></i>Download
                                </a>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="alert alert-warning small mb-0 py-2">
                        <i class="bi bi-exclamation-circle me-1"></i>No fee receipts found.
                    </div>
                @endif
            </div>
        </div>

        {{-- Application Payment (online admissions only) --}}
        @if($student->admitted_by_type === 'online')
        @php
            $paymentVerifyRoute = $isStaff ? 'staff.payment-claims.verify' : 'payment-claims.verify';
            $paymentRejectRoute = $isStaff ? 'staff.payment-claims.reject' : 'payment-claims.reject';
            $paymentRecordRoute = $isStaff ? 'staff.payment-claims.record' : 'payment-claims.record';
            $paymentResendRoute = $isStaff ? 'staff.payment-claims.resend-link' : 'payment-claims.resend-link';
        @endphp
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2 d-flex align-items-center gap-2">
                <i class="bi bi-credit-card text-primary"></i>
                <h6 class="mb-0 fw-semibold">Application Payment</h6>
            </div>
            <div class="card-body py-3">
                @if($dueAmount <= 0)
                    <div class="text-muted small">No payment was due at admission for this course.</div>
                @else
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Due at Admission</span>
                        <span class="fw-semibold">Rs {{ number_format($dueAmount, 2) }}</span>
                    </div>

                    @if(!$paymentClaim)
                        <div class="alert alert-warning small py-2 mb-3">No payment claim submitted yet.</div>
                        <form method="POST" action="{{ route($paymentResendRoute, $student) }}" class="mb-3">
                            @csrf
                            <button class="btn btn-outline-primary btn-sm w-100">
                                <i class="bi bi-envelope me-1"></i>Resend Payment Link to Student
                            </button>
                        </form>
                    @elseif($paymentClaim->isApproved())
                        <div class="alert alert-success small py-2 mb-2">
                            <i class="bi bi-check-circle me-1"></i> Verified — Rs {{ number_format($paymentClaim->amount_claimed, 2) }}
                            ({{ $paymentClaim->payment_mode === 'pay_at_institute' ? 'Pay at Institute' : 'UPI/NEFT' }})
                        </div>
                    @else
                        @if($paymentClaim->isRejected())
                            <div class="alert alert-danger small py-2 mb-2">Rejected: {{ $paymentClaim->rejection_reason }}</div>
                            <form method="POST" action="{{ route($paymentResendRoute, $student) }}" class="mb-3">
                                @csrf
                                <button class="btn btn-outline-primary btn-sm w-100">
                                    <i class="bi bi-envelope me-1"></i>Resend Payment Link to Student
                                </button>
                            </form>
                        @endif

                        @if($paymentClaim->isPending())
                            <div class="small mb-2">
                                <div>Claimed: <strong>Rs {{ number_format($paymentClaim->amount_claimed, 2) }}</strong>
                                    @if((float) $paymentClaim->amount_claimed < $dueAmount)
                                        <span class="badge bg-warning text-dark ms-1">Below due amount</span>
                                    @endif
                                </div>
                                <div>Mode: {{ $paymentClaim->payment_mode === 'pay_at_institute' ? 'Pay at Institute' : 'UPI/NEFT' }}</div>
                                @if($paymentClaim->transaction_ref)
                                    <div>Ref: {{ $paymentClaim->transaction_ref }}</div>
                                @endif
                                @if($paymentClaim->screenshot_path)
                                    <a href="{{ asset('storage/' . $paymentClaim->screenshot_path) }}" target="_blank" class="d-inline-block mt-1">
                                        <i class="bi bi-image me-1"></i>View Screenshot
                                    </a>
                                @endif
                            </div>

                            <form method="POST" action="{{ route($paymentVerifyRoute, $paymentClaim) }}" class="d-flex gap-1 mb-2">
                                @csrf
                                <input type="number" step="0.01" name="confirmed_amount" class="form-control form-control-sm" value="{{ $paymentClaim->amount_claimed }}" style="max-width:110px;">
                                <button class="btn btn-success btn-sm">Verify</button>
                            </form>
                            <form method="POST" action="{{ route($paymentRejectRoute, $paymentClaim) }}" class="d-flex gap-1">
                                @csrf
                                <input type="text" name="rejection_reason" class="form-control form-control-sm" placeholder="Reason" required maxlength="500">
                                <button class="btn btn-outline-danger btn-sm">Reject</button>
                            </form>
                        @endif
                    @endif

                    @if(!$paymentClaim || !$paymentClaim->isApproved())
                        <div class="border-top pt-2 mt-3">
                            <div class="small fw-semibold mb-1">Record Payment Manually</div>
                            <form method="POST" action="{{ route($paymentRecordRoute, $student) }}" class="row g-1">
                                @csrf
                                <div class="col-6">
                                    <input type="number" step="0.01" name="amount" class="form-control form-control-sm" placeholder="Amount" value="{{ $dueAmount }}" required>
                                </div>
                                <div class="col-6">
                                    <select name="payment_mode" class="form-select form-select-sm" required>
                                        <option value="cash">Cash</option>
                                        <option value="upi">UPI</option>
                                        <option value="neft">NEFT</option>
                                        <option value="rtgs">RTGS</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="dd">DD</option>
                                    </select>
                                </div>
                                <div class="col-12 mt-1">
                                    <button class="btn btn-outline-success btn-sm w-100">Record</button>
                                </div>
                            </form>
                        </div>
                    @endif
                @endif
            </div>
        </div>
        @endif

        {{-- Approval / Status Action --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2 d-flex align-items-center gap-2">
                @if($student->status === 'pending')
                    <i class="bi bi-shield-check text-warning"></i>
                    <h6 class="mb-0 fw-semibold">Approval Action</h6>
                @else
                    <i class="bi bi-arrow-repeat text-primary"></i>
                    <h6 class="mb-0 fw-semibold">Change Status</h6>
                @endif
            </div>
            <div class="card-body py-3">

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show border-0 py-2 small mb-3">
                        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger border-0 py-2 small mb-3">
                        @foreach($errors->all() as $e)
                            <div><i class="bi bi-x-circle me-1"></i>{{ $e }}</div>
                        @endforeach
                    </div>
                @endif

                @if($student->status !== 'pending')
                    <div class="mb-3 p-2 rounded bg-light small">
                        <div class="text-muted">Current Status</div>
                        <span class="badge
                            {{ $student->status === 'active'    ? 'bg-success'          : '' }}
                            {{ $student->status === 'cancelled' ? 'bg-danger'           : '' }}
                            {{ $student->status === 'inactive'  ? 'bg-secondary'        : '' }}
                            {{ !in_array($student->status, ['active','cancelled','inactive']) ? 'bg-warning text-dark' : '' }}
                            mt-1">
                            {{ ucwords(str_replace('_',' ', $student->status)) }}
                        </span>
                        @if($student->status_reason)
                            <div class="text-muted mt-1">Reason: {{ $student->status_reason }}</div>
                        @endif
                        @if($student->approved_at)
                            <div class="text-muted mt-1">
                                {{ $student->approved_by_name ?? ($student->approvedByStaff?->name ?? '-') }}
                                · {{ $student->approved_at->format('d M Y, h:i A') }}
                            </div>
                        @endif
                    </div>
                @endif

                <form method="POST" action="{{ route($statusRoute, $student) }}" id="approvalActionForm">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Select Action</label>
                        <div class="d-flex flex-column gap-2">
                            <label class="d-flex align-items-center gap-2 p-2 border rounded action-option {{ old('action', $student->status === 'pending' ? 'approve' : '') === 'approve' ? 'border-success bg-success bg-opacity-10' : '' }}" style="cursor:pointer;">
                                <input type="radio" name="action" value="approve" class="form-check-input mt-0 action-radio"
                                    {{ old('action', $student->status === 'pending' ? 'approve' : '') === 'approve' ? 'checked' : '' }}>
                                <span class="small"><i class="bi bi-patch-check text-success me-1"></i><strong>Approve</strong> — Make Active</span>
                            </label>
                            <label class="d-flex align-items-center gap-2 p-2 border rounded action-option {{ old('action') === 'resubmit' ? 'border-warning bg-warning bg-opacity-10' : '' }}" style="cursor:pointer;">
                                <input type="radio" name="action" value="resubmit" class="form-check-input mt-0 action-radio"
                                    {{ old('action') === 'resubmit' ? 'checked' : '' }}>
                                <span class="small"><i class="bi bi-arrow-counterclockwise text-warning me-1"></i><strong>Re-submit</strong> — Send Back to Pending</span>
                            </label>
                            <label class="d-flex align-items-center gap-2 p-2 border rounded action-option {{ old('action') === 'reject' ? 'border-danger bg-danger bg-opacity-10' : '' }}" style="cursor:pointer;">
                                <input type="radio" name="action" value="reject" class="form-check-input mt-0 action-radio"
                                    {{ old('action') === 'reject' ? 'checked' : '' }}>
                                <span class="small"><i class="bi bi-x-circle text-danger me-1"></i><strong>Reject</strong> — Mark as Cancelled (reason required)</span>
                            </label>
                            <label class="d-flex align-items-center gap-2 p-2 border rounded action-option {{ old('action') === 'cancel' ? 'border-danger bg-danger bg-opacity-10' : '' }}" style="cursor:pointer;">
                                <input type="radio" name="action" value="cancel" class="form-check-input mt-0 action-radio"
                                    {{ old('action') === 'cancel' ? 'checked' : '' }}>
                                <span class="small"><i class="bi bi-slash-circle text-danger me-1"></i><strong>Cancel</strong> — Permanently cancel (reason required)</span>
                            </label>
                            <label class="d-flex align-items-center gap-2 p-2 border rounded action-option {{ old('action') === 'inactive' ? 'border-secondary bg-secondary bg-opacity-10' : '' }}" style="cursor:pointer;">
                                <input type="radio" name="action" value="inactive" class="form-check-input mt-0 action-radio"
                                    {{ old('action') === 'inactive' ? 'checked' : '' }}>
                                <span class="small"><i class="bi bi-pause-circle text-secondary me-1"></i><strong>Inactive</strong> — Temporarily deactivate</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3" id="reasonWrap" style="{{ in_array(old('action'), ['reject','cancel','resubmit','inactive']) ? '' : 'display:none;' }}">
                        <label class="form-label small fw-semibold">
                            Reason <span id="reasonRequired" class="{{ in_array(old('action'), ['reject','cancel']) ? '' : 'd-none' }}" style="color:red;">*</span>
                        </label>
                        <textarea name="reason" rows="3" class="form-control form-control-sm"
                            placeholder="Enter reason...">{{ old('reason') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100" id="submitBtn">
                        <i class="bi bi-check2-circle me-1"></i>Submit
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const radios = document.querySelectorAll('.action-radio');
    const reasonWrap = document.getElementById('reasonWrap');
    const reasonRequired = document.getElementById('reasonRequired');
    const submitBtn = document.getElementById('submitBtn');

    const actionColors = {
        approve:  'border-success bg-success bg-opacity-10',
        resubmit: 'border-warning bg-warning bg-opacity-10',
        reject:   'border-danger bg-danger bg-opacity-10',
        cancel:   'border-danger bg-danger bg-opacity-10',
        inactive: 'border-secondary bg-secondary bg-opacity-10',
    };
    const submitLabels = {
        approve:  'Approve',
        resubmit: 'Send to Pending',
        reject:   'Reject',
        cancel:   'Cancel',
        inactive: 'Deactivate',
    };

    function onActionChange(val) {
        document.querySelectorAll('.action-option').forEach(el => {
            el.classList.remove(...Object.values(actionColors).flatMap(c => c.split(' ')));
        });
        const selected = document.querySelector(`.action-radio[value="${val}"]`);
        if (selected) {
            selected.closest('.action-option').classList.add(...(actionColors[val] || '').split(' '));
        }
        const needsReason = ['reject', 'cancel', 'resubmit', 'inactive'].includes(val);
        const requiredReason = ['reject', 'cancel'].includes(val);
        reasonWrap.style.display = needsReason ? '' : 'none';
        reasonRequired.classList.toggle('d-none', !requiredReason);
        submitBtn.innerHTML = `<i class="bi bi-check2-circle me-1"></i>${submitLabels[val] || 'Submit'}`;
    }

    radios.forEach(r => r.addEventListener('change', () => onActionChange(r.value)));
    const checked = document.querySelector('.action-radio:checked');
    if (checked) onActionChange(checked.value);
});
</script>
@endpush
@endsection
