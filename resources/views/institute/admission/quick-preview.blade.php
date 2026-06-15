@php
    $admissionLayout = auth()->guard('staff')->check()
        ? 'staff.layout'
        : (auth()->guard('center')->check()
            ? 'center.layout'
            : (auth()->guard('partner')->check() ? 'partner.layout' : 'institute.layout'));
    $admissionRoutePrefix = auth()->guard('staff')->check()
        ? 'staff.admissions'
        : (auth()->guard('center')->check()
            ? 'center.admissions'
            : (auth()->guard('partner')->check() ? 'partner.admissions' : 'admissions'));
@endphp
@extends($admissionLayout)
@section('title', 'Quick Registration — Preview')
@section('breadcrumb', 'Admissions / Quick Preview')

@section('content')

{{-- Progress Steps --}}
<div class="mb-4">
    <div class="d-flex align-items-center gap-2 small">
        <span class="badge bg-success px-3 py-2">✓ 1 Form Filled</span>
        <div style="height:2px;width:40px;background:#16a34a;"></div>
        <span class="badge bg-primary px-3 py-2">2 Preview</span>
        <div style="height:2px;width:40px;background:#cbd5e1;"></div>
        <span class="badge bg-secondary px-3 py-2">3 Fee Payment</span>
        <div style="height:2px;width:40px;background:#cbd5e1;"></div>
        <span class="badge bg-secondary px-3 py-2">4 Receipt</span>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-eye me-2 text-primary"></i>Preview & Verify</h4>
        <small class="text-muted">Review all details — then save to complete admission</small>
    </div>
    <a href="{{ route($admissionRoutePrefix . '.quick-edit-preview') }}" class="btn btn-outline-warning btn-sm">
        <i class="bi bi-pencil me-1"></i> Edit Form
    </a>
</div>

