@extends($layout ?? 'institute.layout')

@section('title', 'Monthly Attendance Summary')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 d-inline-block me-3">Monthly Attendance Summary</h1>
            @if($isLocked)
                <span class="badge bg-danger fs-6"><i class="fas fa-lock me-1"></i> Locked</span>
            @else
                <span class="badge bg-success fs-6"><i class="fas fa-lock-open me-1"></i> Open</span>
            @endif
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-8">
            <form method="GET" id="filterForm" class="d-flex gap-2 align-items-center">
                <select name="year" id="filterYear" class="form-control" style="max-width: 100px;">
                    @for($y = now()->year - 2; $y <= now()->year + 2; $y++)
                        <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                    @endfor
                </select>
                <select name="month" id="filterMonth" class="form-control" style="max-width: 130px;">
                    @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $mName)
                        <option value="{{ $i + 1 }}" @selected($month == $i + 1)>{{ $mName }}</option>
                    @endforeach
                </select>
                <select name="category" class="form-control" style="max-width: 160px;">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
        </div>
        <div class="col-md-4 text-end d-flex gap-2 justify-content-end">
            @if($isLocked)
                <button class="btn btn-warning" onclick="unlockMonth()">
                    <i class="fas fa-lock-open"></i> Unlock Month
                </button>
            @else
                <button class="btn btn-danger" onclick="lockMonth()">
                    <i class="fas fa-lock"></i> Lock This Month
                </button>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                Staff Attendance Summary —
                {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}
            </h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 20%">Staff Name</th>
                        <th class="text-center" style="width: 9%">Present</th>
                        <th class="text-center" style="width: 9%">Absent</th>
                        <th class="text-center" style="width: 9%">Half Day</th>
                        <th class="text-center" style="width: 9%">Paid Leave</th>
                        <th class="text-center" style="width: 9%">Unpaid Leave</th>
                        <th class="text-center" style="width: 8%">Holiday</th>
                        <th class="text-center" style="width: 8%">Week Off</th>
                        <th class="text-center" style="width: 10%">Payable Days</th>
                        <th style="width: 9%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summaries as $item)
                        @php $summary = $item['summary']; @endphp
                        <tr>
                            <td>
                                <strong>{{ $item['staff']->name }}</strong><br>
                                <small class="text-muted">{{ $item['staff']->staff_category }}</small>
                            </td>
                            <td class="text-center"><span class="badge bg-success">{{ $summary['present'] }}</span></td>
                            <td class="text-center"><span class="badge bg-danger">{{ $summary['absent'] }}</span></td>
                            <td class="text-center"><span class="badge bg-warning text-dark">{{ $summary['half_day'] }}</span></td>
                            <td class="text-center"><span class="badge bg-info text-dark">{{ $summary['paid_leave'] }}</span></td>
                            <td class="text-center"><span class="badge bg-secondary">{{ $summary['unpaid_leave'] }}</span></td>
                            <td class="text-center"><span class="badge bg-dark">{{ $summary['holiday'] }}</span></td>
                            <td class="text-center"><span class="badge bg-light text-dark border">{{ $summary['week_off'] }}</span></td>
                            <td class="text-center fw-bold text-success">{{ number_format($summary['payable_days'], 1) }}</td>
                            <td>
                                <a href="{{ route(($rp ?? 'finance') . '.payroll.attendance.monthly', ['staff_id' => $item['staff']->id, 'year' => $year, 'month' => $month]) }}"
                                   class="btn btn-sm btn-outline-primary" title="View Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                No staff found for this period
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($summaries) > 0)
                @php $sc = collect($summaries); @endphp
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td>Total ({{ count($summaries) }} staff)</td>
                        <td class="text-center">{{ $sc->sum(fn($i) => $i['summary']['present']) }}</td>
                        <td class="text-center">{{ $sc->sum(fn($i) => $i['summary']['absent']) }}</td>
                        <td class="text-center">{{ $sc->sum(fn($i) => $i['summary']['half_day']) }}</td>
                        <td class="text-center">{{ $sc->sum(fn($i) => $i['summary']['paid_leave']) }}</td>
                        <td class="text-center">{{ $sc->sum(fn($i) => $i['summary']['unpaid_leave']) }}</td>
                        <td class="text-center">{{ $sc->sum(fn($i) => $i['summary']['holiday']) }}</td>
                        <td class="text-center">{{ $sc->sum(fn($i) => $i['summary']['week_off']) }}</td>
                        <td class="text-center">{{ number_format($sc->sum(fn($i) => $i['summary']['payable_days']), 1) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<script>
function getLockParams() {
    return {
        year: parseInt(document.getElementById('filterYear').value),
        month: parseInt(document.getElementById('filterMonth').value),
    };
}

function lockMonth() {
    const { year, month } = getLockParams();
    const monthName = document.getElementById('filterMonth').options[document.getElementById('filterMonth').selectedIndex].text;

    if (!confirm(`${monthName} ${year} ka attendance lock karein? Lock hone ke baad edit nahi hoga.`)) return;

    fetch('{{ route(($rp ?? 'finance') . ".payroll.attendance.lock-month") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ year, month, reason: 'month_closed', remarks: 'Month closed for salary generation' })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { location.reload(); }
        else { alert('Error: ' + data.message); }
    });
}

function unlockMonth() {
    const { year, month } = getLockParams();
    const monthName = document.getElementById('filterMonth').options[document.getElementById('filterMonth').selectedIndex].text;

    if (!confirm(`${monthName} ${year} unlock karein? Attendance dubara edit kiya ja sakega.`)) return;

    fetch('{{ route(($rp ?? 'finance') . ".payroll.attendance.unlock-month") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ year, month })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { location.reload(); }
        else { alert('Error: ' + data.message); }
    });
}
</script>
@endsection
