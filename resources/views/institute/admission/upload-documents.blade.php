@php
    $uploadDocLayout = auth()->guard('staff')->check() ? 'staff.layout'
        : (auth()->guard('center')->check() ? 'center.layout'
        : (auth()->guard('partner')->check() ? 'partner.layout' : 'institute.layout'));
@endphp
@extends($uploadDocLayout)
@section('title', 'Document Upload')
@section('breadcrumb', 'Admission / Document Upload')

@section('content')
@php
    $studentName = $student->name;
    $courseName  = $student->stream?->course?->name ?? 'N/A';
    $streamName  = $student->stream?->name ?? '';
    $isRequired  = $docSetting === 'required';

    // Index uploaded docs by document_type_id for quick lookup
    $uploadedMap = $uploaded instanceof \Illuminate\Support\Collection
        ? $uploaded->keyBy('document_type_id')
        : collect($uploaded)->keyBy('document_type_id');

    // Count pending required docs
    $pendingRequired = collect($rules)->filter(function($r) use ($uploadedMap) {
        return ($r['requirement'] === 'required')
            && !isset($uploadedMap[$r['document_type']?->id]);
    })->count();
@endphp

{{-- Header Card --}}
<div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #6366f1 !important;">
    <div class="card-body py-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="mb-1 fw-bold">
                    <i class="bi bi-paperclip me-2 text-primary"></i>Document Upload
                </h5>
                <div class="text-muted small">
                    <b>{{ $studentName }}</b> &mdash; {{ $courseName }}
                    @if($streamName) <span class="text-muted">({{ $streamName }})</span> @endif
                </div>
            </div>
            <div>
                @if($isRequired)
                    <span class="badge bg-danger px-3 py-2" style="font-size:12px;">
                        <i class="bi bi-exclamation-circle me-1"></i>Documents Required
                    </span>
                @else
                    <span class="badge bg-warning text-dark px-3 py-2" style="font-size:12px;">
                        <i class="bi bi-info-circle me-1"></i>Optional — You can skip
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>

@if($errors->has('file') || $errors->has('document_type_id'))
<div class="alert alert-danger alert-dismissible fade show py-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first('file') ?: $errors->first('document_type_id') }}
    <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
</div>
@endif

