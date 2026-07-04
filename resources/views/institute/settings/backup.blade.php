@extends('institute.layout')
@section('title', 'Backup & Export')
@section('breadcrumb', 'Settings / Backup & Export')

@section('content')

{{-- Success Toast --}}
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:9999;">
    <div id="downloadToast" class="toast align-items-center text-bg-success border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <div>
                    <strong id="toastTitle">Download Started</strong>
                    <div id="toastMessage" class="small opacity-75">Your file is downloading…</div>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Backup & Data Export</h4>
        <small class="text-muted">Only your institute's data will be exported — no other institute's data will be included</small>
    </div>
    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2" style="font-size:13px;">
        <i class="bi bi-shield-check me-1"></i> {{ $institute->name }}
    </span>
</div>

{{-- Info Banner --}}
<div class="alert border-0 mb-4" style="background:#eff6ff;border-left:4px solid #3b82f6 !important;border-radius:10px;">
    <div class="d-flex gap-2">
        <i class="bi bi-info-circle-fill text-primary mt-1"></i>
        <div style="font-size:13px;color:#1e40af;line-height:1.7;">
            <strong>Three types of exports are available:</strong>
            <ul class="mb-0 mt-1 ps-3">
                <li><strong>SQL Backup</strong> — Complete database backup. Use this for disaster recovery.</li>
                <li><strong>Student Report (Excel)</strong> — Students, fee history, library &amp; transport in 4 sheets.</li>
                <li><strong>Financial Report (Excel)</strong> — Income, expenses, salary &amp; staff details in 13 sheets.</li>
            </ul>
        </div>
    </div>
</div>

