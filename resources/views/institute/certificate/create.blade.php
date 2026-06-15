@extends('institute.layout')
@section('title', 'Issue Certificate')
@section('breadcrumb', 'Certificates / Issue')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-award me-2 text-primary"></i>Issue Certificate</h4>
        <small class="text-muted">Student search karo, certificate type choose karo, phir preview karke issue karo</small>
    </div>
    <a href="{{ route('certificate.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-list-ul me-1"></i> All Certificates
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4 justify-content-center">
<div class="col-lg-7">

{{-- Step 1: Student Search --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <span class="fw-semibold"><span class="badge bg-primary me-2">1</span>Student Select Karo</span>
    </div>
    <div class="card-body">
        <div class="position-relative">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="studentSearch" class="form-control border-start-0 ps-0"
                       placeholder="Name, Enrollment No, Roll No, Father Name, Mobile..."
                       autocomplete="off"
                       value="{{ $student?->name }}">
            </div>
            <div id="searchResults" class="list-group position-absolute w-100 shadow"
                 style="z-index:1050; display:none; max-height:280px; overflow-y:auto; top:100%; margin-top:4px;">
            </div>
        </div>

        {{-- Selected student card --}}
        <div id="studentCard" class="mt-3 {{ $student ? '' : 'd-none' }}">
            <div class="d-flex align-items-center gap-3 p-3 border rounded bg-light">
                <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                    <i class="bi bi-person-fill text-primary fs-5"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold" id="cardName">{{ $student?->name }}</div>
                    <div class="small text-muted" id="cardInfo">
                        @if($student)
                            {{ $student->student_uid }}
                            @if($student->stream?->course) &nbsp;•&nbsp; {{ $student->stream->course->name }} @endif
                            @if($student->stream) &nbsp;•&nbsp; {{ $student->stream->name }} @endif
                            @if($student->current_semester) &nbsp;•&nbsp; Sem {{ $student->current_semester }} @endif
                        @endif
                    </div>
                    <div class="small text-muted" id="cardFather">
                        @if($student?->father_name) Father: {{ $student->father_name }} @endif
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearStudent()">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </div>

        <input type="hidden" id="selectedStudentId" value="{{ $student?->id }}">
    </div>
</div>

{{-- Step 2: Certificate Details --}}
<div class="card border-0 shadow-sm mb-4" id="certDetailsCard" style="{{ $student ? '' : 'opacity:.5; pointer-events:none;' }}">
    <div class="card-header bg-white border-bottom py-3">
        <span class="fw-semibold"><span class="badge bg-primary me-2">2</span>Certificate Details</span>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label fw-semibold">Certificate Type <span class="text-danger">*</span></label>
            <select id="certTypeSelect" class="form-select">
                <option value="">-- Select type --</option>
                @foreach($types as $type)
                    <option value="{{ $type->id }}">{{ $type->name }} ({{ strtoupper($type->slug) }})</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Remarks <span class="text-muted small">(optional)</span></label>
            <textarea id="certRemarks" class="form-control" rows="2" placeholder="Kisi khas purpose ke liye hai to likhein..."></textarea>
        </div>
    </div>
</div>

{{-- Step 3: Preview --}}
<div class="card border-0 shadow-sm" id="previewCard" style="{{ $student ? '' : 'opacity:.5; pointer-events:none;' }}">
    <div class="card-header bg-white border-bottom py-3">
        <span class="fw-semibold"><span class="badge bg-primary me-2">3</span>Preview & Issue</span>
    </div>
    <div class="card-body">
        @if($types->isEmpty())
            <div class="alert alert-warning mb-0">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Pehle <a href="{{ route('certificate.types.index') }}" class="alert-link">Certificate Types setup karo</a>.
            </div>
        @else
        <div class="d-flex gap-2">
            <button type="button" id="previewBtn" class="btn btn-outline-primary flex-grow-1" onclick="openPreview()">
                <i class="bi bi-eye me-1"></i> Preview Certificate
            </button>
            <button type="button" id="issueBtn" class="btn btn-primary flex-grow-1" onclick="issueCertificate()">
                <i class="bi bi-award me-1"></i> Issue & Download PDF
            </button>
        </div>
        <div class="text-muted small mt-2 text-center">
            <i class="bi bi-info-circle me-1"></i>
            Preview mein dekho pehle, phir Issue karo — ek baar issue hone ke baad certificate number assign ho jaata hai
        </div>
        @endif
    </div>
</div>

</div>

{{-- Info sidebar --}}
<div class="col-lg-3">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-bottom py-2">
            <span class="fw-semibold small">Available Types</span>
        </div>
        <div class="list-group list-group-flush">
            @forelse($types as $type)
            <div class="list-group-item py-2 px-3 border-0">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-2">{{ strtoupper($type->slug) }}</span>
                <span class="small">{{ $type->name }}</span>
            </div>
            @empty
            <div class="list-group-item py-3 text-center text-muted small">
                <a href="{{ route('certificate.types.index') }}">Setup karo</a>
            </div>
            @endforelse
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-2">
            <span class="fw-semibold small">Quick Links</span>
        </div>
        <div class="list-group list-group-flush">
            <a href="{{ route('certificate.settings.index') }}" class="list-group-item list-group-item-action py-2 px-3 small border-0">
                <i class="bi bi-gear me-2 text-muted"></i>Certificate Settings
            </a>
            <a href="{{ route('certificate.types.index') }}" class="list-group-item list-group-item-action py-2 px-3 small border-0">
                <i class="bi bi-list-ul me-2 text-muted"></i>Certificate Types
            </a>
            <a href="{{ route('certificate.index') }}" class="list-group-item list-group-item-action py-2 px-3 small border-0">
                <i class="bi bi-clock-history me-2 text-muted"></i>Issued History
            </a>
        </div>
    </div>
</div>

</div>

{{-- Hidden forms --}}
<form id="previewForm" method="POST" action="{{ route('certificate.preview') }}" target="_blank">
    @csrf
    <input type="hidden" name="student_id" id="fStudentId">
    <input type="hidden" name="certificate_type_id" id="fTypeId">
    <input type="hidden" name="remarks" id="fRemarks">
</form>

<form id="issueForm" method="POST" action="{{ route('certificate.store') }}">
    @csrf
    <input type="hidden" name="student_id" id="iStudentId">
    <input type="hidden" name="certificate_type_id" id="iTypeId">
    <input type="hidden" name="remarks" id="iRemarks">
</form>

@push('scripts')
<script>
const ajaxUrl = @json(route('certificate.search-student'));

let searchTimer;
const searchInput  = document.getElementById('studentSearch');
const resultsBox   = document.getElementById('searchResults');
const studentCard  = document.getElementById('studentCard');
const detailsCard  = document.getElementById('certDetailsCard');
const previewCard  = document.getElementById('previewCard');

searchInput.addEventListener('input', function () {
    clearTimeout(searchTimer);
    const q = this.value.trim();
    if (q.length < 2) { resultsBox.style.display = 'none'; return; }

    searchTimer = setTimeout(() => {
        fetch(ajaxUrl + '?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                if (!data.length) {
                    resultsBox.innerHTML = '<div class="list-group-item text-muted text-center py-3 small"><i class="bi bi-search me-2"></i>Koi student nahi mila</div>';
                } else {
                    resultsBox.innerHTML = data.map(s => `
                        <button type="button" class="list-group-item list-group-item-action py-2 px-3" onclick='selectStudent(${JSON.stringify(s)})'>
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold small">${s.name}</div>
                                    <div class="text-muted" style="font-size:11px;">
                                        ${s.student_uid}
                                        ${s.course ? ' • ' + s.course : ''}
                                        ${s.stream ? ' • ' + s.stream : ''}
                                        ${s.semester ? ' • Sem ' + s.semester : ''}
                                    </div>
                                    ${s.father_name ? `<div class="text-muted" style="font-size:11px;">Father: ${s.father_name}</div>` : ''}
                                </div>
                                <small class="text-muted">${s.mobile || ''}</small>
                            </div>
                        </button>`).join('');
                }
                resultsBox.style.display = 'block';
            });
    }, 280);
});

