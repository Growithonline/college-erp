@extends('institute.layout')
@section('title', 'Fee Plan Report')
@section('breadcrumb', 'Master / Fee Plans / Report')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-bar-chart me-2 text-primary"></i>Fee Plan Report</h4>
        <small class="text-muted">Student count and installment breakdown per fee plan</small>
    </div>
    <a href="{{ route('master.fee-plans.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Plans
    </a>
</div>

{{-- Course Filter --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2 px-3">
        <form method="GET" class="d-flex align-items-center gap-3">
            <label class="form-label small fw-semibold mb-0">Filter by Course:</label>
            <select name="course_id" class="form-select form-select-sm" style="max-width:260px;" onchange="this.form.submit()">
                <option value="">All Courses</option>
                @foreach($courses as $c)
                <option value="{{ $c->id }}" {{ $courseId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            @if($courseId)
            <a href="{{ route('master.fee-plans.report') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            @endif
        </form>
    </div>
</div>

@if($plans->isEmpty())
<div class="text-center text-muted py-5">
    <i class="bi bi-bar-chart fs-2 d-block mb-3 text-primary opacity-50"></i>
    <h6>No fee plans found</h6>
    <p class="small">Create fee plans from the <a href="{{ route('master.fee-plans.index') }}">Fee Plans</a> page.</p>
</div>
@else

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-primary">{{ $plans->count() }}</div>
            <div class="small text-muted">Total Plans</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-success">{{ $plans->where('is_active', true)->count() }}</div>
            <div class="small text-muted">Active Plans</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-info">{{ $plans->sum('student_count') }}</div>
            <div class="small text-muted">Students Assigned</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-warning">{{ $plans->where('student_count', 0)->count() }}</div>
            <div class="small text-muted">Unused Plans</div>
        </div>
    </div>
</div>

{{-- Plans Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Plan Name</th>
                        <th>Course</th>
                        <th class="text-center">Installments</th>
                        <th class="text-center">Students</th>
                        <th>Installment Schedule</th>
                        <th class="text-center">Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($plans as $plan)
                    <tr>
                        <td class="fw-semibold">{{ $plan->name }}</td>
                        <td>
                            @if($plan->course)
                            <span class="badge bg-info text-dark">{{ $plan->course->name }}</span>
                            @else
                            <span class="badge bg-secondary">All Courses</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary">{{ $plan->installment_count }}</span>
                        </td>
                        <td class="text-center">
                            @if($plan->student_count > 0)
                            <span class="badge bg-success">{{ $plan->student_count }}</span>
                            @else
                            <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($plan->installments as $inst)
                                <span class="badge bg-light text-dark border" style="font-size:10px;">
                                    {{ $inst->label }}
                                    <span class="text-success fw-bold">{{ $inst->percentage }}%</span>
                                    <span class="text-muted">· {{ $inst->dueTriggerLabel() }}</span>
                                </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="text-center">
                            @if($plan->is_active)
                            <span class="badge bg-success">Active</span>
                            @else
                            <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('master.fee-plans.index') }}" class="btn btn-outline-secondary btn-sm py-0 px-2">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="3" class="fw-semibold small text-end">Total Students Assigned:</td>
                        <td class="text-center fw-bold text-success">{{ $plans->sum('student_count') }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endif

@endsection
