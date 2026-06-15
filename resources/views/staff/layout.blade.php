<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { margin: 0; background: #f8fafc; font-size: 14px; }
        .sidebar {
            width: 220px; height: 100vh; background: #1e293b;
            position: fixed; top: 0; left: 0; overflow-y: auto;
            z-index: 100; scrollbar-width: thin; scrollbar-color: #334155 #1e293b;
        }
        .sidebar-brand { padding: 14px 16px; background: #0f172a; border-bottom: 1px solid #334155; }
        .sidebar-brand h6 { color: #f8fafc; margin: 0; font-size: 13px; font-weight: 600; }
        .sidebar-brand small { font-size: 10px; }
        .sidebar .nav-link {
            color: #94a3b8; padding: 7px 16px; font-size: 12.5px;
            display: flex; align-items: center; gap: 8px;
        }
        .sidebar .nav-link:hover { color: #f8fafc; background: #334155; }
        .sidebar .nav-link.active { color: #38bdf8; background: #0f172a; border-left: 3px solid #38bdf8; }
        .sidebar .nav-link i { font-size: 14px; width: 16px; }
        .nav-section { color: #475569; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; padding: 12px 16px 4px; }
        .sidebar-badge { font-size: 9px; padding: 2px 6px; border-radius: 99px; margin-left: auto; }
        .main-content { margin-left: 220px; min-height: 100vh; }
        .topbar {
            background: #fff; border-bottom: 1px solid #e2e8f0;
            padding: 10px 24px; display: flex; align-items: center;
            justify-content: space-between; position: sticky; top: 0; z-index: 50;
        }
        .role-chip {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 11px; padding: 3px 10px; border-radius: 99px;
            font-weight: 500;
        }
        .page-content { padding: 24px; }
        .permission-disabled { opacity: 0.4; pointer-events: none; }
    </style>
    @stack('styles')
</head>
<body>

{{-- Sidebar --}}
<div class="sidebar">
    <div class="sidebar-brand">
        <h6>{{ $authUser->institute?->name ?? config('app.name') }}</h6>
        <small style="color:#64748b;">
            @if($authGuard === 'center') Center Portal
            @elseif($authGuard === 'staff') Staff Portal
            @else Partner Portal
            @endif
        </small>
    </div>

    <ul class="nav flex-column mt-2">
        {{-- Dashboard --}}
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs($authGuard.'.dashboard') ? 'active' : '' }}"
               href="{{ route($authGuard.'.dashboard') }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>

        {{-- Admissions --}}
        @php
            $canAdmit = $authGuard === 'staff'
                ? $authUser->canManageAdmissions()
                : ($authUser->can_add_admission ?? false);
        @endphp
        @if($canAdmit)
        <div class="nav-section">Admissions</div>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs($authGuard.'.admissions.quick*') ? 'active' : '' }}"
               href="{{ route($authGuard.'.admissions.quick-create') }}">
                <i class="bi bi-lightning-fill" style="color:#f59e0b;"></i> Quick Register
            </a>
        </li>
        @if($authGuard !== 'partner')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs($authGuard.'.admissions.create') ? 'active' : '' }}"
               href="{{ route($authGuard.'.admissions.create') }}">
                <i class="bi bi-person-plus"></i> Full Admission
            </a>
        </li>
        @endif
        @endif

        {{-- Students --}}
        @php
            $canView = $authGuard === 'staff'
                ? $authUser->canViewAdmissions()
                : ($authUser->can_view_students ?? false);
        @endphp
        @if($canView)
        @if(!$canAdmit)<div class="nav-section">Students</div>@endif
        <li class="nav-item">
            @php $studRoute = $authGuard === 'staff' ? $authGuard.'.admissions.index' : $authGuard.'.students.index'; @endphp
            <a class="nav-link {{ request()->routeIs($studRoute) ? 'active' : '' }}"
               href="{{ route($studRoute) }}">
                <i class="bi bi-people"></i> My Students
            </a>
        </li>
        @if($authGuard === 'staff')
        @if($authUser->canApproveAdmissions())
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.admissions.approvals.*') ? 'active' : '' }}"
               href="{{ route('staff.admissions.approvals.index') }}">
                <i class="bi bi-shield-check"></i> Admission Approvals
            </a>
        </li>
        @endif
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.students.search') ? 'active' : '' }}"
               href="{{ route('staff.students.search') }}">
                <i class="bi bi-search"></i> Global Search
            </a>
        </li>
        @if($authUser->hasPermission('student_promote'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.admissions.promote.*') ? 'active' : '' }}"
               href="{{ route('staff.admissions.promote.index') }}">
                <i class="bi bi-arrow-up-circle"></i> Promote Students
            </a>
        </li>
        @endif
        @endif
        @endif

        {{-- Fee --}}
        @php
            $canCollectFee = $authGuard === 'staff'
                ? $authUser->canCollectFee()
                : ($authUser->can_collect_fee ?? false);
            $canFeeHistory = $authGuard === 'staff'
                ? $authUser->canViewFeeHistory()
                : ($authUser->can_collect_fee ?? false);
            $canFeeWallet = $authGuard === 'staff'
                ? $authUser->canViewFeeWallet()
                : false;
            $canReports = $authGuard === 'staff'
                ? ($authUser->canViewFeeReports() || $authUser->canViewAdmissionReports())
                : false;
            $canAdmissionReports = $authGuard === 'staff' ? $authUser->canViewAdmissionReports() : false;
            $canFeeReports       = $authGuard === 'staff' ? $authUser->canViewFeeReports() : false;
            $canPracticalTokens  = $authGuard === 'staff' ? $authUser->canManagePracticalTokens() : false;
        @endphp
        @if($canCollectFee || $canFeeHistory || $canFeeWallet)
        <div class="nav-section">Fee</div>
        @if($canCollectFee)
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs($authGuard.'.fee.create') ? 'active' : '' }}"
               href="{{ route($authGuard.'.fee.create') }}">
                <i class="bi bi-cash-coin"></i> Collect Fee
            </a>
        </li>
        @endif
        @if($authGuard === 'staff' && $canPracticalTokens)
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.fee.practical-tokens.*') ? 'active' : '' }}"
               href="{{ route('staff.fee.practical-tokens.index') }}">
                <i class="bi bi-ticket-perforated"></i> Practical Tokens
            </a>
        </li>
        @endif
        @if($authGuard === 'staff' && $canFeeHistory)
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.fee.index') ? 'active' : '' }}"
               href="{{ route('staff.fee.index') }}">
                <i class="bi bi-list-ul"></i> Fee History
            </a>
        </li>
        @endif
        @endif

        {{-- Library --}}
        @php
            $canLibraryView = $authGuard === 'staff' ? $authUser->canViewLibrary() : false;
            $canLibraryManage = $authGuard === 'staff' ? $authUser->canManageLibrary() : false;
            $canLibraryIssue = $authGuard === 'staff' ? $authUser->canIssueLibraryBooks() : false;
            $canLibraryReports = $authGuard === 'staff' ? $authUser->canViewLibraryReports() : false;
            $canLibraryMembers = $authGuard === 'staff' ? $authUser->canManageLibraryMembers() : false;
            $canLibraryReservations = $authGuard === 'staff' ? $authUser->canManageLibraryReservations() : false;
            $canLibraryNoDue = $authGuard === 'staff' ? $authUser->canGenerateLibraryNoDue() : false;
        @endphp
        @if($canLibraryView)
        <div class="nav-section">Library</div>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.library.dashboard') ? 'active' : '' }}"
               href="{{ route('staff.library.dashboard') }}">
                <i class="bi bi-journal-bookmark"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.library.books.*') ? 'active' : '' }}"
               href="{{ route('staff.library.books.index') }}">
                <i class="bi bi-book"></i> Books
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.library.members.*') ? 'active' : '' }} {{ !$canLibraryMembers ? 'permission-disabled' : '' }}"
               href="{{ $canLibraryMembers ? route('staff.library.members.index') : '#' }}">
                <i class="bi bi-person-vcard"></i> Members
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.library.circulation.*') ? 'active' : '' }} {{ !$canLibraryIssue ? 'permission-disabled' : '' }}"
               href="{{ $canLibraryIssue ? route('staff.library.circulation.index') : '#' }}">
                <i class="bi bi-arrow-left-right"></i> Issue / Return
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.library.reservations.*') ? 'active' : '' }} {{ !$canLibraryReservations ? 'permission-disabled' : '' }}"
               href="{{ $canLibraryReservations ? route('staff.library.reservations.index') : '#' }}">
                <i class="bi bi-bookmark-check"></i> Reservations
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.library.fines.*') ? 'active' : '' }} {{ !$canLibraryIssue ? 'permission-disabled' : '' }}"
               href="{{ $canLibraryIssue ? route('staff.library.fines.index') : '#' }}">
                <i class="bi bi-cash-coin"></i> Fine Collection
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.library.no-due.*') ? 'active' : '' }} {{ !$canLibraryNoDue ? 'permission-disabled' : '' }}"
               href="{{ $canLibraryNoDue ? route('staff.library.no-due.index') : '#' }}">
                <i class="bi bi-patch-check"></i> No Dues
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.library.reports.*') ? 'active' : '' }} {{ !$canLibraryReports ? 'permission-disabled' : '' }}"
               href="{{ $canLibraryReports ? route('staff.library.reports.index') : '#' }}">
                <i class="bi bi-bar-chart-line"></i> Reports
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.library.subjects.*') ? 'active' : '' }} {{ !$canLibraryManage ? 'permission-disabled' : '' }}"
               href="{{ $canLibraryManage ? route('staff.library.subjects.index') : '#' }}">
                <i class="bi bi-journal-text"></i> Subjects
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.library.vendors.*') ? 'active' : '' }} {{ !$canLibraryManage ? 'permission-disabled' : '' }}"
               href="{{ $canLibraryManage ? route('staff.library.vendors.index') : '#' }}">
                <i class="bi bi-truck"></i> Vendors
            </a>
        </li>
        @endif

        {{-- Finance --}}
        @if($authGuard === 'staff' && $authUser->canViewFinance())
        @php
            $canExpenseCreate = $authUser->canCreateExpense();
            $canSalaryView    = $authUser->canManageSalary() || $authUser->canViewFinance();
        @endphp
        <div class="nav-section">Finance</div>
        @if($canExpenseCreate)
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.finance.expenses.create') ? 'active' : '' }}"
               href="{{ route('staff.finance.expenses.create') }}">
                <i class="bi bi-plus-circle" style="color:#ef4444;"></i> Add Expense
            </a>
        </li>
        @endif
        @if($authUser->canViewFinanceReports())
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.finance.reports.income-book') ? 'active' : '' }}"
               href="{{ route('staff.finance.reports.income-book') }}">
                <i class="bi bi-graph-up-arrow text-success"></i> Income Book
            </a>
        </li>
        @endif
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.finance.expenses.index') ? 'active' : '' }}"
               href="{{ route('staff.finance.expenses.index') }}">
                <i class="bi bi-receipt-cutoff text-danger"></i> Expense Book
            </a>
        </li>
        @if($canSalaryView)
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.finance.salary.index') ? 'active' : '' }}"
               href="{{ route('staff.finance.salary.index') }}">
                <i class="bi bi-person-workspace text-primary"></i> Salary Book
            </a>
        </li>
        @endif
        @if($authUser->canViewFinanceReports())
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.finance.reports.*') ? 'active' : '' }}"
               href="{{ route('staff.finance.reports.ledger') }}">
                <i class="bi bi-journal-richtext text-primary"></i> Ledger
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.finance.reports.day-book') ? 'active' : '' }}"
               href="{{ route('staff.finance.reports.day-book') }}">
                <i class="bi bi-calendar2-day text-primary"></i> Day Book
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.finance.reports.trial-balance') ? 'active' : '' }}"
               href="{{ route('staff.finance.reports.trial-balance') }}">
                <i class="bi bi-list-check text-primary"></i> Trial Balance
            </a>
        </li>
        @endif
        @endif

        {{-- Payroll --}}
        @if($authGuard === 'staff' && $authUser->canViewPayroll())
        @php
            $canAttendanceMark  = $authUser->canMarkAttendance();
            $canAttendanceView  = $authUser->canViewAttendance();
            $canPayrollGenerate = $authUser->canGeneratePayroll();
            $canPayrollApprove  = $authUser->canApprovePayroll();
        @endphp
        <div class="nav-section">Payroll</div>
        @if($canAttendanceMark)
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.finance.payroll.attendance.daily') ? 'active' : '' }}"
               href="{{ route('staff.finance.payroll.attendance.daily') }}">
                <i class="bi bi-calendar-check" style="color:#10b981;"></i> Mark Attendance
            </a>
        </li>
        @endif
        @if($canAttendanceView)
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.finance.payroll.attendance.monthly') ? 'active' : '' }}"
               href="{{ route('staff.finance.payroll.attendance.monthly') }}">
                <i class="bi bi-calendar3-week text-secondary"></i> Monthly Summary
            </a>
        </li>
        @endif
        @if($canPayrollGenerate || $canPayrollApprove)
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.finance.payroll.draft-view') ? 'active' : '' }}"
               href="{{ route('staff.finance.payroll.draft-view') }}">
                <i class="bi bi-file-earmark-spreadsheet text-info"></i> Payroll Draft
            </a>
        </li>
        @endif
        @endif

        {{-- Get Statement --}}
        @if($authGuard === 'staff' && $authUser->canViewStatements())
        <div class="nav-section">Statement</div>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.statement.balance') ? 'active' : '' }}"
               href="{{ route('staff.statement.balance') }}">
                <i class="bi bi-wallet2"></i> Student Balance
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.statement.fee-record') ? 'active' : '' }}"
               href="{{ route('staff.statement.fee-record') }}">
                <i class="bi bi-receipt"></i> Fee Submit Record
            </a>
        </li>
        @endif

        {{-- Reports --}}
        @if($authGuard === 'staff' && ($canFeeReports || $canAdmissionReports))
        <div class="nav-section">Reports</div>
        @if($canFeeReports)
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.reports.fee-due-list') ? 'active' : '' }}"
               href="{{ route('staff.reports.fee-due-list') }}">
                <i class="bi bi-exclamation-circle"></i> Fee Due List
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.reports.fee-collection') ? 'active' : '' }}"
               href="{{ route('staff.reports.fee-collection') }}">
                <i class="bi bi-cash-stack"></i> Fee Collection
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.reports.cancelled-fee') ? 'active' : '' }}"
               href="{{ route('staff.reports.cancelled-fee') }}">
                <i class="bi bi-x-octagon"></i> Cancelled Fee
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.reports.daily-collection') ? 'active' : '' }}"
               href="{{ route('staff.reports.daily-collection') }}">
                <i class="bi bi-calendar3"></i> Daily / Monthly
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.reports.semester-wise') ? 'active' : '' }}"
               href="{{ route('staff.reports.semester-wise') }}">
                <i class="bi bi-layers"></i> Semester Wise
            </a>
        </li>
        @endif
        @if($canAdmissionReports)
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.reports.admission') ? 'active' : '' }}"
               href="{{ route('staff.reports.admission') }}">
                <i class="bi bi-bar-chart-line"></i> Admission Report
            </a>
        </li>
        @endif
        @endif

        {{-- Notices --}}
        @if($authGuard === 'staff')
        <div class="nav-section">Notices</div>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.notices.*') ? 'active' : '' }}"
               href="{{ route('staff.notices.index') }}">
                <i class="bi bi-megaphone"></i> Notices
            </a>
        </li>
        @endif

        {{-- Staff Management --}}
        @if($authGuard === 'staff' && $authUser->canManageStaff())
        <div class="nav-section">Staff</div>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.staff-manage.*') ? 'active' : '' }}"
               href="{{ route('staff.staff-manage.index') }}">
                <i class="bi bi-people-fill"></i> Staff Members
            </a>
        </li>
        @endif

        {{-- Account --}}
        <div class="nav-section">Account</div>
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
</div>

