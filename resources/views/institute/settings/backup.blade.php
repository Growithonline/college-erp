@extends('institute.layout')
@section('title', 'Backup & Export')
@section('breadcrumb', 'Settings / Backup & Export')

@section('content')

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Backup & Data Export</h4>
        <small class="text-muted">Sirf aapke institute ka data export hoga — kisi aur institute ka data nahi aayega</small>
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
            <strong>Teen tarah ke export available hain:</strong>
            <ul class="mb-0 mt-1 ps-3">
                <li><strong>SQL Backup</strong> — Complete database backup. Disaster recovery ke liye use karo.</li>
                <li><strong>Student Report (Excel)</strong> — Students + fee history + library + transport, 4 sheets mein.</li>
                <li><strong>Financial Report (Excel)</strong> — Income, expenses, salary, employees, 5 sheets mein.</li>
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
                    Institute ka poora data ek <code>.sql</code> file mein milega —
                    students, fees, staff, courses, library, transport sab kuch.
                    Cyber attack ya data loss hone pe is file se restore kar sakte ho.
                </p>

                <div class="mb-4">
                    <p class="small fw-semibold text-dark mb-2">Ismein shamil hai:</p>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach(['Students','Fee Invoices','Staff','Courses','Library','Transport','Expenses','Salary','Notices'] as $tag)
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:11px;">{{ $tag }}</span>
                        @endforeach
                        <span class="badge bg-secondary-subtle text-secondary border" style="font-size:11px;">+ sab kuch</span>
                    </div>
                </div>

                <div class="mt-auto">
                    <a href="{{ route('master.settings.data-export') }}"
                       class="btn btn-success w-100"
                       target="_blank">
                        <i class="bi bi-download me-2"></i>Download .sql Backup
                    </a>
                    <p class="text-muted text-center mt-2 mb-0" style="font-size:11px;">
                        <i class="bi bi-clock me-1"></i>Large institute ka data lene mein 1-2 minute lag sakte hain
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
                    Har student ki poori detail — personal info se lekar fee history,
                    library books, aur transport allocation tak. Excel mein open karke
                    filter, sort, ya print kar sakte ho.
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
                            <span><strong>Fee History</strong> — sabhi sessions ki fees</span>
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
                       class="btn btn-primary w-100">
                        <i class="bi bi-file-earmark-excel me-2"></i>Download Student Report
                    </a>
                    <p class="text-muted text-center mt-2 mb-0" style="font-size:11px;">
                        <i class="bi bi-info-circle me-1"></i>Excel mein 4 alag sheets hongi
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
                        <small class="text-muted">Excel (.xlsx) — 5 sheets</small>
                    </div>
                </div>

                <p class="small text-muted mb-3" style="line-height:1.6;">
                    Institute ki poori financial picture — session-wise income,
                    expenses, salary records aur employee details. Accountant ya
                    management ke saath share karne ke liye best format.
                </p>

                <div class="mb-4">
                    <p class="small fw-semibold text-dark mb-2">Sheets:</p>
                    <div class="d-flex flex-column gap-1" style="font-size:12px;">
                        <div class="d-flex align-items-center gap-2 p-2 rounded" style="background:#faf5ff;">
                            <i class="bi bi-calendar3" style="color:#7c3aed;"></i>
                            <span><strong>Sessions Overview</strong> — income vs expense</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 p-2 rounded" style="background:#faf5ff;">
                            <i class="bi bi-cash-stack" style="color:#7c3aed;"></i>
                            <span><strong>Fee Collections</strong> — all invoices detail</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 p-2 rounded" style="background:#faf5ff;">
                            <i class="bi bi-wallet2" style="color:#7c3aed;"></i>
                            <span><strong>Expenses</strong> — all expense records</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 p-2 rounded" style="background:#faf5ff;">
                            <i class="bi bi-person-badge" style="color:#7c3aed;"></i>
                            <span><strong>Salary Records</strong> — monthly salary</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 p-2 rounded" style="background:#faf5ff;">
                            <i class="bi bi-people" style="color:#7c3aed;"></i>
                            <span><strong>Employees</strong> — staff list with roles</span>
                        </div>
                    </div>
                </div>

                <div class="mt-auto">
                    <a href="{{ route('master.settings.backup.financial') }}"
                       style="background:#7c3aed;border-color:#7c3aed;"
                       class="btn btn-primary w-100">
                        <i class="bi bi-file-earmark-excel me-2"></i>Download Financial Report
                    </a>
                    <p class="text-muted text-center mt-2 mb-0" style="font-size:11px;">
                        <i class="bi bi-info-circle me-1"></i>Excel mein 5 alag sheets hongi
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
            <strong>Privacy Note:</strong>
            Ye exports sirf aapke institute ({{ $institute->name }}) ka data contain karte hain.
            Downloads aapke browser mein seedha aate hain — server pe koi file save nahi hoti.
            Exported files ko secure rakhein aur sharing se pehle sochein.
        </div>
    </div>
</div>

@endsection
