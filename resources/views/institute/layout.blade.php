<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Institute Panel')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f1f5f9; }
        .sidebar { width:240px; height:100vh; background:#1e293b; position:fixed; top:0; left:0; overflow:hidden; z-index:100; display:flex; flex-direction:column; }
        .sidebar-nav-wrap { flex:1 1 auto; overflow-y:auto; overflow-x:hidden; scrollbar-width:thin; scrollbar-color:#334155 #1e293b; }
        .sidebar-nav-wrap::-webkit-scrollbar { width:4px; }
        .sidebar-nav-wrap::-webkit-scrollbar-track { background:#1e293b; }
        .sidebar-nav-wrap::-webkit-scrollbar-thumb { background:#334155; border-radius:2px; }
        .sidebar-brand { padding:14px 16px; background:#0f172a; border-bottom:1px solid #334155; }
        .sidebar-brand h6 { color:#f8fafc; margin:0; font-size:13px; font-weight:600; }
        .sidebar-brand small { color:#64748b; font-size:11px; }
        /* Session Switcher */
        .session-switcher { padding:10px 12px; background:#0f172a; border-bottom:1px solid #334155; position:relative; }
        .session-label { font-size:9px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.8px; margin-bottom:5px; display:flex; align-items:center; gap:5px; }
        .session-label .dot { width:6px; height:6px; border-radius:50%; background:#22c55e; flex-shrink:0; }
        .session-btn { width:100%; background:#1e293b; border:1px solid #334155; border-radius:7px; padding:6px 10px; color:#e2e8f0; font-size:12px; font-weight:600; display:flex; align-items:center; justify-content:space-between; cursor:pointer; transition:border-color .15s; }
        .session-btn:hover { border-color:#38bdf8; }
        .session-btn.past-view { border-color:#f59e0b; color:#fbbf24; }
        .session-btn i { font-size:10px; color:#64748b; transition:transform .2s; }
        .session-btn.open i { transform:rotate(180deg); }
        .session-dropdown { position:absolute; left:12px; right:12px; top:calc(100% - 4px); background:#1e293b; border:1px solid #334155; border-radius:8px; z-index:200; box-shadow:0 8px 24px rgba(0,0,0,.4); overflow:hidden; display:none; }
        .session-dropdown.show { display:block; }
        .session-search { padding:8px; border-bottom:1px solid #334155; }
        .session-search input { width:100%; background:#0f172a; border:1px solid #334155; border-radius:5px; padding:4px 8px; font-size:11px; color:#e2e8f0; outline:none; }
        .session-search input::placeholder { color:#475569; }
        .session-list { max-height:180px; overflow-y:auto; }
        .session-item { padding:7px 12px; font-size:12px; color:#94a3b8; cursor:pointer; display:flex; align-items:center; justify-content:space-between; transition:background .1s; }
        .session-item:hover { background:#334155; color:#f8fafc; }
        .session-item.active { color:#38bdf8; background:#0f172a; }
        .session-item .active-dot { width:6px; height:6px; border-radius:50%; background:#38bdf8; flex-shrink:0; }
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
        /* Sub-menus (level 1) */
        .sub-menu { background:#0f172a; border-left:2px solid #334155; margin-left:20px; }
        .sub-menu .nav-link { font-size:12px; padding:6px 12px; color:#64748b; border-left:none; }
        .sub-menu .nav-link:hover { color:#f8fafc; background:transparent; }
        .sub-menu .nav-link.active { color:#38bdf8; background:transparent; }
        .sub-menu hr { border-color:#1e293b; margin:3px 8px; }
        /* Sub-menus (level 2 — inside sub-menu) */
        .sub-sub-menu { background:transparent; border-left:1px solid #1e293b; margin-left:12px; }
        .sub-sub-menu .nav-link { font-size:11px; padding:5px 8px; color:#475569; border-left:none; }
        .sub-sub-menu .nav-link:hover { color:#94a3b8; }
        .sub-sub-menu .nav-link.active { color:#38bdf8; }
        .collapse-arrow { margin-left:auto; transition:transform .2s; font-size:11px; }
        [aria-expanded="true"] .collapse-arrow { transform:rotate(180deg); }
        .sidebar { transition: transform .25s ease; }
        .main-content { margin-left:240px; padding:20px; transition: margin-left .25s ease; }
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
            /* Overlay backdrop */
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
        .bg-purple { background-color: #6f42c1 !important; }
        .text-purple { color: #6f42c1 !important; }
    </style>
</head>
<body>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="sidebar">
    <div class="sidebar-brand">
        @php $inst = auth()->user()->institute; @endphp
        <div class="d-flex align-items-center gap-2">
            @if($inst->image)
                <img src="{{ asset('storage/' . $inst->image) }}" alt="{{ $inst->name }}"
                     style="height:32px;width:32px;object-fit:contain;border-radius:6px;background:#1e293b;flex-shrink:0;">
            @else
                <i class="bi bi-mortarboard-fill text-primary" style="font-size:20px;flex-shrink:0;"></i>
            @endif
            <div style="min-width:0;">
                <h6 class="mb-0" style="font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $inst->name }}</h6>
                <small>Institute Panel</small>
            </div>
        </div>
    </div>

    {{-- Session Switcher --}}
    @php
        $instId      = auth()->user()->institute_id;
        $allSessions = \App\Models\AcademicSession::where('institute_id', $instId)
            ->orderByDesc('start_date')->get();
        $dbActiveSession  = $allSessions->firstWhere('is_active', true);
        $viewSession      = \App\Models\AcademicSession::viewSession($instId);
        $isPastView       = \App\Models\AcademicSession::isViewingPastSession($instId);
    @endphp
    <div class="session-switcher">
        <div class="session-label">
            {{-- Dot: green = live active session, amber = viewing past --}}
            <span class="dot" style="{{ $isPastView ? 'background:#f59e0b;' : '' }}"></span>
            SESSION
            @if($isPastView)
                <span style="color:#f59e0b;font-size:9px;font-weight:700;">VIEW ONLY</span>
            @endif
        </div>
        <button class="session-btn {{ $isPastView ? 'past-view' : '' }}" id="sessionSwitcherBtn" type="button">
            <span>{{ $viewSession ? $viewSession->name : 'No Session' }}</span>
            <i class="bi bi-chevron-down"></i>
        </button>
        <div class="session-dropdown" id="sessionDropdown">
            <div class="session-search">
                <input type="text" id="sessionSearch" placeholder="Search session..." autocomplete="off">
            </div>
            <div class="session-list" id="sessionList">
                @foreach($allSessions as $sess)
                    @php $isCurrentView = $viewSession && $viewSession->id === $sess->id; @endphp
                    <div class="session-item {{ $isCurrentView ? 'active' : '' }}"
                         data-name="{{ $sess->name }}"
                         onclick="switchViewSession({{ $sess->id }})">
                        <span>
                            {{ $sess->name }}
                            @if($sess->is_active)
                                <span style="font-size:9px;color:#22c55e;font-weight:600;"> LIVE</span>
                            @endif
                        </span>
                        @if($isCurrentView)
                            <span class="active-dot"></span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    {{-- Hidden form for view-only session switch (no DB change) --}}
    <form id="sessionViewSwitchForm" method="POST" action="{{ route('master.sessions.view-switch') }}" style="display:none;">
        @csrf
        <input type="hidden" name="session_id" id="sessionViewSwitchId">
    </form>

    <div class="sidebar-nav-wrap">
    <ul class="nav flex-column pt-1">

        {{-- Dashboard --}}
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('institute.dashboard') ? 'active' : '' }}"
               href="{{ route('institute.dashboard') }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>

        {{-- ═══════ MASTER (Collapsible Group) ═══════ --}}
        @php
            $masterActive = request()->routeIs('master.*') || request()->routeIs('library.categories.*') || request()->routeIs('library.authors.*') || request()->routeIs('library.publishers.*') || request()->routeIs('library.subjects.*') || request()->routeIs('library.vendors.*') || request()->routeIs('library.racks.*') || request()->routeIs('library.rules.*');
        @endphp
        <li class="nav-item mt-1">
            <a class="group-header {{ $masterActive ? 'active-group' : '' }} d-flex"
               data-bs-toggle="collapse" href="#masterGroup" role="button"
               aria-expanded="{{ $masterActive ? 'true' : 'false' }}">
                <i class="bi bi-grid group-icon"></i>
                <span>Master</span>
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ $masterActive ? 'show' : '' }}" id="masterGroup">
                <ul class="nav flex-column sub-menu">

                    {{-- Academic Session --}}
                    <li><a class="nav-link {{ request()->routeIs('master.sessions*') ? 'active' : '' }}"
                           href="{{ route('master.sessions.index') }}">
                        <i class="bi bi-calendar3"></i> Academic Session
                    </a></li>

                    {{-- Course Types --}}
                    <li><a class="nav-link {{ request()->routeIs('master.course-types*') ? 'active' : '' }}"
                           href="{{ route('master.course-types.index') }}">
                        <i class="bi bi-mortarboard"></i> Course Types
                    </a></li>

                    {{-- Course --}}
                    <li>
                        <a class="nav-link d-flex {{ request()->routeIs('master.courses*') || request()->routeIs('master.streams*') ? 'active' : '' }}"
                           data-bs-toggle="collapse" href="#menuCourse" role="button"
                           aria-expanded="{{ request()->routeIs('master.courses*') || request()->routeIs('master.streams*') ? 'true' : 'false' }}">
                            <i class="bi bi-book"></i> Course
                            <i class="bi bi-chevron-down collapse-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->routeIs('master.courses*') || request()->routeIs('master.streams*') ? 'show' : '' }}" id="menuCourse">
                            <ul class="nav flex-column sub-sub-menu">
                                <li><a class="nav-link {{ request()->routeIs('master.courses.create') ? 'active' : '' }}"
                                       href="{{ route('master.courses.create') }}">
                                    <i class="bi bi-plus-circle"></i> Add Course
                                </a></li>
                                <li><a class="nav-link {{ request()->routeIs('master.courses.index') ? 'active' : '' }}"
                                       href="{{ route('master.courses.index') }}">
                                    <i class="bi bi-list-ul"></i> View / Edit
                                </a></li>
                            </ul>
                        </div>
                    </li>

                    

                    

                    {{-- Subject --}}
                    <li>
                        <a class="nav-link d-flex {{ request()->routeIs('master.subjects*') ? 'active' : '' }}"
                           data-bs-toggle="collapse" href="#menuSubject" role="button"
                           aria-expanded="{{ request()->routeIs('master.subjects*') ? 'true' : 'false' }}">
                            <i class="bi bi-journal-text"></i> Subject
                            <i class="bi bi-chevron-down collapse-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->routeIs('master.subjects*') ? 'show' : '' }}" id="menuSubject">
                            <ul class="nav flex-column sub-sub-menu">
                                <li><a class="nav-link {{ request()->routeIs('master.subjects.create') ? 'active' : '' }}"
                                       href="{{ route('master.subjects.create') }}">
                                    <i class="bi bi-plus-circle"></i> Add Subject
                                </a></li>
                                <li><a class="nav-link {{ request()->routeIs('master.subjects.index') ? 'active' : '' }}"
                                       href="{{ route('master.subjects.index') }}">
                                    <i class="bi bi-list-ul"></i> View / Edit
                                </a></li>
                            </ul>
                        </div>
                    </li>

                    {{-- Student Types --}}
                    <li><a class="nav-link {{ request()->routeIs('master.student-types*') ? 'active' : '' }}"
                           href="{{ route('master.student-types.index') }}">
                        <i class="bi bi-person-badge"></i> Student Types
                    </a></li>

                    {{-- Fee Structure --}}
                    <li>
                        <a class="nav-link d-flex {{ request()->routeIs('master.fee*') ? 'active' : '' }}"
                           data-bs-toggle="collapse" href="#menuFeeStructure" role="button"
                           aria-expanded="{{ request()->routeIs('master.fee*') ? 'true' : 'false' }}">
                            <i class="bi bi-cash-coin"></i> Fee Structure
                            <i class="bi bi-chevron-down collapse-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->routeIs('master.fee*') ? 'show' : '' }}" id="menuFeeStructure">
                            <ul class="nav flex-column sub-sub-menu">
                                <li><a class="nav-link {{ request()->routeIs('master.fee-types*') ? 'active' : '' }}"
                                       href="{{ route('master.fee-types.index') }}">
                                    <i class="bi bi-tags"></i> Fee Types
                                </a></li>
                                <li><a class="nav-link {{ request()->routeIs('master.fee-assignments*') ? 'active' : '' }}"
                                       href="{{ route('master.fee-assignments.index') }}">
                                    <i class="bi bi-currency-rupee"></i> Fee Assignment
                                </a></li>
                                <li><hr class="my-1"></li>
                                <li><a class="nav-link {{ request()->routeIs('master.fee-plans*') ? 'active' : '' }}"
                                       href="{{ route('master.fee-plans.index') }}">
                                    <i class="bi bi-layers"></i> Fee Plans
                                </a></li>
                                <li><a class="nav-link {{ request()->routeIs('master.fee-structure.course-fees*') ? 'active' : '' }}"
                                       href="{{ route('master.fee-structure.course-fees') }}">
                                    <i class="bi bi-buildings"></i> Course Fees
                                </a></li>
                                <li><a class="nav-link {{ request()->is('master/fee-structure/subject-fees') ? 'active' : '' }}"
                                       href="{{ route('master.fee-structure.subject-fees') }}">
                                    <i class="bi bi-book-half"></i> Subject Fees
                                </a></li>
                                <li><a class="nav-link {{ request()->routeIs('master.fee-structure.subject-fees.summary') ? 'active' : '' }}"
                                       href="{{ route('master.fee-structure.subject-fees.summary') }}">
                                    <i class="bi bi-list-check"></i> Subject Fee List
                                </a></li>
                            </ul>
                        </div>
                    </li>

                    {{-- Form Builder --}}
                    <li>
                        <a class="nav-link d-flex {{ request()->routeIs('master.forms*') ? 'active' : '' }}"
                           data-bs-toggle="collapse" href="#menuForms" role="button"
                           aria-expanded="{{ request()->routeIs('master.forms*') ? 'true' : 'false' }}">
                            <i class="bi bi-ui-checks"></i> Form Builder
                            <i class="bi bi-chevron-down collapse-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->routeIs('master.forms*') ? 'show' : '' }}" id="menuForms">
                            <ul class="nav flex-column sub-sub-menu">
                                <li><a class="nav-link" href="{{ route('master.forms.builder', 'admission') }}"><i class="bi bi-file-earmark-person"></i> Admission Form</a></li>
                                <li><a class="nav-link" href="{{ route('master.forms.builder', 'quick') }}"><i class="bi bi-lightning"></i> Quick Form</a></li>
                                <li><a class="nav-link" href="{{ route('master.forms.builder', 'online') }}"><i class="bi bi-globe"></i> Online Form</a></li>
                                <li><a class="nav-link" href="{{ route('master.forms.builder', 'receipt') }}"><i class="bi bi-receipt"></i> Fee Receipt</a></li>
                            </ul>
                        </div>
                    </li>

                    

                    {{-- Centers --}}
                    <li><a class="nav-link {{ request()->routeIs('master.centers*') ? 'active' : '' }}"
                           href="{{ route('master.centers.index') }}">
                        <i class="bi bi-building"></i> Centers
                    </a></li>

                    {{-- Channel Partners --}}
                    <li><a class="nav-link {{ request()->routeIs('master.channel-partners*') ? 'active' : '' }}"
                           href="{{ route('master.channel-partners.index') }}">
                        <i class="bi bi-people"></i> Channel Partners
                    </a></li>

                    {{-- Staff --}}
                    <li>
                        <a class="nav-link d-flex {{ request()->routeIs('master.staff*') ? 'active' : '' }}"
                           data-bs-toggle="collapse" href="#staffMenu" role="button"
                           aria-expanded="{{ request()->routeIs('master.staff*') ? 'true' : 'false' }}">
                            <i class="bi bi-person-badge"></i> Staff
                            <i class="bi bi-chevron-down collapse-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->routeIs('master.staff*') ? 'show' : '' }}" id="staffMenu">
                            <ul class="nav flex-column sub-sub-menu">
                                <li><a class="nav-link {{ request()->routeIs('master.staff-roles*') ? 'active' : '' }}"
                                       href="{{ route('master.staff-roles.index') }}">
                                    <i class="bi bi-shield-check"></i> Roles & Permissions
                                </a></li>
                                <li><a class="nav-link {{ request()->routeIs('master.staff-members*') ? 'active' : '' }}"
                                       href="{{ route('master.staff-members.index') }}">
                                    <i class="bi bi-people"></i> Staff Members
                                </a></li>
                            </ul>
                        </div>
                    </li>

                    {{-- Bank Accounts --}}
                    <li>
                        <a class="nav-link d-flex {{ request()->routeIs('master.bank-accounts*') ? 'active' : '' }}"
                           data-bs-toggle="collapse" href="#menuBankAccounts" role="button"
                           aria-expanded="{{ request()->routeIs('master.bank-accounts*') ? 'true' : 'false' }}">
                            <i class="bi bi-bank"></i> Bank Accounts
                            <i class="bi bi-chevron-down collapse-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->routeIs('master.bank-accounts*') ? 'show' : '' }}" id="menuBankAccounts">
                            <ul class="nav flex-column sub-sub-menu">
                                <li><a class="nav-link {{ request()->routeIs('master.bank-accounts.index') ? 'active' : '' }}"
                                       href="{{ route('master.bank-accounts.index') }}">
                                    <i class="bi bi-list-ul"></i> All Accounts
                                </a></li>
                                <li><a class="nav-link {{ request()->routeIs('master.bank-accounts.permissions') ? 'active' : '' }}"
                                       href="{{ route('master.bank-accounts.permissions') }}">
                                    <i class="bi bi-shield-check"></i> Payment Permissions
                                </a></li>
                            </ul>
                        </div>
                    </li>

                    {{-- Documents --}}
                    <li>
                        <a class="nav-link d-flex {{ request()->routeIs('master.document*') ? 'active' : '' }}"
                           data-bs-toggle="collapse" href="#menuDocuments" role="button"
                           aria-expanded="{{ request()->routeIs('master.document*') ? 'true' : 'false' }}">
                            <i class="bi bi-paperclip"></i> Documents
                            <i class="bi bi-chevron-down collapse-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->routeIs('master.document*') ? 'show' : '' }}" id="menuDocuments">
                            <ul class="nav flex-column sub-sub-menu">
                                <li><a class="nav-link {{ request()->routeIs('master.document-categories*') ? 'active' : '' }}"
                                       href="{{ route('master.document-categories.index') }}">
                                    <i class="bi bi-folder2"></i> Categories
                                </a></li>
                                <li><a class="nav-link {{ request()->routeIs('master.document-types*') ? 'active' : '' }}"
                                       href="{{ route('master.document-types.index') }}">
                                    <i class="bi bi-file-earmark-text"></i> Document Types
                                </a></li>
                                <li><a class="nav-link {{ request()->routeIs('master.document-rules*') ? 'active' : '' }}"
                                       href="{{ route('master.document-rules.index') }}">
                                    <i class="bi bi-sliders"></i> Upload Rules
                                </a></li>
                            </ul>
                        </div>
                    </li>

                    {{-- Library Management --}}
                    @php $libMasterActive = request()->routeIs('library.categories.*') || request()->routeIs('library.authors.*') || request()->routeIs('library.publishers.*') || request()->routeIs('library.subjects.*') || request()->routeIs('library.vendors.*') || request()->routeIs('library.racks.*') || request()->routeIs('library.rules.*') || request()->routeIs('library.staff.*'); @endphp
                    <li>
                        <a class="nav-link d-flex {{ $libMasterActive ? 'active' : '' }}"
                           data-bs-toggle="collapse" href="#menuLibraryMasters" role="button"
                           aria-expanded="{{ $libMasterActive ? 'true' : 'false' }}">
                            <i class="bi bi-journal-bookmark"></i> Library Management
                            <i class="bi bi-chevron-down collapse-arrow"></i>
                        </a>
                        <div class="collapse {{ $libMasterActive ? 'show' : '' }}" id="menuLibraryMasters">
                            <ul class="nav flex-column sub-sub-menu">
                                <li><a class="nav-link {{ request()->routeIs('library.categories.*') ? 'active' : '' }}"
                                       href="{{ route('library.categories.index') }}">
                                    <i class="bi bi-tags"></i> Categories
                                </a></li>
                                <li><a class="nav-link {{ request()->routeIs('library.authors.*') ? 'active' : '' }}"
                                       href="{{ route('library.authors.index') }}">
                                    <i class="bi bi-pen"></i> Authors
                                </a></li>
                                <li><a class="nav-link {{ request()->routeIs('library.publishers.*') ? 'active' : '' }}"
                                       href="{{ route('library.publishers.index') }}">
                                    <i class="bi bi-buildings"></i> Publishers
                                </a></li>
                                <li><a class="nav-link {{ request()->routeIs('library.subjects.*') ? 'active' : '' }}"
                                       href="{{ route('library.subjects.index') }}">
                                    <i class="bi bi-journal-text"></i> Subjects
                                </a></li>
                                <li><a class="nav-link {{ request()->routeIs('library.vendors.*') ? 'active' : '' }}"
                                       href="{{ route('library.vendors.index') }}">
                                    <i class="bi bi-truck"></i> Vendors
                                </a></li>
                                <li><a class="nav-link {{ request()->routeIs('library.racks.*') ? 'active' : '' }}"
                                       href="{{ route('library.racks.index') }}">
                                    <i class="bi bi-grid-3x3-gap"></i> Racks
                                </a></li>
                                <li><a class="nav-link {{ request()->routeIs('library.rules.*') ? 'active' : '' }}"
                                       href="{{ route('library.rules.index') }}">
                                    <i class="bi bi-shield-check"></i> Rule Sets
                                </a></li>
                                <li><hr class="my-1"></li>
                                <li><a class="nav-link {{ request()->routeIs('library.staff.index') || request()->routeIs('library.staff.create') || request()->routeIs('library.staff.edit') ? 'active' : '' }}"
                                       href="{{ route('library.staff.index') }}">
                                    <i class="bi bi-person-workspace"></i> Library Staff
                                </a></li>
                                <li><a class="nav-link {{ request()->routeIs('library.staff.login-logs') ? 'active' : '' }}"
                                       href="{{ route('library.staff.login-logs') }}">
                                    <i class="bi bi-clock-history"></i> Login Logs
                                </a></li>
                                <li><a class="nav-link {{ request()->routeIs('library.staff.activity-logs') ? 'active' : '' }}"
                                       href="{{ route('library.staff.activity-logs') }}">
                                    <i class="bi bi-activity"></i> Activity Logs
                                </a></li>
                            </ul>
                        </div>
                    </li>

                </ul>
            </div>
        </li>

        {{-- ═══════ MANAGEMENT ═══════ --}}
        <li class="nav-item mt-1">
            <div class="px-3 py-1" style="color:#475569; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.8px;">Management</div>
        </li>

        {{-- Admissions --}}
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admissions*') && !request()->routeIs('admissions.promote.*') && !request()->routeIs('admissions.bulk-correction*') ? 'active' : '' }} d-flex"
               data-bs-toggle="collapse" href="#admissionMenu" role="button"
               aria-expanded="{{ request()->routeIs('admissions*') && !request()->routeIs('admissions.promote.*') && !request()->routeIs('admissions.bulk-correction*') ? 'true' : 'false' }}">
                <i class="bi bi-person-plus"></i> Admissions
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ request()->routeIs('admissions*') && !request()->routeIs('admissions.promote.*') && !request()->routeIs('admissions.bulk-correction*') ? 'show' : '' }}" id="admissionMenu">
                <ul class="nav flex-column sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('admissions.index') ? 'active' : '' }}"
                           href="{{ route('admissions.index') }}">
                        <i class="bi bi-list"></i> All Admissions
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('admissions.approvals.*') ? 'active' : '' }}"
                           href="{{ route('admissions.approvals.index') }}">
                        <i class="bi bi-shield-check"></i> Admission Approvals
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('admissions.create') ? 'active' : '' }}"
                           href="{{ route('admissions.create') }}">
                        <i class="bi bi-file-earmark-person"></i> Full Form
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('admissions.quick*') ? 'active' : '' }}"
                           href="{{ route('admissions.quick-create') }}">
                        <i class="bi bi-lightning-fill text-warning"></i> Quick Register
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('admissions.online') ? 'active' : '' }}"
                           href="{{ route('admissions.online') }}">
                        <i class="bi bi-globe text-info"></i> Online Admission
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('enquiries.*') ? 'active' : '' }}"
                           href="{{ route('enquiries.index') }}">
                        <i class="bi bi-chat-left-text text-primary"></i> Online Enquiries
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('admissions.bulk-import.*') ? 'active' : '' }}"
                           href="{{ route('admissions.bulk-import.index') }}">
                        <i class="bi bi-file-earmark-arrow-up text-success"></i> Bulk Import (Excel)
                    </a></li>
                </ul>
            </div>
        </li>

        {{-- Student Promotions (separate) --}}
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admissions.promote.*') ? 'active' : '' }} d-flex"
               data-bs-toggle="collapse" href="#promotionMenu" role="button"
               aria-expanded="{{ request()->routeIs('admissions.promote.*') ? 'true' : 'false' }}">
                <i class="bi bi-arrow-up-circle"></i> Student Promotions
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ request()->routeIs('admissions.promote.*') ? 'show' : '' }}" id="promotionMenu">
                <ul class="nav flex-column sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('admissions.promote.semester*') ? 'active' : '' }}"
                           href="{{ route('admissions.promote.semester') }}">
                        <i class="bi bi-arrow-up-circle text-info"></i> Semester Promotion
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('admissions.promote.session*') ? 'active' : '' }}"
                           href="{{ route('admissions.promote.session') }}">
                        <i class="bi bi-calendar-arrow-up text-warning"></i> Session Promotion
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('admissions.promote.report') ? 'active' : '' }}"
                           href="{{ route('admissions.promote.report') }}">
                        <i class="bi bi-file-earmark-text text-success"></i> Promotion Report
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('admissions.promote.promoted-students') ? 'active' : '' }}"
                           href="{{ route('admissions.promote.promoted-students') }}">
                        <i class="bi bi-arrow-up-circle text-info"></i> Promoted Students
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('admissions.promote.identity*') ? 'active' : '' }}"
                           href="{{ route('admissions.promote.identity') }}">
                        <i class="bi bi-person-badge text-primary"></i> Roll / Form No
                    </a></li>
                </ul>
            </div>
        </li>

        {{-- Students --}}
        @php $studentsMenuActive = request()->routeIs('students.*') || request()->routeIs('reports.admission*'); @endphp
        <li class="nav-item">
            <a class="nav-link {{ $studentsMenuActive ? 'active' : '' }} d-flex"
               data-bs-toggle="collapse" href="#menuStudents" role="button"
               aria-expanded="{{ $studentsMenuActive ? 'true' : 'false' }}">
                <i class="bi bi-people"></i> Students
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ $studentsMenuActive ? 'show' : '' }}" id="menuStudents">
                <ul class="nav flex-column sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('students.search') ? 'active' : '' }}"
                           href="{{ route('students.search') }}">
                        <i class="bi bi-search"></i> Global Search
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('students.wallet') ? 'active' : '' }}"
                           href="{{ route('students.wallet') }}">
                        <i class="bi bi-wallet2"></i> Student Wallet
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('students.history') ? 'active' : '' }}"
                           href="{{ route('students.history') }}">
                        <i class="bi bi-receipt"></i> Fee History
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('students.quick') ? 'active' : '' }}"
                           href="{{ route('students.quick') }}">
                        <i class="bi bi-lightning-fill text-warning"></i> Quick Admissions
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('students.index') ? 'active' : '' }}"
                           href="{{ route('students.index') }}">
                        <i class="bi bi-list-ul"></i> All Students
                    </a></li>
                    <li><hr class="my-1"></li>
                    <li><a class="nav-link {{ request()->routeIs('reports.admission') ? 'active' : '' }}"
                           href="{{ route('reports.admission') }}">
                        <i class="bi bi-file-earmark-bar-graph"></i> All Admissions
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('reports.admission.full-form') ? 'active' : '' }}"
                           href="{{ route('reports.admission.full-form') }}">
                        <i class="bi bi-file-earmark-person"></i> Full Form Report
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('reports.admission.online') ? 'active' : '' }}"
                           href="{{ route('reports.admission.online') }}">
                        <i class="bi bi-globe"></i> Online Admission Report
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('reports.admission.centre') ? 'active' : '' }}"
                           href="{{ route('reports.admission.centre') }}">
                        <i class="bi bi-building"></i> Centre Admissions
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('reports.admission.channel-partner') ? 'active' : '' }}"
                           href="{{ route('reports.admission.channel-partner') }}">
                        <i class="bi bi-people"></i> Channel Partner Admissions
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('reports.admission.staff') ? 'active' : '' }}"
                           href="{{ route('reports.admission.staff') }}">
                        <i class="bi bi-person-badge"></i> Staff Admissions
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('reports.admission.blocked') ? 'active' : '' }}"
                           href="{{ route('reports.admission.blocked') }}">
                        <i class="bi bi-slash-circle text-danger"></i> Blocked Students
                    </a></li>
                </ul>
            </div>
        </li>

        {{-- Certificates --}}
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('certificate.*') ? 'active' : '' }} d-flex"
               data-bs-toggle="collapse" href="#menuCertificates" role="button"
               aria-expanded="{{ request()->routeIs('certificate.*') ? 'true' : 'false' }}">
                <i class="bi bi-award"></i> Certificates
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ request()->routeIs('certificate.*') ? 'show' : '' }}" id="menuCertificates">
                <ul class="nav flex-column sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('certificate.create') || request()->routeIs('certificate.store') ? 'active' : '' }}"
                           href="{{ route('certificate.create') }}">
                        <i class="bi bi-plus-circle"></i> Issue Certificate
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('certificate.index') ? 'active' : '' }}"
                           href="{{ route('certificate.index') }}">
                        <i class="bi bi-clock-history"></i> Issued History
                    </a></li>
                    <li><hr class="my-1"></li>
                    <li><a class="nav-link {{ request()->routeIs('certificate.types.*') ? 'active' : '' }}"
                           href="{{ route('certificate.types.index') }}">
                        <i class="bi bi-list-ul"></i> Certificate Types
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('certificate.settings.*') ? 'active' : '' }}"
                           href="{{ route('certificate.settings.index') }}">
                        <i class="bi bi-gear"></i> Settings
                    </a></li>
                </ul>
            </div>
        </li>

        {{-- Notices --}}
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('notices.*') ? 'active' : '' }}"
               href="{{ route('notices.index') }}">
                <i class="bi bi-megaphone"></i> Notices
            </a>
        </li>

        {{-- Library --}}
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('library.*') ? 'active' : '' }} d-flex"
               data-bs-toggle="collapse" href="#menuLibrary" role="button"
               aria-expanded="{{ request()->routeIs('library.*') ? 'true' : 'false' }}">
                <i class="bi bi-journal-bookmark"></i> Library
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ request()->routeIs('library.*') ? 'show' : '' }}" id="menuLibrary">
                <ul class="nav flex-column sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('library.dashboard') ? 'active' : '' }}"
                           href="{{ route('library.dashboard') }}">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('library.books.*') ? 'active' : '' }}"
                           href="{{ route('library.books.index') }}">
                        <i class="bi bi-book"></i> Books & Copies
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('library.members.*') ? 'active' : '' }}"
                           href="{{ route('library.members.index') }}">
                        <i class="bi bi-person-vcard"></i> Members
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('library.circulation.*') ? 'active' : '' }}"
                           href="{{ route('library.circulation.index') }}">
                        <i class="bi bi-arrow-left-right"></i> Issue / Return
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('library.reservations.*') ? 'active' : '' }}"
                           href="{{ route('library.reservations.index') }}">
                        <i class="bi bi-bookmark-check"></i> Reservations
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('library.fines.*') ? 'active' : '' }}"
                           href="{{ route('library.fines.index') }}">
                        <i class="bi bi-cash-coin"></i> Fine Collection
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('library.no-due.*') ? 'active' : '' }}"
                           href="{{ route('library.no-due.index') }}">
                        <i class="bi bi-patch-check"></i> No Dues
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('library.reports.*') ? 'active' : '' }}"
                           href="{{ route('library.reports.index') }}">
                        <i class="bi bi-bar-chart-line"></i> Reports
                    </a></li>
                </ul>
            </div>
        </li>

        {{-- Fee Collection --}}
        <li class="nav-item">
            @php $feeMenuActive = request()->routeIs('fee.*') || request()->routeIs('reports.fee-ledger.*') || request()->routeIs('reports.fee-collection.*'); @endphp
            <a class="nav-link {{ $feeMenuActive ? 'active' : '' }} d-flex"
               data-bs-toggle="collapse" href="#menuFeeCollection" role="button"
               aria-expanded="{{ $feeMenuActive ? 'true' : 'false' }}">
                <i class="bi bi-cash-stack"></i> Fee Collection
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ $feeMenuActive ? 'show' : '' }}" id="menuFeeCollection">
                <ul class="nav flex-column sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('fee.create') ? 'active' : '' }}"
                           href="{{ route('fee.create') }}">
                        <i class="bi bi-plus-circle"></i> Collect Fee
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('students.index') ? 'active' : '' }}"
                           href="{{ route('students.index') }}">
                        <i class="bi bi-people"></i> All Students
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('fee.index') ? 'active' : '' }}"
                           href="{{ route('fee.index') }}">
                        <i class="bi bi-list-ul"></i> All Collections
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('fee.practical-tokens.*') ? 'active' : '' }}"
                           href="{{ route('fee.practical-tokens.index') }}">
                        <i class="bi bi-ticket-perforated"></i> Practical Tokens
                    </a></li>
                    <li>
                        @php
                            $walletPending = \App\Models\WalletExtensionRequest::where('institute_id', auth()->user()?->institute_id ?? 0)->where('status','pending')->count();
                        @endphp
                        <a class="nav-link {{ request()->routeIs('fee-wallets.*') ? 'active' : '' }} d-flex justify-content-between align-items-center"
                           href="{{ route('fee-wallets.centers') }}">
                            <span><i class="bi bi-wallet2"></i> Fee Wallets</span>
                            @if($walletPending > 0)
                                <span class="badge bg-danger rounded-pill" style="font-size:10px;">{{ $walletPending }}</span>
                            @endif
                        </a>
                    </li>
                    <li><a class="nav-link {{ request()->routeIs('reports.fee-ledger.*') ? 'active' : '' }}"
                           href="{{ route('reports.fee-ledger.index') }}">
                        <i class="bi bi-journal-text"></i> Fee Ledger (Bulk)
                    </a></li>
                    <li><hr class="my-1"></li>
                    <li><a class="nav-link {{ request()->routeIs('reports.fee-collection.staff') ? 'active' : '' }}"
                           href="{{ route('reports.fee-collection.staff') }}">
                        <i class="bi bi-person-badge"></i> Staff Collections
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('reports.fee-collection.centre') ? 'active' : '' }}"
                           href="{{ route('reports.fee-collection.centre') }}">
                        <i class="bi bi-building"></i> Centre Collections
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('reports.fee-collection.channel-partner') ? 'active' : '' }}"
                           href="{{ route('reports.fee-collection.channel-partner') }}">
                        <i class="bi bi-people"></i> Channel Partner Collection
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('reports.fee-collection.practical-token') ? 'active' : '' }}"
                           href="{{ route('reports.fee-collection.practical-token') }}">
                        <i class="bi bi-ticket-perforated"></i> Practical Token Collection
                    </a></li>
                </ul>
            </div>
        </li>

        {{-- Transport --}}
        @php $transportActive = request()->routeIs('transport.*'); @endphp
        <li class="nav-item">
            <a class="nav-link {{ $transportActive ? 'active' : '' }} d-flex"
               data-bs-toggle="collapse" href="#menuTransport" role="button"
               aria-expanded="{{ $transportActive ? 'true' : 'false' }}">
                <i class="bi bi-bus-front"></i> Transport
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ $transportActive ? 'show' : '' }}" id="menuTransport">
                <ul class="nav flex-column sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('transport.dashboard') ? 'active' : '' }}"
                           href="{{ route('transport.dashboard') }}">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('transport.routes*') ? 'active' : '' }}"
                           href="{{ route('transport.routes.index') }}">
                        <i class="bi bi-signpost-split"></i> Routes
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('transport.route-assignments*') ? 'active' : '' }}"
                           href="{{ route('transport.route-assignments.index') }}">
                        <i class="bi bi-diagram-3"></i> Route Assignments
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('transport.vehicle-types*') ? 'active' : '' }}"
                           href="{{ route('transport.vehicle-types.index') }}">
                        <i class="bi bi-tags"></i> Vehicle Types
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('transport.vehicles*') ? 'active' : '' }}"
                           href="{{ route('transport.vehicles.index') }}">
                        <i class="bi bi-bus-front"></i> Vehicles
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('transport.drivers*') ? 'active' : '' }}"
                           href="{{ route('transport.drivers.index') }}">
                        <i class="bi bi-person-badge"></i> Drivers
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('transport.helpers*') ? 'active' : '' }}"
                           href="{{ route('transport.helpers.index') }}">
                        <i class="bi bi-person-raised-hand"></i> Helpers
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('transport.allocations*') ? 'active' : '' }}"
                           href="{{ route('transport.allocations.index') }}">
                        <i class="bi bi-people"></i> Allocations
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('transport.maintenance.*') ? 'active' : '' }}"
                           href="{{ route('transport.maintenance.index') }}">
                        <i class="bi bi-wrench-adjustable"></i> Maintenance
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('transport.compliance.*') ? 'active' : '' }}"
                           href="{{ route('transport.compliance.index') }}">
                        <i class="bi bi-shield-check"></i> Compliance
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('transport.billing.*') ? 'active' : '' }}"
                           href="{{ route('transport.billing.index') }}">
                        <i class="bi bi-receipt"></i> Monthly Billing
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('transport.reports.*') ? 'active' : '' }}"
                           href="{{ route('transport.reports.index') }}">
                        <i class="bi bi-bar-chart-line"></i> Reports
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('transport.settings.*') ? 'active' : '' }}"
                           href="{{ route('transport.settings.index') }}">
                        <i class="bi bi-gear"></i> Settings
                    </a></li>
                </ul>
            </div>
        </li>

        {{-- Employees (non-teaching support staff) --}}
        @php $empActive = request()->routeIs('employees.*'); @endphp
        <li class="nav-item">
            <a class="nav-link {{ $empActive ? 'active' : '' }} d-flex"
               data-bs-toggle="collapse" href="#menuEmployees" role="button"
               aria-expanded="{{ $empActive ? 'true' : 'false' }}">
                <i class="bi bi-people-fill"></i> Employees
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ $empActive ? 'show' : '' }}" id="menuEmployees">
                <ul class="nav flex-column sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('employees.index') || request()->routeIs('employees.show') || request()->routeIs('employees.create') || request()->routeIs('employees.edit') ? 'active' : '' }}"
                           href="{{ route('employees.index') }}">
                        <i class="bi bi-people"></i> All Employees
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('employees.departments*') ? 'active' : '' }}"
                           href="{{ route('employees.departments.index') }}">
                        <i class="bi bi-building"></i> Departments
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('employees.designations*') ? 'active' : '' }}"
                           href="{{ route('employees.designations.index') }}">
                        <i class="bi bi-person-lines-fill"></i> Designations
                    </a></li>
                </ul>
            </div>
        </li>

        {{-- Payroll --}}
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('finance.payroll.*') ? 'active' : '' }} d-flex"
               data-bs-toggle="collapse" href="#menuPayroll" role="button"
               aria-expanded="{{ request()->routeIs('finance.payroll.*') ? 'true' : 'false' }}">
                <i class="bi bi-wallet2"></i> Payroll
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ request()->routeIs('finance.payroll.*') ? 'show' : '' }}" id="menuPayroll">
                <ul class="nav flex-column sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('finance.payroll.attendance.daily') ? 'active' : '' }}"
                           href="{{ route('finance.payroll.attendance.daily') }}">
                        <i class="bi bi-calendar-check"></i> Daily Attendance
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.payroll.attendance.monthly') ? 'active' : '' }}"
                           href="{{ route('finance.payroll.attendance.monthly') }}">
                        <i class="bi bi-calendar3"></i> Monthly Attendance
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.payroll.draft-view') ? 'active' : '' }}"
                           href="{{ route('finance.payroll.draft-view') }}">
                        <i class="bi bi-file-earmark-text"></i> Salary Draft
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.payroll.summary') ? 'active' : '' }}"
                           href="{{ route('finance.payroll.summary') }}">
                        <i class="bi bi-clipboard-data"></i> Payroll Summary
                    </a></li>
                </ul>
            </div>
        </li>

        @php
            $financeActive = request()->routeIs('finance.*') && !request()->routeIs('finance.wallet.*');
            $walletActive  = request()->routeIs('finance.wallet.*');
        @endphp

        {{-- Finance (GL / Accounting) --}}
        <li class="nav-item">
            <a class="nav-link {{ $financeActive ? 'active' : '' }} d-flex"
               data-bs-toggle="collapse" href="#menuFinance" role="button"
               aria-expanded="{{ $financeActive ? 'true' : 'false' }}">
                <i class="bi bi-cash-coin"></i> Finance
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ $financeActive ? 'show' : '' }}" id="menuFinance">
                <ul class="nav flex-column sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('finance.settings.*') ? 'active' : '' }}"
                           href="{{ route('finance.settings.index') }}">
                        <i class="bi bi-sliders"></i> Finance Settings
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.reports.income-book') ? 'active' : '' }}"
                           href="{{ route('finance.reports.income-book') }}">
                        <i class="bi bi-graph-up-arrow"></i> Income Book
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.expenses.create') ? 'active' : '' }}"
                           href="{{ route('finance.expenses.create') }}">
                        <i class="bi bi-plus-circle"></i> Add Expense
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.expenses.index') ? 'active' : '' }}"
                           href="{{ route('finance.expenses.index') }}">
                        <i class="bi bi-receipt-cutoff"></i> Expense Book
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.salary.*') ? 'active' : '' }}"
                           href="{{ route('finance.salary.index') }}">
                        <i class="bi bi-person-workspace"></i> Salary Book
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.reports.ledger') ? 'active' : '' }}"
                           href="{{ route('finance.reports.ledger') }}">
                        <i class="bi bi-journal-text"></i> Ledger
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.reports.day-book') ? 'active' : '' }}"
                           href="{{ route('finance.reports.day-book') }}">
                        <i class="bi bi-journal-richtext"></i> Day Book
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.reports.cash-book') ? 'active' : '' }}"
                           href="{{ route('finance.reports.cash-book') }}">
                        <i class="bi bi-cash"></i> Cash Book
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.reports.bank-book') ? 'active' : '' }}"
                           href="{{ route('finance.reports.bank-book') }}">
                        <i class="bi bi-bank2"></i> Bank Book
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.reports.trial-balance') ? 'active' : '' }}"
                           href="{{ route('finance.reports.trial-balance') }}">
                        <i class="bi bi-table"></i> Trial Balance
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.reports.profit-loss') ? 'active' : '' }}"
                           href="{{ route('finance.reports.profit-loss') }}">
                        <i class="bi bi-graph-up-arrow"></i> Profit & Loss
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.reports.reconciliation') ? 'active' : '' }}"
                           href="{{ route('finance.reports.reconciliation') }}">
                        <i class="bi bi-check2-square"></i> Reconciliation
                    </a></li>
                </ul>
            </div>
        </li>

        {{-- Institute Wallet --}}
        <li class="nav-item">
            <a class="nav-link {{ $walletActive ? 'active' : '' }} d-flex"
               data-bs-toggle="collapse" href="#menuWallet" role="button"
               aria-expanded="{{ $walletActive ? 'true' : 'false' }}">
                <i class="bi bi-wallet2"></i> Institute Wallet
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ $walletActive ? 'show' : '' }}" id="menuWallet">
                <ul class="nav flex-column sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('finance.wallet.dashboard') ? 'active' : '' }}"
                           href="{{ route('finance.wallet.dashboard') }}">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.wallet.ledger') ? 'active' : '' }}"
                           href="{{ route('finance.wallet.ledger') }}">
                        <i class="bi bi-journal-text"></i> Wallet Ledger
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.wallet.manual-income.*') ? 'active' : '' }}"
                           href="{{ route('finance.wallet.manual-income.index') }}">
                        <i class="bi bi-pencil-square"></i> Manual Income
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.wallet.cheques.*') ? 'active' : '' }} d-flex justify-content-between align-items-center"
                           href="{{ route('finance.wallet.cheques.index') }}">
                        <span><i class="bi bi-card-checklist"></i> Cheque Tracking</span>
                        @php
                            $instId = auth()->user()?->institute_id ?? 0;
                            $pendingCheques = \Illuminate\Support\Facades\Cache::remember(
                                'pending_cheques_' . $instId, 120,
                                fn() => \App\Models\ChequePayment::where('institute_id', $instId)->pending()->count()
                            );
                        @endphp
                        @if($pendingCheques > 0)
                            <span class="badge bg-warning text-dark rounded-pill" style="font-size:10px">{{ $pendingCheques }}</span>
                        @endif
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.wallet.contra.*') ? 'active' : '' }}"
                           href="{{ route('finance.wallet.contra.index') }}">
                        <i class="bi bi-arrow-left-right"></i> Contra Entries
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.wallet.expense-approvals.*') ? 'active' : '' }}"
                           href="{{ route('finance.wallet.expense-approvals.index') }}">
                        <i class="bi bi-hourglass-split"></i> Pending Approvals
                    </a></li>

                    <li class="mt-2"><small class="text-muted px-2 fw-semibold" style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;">Settings</small></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.wallet.income-categories.*') ? 'active' : '' }}"
                           href="{{ route('finance.wallet.income-categories.index') }}">
                        <i class="bi bi-tags"></i> Income Categories
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.wallet.expense-categories.*') ? 'active' : '' }}"
                           href="{{ route('finance.wallet.expense-categories.index') }}">
                        <i class="bi bi-diagram-3"></i> Expense Categories
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.wallet.approval-limits.*') ? 'active' : '' }}"
                           href="{{ route('finance.wallet.approval-limits.index') }}">
                        <i class="bi bi-shield-check"></i> Approval Limits
                    </a></li>

                    <li class="mt-2"><small class="text-muted px-2 fw-semibold" style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;">Reports</small></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.wallet.reports.income') ? 'active' : '' }}"
                           href="{{ route('finance.wallet.reports.income') }}">
                        <i class="bi bi-bar-chart"></i> Income Report
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.wallet.reports.expense') ? 'active' : '' }}"
                           href="{{ route('finance.wallet.reports.expense') }}">
                        <i class="bi bi-pie-chart"></i> Expense Report
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('finance.wallet.reports.session-comparison') ? 'active' : '' }}"
                           href="{{ route('finance.wallet.reports.session-comparison') }}">
                        <i class="bi bi-table"></i> Session Comparison
                    </a></li>
                </ul>
            </div>
        </li>

        {{-- Get Statement --}}
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('statement.*') ? 'active' : '' }} d-flex"
               data-bs-toggle="collapse" href="#menuStatement" role="button"
               aria-expanded="{{ request()->routeIs('statement.*') ? 'true' : 'false' }}">
                <i class="bi bi-file-earmark-text"></i> Get Statement
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ request()->routeIs('statement.*') ? 'show' : '' }}" id="menuStatement">
                <ul class="nav flex-column sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('statement.balance') ? 'active' : '' }}"
                           href="{{ route('statement.balance') }}">
                        <i class="bi bi-wallet2"></i> Get Student Balance
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('statement.fee-record') ? 'active' : '' }}"
                           href="{{ route('statement.fee-record') }}">
                        <i class="bi bi-receipt"></i> Fee Submit Record
                    </a></li>
                </ul>
            </div>
        </li>

        {{-- Reports --}}
        @php $reportsMenuActive = request()->routeIs('reports.*') && !request()->routeIs('reports.admission*') && !request()->routeIs('reports.fee-collection.*') && !request()->routeIs('reports.fee-ledger.*'); @endphp
        <li class="nav-item">
            <a class="nav-link {{ $reportsMenuActive ? 'active' : '' }} d-flex"
               data-bs-toggle="collapse" href="#menuReports" role="button"
               aria-expanded="{{ $reportsMenuActive ? 'true' : 'false' }}">
                <i class="bi bi-bar-chart-line"></i> Reports
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ $reportsMenuActive ? 'show' : '' }}" id="menuReports">
                <ul class="nav flex-column sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('reports.fee-due-list') ? 'active' : '' }}"
                           href="{{ route('reports.fee-due-list') }}">
                        <i class="bi bi-exclamation-circle"></i> Fee Due List
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('reports.fee-collection') ? 'active' : '' }}"
                           href="{{ route('reports.fee-collection') }}">
                        <i class="bi bi-cash-stack"></i> Fee Collection
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('reports.cancelled-fee') ? 'active' : '' }}"
                           href="{{ route('reports.cancelled-fee') }}">
                        <i class="bi bi-x-octagon"></i> Cancelled Fee
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('reports.daily-collection') ? 'active' : '' }}"
                           href="{{ route('reports.daily-collection') }}">
                        <i class="bi bi-calendar3"></i> Daily / Monthly
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('reports.semester-wise') ? 'active' : '' }}"
                           href="{{ route('reports.semester-wise') }}">
                        <i class="bi bi-layers"></i> Semester Wise
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('reports.admission') ? 'active' : '' }}"
                           href="{{ route('reports.admission') }}">
                        <i class="bi bi-person-plus"></i> Admission Report
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('reports.custom-student') ? 'active' : '' }}"
                           href="{{ route('reports.custom-student') }}">
                        <i class="bi bi-table"></i> Custom Report
                    </a></li>
                </ul>
            </div>
        </li>

        {{-- SMS Settings --}}
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('master.sms.*') ? 'active' : '' }} d-flex"
               data-bs-toggle="collapse" href="#menuSms" role="button"
               aria-expanded="{{ request()->routeIs('master.sms.*') ? 'true' : 'false' }}">
                <i class="bi bi-phone"></i> SMS
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ request()->routeIs('master.sms.*') ? 'show' : '' }}" id="menuSms">
                <ul class="nav flex-column sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('master.sms.index') ? 'active' : '' }}"
                           href="{{ route('master.sms.index') }}">
                        <i class="bi bi-gear"></i> SMS Settings
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('master.sms.logs') ? 'active' : '' }}"
                           href="{{ route('master.sms.logs') }}">
                        <i class="bi bi-list-ul"></i> SMS History
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('master.sms.reminders.*') ? 'active' : '' }}"
                           href="{{ route('master.sms.reminders.index') }}">
                        <i class="bi bi-alarm"></i> Due Reminders
                    </a></li>
                </ul>
            </div>
        </li>

        {{-- Email Settings --}}
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('master.settings.email*') ? 'active' : '' }}"
               href="{{ route('master.settings.email') }}">
                <i class="bi bi-envelope-gear"></i> Email Settings
                @php $inst = auth()->user()?->institute; @endphp
                @if($inst && $inst->hasSmtp())
                    <span class="ms-auto badge bg-success-subtle text-success border border-success-subtle" style="font-size:9px;padding:2px 5px;">ON</span>
                @endif
            </a>
        </li>

        {{-- Data Backup --}}
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('master.settings.backup*') ? 'active' : '' }}"
               href="{{ route('master.settings.backup') }}">
                <i class="bi bi-database-down"></i> Backup & Export
            </a>
        </li>

        {{-- Bulk Student Correction --}}
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admissions.bulk-correction*') ? 'active' : '' }}"
               href="{{ route('admissions.bulk-correction') }}">
                <i class="bi bi-file-earmark-spreadsheet"></i> Bulk Student Correction
            </a>
        </li>

    </ul>
    </div>{{-- end sidebar-nav-wrap --}}

    {{-- Gaurangi Branding --}}
    <div style="flex-shrink:0; border-top:1px solid #334155; padding:10px 14px; display:flex; align-items:center; gap:9px; background:#0f172a;">
        <img src="{{ asset('images/logog.png') }}" alt="Gaurangi" style="height:26px; width:auto; object-fit:contain; flex-shrink:0; opacity:0.85;">
        <span style="font-size:10px; color:#64748b; line-height:1.35;">Developed &amp; Maintained by<br><span style="color:#94a3b8; font-weight:600;">Gaurangi Technologies</span></span>
    </div>

