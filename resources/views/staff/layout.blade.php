<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f1f5f9; }
        .sidebar { width:240px; height:100vh; background:#1e293b; position:fixed; top:0; left:0; overflow:hidden; z-index:100; display:flex; flex-direction:column; }
        .sidebar-nav-wrap { flex:1 1 auto; overflow-y:auto; overflow-x:hidden; scrollbar-width:thin; scrollbar-color:#334155 #1e293b; }
        .sidebar-nav-wrap::-webkit-scrollbar { width:4px; }
        .sidebar-nav-wrap::-webkit-scrollbar-track { background:#1e293b; }
        .sidebar-nav-wrap::-webkit-scrollbar-thumb { background:#334155; border-radius:2px; }
        .sidebar-brand { padding:14px 16px; background:#0f172a; border-bottom:1px solid #334155; flex-shrink:0; }
        .sidebar-brand h6 { color:#f8fafc; margin:0; font-size:13px; font-weight:600; }
        .sidebar-brand small { color:#64748b; font-size:11px; }
        .sidebar ul.nav { padding-bottom:20px; }
        /* Top-level nav links */
        .sidebar .nav-link { color:#94a3b8; padding:8px 16px; font-size:13px; display:flex; align-items:center; gap:8px; border-left:3px solid transparent; }
        .sidebar .nav-link:hover { color:#f8fafc; background:#334155; }
        .sidebar .nav-link.active { color:#38bdf8; background:#0f172a; border-left:3px solid #38bdf8; }
        .sidebar .nav-link i { font-size:14px; width:16px; flex-shrink:0; }
        /* Section parent (collapsible group header) */
        .sidebar .group-header { color:#cbd5e1; padding:8px 16px; font-size:13px; display:flex; align-items:center; gap:8px; cursor:pointer; border-left:3px solid transparent; }
        .sidebar .group-header:hover { color:#f8fafc; background:#334155; }
        .sidebar .group-header.active-group { color:#38bdf8; border-left:3px solid #38bdf8; background:#0f172a; }
        .sidebar .group-header i.group-icon { font-size:14px; width:16px; flex-shrink:0; }
        /* Sub-menus */
        .sub-menu { background:#0f172a; border-left:2px solid #334155; margin-left:20px; }
        .sub-menu .nav-link { font-size:12px; padding:6px 12px; color:#64748b; border-left:none; }
        .sub-menu .nav-link:hover { color:#f8fafc; background:transparent; }
        .sub-menu .nav-link.active { color:#38bdf8; background:transparent; }
        .collapse-arrow { margin-left:auto; transition:transform .2s; font-size:11px; }
        [aria-expanded="true"] .collapse-arrow { transform:rotate(180deg); }
        .sidebar { transition: transform .25s ease; }
        .main-content { margin-left:240px; padding:20px; transition: margin-left .25s ease; min-height:100vh; }
        .topbar { background:#fff; border-bottom:1px solid #e2e8f0; padding:8px 20px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:50; }
        /* Sidebar toggle button */
        #sidebarToggle { background:none; border:none; padding:4px 8px; cursor:pointer; color:#64748b; border-radius:6px; line-height:1; }
        #sidebarToggle:hover { background:#f1f5f9; color:#1e293b; }
        #sidebarToggle i { font-size:18px; }
        /* Collapsed state (desktop) */
        body.sidebar-collapsed .sidebar { transform: translateX(-240px); }
        body.sidebar-collapsed .main-content { margin-left: 0; }
        /* Mobile: start collapsed, show as overlay when open */
        @media (max-width: 767px) {
            .sidebar { transform: translateX(-240px); z-index: 1050; }
            .main-content { margin-left: 0; }
            body.sidebar-open .sidebar { transform: translateX(0); }
            .sidebar-backdrop { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1040; }
            body.sidebar-open .sidebar-backdrop { display:block; }
        }
        @media (min-width: 768px) {
            .sidebar-backdrop { display:none !important; }
        }
        @media print {
            .sidebar, .topbar, .no-print { display:none !important; }
            .main-content { margin-left:0 !important; padding:8px !important; }
            body { background:#fff !important; }
        }
        .permission-disabled { opacity: 0.4; pointer-events: none; }
    </style>
    @stack('styles')
</head>
<body>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="sidebar">
    <div class="sidebar-brand">
        @php $inst = $authUser->institute; @endphp
        <div class="d-flex align-items-center gap-2">
            @if($inst && $inst->image)
                <img src="{{ asset('storage/' . $inst->image) }}" alt="{{ $inst->name }}"
                     style="height:32px;width:32px;object-fit:contain;border-radius:6px;background:#1e293b;flex-shrink:0;">
            @else
                <i class="bi bi-mortarboard-fill text-primary" style="font-size:20px;flex-shrink:0;"></i>
            @endif
            <div style="min-width:0;">
                <h6 class="mb-0" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $inst?->name ?? config('app.name') }}</h6>
                <small>
                    @if($authGuard === 'center') Center Portal
                    @elseif($authGuard === 'staff') Staff Portal
                    @else Partner Portal
                    @endif
                </small>
            </div>
        </div>
    </div>

    <div class="sidebar-nav-wrap">
    <ul class="nav flex-column pt-1">

        {{-- Dashboard --}}
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs($authGuard.'.dashboard') ? 'active' : '' }}"
               href="{{ route($authGuard.'.dashboard') }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>

        {{-- Admissions Group --}}
        @php
            $canAdmit = $authGuard === 'staff'
                ? $authUser->canManageAdmissions()
                : ($authUser->can_add_admission ?? false);
            $canView = $authGuard === 'staff'
                ? $authUser->canViewAdmissions()
                : ($authUser->can_view_students ?? false);
        @endphp
        @if($canAdmit || $canView)
        @php
            $admissionsGroupActive = request()->routeIs($authGuard.'.admissions.*') || request()->routeIs($authGuard.'.students.*');
        @endphp
        <li class="nav-item mt-1">
            <a class="group-header {{ $admissionsGroupActive ? 'active-group' : '' }} d-flex"
               data-bs-toggle="collapse" href="#staffAdmissionsGroup" role="button"
               aria-expanded="{{ $admissionsGroupActive ? 'true' : 'false' }}">
                <i class="bi bi-person-plus group-icon"></i>
                <span>Admissions</span>
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ $admissionsGroupActive ? 'show' : '' }}" id="staffAdmissionsGroup">
                <ul class="nav flex-column sub-menu">
                    @if($canAdmit)
                    <li><a class="nav-link {{ request()->routeIs($authGuard.'.admissions.quick*') ? 'active' : '' }}"
                           href="{{ route($authGuard.'.admissions.quick-create') }}">
                        <i class="bi bi-lightning-fill" style="color:#f59e0b;"></i> Quick Register
                    </a></li>
                    @if($authGuard !== 'partner')
                    <li><a class="nav-link {{ request()->routeIs($authGuard.'.admissions.create') ? 'active' : '' }}"
                           href="{{ route($authGuard.'.admissions.create') }}">
                        <i class="bi bi-person-plus"></i> Full Admission
                    </a></li>
                    @endif
                    @endif
                    @if($canView)
                    @php $studRoute = $authGuard === 'staff' ? $authGuard.'.admissions.index' : $authGuard.'.students.index'; @endphp
                    <li><a class="nav-link {{ request()->routeIs($studRoute) ? 'active' : '' }}"
                           href="{{ route($studRoute) }}">
                        <i class="bi bi-people"></i> My Students
                    </a></li>
                    @if($authGuard === 'staff')
                    @if($authUser->canApproveAdmissions())
                    <li><a class="nav-link {{ request()->routeIs('staff.admissions.approvals.*') ? 'active' : '' }}"
                           href="{{ route('staff.admissions.approvals.index') }}">
                        <i class="bi bi-shield-check"></i> Admission Approvals
                    </a></li>
                    @endif
                    <li><a class="nav-link {{ request()->routeIs('staff.students.search') ? 'active' : '' }}"
                           href="{{ route('staff.students.search') }}">
                        <i class="bi bi-search"></i> Global Search
                    </a></li>
                    @if($authUser->hasPermission('student_promote'))
                    <li><a class="nav-link {{ request()->routeIs('staff.admissions.promote.*') ? 'active' : '' }}"
                           href="{{ route('staff.admissions.promote.index') }}">
                        <i class="bi bi-arrow-up-circle"></i> Promote Students
                    </a></li>
                    @endif
                    @endif
                    @endif
                </ul>
            </div>
        </li>
        @endif

        {{-- Fee Group --}}
        @php
            $canCollectFee = $authGuard === 'staff'
                ? $authUser->canCollectFee()
                : ($authUser->can_collect_fee ?? false);
            $canFeeHistory = $authGuard === 'staff'
                ? $authUser->canViewFeeHistory()
                : ($authUser->can_collect_fee ?? false);
            $canFeeWallet = $authGuard === 'staff' ? $authUser->canViewFeeWallet() : false;
            $canAdmissionReports = $authGuard === 'staff' ? $authUser->canViewAdmissionReports() : false;
            $canFeeReports       = $authGuard === 'staff' ? $authUser->canViewFeeReports() : false;
            $canPracticalTokens  = $authGuard === 'staff' ? $authUser->canManagePracticalTokens() : false;
        @endphp
        @if($canCollectFee || $canFeeHistory || $canFeeWallet)
        @php $feeGroupActive = request()->routeIs($authGuard.'.fee.*') || request()->routeIs('staff.fee.*'); @endphp
        <li class="nav-item mt-1">
            <a class="group-header {{ $feeGroupActive ? 'active-group' : '' }} d-flex"
               data-bs-toggle="collapse" href="#staffFeeGroup" role="button"
               aria-expanded="{{ $feeGroupActive ? 'true' : 'false' }}">
                <i class="bi bi-cash-stack group-icon"></i>
                <span>Fee</span>
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ $feeGroupActive ? 'show' : '' }}" id="staffFeeGroup">
                <ul class="nav flex-column sub-menu">
                    @if($canCollectFee)
                    <li><a class="nav-link {{ request()->routeIs($authGuard.'.fee.create') ? 'active' : '' }}"
                           href="{{ route($authGuard.'.fee.create') }}">
                        <i class="bi bi-cash-coin"></i> Collect Fee
                    </a></li>
                    @endif
                    @if($authGuard === 'staff' && $canPracticalTokens)
                    <li><a class="nav-link {{ request()->routeIs('staff.fee.practical-tokens.*') ? 'active' : '' }}"
                           href="{{ route('staff.fee.practical-tokens.index') }}">
                        <i class="bi bi-ticket-perforated"></i> Practical Tokens
                    </a></li>
                    @endif
                    @if($authGuard === 'staff' && $canFeeHistory)
                    <li><a class="nav-link {{ request()->routeIs('staff.fee.index') ? 'active' : '' }}"
                           href="{{ route('staff.fee.index') }}">
                        <i class="bi bi-list-ul"></i> Fee History
                    </a></li>
                    @endif
                </ul>
            </div>
        </li>
        @endif

        {{-- Library Group --}}
        @php
            $canLibraryView         = $authGuard === 'staff' ? $authUser->canViewLibrary() : false;
            $canLibraryManage       = $authGuard === 'staff' ? $authUser->canManageLibrary() : false;
            $canLibraryIssue        = $authGuard === 'staff' ? $authUser->canIssueLibraryBooks() : false;
            $canLibraryReports      = $authGuard === 'staff' ? $authUser->canViewLibraryReports() : false;
            $canLibraryMembers      = $authGuard === 'staff' ? $authUser->canManageLibraryMembers() : false;
            $canLibraryReservations = $authGuard === 'staff' ? $authUser->canManageLibraryReservations() : false;
            $canLibraryNoDue        = $authGuard === 'staff' ? $authUser->canGenerateLibraryNoDue() : false;
        @endphp
        @if($canLibraryView)
        @php $libraryGroupActive = request()->routeIs('staff.library.*'); @endphp
        <li class="nav-item mt-1">
            <a class="group-header {{ $libraryGroupActive ? 'active-group' : '' }} d-flex"
               data-bs-toggle="collapse" href="#staffLibraryGroup" role="button"
               aria-expanded="{{ $libraryGroupActive ? 'true' : 'false' }}">
                <i class="bi bi-journal-bookmark group-icon"></i>
                <span>Library</span>
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ $libraryGroupActive ? 'show' : '' }}" id="staffLibraryGroup">
                <ul class="nav flex-column sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('staff.library.dashboard') ? 'active' : '' }}"
                           href="{{ route('staff.library.dashboard') }}">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('staff.library.books.*') ? 'active' : '' }}"
                           href="{{ route('staff.library.books.index') }}">
                        <i class="bi bi-book"></i> Books
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('staff.library.members.*') ? 'active' : '' }} {{ !$canLibraryMembers ? 'permission-disabled' : '' }}"
                           href="{{ $canLibraryMembers ? route('staff.library.members.index') : '#' }}">
                        <i class="bi bi-person-vcard"></i> Members
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('staff.library.circulation.*') ? 'active' : '' }} {{ !$canLibraryIssue ? 'permission-disabled' : '' }}"
                           href="{{ $canLibraryIssue ? route('staff.library.circulation.index') : '#' }}">
                        <i class="bi bi-arrow-left-right"></i> Issue / Return
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('staff.library.reservations.*') ? 'active' : '' }} {{ !$canLibraryReservations ? 'permission-disabled' : '' }}"
                           href="{{ $canLibraryReservations ? route('staff.library.reservations.index') : '#' }}">
                        <i class="bi bi-bookmark-check"></i> Reservations
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('staff.library.fines.*') ? 'active' : '' }} {{ !$canLibraryIssue ? 'permission-disabled' : '' }}"
                           href="{{ $canLibraryIssue ? route('staff.library.fines.index') : '#' }}">
                        <i class="bi bi-cash-coin"></i> Fine Collection
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('staff.library.no-due.*') ? 'active' : '' }} {{ !$canLibraryNoDue ? 'permission-disabled' : '' }}"
                           href="{{ $canLibraryNoDue ? route('staff.library.no-due.index') : '#' }}">
                        <i class="bi bi-patch-check"></i> No Dues
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('staff.library.reports.*') ? 'active' : '' }} {{ !$canLibraryReports ? 'permission-disabled' : '' }}"
                           href="{{ $canLibraryReports ? route('staff.library.reports.index') : '#' }}">
                        <i class="bi bi-bar-chart-line"></i> Reports
                    </a></li>
                    @if($canLibraryManage)
                    <li><a class="nav-link {{ request()->routeIs('staff.library.subjects.*') ? 'active' : '' }}"
                           href="{{ route('staff.library.subjects.index') }}">
                        <i class="bi bi-journal-text"></i> Subjects
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('staff.library.vendors.*') ? 'active' : '' }}"
                           href="{{ route('staff.library.vendors.index') }}">
                        <i class="bi bi-truck"></i> Vendors
                    </a></li>
                    @endif
                </ul>
            </div>
        </li>
        @endif

        {{-- Finance Group --}}
        @if($authGuard === 'staff' && $authUser->canViewFinance())
        @php
            $canExpenseCreate = $authUser->canCreateExpense();
            $canSalaryView    = $authUser->canManageSalary() || $authUser->canViewFinance();
            $financeGroupActive = request()->routeIs('staff.finance.*') && !request()->routeIs('staff.finance.payroll.*');
        @endphp
        <li class="nav-item mt-1">
            <a class="group-header {{ $financeGroupActive ? 'active-group' : '' }} d-flex"
               data-bs-toggle="collapse" href="#staffFinanceGroup" role="button"
               aria-expanded="{{ $financeGroupActive ? 'true' : 'false' }}">
                <i class="bi bi-cash-coin group-icon"></i>
                <span>Finance</span>
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ $financeGroupActive ? 'show' : '' }}" id="staffFinanceGroup">
                <ul class="nav flex-column sub-menu">
                    @if($canExpenseCreate)
                    <li><a class="nav-link {{ request()->routeIs('staff.finance.expenses.create') ? 'active' : '' }}"
                           href="{{ route('staff.finance.expenses.create') }}">
                        <i class="bi bi-plus-circle text-danger"></i> Add Expense
                    </a></li>
                    @endif
                    @if($authUser->canViewFinanceReports())
                    <li><a class="nav-link {{ request()->routeIs('staff.finance.reports.income-book') ? 'active' : '' }}"
                           href="{{ route('staff.finance.reports.income-book') }}">
                        <i class="bi bi-graph-up-arrow text-success"></i> Income Book
                    </a></li>
                    @endif
                    <li><a class="nav-link {{ request()->routeIs('staff.finance.expenses.index') ? 'active' : '' }}"
                           href="{{ route('staff.finance.expenses.index') }}">
                        <i class="bi bi-receipt-cutoff text-danger"></i> Expense Book
                    </a></li>
                    @if($canSalaryView)
                    <li><a class="nav-link {{ request()->routeIs('staff.finance.salary.index') ? 'active' : '' }}"
                           href="{{ route('staff.finance.salary.index') }}">
                        <i class="bi bi-person-workspace text-primary"></i> Salary Book
                    </a></li>
                    @endif
                    @if($authUser->canViewFinanceReports())
                    <li><a class="nav-link {{ request()->routeIs('staff.finance.reports.ledger') ? 'active' : '' }}"
                           href="{{ route('staff.finance.reports.ledger') }}">
                        <i class="bi bi-journal-richtext text-primary"></i> Ledger
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('staff.finance.reports.day-book') ? 'active' : '' }}"
                           href="{{ route('staff.finance.reports.day-book') }}">
                        <i class="bi bi-calendar2-day text-primary"></i> Day Book
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('staff.finance.reports.trial-balance') ? 'active' : '' }}"
                           href="{{ route('staff.finance.reports.trial-balance') }}">
                        <i class="bi bi-list-check text-primary"></i> Trial Balance
                    </a></li>
                    @endif
                </ul>
            </div>
        </li>
        @endif

        {{-- Payroll Group --}}
        @if($authGuard === 'staff' && $authUser->canViewPayroll())
        @php
            $canAttendanceMark  = $authUser->canMarkAttendance();
            $canAttendanceView  = $authUser->canViewAttendance();
            $canPayrollGenerate = $authUser->canGeneratePayroll();
            $canPayrollApprove  = $authUser->canApprovePayroll();
            $payrollGroupActive = request()->routeIs('staff.finance.payroll.*');
        @endphp
        <li class="nav-item mt-1">
            <a class="group-header {{ $payrollGroupActive ? 'active-group' : '' }} d-flex"
               data-bs-toggle="collapse" href="#staffPayrollGroup" role="button"
               aria-expanded="{{ $payrollGroupActive ? 'true' : 'false' }}">
                <i class="bi bi-wallet2 group-icon"></i>
                <span>Payroll</span>
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ $payrollGroupActive ? 'show' : '' }}" id="staffPayrollGroup">
                <ul class="nav flex-column sub-menu">
                    @if($canAttendanceMark)
                    <li><a class="nav-link {{ request()->routeIs('staff.finance.payroll.attendance.daily') ? 'active' : '' }}"
                           href="{{ route('staff.finance.payroll.attendance.daily') }}">
                        <i class="bi bi-calendar-check" style="color:#10b981;"></i> Mark Attendance
                    </a></li>
                    @endif
                    @if($canAttendanceView)
                    <li><a class="nav-link {{ request()->routeIs('staff.finance.payroll.attendance.monthly') ? 'active' : '' }}"
                           href="{{ route('staff.finance.payroll.attendance.monthly') }}">
                        <i class="bi bi-calendar3-week text-secondary"></i> Monthly Summary
                    </a></li>
                    @endif
                    @if($canPayrollGenerate || $canPayrollApprove)
                    <li><a class="nav-link {{ request()->routeIs('staff.finance.payroll.draft-view') ? 'active' : '' }}"
                           href="{{ route('staff.finance.payroll.draft-view') }}">
                        <i class="bi bi-file-earmark-spreadsheet text-info"></i> Payroll Draft
                    </a></li>
                    @endif
                </ul>
            </div>
        </li>
        @endif

        {{-- Statement Group --}}
        @if($authGuard === 'staff' && $authUser->canViewStatements())
        @php $statementGroupActive = request()->routeIs('staff.statement.*'); @endphp
        <li class="nav-item mt-1">
            <a class="group-header {{ $statementGroupActive ? 'active-group' : '' }} d-flex"
               data-bs-toggle="collapse" href="#staffStatementGroup" role="button"
               aria-expanded="{{ $statementGroupActive ? 'true' : 'false' }}">
                <i class="bi bi-file-earmark-text group-icon"></i>
                <span>Statement</span>
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ $statementGroupActive ? 'show' : '' }}" id="staffStatementGroup">
                <ul class="nav flex-column sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('staff.statement.balance') ? 'active' : '' }}"
                           href="{{ route('staff.statement.balance') }}">
                        <i class="bi bi-wallet2"></i> Student Balance
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('staff.statement.fee-record') ? 'active' : '' }}"
                           href="{{ route('staff.statement.fee-record') }}">
                        <i class="bi bi-receipt"></i> Fee Submit Record
                    </a></li>
                </ul>
            </div>
        </li>
        @endif

        {{-- Reports Group --}}
        @if($authGuard === 'staff' && ($canFeeReports || $canAdmissionReports))
        @php $reportsGroupActive = request()->routeIs('staff.reports.*'); @endphp
        <li class="nav-item mt-1">
            <a class="group-header {{ $reportsGroupActive ? 'active-group' : '' }} d-flex"
               data-bs-toggle="collapse" href="#staffReportsGroup" role="button"
               aria-expanded="{{ $reportsGroupActive ? 'true' : 'false' }}">
                <i class="bi bi-bar-chart-line group-icon"></i>
                <span>Reports</span>
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ $reportsGroupActive ? 'show' : '' }}" id="staffReportsGroup">
                <ul class="nav flex-column sub-menu">
                    @if($canFeeReports)
                    <li><a class="nav-link {{ request()->routeIs('staff.reports.fee-due-list') ? 'active' : '' }}"
                           href="{{ route('staff.reports.fee-due-list') }}">
                        <i class="bi bi-exclamation-circle"></i> Fee Due List
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('staff.reports.fee-collection') ? 'active' : '' }}"
                           href="{{ route('staff.reports.fee-collection') }}">
                        <i class="bi bi-cash-stack"></i> Fee Collection
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('staff.reports.cancelled-fee') ? 'active' : '' }}"
                           href="{{ route('staff.reports.cancelled-fee') }}">
                        <i class="bi bi-x-octagon"></i> Cancelled Fee
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('staff.reports.daily-collection') ? 'active' : '' }}"
                           href="{{ route('staff.reports.daily-collection') }}">
                        <i class="bi bi-calendar3"></i> Daily / Monthly
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('staff.reports.semester-wise') ? 'active' : '' }}"
                           href="{{ route('staff.reports.semester-wise') }}">
                        <i class="bi bi-layers"></i> Semester Wise
                    </a></li>
                    @endif
                    @if($canAdmissionReports)
                    <li><a class="nav-link {{ request()->routeIs('staff.reports.admission') ? 'active' : '' }}"
                           href="{{ route('staff.reports.admission') }}">
                        <i class="bi bi-bar-chart-line"></i> Admission Report
                    </a></li>
                    @endif
                </ul>
            </div>
        </li>
        @endif

        {{-- Notices --}}
        @if($authGuard === 'staff')
        <li class="nav-item mt-1">
            <a class="nav-link {{ request()->routeIs('staff.notices.*') ? 'active' : '' }}"
               href="{{ route('staff.notices.index') }}">
                <i class="bi bi-megaphone"></i> Notices
            </a>
        </li>
        @endif

        {{-- Staff Management --}}
        @if($authGuard === 'staff' && $authUser->canManageStaff())
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.staff-manage.*') ? 'active' : '' }}"
               href="{{ route('staff.staff-manage.index') }}">
                <i class="bi bi-people-fill"></i> Staff Members
            </a>
        </li>
        @endif

        {{-- Account --}}
        <li class="nav-item mt-1">
            <div class="px-3 py-1" style="color:#475569; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.8px;">Account</div>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs($authGuard.'.change-password') ? 'active' : '' }}"
               href="{{ route($authGuard.'.change-password') }}">
                <i class="bi bi-key"></i> Change Password
            </a>
        </li>
        <li class="nav-item">
            <form method="POST" action="{{ route($authGuard.'.logout') }}">
                @csrf
                <button class="nav-link w-100 text-start border-0 bg-transparent" style="color:#ef4444;">
                    <i class="bi bi-box-arrow-left"></i> Logout
                </button>
            </form>
        </li>

    </ul>
    </div>{{-- end sidebar-nav-wrap --}}

    {{-- Gaurangi Branding --}}
    <div style="flex-shrink:0; border-top:1px solid #334155; padding:10px 14px; display:flex; align-items:center; gap:9px; background:#0f172a;">
        <img src="{{ asset('images/logog.png') }}" alt="Gaurangi" style="height:26px; width:auto; object-fit:contain; flex-shrink:0; opacity:0.85;">
        <span style="font-size:10px; color:#64748b; line-height:1.35;">Developed &amp; Maintained by<br><span style="color:#94a3b8; font-weight:600;">Gaurangi Technologies</span></span>
    </div>

