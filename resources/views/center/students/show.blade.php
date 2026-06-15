@extends('center.layout')
@section('title', $student->name)
@section('breadcrumb', 'Students / Profile')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Student Profile</h4>
    <a href="{{ route('center.students.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-4">
                @if($student->photo)
                    <img src="{{ Storage::url($student->photo) }}"
                         class="rounded-circle mb-3" style="width:80px;height:80px;object-fit:cover;">
                @else
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3"
                         style="width:80px;height:80px;font-size:28px;font-weight:700;">
                        {{ strtoupper(substr($student->name, 0, 1)) }}
                    </div>
                @endif
                <h5 class="fw-bold mb-1">{{ $student->name }}</h5>
                <div class="text-muted small">{{ $student->student_uid }}</div>
                <span class="badge {{ $student->status === 'active' ? 'bg-success' : 'bg-secondary' }} mt-2">
                    {{ ucfirst($student->status ?? 'active') }}
                </span>
            </div>
            <div class="card-footer bg-white border-top py-3">
                <table class="table table-sm mb-0" style="font-size:13px;">
                    <tr><td class="text-muted">Mobile</td><td class="fw-semibold">{{ $student->mobile }}</td></tr>
                    <tr><td class="text-muted">Course</td><td class="fw-semibold">{{ $student->stream->course->name ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Stream</td><td class="fw-semibold">{{ $student->stream->name ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Gender</td><td>{{ ucfirst($student->gender ?? '—') }}</td></tr>
                    <tr><td class="text-muted">Category</td><td>{{ strtoupper($student->category ?? '—') }}</td></tr>
                    @if($student->dob)
                    <tr><td class="text-muted">DOB</td><td>{{ $student->dob->format('d M Y') }}</td></tr>
                    @endif
                    <tr><td class="text-muted">Admission</td><td>{{ $student->admission_date?->format('d M Y') ?? '—' }}</td></tr>
                    @if($student->admission_source && $student->admission_source !== 'direct')
                    @php
                        $srcName = '';
                        if ($student->admission_source === 'center' && $student->admission_source_id) {
                            $srcName = \App\Models\Center::find($student->admission_source_id)?->name ?? '';
                        } elseif ($student->admission_source === 'channel_partner' && $student->admission_source_id) {
                            $srcName = \App\Models\ChannelPartner::find($student->admission_source_id)?->name ?? '';
                        }
                    @endphp
                    <tr>
                        <td class="text-muted">{{ $student->admission_source === 'channel_partner' ? 'Referred By' : 'Center' }}</td>
                        <td class="fw-semibold text-primary small">{{ $srcName ?: '—' }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        {{-- Documents (upload only — no verify) --}}
        @include('institute.admission._documents-verify', [
            'student'   => $student,
            'canVerify' => false,
            'canUpload' => $authUser->canManageAdmissions(),
            'canDelete' => false,
        ])

        {{-- Education Details --}}
        @if($student->educationDetails && $student->educationDetails->count() > 0)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2">
                <h6 class="mb-0 fw-semibold small"><i class="bi bi-mortarboard me-2 text-primary"></i>Education</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:12px;">
                    <thead class="table-light">
                        <tr><th class="ps-3">Exam</th><th>Institute</th><th>Year</th><th class="text-end pe-3">%</th></tr>
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

        {{-- Fee Info (if can_collect_fee) --}}
        @php
            $isOwnStudent = $student->admission_source === 'center'
                && (int) $student->admission_source_id === (int) $authUser->id;
            $canCollectThisStudent = $authUser->canCollectFee()
                && (!$authUser->isFeesScopeOwn() || $isOwnStudent);
        @endphp
        @if($canCollectThisStudent)
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold small"><i class="bi bi-cash-stack me-2 text-success"></i>Fee</h6>
                <a href="{{ route('center.fee.create') }}?student_id={{ $student->id }}"
                   class="btn btn-outline-success btn-sm py-0">Collect Fee</a>
            </div>
            <div class="card-body py-3">
                @php
                    $sessionId = \App\Models\AcademicSession::where('institute_id', $student->institute_id)
                        ->where('is_active', true)->value('id');
                    $totalPaid = \App\Models\FeeInvoice::where('student_id', $student->id)
                        ->where('academic_session_id', $sessionId)
                        ->where('is_cancelled', false)->sum('paid_amount');
                @endphp
                <div class="small text-muted">Total Paid (this session)</div>
                <div class="fw-bold fs-5 text-success">₹ {{ number_format($totalPaid) }}</div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
