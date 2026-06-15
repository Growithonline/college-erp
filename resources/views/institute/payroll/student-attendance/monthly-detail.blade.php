@extends($layout ?? 'institute.layout')

@section('title', 'Student Attendance Detail')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <a href="{{ route('finance.payroll.student-attendance.monthly', ['year' => $year, 'month' => $month]) }}" class="btn btn-sm btn-outline-secondary mb-2">
            &larr; Back to Monthly
        </a>
        <h1 class="h3">{{ $student->name }} — {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}</h1>
        <small class="text-muted">Roll No: {{ $student->roll_no ?? '—' }}</small>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card text-center border-success">
                <div class="card-body py-2">
                    <div class="h4 text-success mb-0">{{ $summary['present'] }}</div>
                    <small>Present</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-danger">
                <div class="card-body py-2">
                    <div class="h4 text-danger mb-0">{{ $summary['absent'] }}</div>
                    <small>Absent</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-warning">
                <div class="card-body py-2">
                    <div class="h4 text-warning mb-0">{{ $summary['half_day'] }}</div>
                    <small>Half Day</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-secondary">
                <div class="card-body py-2">
                    <div class="h4 mb-0">{{ $summary['working_days'] }}</div>
                    <small>Working Days</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center {{ $summary['is_shortage'] ? 'border-danger bg-danger bg-opacity-10' : 'border-success' }}">
                <div class="card-body py-2">
                    <div class="h4 mb-0 {{ $summary['is_shortage'] ? 'text-danger' : 'text-success' }}">{{ $summary['percentage'] }}%</div>
                    <small>Attendance %</small>
                </div>
            </div>
        </div>
    </div>

    @if($summary['is_shortage'])
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-1"></i>
            <strong>Attendance Shortage!</strong> {{ $student->name }} ki attendance {{ $summary['percentage'] }}% hai — 75% se kam. Yeh student exam ke liye eligible nahi ho sakta.
        </div>
    @endif

    {{-- Day-wise Records --}}
    <div class="card">
        <div class="card-header bg-light"><strong>Day-wise Records</strong></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $rec)
                        <tr>
                            <td>{{ $rec->attendance_date->format('d M Y') }}</td>
                            <td>{{ $rec->attendance_date->format('l') }}</td>
                            <td>
                                <span class="badge {{ match($rec->status) {
                                    'Present'  => 'bg-success',
                                    'Absent'   => 'bg-danger',
                                    'Half Day' => 'bg-warning text-dark',
                                    'Holiday'  => 'bg-info text-dark',
                                    'Week Off' => 'bg-secondary',
                                    default    => 'bg-secondary'
                                } }}">{{ $rec->status }}</span>
                            </td>
                            <td><small class="text-muted">{{ $rec->remarks ?? '' }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Is mahine ka koi attendance record nahi hai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
