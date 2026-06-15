@extends('institute.layout')
@section('title', 'Certificate Types')
@section('breadcrumb', 'Certificates / Types')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Certificate Types</h4>
        <small class="text-muted">TC, CC, Bonafide aur other certificate types yahin manage karo</small>
    </div>
    <div class="d-flex gap-2">
        @if($types->isEmpty())
        <form method="POST" action="{{ route('certificate.types.seed') }}">
            @csrf
            <button type="submit" class="btn btn-outline-success btn-sm">
                <i class="bi bi-magic me-1"></i> Load Defaults (TC, CC, Bonafide, Migration)
            </button>
        </form>
        @endif
        <a href="{{ route('certificate.settings.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-gear me-1"></i> Settings
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('info'))
    <div class="alert alert-info alert-dismissible fade show">
        <i class="bi bi-info-circle me-2"></i>{{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->has('delete'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first('delete') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Available Placeholders Info --}}
<div class="alert alert-light border mb-4">
    <strong><i class="bi bi-info-circle me-1 text-primary"></i>Available Placeholders:</strong>
    <div class="mt-2 d-flex flex-wrap gap-2">
        @foreach(['@{{student_name}}','@{{father_name}}','@{{mother_name}}','@{{enrollment_no}}','@{{roll_no}}','@{{course_name}}','@{{stream_name}}','@{{semester}}','@{{admission_date}}','@{{current_date}}','@{{academic_session}}','@{{certificate_number}}','@{{institute_name}}'] as $ph)
            <code class="bg-white border rounded px-2 py-1 small" style="cursor:pointer;" onclick="navigator.clipboard.writeText('{{ $ph }}'); this.style.background='#d1fae5';" title="Click to copy">{{ $ph }}</code>
        @endforeach
    </div>
    <div class="text-muted small mt-1">Kisi bhi placeholder pe click karke copy karo, phir template mein paste karo.</div>
</div>

<div class="row g-4">

    {{-- Add New Form --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <span class="fw-semibold">New Certificate Type</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('certificate.types.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Certificate Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                               placeholder="e.g. Transfer Certificate">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Slug <span class="text-danger">*</span></label>
                        <input type="text" name="slug" id="slugInput" class="form-control" value="{{ old('slug') }}"
                               placeholder="e.g. tc">
                        <div class="form-text">Short unique code — letters, numbers, dash only (e.g. tc, cc, bonafide)</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Body Template <span class="text-danger">*</span></label>
                        <textarea name="body_template" class="form-control" rows="8"
                                  placeholder="Certificate ka content likhein. Placeholders use kar sakte hain jaise @{{student_name}}">{{ old('body_template') }}</textarea>
                        <div class="form-text">HTML allowed — bold, italic, paragraph etc.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-circle me-1"></i> Add Type
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- List --}}
    <div class="col-lg-8">
        @forelse($types as $type)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold">{{ strtoupper($type->slug) }}</span>
                <span class="fw-semibold">{{ $type->name }}</span>
                @if(!$type->is_active)
                    <span class="badge bg-secondary-subtle text-secondary border ms-1">Inactive</span>
                @endif
                <div class="ms-auto d-flex gap-1">
                    <form method="POST" action="{{ route('certificate.types.toggle', $type) }}" class="d-inline">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sm {{ $type->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $type->is_active ? 'Deactivate' : 'Activate' }}">
                            <i class="bi bi-{{ $type->is_active ? 'pause-circle' : 'play-circle' }}"></i>
                        </button>
                    </form>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $type->id }}" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form method="POST" action="{{ route('certificate.types.destroy', $type) }}" class="d-inline"
                          onsubmit="return confirm('{{ $type->name }} delete karna chahte hain?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body py-2">
                <div class="small text-muted" style="max-height:60px; overflow:hidden;">
                    {!! strip_tags($type->body_template) !!}
                </div>
            </div>
        </div>

        {{-- Edit Modal --}}
        <div class="modal fade" id="editModal{{ $type->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-semibold">Edit: {{ $type->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="{{ route('certificate.types.update', $type) }}">
                        @csrf @method('PUT')
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Certificate Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ $type->name }}" required>
                            </div>
                            <div class="mb-1">
                                <label class="form-label fw-semibold">Body Template <span class="text-danger">*</span></label>
                                <div class="mb-2 d-flex flex-wrap gap-1">
                                    @foreach(['@{{student_name}}','@{{father_name}}','@{{enrollment_no}}','@{{course_name}}','@{{stream_name}}','@{{semester}}','@{{admission_date}}','@{{current_date}}','@{{academic_session}}','@{{certificate_number}}'] as $ph)
                                        <code class="bg-light border rounded px-2 py-1 small" style="cursor:pointer; font-size:11px;"
                                              onclick="insertPlaceholder('editBody{{ $type->id }}', '{{ $ph }}')" title="Insert">{{ $ph }}</code>
                                    @endforeach
                                </div>
                                <textarea name="body_template" id="editBody{{ $type->id }}" class="form-control" rows="12" required>{{ $type->body_template }}</textarea>
                                <div class="form-text">HTML allowed</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @empty
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-file-earmark-text text-muted" style="font-size:2.5rem;"></i>
                <div class="mt-2 fw-semibold text-muted">Koi certificate type nahi hai</div>
                <div class="text-muted small mb-3">Left form se add karo, ya "Load Defaults" button se TC, CC, Bonafide ek click mein load karo</div>
            </div>
        </div>
        @endforelse
    </div>

</div>

<script>
// Auto-generate slug from name
document.querySelector('input[name="name"]')?.addEventListener('input', function() {
    const slugInput = document.getElementById('slugInput');
    if (!slugInput.dataset.manuallyEdited) {
        slugInput.value = this.value.toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^a-z0-9-]/g, '')
            .substring(0, 30);
    }
});

document.getElementById('slugInput')?.addEventListener('input', function() {
    this.dataset.manuallyEdited = 'true';
});

function insertPlaceholder(textareaId, placeholder) {
    const ta = document.getElementById(textareaId);
    const start = ta.selectionStart;
    const end = ta.selectionEnd;
    ta.value = ta.value.substring(0, start) + placeholder + ta.value.substring(end);
    ta.selectionStart = ta.selectionEnd = start + placeholder.length;
    ta.focus();
}
</script>

@endsection
