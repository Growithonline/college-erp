@extends('institute.layout')
@section('title','Marksheet & Degree Fee Settings')
@section('breadcrumb','Master / Marksheet & Degree / Fee Settings')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Marksheet & Degree Fee Settings</h4>
        <small class="text-muted">Course-wise fixed fee — auto-fills in the Distribution "Charge Fee" popup</small>
    </div>
    <a href="{{ route('master.document-batches.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Batches
    </a>
</div>

<form method="POST" action="{{ route('master.document-fee-settings.update') }}">
    @csrf
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Course</th>
                        <th style="width:220px;">Marksheet Fee (₹)</th>
                        <th style="width:220px;">Degree Fee (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($courses as $course)
                    <tr>
                        <td>{{ $course->name }}</td>
                        <td>
                            <input type="number" class="form-control" min="0" step="0.01"
                                   name="fees[{{ $course->id }}][marksheet_fee]"
                                   value="{{ $course->documentFee->marksheet_fee ?? '' }}"
                                   placeholder="Leave blank if none">
                        </td>
                        <td>
                            <input type="number" class="form-control" min="0" step="0.01"
                                   name="fees[{{ $course->id }}][degree_fee]"
                                   value="{{ $course->documentFee->degree_fee ?? '' }}"
                                   placeholder="Leave blank if none">
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">No courses found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($courses->isNotEmpty())
    <div class="mt-3">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg me-1"></i> Save Fee Settings
        </button>
    </div>
    @endif
</form>
@endsection