{{-- Course Details --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b;color:white;">
        <span class="fw-bold small"><i class="bi bi-book me-2"></i>Course Details</span>
    </div>
    <div class="card-body">
        <div class="row g-3" style="font-size:14px;">
            <div class="col-md-3">
                <div class="text-muted small">Course</div>
                <div class="fw-semibold">{{ $stream->course->name ?? '—' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Stream / Major</div>
                <div class="fw-semibold">{{ $stream->name ?? '—' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Year / Part</div>
                <div class="fw-semibold">{{ $part?->year_label ?? '1st Year' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Session</div>
                <div class="fw-semibold">{{ $activeSession->name }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Student Type</div>
                <div class="fw-semibold">{{ ucfirst($formData['student_type'] ?? 'Regular') }}</div>
            </div>
            @if(($formConfig['admission_source']['enabled'] ?? false) && ($formConfig['admission_source']['section_enabled'] ?? true))
            <div class="col-md-3">
                <div class="text-muted small">Admission Source</div>
                @php
                    $fSrc = $formData['admission_source'] ?? 'direct';
                    $fSrcId = $formData['admission_source_id'] ?? null;
                    $fSrcName = '';
                    if ($fSrc === 'center' && $fSrcId) $fSrcName = \App\Models\Center::find($fSrcId)?->name ?? '';
                    elseif ($fSrc === 'channel_partner' && $fSrcId) $fSrcName = \App\Models\ChannelPartner::find($fSrcId)?->name ?? '';
                    $fSrcDisplay = ucfirst(str_replace('_', ' ', $fSrc)) . ($fSrcName ? ' — ' . $fSrcName : '');
                @endphp
                <div class="fw-semibold">{{ $fSrcDisplay }}</div>
            </div>
            @endif
            @if(($formConfig['admission_type']['enabled'] ?? false) && ($formConfig['admission_type']['section_enabled'] ?? true))
            <div class="col-md-3">
                <div class="text-muted small">Admission Type</div>
                <div class="fw-semibold">{{ ucfirst(str_replace('_',' ', $formData['admission_type'] ?? 'new')) }}</div>
            </div>
            @endif
            @if(($formConfig['admission_date']['enabled'] ?? false) && ($formConfig['admission_date']['section_enabled'] ?? true))
            <div class="col-md-3">
                <div class="text-muted small">Admission Date</div>
                <div class="fw-semibold">{{ \Carbon\Carbon::parse($formData['admission_date'] ?? now())->format('d M Y') }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Selected Subjects --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#0f4c81;color:white;">
        <span class="fw-bold small"><i class="bi bi-list-check me-2"></i>Selected Subjects</span>
    </div>
    <div class="card-body py-3">
        @php
            $selectedCompulsoryIds = $compulsorySubjects->pluck('subject_id')->map(fn($id) => (int) $id)->all();
        @endphp
        @if($selectedSubjects->isNotEmpty())
        <div class="d-flex flex-wrap gap-2">
            @foreach($selectedSubjects as $sub)
                <span class="badge {{ in_array((int) $sub->id, $selectedCompulsoryIds, true) ? 'bg-success' : 'bg-primary' }} px-3 py-2" style="font-size:12px;">
                    {{ $sub->name }}
                    @if($sub->code) ({{ $sub->code }}) @endif
                    @if(in_array((int) $sub->id, $selectedCompulsoryIds, true))
                        <span class="ms-1 opacity-75" style="font-size:10px;">Compulsory</span>
                    @endif
                </span>
            @endforeach
        </div>
        @else
        <div class="small text-muted">
            No subjects have been selected. Fee will be calculated on course-level items only. Subjects can be added later from the student edit page.
        </div>
        @endif
    </div>
</div>

{{-- Personal Details --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b;color:white;">
        <span class="fw-bold small"><i class="bi bi-person me-2"></i>Personal Details</span>
    </div>
    <div class="card-body">
        <div class="row g-3" style="font-size:14px;">
            <div class="col-md-3"><div class="text-muted small">Student Name</div><div class="fw-semibold">{{ $formData['name'] }}</div></div>
            <div class="col-md-3"><div class="text-muted small">Mobile</div><div class="fw-semibold">{{ $formData['mobile'] }}</div></div>
            @if(($formConfig['email']['enabled'] ?? false) && !empty($formData['email']))
            <div class="col-md-3"><div class="text-muted small">Email</div><div class="fw-semibold">{{ $formData['email'] }}</div></div>
            @endif
            @if(($formConfig['dob']['enabled'] ?? false) && !empty($formData['dob']))
            <div class="col-md-3"><div class="text-muted small">Date of Birth</div><div class="fw-semibold">{{ \Carbon\Carbon::parse($formData['dob'])->format('d M Y') }}</div></div>
            @endif
            @if(($formConfig['gender']['enabled'] ?? false) && !empty($formData['gender']))
            <div class="col-md-3"><div class="text-muted small">Gender</div><div class="fw-semibold">{{ ucfirst($formData['gender']) }}</div></div>
            @endif
            @if(($formConfig['father_name']['enabled'] ?? false) && !empty($formData['father_name']))
            <div class="col-md-3"><div class="text-muted small">Father Name</div><div class="fw-semibold">{{ $formData['father_name'] }}</div></div>
            @endif
            @if(($formConfig['mother_name']['enabled'] ?? false) && !empty($formData['mother_name']))
            <div class="col-md-3"><div class="text-muted small">Mother Name</div><div class="fw-semibold">{{ $formData['mother_name'] }}</div></div>
            @endif
            @if(($formConfig['category']['enabled'] ?? false) && !empty($formData['category']))
            <div class="col-md-3"><div class="text-muted small">Category</div><div class="fw-semibold">{{ strtoupper($formData['category']) }}</div></div>
            @endif
            @if(($formConfig['aadhar_no']['enabled'] ?? false) && !empty($formData['aadhar_no']))
            <div class="col-md-3"><div class="text-muted small">Aadhar No.</div><div class="fw-semibold">{{ $formData['aadhar_no'] }}</div></div>
            @endif
        </div>
    </div>
</div>

{{-- Education Details --}}
@php
    $eduFields = ['q_edu_10th','q_edu_12th','q_edu_graduation','q_edu_other'];
    $eduSectionEnabled = $formConfig['q_edu_10th']['section_enabled'] ?? false;
    $showEduPreview = $eduSectionEnabled &&
        collect($eduFields)->contains(fn($k) => $formConfig[$k]['enabled'] ?? false);

    $hasEducation = false;
    foreach ($formData['education'] ?? [] as $edu) {
        if (!empty($edu['institute_name']) || !empty($edu['passing_year']) || !empty($edu['obtained_marks'])) {
            $hasEducation = true; break;
        }
    }
@endphp
@if($showEduPreview)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b;color:white;">
        <span class="fw-bold small"><i class="bi bi-mortarboard me-2"></i>Academic / Education Details</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">EXAM</th>
                    <th>STREAM</th>
                    <th>Institute Name</th>
                    <th>Roll No.</th>
                    <th>Year</th>
                    <th>Division</th>
                    <th>Board/University</th>
                    <th class="text-end">Marks</th>
                    <th class="text-end">Max</th>
                    <th class="text-end pe-3">%</th>
                </tr>
            </thead>
            <tbody>
                @foreach($formData['education'] ?? [] as $edu)
                @if(!empty($edu['institute_name']) || !empty($edu['passing_year']) || !empty($edu['obtained_marks']))
                <tr>
                    <td class="ps-3 fw-semibold small text-primary">{{ $edu['exam_name'] ?? '—' }}</td>
                    <td class="small">{{ $edu['institute_name'] ?? '—' }}</td>
                    <td class="small">{{ $edu['roll_number'] ?? '—' }}</td>
                    <td class="small">{{ $edu['passing_year'] ?? '—' }}</td>
                    <td class="small">{{ $edu['division'] ?? '—' }}</td>
                    <td class="small">{{ $edu['board_university'] ?? '—' }}</td>
                    <td class="text-end small">{{ $edu['obtained_marks'] ?? '—' }}</td>
                    <td class="text-end small">{{ $edu['max_marks'] ?? '—' }}</td>
                    <td class="text-end pe-3 small fw-semibold">{{ $edu['percentage'] ?? '—' }}</td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Fee Preview --}}
@if(!empty($feeData['items']))
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header py-2" style="background:#166534;color:white;">
        <span class="fw-bold small"><i class="bi bi-cash-stack me-2"></i>Fee Breakup</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <tbody>
                @foreach($feeData['items'] as $item)
                <tr>
                    <td class="ps-3 small">{{ $item['label'] }}</td>
                    <td class="text-end pe-3 fw-semibold text-danger small">₹ {{ number_format($item['amount'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-dark">
                <tr>
                    <td class="ps-3 fw-bold">Total Fee</td>
                    <td class="text-end pe-3 fw-bold">₹ {{ number_format($feeData['total'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- Action Buttons --}}
<form method="POST" action="{{ route($admissionRoutePrefix . '.quick-confirm') }}">
@csrf
<div class="d-flex gap-3 mb-5">
    <button type="submit" class="btn btn-success btn-lg px-5 fw-bold">
        <i class="bi bi-check-lg me-2"></i> Save & Proceed to Fee Payment
    </button>
    <a href="{{ route($admissionRoutePrefix . '.quick-edit-preview') }}" class="btn btn-outline-warning btn-lg px-4">
        <i class="bi bi-pencil me-1"></i> Edit Form
    </a>
</div>
</form>

@endsection