<div class="row g-4">

    {{-- Card 1: SQL Full Backup --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                         style="width:52px;height:52px;background:#dcfce7;flex-shrink:0;">
                        <i class="bi bi-database-down text-success" style="font-size:22px;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">SQL Full Backup</h6>
                        <small class="text-muted">.sql file download</small>
                    </div>
                </div>

                <p class="small text-muted mb-3" style="line-height:1.6;">
                    Your complete institute data in a single <code>.sql</code> file —
                    students, fees, staff, courses, library, transport and everything else.
                    Use this file to restore data in the event of a cyber attack or data loss.
                </p>

                <div class="mb-4">
                    <p class="small fw-semibold text-dark mb-2">Includes:</p>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach(['Students','Fee Invoices','Staff','Courses','Library','Transport','Expenses','Salary','Notices'] as $tag)
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:11px;">{{ $tag }}</span>
                        @endforeach
                        <span class="badge bg-secondary-subtle text-secondary border" style="font-size:11px;">+ everything</span>
                    </div>
                </div>

                <div class="mt-auto">
                    <a href="{{ route('master.settings.data-export') }}"
                       class="btn btn-success w-100"
                       target="_blank"
                       onclick="showDownloadToast('SQL Backup', 'Full database backup is downloading…')">
                        <i class="bi bi-download me-2"></i>Download .sql Backup
                    </a>
                    <p class="text-muted text-center mt-2 mb-0" style="font-size:11px;">
                        <i class="bi bi-clock me-1"></i>Large institutes may take 1–2 minutes to generate
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 2: Student Report Excel --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                         style="width:52px;height:52px;background:#dbeafe;flex-shrink:0;">
                        <i class="bi bi-people text-primary" style="font-size:22px;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">Student Report</h6>
                        <small class="text-muted">Excel (.xlsx) — 4 sheets</small>
                    </div>
                </div>

                <p class="small text-muted mb-3" style="line-height:1.6;">
                    Complete student records — personal details, fee history across all sessions,
                    library books issued, and transport allocations.
                    Open in Excel to filter, sort, or print as needed.
                </p>

                <div class="mb-4">
                    <p class="small fw-semibold text-dark mb-2">Sheets:</p>
                    <div class="d-flex flex-column gap-1" style="font-size:12px;">
                        <div class="d-flex align-items-center gap-2 p-2 rounded" style="background:#f0f9ff;">
                            <i class="bi bi-table text-primary"></i>
                            <span><strong>Students</strong> — basic info, course, status</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 p-2 rounded" style="background:#f0f9ff;">
                            <i class="bi bi-receipt text-primary"></i>
                            <span><strong>Fee History</strong> — all sessions, all invoices</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 p-2 rounded" style="background:#f0f9ff;">
                            <i class="bi bi-book text-primary"></i>
                            <span><strong>Library</strong> — issued books, fines</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 p-2 rounded" style="background:#f0f9ff;">
                            <i class="bi bi-bus-front text-primary"></i>
                            <span><strong>Transport</strong> — route, stop, payment</span>
                        </div>
                    </div>
                </div>

                <div class="mt-auto">
                    <a href="{{ route('master.settings.backup.students') }}"
                       class="btn btn-primary w-100"
                       onclick="showDownloadToast('Student Report', 'Student data Excel is downloading…')">
                        <i class="bi bi-file-earmark-excel me-2"></i>Download Student Report
                    </a>
                    <p class="text-muted text-center mt-2 mb-0" style="font-size:11px;">
                        <i class="bi bi-info-circle me-1"></i>Excel file with 4 separate sheets
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 3: Financial Report Excel --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                         style="width:52px;height:52px;background:#f3e8ff;flex-shrink:0;">
                        <i class="bi bi-bar-chart-line" style="font-size:22px;color:#7c3aed;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">Financial Report</h6>
                        <small class="text-muted">Excel (.xlsx) — 13 sheets</small>
                    </div>
                </div>

                <p class="small text-muted mb-3" style="line-height:1.6;">
                    Complete financial and operational overview — session-wise income,
                    expenses, salary records, staff, transport, centers, channel partners,
                    and library catalog. Ideal for management and audit purposes.
                </p>

                <div class="mb-4">
                    <p class="small fw-semibold text-dark mb-2">Sheets:</p>
                    <div class="d-flex flex-column gap-1" style="font-size:12px;">
                        @foreach([
                            ['icon'=>'bi-calendar3','label'=>'Sessions Overview','desc'=>'income vs expense summary'],
                            ['icon'=>'bi-cash-stack','label'=>'Fee Collections','desc'=>'all invoices in detail'],
                            ['icon'=>'bi-wallet2','label'=>'Expenses','desc'=>'all expense records'],
                            ['icon'=>'bi-person-badge','label'=>'Salary Records','desc'=>'monthly salary history'],
                            ['icon'=>'bi-people','label'=>'Staff / Library / Drivers','desc'=>'all personnel'],
                            ['icon'=>'bi-bus-front','label'=>'Vehicles / Routes / Stops','desc'=>'transport details'],
                            ['icon'=>'bi-building','label'=>'Centers / Partners / Books','desc'=>'operational data'],
                        ] as $sheet)
                        <div class="d-flex align-items-center gap-2 p-2 rounded" style="background:#faf5ff;">
                            <i class="bi {{ $sheet['icon'] }}" style="color:#7c3aed;"></i>
                            <span><strong>{{ $sheet['label'] }}</strong> — {{ $sheet['desc'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-auto">
                    <a href="{{ route('master.settings.backup.financial') }}"
                       style="background:#7c3aed;border-color:#7c3aed;"
                       class="btn btn-primary w-100"
                       onclick="showDownloadToast('Financial Report', 'Financial data Excel is downloading…')">
                        <i class="bi bi-file-earmark-excel me-2"></i>Download Financial Report
                    </a>
                    <p class="text-muted text-center mt-2 mb-0" style="font-size:11px;">
                        <i class="bi bi-info-circle me-1"></i>Excel file with 13 separate sheets
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Bottom Note --}}
<div class="alert border-0 mt-4" style="background:#fffbeb;border-left:4px solid #f59e0b !important;border-radius:10px;">
    <div class="d-flex gap-2 align-items-start">
        <i class="bi bi-shield-lock-fill text-warning mt-1"></i>
        <div style="font-size:13px;color:#92400e;line-height:1.6;">
            <strong>Privacy Notice:</strong>
            All exports contain data exclusively belonging to <strong>{{ $institute->name }}</strong>.
            Files are generated on demand and delivered directly to your browser — nothing is saved on the server.
            Keep exported files secure and exercise caution before sharing them.
        </div>
    </div>
</div>

<script>
function showDownloadToast(title, message) {
    document.getElementById('toastTitle').textContent   = title + ' — Download Started';
    document.getElementById('toastMessage').textContent = message;
    const toastEl = document.getElementById('downloadToast');
    const toast   = bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 4000 });
    toast.show();
}
</script>

@endsection