{{-- Main --}}
<div class="main-content">
    {{-- Topbar --}}
    <div class="topbar">
        <small class="text-muted fw-semibold">@yield('breadcrumb', 'Dashboard')</small>
        <div class="d-flex align-items-center gap-3">
            @php
                $roleColors = ['center'=>'#185FA5','staff'=>'#1D9E75','partner'=>'#854F0B'];
                $roleLabels = ['center'=>'Center','staff'=>'Staff','partner'=>'Partner'];
                $rc = $roleColors[$authGuard] ?? '#64748b';
                $rl = $roleLabels[$authGuard] ?? 'User';
            @endphp
            <span class="role-chip" style="background:{{ $rc }}20;color:{{ $rc }};">
                <i class="bi bi-shield-check" style="font-size:11px;"></i>
                {{ $rl }}
            </span>
            {{-- Notices bell --}}
            @php
                $staffNoticeCount = \App\Models\Notice::forRole($authUser->institute_id, 'staff')
                    ->whereDoesntHave('reads', fn($q) => $q->where('reader_type','staff')->where('reader_id',$authUser->id))
                    ->count();
            @endphp
            <a href="{{ route('staff.notices.index') }}"
               class="position-relative text-decoration-none text-muted"
               title="Notices">
                <i class="bi bi-bell fs-5"></i>
                @if($staffNoticeCount > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                          style="font-size:9px;padding:2px 5px;">
                        {{ $staffNoticeCount > 9 ? '9+' : $staffNoticeCount }}
                    </span>
                @endif
            </a>
            <small class="text-muted">{{ $authUser->name }}</small>
        </div>
    </div>

    {{-- Alerts --}}
    <div class="page-content">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
