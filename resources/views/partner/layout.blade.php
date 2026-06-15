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
                ? $authUser->hasPermission('admission_add')
                : $authUser->canManageAdmissions();
        @endphp
        @if($canAdmit)
        <div class="nav-section">Admissions</div>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs($authGuard.'.admissions.quick*') ? 'active' : '' }}"
               href="{{ route($authGuard.'.admissions.quick-create') }}">
                <i class="bi bi-lightning-fill" style="color:#f59e0b;"></i> Quick Register
            </a>
        </li>
        @if($authGuard !== 'partner' || $authUser->canUseFullAdmissionForm())
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
                ? $authUser->hasPermission('student_view')
                : $authUser->canViewStudents();
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
        @if($authGuard === 'partner')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('partner.students.search') ? 'active' : '' }}"
               href="{{ route('partner.students.search') }}">
                <i class="bi bi-search"></i> Global Search
            </a>
        </li>
        @endif
        @endif

        {{-- Fee --}}
        @php
            $canFee = $authGuard === 'staff'
                ? $authUser->hasPermission('fee_collect')
                : $authUser->canCollectFee();
        @endphp
        @if($canFee)
        <div class="nav-section">Fee</div>
        @if($authGuard === 'partner')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('partner.fee.index') ? 'active' : '' }}"
               href="{{ route('partner.fee.index') }}">
                <i class="bi bi-list-ul"></i> My Collections
            </a>
        </li>
        @endif
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs($authGuard.'.fee.create') ? 'active' : '' }}"
               href="{{ route($authGuard.'.fee.create') }}">
                <i class="bi bi-cash-coin"></i> Collect Fee
            </a>
        </li>
        @if($authGuard === 'staff')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.fee.index') ? 'active' : '' }}"
               href="{{ route('staff.fee.index') }}">
                <i class="bi bi-list-ul"></i> Fee History
            </a>
        </li>
        @endif

        @if(in_array($authGuard, ['center', 'partner']) && $authUser->wallet)
        @php
            $fwWallet = $authUser->wallet;
            $fwBadgeColor = ($fwWallet->isExpired() || (float)$fwWallet->remaining_tokens <= 0) ? 'danger' : (((float)$fwWallet->remaining_tokens < (float)$fwWallet->total_tokens * 0.15) ? 'warning' : null);
        @endphp
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs($authGuard.'.fee.wallet.*') ? 'active' : '' }} d-flex justify-content-between align-items-center"
               href="{{ route($authGuard.'.fee.wallet.status') }}">
                <span><i class="bi bi-wallet2"></i> Fee Wallet</span>
                @if($fwBadgeColor)
                    <span class="badge bg-{{ $fwBadgeColor }} rounded-pill" style="font-size:9px;">
                        {{ $fwBadgeColor === 'danger' ? '!' : 'Low' }}
                    </span>
                @endif
            </a>
        </li>
        @endif
        @endif

        {{-- Reports --}}
        @if($authGuard === 'partner' && $authUser->canDownloadReports())
        <div class="nav-section">Reports</div>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('partner.reports.*') ? 'active' : '' }}"
               href="{{ route('partner.reports.index') }}">
                <i class="bi bi-download"></i> Download Reports
            </a>
        </li>
        @endif

        {{-- Notices --}}
        <div class="nav-section">Notices</div>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('partner.notices.*') ? 'active' : '' }}"
               href="{{ route('partner.notices.index') }}">
                <i class="bi bi-megaphone"></i> Notices
            </a>
        </li>

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
                $partnerNoticeCount = \App\Models\Notice::forRole($authUser->institute_id, 'channel')
                    ->whereDoesntHave('reads', fn($q) => $q->where('reader_type','partner')->where('reader_id',$authUser->id))
                    ->count();
            @endphp
            <a href="{{ route('partner.notices.index') }}"
               class="position-relative text-decoration-none text-muted"
               title="Notices">
                <i class="bi bi-bell fs-5"></i>
                @if($partnerNoticeCount > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                          style="font-size:9px;padding:2px 5px;">
                        {{ $partnerNoticeCount > 9 ? '9+' : $partnerNoticeCount }}
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