@if(count($rules) === 0)
{{-- No rules configured —— show info and continue --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body text-center py-5">
        <i class="bi bi-folder2-open text-muted" style="font-size:2.5rem;"></i>
        <p class="text-muted mt-3 mb-0">No document requirements have been configured for this course.</p>
        <p class="text-muted small">Ask the admin to configure Document Rules.</p>
    </div>
</div>
@else
<div class="row g-3">
    @foreach($rules as $rule)
    @php
        $docType  = $rule['document_type'];
        $req      = $rule['requirement'];
        $existing = $uploadedMap[$docType?->id] ?? null;
        $isUploaded = $existing !== null;
        $statusColor = $isUploaded
            ? ($existing->verification_status === 'approved' ? 'success'
                : ($existing->verification_status === 'rejected' ? 'danger' : 'warning'))
            : 'light';
        $statusText = $isUploaded
            ? ucfirst($existing->verification_status)
            : 'Not Uploaded';
    @endphp
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100"
             style="border-left:4px solid {{ $req === 'required' ? '#ef4444' : '#f59e0b' }} !important;">
            <div class="card-body">

                {{-- Doc type header --}}
                <div class="d-flex align-items-start justify-content-between mb-2">
                    <div>
                        <div class="fw-semibold">{{ $docType?->name ?? 'Unknown' }}</div>
                        <div class="text-muted small">
                            Max: {{ $docType?->max_size_kb ?? 'N/A' }} KB &bull;
                            Formats: {{ $docType?->allowed_formats ?? 'N/A' }}
                        </div>
                    </div>
                    <div class="d-flex gap-1">
                        <span class="badge bg-{{ $req === 'required' ? 'danger' : 'warning text-dark' }}" style="font-size:10px;">
                            {{ ucfirst($req) }}
                        </span>
                    </div>
                </div>

                {{-- Upload status --}}
                @if($isUploaded)
                <div class="rounded p-2 mb-2 d-flex align-items-center gap-2"
                     style="background:#f0fdf4;border:1px solid #bbf7d0;">
                    <i class="bi bi-{{ $existing->verification_status === 'approved' ? 'check-circle-fill text-success'
                        : ($existing->verification_status === 'rejected' ? 'x-circle-fill text-danger'
                        : 'clock-fill text-warning') }}"></i>
                    <div class="flex-fill small">
                        <span class="fw-semibold">{{ $existing->original_name }}</span>
                        <span class="text-muted ms-1">({{ $existing->file_size_kb }}KB)</span>
                        <span class="badge bg-{{ $statusColor }} ms-1" style="font-size:9px;">{{ $statusText }}</span>
                    </div>
                    <a href="{{ route($docRoutePrefix.'.show', $existing->id) }}"
                       target="_blank" class="btn btn-xs btn-outline-secondary btn-sm py-0 px-1">
                        <i class="bi bi-eye"></i>
                    </a>
                </div>
                @if($existing->verification_status === 'rejected' && $existing->rejection_reason)
                <div class="alert alert-danger py-1 px-2 mb-2" style="font-size:11px;">
                    <i class="bi bi-x-circle me-1"></i><b>Rejection Reason:</b> {{ $existing->rejection_reason }}
                </div>
                @endif
                @else
                <div class="rounded p-2 mb-2 text-muted small d-flex align-items-center gap-2"
                     style="background:#f8fafc;border:1px dashed #cbd5e1;">
                    <i class="bi bi-cloud-upload"></i>
                    <span>Not yet uploaded</span>
                </div>
                @endif

                {{-- Upload form --}}
                @if(!$isUploaded || in_array($existing->verification_status, ['pending', 'rejected']))
                @php $cardErrorKey = 'file_' . ($docType?->id ?? ''); @endphp
                <form method="POST"
                      action="{{ route($docRoutePrefix.'.upload', $student->id) }}"
                      enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="document_type_id" value="{{ $docType?->id }}">
                    <div class="input-group input-group-sm {{ $errors->has($cardErrorKey) ? 'is-invalid' : '' }}">
                        <input type="file" name="file"
                               class="form-control form-control-sm {{ $errors->has($cardErrorKey) ? 'is-invalid' : '' }}"
                               accept="{{ collect(explode(',', $docType?->allowed_formats ?? ''))->map(fn($f) => '.'.(trim($f)))->implode(',') }}"
                               required>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-upload me-1"></i>Upload
                        </button>
                    </div>
                    @if($errors->has($cardErrorKey))
                    <div class="text-danger mt-1" style="font-size:11px;">
                        <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first($cardErrorKey) }}
                    </div>
                    @endif
                </form>
                @elseif($existing->verification_status === 'approved')
                <div class="text-success small mt-1">
                    <i class="bi bi-check-circle-fill me-1"></i>Document approved. No replacement needed.
                </div>
                @endif

            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Bottom action bar --}}
<div class="card border-0 shadow-sm mt-4">
    <div class="card-body py-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="text-muted small">
                @if($isRequired && $pendingRequired > 0)
                    <i class="bi bi-exclamation-triangle text-danger me-1"></i>
                    <b>{{ $pendingRequired }}</b> required document(s) still pending.
                @elseif($pendingRequired === 0 && count($rules) > 0)
                    <i class="bi bi-check-circle text-success me-1"></i>
                    All required documents uploaded.
                @else
                    <i class="bi bi-info-circle text-muted me-1"></i>
                    Upload documents or update later from the student profile.
                @endif
            </div>
            <div class="d-flex gap-2">
                @if(!$isRequired)
                <a href="{{ $nextUrl ?? $profileUrl }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-skip-forward me-1"></i>Skip for Now
                </a>
                @endif

                <a href="{{ $nextUrl ?? $profileUrl }}"
                   class="btn btn-{{ $isRequired && $pendingRequired > 0 ? 'secondary disabled' : 'success' }} btn-sm"
                   id="continueBtn"
                   @if($isRequired && $pendingRequired > 0) tabindex="-1" aria-disabled="true" @endif>
                    <i class="bi bi-arrow-right me-1"></i>
                    {{ $isRequired && $pendingRequired > 0 ? 'Upload Documents First' : 'Continue' }}
                </a>
            </div>
        </div>
    </div>
</div>

@endsection