function selectStudent(s) {
    document.getElementById('selectedStudentId').value = s.id;
    searchInput.value = s.name;
    resultsBox.style.display = 'none';

    document.getElementById('cardName').textContent  = s.name;
    let info = s.student_uid;
    if (s.course)    info += ' • ' + s.course;
    if (s.stream)    info += ' • ' + s.stream;
    if (s.semester)  info += ' • Sem ' + s.semester;
    document.getElementById('cardInfo').textContent   = info;
    document.getElementById('cardFather').textContent = s.father_name ? 'Father: ' + s.father_name : '';

    studentCard.classList.remove('d-none');
    detailsCard.style.opacity = '1'; detailsCard.style.pointerEvents = 'auto';
    previewCard.style.opacity = '1'; previewCard.style.pointerEvents = 'auto';
}

function clearStudent() {
    document.getElementById('selectedStudentId').value = '';
    searchInput.value = '';
    studentCard.classList.add('d-none');
    detailsCard.style.opacity = '.5'; detailsCard.style.pointerEvents = 'none';
    previewCard.style.opacity = '.5'; previewCard.style.pointerEvents = 'none';
}

function validateForm() {
    const studentId = document.getElementById('selectedStudentId').value;
    const typeId    = document.getElementById('certTypeSelect').value;
    if (!studentId) { alert('Pehle ek student select karo.'); return false; }
    if (!typeId)    { alert('Certificate type select karo.'); return false; }
    return { studentId, typeId, remarks: document.getElementById('certRemarks').value };
}

function openPreview() {
    const data = validateForm(); if (!data) return;
    document.getElementById('fStudentId').value = data.studentId;
    document.getElementById('fTypeId').value    = data.typeId;
    document.getElementById('fRemarks').value   = data.remarks;
    document.getElementById('previewForm').submit();
}

function issueCertificate() {
    const data = validateForm(); if (!data) return;
    if (!confirm('Certificate issue karna chahte hain? Ek baar issue hone ke baad certificate number assign ho jaayega.')) return;
    document.getElementById('iStudentId').value = data.studentId;
    document.getElementById('iTypeId').value    = data.typeId;
    document.getElementById('iRemarks').value   = data.remarks;
    document.getElementById('issueForm').submit();
}

document.addEventListener('click', function (e) {
    if (!resultsBox.contains(e.target) && e.target !== searchInput) {
        resultsBox.style.display = 'none';
    }
});
</script>
@endpush
@endsection
