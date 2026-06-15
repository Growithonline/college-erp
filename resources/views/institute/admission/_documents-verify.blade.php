{{--
  Documents Verification Panel — include in admission profile page
  Variables expected:
    $student    — Student model
    $canVerify  — bool: can current user verify docs
    $canUpload  — bool: can current user upload docs
    $canDelete  — bool: can current user delete docs
--}}

@php
    use App\Models\AdmissionDocument;
    use App\Models\DocumentCategory;
    use App\Models\DocumentUploadRule;

    $instituteId = $student->institute_id;
    $courseId    = $student->stream?->course_id ?? $student->course_id ?? null;
    $guardType   = 'web';
    foreach (['staff', 'center', 'partner'] as $g) {
        if (auth()->guard($g)->check()) { $guardType = $g; break; }
    }
    // Route prefix per portal — same route name 'admission.documents.*' works across all portals
    // because each portal group has its own prefix but same named sub-routes
    $docRoutePrefix = match($guardType) {
        'center'  => 'center.admission.documents',
        'partner' => 'partner.admission.documents',
        'staff'   => 'staff.admission.documents',
        default   => 'admission.documents',
    };
    $userType = match($guardType) {
        'center'  => 'center',
        'partner' => 'partner',
        'staff'   => 'staff',
        default   => 'online',
    };

    $uploadedDocs = AdmissionDocument::where('student_id', $student->id)
        ->with(['documentType.category', 'verifiedByStaff'])
        ->get()
        ->keyBy('document_type_id');

    $totalDocs   = $uploadedDocs->count();
    $pendingDocs = $uploadedDocs->filter->isPending()->count();
    $approved    = $uploadedDocs->filter->isApproved()->count();
    $rejected    = $uploadedDocs->filter->isRejected()->count();

    // Rules for this course + user type
    $rules = [];
    if ($courseId) {
        $rules = DocumentUploadRule::where('course_id', $courseId)
            ->where('user_type', $userType)
            ->where('requirement', '!=', 'skip')
            ->with('documentType')
            ->get()
            ->keyBy('document_type_id');
    }
@endphp

