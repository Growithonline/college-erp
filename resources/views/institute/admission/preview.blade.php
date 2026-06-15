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
@section('title', 'Admission Preview')
@section('breadcrumb', 'Admissions / Preview')

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-eye me-2 text-primary"></i>Form Preview</h4>
        <small class="text-muted">Review all details, then confirm to proceed</small>
    </div>
    <a href="{{ route($admissionRoutePrefix . '.edit-preview') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-pencil me-1"></i> Edit Form
    </a>
</div>

{{-- Progress bar --}}
<div class="mb-4">
    <div class="d-flex align-items-center gap-2 small text-muted">
        <span class="badge bg-success">✓ Form Filled</span>
        <div style="height:2px;width:40px;background:#16a34a;"></div>
        <span class="badge bg-primary">2 Preview</span>
        <div style="height:2px;width:40px;background:#cbd5e1;"></div>
        <span class="badge bg-secondary">3 Fee Payment</span>
        <div style="height:2px;width:40px;background:#cbd5e1;"></div>
        <span class="badge bg-secondary">4 Print</span>
    </div>
</div>

{{-- Course Details --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b;color:white;">
        <span class="fw-bold small"><i class="bi bi-book me-2"></i>Course Details</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="small text-muted">Course</div>
                <div class="fw-semibold">{{ $stream->course->name ?? '—' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Stream / Major</div>
                <div class="fw-semibold">{{ $stream->name ?? '—' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Year / Part</div>
                <div class="fw-semibold">{{ $part?->year_label ?? '—' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Session</div>
                <div class="fw-semibold">{{ $activeSession->name }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Student Type</div>
                <div class="fw-semibold">{{ ucfirst($formData['student_type'] ?? 'regular') }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Admission Source</div>
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
        </div>
    </div>
</div>

{{-- Office Details --}}
@php
    $allOfficeFields = [
        'form_no'          => ['label' => 'Serial No.',       'default' => true],
        'institute_form_no'=> ['label' => 'Form No.',         'default' => false],
        'sr_no'            => ['label' => 'SR No.',           'default' => true],
        'enrollment_no'    => ['label' => 'Enrollment No.',   'default' => false],
        'roll_no'          => ['label' => 'Roll No.',         'default' => false],
        'exam_form_no'     => ['label' => 'Exam Form No.',    'default' => false],
        'uin_no'           => ['label' => 'UIN No.',          'default' => false],
        'reference_no'     => ['label' => 'Reference No.',    'default' => false],
        'admission_type'   => ['label' => 'Admission Type',   'default' => true],
        'admission_source' => ['label' => 'Admission Source', 'default' => true],
        'gap_year'         => ['label' => 'Gap Year',         'default' => true],
        'admission_date'   => ['label' => 'Admission Date',   'default' => true],
        'submitted_date'   => ['label' => 'Submitted Date',   'default' => true],
        'academic_session' => ['label' => 'Academic Session', 'default' => true],
    ];
    $enabledOfficeFields = array_filter($allOfficeFields, fn($f, $k) =>
        ($formConfig[$k]['enabled'] ?? $f['default']) && ($formConfig[$k]['section_enabled'] ?? true),
    ARRAY_FILTER_USE_BOTH);
@endphp
@if(count($enabledOfficeFields))
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b;color:white;">
        <span class="fw-bold small"><i class="bi bi-briefcase me-2"></i>Office Details</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach($enabledOfficeFields as $key => $field)
            <div class="col-md-3">
                <div class="small text-muted">{{ $field['label'] }}</div>
                <div class="fw-semibold">
                    @if($key === 'form_no')
                        Auto
                    @elseif($key === 'gap_year')
                        {{ ($formData['gap_year'] ?? false) ? 'Yes' : 'No' }}
                    @elseif($key === 'academic_session')
                        {{ $activeSession->name }}
                    @elseif($key === 'admission_type')
                        {{ ucfirst(str_replace('_', ' ', $formData['admission_type'] ?? 'new')) }}
                    @elseif($key === 'admission_source')
                        {{ $fSrcDisplay }}
                    @elseif(in_array($key, ['admission_date', 'submitted_date']) && !empty($formData[$key]))
                        {{ \Carbon\Carbon::parse($formData[$key])->format('d M Y') }}
                    @else
                        {{ $formData[$key] ?? '—' }}
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Transport --}}
@if(!empty($formData['transport_use']))
@php
    $transportRoute = $transportRoutes->firstWhere('id', (int) ($formData['transport_route_id'] ?? 0));
    $transportStop = $transportStops->firstWhere('id', (int) ($formData['transport_route_stop_id'] ?? 0));
    $transportVehicle = $transportVehicles->firstWhere('id', (int) ($formData['transport_vehicle_id'] ?? 0));
    $transportDriver = $transportDrivers->firstWhere('id', (int) ($formData['transport_driver_id'] ?? 0));
@endphp
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#334155;color:white;">
        <span class="fw-bold small"><i class="bi bi-bus-front me-2"></i>Transport Allocation</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="small text-muted">Route</div>
                <div class="fw-semibold">{{ $transportRoute->name ?? '—' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Stop</div>
                <div class="fw-semibold">{{ $transportStop->stop_name ?? '—' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Vehicle</div>
                <div class="fw-semibold">{{ $transportVehicle->vehicle_no ?? '—' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Driver</div>
                <div class="fw-semibold">{{ $transportDriver->name ?? '—' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Transport Fee</div>
                <div class="fw-semibold">₹{{ number_format((float) ($formData['transport_fee_amount'] ?? $transportRoute?->fee_amount ?? 0), 2) }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Start Date</div>
                <div class="fw-semibold">{{ !empty($formData['transport_start_date']) ? \Carbon\Carbon::parse($formData['transport_start_date'])->format('d M Y') : '—' }}</div>
            </div>
            <div class="col-12">
                <div class="small text-muted">Remarks</div>
                <div class="fw-semibold">{{ $formData['transport_remarks'] ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Subjects --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#0f4c81;color:white;">
        <span class="fw-bold small"><i class="bi bi-list-check me-2"></i>Selected Subjects</span>
    </div>
    <div class="card-body">
        @if($compulsorySubjects->isNotEmpty())
        <div class="mb-2">
            <span class="small text-muted fw-semibold">Compulsory (Auto):</span>
            @foreach($compulsorySubjects as $cs)
            <span class="badge bg-success ms-1">{{ $cs->subject->name ?? '' }}</span>
            @endforeach
        </div>
        @endif
        @if($selectedSubjects->isNotEmpty())
        <div>
            <span class="small text-muted fw-semibold">Selected:</span>
            @foreach($selectedSubjects as $s)
            <span class="badge bg-primary ms-1">{{ $s->name }} @if($s->code)({{ $s->code }})@endif</span>
            @endforeach
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
        <div class="d-flex gap-4">
            {{-- Photo --}}
            @if(!empty($formData['photo_temp']))
            <div class="flex-shrink-0">
                <img src="{{ \Illuminate\Support\Facades\Storage::url($formData['photo_temp']) }}"
                     style="width:90px;height:110px;object-fit:cover;border-radius:8px;border:2px solid #e2e8f0;"
                     onerror="this.style.display='none'">
            </div>
            @endif
            <div class="flex-grow-1">
                <div class="row g-3">
                    @php
                        $personalFields = [
                            'name'              => 'Student Name',
                            'mobile'            => 'Mobile',
                            'email'             => 'Email',
                            'dob'               => 'Date of Birth',
                            'gender'            => 'Gender',
                            'category'          => 'Category',
                            'special_category'  => 'Special Category',
                            'religion'          => 'Religion',
                            'nationality'       => 'Nationality',
                            'aadhar_no'         => 'Aadhar No',
                            'apaar_no'          => 'APAAR No',
                            'student_type'      => 'Student Type',
                            'marital_status'    => 'Marital Status',
                            'father_name'       => 'Father Name',
                            'father_mobile'     => 'Father Mobile',
                            'mother_name'       => 'Mother Name',
                            'guardian_mobile'   => 'Guardian Mobile',
                            'guardian_name'     => 'Guardian Name',
                            'guardian_relation' => 'Guardian Relation',
                        ];
                    @endphp
                    @foreach($personalFields as $key => $label)
                    @if(($formConfig[$key]['enabled'] ?? false) && ($formConfig[$key]['section_enabled'] ?? true))
                    <div class="col-md-3">
                        <div class="small text-muted">{{ $label }}</div>
                        <div class="fw-semibold">
                            @if($key === 'dob' && !empty($formData[$key]))
                                {{ \Carbon\Carbon::parse($formData[$key])->format('d M Y') }}
                            @elseif(!empty($formData[$key]))
                                {{ ucfirst(str_replace('_', ' ', $formData[$key])) }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>

                {{-- Scholarship --}}
                @if(!empty($formData['has_scholarship']) && $formData['has_scholarship'])
                <div class="mt-3 p-2 border rounded bg-light">
                    <div class="small fw-semibold text-warning mb-1"><i class="bi bi-award me-1"></i>Scholarship</div>
                    <div class="row g-2">
                        @foreach(['scholarship_name'=>'Name','scholarship_type'=>'Type','scholarship_authority'=>'Authority','scholarship_amount'=>'Amount (₹)','scholarship_ref_no'=>'Ref No.'] as $sk => $sl)
                        <div class="col-md-3"><div class="small text-muted">{{ $sl }}</div><div class="fw-semibold small">{{ $formData[$sk] ?? '—' }}</div></div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Address --}}
@php
    $permFields = ['perm_village'=>'Village/City','perm_post'=>'Post','perm_thana'=>'Thana','perm_district'=>'District','perm_state'=>'State','perm_pincode'=>'Pin Code'];
    $hasPermAddr = collect($permFields)->keys()->contains(fn($k) =>
        ($formConfig[$k]['enabled'] ?? false) && ($formConfig[$k]['section_enabled'] ?? true));
    $commAddrEnabled = ($formConfig['comm_address']['enabled'] ?? false) && ($formConfig['comm_address']['section_enabled'] ?? true);
    $hasAddress = $hasPermAddr || $commAddrEnabled;
@endphp
@if($hasAddress)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b;color:white;">
        <span class="fw-bold small"><i class="bi bi-geo-alt me-2"></i>Address Details</span>
    </div>
    <div class="card-body">
        {{-- Permanent Address --}}
        @if($hasPermAddr)
        <div class="mb-3">
            <div class="small fw-semibold text-muted mb-2">Permanent Address</div>
            <div class="row g-2">
                @foreach($permFields as $k => $l)
                @if(($formConfig[$k]['enabled'] ?? false) && ($formConfig[$k]['section_enabled'] ?? true))
                <div class="col-md-2">
                    <div class="small text-muted">{{ $l }}</div>
                    <div class="fw-semibold">{{ $formData[$k] ?? '—' }}</div>
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @endif

        {{-- Communication Address --}}
        @if(!empty($formData['comm_same_as_perm']) && $formData['comm_same_as_perm'])
        <div class="small text-muted fst-italic">
            <i class="bi bi-check-circle text-success me-1"></i>Communication address — same as permanent
        </div>
        @elseif($commAddrEnabled)
        <div>
            <div class="small fw-semibold text-muted mb-1">Communication Address</div>
            <div class="fw-semibold">{{ $formData['comm_address'] ?? '—' }}</div>
        </div>
        @endif
    </div>
</div>
@endif

{{-- Education --}}
@if(!empty($formData['education']))
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2" style="background:#1e293b;color:white;">
        <span class="fw-bold small"><i class="bi bi-mortarboard me-2"></i>Education</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr><th>EXAM</th><th>STREAM</th><th>Institute</th><th>Year</th><th>Division</th><th>%</th></tr>
            </thead>
            <tbody>
                @foreach($formData['education'] as $edu)
                @if(!empty($edu['exam_name']))
                <tr>
                    <td class="fw-semibold">{{ $edu['exam_name'] }}</td>
                    <td>{{ $edu['institute_name'] ?? '—' }}</td>
                    <td>{{ $edu['passing_year'] ?? '—' }}</td>
                    <td>{{ $edu['division'] ?? '—' }}</td>
                    <td>{{ $edu['percentage'] ?? '—' }}</td>
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
        <span class="fw-bold small"><i class="bi bi-receipt me-2"></i>Estimated Fee</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <tbody>
                @foreach($feeData['items'] as $item)
                <tr>
                    <td class="small">{{ $item['label'] }}</td>
                    <td class="text-end fw-semibold">₹ {{ number_format($item['amount'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-dark">
                <tr>
                    <td class="fw-bold">Total</td>
                    <td class="text-end fw-bold">₹ {{ number_format($feeData['total'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- Action Buttons --}}
<div class="card border-0 shadow-sm mb-5">
    <div class="card-body d-flex gap-3 align-items-center flex-wrap">

        {{-- Confirm & Submit --}}
        <form method="POST" action="{{ route($admissionRoutePrefix . '.confirm') }}">
            @csrf
            <button type="submit" class="btn btn-success btn-lg px-5">
                <i class="bi bi-check-lg me-2"></i>Confirm & Submit Admission
            </button>
        </form>

        {{-- Edit Form --}}
        <a href="{{ route($admissionRoutePrefix . '.edit-preview') }}" class="btn btn-outline-primary px-4">
            <i class="bi bi-pencil me-1"></i> Edit Form
        </a>

        <span class="text-muted small">
            <i class="bi bi-info-circle me-1"></i>After confirming, you will be taken to the fee payment page
        </span>
    </div>
</div>

@endsection
