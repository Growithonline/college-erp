@extends($layout ?? 'institute.layout')

@section('title', 'Salary Draft')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">Payroll — Salary Draft</h1>
        </div>
    </div>

    {{-- Filters + Generate --}}
    <div class="row mb-4">
        <div class="col-md-8">
            <form method="GET" class="d-flex gap-2 align-items-center">
                <select name="year" class="form-control" style="max-width: 100px;">
                    @for($y = now()->year - 2; $y <= now()->year + 2; $y++)
                        <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                    @endfor
                </select>
                <select name="month" class="form-control" style="max-width: 130px;">
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
        <div class="col-md-4 text-end">
            <button class="btn btn-success" id="generateDraftBtn" onclick="generateDraft()">
                <i class="fas fa-file-invoice me-1"></i> Generate Draft
                <span id="generateSpinner" class="spinner-border spinner-border-sm d-none ms-1"></span>
            </button>
        </div>
    </div>

    {{-- Warnings panel (hidden by default, shown after generate) --}}
    <div id="warningsPanel" class="d-none mb-4">
        <div class="alert alert-warning">
            <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-1"></i> Attendance Warnings</h6>
            <ul id="warningsList" class="mb-0 ps-3 small"></ul>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Records</h6>
                    <h3 class="text-primary">{{ $summary['total_records'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Gross</h6>
                    <h3 class="text-info">
                        ₹{{ number_format($summary['total_basic'] + $summary['total_allowances'], 2) }}
                    </h3>
                    <small class="text-muted">Basic: ₹{{ number_format($summary['total_basic'], 2) }}
                        + OT: ₹{{ number_format($summary['total_allowances'], 2) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Deductions</h6>
                    <h3 class="text-warning">₹{{ number_format($summary['total_deductions'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="card-title text-muted">Net Payable</h6>
                    <h3 class="text-success">₹{{ number_format($summary['total_net_payable'], 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Records Table --}}
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                Salary Details — {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}
            </h5>
            <div class="d-flex align-items-center gap-2">
                @if($summary['total_records'] > 0 && $summary['draft_count'] > 0)
                    <button class="btn btn-sm btn-outline-success" onclick="approveAll()">
                        <i class="fas fa-check-double me-1"></i> Approve All Draft
                    </button>
                @endif
                <span class="badge bg-primary">{{ $summary['draft_count'] }} Draft</span>
                <span class="badge bg-info">{{ $summary['approved_count'] }} Approved</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 20%">Staff Name</th>
                        <th style="width: 18%">Attendance</th>
                        <th style="width: 12%" class="text-end">Basic</th>
                        <th style="width: 10%" class="text-end">OT Allow.</th>
                        <th style="width: 10%" class="text-end">Deductions</th>
                        <th style="width: 12%" class="text-end">Net Payable</th>
                        <th style="width: 9%">Status</th>
                        <th style="width: 9%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary['records'] as $record)
                        @php
                            $isDraft    = $record->status === \App\Models\SalaryRecord::STATUS_DRAFT;
                            $isApproved = $record->status === \App\Models\SalaryRecord::STATUS_APPROVED;
                            $isPending  = $record->status === \App\Models\SalaryRecord::STATUS_PENDING;

                            $badgeClass = match($record->status) {
                                \App\Models\SalaryRecord::STATUS_DRAFT    => 'bg-secondary',
                                \App\Models\SalaryRecord::STATUS_APPROVED => 'bg-info text-dark',
                                \App\Models\SalaryRecord::STATUS_PENDING  => 'bg-warning text-dark',
                                default                                    => 'bg-secondary',
                            };

                            $att = $attendanceSummaries[$record->staff_member_id] ?? null;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $record->staffMember?->name ?? '—' }}</strong><br>
                                <small class="text-muted">{{ $record->staffMember?->staff_category ?? '—' }}</small>
                                @if(!$record->expense_account_id)
                                    <br><small class="text-danger">
                                        <i class="fas fa-exclamation-circle"></i> Expense account missing
                                    </small>
                                @endif
                            </td>
                            <td>
                                @if($att)
                                    <div class="d-flex flex-wrap gap-1">
                                        @if($att['present'] > 0)
                                            <span class="badge bg-success" title="Present">P: {{ $att['present'] }}</span>
                                        @endif
                                        @if($att['absent'] > 0)
                                            <span class="badge bg-danger" title="Absent">A: {{ $att['absent'] }}</span>
                                        @endif
                                        @if($att['half_day'] > 0)
                                            <span class="badge bg-warning text-dark" title="Half Day">HD: {{ $att['half_day'] }}</span>
                                        @endif
                                        @if($att['unpaid_leave'] > 0)
                                            <span class="badge bg-secondary" title="Unpaid Leave">UL: {{ $att['unpaid_leave'] }}</span>
                                        @endif
                                        @if($att['paid_leave'] > 0)
                                            <span class="badge bg-info text-dark" title="Paid Leave">PL: {{ $att['paid_leave'] }}</span>
                                        @endif
                                    </div>
                                    <small class="text-muted">{{ number_format($att['payable_days'], 1) }} payable days</small>
                                @else
                                    <small class="text-muted">No attendance data</small>
                                @endif
                            </td>
                            <td class="text-end">₹{{ number_format($record->basic_salary, 2) }}</td>
                            <td class="text-end">₹{{ number_format($record->allowances, 2) }}</td>
                            <td class="text-end text-danger">₹{{ number_format($record->deductions, 2) }}</td>
                            <td class="text-end fw-bold">₹{{ number_format($record->net_payable, 2) }}</td>
                            <td>
                                <span class="badge {{ $badgeClass }}">{{ ucfirst($record->status) }}</span>
                            </td>
                            <td>
                                @if($isDraft)
                                    <button class="btn btn-sm btn-outline-success"
                                        onclick="approveSalary({{ $record->id }}, this)"
                                        title="Approve">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                @elseif($isApproved || $isPending)
                                    <span class="text-success small">
                                        <i class="fas fa-check-circle"></i> Approved
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No salary records found.
                                <a href="#" onclick="generateDraft(); return false;">Generate draft now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function generateDraft() {
    const btn     = document.getElementById('generateDraftBtn');
    const spinner = document.getElementById('generateSpinner');
    const params  = new URLSearchParams(window.location.search);
    const year    = parseInt(params.get('year'))     || new Date().getFullYear();
    const month   = parseInt(params.get('month'))    || new Date().getMonth() + 1;
    const category = params.get('category')          || null;

    if (!confirm('Is month ka salary draft generate karein?')) return;

    btn.disabled = true;
    spinner.classList.remove('d-none');

    fetch('{{ route(($rp ?? 'finance') . ".payroll.generate-draft") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ year, month, category })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        spinner.classList.add('d-none');

        if (data.success) {
            if (data.warnings && data.warnings.length > 0) {
                const panel = document.getElementById('warningsPanel');
                const list  = document.getElementById('warningsList');
                list.innerHTML = '';
                data.warnings.forEach(w => {
                    const li = document.createElement('li');
                    li.textContent = w.message;
                    list.appendChild(li);
                });
                panel.classList.remove('d-none');
            }
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(() => {
        btn.disabled = false;
        spinner.classList.add('d-none');
        alert('Network error. Please try again.');
    });
}

function approveSalary(recordId, btn) {
    if (!confirm('Yeh salary record approve karein?')) return;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    fetch(`{{ route(($rp ?? 'finance') . '.payroll.approve', ':id') }}`.replace(':id', recordId), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { location.reload(); }
        else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Approve';
            alert('Error: ' + data.message);
        }
    });
}

function approveAll() {
    if (!confirm('Saare draft records approve karein?')) return;

    const draftBtns = document.querySelectorAll('button[onclick^="approveSalary"]');
    const promises  = [];

    draftBtns.forEach(btn => {
        const match = btn.getAttribute('onclick').match(/approveSalary\((\d+)/);
        if (!match) return;
        const recordId = match[1];
        btn.disabled = true;

        promises.push(
            fetch(`{{ route(($rp ?? 'finance') . '.payroll.approve', ':id') }}`.replace(':id', recordId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }).then(r => r.json())
        );
    });

    Promise.all(promises).then(() => location.reload());
}
</script>
@endsection