<div class="card border-0 shadow-sm mb-3" id="documentsVerifyCard">
    <div class="card-header py-2 d-flex align-items-center gap-2" style="background:#1e293b;color:white;">
        <i class="bi bi-paperclip"></i>
        <span class="fw-bold small">Documents</span>
        <div class="ms-auto d-flex gap-2">
            @if($totalDocs > 0)
            <span class="badge bg-success-subtle text-success border">{{ $approved }} Approved</span>
            <span class="badge bg-warning-subtle text-warning border">{{ $pendingDocs }} Pending</span>
            <span class="badge bg-danger-subtle text-danger border">{{ $rejected }} Rejected</span>
            @endif
        </div>
    </div>
    <div class="card-body p-3">

        @if(session('doc_success'))
        <div class="alert alert-success alert-dismissible fade show py-2 small">
            <i class="bi bi-check-circle me-1"></i>{{ session('doc_success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('success') && str_contains(session('success'), 'document'))
        <div class="alert alert-success alert-dismissible fade show py-2 small">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- Upload new document (for admin/staff) --}}
        @if($canUpload ?? false)
        <div class="mb-3">
            <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#uploadNewDocPanel">
                <i class="bi bi-upload me-1"></i>Upload New Document
            </button>
            <div class="collapse mt-2" id="uploadNewDocPanel">
                <div class="card card-body p-3 border-0 bg-light">
                    <form method="POST" action="{{ route($docRoutePrefix . '.upload', $student) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Document Type <span class="text-danger">*</span></label>
                                @php
                                    $allDocTypes = \App\Models\DocumentType::forInstitute($instituteId)->active()->with('category')->orderBy('name')->get();
                                @endphp
                                <select name="document_type_id" class="form-select form-select-sm" required>
                                    <option value="">-- Select --</option>
                                    @foreach($allDocTypes->groupBy(fn($d) => $d->category->name ?? 'Other') as $catName => $dts)
                                    <optgroup label="{{ $catName }}">
                                        @foreach($dts as $dt)
                                        <option value="{{ $dt->id }}">{{ $dt->name }}</option>
                                        @endforeach
                                    </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">File <span class="text-danger">*</span></label>
                                <input type="file" name="file" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-upload me-1"></i>Upload
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        @if($uploadedDocs->isEmpty())
        <div class="text-center py-4 text-muted">
            <i class="bi bi-folder2-open" style="font-size:2.5rem;"></i>
            <p class="mt-2 small">Koi document upload nahi hua hai.</p>
        </div>
        @else
        <div class="row g-3">
            @foreach($uploadedDocs as $doc)
            <div class="col-md-6">
                <div class="border rounded p-3 position-relative
                    {{ $doc->isApproved() ? 'border-success bg-success-subtle' :
                       ($doc->isRejected() ? 'border-danger bg-danger-subtle' : 'border-warning bg-warning-subtle') }}">

                    {{-- Status Badge --}}
                    <div class="position-absolute top-0 end-0 m-2">
                        <span class="badge {{ $doc->isApproved() ? 'bg-success' : ($doc->isRejected() ? 'bg-danger' : 'bg-warning text-dark') }}">
                            @if($doc->isApproved()) <i class="bi bi-check-circle me-1"></i>
                            @elseif($doc->isRejected()) <i class="bi bi-x-circle me-1"></i>
                            @else <i class="bi bi-clock me-1"></i>
                            @endif
                            {{ ucfirst($doc->verification_status) }}
                        </span>
                    </div>

                    {{-- Doc Info --}}
                    <div class="fw-semibold small mb-1">{{ $doc->documentType->name ?? '—' }}</div>
                    <div class="text-muted" style="font-size:0.72rem;">
                        {{ $doc->documentType->category->name ?? '' }}
                        @if($doc->file_size_kb) &bull; {{ number_format($doc->file_size_kb) }} KB @endif
                    </div>

                    <div class="d-flex align-items-center gap-2 mt-2 mb-2">
                        <i class="bi bi-{{ $doc->isImage() ? 'image' : 'file-earmark-pdf' }} text-secondary"></i>
                        <a href="{{ route($docRoutePrefix . '.show', $doc) }}" target="_blank"
                           class="small text-primary text-truncate" style="max-width:170px;">
                            {{ $doc->original_name }}
                        </a>
                    </div>

                    @if($doc->isRejected() && $doc->rejection_reason)
                    <div class="alert alert-danger py-1 small mb-2">
                        <strong>Reason:</strong> {{ $doc->rejection_reason }}
                    </div>
                    @endif

                    @if($doc->isApproved() && $doc->verifiedByStaff)
                    <div class="text-muted" style="font-size:0.7rem;">
                        <i class="bi bi-person-check me-1"></i>{{ $doc->verifiedByStaff->name }}
                        @if($doc->verified_at) &bull; {{ $doc->verified_at->format('d M Y, h:i A') }} @endif
                    </div>
                    @endif

                    {{-- Actions --}}
                    <div class="d-flex gap-2 mt-2 flex-wrap">

                        @if(($canVerify ?? false) && !$doc->isApproved())
                        <form method="POST" action="{{ route($docRoutePrefix . '.verify', $doc) }}">@csrf
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="bi bi-check-lg me-1"></i>Approve
                            </button>
                        </form>
                        @endif

                        @if(($canVerify ?? false) && !$doc->isRejected())
                        <button class="btn btn-danger btn-sm" type="button"
                                data-bs-toggle="collapse" data-bs-target="#rejectForm{{ $doc->id }}">
                            <i class="bi bi-x-lg me-1"></i>Reject
                        </button>
                        @endif

                        @if($canDelete ?? false)
                        <form method="POST" action="{{ route($docRoutePrefix . '.destroy', $doc) }}"
                              onsubmit="return confirm('Delete karna chahte ho?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @endif
                    </div>

                    {{-- Reject Form (collapsible) --}}
                    @if($canVerify ?? false)
                    <div class="collapse mt-2" id="rejectForm{{ $doc->id }}">
                        <form method="POST" action="{{ route($docRoutePrefix . '.reject', $doc) }}">@csrf
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Rejection Reason <span class="text-danger">*</span></label>
                                <textarea name="rejection_reason" class="form-control form-control-sm" rows="2" required
                                          placeholder="Student ko kya batana chahte ho..."></textarea>
                            </div>
                            @if($student->email && ($student->institute?->doc_rejection_notify ?? false))
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="send_notification" value="1"
                                       id="sendNotif{{ $doc->id }}" checked>
                                <label class="form-check-label small" for="sendNotif{{ $doc->id }}">
                                    Student ko email notification bhejo
                                </label>
                            </div>
                            @endif
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="bi bi-x-circle me-1"></i>Confirm Reject
                            </button>
                        </form>
                    </div>
                    @endif

                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Show required docs that are not yet uploaded --}}
        @if($rules->isNotEmpty())
        @php
            $missing = $rules->filter(fn($r) => $r->requirement === 'required' && !isset($uploadedDocs[$r->document_type_id]));
        @endphp
        @if($missing->isNotEmpty())
        <div class="alert alert-warning mt-3 small">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <strong>Required documents not uploaded:</strong>
            <ul class="mb-0 mt-1">
                @foreach($missing as $r)
                <li>{{ $r->documentType->name }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        @endif

    </div>
</div>
