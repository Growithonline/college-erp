@extends('institute.layout')
@section('title', isset($subject) ? 'Edit Subject' : 'Add Subject')
@section('breadcrumb', 'Master / Subject / ' . (isset($subject) ? 'Edit' : 'New'))

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="bi bi-journal-text me-2 text-primary"></i>
                    {{ isset($subject) ? 'Edit Subject — '.$subject->name : 'Add New Subject' }}
                </h5>
            </div>
            <div class="card-body p-4">
                @if(isset($subject))
                    <form method="POST" action="{{ route('master.subjects.update', $subject) }}">
                    @method('PUT')
                @else
                    <form method="POST" action="{{ route('master.subjects.store') }}">
                @endif
                @csrf

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" name="name"
                               value="{{ old('name', $subject->name ?? '') }}"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="e.g. ENGLISH, MATHEMATICS"
                               style="text-transform:uppercase">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Code</label>
                        <input type="text" name="code"
                               value="{{ old('code', $subject->code ?? '') }}"
                               class="form-control" placeholder="e.g. ENG"
                               style="text-transform:uppercase">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Credits</label>
                        <input type="number" name="credit" min="0"
                               value="{{ old('credit', $subject->credit ?? '') }}"
                               class="form-control" placeholder="e.g. 4">
                    </div>
                </div>

                {{-- Has Practical Toggle --}}
                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="has_practical"
                               id="hasPractical" value="1"
                               {{ old('has_practical', $subject->has_practical ?? false) ? 'checked' : '' }}
                               onchange="togglePractical(this.checked)">
                        <label class="form-check-label fw-semibold" for="hasPractical">
                            <i class="bi bi-flask me-1"></i> Has Practical Component?
                        </label>
                    </div>
                    <small class="text-muted">Enable if this subject includes a practical/lab component</small>
                </div>

                {{-- Theory Marks --}}
                <div class="card bg-light border-0 p-3 mb-3">
                    <h6 class="fw-semibold mb-3"><i class="bi bi-book me-1 text-primary"></i> Theory Marks</h6>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Max Marks <span class="text-danger">*</span></label>
                            <input type="number" name="theory_max_marks" min="1"
                                   value="{{ old('theory_max_marks', isset($subject) ? ($subject->theoryComponent->max_marks ?? 100) : 100) }}"
                                   class="form-control @error('theory_max_marks') is-invalid @enderror">
                            @error('theory_max_marks') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Pass Marks <span class="text-danger">*</span></label>
                            <input type="number" name="theory_pass_marks" min="1"
                                   value="{{ old('theory_pass_marks', isset($subject) ? ($subject->theoryComponent->pass_marks ?? 33) : 33) }}"
                                   class="form-control @error('theory_pass_marks') is-invalid @enderror">
                            @error('theory_pass_marks') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- Practical Marks --}}
                <div id="practicalSection" class="card border-0 p-3 mb-4"
                     style="background:#f3e8ff; display: {{ old('has_practical', $subject->has_practical ?? false) ? 'block' : 'none' }}">
                    <h6 class="fw-semibold mb-3" style="color:#7c3aed;"><i class="bi bi-flask me-1"></i> Practical Marks</h6>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Max Marks</label>
                            <input type="number" name="practical_max_marks" min="1"
                                   value="{{ old('practical_max_marks', isset($subject) ? ($subject->practicalComponent->max_marks ?? 50) : 50) }}"
                                   class="form-control @error('practical_max_marks') is-invalid @enderror">
                            @error('practical_max_marks') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Pass Marks</label>
                            <input type="number" name="practical_pass_marks" min="1"
                                   value="{{ old('practical_pass_marks', isset($subject) ? ($subject->practicalComponent->pass_marks ?? 17) : 17) }}"
                                   class="form-control @error('practical_pass_marks') is-invalid @enderror">
                            @error('practical_pass_marks') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i>
                        {{ isset($subject) ? 'Update Subject' : 'Save Subject' }}
                    </button>
                    <a href="{{ route('master.subjects.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function togglePractical(show) {
    document.getElementById('practicalSection').style.display = show ? 'block' : 'none';
}
</script>
@endpush
@endsection
