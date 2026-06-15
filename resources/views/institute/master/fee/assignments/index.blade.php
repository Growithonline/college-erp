@extends('institute.layout')
@section('title','Fee Assignment')
@section('breadcrumb','Master / Fee Structure / Assignment')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="mb-0 fw-bold">Fee Assignment</h4><small class="text-muted">Assign fee amounts to courses & subjects</small></div>
</div>
@if($sessions->isEmpty() || $courses->isEmpty() || $feeTypes->isEmpty())
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Pehle complete karo:
    @if($sessions->isEmpty()) <strong>Academic Session</strong> @endif
    @if($courses->isEmpty()) • <strong>Courses</strong> @endif
    @if($feeTypes->isEmpty()) • <strong>Fee Types</strong> @endif
</div>
@else
<div class="row g-4">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-plus-circle me-2 text-primary"></i>Add Fee Assignment</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('master.fee-assignments.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Session <span class="text-danger">*</span></label>
                    <select name="academic_session_id" class="form-select form-select-sm">
                        @foreach($sessions as $s)
                            <option value="{{ $s->id }}" {{ $s->is_active ? 'selected' : '' }}>{{ $s->name }}{{ $s->is_active ? ' (Active)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Fee Type <span class="text-danger">*</span></label>
                    <select name="fee_type_id" class="form-select form-select-sm">
                        <option value="">Select Fee Type</option>
                        @foreach($feeTypes as $ft)
                            <option value="{{ $ft->id }}">{{ $ft->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Applies To <span class="text-danger">*</span></label>
                    <select name="applies_to" class="form-select form-select-sm">
                        <option value="course">Course / Stream</option>
                        <option value="subject">Subject Component</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Stream (Course)</label>
                    <select name="course_stream_id" class="form-select form-select-sm">
                        <option value="">All Streams</option>
                        @foreach($courses as $c)
                            @foreach($c->streams as $st)
                                <option value="{{ $st->id }}">{{ $c->name }} — {{ $st->name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Amount (₹) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" min="0" step="0.01" class="form-control form-control-sm" placeholder="0.00">
                </div>
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-check-lg me-1"></i>Save Assignment
                </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-list-ul me-2"></i>Existing Assignments</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Fee Type</th><th>Stream</th><th>Amount</th><th></th></tr></thead>
                    <tbody>
                        @forelse($assignments as $a)
                        <tr>
                            <td class="small fw-semibold">{{ $a->feeType->name ?? '—' }}</td>
                            <td class="small text-muted">{{ $a->stream ? $a->stream->course->name.' — '.$a->stream->name : 'General' }}</td>
                            <td class="fw-semibold text-success">₹{{ number_format($a->amount, 2) }}</td>
                            <td>
                                <form method="POST" action="{{ route('master.fee-assignments.destroy', $a) }}" onsubmit="return confirm('Remove?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No assignments yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