</div>

<div class="main-content">
    <div class="topbar mb-4 rounded shadow-sm">
        <div class="d-flex align-items-center gap-2" style="min-width:0;flex:1;">
            <button id="sidebarToggle" title="Toggle sidebar">
                <i class="bi bi-list"></i>
            </button>
            <small class="text-muted fw-semibold text-truncate d-none d-md-inline">@yield('breadcrumb', 'Dashboard')</small>
        </div>
        {{-- Quick Action Buttons --}}
        <div class="d-flex align-items-center gap-1 mx-2">
            <a href="{{ route('admissions.create') }}"
               class="btn btn-sm d-flex align-items-center gap-1 {{ request()->routeIs('admissions.create') ? 'btn-primary' : 'btn-outline-primary' }}"
               title="Full Admission" style="font-size:11px;padding:4px 9px;white-space:nowrap;">
                <i class="bi bi-person-plus-fill"></i>
                <span class="d-none d-lg-inline">Full Admission</span>
            </a>
            <a href="{{ route('admissions.quick-create') }}"
               class="btn btn-sm d-flex align-items-center gap-1 {{ request()->routeIs('admissions.quick*') ? 'btn-warning' : 'btn-outline-warning' }}"
               title="Quick Register" style="font-size:11px;padding:4px 9px;white-space:nowrap;">
                <i class="bi bi-lightning-fill"></i>
                <span class="d-none d-lg-inline">Quick Reg.</span>
            </a>
            <a href="{{ route('fee.create') }}"
               class="btn btn-sm d-flex align-items-center gap-1 {{ request()->routeIs('fee.create') ? 'btn-success' : 'btn-outline-success' }}"
               title="Collect Fee" style="font-size:11px;padding:4px 9px;white-space:nowrap;">
                <i class="bi bi-cash-coin"></i>
                <span class="d-none d-lg-inline">Collect Fee</span>
            </a>
            <a href="{{ route('students.search') }}"
               class="btn btn-sm d-flex align-items-center gap-1 {{ request()->routeIs('students.search') ? 'btn-info' : 'btn-outline-secondary' }}"
               title="Search Admission" style="font-size:11px;padding:4px 9px;white-space:nowrap;">
                <i class="bi bi-search"></i>
                <span class="d-none d-lg-inline">Search</span>
            </a>
        </div>
        <div class="d-flex align-items-center gap-2">
            {{-- Session badge in topbar — shows view session, with warning if past --}}
            @if(isset($isPastView) && $isPastView)
                <form method="POST" action="{{ route('master.sessions.view-switch') }}" class="mb-0 d-none d-sm-inline-flex">
                    @csrf
                    <input type="hidden" name="session_id" value="">
                    <button type="submit"
                            class="badge border-0 bg-warning text-dark px-2 py-1 d-inline-flex align-items-center gap-1"
                            style="font-size:11px;cursor:pointer;"
                            title="View only mode — click to go back to live session">
                        <i class="bi bi-eye me-1"></i>
                        Viewing: {{ isset($viewSession) ? $viewSession->name : '' }}
                        <i class="bi bi-x-circle ms-1"></i>
                    </button>
                </form>
            @elseif(isset($viewSession) && $viewSession)
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 d-none d-sm-inline-flex align-items-center"
                      style="font-size:11px;">
                    <i class="bi bi-calendar-check me-1"></i>{{ $viewSession->name }}
                </span>
            @else
                <a href="{{ route('master.sessions.create') }}"
                   class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 text-decoration-none d-none d-sm-inline-flex align-items-center"
                   style="font-size:11px;">
                    <i class="bi bi-exclamation-triangle me-1"></i>No Session
                </a>
            @endif

            {{-- Notification bell with dropdown (notices + wallet extension requests) --}}
            @php
                $instituteNoticeCount = \App\Models\Notice::forRole(auth()->user()->institute_id, 'staff')
                    ->whereDoesntHave('reads', fn($q) => $q->where('reader_type','institute')->where('reader_id', auth()->id()))
                    ->count();
                $walletPendingRequests = \App\Models\WalletExtensionRequest::where('institute_id', auth()->user()->institute_id)
                    ->where('status', 'pending')
                    ->latest()
                    ->limit(5)
                    ->get();
                $walletPendingCount = $walletPendingRequests->count();
                $totalBellCount = $instituteNoticeCount + $walletPendingCount;
            @endphp
            <div class="dropdown">
                <button class="btn p-0 border-0 bg-transparent position-relative text-muted d-flex align-items-center"
                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Notifications">
                    <i class="bi bi-bell" style="font-size:16px;"></i>
                    @if($totalBellCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                              style="font-size:9px;padding:2px 5px;">
                            {{ $totalBellCount > 9 ? '9+' : $totalBellCount }}
                        </span>
                    @endif
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow p-0 overflow-hidden"
                    style="min-width:300px;max-width:340px;border-radius:10px;">
                    <li class="px-3 py-2 border-bottom" style="background:#f8fafc;">
                        <span class="fw-semibold text-dark" style="font-size:13px;">Notifications</span>
                    </li>

                    @if($walletPendingCount > 0)
                        <li class="px-3 pt-2 pb-1">
                            <small class="text-uppercase text-muted fw-semibold" style="font-size:10px;letter-spacing:.5px;">
                                <i class="bi bi-wallet2 me-1"></i>Wallet Requests
                            </small>
                        </li>
                        @foreach($walletPendingRequests as $wr)
                            <li>
                                <a href="{{ route('fee-wallets.extension-requests') }}"
                                   class="dropdown-item d-flex align-items-start gap-2 py-2 px-3"
                                   style="white-space:normal;">
                                    <span class="flex-shrink-0 mt-1" style="color:#f59e0b;">
                                        <i class="bi bi-wallet2" style="font-size:13px;"></i>
                                    </span>
                                    <div>
                                        <div style="font-size:12px;font-weight:600;color:#1e293b;">{{ $wr->entity_name }}</div>
                                        <div style="font-size:11px;color:#64748b;">
                                            {{ $wr->request_type === 'token_topup' ? 'Token Top-up' : 'Expiry Extension' }}
                                            @if($wr->request_type === 'token_topup' && $wr->requested_amount)
                                                &mdash; ₹{{ number_format($wr->requested_amount) }}
                                            @elseif($wr->request_type === 'expiry_extension' && $wr->requested_days)
                                                &mdash; {{ $wr->requested_days }} days
                                            @endif
                                            &middot; {{ $wr->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                        <li>
                            <a href="{{ route('fee-wallets.extension-requests') }}"
                               class="dropdown-item text-center border-top py-2"
                               style="font-size:12px;color:#3b82f6;">
                                View all wallet requests →
                            </a>
                        </li>
                    @endif

                    @if($instituteNoticeCount > 0)
                        @if($walletPendingCount > 0)<li><hr class="dropdown-divider my-0"></li>@endif
                        <li class="px-3 pt-2 pb-1">
                            <small class="text-uppercase text-muted fw-semibold" style="font-size:10px;letter-spacing:.5px;">
                                <i class="bi bi-bell me-1"></i>Notices
                            </small>
                        </li>
                        <li>
                            <a href="{{ route('notices.index') }}"
                               class="dropdown-item d-flex align-items-center gap-2 py-2 px-3">
                                <i class="bi bi-bell-fill text-primary" style="font-size:13px;"></i>
                                <span style="font-size:12px;">
                                    {{ $instituteNoticeCount }} unread {{ $instituteNoticeCount === 1 ? 'notice' : 'notices' }}
                                </span>
                            </a>
                        </li>
                    @endif

                    @if($totalBellCount === 0)
                        <li class="px-3 py-3 text-center text-muted" style="font-size:12px;">
                            <i class="bi bi-check-circle me-1 text-success"></i>No new notifications
                        </li>
                    @endif
                </ul>
            </div>

            {{-- User + Logout --}}
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                     style="width:28px;height:28px;font-size:11px;">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                {{-- Name — hidden on mobile --}}
                <small class="text-muted fw-semibold d-none d-md-inline">{{ auth()->user()->name }}</small>
                <form method="POST" action="{{ route('logout') }}" class="mb-0">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

{{-- Global Toast Container --}}
<div id="toast-container" style="position:fixed;bottom:28px;right:28px;z-index:9999;display:flex;flex-direction:column;gap:10px;min-width:320px;max-width:400px;"></div>

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

    window.addEventListener('unhandledrejection', function (e) {
        window.showToast('An unexpected error occurred. Please refresh the page.', 'danger');
    });

    if (window.__flashToast) {
        showToast(window.__flashToast.message, window.__flashToast.type);
    }
})();
</script>

<script>
// Limit year segment of native date inputs to 4 digits (prevents Chrome/Edge
// letting the year spinner run past 9999 when typed continuously).
document.addEventListener('input', function (e) {
    if (!e.target.matches('input[type="date"]')) return;
    var val = e.target.value;
    if (!val) return;
    var parts = val.split('-');
    if (parts[0] && parts[0].length > 4) {
        parts[0] = parts[0].slice(0, 4);
        e.target.value = parts.join('-');
    }
});
</script>

@stack('scripts')

<script>
// Session switcher logic
(function () {
    var btn      = document.getElementById('sessionSwitcherBtn');
    var dropdown = document.getElementById('sessionDropdown');
    var search   = document.getElementById('sessionSearch');
    var list     = document.getElementById('sessionList');

    if (!btn) return;

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var open = dropdown.classList.toggle('show');
        btn.classList.toggle('open', open);
        if (open) { search.focus(); search.value = ''; filterSessions(''); }
    });

    document.addEventListener('click', function () {
        dropdown.classList.remove('show');
        btn.classList.remove('open');
    });

    dropdown.addEventListener('click', function (e) { e.stopPropagation(); });

    search.addEventListener('input', function () { filterSessions(this.value.toLowerCase()); });

    function filterSessions(q) {
        var items = list.querySelectorAll('.session-item');
        items.forEach(function (item) {
            item.style.display = item.dataset.name.toLowerCase().includes(q) ? '' : 'none';
        });
    }
})();

