{{--
  Document Upload Section — include inside admission forms
  Variables expected:
    $student      — Student model (for edit mode)
    $courseId     — int, selected course id
    $userType     — string: online/center/partner/staff
    $uploadRoute  — route name for upload action
--}}

@php
    use App\Models\DocumentUploadRule;
    use App\Models\DocumentCategory;
    use App\Models\AdmissionDocument;

    $instituteId = auth()->user()->institute_id
        ?? auth()->guard('staff')->user()?->institute_id
        ?? auth()->guard('center')->user()?->institute_id
        ?? auth()->guard('partner')->user()?->institute_id;

    $categories = collect();
    $uploadedDocs = collect();
    $rules = [];

    if (!empty($courseId) && $instituteId) {
        $rules = DocumentUploadRule::where('course_id', $courseId)
            ->where('user_type', $userType ?? 'online')
            ->where('requirement', '!=', 'skip')
            ->with(['documentType.category'])
            ->get()
            ->groupBy('document_type_id');

        if ($rules->isNotEmpty()) {
            $categories = DocumentCategory::forInstitute($instituteId)
                ->active()
                ->with(['documentTypes' => function ($q) use ($rules) {
                    $q->active()->whereIn('id', $rules->keys());
                }])
                ->orderBy('name')
                ->get()
                ->filter(fn($c) => $c->documentTypes->isNotEmpty());
        }
    }

    if (isset($student) && $student->exists) {
        $uploadedDocs = AdmissionDocument::where('student_id', $student->id)
            ->with('documentType')
            ->get()
            ->keyBy('document_type_id');
    }
@endphp

@if($rules->isNotEmpty())
<div class="card border-0 shadow-sm mb-4" id="documentsSection">
    <div class="card-header py-2 bg-white border-bottom d-flex align-items-center gap-2">
        <i class="bi bi-paperclip text-primary"></i>
        <span class="fw-semibold">Documents Upload</span>
        <span class="ms-auto">
            @php
                $requiredCount = $rules->filter(fn($r) => $r->first()?->requirement === 'required')->count();
                $uploadedRequired = 0;
                foreach($rules as $dtId => $ruleGroup) {
                    if ($ruleGroup->first()?->requirement === 'required' && isset($uploadedDocs[$dtId])) {
                        $uploadedRequired++;
                    }
                }
            @endphp
            @if($requiredCount > 0)
            <span class="badge {{ $uploadedRequired >= $requiredCount ? 'bg-success' : 'bg-warning text-dark' }}">
                {{ $uploadedRequired }}/{{ $requiredCount }} Required Uploaded
            </span>
            @endif
        </span>
    </div>
    <div class="card-body p-3">

        @if(session('doc_success'))
        <div class="alert alert-success alert-dismissible fade show py-2 small">
            <i class="bi bi-check-circle me-1"></i>{{ session('doc_success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @foreach($categories as $category)
        @if($category->documentTypes->isNotEmpty())
        <div class="mb-3">
            <div class="text-muted small fw-semibold mb-2 text-uppercase" style="letter-spacing:0.05em;">
                <i class="bi bi-folder2-open me-1"></i>{{ $category->name }}
            </div>
            <div class="row g-3">
                @foreach($category->documentTypes as $dt)
                @php
                    $rule = $rules[$dt->id]?->first();
                    $uploaded = $uploadedDocs[$dt->id] ?? null;
                    $isRequired = $rule?->requirement === 'required';
                @endphp
                <div class="col-md-6">
                    <div class="border rounded p-3 position-relative {{ $uploaded ? ($uploaded->isApproved() ? 'border-success bg-success-subtle' : ($uploaded->isRejected() ? 'border-danger bg-danger-subtle' : 'border-warning bg-warning-subtle')) : 'border-dashed' }}">

                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-semibold small">
                                    {{ $dt->name }}
                                    @if($isRequired)
                                    <span class="text-danger">*</span>
                                    @else
                                    <span class="text-muted small">(Optional)</span>
                                    @endif
                                </div>
                                <div class="text-muted" style="font-size:0.7rem;">
                                    Max {{ number_format($dt->max_size_kb) }}KB &bull; {{ $dt->allowed_formats }}
                                </div>
                            </div>
                            @if($uploaded)
                            <span class="badge {{ $uploaded->isApproved() ? 'bg-success' : ($uploaded->isRejected() ? 'bg-danger' : 'bg-warning text-dark') }}">
                                {{ ucfirst($uploaded->verification_status) }}
                            </span>
                            @endif
                        </div>

                        @if($uploaded)
                        <div class="d-flex gap-2 align-items-center mb-2">
                            <i class="bi bi-{{ $uploaded->isImage() ? 'image' : 'file-earmark-pdf' }} text-secondary"></i>
                            <a href="{{ route('admission.documents.show', $uploaded) }}" target="_blank"
                               class="text-primary small text-truncate" style="max-width:160px;">
                                {{ $uploaded->original_name }}
                            </a>
                        </div>
                        @if($uploaded->isRejected() && $uploaded->rejection_reason)
                        <div class="alert alert-danger py-1 small mb-2">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <strong>Rejected:</strong> {{ $uploaded->rejection_reason }}
                        </div>
                        @endif
                        @endif

                        {{-- Upload / Replace Form --}}
                        @if(!$uploaded || !$uploaded->isApproved())
                        <form method="POST"
                              action="{{ route('admission.documents.upload', isset($student) ? $student : 0) }}"
                              enctype="multipart/form-data"
                              class="d-flex gap-2 align-items-center">
                            @csrf
                            <input type="hidden" name="document_type_id" value="{{ $dt->id }}">
                            <input type="file" name="file" class="form-control form-control-sm"
                                   accept="{{ collect(explode(',', $dt->allowed_formats))->map(fn($f) => '.' . trim($f))->implode(',') }}"
                                   required>
                            <button type="submit" class="btn btn-sm {{ $uploaded ? 'btn-warning' : 'btn-primary' }} text-nowrap">
                                <i class="bi bi-upload me-1"></i>{{ $uploaded ? 'Replace' : 'Upload' }}
                            </button>
                        </form>
                        @elseif($uploaded->isApproved())
                        <div class="text-success small"><i class="bi bi-shield-check me-1"></i>Verified by admin</div>
                        @endif

                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @endforeach

    </div>
</div>
@elseif(!empty($courseId))
<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    Is course ke liye koi document rules configure nahi hain. Admin se contact karo ya documents baad me upload karo.
</div>
@endif
