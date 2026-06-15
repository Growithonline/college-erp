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
    $indexUrl = auth()->guard('staff')->check()
        ? route('staff.admissions.index')
        : (auth()->guard('center')->check()
            ? route('center.students.index')
            : (auth()->guard('partner')->check() ? route('partner.students.index') : route('admissions.index')));
    $profileUrl = auth()->guard('center')->check()
        ? route('center.students.show', $student)
        : (auth()->guard('partner')->check()
            ? route('partner.students.show', $student)
            : (auth()->guard('staff')->check() ? $indexUrl : route('admissions.show', $student)));
@endphp
@extends($admissionLayout)
@section('title', 'Admission Successful')
@section('breadcrumb', 'Admissions / Success')

@section('content')
@php
    // Check pending required docs for this student
    $courseId = $student->stream?->course_id;
    $uploadedDocTypeIds = $courseId
        ? \App\Models\AdmissionDocument::where('student_id', $student->id)
            ->pluck('document_type_id')
            ->toArray()
        : [];
    $pendingDocCount = 0;
    if ($courseId) {
        $pendingDocCount = \App\Models\DocumentUploadRule::where('course_id', $courseId)
            ->where('requirement', 'required')
            ->whereNotIn('document_type_id', $uploadedDocTypeIds ?: [-1])
            ->count();
    }

    $uploadDocsUrl = null;
    if ($pendingDocCount > 0) {
        $uploadDocsUrl = auth()->guard('staff')->check()
            ? route('staff.admissions.upload-documents', $student)
            : (auth()->guard('center')->check()
                ? route('center.admissions.upload-documents', $student)
                : (auth()->guard('partner')->check()
                    ? route('partner.admissions.upload-documents', $student)
                    : route('admissions.upload-documents', $student)));
    }
@endphp

{{-- Pending Documents Warning Banner --}}
@if($pendingDocCount > 0)
<div class="row justify-content-center mb-3">
<div class="col-md-6">
    <div class="alert alert-warning d-flex align-items-start gap-3 mb-0 shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 mt-1 flex-shrink-0"></i>
        <div class="flex-fill">
            <div class="fw-semibold">{{ $pendingDocCount }} Required Document(s) Pending!</div>
            <div class="small mt-1">
                Is student ke liye
                <b>{{ $pendingDocCount }}</b> required document(s) abhi upload nahi hue.
                Admission complete karne ke liye documents upload karo.
            </div>
            <a href="{{ $uploadDocsUrl }}" class="btn btn-warning btn-sm mt-2 fw-semibold">
                <i class="bi bi-upload me-1"></i>Documents Upload Karo
            </a>
        </div>
    </div>
</div>
</div>
@endif

<div class="row justify-content-center">
<div class="col-md-6">

    {{-- Success Card --}}
    <div class="card border-0 shadow-sm text-center mb-4 border-top border-success border-3">
        <div class="card-body p-4">

            {{-- Success Icon --}}
            <div class="mb-3">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success"
                     style="width:70px;height:70px;">
                    <i class="bi bi-check-lg text-white" style="font-size:2rem;"></i>
                </div>
            </div>

            <h4 class="fw-bold text-success mb-1">Admission Successful!</h4>
            <p class="text-muted small mb-3">Student successfully registered</p>

            {{-- Student ID Badge --}}
            <div class="p-3 rounded mb-3" style="background:#f0fdf4; border:2px solid #bbf7d0;">
                <div class="text-muted small mb-1">Student ID</div>
                <div class="fw-bold" style="font-size:1.3rem; color:#166534; letter-spacing:1px;">
                    {{ $student->student_uid }}
                </div>
            </div>

            {{-- Student Details --}}
            <div class="text-start rounded p-3 mb-4" style="background:#f8fafc;">
                <div class="row g-2" style="font-size:13px;">
                    <div class="col-5 text-muted">Name:</div>
                    <div class="col-7 fw-semibold">{{ $student->name }}</div>

                    <div class="col-5 text-muted">Mobile:</div>
                    <div class="col-7">{{ $student->mobile }}</div>

                    @if($student->gender)
                    <div class="col-5 text-muted">Gender:</div>
                    <div class="col-7">{{ ucfirst($student->gender) }}</div>
                    @endif

                    <div class="col-5 text-muted">Course:</div>
                    <div class="col-7">{{ $student->stream->course->name ?? '—' }}</div>

                    <div class="col-5 text-muted">Stream:</div>
                    <div class="col-7">{{ $student->stream->name ?? '—' }}</div>

                    <div class="col-5 text-muted">Session:</div>
                    <div class="col-7">{{ $student->session->name ?? '—' }}</div>

                    <div class="col-5 text-muted">Date:</div>
                    <div class="col-7">{{ now()->format('d M Y') }}</div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="d-grid gap-2">
                <a href="{{ route($admissionRoutePrefix . '.quick-create') }}"
                   class="btn btn-warning fw-bold">
                    <i class="bi bi-lightning-fill me-1"></i>
                    New Quick Registration
                </a>
                <div class="row g-2">
                    <div class="col-6">
                        <a href="{{ $profileUrl }}"
                           class="btn btn-outline-primary w-100 btn-sm">
                            <i class="bi bi-eye me-1"></i> View Profile
                        </a>
                    </div>
                    <div class="col-6">
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary w-100 btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="bi bi-printer me-1"></i> Print
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="openThermalSlip();return false;">
                                    <i class="bi bi-card-text me-1"></i> Admission Slip (Thermal)
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="openThermalFee();return false;">
                                    <i class="bi bi-receipt me-1"></i> Fee Receipt (Thermal)
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <a href="{{ $indexUrl }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-list me-1"></i> All Admissions
                </a>
            </div>

        </div>
    </div>

</div>
</div>

<script>
@php
$_printAllUrl = $lastInvoice
    ? route($admissionRoutePrefix . '.print-all-receipt', ['student' => $student->id, 'invoice' => $lastInvoice->id])
    : route($admissionRoutePrefix . '.print-all', ['student' => $student->id]);
$_thermalLinks = [
    'slip' => $_printAllUrl . '?view=slipThermal&autoprint=1',
    'fee'  => $lastInvoice
        ? route($admissionRoutePrefix . '.print-all-receipt', ['student' => $student->id, 'invoice' => $lastInvoice->id]) . '?view=thermal&autoprint=1'
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
@endsection
