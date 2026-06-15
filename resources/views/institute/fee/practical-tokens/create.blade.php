@php
    $feeLayout = auth()->guard('staff')->check() ? 'staff.layout' : 'institute.layout';
@endphp
@extends($feeLayout)
@section('title', 'Create Practical Token')
@section('breadcrumb', 'Fee / Practical Tokens / Create')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Create Practical Token Batch</h4>
        <small class="text-muted">Course/subject wise total collection token</small>
    </div>
    <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route($routePrefix . '.store') }}" class="row g-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Session</label>
                <select name="academic_session_id" class="form-select" required>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ old('academic_session_id', $activeSession?->id) == $session->id ? 'selected' : '' }}>{{ $session->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Course</label>
                <select name="course_id" id="courseSelect" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" data-parts='@json($course->parts->map(fn($p) => ["id" => $p->id, "label" => $p->year_label, "year" => $p->year_number]))' {{ old('course_id') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Subject</label>
                <select name="subject_id" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Year / Part</label>
                <select name="course_part_id" id="partSelect" class="form-select">
                    <option value="">Select course first</option>
                </select>
                <input type="hidden" name="year_number" id="yearNumber" value="{{ old('year_number', 1) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Semester</label>
                <input type="number" name="semester" value="{{ old('semester', 1) }}" min="1" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Token Amount</label>
                <input type="number" name="token_amount" value="{{ old('token_amount') }}" min="1" step="0.01" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Payment Mode</label>
                <select name="payment_mode" class="form-select" required>
                    @foreach(['cash' => 'Cash', 'upi' => 'UPI', 'online' => 'Online', 'cheque' => 'Cheque', 'dd' => 'DD', 'neft' => 'NEFT', 'rtgs' => 'RTGS'] as $key => $label)
                        <option value="{{ $key }}" {{ old('payment_mode', 'cash') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Practical Date</label>
                <input type="date" name="collection_date" value="{{ old('collection_date', now()->toDateString()) }}" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Title</label>
                <input type="text" name="title" value="{{ old('title') }}" class="form-control" placeholder="BA Geography Practical">
            </div>
            <div class="col-12">
                <label class="form-label small fw-semibold">Remarks</label>
                <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-primary">
                    <i class="bi bi-check2 me-1"></i> Create Batch
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const courseSelect = document.getElementById('courseSelect');
const partSelect = document.getElementById('partSelect');
const yearInput = document.getElementById('yearNumber');
function fillParts() {
    const opt = courseSelect.options[courseSelect.selectedIndex];
    const parts = opt && opt.dataset.parts ? JSON.parse(opt.dataset.parts) : [];
    partSelect.innerHTML = '<option value="">Select</option>';
    parts.forEach(part => {
        const option = document.createElement('option');
        option.value = part.id;
        option.textContent = part.label;
        option.dataset.year = part.year;
        partSelect.appendChild(option);
    });
}
courseSelect.addEventListener('change', fillParts);
partSelect.addEventListener('change', () => {
    const opt = partSelect.options[partSelect.selectedIndex];
    yearInput.value = opt && opt.dataset.year ? opt.dataset.year : 1;
});
fillParts();
</script>
@endpush
