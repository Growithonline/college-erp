@extends('center.layout')
@section('title', $student->name)
@section('breadcrumb', 'Students / Profile')
@section('content')

@php
    $courseName  = $student->stream->course->name ?? '—';
    $streamName  = $student->stream->name ?? '—';
    $sessionName = $student->session->name ?? '—';
    $partName    = $student->coursePart->year_label ?? '—';

    $isOwnStudent = $student->admission_source === 'center'
        && (int) $student->admission_source_id === (int) $authUser->id;
    $canCollectThisStudent = $authUser->canCollectFee()
        && (!$authUser->isFeesScopeOwn() || $isOwnStudent);

    $srcName = '';
    if ($student->admission_source === 'center' && $student->admission_source_id) {
        $srcName = \App\Models\Center::find($student->admission_source_id)?->name ?? '';
    } elseif ($student->admission_source === 'channel_partner' && $student->admission_source_id) {
        $srcName = \App\Models\ChannelPartner::find($student->admission_source_id)?->name ?? '';
    }

    $activeSessionId = $student->academic_session_id;
@endphp

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-person-badge me-2 text-primary"></i>Student Profile</h4>
        <small class="text-muted">{{ $sessionName }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('center.students.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        @if($canCollectThisStudent)
        <a href="{{ route('center.fee.create') }}?student_id={{ $student->id }}" class="btn btn-success btn-sm">
            <i class="bi bi-cash me-1"></i> Collect Fee
        </a>
        @endif
    </div>
</div>

{{-- Hero card --}}
<div class="card border-0 shadow-sm mb-4" style="background:linear-gradient(135deg,#1e293b,#0f4c81);color:white;">
    <div class="card-body p-4">
        <div class="d-flex gap-4 align-items-center">
            <div class="flex-shrink-0">
                @if($student->photo)
                    <img src="{{ Storage::url($student->photo) }}"
                         style="width:90px;height:100px;object-fit:cover;border-radius:8px;border:3px solid rgba(255,255,255,0.3);">
                @else
                    <div style="width:90px;height:100px;border-radius:8px;background:rgba(255,255,255,0.15);
                                display:flex;align-items:center;justify-content:center;">
                        <span style="font-size:2.5rem;font-weight:700;">{{ strtoupper(substr($student->name, 0, 1)) }}</span>
                    </div>
                @endif
            </div>
            <div class="flex-fill">
                <div class="d-flex align-items-center gap-3 mb-2 flex-wrap">
                    <h4 class="fw-bold mb-0 text-white">{{ $student->name }}</h4>
                    <span class="badge bg-primary px-3" style="font-size:12px;">{{ $student->student_uid }}</span>
                    <span class="badge {{ $student->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
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
                        <div class="opacity-75 small">Year / Part</div>
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
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">

    {{-- Left column --}}
    <div class="col-md-5">

        {{-- Personal Details --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2" style="background:#1e293b;color:white;">
                <span class="fw-bold small"><i class="bi bi-person me-2"></i>Personal Details</span>
            </div>
            <div class="card-body p-0">
                @foreach([
                    'Email'            => $student->email ?? '—',
                    'Category'         => strtoupper($student->category ?? '—'),
                    'Special Category' => strtoupper($student->special_category ?? '—'),
                    'Religion'         => ucfirst($student->religion ?? '—'),
                    'Nationality'      => ucfirst($student->nationality ?? '—'),
                    'Marital Status'   => ucfirst($student->marital_status ?? '—'),
                    'Student Type'     => ucfirst($student->student_type ?? '—'),
                    'Aadhar No.'       => $student->aadhar_no ?? '—',
                    'APAAR No.'        => $student->apaar_no ?? '—',
                ] as $label => $value)
                <div class="d-flex border-bottom px-3 py-2" style="font-size:13px;">
                    <div class="text-muted" style="width:140px;flex-shrink:0;">{{ $label }}</div>
                    <div class="fw-semibold">{{ $value }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Parent Details --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2" style="background:#1e293b;color:white;">
                <span class="fw-bold small"><i class="bi bi-people me-2"></i>Parent / Guardian</span>
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
                    'Guardian Relation' => $student->guardian_relation ?? '—',
                ] as $label => $value)
                <div class="d-flex border-bottom px-3 py-2" style="font-size:13px;">
                    <div class="text-muted" style="width:140px;flex-shrink:0;">{{ $label }}</div>
                    <div class="fw-semibold">{{ $value }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Address --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2" style="background:#1e293b;color:white;">
                <span class="fw-bold small"><i class="bi bi-geo-alt me-2"></i>Address</span>
            </div>
            <div class="card-body p-0">
                @foreach([
                    'Village / City'  => $student->perm_village ?? '—',
                    'Post'            => $student->perm_post ?? '—',
                    'Thana'           => $student->perm_thana ?? '—',
                    'District'        => $student->perm_district ?? '—',
                    'State'           => $student->perm_state ?? '—',
                    'Pin Code'        => $student->perm_pincode ?? '—',
                    'Comm. Address'   => $student->comm_same_as_perm ? 'Same as above' : ($student->comm_address ?? '—'),
                ] as $label => $value)
                <div class="d-flex border-bottom px-3 py-2" style="font-size:13px;">
                    <div class="text-muted" style="width:140px;flex-shrink:0;">{{ $label }}</div>
                    <div class="fw-semibold">{{ $value }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Academic / Office --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2" style="background:#1e293b;color:white;">
                <span class="fw-bold small"><i class="bi bi-briefcase me-2"></i>Academic Details</span>
            </div>
            <div class="card-body p-0">
                @php
                    $admSourceDisplay = ucwords(str_replace('_', ' ', $student->admission_source ?? 'direct'));
                    if ($srcName) $admSourceDisplay .= ' — ' . $srcName;
                    $uidParts = explode('/', (string) $student->student_uid);
                    $serialNo = end($uidParts) ?: '—';
                @endphp
                @foreach([
                    'Serial No.'       => $serialNo,
                    'Form No.'         => $student->institute_form_no ?? '—',
                    'SR No.'           => $student->sr_no ?? '—',
                    'Enrollment No.'   => $student->enrollment_no ?? '—',
                    'Roll No.'         => $student->roll_no ?? '—',
                    'Exam Form No.'    => $student->exam_form_no ?? '—',
                    'UIN No.'          => $student->uin_no ?? '—',
                    'Reference No.'    => $student->reference_no ?? '—',
                    'Admission Type'   => ucfirst($student->admission_type ?? 'new'),
                    'Admission Source' => $admSourceDisplay,
                    'Gap Year'         => $student->gap_year ? 'Yes' : 'No',
                ] as $label => $value)
                <div class="d-flex border-bottom px-3 py-2" style="font-size:13px;">
                    <div class="text-muted" style="width:140px;flex-shrink:0;">{{ $label }}</div>
                    <div class="fw-semibold">{{ $value }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Scholarship (if applicable) --}}
        @if($student->has_scholarship)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2" style="background:#1e293b;color:white;">
                <span class="fw-bold small"><i class="bi bi-award me-2"></i>Scholarship</span>
            </div>
            <div class="card-body p-0">
                @foreach([
                    'Name'         => $student->scholarship_name ?? '—',
                    'Type'         => ucfirst($student->scholarship_type ?? '—'),
                    'Authority'    => $student->scholarship_authority ?? '—',
                    'Amount'       => $student->scholarship_amount ? '₹ '.number_format($student->scholarship_amount) : '—',
                    'Ref No.'      => $student->scholarship_ref_no ?? '—',
                    'Applied Date' => $student->scholarship_applied_date?->format('d M Y') ?? '—',
                ] as $label => $value)
                <div class="d-flex border-bottom px-3 py-2" style="font-size:13px;">
                    <div class="text-muted" style="width:140px;flex-shrink:0;">{{ $label }}</div>
                    <div class="fw-semibold">{{ $value }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>

    {{-- Right column --}}
    <div class="col-md-7">

        {{-- Documents --}}
        @include('institute.admission._documents-verify', [
            'student'   => $student,
            'canVerify' => false,
            'canUpload' => $authUser->canManageAdmissions(),
            'canDelete' => false,
        ])

        {{-- Education Details --}}
        @if($student->educationDetails && $student->educationDetails->count() > 0)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2" style="background:#1e293b;color:white;">
                <span class="fw-bold small"><i class="bi bi-mortarboard me-2"></i>Education Details</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:12px;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Exam</th>
                            <th>Institute</th>
                            <th>Year</th>
                            <th class="text-end pe-3">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($student->educationDetails as $edu)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $edu->exam_name }}</td>
                            <td class="small">{{ $edu->institute_name ?? '—' }}</td>
                            <td class="small">{{ $edu->passing_year ?? '—' }}</td>
                            <td class="text-end pe-3 small">{{ $edu->percentage ? $edu->percentage.'%' : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Fee Section with session filter --}}
        @if($canCollectThisStudent)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background:#1e293b;color:white;">
                <span class="fw-bold small"><i class="bi bi-cash-stack me-2"></i>Fee History</span>
                <a href="{{ route('center.fee.create') }}?student_id={{ $student->id }}"
                   class="btn btn-success btn-sm py-0 px-2" style="font-size:12px;">
                    <i class="bi bi-plus-lg me-1"></i>Collect Fee
                </a>
            </div>
            <div class="card-body p-3">

                {{-- Session filter tabs --}}
                @if($allowedSessions->count() > 1)
                <div class="d-flex gap-2 flex-wrap mb-3" id="sessionTabs">
                    @foreach($allowedSessions as $sess)
                    <button type="button"
                        class="btn btn-sm session-tab-btn {{ $sess->id == $activeSessionId ? 'btn-primary' : 'btn-outline-secondary' }}"
                        data-session="{{ $sess->id }}"
                        style="font-size:12px;">
                        {{ $sess->name }}
                    </button>
                    @endforeach
                </div>
                @endif

                {{-- Session panels --}}
                @foreach($allowedSessions as $sess)
                @php
                    $sessInvoices = $feeBySession->get($sess->id, collect());
                    $totalPaid    = $sessInvoices->sum('paid_amount');
                    $totalDisc    = $sessInvoices->sum('discount');
                @endphp
                <div class="session-fee-panel" data-session="{{ $sess->id }}"
                     style="{{ $sess->id == $activeSessionId ? '' : 'display:none;' }}">

                    {{-- Summary chips --}}
                    <div class="d-flex gap-3 mb-3 flex-wrap">
                        <div class="px-3 py-2 rounded-3 text-center" style="background:#f0fdf4;border:1px solid #bbf7d0;min-width:120px;">
                            <div class="small text-muted">Total Paid</div>
                            <div class="fw-bold text-success fs-6">₹ {{ number_format($totalPaid) }}</div>
                        </div>
                        @if($totalDisc > 0)
                        <div class="px-3 py-2 rounded-3 text-center" style="background:#fffbeb;border:1px solid #fde68a;min-width:120px;">
                            <div class="small text-muted">Discount</div>
                            <div class="fw-bold text-warning fs-6">₹ {{ number_format($totalDisc) }}</div>
                        </div>
                        @endif
                        <div class="px-3 py-2 rounded-3 text-center" style="background:#eff6ff;border:1px solid #bfdbfe;min-width:120px;">
                            <div class="small text-muted">Receipts</div>
                            <div class="fw-bold text-primary fs-6">{{ $sessInvoices->count() }}</div>
                        </div>
                    </div>

                    @if($sessInvoices->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" style="font-size:12px;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-2">#</th>
                                    <th>Invoice No.</th>
                                    <th>Date</th>
                                    <th>Mode</th>
                                    <th class="text-end pe-2">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sessInvoices as $inv)
                                <tr>
                                    <td class="ps-2 text-muted">{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ $inv->invoice_no ?? '—' }}</td>
                                    <td>{{ $inv->payment_date?->format('d M Y') ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border" style="font-size:10px;">
                                            {{ strtoupper($inv->payment_mode ?? '—') }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-2 fw-semibold text-success">
                                        ₹ {{ number_format($inv->paid_amount) }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-3 text-muted small">
                        <i class="bi bi-receipt fs-4 d-block mb-1 opacity-50"></i>
                        Is session mein koi fee collect nahi hui hai.
                    </div>
                    @endif

                </div>
                @endforeach

            </div>
        </div>
        @endif

    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.session-tab-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var sessId = this.dataset.session;
        document.querySelectorAll('.session-tab-btn').forEach(function(b) {
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-secondary');
        });
        this.classList.remove('btn-outline-secondary');
        this.classList.add('btn-primary');
        document.querySelectorAll('.session-fee-panel').forEach(function(p) {
            p.style.display = p.dataset.session === sessId ? '' : 'none';
        });
    });
});
</script>
@endpush

@endsection