// View-only session switch — DB touch nahi hota, sirf PHP session mein store hota hai
window.switchViewSession = function (sessionId) {
    var form   = document.getElementById('sessionViewSwitchForm');
    var input  = document.getElementById('sessionViewSwitchId');
    input.value = sessionId;
    form.submit();
};
</script>

<script>
(function () {
    var body     = document.body;
    var backdrop = document.getElementById('sidebarBackdrop');
    var btn      = document.getElementById('sidebarToggle');
    var isMobile = window.innerWidth < 768;

    // Restore saved state
    if (isMobile) {
        // Mobile: always start collapsed (no sidebar-open class)
    } else {
        if (localStorage.getItem('sidebarCollapsed') === '1') {
            body.classList.add('sidebar-collapsed');
        }
    }

    btn.addEventListener('click', function () {
        if (window.innerWidth < 768) {
            // Mobile: toggle overlay open
            body.classList.toggle('sidebar-open');
        } else {
            // Desktop: toggle collapsed
            body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed',
                body.classList.contains('sidebar-collapsed') ? '1' : '0');
        }
    });

    // Close sidebar when backdrop clicked (mobile)
    backdrop.addEventListener('click', function () {
        body.classList.remove('sidebar-open');
    });

    // On resize: switch between mobile/desktop modes cleanly
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 768) {
            body.classList.remove('sidebar-open');
            if (localStorage.getItem('sidebarCollapsed') === '1') {
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
</body>
</html>
