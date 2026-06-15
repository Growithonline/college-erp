@extends($layout ?? 'institute.layout')

@section('title', 'Student Attendance — Monthly')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Student Attendance — Monthly Report</h1>

    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-2">
            <select name="year" class="form-control">
                @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <div class="col-md-2">
            <select name="month" class="form-control">
                @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $m)
                    <option value="{{ $i+1 }}" @selected($month == $i+1)>{{ $m }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="session_id" class="form-control">
                <option value="">All Sessions</option>
                @foreach($sessions as $sess)
                    <option value="{{ $sess->id }}" @selected((int)$sessionId === $sess->id)>{{ $sess->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">Apply</button>
        </div>
    </form>

    <div class="card">
        <div class="card-header bg-light">
            <strong>{{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}</strong>
            <span class="badge bg-danger ms-2">Below 75% = Shortage</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Student</th>
                        <th class="text-center">Present</th>
                        <th class="text-center">Absent</th>
                        <th class="text-center">Half Day</th>
                        <th class="text-center">Working Days</th>
                        <th class="text-center">Attended</th>
                        <th class="text-center">%</th>
                        <th class="text-center">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summaries as $row)
                        @php $s = $row['summary']; @endphp
                        <tr class="{{ $s['is_shortage'] ? 'table-danger' : '' }}">
                            <td>
                                <strong>{{ $row['student']->name }}</strong><br>
                                <small class="text-muted">{{ $row['student']->roll_no ?? '' }}</small>
                            </td>
                            <td class="text-center text-success">{{ $s['present'] }}</td>
                            <td class="text-center text-danger">{{ $s['absent'] }}</td>
                            <td class="text-center">{{ $s['half_day'] }}</td>
                            <td class="text-center">{{ $s['working_days'] }}</td>
                            <td class="text-center">{{ $s['attended_days'] }}</td>
                            <td class="text-center">
                                <span class="badge {{ $s['is_shortage'] ? 'bg-danger' : 'bg-success' }}">
                                    {{ $s['percentage'] }}%
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('finance.payroll.student-attendance.monthly', ['year' => $year, 'month' => $month, 'student_id' => $row['student']->id]) }}"
                                   class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Is session mein koi student nahi mila.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
