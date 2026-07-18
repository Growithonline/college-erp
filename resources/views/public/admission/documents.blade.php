<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Documents — {{ $institute->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('public.admission.partials._brand-style')
    <style>
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f0f4f8; min-height: 100vh; }
        .documents-card { max-width: 680px; margin: 40px auto; }
        .institute-logo { max-height: 64px; max-width: 200px; object-fit: contain; }
        .doc-row { border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="container documents-card">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    @if($institute->image)
                        <img src="{{ asset('storage/' . $institute->image) }}" alt="{{ $institute->name }}" class="institute-logo mb-2 d-block mx-auto">
                    @endif
                    <h4 class="fw-bold mb-0">{{ $institute->name }}</h4>
                    <div class="text-muted small">Upload Admission Documents — {{ $student->name }} ({{ $student->student_uid }})</div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                @forelse($required as $item)
                    @php
                        $docType = $item['document_type'];
                        $doc = $uploaded->get($docType->id);
                    @endphp
                    <div class="doc-row">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">{{ $docType->name }}</div>
                                <div class="small text-muted">
                                    {{ ucfirst($item['requirement']) }} &bull; Max {{ $docType->max_size_kb }} KB &bull; {{ $docType->allowed_formats }}
                                </div>
                            </div>
                            @if($doc)
                                <span class="badge bg-{{ ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$doc->verification_status] ?? 'secondary' }}-subtle text-{{ ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$doc->verification_status] ?? 'secondary' }}">
                                    {{ ucfirst($doc->verification_status) }}
                                </span>
                            @endif
                        </div>

                        @if($doc && $doc->isRejected() && $doc->rejection_reason)
                            <div class="small text-danger mt-2">Reason: {{ $doc->rejection_reason }}</div>
                        @endif

                        @if(!$doc || !$doc->isApproved())
                            <form method="POST" action="{{ url()->full() }}" enctype="multipart/form-data" class="d-flex gap-2 mt-2">
                                @csrf
                                <input type="hidden" name="document_type_id" value="{{ $docType->id }}">
                                <input type="file" name="file" class="form-control form-control-sm" required>
                                <button type="submit" class="btn btn-primary btn-sm text-nowrap">
                                    {{ $doc ? 'Re-upload' : 'Upload' }}
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-info-circle fs-3 d-block mb-2 opacity-25"></i>
                        No documents are required for your course. Your application is ready for review.
                    </div>
                @endforelse

                @if(!empty($required) && collect($required)->every(fn ($item) => $uploaded->get($item['document_type']->id)))
                    <div class="alert alert-success mt-3 mb-0">
                        <i class="bi bi-check-circle me-1"></i> All documents uploaded. Your application is now awaiting review.
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
