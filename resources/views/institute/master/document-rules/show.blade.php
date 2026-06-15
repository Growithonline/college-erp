@extends('institute.layout')
@section('title', 'Document Rules — ' . $course->name)
@section('breadcrumb', 'Master / Documents / Rules / ' . $course->name)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('master.document-rules.index') }}" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Back to Courses
        </a>
        <h4 class="mb-0 fw-bold mt-1">{{ $course->name }} — Document Rules</h4>
        <small class="text-muted">Define per user type — whether each document is required, optional, or skipped</small>
    </div>
</div>

@if($categories->isEmpty())
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    No active document types found. Please create
    <a href="{{ route('master.document-categories.index') }}">categories</a> and
    <a href="{{ route('master.document-types.index') }}">document types</a> first.
</div>
@else

<form method="POST" action="{{ route('master.document-rules.save', $course) }}">
    @csrf

    {{-- Legend --}}
    <div class="d-flex gap-3 mb-3 flex-wrap">
        <span class="badge bg-danger-subtle text-danger border px-3 py-2">Required — form submission blocked until uploaded</span>
        <span class="badge bg-warning-subtle text-warning border px-3 py-2">Optional — can be uploaded but not mandatory</span>
        <span class="badge bg-secondary-subtle text-secondary border px-3 py-2">Skip — not shown for this user type</span>
    </div>

    @foreach($categories as $category)
    @if($category->documentTypes->isNotEmpty())
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-light border-bottom py-2 d-flex align-items-center gap-2">
            <i class="bi bi-folder2-open text-primary"></i>
            <span class="fw-semibold">{{ $category->name }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th style="min-width:200px;">Document Type</th>
                        @foreach($userTypes as $ut)
                        <th class="text-center" style="min-width:130px;">
                            {{ \App\Models\DocumentUploadRule::USER_TYPE_LABELS[$ut] ?? $ut }}
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($category->documentTypes as $dt)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $dt->name }}</div>
                            <div class="text-muted" style="font-size:0.75rem;">
                                Max {{ number_format($dt->max_size_kb) }}KB &bull; {{ $dt->allowed_formats }}
                            </div>
                        </td>
                        @foreach($userTypes as $ut)
                        @php $current = $rules[$ut][$dt->id] ?? 'skip'; @endphp
                        <td class="text-center">
                            <div class="d-flex flex-column gap-1 align-items-center">
                                @foreach(['required' => ['danger', 'Required'], 'optional' => ['warning', 'Optional'], 'skip' => ['secondary', 'Skip']] as $val => [$color, $label])
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio"
                                           name="rules[{{ $ut }}][{{ $dt->id }}]"
                                           id="rule_{{ $ut }}_{{ $dt->id }}_{{ $val }}"
                                           value="{{ $val }}"
                                           @checked($current === $val)>
                                    <label class="form-check-label badge bg-{{ $color }}-subtle text-{{ $color }} border"
                                           for="rule_{{ $ut }}_{{ $dt->id }}_{{ $val }}">
                                        {{ $label }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    @endforeach

    <div class="d-flex gap-2 justify-content-end mt-3">
        <a href="{{ route('master.document-rules.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i>Save Rules
        </button>
    </div>
</form>
@endif

@endsection
