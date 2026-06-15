<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admission Form — {{ $student->name }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 11px; background:#f5f5f5; }

.page-wrapper { max-width: 210mm; margin: 20px auto; background:white; padding: 20px; }

/* Screen buttons */
.print-actions { display:flex; gap:8px; margin-bottom:16px; }
.btn { padding:7px 18px; border:none; border-radius:5px; cursor:pointer;
       font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:5px; text-decoration:none; }
.btn-primary  { background:#1d4ed8; color:white; }
.btn-secondary{ background:#64748b; color:white; }

/* Form Header */
.form-header { text-align:center; border-bottom:2px solid #000; padding-bottom:8px; margin-bottom:10px; }
.form-header .inst-name { font-size:18px; font-weight:900; }
.form-header .inst-address { font-size:11px; color:#333; margin-top:2px; }
.form-header .form-title { font-size:13px; font-weight:700; margin-top:5px; }
.form-header .form-class { font-size:12px; font-weight:600; }

/* Section Headers */
.section-head {
    border:1px solid #000;
    display:inline-block;
    padding:2px 10px;
    font-size:10px;
    font-weight:700;
    margin-bottom:6px;
    margin-top:8px;
}

/* Field Rows */
.field-row {
    display:flex;
    flex-wrap:wrap;
    gap:0;
    margin-bottom:4px;
}
.field-item {
    display:flex;
    align-items:flex-end;
    gap:4px;
    margin-right:16px;
    margin-bottom:4px;
}
.field-label { font-size:10px; color:#333; white-space:nowrap; }
.field-label sup { color:red; }
.field-value {
    border-bottom:1px solid #000;
    min-width:80px;
    font-size:11px;
    font-weight:700;
    padding-bottom:1px;
    padding-left:2px;
}
.field-value.wide  { min-width:150px; }
.field-value.xwide { min-width:220px; }
.field-value.sm    { min-width:50px; }

/* Photo Box */
.photo-box {
    width:80px; height:90px;
    border:1px solid #000;
    display:flex; align-items:center; justify-content:center;
    font-size:9px; color:#666; text-align:center;
}

/* Office section - top area */
.office-grid {
    display:grid;
    grid-template-columns:1fr 80px;
    gap:8px;
    border-bottom:1px dashed #ccc;
    padding-bottom:8px;
    margin-bottom:8px;
}
.office-fields { }

/* Education Table */
.edu-table { width:100%; border-collapse:collapse; margin-top:4px; font-size:10px; }
.edu-table th { background:#1e293b; color:white; padding:3px 5px; text-align:left; border:1px solid #000; }
.edu-table td { border:1px solid #ccc; padding:3px 5px; height:20px; }

/* Signature row */
.sign-row { display:flex; justify-content:space-between; margin-top:16px; padding-top:8px; }
.sign-box { text-align:center; }
.sign-line { border-top:1px solid #000; width:100px; margin-bottom:3px; }
.sign-label { font-size:9px; color:#333; }

/* Print */
@media print {
    body { background:white; }
    .page-wrapper { margin:0; padding:10mm; max-width:100%; box-shadow:none; }
    .print-actions { display:none !important; }
}
</style>
</head>
<body>
<div class="page-wrapper">
    @php
        $panel = auth()->guard('staff')->check()
            ? 'staff'
            : (auth()->guard('center')->check()
                ? 'center'
                : (auth()->guard('partner')->check() ? 'partner' : 'institute'));
        $backUrl = $panel === 'center'
            ? route('center.students.show', $student)
            : ($panel === 'partner'
                ? route('partner.students.show', $student)
                : ($panel === 'institute' ? route('admissions.show', $student) : route('staff.admissions.index')));
    @endphp

    {{-- Screen actions --}}
    <div class="print-actions">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Print A4 Form</button>
        <button onclick="openThermalSlip()" class="btn btn-secondary" style="background:#0f766e;">🖨️ Thermal — Admission Slip</button>
        <button onclick="openThermalFee()" class="btn btn-secondary" style="background:#7c3aed;">🖨️ Thermal — Fee Receipt</button>
        <a href="{{ $backUrl }}" class="btn btn-secondary">← Back</a>
    </div>

    @php
        $inst = \App\Models\Institute::find($student->institute_id);
    @endphp

    {{-- ══ HEADER ══ --}}
    <div class="form-header">
        <div class="inst-name">{{ $inst->name ?? 'Institute Name' }}</div>
        <div class="inst-address">{{ $inst->address ?? '' }}</div>
        <div class="form-title">
            प्रवेश आवेदन पत्र (सत्र {{ $student->session->name ?? '2025-26' }})
        </div>
        <div class="form-class">
            Class: {{ $student->stream->course->name ?? '' }}
            @if($student->stream) — {{ $student->stream->name }} @endif
        </div>
    </div>

    {{-- ══ OFFICE + PHOTO ROW ══ --}}
    <div class="office-grid">
        <div class="office-fields">
            <div class="section-head">Official Details</div>
            <div class="field-row">
                @if($formConfig['form_no']['enabled'] ?? true)
                <div class="field-item">
                    <span class="field-label">Form No.<sup>*</sup></span>
                    <span class="field-value sm">{{ $student->currentAcademicIdentity?->form_no ?? $student->id }}</span>
                </div>
                @endif
                @if($formConfig['sr_no']['enabled'] ?? true)
                <div class="field-item">
                    <span class="field-label">Serial No. / SR No.</span>
                    <span class="field-value sm">{{ $student->sr_no }}</span>
                </div>
                @endif
                @if($formConfig['enrollment_no']['enabled'] ?? false)
                <div class="field-item">
                    <span class="field-label">Enrollment No.</span>
                    <span class="field-value">{{ $student->enrollment_no }}</span>
                </div>
                @endif
                @if($formConfig['roll_no']['enabled'] ?? false)
                <div class="field-item">
                    <span class="field-label">Roll No.</span>
                    <span class="field-value sm">{{ $student->roll_no }}</span>
                </div>
                @endif
                @if($formConfig['exam_form_no']['enabled'] ?? false)
                <div class="field-item">
                    <span class="field-label">Exam Form No.</span>
                    <span class="field-value">{{ $student->exam_form_no }}</span>
                </div>
                @endif
                @if($formConfig['uin_no']['enabled'] ?? false)
                <div class="field-item">
                    <span class="field-label">UIN No.</span>
                    <span class="field-value">{{ $student->uin_no }}</span>
                </div>
                @endif
                @if($formConfig['reference_no']['enabled'] ?? false)
                <div class="field-item">
                    <span class="field-label">Reference No.</span>
                    <span class="field-value">{{ $student->reference_no }}</span>
                </div>
                @endif
            </div>
            <div class="field-row">
                @if($formConfig['admission_type']['enabled'] ?? true)
                <div class="field-item">
                    <span class="field-label">Admission Type<sup>*</sup></span>
                    <span class="field-value">{{ ucfirst($student->admission_type ?? 'Regular') }}</span>
                </div>
                @endif
                @if($formConfig['admission_source']['enabled'] ?? false)
                <div class="field-item">
                    <span class="field-label">Admission Source</span>
                    <span class="field-value">
                        {{ ucfirst(str_replace('_', ' ', $student->admission_source ?? 'direct')) }}
                        @if($admissionSourceName) — {{ $admissionSourceName }}@endif
                    </span>
                </div>
                @endif
                @if($formConfig['gap_year']['enabled'] ?? true)
                <div class="field-item">
                    <span class="field-label">Gap Year</span>
                    <span class="field-value sm">{{ $student->gap_year ? 'Yes' : 'No' }}</span>
                </div>
                @endif
                @if($formConfig['admission_date']['enabled'] ?? true)
                <div class="field-item">
                    <span class="field-label">Admission Date<sup>*</sup></span>
                    <span class="field-value">{{ $student->admission_date?->format('d-M-Y') }}</span>
                </div>
                @endif
                @if($formConfig['submitted_date']['enabled'] ?? true)
                <div class="field-item">
                    <span class="field-label">Submitted Date<sup>*</sup></span>
                    <span class="field-value">{{ optional($student->submitted_date ?? $student->created_at)->format('d-M-Y') }}</span>
                </div>
                @endif
                @if($formConfig['academic_session']['enabled'] ?? true)
                <div class="field-item">
                    <span class="field-label">Edu. Session<sup>*</sup></span>
                    <span class="field-value">{{ $student->session->name ?? '' }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Photo --}}
        <div>
            <div class="photo-box">
                @if($student->photo)
                    <img src="{{ public_path('storage/'.$student->photo) }}"
                         style="width:100%;height:100%;object-fit:cover;">
                @else
                    <span>Photo</span>
                @endif
            </div>
        </div>
    </div>

    {{-- ══ PERSONAL DETAILS ══ --}}
    <div class="section-head">Student Personal Details</div>
    <div class="field-row">
        <div class="field-item">
            <span class="field-label">Student Name<sup>*</sup></span>
            <span class="field-value wide">{{ $student->name }}</span>
        </div>
        @if($formConfig['dob']['enabled'] ?? true)
        <div class="field-item">
            <span class="field-label">Date of Birth</span>
            <span class="field-value">{{ $student->dob?->format('d-M-Y') }}</span>
        </div>
        @endif
        @if($formConfig['father_name']['enabled'] ?? true)
        <div class="field-item">
            <span class="field-label">Father Name</span>
            <span class="field-value wide">{{ $student->father_name }}</span>
        </div>
        @endif
        @if($formConfig['mother_name']['enabled'] ?? true)
        <div class="field-item">
            <span class="field-label">Mother Name</span>
            <span class="field-value wide">{{ $student->mother_name }}</span>
        </div>
        @endif
    </div>
    <div class="field-row">
        @if($formConfig['religion']['enabled'] ?? true)
        <div class="field-item">
            <span class="field-label">Religion</span>
            <span class="field-value">{{ ucfirst($student->religion) }}</span>
        </div>
        @endif
        @if($formConfig['category']['enabled'] ?? true)
        <div class="field-item">
            <span class="field-label">Category</span>
            <span class="field-value sm">{{ strtoupper($student->category) }}</span>
        </div>
        @endif
        @if($formConfig['aadhar_no']['enabled'] ?? true)
        <div class="field-item">
            <span class="field-label">Aadhar Card No.</span>
            <span class="field-value wide">{{ $student->aadhar_no }}</span>
        </div>
        @endif
        @if($formConfig['mobile']['enabled'] ?? true)
        <div class="field-item">
            <span class="field-label">Mobile No.<sup>*</sup></span>
            <span class="field-value">{{ $student->mobile }}</span>
        </div>
        @endif
        @if($formConfig['father_mobile']['enabled'] ?? false)
        <div class="field-item">
            <span class="field-label">Father Mobile</span>
            <span class="field-value">{{ $student->father_mobile }}</span>
        </div>
        @endif
        @if($formConfig['email']['enabled'] ?? false)
        <div class="field-item">
            <span class="field-label">Email</span>
            <span class="field-value wide">{{ $student->email }}</span>
        </div>
        @endif
    </div>
    <div class="field-row">
        @if($formConfig['special_category']['enabled'] ?? false)
        <div class="field-item">
            <span class="field-label">Special Category</span>
            <span class="field-value">{{ ucfirst($student->special_category) }}</span>
        </div>
        @endif
        @if($formConfig['nationality']['enabled'] ?? false)
        <div class="field-item">
            <span class="field-label">Nationality</span>
            <span class="field-value">{{ ucfirst($student->nationality) }}</span>
        </div>
        @endif
        @if($formConfig['apaar_no']['enabled'] ?? false)
        <div class="field-item">
            <span class="field-label">APAAR No.</span>
            <span class="field-value wide">{{ $student->apaar_no }}</span>
        </div>
        @endif
    </div>
    <div class="field-row">
        @if($formConfig['guardian_mobile']['enabled'] ?? false)
        <div class="field-item">
            <span class="field-label">Guardian Mobile</span>
            <span class="field-value">{{ $student->guardian_mobile }}</span>
        </div>
        @endif
        @if($formConfig['marital_status']['enabled'] ?? false)
        <div class="field-item">
            <span class="field-label">Marital Status</span>
            <span class="field-value">{{ ucfirst($student->marital_status) }}</span>
        </div>
        @endif
        @if($formConfig['gender']['enabled'] ?? true)
        <div class="field-item">
            <span class="field-label">Gender</span>
            <span class="field-value">{{ ucfirst($student->gender) }}</span>
        </div>
        @endif
        @if($formConfig['student_type']['enabled'] ?? false)
        <div class="field-item">
            <span class="field-label">Student Type</span>
            <span class="field-value">{{ ucfirst($student->student_type) }}</span>
        </div>
        @endif
    </div>

    {{-- ══ ADDRESS ══ --}}
    @if(($formConfig['perm_village']['enabled'] ?? true) || ($formConfig['perm_district']['enabled'] ?? true))
    <div class="section-head">Student Address Details</div>
    <div class="field-row">
        @if($formConfig['perm_village']['enabled'] ?? true)
        <div class="field-item">
            <span class="field-label">Village</span>
            <span class="field-value wide">{{ $student->perm_village ?? $student->perm_city }}</span>
        </div>
        @endif
        @if($formConfig['perm_post']['enabled'] ?? true)
        <div class="field-item">
            <span class="field-label">Post</span>
            <span class="field-value">{{ $student->perm_post }}</span>
        </div>
        @endif
        @if($formConfig['perm_thana']['enabled'] ?? false)
        <div class="field-item">
            <span class="field-label">Thana</span>
            <span class="field-value">{{ $student->perm_thana }}</span>
        </div>
        @endif
        @if($formConfig['perm_district']['enabled'] ?? true)
        <div class="field-item">
            <span class="field-label">District</span>
            <span class="field-value">{{ $student->perm_district }}</span>
        </div>
        @endif
        @if($formConfig['perm_state']['enabled'] ?? true)
        <div class="field-item">
            <span class="field-label">State</span>
            <span class="field-value">{{ $student->perm_state }}</span>
        </div>
        @endif
        @if($formConfig['perm_pincode']['enabled'] ?? true)
        <div class="field-item">
            <span class="field-label">Pin Code</span>
            <span class="field-value sm">{{ $student->perm_pincode }}</span>
        </div>
        @endif
        @if($formConfig['comm_address']['enabled'] ?? false)
        <div class="field-item">
            <span class="field-label">Communication Address</span>
            <span class="field-value wide">{{ $student->comm_address }}</span>
        </div>
        @endif
    </div>
    @endif

    {{-- ══ EDUCATION ══ --}}
    @if($student->educationDetails->count())
    <div class="section-head">Passed Exam Details</div>
    <table class="edu-table">
        <thead>
            <tr>
                <th>Exam Name</th>
                <th>Stream</th>
                <th>Institute Name</th>
                <th>Board/University Name</th>
                <th>Roll Number</th>
                <th>Passing Year</th>
                <th>District</th>
                <th>Division</th>
                <th>Obtained Marks</th>
                <th>Max. Marks</th>
                <th>%</th>
            </tr>
        </thead>
        <tbody>
            @foreach($student->educationDetails as $edu)
            <tr>
                <td><strong>{{ $edu->exam_name }}</strong></td>
                <td>{{ $edu->education_stream ?? '—' }}</td>
                <td>{{ $edu->institute_name }}</td>
                <td>{{ $edu->board_university }}</td>
                <td>{{ $edu->roll_number }}</td>
                <td>{{ $edu->passing_year }}</td>
                <td>{{ $edu->district ?? '—' }}</td>
                <td>{{ $edu->division }}</td>
                <td>{{ $edu->obtained_marks }}</td>
                <td>{{ $edu->max_marks }}</td>
                <td>{{ $edu->percentage }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- ══ SIGNATURES ══ --}}
    <div class="sign-row">
        <div class="sign-box">
            <div class="sign-line"></div>
            <div class="sign-label">Student Signature</div>
        </div>
        <div class="sign-box">
            <div class="sign-line"></div>
            <div class="sign-label">Office Signature</div>
        </div>
    </div>

</div>

<script>
@php
$_admissionRoutePrefix = auth()->guard('staff')->check()
    ? 'staff.admissions'
    : (auth()->guard('center')->check()
        ? 'center.admissions'
        : (auth()->guard('partner')->check() ? 'partner.admissions' : 'admissions'));
$_printAllUrl = $lastInvoice
    ? route($_admissionRoutePrefix . '.print-all-receipt', ['student' => $student->id, 'invoice' => $lastInvoice->id])
    : route($_admissionRoutePrefix . '.print-all', ['student' => $student->id]);
$_thermalLinks = [
    'slip' => $_printAllUrl . '?view=slipThermal&autoprint=1',
    'fee'  => $lastInvoice
        ? route($_admissionRoutePrefix . '.print-all-receipt', ['student' => $student->id, 'invoice' => $lastInvoice->id]) . '?view=thermal&autoprint=1'
        : null,
];
@endphp
const THERMAL_LINKS = @json($_thermalLinks);

function openThermalSlip() {
    window.open(THERMAL_LINKS.slip, '_blank', 'noopener,noreferrer');
}

function openThermalFee() {
    if (!THERMAL_LINKS.fee) {
        alert('No fee receipt found for this student.');
        return;
    }

    window.open(THERMAL_LINKS.fee, '_blank', 'noopener,noreferrer');
}
</script>
</body>
</html>
