@php
    $isStaff = auth()->guard('staff')->check();
    $layout  = $isStaff ? 'staff.layout' : 'institute.layout';
@endphp
@extends($layout)
@section('title', 'Fee Ledger Report')
@section('breadcrumb', 'Reports / Fee Ledger')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-0 fw-bold">Fee Ledger Report</h4>
        <small class="text-muted">Course-wise student fee summary — {{ number_format($summary->total_students) }} students</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="doExport('print')">
            <i class="bi bi-printer me-1"></i> Print All
        </button>
        <button type="button" class="btn btn-outline-success btn-sm" onclick="doExport('csv')">
            <i class="bi bi-filetype-csv me-1"></i> CSV
        </button>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="doExport('excel')">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </button>
        <button type="button" class="btn btn-warning btn-sm" id="pdfBtn" onclick="doPdfExport()">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </button>
    </div>
</div>

{{-- PDF status bar --}}
<div id="pdfStatusBar" class="alert alert-info d-none d-flex align-items-center gap-2 mb-3" role="alert">
    <span id="pdfStatusMsg"></span>
    <a id="pdfDownloadLink" href="#" class="btn btn-sm btn-success ms-auto d-none">
        <i class="bi bi-download me-1"></i> Download PDF
    </a>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    @php
        $cards = [
            ['label' => 'Total Students',    'value' => number_format($summary->total_students),                    'icon' => 'bi-people',               'color' => 'primary'],
            ['label' => 'Total Collected',   'value' => '₹ ' . number_format($summary->total_paid),               'icon' => 'bi-cash-stack',           'color' => 'success'],
            ['label' => 'Total Discount',    'value' => '₹ ' . number_format($summary->total_discount),           'icon' => 'bi-tag',                  'color' => 'info'],
            ['label' => 'Total Fine',        'value' => '₹ ' . number_format($summary->total_fine),               'icon' => 'bi-exclamation-triangle', 'color' => 'warning'],
            ['label' => 'Library Fine Due',  'value' => '₹ ' . number_format($summary->total_library_fine ?? 0),  'icon' => 'bi-book',                 'color' => 'warning'],
            ['label' => 'Total Due',         'value' => '₹ ' . number_format($summary->total_due),               'icon' => 'bi-exclamation-circle',   'color' => 'danger'],
            ['label' => 'Students with Due', 'value' => number_format($summary->due_count),                        'icon' => 'bi-person-x',             'color' => 'danger'],
        ];
    @endphp
    @foreach($cards as $card)
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-{{ $card['color'] }} bg-opacity-10 p-2">
                        <i class="bi {{ $card['icon'] }} text-{{ $card['color'] }} fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">{{ $card['label'] }}</div>
                        <div class="fw-bold">{{ $card['value'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Filter Form --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="" id="filterForm">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold mb-1">Courses</label>
                    <select name="course_ids[]" id="courseSelect" class="form-select form-select-sm" multiple
                            style="height:auto;" size="1">
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}"
                                {{ in_array($course->id, (array)($filters['course_ids'] ?? [])) ? 'selected' : '' }}>
                                {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Session</label>
                    <select name="session_id" class="form-select form-select-sm">
                        <option value="">All Sessions</option>
                        @foreach($sessions as $sess)
                            <option value="{{ $sess->id }}" {{ $filters['session_id'] == $sess->id ? 'selected' : '' }}>
                                {{ $sess->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Search Student</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Name / ID / Mobile"
                           value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-6 col-md-2 d-flex align-items-center gap-2">
                    <div class="form-check mb-0">
                        <input type="checkbox" name="due_only" value="1" id="dueOnly" class="form-check-input"
                               {{ !empty($filters['due_only']) ? 'checked' : '' }}>
                        <label class="form-check-label small" for="dueOnly">Due Only</label>
                    </div>
                </div>
                <div class="col-6 col-md-1 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="{{ route('reports.fee-ledger.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Data Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">
            <i class="bi bi-table me-2 text-primary"></i>
            Student Fee Ledger
        </h6>
        <small class="text-muted">
            Page {{ $students->currentPage() }} of {{ $students->lastPage() }} &mdash;
            {{ number_format($students->total()) }} records
        </small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-bordered mb-0 align-middle" style="font-size:13px;">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3" style="width:36px;">#</th>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Roll No</th>
                        <th>Father Name</th>
                        <th>Mother Name</th>
                        <th>Course</th>
                        <th>Year/Sem</th>
                        <th>Session</th>
                        <th class="text-end">Invoiced</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Discount</th>
                        <th class="text-end">Fine</th>
                        <th class="text-end" style="color:#0891b2;">Lib Fine</th>
                        <th class="text-end">Due</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php $offset = ($students->currentPage() - 1) * $students->perPage(); @endphp
                    @forelse($students as $i => $row)
                    @php
                        $libFine = (float) ($row->library_fine_due ?? 0);
                        $due     = $row->wallet_balance < 0 ? abs($row->wallet_balance) : 0;
                        $credit  = $row->wallet_balance > 0 ? $row->wallet_balance : 0;
                        $yearSem = $row->year_number
                            ? 'Year ' . $row->year_number
                            : ($row->current_semester ? 'Sem ' . $row->current_semester : '—');
                    @endphp
                    <tr class="{{ ($due > 0 || $libFine > 0) ? 'table-warning' : '' }}">
                        <td class="ps-3 text-muted small">{{ $offset + $i + 1 }}</td>
                        <td>
                            <div class="fw-semibold">{{ $row->name }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ $row->mobile }}</div>
                        </td>
                        <td><span class="badge bg-light text-dark border small">{{ $row->student_uid }}</span></td>
                        <td class="small">{{ $row->roll_no ?? '—' }}</td>
                        <td class="small">{{ $row->father_name ?? '—' }}</td>
                        <td class="small">{{ $row->mother_name ?? '—' }}</td>
                        <td>
                            <div class="fw-semibold small">{{ $row->course_name }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ $row->stream_name }}</div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary bg-opacity-10 text-primary border" style="font-size:10px;">
                                {{ $yearSem }}
                            </span>
                        </td>
                        <td class="small text-muted">{{ $row->session_name ?? '—' }}</td>
                        <td class="text-end">₹ {{ number_format($row->total_invoiced) }}</td>
                        <td class="text-end fw-bold text-success">₹ {{ number_format($row->total_paid) }}</td>
                        <td class="text-end text-info small">
                            {{ $row->total_discount > 0 ? '₹ ' . number_format($row->total_discount) : '—' }}
                        </td>
                        <td class="text-end text-warning small">
                            {{ $row->total_fine > 0 ? '₹ ' . number_format($row->total_fine) : '—' }}
                        </td>
                        <td class="text-end small fw-semibold" style="color:#0891b2;">
                            {{ $libFine > 0 ? '₹ ' . number_format($libFine) : '—' }}
                        </td>
                        <td class="text-end fw-bold {{ ($due > 0 || $libFine > 0) ? 'text-danger' : 'text-success' }}">
                            @if($due > 0)
                                ₹ {{ number_format($due) }}
                            @elseif($credit > 0)
                                <span class="text-success small">+₹{{ number_format($credit) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($due > 0 && $libFine > 0)
                                <span class="badge bg-danger">Due + Lib Fine</span>
                            @elseif($due > 0)
                                <span class="badge bg-danger">Due</span>
                            @elseif($libFine > 0)
                                <span class="badge" style="background:#0891b2;">Lib Fine</span>
                            @elseif($row->total_paid > 0)
                                <span class="badge bg-success">Paid</span>
                            @else
                                <span class="badge bg-secondary">No Payment</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Koi record nahi mila. Filter change karke try karo.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($students->hasPages())
    <div class="card-footer bg-white border-top py-2">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Showing {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ number_format($students->total()) }}
            </small>
            {{ $students->links('pagination::bootstrap-5') }}
        </div>
    </div>
    @endif
</div>

{{-- Hidden form for exports (carries current filters) --}}
<form id="exportForm" method="GET" target="_blank" style="display:none;">
    @foreach((array)($filters['course_ids'] ?? []) as $cid)
        <input type="hidden" name="course_ids[]" value="{{ $cid }}">
    @endforeach
    @if(!empty($filters['session_id']))
        <input type="hidden" name="session_id" value="{{ $filters['session_id'] }}">
    @endif
    @if(!empty($filters['search']))
        <input type="hidden" name="search" value="{{ $filters['search'] }}">
    @endif
    @if(!empty($filters['due_only']))
        <input type="hidden" name="due_only" value="1">
    @endif
</form>

<script>
const EXPORT_ROUTES = {
    print:  '{{ route("reports.fee-ledger.print") }}',
    csv:    '{{ route("reports.fee-ledger.export-csv") }}',
    excel:  '{{ route("reports.fee-ledger.export-excel") }}',
};

function doExport(type) {
    const form = document.getElementById('exportForm');
    form.action = EXPORT_ROUTES[type];
    // CSV/Excel should not open in new tab (file download)
    form.target = type === 'print' ? '_blank' : '_self';
    form.submit();
}

let pdfPollTimer = null;

function doPdfExport() {
    const bar  = document.getElementById('pdfStatusBar');
    const msg  = document.getElementById('pdfStatusMsg');
    const link = document.getElementById('pdfDownloadLink');
    const btn  = document.getElementById('pdfBtn');

    bar.classList.remove('d-none', 'alert-success', 'alert-danger');
    bar.classList.add('alert-info');
    link.classList.add('d-none');
    msg.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> PDF ban raha hai, please wait...';
    btn.disabled = true;

    const params = new URLSearchParams(new FormData(document.getElementById('exportForm')));

    fetch('{{ route("reports.fee-ledger.queue-pdf") }}?' + params.toString(), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'ready') {
            // Small dataset — generated instantly
            showPdfReady(data.job_id);
        } else {
            // Large dataset — poll until done
            msg.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> ' + (data.message || 'PDF ban raha hai...');
            pdfPollTimer = setInterval(() => pollPdf(data.job_id), 2000);
        }
    })
    .catch(() => {
        msg.textContent = 'Error: PDF generate nahi hua. Dobara try karo.';
        bar.classList.replace('alert-info', 'alert-danger');
        btn.disabled = false;
    });
}

function showPdfReady(jobId) {
    const bar  = document.getElementById('pdfStatusBar');
    const msg  = document.getElementById('pdfStatusMsg');
    const link = document.getElementById('pdfDownloadLink');
    const btn  = document.getElementById('pdfBtn');

    clearInterval(pdfPollTimer);
    bar.classList.remove('alert-info');
    bar.classList.add('alert-success');
    msg.textContent = 'PDF ready hai! Neeche download karo.';
    link.href = '{{ route("reports.fee-ledger.download-pdf") }}?job_id=' + jobId;
    link.classList.remove('d-none');
    btn.disabled = false;

    // Auto-trigger download
    link.click();
}

function pollPdf(jobId) {
    fetch('{{ route("reports.fee-ledger.pdf-status") }}?job_id=' + jobId, {
        headers: { 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'ready') {
            showPdfReady(jobId);
        } else if (data.status === 'failed') {
            clearInterval(pdfPollTimer);
            const bar = document.getElementById('pdfStatusBar');
            document.getElementById('pdfStatusMsg').textContent = 'PDF failed: ' + (data.message || 'Unknown error');
            bar.classList.replace('alert-info', 'alert-danger');
            document.getElementById('pdfBtn').disabled = false;
        }
    });
}

// Make course select a proper multi-select with checkboxes style
document.getElementById('courseSelect').size = Math.min(
    {{ count($courses) }}, 5
);
</script>

@endsection