</div>

{{-- Main --}}
<div class="main-content">
    <div class="topbar mb-4 rounded shadow-sm">
        <div class="d-flex align-items-center gap-2" style="min-width:0;flex:1;">
            <button id="sidebarToggle" title="Toggle sidebar">
                <i class="bi bi-list"></i>
            </button>
            <small class="text-muted fw-semibold text-truncate">@yield('breadcrumb', 'Dashboard')</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            {{-- Active Session badge --}}
            @php
                $activeSession = \App\Models\AcademicSession::where('institute_id', $authUser->institute_id)
                    ->where('is_active', true)->first();
            @endphp
            @if($activeSession)
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 d-none d-sm-inline-flex align-items-center"
                      style="font-size:11px;">
                    <i class="bi bi-calendar-check me-1"></i>{{ $activeSession->name }}
                </span>
            @endif

            {{-- Role chip --}}
            @php
                $roleColors = ['center'=>'#185FA5','staff'=>'#1D9E75','partner'=>'#854F0B'];
                $roleLabels = ['center'=>'Center','staff'=>'Staff','partner'=>'Partner'];
                $rc = $roleColors[$authGuard] ?? '#64748b';
                $rl = $roleLabels[$authGuard] ?? 'User';
            @endphp
            <span class="d-none d-sm-inline-flex align-items-center gap-1 px-2 py-1 rounded-pill"
                  style="font-size:11px;font-weight:500;background:{{ $rc }}20;color:{{ $rc }};">
                <i class="bi bi-shield-check" style="font-size:11px;"></i>{{ $rl }}
            </span>

            {{-- Notices bell --}}
            @php
                $staffNoticeCount = \App\Models\Notice::forRole($authUser->institute_id, 'staff')
                    ->whereDoesntHave('reads', fn($q) => $q->where('reader_type','staff')->where('reader_id',$authUser->id))
                    ->count();
            @endphp
            <a href="{{ route('staff.notices.index') }}"
               class="position-relative text-decoration-none text-muted d-flex align-items-center"
               title="Notices">
                <i class="bi bi-bell" style="font-size:16px;"></i>
                @if($staffNoticeCount > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                          style="font-size:9px;padding:2px 5px;">
                        {{ $staffNoticeCount > 9 ? '9+' : $staffNoticeCount }}
                    </span>
                @endif
            </a>

            {{-- User avatar + name + logout --}}
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-success d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                     style="width:28px;height:28px;font-size:11px;">
                    {{ strtoupper(substr($authUser->name, 0, 1)) }}
                </div>
                <small class="text-muted fw-semibold d-none d-md-inline">{{ $authUser->name }}</small>
                <form method="POST" action="{{ route($authGuard.'.logout') }}" class="mb-0">
                    @csrf
                    <button type="submit"
                            class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                            style="font-size:11px;padding:3px 8px;">
                        <i class="bi bi-box-arrow-right"></i>
                        <span class="d-none d-sm-inline">Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    @if(session('success'))
    <script>window.__flashToast = { message: @json(session('success')), type: 'success' };</script>
    @elseif(session('error'))
    <script>window.__flashToast = { message: @json(session('error')), type: 'danger' };</script>
    @elseif($errors->any())
    <script>window.__flashToast = { message: @json($errors->first()), type: 'danger' };</script>
    @endif

    @yield('content')
</div>

{{-- Global Toast Container --}}
<div id="toast-container" style="position:fixed;bottom:28px;right:28px;z-index:9999;display:flex;flex-direction:column;gap:10px;min-width:320px;max-width:400px;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function () {
    var cfg = {
        success: { bg:'#f0fdf4', border:'#22c55e', icon:'✓', iconBg:'#22c55e', title:'Success' },
        danger:  { bg:'#fef2f2', border:'#ef4444', icon:'✕', iconBg:'#ef4444', title:'Error' },
        warning: { bg:'#fffbeb', border:'#f59e0b', icon:'!', iconBg:'#f59e0b', title:'Warning' },
    };

    window.showToast = function (message, type, duration) {
        type     = type     || 'danger';
        duration = duration || 4500;
        var c   = cfg[type] || cfg.danger;
        var box = document.getElementById('toast-container');

        var t = document.createElement('div');
        t.setAttribute('data-toast','1');
        t.style.cssText = [
            'background:'+c.bg,
            'border:1px solid '+c.border,
            'border-left:4px solid '+c.border,
            'border-radius:12px',
            'box-shadow:0 8px 32px rgba(0,0,0,0.12)',
            'padding:14px 16px 10px',
            'display:flex',
            'gap:12px',
            'align-items:flex-start',
            'opacity:0',
            'transform:translateY(16px)',
            'transition:opacity 0.28s ease,transform 0.28s ease',
            'overflow:hidden',
            'position:relative',
        ].join(';');

        t.innerHTML =
            '<div style="width:28px;height:28px;border-radius:50%;background:'+c.iconBg+';color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;">'+c.icon+'</div>'+
            '<div style="flex:1;min-width:0;">'+
                '<div style="font-size:13px;font-weight:700;color:#1e293b;margin-bottom:2px;">'+c.title+'</div>'+
                '<div style="font-size:13px;color:#475569;line-height:1.45;word-break:break-word;">'+message+'</div>'+
                '<div class="toast-bar" style="height:3px;border-radius:2px;background:'+c.border+';margin-top:10px;width:100%;transform-origin:left;transition:width linear '+duration+'ms;"></div>'+
            '</div>'+
            '<button onclick="dismissToast(this.closest(\'[data-toast]\'))" style="background:none;border:none;padding:0;cursor:pointer;color:#94a3b8;font-size:16px;line-height:1;flex-shrink:0;margin-top:-2px;">&#x2715;</button>';

        box.appendChild(t);

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                t.style.opacity   = '1';
                t.style.transform = 'translateY(0)';
                var bar = t.querySelector('.toast-bar');
                if (bar) bar.style.width = '0%';
            });
        });

        var timer = setTimeout(function () { dismissToast(t); }, duration);
        t.__timer = timer;
    };

    window.dismissToast = function (t) {
        if (!t || t.__dismissed) return;
        t.__dismissed = true;
        clearTimeout(t.__timer);
        t.style.opacity   = '0';
        t.style.transform = 'translateY(8px)';
        setTimeout(function () { if (t.parentNode) t.parentNode.removeChild(t); }, 300);
    };

    if (window.__flashToast) {
        showToast(window.__flashToast.message, window.__flashToast.type);
    }
})();
</script>

<script>
(function () {
    var body     = document.body;
    var backdrop = document.getElementById('sidebarBackdrop');
    var btn      = document.getElementById('sidebarToggle');

    if (window.innerWidth >= 768) {
        if (localStorage.getItem('staffSidebarCollapsed') === '1') {
            body.classList.add('sidebar-collapsed');
        }
    }

    btn.addEventListener('click', function () {
        if (window.innerWidth < 768) {
            body.classList.toggle('sidebar-open');
        } else {
            body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('staffSidebarCollapsed',
                body.classList.contains('sidebar-collapsed') ? '1' : '0');
        }
    });

    backdrop.addEventListener('click', function () {
        body.classList.remove('sidebar-open');
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 768) {
            body.classList.remove('sidebar-open');
            if (localStorage.getItem('staffSidebarCollapsed') === '1') {
                body.classList.add('sidebar-collapsed');
            } else {
                body.classList.remove('sidebar-collapsed');
            }
        } else {
            body.classList.remove('sidebar-collapsed');
        }
    });
})();
</script>

@stack('scripts')
</body>
</html>
