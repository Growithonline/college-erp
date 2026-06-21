@extends('institute.layout')
@section('title', isset($course) ? 'Edit Course' : 'Add Course')
@section('breadcrumb', 'Master / Course / ' . (isset($course) ? 'Edit' : 'New'))

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="bi bi-book me-2 text-primary"></i>
                    {{ isset($course) ? 'Edit Course — '.$course->name : 'Add New Course' }}
                </h5>
            </div>
            <div class="card-body p-4">
                @if(isset($course))
                    <form method="POST" action="{{ route('master.courses.update', $course) }}">
                    @method('PUT')
                @else
                    <form method="POST" action="{{ route('master.courses.store') }}">
                @endif
                @csrf

                <div class="row g-3">
                    {{-- Course Type --}}
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Course Type <span class="text-danger">*</span></label>
                        <select name="course_type_id" class="form-select @error('course_type_id') is-invalid @enderror">
                            <option value="">Select Type</option>
                            @foreach($courseTypes as $type)
                                <option value="{{ $type->id }}"
                                    {{ old('course_type_id', $course->course_type_id ?? '') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('course_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Course Name --}}
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Course Name <span class="text-danger">*</span></label>
                        <input type="text" name="name"
                               value="{{ old('name', $course->name ?? '') }}"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="e.g. BACHELOR OF ARTS"
                               style="text-transform:uppercase">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Code --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Short Code <span class="text-danger">*</span></label>
                        <input type="text" name="code"
                               value="{{ old('code', $course->code ?? '') }}"
                               class="form-control @error('code') is-invalid @enderror"
                               placeholder="e.g. BA" style="text-transform:uppercase">
                        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Duration --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Duration <span class="text-danger">*</span></label>
                        <input type="number" name="duration" min="1"
                               value="{{ old('duration', $course->duration ?? '') }}"
                               class="form-control @error('duration') is-invalid @enderror"
                               placeholder="3">
                        @error('duration') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Duration Type --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Duration In <span class="text-danger">*</span></label>
                        <select name="duration_type" id="durationType" class="form-select @error('duration_type') is-invalid @enderror" onchange="onDurationTypeChange()">
                            <option value="year"  {{ old('duration_type', $course->duration_type ?? 'year') == 'year'  ? 'selected' : '' }}>Years</option>
                            <option value="month" {{ old('duration_type', $course->duration_type ?? '')     == 'month' ? 'selected' : '' }}>Months</option>
                        </select>
                        @error('duration_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Structure Type --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Structure <span class="text-danger">*</span></label>
                        <select name="structure_type" id="structureType" class="form-select @error('structure_type') is-invalid @enderror" onchange="onStructureChange()">
                            <option value="semester"  {{ old('structure_type', $course->structure_type ?? '') == 'semester'  ? 'selected' : '' }}>Semester</option>
                            <option value="yearly"    {{ old('structure_type', $course->structure_type ?? '') == 'yearly'    ? 'selected' : '' }}>Yearly</option>
                            <option value="trimester" {{ old('structure_type', $course->structure_type ?? '') == 'trimester' ? 'selected' : '' }}>Trimester</option>
                            <option value="modular"   {{ old('structure_type', $course->structure_type ?? '') == 'modular'   ? 'selected' : '' }}>Modular (Monthly)</option>
                        </select>
                        @error('structure_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Semesters per Year (conditional) --}}
                    <div class="col-md-3" id="spyWrapper">
                        <label class="form-label fw-semibold">Parts per Year <span class="text-danger">*</span></label>
                        <input type="number" name="semesters_per_year" id="semestersPerYear" min="1" max="12"
                               value="{{ old('semesters_per_year', $course->semesters_per_year ?? 2) }}"
                               class="form-control @error('semesters_per_year') is-invalid @enderror">
                        <small class="text-muted" id="spyHint">Semesters in one academic year</small>
                        @error('semesters_per_year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Max ATKT (hidden for modular) --}}
                    <div class="col-md-3" id="atktWrapper">
                        <label class="form-label fw-semibold">Max ATKT Allowed</label>
                        <input type="number" name="max_atkt_allowed" min="0" max="10"
                               value="{{ old('max_atkt_allowed', $course->max_atkt_allowed ?? 2) }}"
                               class="form-control @error('max_atkt_allowed') is-invalid @enderror">
                        <small class="text-muted">0 = ATKT not allowed (e.g. BEd)</small>
                        @error('max_atkt_allowed') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Short-term info banner (shown for modular) --}}
                    <div class="col-12" id="shortTermBanner" style="display:none;">
                        <div class="alert alert-info py-2 mb-0 small border-0">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Short-term Course:</strong> No semester promotion applies. Students complete the course at end of duration. Fee is charged one-time at admission.
                        </div>
                    </div>

                    {{-- Lateral Entry (hidden for modular) --}}
                    <div class="col-12" id="lateralWrapper">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="lateral_entry_allowed"
                                   id="lateralEntry" value="1"
                                   {{ old('lateral_entry_allowed', $course->lateral_entry_allowed ?? false) ? 'checked' : '' }}
                                   onchange="document.getElementById('lateralPart').style.display = this.checked ? 'block' : 'none'">
                            <label class="form-check-label fw-semibold" for="lateralEntry">
                                Lateral Entry Allowed?
                            </label>
                        </div>
                    </div>

                    <div class="col-md-4" id="lateralPart"
                         style="display: {{ old('lateral_entry_allowed', $course->lateral_entry_allowed ?? false) ? 'block' : 'none' }}">
                        <label class="form-label fw-semibold">Lateral Entry From Part #</label>
                        <input type="number" name="lateral_entry_start_part" min="1"
                               value="{{ old('lateral_entry_start_part', $course->lateral_entry_start_part ?? '') }}"
                               class="form-control" placeholder="e.g. 3">
                        <small class="text-muted">Semester/Year number from which lateral entry starts</small>
                    </div>
                </div>

                <hr class="my-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i>
                        {{ isset($course) ? 'Update Course' : 'Save Course' }}
                    </button>
                    <a href="{{ route('master.courses.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function onStructureChange() {
    const structure = document.getElementById('structureType').value;
    const spyWrapper   = document.getElementById('spyWrapper');
    const spyInput     = document.getElementById('semestersPerYear');
    const spyHint      = document.getElementById('spyHint');
    const atktWrapper  = document.getElementById('atktWrapper');
    const lateralWrap  = document.getElementById('lateralWrapper');
    const lateralPart  = document.getElementById('lateralPart');
    const shortTermBanner = document.getElementById('shortTermBanner');

    // Reset visibility
    spyWrapper.style.display    = 'block';
    atktWrapper.style.display   = 'block';
    lateralWrap.style.display   = 'block';
    shortTermBanner.style.display = 'none';

    if (structure === 'semester') {
        spyInput.value = spyInput.value || 2;
        spyInput.readOnly = false;
        spyHint.textContent = 'Semesters in one academic year (e.g. 2)';
    } else if (structure === 'trimester') {
        spyInput.value = 3;
        spyInput.readOnly = true;
        spyHint.textContent = 'Trimesters per year — fixed at 3';
    } else if (structure === 'yearly') {
        spyWrapper.style.display = 'none';
        spyInput.value = 1;
    } else if (structure === 'modular') {
        spyWrapper.style.display    = 'none';
        atktWrapper.style.display   = 'none';
        lateralWrap.style.display   = 'none';
        lateralPart.style.display   = 'none';
        shortTermBanner.style.display = 'block';
        spyInput.value = 0;
    }
}

function onDurationTypeChange() {
    const durationType = document.getElementById('durationType').value;
    const structureSelect = document.getElementById('structureType');

    if (durationType === 'month') {
        // Lock to modular for month-based courses
        structureSelect.value = 'modular';
        onStructureChange();
    }
}

// Run on page load to set correct initial state
document.addEventListener('DOMContentLoaded', function () {
    onStructureChange();
    // If duration type is months, also trigger that logic
    if (document.getElementById('durationType').value === 'month') {
        onDurationTypeChange();
    }
});
</script>
@endpush
@endsection
