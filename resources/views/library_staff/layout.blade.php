<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Library Portal')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --lib-accent:#0ea5e9; --lib-dark:#0c4a6e; }
        body { background:#f1f5f9; }
        .sidebar { width:240px; height:100vh; background:#0c4a6e; position:fixed; top:0; left:0; overflow:hidden; z-index:100; display:flex; flex-direction:column; }
        .sidebar-nav-wrap { flex:1 1 auto; overflow-y:auto; overflow-x:hidden; scrollbar-width:thin; scrollbar-color:#164e63 #0c4a6e; }
        .sidebar-nav-wrap::-webkit-scrollbar { width:4px; }
        .sidebar-nav-wrap::-webkit-scrollbar-track { background:#0c4a6e; }
        .sidebar-nav-wrap::-webkit-scrollbar-thumb { background:#164e63; border-radius:2px; }
        .sidebar-brand { padding:14px 16px; background:#082f49; border-bottom:1px solid #164e63; }
        .sidebar-brand h6 { color:#f0f9ff; margin:0; font-size:13px; font-weight:600; }
        .sidebar-brand small { color:#7dd3fc; font-size:11px; }
        .sidebar ul.nav { padding-bottom:20px; }
        .sidebar .nav-link { color:#7dd3fc; padding:9px 16px; font-size:13px; display:flex; align-items:center; gap:8px; border-left:3px solid transparent; transition:all .15s; }
        .sidebar .nav-link:hover { color:#f0f9ff; background:#164e63; }
        .sidebar .nav-link.active { color:#fff; background:#082f49; border-left:3px solid var(--lib-accent); }
        .sidebar .nav-link i { font-size:14px; width:16px; flex-shrink:0; }
        .sidebar .section-label { color:#38bdf8; padding:10px 16px 4px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; }
        .main-content { margin-left:240px; padding:20px; transition:margin-left .25s ease; }
        .topbar { background:#fff; border-bottom:1px solid #e2e8f0; padding:8px 20px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:50; border-radius:0; }

        /* Sidebar toggle */
        #sidebarToggle { background:none; border:none; padding:4px 8px; cursor:pointer; color:#64748b; border-radius:6px; }
        #sidebarToggle:hover { background:#f1f5f9; }
        #sidebarToggle i { font-size:18px; }
        body.sidebar-collapsed .sidebar { transform:translateX(-240px); }
        body.sidebar-collapsed .main-content { margin-left:0; }
        .sidebar { transition:transform .25s ease; }

        @media (max-width:767px) {
            .sidebar { transform:translateX(-240px); z-index:1050; }
            .main-content { margin-left:0; }
            body.sidebar-open .sidebar { transform:translateX(0); }
            .sidebar-backdrop { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1040; }
            body.sidebar-open .sidebar-backdrop { display:block; }
        }
        @media (min-width:768px) { .sidebar-backdrop { display:none !important; } }

        /* Global toast */
        .toast-wrap { position:fixed; bottom:20px; right:20px; z-index:1100; display:flex; flex-direction:column; gap:10px; min-width:280px; }
        .notif { display:flex; align-items:flex-start; gap:10px; padding:12px 16px; border-radius:12px; border:1px solid; box-shadow:0 4px 20px rgba(0,0,0,.1); animation:slideIn .3s ease; position:relative; overflow:hidden; }
        @keyframes slideIn { from{transform:translateX(60px);opacity:0} to{transform:translateX(0);opacity:1} }
        .notif.error   { background:#fef2f2; border-color:#fecaca; color:#991b1b; }
        .notif.success { background:#f0fdf4; border-color:#bbf7d0; color:#166534; }
        .notif.info    { background:#f0f9ff; border-color:#bae6fd; color:#0c4a6e; }
        .notif .notif-body { flex:1; font-size:13px; font-weight:500; }
        .notif .notif-close { background:none; border:none; opacity:.5; cursor:pointer; font-size:14px; padding:0; }
        .notif-progress { position:absolute; bottom:0; left:0; height:3px; background:currentColor; opacity:.2; animation:progress 5.2s linear forwards; }
        @keyframes progress { from{width:100%} to{width:0} }

        @media print {
            .sidebar, .topbar, .no-print { display:none !important; }
            .main-content { margin-left:0 !important; padding:8px !important; }
            body { background:#fff !important; }
        }
    </style>
</head>
<body>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="sidebar">
    <div class="sidebar-brand">
        <h6><i class="bi bi-journals me-2" style="color:var(--lib-accent);"></i>Library Portal</h6>
        <small>{{ Auth::guard('library_staff')->user()->institute->name ?? 'Institute' }}</small>
    </div>
    <div class="sidebar-nav-wrap">
    <ul class="nav flex-column pt-1">

        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('library_staff.dashboard') ? 'active' : '' }}"
               href="{{ route('library_staff.dashboard') }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>

        <li><div class="section-label">Catalog</div></li>

        @if(Auth::guard('library_staff')->user()->hasPermission('books_view'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('library.books.*') ? 'active' : '' }}"
               href="{{ route('library.books.index') }}">
                <i class="bi bi-book"></i> Books &amp; Copies
            </a>
        </li>
        @endif

        <li><div class="section-label">Circulation</div></li>

        @if(Auth::guard('library_staff')->user()->hasPermission('issue_create') || Auth::guard('library_staff')->user()->hasPermission('return_process'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('library.circulation.*') ? 'active' : '' }}"
               href="{{ route('library.circulation.index') }}">
                <i class="bi bi-arrow-left-right"></i> Issue / Return
            </a>
        </li>
        @endif

        @if(Auth::guard('library_staff')->user()->hasPermission('fine_view'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('library.fines.*') ? 'active' : '' }}"
               href="{{ route('library.fines.index') }}">
                <i class="bi bi-cash-coin"></i> Fine Collection
            </a>
        </li>
        @endif

        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('library.reservations.*') ? 'active' : '' }}"
               href="{{ route('library.reservations.index') }}">
                <i class="bi bi-bookmark-check"></i> Reservations
            </a>
        </li>

        @if(Auth::guard('library_staff')->user()->hasPermission('members_view'))
        <li><div class="section-label">Members</div></li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('library.members.*') ? 'active' : '' }}"
               href="{{ route('library.members.index') }}">
                <i class="bi bi-person-vcard"></i> Members
            </a>
        </li>
        @endif

        @if(Auth::guard('library_staff')->user()->hasPermission('reports_view'))
        <li><div class="section-label">Reports</div></li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('library.reports.*') ? 'active' : '' }}"
               href="{{ route('library.reports.index') }}">
                <i class="bi bi-bar-chart-line"></i> Reports
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('library.no-due.*') ? 'active' : '' }}"
               href="{{ route('library.no-due.index') }}">
                <i class="bi bi-patch-check"></i> No Dues
            </a>
        </li>
        @endif

    </ul>
    </div>

    {{-- Staff info at bottom --}}
    <div style="flex-shrink:0; border-top:1px solid #164e63; padding:12px 14px; background:#082f49;">
        @php $libStaff = Auth::guard('library_staff')->user(); @endphp
        <div style="font-size:12px; color:#7dd3fc;">{{ $libStaff->name }}</div>
        <div style="font-size:11px; color:#38bdf8; opacity:.75;">{{ $libStaff->employee_id }}</div>
        <div style="font-size:10px; color:#475569; margin-top:2px;">
            {{ \App\Models\LibraryStaff::DESIGNATION_LABELS[$libStaff->designation] ?? $libStaff->designation }}
        </div>
    </div>
</div>

<div class="main-content">
    <div class="topbar mb-4 rounded shadow-sm">
        <div class="d-flex align-items:center; gap:2">
            <button id="sidebarToggle" title="Toggle sidebar">
                <i class="bi bi-list"></i>
            </button>
            <small class="text-muted fw-semibold ms-2">@yield('breadcrumb', 'Dashboard')</small>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1" style="font-size:11px;">
                <i class="bi bi-journals me-1"></i>Library Staff
            </span>
            <div class="d-flex align-items-center gap-2">
                {{-- User dropdown --}}
                <div class="dropdown">
                    <button class="btn btn-sm d-flex align-items-center gap-2 border-0 bg-transparent p-1"
                            data-bs-toggle="dropdown" aria-expanded="false" style="cursor:pointer;">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                             style="width:30px;height:30px;font-size:12px;background:var(--lib-accent);flex-shrink:0;">
                            {{ strtoupper(substr($libStaff->name, 0, 1)) }}
                        </div>
                        <small class="text-muted fw-semibold d-none d-md-inline">{{ $libStaff->name }}</small>
                        <i class="bi bi-chevron-down text-muted" style="font-size:10px;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width:200px;border-radius:12px;">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-semibold" style="font-size:13px;">{{ $libStaff->name }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ $libStaff->employee_id }}</div>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                               href="{{ route('library_staff.profile') }}" style="font-size:13px;">
                                <i class="bi bi-person-circle text-primary"></i> My Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                               href="{{ route('library_staff.activity') }}" style="font-size:13px;">
                                <i class="bi bi-activity text-info"></i> Activity Log
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <form method="POST" action="{{ route('library_staff.logout') }}" class="mb-0">
                                @csrf
                                <button type="submit"
                                        class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger"
                                        style="font-size:13px;">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast-based flash messages --}}
    <div class="toast-wrap" id="toastWrap"></div>

    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.showToast = function(type, message) {
    const icons = { error:'exclamation-circle-fill', success:'check-circle-fill', info:'info-circle-fill' };
    const wrap = document.getElementById('toastWrap');
    const el = document.createElement('div');
    el.className = 'notif ' + type;
    el.innerHTML = `
        <i class="bi bi-${icons[type] || 'info-circle'}" style="font-size:15px;flex-shrink:0;margin-top:1px;"></i>
        <span class="notif-body">${message}</span>
        <button class="notif-close" onclick="this.closest('.notif').remove()"><i class="bi bi-x"></i></button>
        <div class="notif-progress"></div>
    `;
    wrap.appendChild(el);
    setTimeout(() => el.style.opacity = '0', 5000);
    setTimeout(() => el.remove(), 5300);
};

// Show session flashes
(function() {
    @if(session('success'))
        showToast('success', '{{ addslashes(session('success')) }}');
    @endif
    @if(session('error'))
        showToast('error', '{{ addslashes(session('error')) }}');
    @endif
    @if(session('info'))
        showToast('info', '{{ addslashes(session('info')) }}');
    @endif
})();

// Sidebar toggle
(function() {
    const body = document.body;
    const backdrop = document.getElementById('sidebarBackdrop');
    const btn = document.getElementById('sidebarToggle');
    const isMobile = () => window.innerWidth < 768;

    if (!isMobile() && localStorage.getItem('libSidebarCollapsed') === '1') {
        body.classList.add('sidebar-collapsed');
    }

    btn.addEventListener('click', function() {
        if (isMobile()) {
            body.classList.toggle('sidebar-open');
        } else {
            body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('libSidebarCollapsed', body.classList.contains('sidebar-collapsed') ? '1' : '0');
        }
    });

    backdrop.addEventListener('click', () => body.classList.remove('sidebar-open'));
})();
</script>
@stack('scripts')
</body>
</html>
