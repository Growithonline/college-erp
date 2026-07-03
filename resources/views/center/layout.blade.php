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
        .sidebar .nav-link { color:#94a3b8; padding:8px 16px; font-size:13px; display:flex; align-items:center; gap:8px; border-left:3px solid transparent; text-decoration:none !important; }
        .sidebar .nav-link:hover { color:#f8fafc; background:#334155; }
        .sidebar .nav-link.active { color:#38bdf8; background:#0f172a; border-left:3px solid #38bdf8; }
        .sidebar .nav-link i { font-size:14px; width:16px; flex-shrink:0; }
        .sidebar .group-header { color:#cbd5e1; padding:8px 16px; font-size:13px; display:flex; align-items:center; gap:8px; cursor:pointer; border-left:3px solid transparent; text-decoration:none !important; }
        .sidebar .group-header:hover { color:#f8fafc; background:#334155; }
        .sidebar .group-header.active-group { color:#38bdf8; border-left:3px solid #38bdf8; background:#0f172a; }
        .sidebar .group-header i.group-icon { font-size:14px; width:16px; flex-shrink:0; }
        .sub-menu { background:#0f172a; border-left:2px solid #334155; margin-left:20px; }
        .sub-menu .nav-link { font-size:12px; padding:6px 12px; color:#64748b; border-left:none; }
        .sub-menu .nav-link:hover { color:#f8fafc; background:transparent; }
        .sub-menu .nav-link.active { color:#38bdf8; background:transparent; }
        .collapse-arrow { margin-left:auto; transition:transform .2s; font-size:11px; }
        [aria-expanded="true"] .collapse-arrow { transform:rotate(180deg); }
        .sidebar { transition: transform .25s ease; }
        .main-content { margin-left:240px; padding:20px; transition: margin-left .25s ease; min-height:100vh; }
        .topbar { background:#fff; border-bottom:1px solid #e2e8f0; padding:8px 20px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:50; }
        #sidebarToggle { background:none; border:none; padding:4px 8px; cursor:pointer; color:#64748b; border-radius:6px; line-height:1; }
        #sidebarToggle:hover { background:#f1f5f9; color:#1e293b; }
        #sidebarToggle i { font-size:18px; }
        body.sidebar-collapsed .sidebar { transform: translateX(-240px); }
        body.sidebar-collapsed .main-content { margin-left: 0; }
        @media (max-width: 767px) {
            .sidebar { transform: translateX(-240px); z-index: 1050; }
            .main-content { margin-left: 0; }
            body.sidebar-open .sidebar { transform: translateX(0); }
            .sidebar-backdrop { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1040; }
            body.sidebar-open .sidebar-backdrop { display:block; }
        }
        @media (min-width: 768px) { .sidebar-backdrop { display:none !important; } }
        @media print {
            .sidebar, .topbar, .no-print { display:none !important; }
            .main-content { margin-left:0 !important; padding:8px !important; }
            body { background:#fff !important; }
        }
        .permission-disabled { opacity:0.4; pointer-events:none; }
    </style>
    @stack('styles')
</head>
<body>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="sidebar">
    <div class="sidebar-brand">
        @php
            $inst = $authUser->institute;
            $fwWallet = $authUser->wallet;
            $fwBadgeColor = $fwWallet ? (($fwWallet->isExpired() || (float)$fwWallet->remaining_tokens <= 0) ? 'danger' : (((float)$fwWallet->remaining_tokens < (float)$fwWallet->total_tokens * 0.15) ? 'warning' : null)) : null;
        @endphp
        <div class="d-flex align-items-center gap-2">
            @if($inst && $inst->image)
                <img src="{{ asset('storage/' . $inst->image) }}" alt="{{ $inst->name }}"
                     style="height:32px;width:32px;object-fit:contain;border-radius:6px;background:#1e293b;flex-shrink:0;">
            @else
                <i class="bi bi-mortarboard-fill text-primary" style="font-size:20px;flex-shrink:0;"></i>
            @endif
            <div style="min-width:0;">
                <h6 class="mb-0" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $inst?->name ?? config('app.name') }}</h6>
                <small>Center Portal</small>
            </div>
        </div>
    </div>

    <div class="sidebar-nav-wrap">
    <ul class="nav flex-column pt-1">

        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('center.dashboard') ? 'active' : '' }}"
               href="{{ route('center.dashboard') }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>

        {{-- Admissions Group --}}
        @php
            $canAdmit = $authUser->canManageAdmissions();
            $canQuick = $canAdmit && $authUser->canUseQuickAdmissionForm();
            $canFull  = $canAdmit && $authUser->canUseFullAdmissionForm();
            $canView  = $authUser->canViewStudents();
            $admGroupActive = request()->routeIs('center.admissions.*') || request()->routeIs('center.students.*');
        @endphp
        @if($canAdmit || $canView)
        <li class="nav-item mt-1">
            <a class="group-header {{ $admGroupActive ? 'active-group' : '' }} d-flex"
               data-bs-toggle="collapse" href="#centerAdmGroup" role="button"
               aria-expanded="{{ $admGroupActive ? 'true' : 'false' }}">
                <i class="bi bi-person-plus group-icon"></i>
                <span>Admissions</span>
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ $admGroupActive ? 'show' : '' }}" id="centerAdmGroup">
                <ul class="nav flex-column sub-menu">
                    @if($canQuick)
                    <li><a class="nav-link {{ request()->routeIs('center.admissions.quick*') ? 'active' : '' }}"
                           href="{{ route('center.admissions.quick-create') }}">
                        <i class="bi bi-lightning-fill" style="color:#f59e0b;"></i> Quick Register
                    </a></li>
                    @endif
                    @if($canFull)
                    <li><a class="nav-link {{ request()->routeIs('center.admissions.create') ? 'active' : '' }}"
                           href="{{ route('center.admissions.create') }}">
                        <i class="bi bi-person-plus"></i> Full Admission
                    </a></li>
                    @endif
                    @if($canView)
                    <li><a class="nav-link {{ request()->routeIs('center.students.index') ? 'active' : '' }}"
                           href="{{ route('center.students.index') }}">
                        <i class="bi bi-people"></i> My Students
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('center.students.search') ? 'active' : '' }}"
                           href="{{ route('center.students.search') }}">
                        <i class="bi bi-search"></i> Global Search
                    </a></li>
                    @endif
                </ul>
            </div>
        </li>
        @endif

        {{-- Fee Group --}}
        @if($authUser->canCollectFee())
        @php $feeGroupActive = request()->routeIs('center.fee.*'); @endphp
        <li class="nav-item mt-1">
            <a class="group-header {{ $feeGroupActive ? 'active-group' : '' }} d-flex"
               data-bs-toggle="collapse" href="#centerFeeGroup" role="button"
               aria-expanded="{{ $feeGroupActive ? 'true' : 'false' }}">
                <i class="bi bi-cash-stack group-icon"></i>
                <span>Fee</span>
                <i class="bi bi-chevron-down collapse-arrow"></i>
            </a>
            <div class="collapse {{ $feeGroupActive ? 'show' : '' }}" id="centerFeeGroup">
                <ul class="nav flex-column sub-menu">
                    <li><a class="nav-link {{ request()->routeIs('center.fee.index') ? 'active' : '' }}"
                           href="{{ route('center.fee.index') }}">
                        <i class="bi bi-list-ul"></i> My Collections
                    </a></li>
                    <li><a class="nav-link {{ request()->routeIs('center.fee.create') ? 'active' : '' }}"
                           href="{{ route('center.fee.create') }}">
                        <i class="bi bi-cash-coin"></i> Collect Fee
                    </a></li>
                    @if($fwWallet)
                    <li><a class="nav-link {{ request()->routeIs('center.fee.wallet.*') ? 'active' : '' }} d-flex justify-content-between align-items-center"
                           href="{{ route('center.fee.wallet.status') }}">
                        <span><i class="bi bi-wallet2"></i> Fee Wallet</span>
                        @if($fwBadgeColor)
                            <span class="badge bg-{{ $fwBadgeColor }} rounded-pill" style="font-size:9px;">
                                {{ $fwBadgeColor === 'danger' ? '!' : 'Low' }}
                            </span>
                        @endif
                    </a></li>
                    @endif
                </ul>
            </div>
        </li>
        @endif

        {{-- Reports --}}
        @if($authUser->canDownloadReports())
        <li class="nav-item mt-1">
            <a class="nav-link {{ request()->routeIs('center.reports.*') ? 'active' : '' }}"
               href="{{ route('center.reports.index') }}">
                <i class="bi bi-download"></i> Download Reports
            </a>
        </li>
        @endif

        {{-- Notices --}}
        <li class="nav-item mt-1">
            <a class="nav-link {{ request()->routeIs('center.notices.*') ? 'active' : '' }}"
               href="{{ route('center.notices.index') }}">
                <i class="bi bi-megaphone"></i> Notices
            </a>
        </li>

        {{-- My Profile --}}
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('center.profile') ? 'active' : '' }}"
               href="{{ route('center.profile') }}">
                <i class="bi bi-person-circle"></i> My Profile
            </a>
        </li>

    </ul>
    </div>

    <div style="flex-shrink:0; border-top:1px solid #334155; padding:10px 14px; display:flex; align-items:center; gap:9px; background:#0f172a;">
        <img src="{{ asset('images/logog.png') }}" alt="Gaurangi" style="height:26px; width:auto; object-fit:contain; flex-shrink:0; opacity:0.85;">
        <span style="font-size:10px; color:#64748b; line-height:1.35;">Developed &amp; Maintained by<br><span style="color:#94a3b8; font-weight:600;">Gaurangi Technologies</span></span>
    </div>
</div>

<div class="main-content">
    <div class="topbar mb-4 rounded shadow-sm">
        <div class="d-flex align-items-center gap-2" style="min-width:0;flex:1;">
            <button id="sidebarToggle" title="Toggle sidebar"><i class="bi bi-list"></i></button>
            <small class="text-muted fw-semibold text-truncate d-none d-md-inline">@yield('breadcrumb', 'Dashboard')</small>
        </div>
        {{-- Quick Action Buttons --}}
        <div class="d-flex align-items-center gap-1 mx-2">
            @if(isset($canFull) ? $canFull : ($authUser->canManageAdmissions() && $authUser->canUseFullAdmissionForm()))
            <a href="{{ route('center.admissions.create') }}"
               class="btn btn-sm d-flex align-items-center gap-1 {{ request()->routeIs('center.admissions.create') ? 'btn-primary' : 'btn-outline-primary' }}"
               title="Full Admission"
               style="font-size:11px;padding:4px 9px;white-space:nowrap;">
                <i class="bi bi-person-plus-fill"></i>
                <span class="d-none d-lg-inline">Full Admission</span>
            </a>
            @endif
            @if(isset($canQuick) ? $canQuick : ($authUser->canManageAdmissions() && $authUser->canUseQuickAdmissionForm()))
            <a href="{{ route('center.admissions.quick-create') }}"
               class="btn btn-sm d-flex align-items-center gap-1 {{ request()->routeIs('center.admissions.quick*') ? 'btn-warning' : 'btn-outline-warning' }}"
               title="Quick Register"
               style="font-size:11px;padding:4px 9px;white-space:nowrap;">
                <i class="bi bi-lightning-fill"></i>
                <span class="d-none d-lg-inline">Quick Reg.</span>
            </a>
            @endif
            @if($authUser->canCollectFee())
            <a href="{{ route('center.fee.create') }}"
               class="btn btn-sm d-flex align-items-center gap-1 {{ request()->routeIs('center.fee.create') ? 'btn-success' : 'btn-outline-success' }}"
               title="Collect Fee"
               style="font-size:11px;padding:4px 9px;white-space:nowrap;">
                <i class="bi bi-cash-coin"></i>
                <span class="d-none d-lg-inline">Collect Fee</span>
            </a>
            @endif
            @if(isset($canView) ? $canView : $authUser->canViewStudents())
            <a href="{{ route('center.students.search') }}"
               class="btn btn-sm d-flex align-items-center gap-1 {{ request()->routeIs('center.students.search') ? 'btn-info' : 'btn-outline-secondary' }}"
               title="Search Admission"
               style="font-size:11px;padding:4px 9px;white-space:nowrap;">
                <i class="bi bi-search"></i>
                <span class="d-none d-lg-inline">Search</span>
            </a>
            @endif
        </div>
        <div class="d-flex align-items-center gap-2">
            @php
                $activeSess = \App\Models\AcademicSession::where('institute_id', $authUser->institute_id)->where('is_active', true)->first();
            @endphp
            @if($activeSess)
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 d-none d-sm-inline-flex align-items-center" style="font-size:11px;">
                    <i class="bi bi-calendar-check me-1"></i>{{ $activeSess->name }}
                </span>
            @endif
            <span class="d-none d-sm-inline-flex align-items-center gap-1 px-2 py-1 rounded-pill"
                  style="font-size:11px;font-weight:500;background:#185FA520;color:#185FA5;">
                <i class="bi bi-shield-check" style="font-size:11px;"></i>Center
            </span>
            @if($fwWallet)
                @php
                    $topbarWalletBg    = $fwBadgeColor === 'danger' ? '#fef2f2' : ($fwBadgeColor === 'warning' ? '#fffbeb' : '#f0fdf4');
                    $topbarWalletColor = $fwBadgeColor === 'danger' ? '#dc2626' : ($fwBadgeColor === 'warning' ? '#d97706' : '#16a34a');
                @endphp
                <span class="d-none d-sm-inline-flex align-items-center gap-1 px-2 py-1 rounded-pill"
                      title="Wallet Balance{{ $fwWallet->expires_at ? ' · Expires ' . $fwWallet->expires_at->format('d M Y') : '' }}"
                      style="font-size:11px;font-weight:500;background:{{ $topbarWalletBg }};color:{{ $topbarWalletColor }};">
                    <i class="bi bi-wallet2" style="font-size:11px;"></i>
                    ₹{{ number_format((float)$fwWallet->remaining_tokens) }}
                </span>
            @endif
            @php
                $centerNoticeCount = \App\Models\Notice::forRole($authUser->institute_id, 'center')
                    ->whereDoesntHave('reads', fn($q) => $q->where('reader_type','center')->where('reader_id',$authUser->id))
                    ->count();
            @endphp
            <a href="{{ route('center.notices.index') }}"
               class="position-relative text-decoration-none text-muted d-flex align-items-center" title="Notices">
                <i class="bi bi-bell" style="font-size:16px;"></i>
                @if($centerNoticeCount > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:9px;padding:2px 5px;">
                        {{ $centerNoticeCount > 9 ? '9+' : $centerNoticeCount }}
                    </span>
                @endif
            </a>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('center.profile') }}" class="text-decoration-none d-flex align-items-center gap-2" title="My Profile">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                         style="width:28px;height:28px;font-size:11px;background:#185FA5;">
                        {{ strtoupper(substr($authUser->name, 0, 1)) }}
                    </div>
                    <small class="text-muted fw-semibold d-none d-md-inline">{{ $authUser->name }}</small>
                </a>
                <form method="POST" action="{{ route('center.logout') }}" class="mb-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1" style="font-size:11px;padding:3px 8px;">
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

<div id="toast-container" style="position:fixed;bottom:28px;right:28px;z-index:9999;display:flex;flex-direction:column;gap:10px;min-width:320px;max-width:400px;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
    var cfg={success:{bg:'#f0fdf4',border:'#22c55e',icon:'✓',iconBg:'#22c55e',title:'Success'},danger:{bg:'#fef2f2',border:'#ef4444',icon:'✕',iconBg:'#ef4444',title:'Error'},warning:{bg:'#fffbeb',border:'#f59e0b',icon:'!',iconBg:'#f59e0b',title:'Warning'}};
    window.showToast=function(msg,type,dur){type=type||'danger';dur=dur||4500;var c=cfg[type]||cfg.danger;var box=document.getElementById('toast-container');var t=document.createElement('div');t.setAttribute('data-toast','1');t.style.cssText='background:'+c.bg+';border:1px solid '+c.border+';border-left:4px solid '+c.border+';border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.12);padding:14px 16px 10px;display:flex;gap:12px;align-items:flex-start;opacity:0;transform:translateY(16px);transition:opacity 0.28s ease,transform 0.28s ease;position:relative;';t.innerHTML='<div style="width:28px;height:28px;border-radius:50%;background:'+c.iconBg+';color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;">'+c.icon+'</div><div style="flex:1;min-width:0;"><div style="font-size:13px;font-weight:700;color:#1e293b;margin-bottom:2px;">'+c.title+'</div><div style="font-size:13px;color:#475569;line-height:1.45;">'+msg+'</div><div class="toast-bar" style="height:3px;border-radius:2px;background:'+c.border+';margin-top:10px;width:100%;transform-origin:left;transition:width linear '+dur+'ms;"></div></div><button onclick="dismissToast(this.closest(\'[data-toast]\'))" style="background:none;border:none;padding:0;cursor:pointer;color:#94a3b8;font-size:16px;line-height:1;flex-shrink:0;">&#x2715;</button>';box.appendChild(t);requestAnimationFrame(function(){requestAnimationFrame(function(){t.style.opacity='1';t.style.transform='translateY(0)';var b=t.querySelector('.toast-bar');if(b)b.style.width='0%';});});var timer=setTimeout(function(){dismissToast(t);},dur);t.__timer=timer;};
    window.dismissToast=function(t){if(!t||t.__dismissed)return;t.__dismissed=true;clearTimeout(t.__timer);t.style.opacity='0';t.style.transform='translateY(8px)';setTimeout(function(){if(t.parentNode)t.parentNode.removeChild(t);},300);};
    if(window.__flashToast){showToast(window.__flashToast.message,window.__flashToast.type);}
})();
</script>
<script>
(function(){
    var body=document.body,backdrop=document.getElementById('sidebarBackdrop'),btn=document.getElementById('sidebarToggle');
    if(window.innerWidth>=768&&localStorage.getItem('centerSidebarCollapsed')==='1')body.classList.add('sidebar-collapsed');
    btn.addEventListener('click',function(){if(window.innerWidth<768){body.classList.toggle('sidebar-open');}else{body.classList.toggle('sidebar-collapsed');localStorage.setItem('centerSidebarCollapsed',body.classList.contains('sidebar-collapsed')?'1':'0');}});
    backdrop.addEventListener('click',function(){body.classList.remove('sidebar-open');});
    window.addEventListener('resize',function(){if(window.innerWidth>=768){body.classList.remove('sidebar-open');if(localStorage.getItem('centerSidebarCollapsed')==='1')body.classList.add('sidebar-collapsed');else body.classList.remove('sidebar-collapsed');}else{body.classList.remove('sidebar-collapsed');}});
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
</body>
</html>
