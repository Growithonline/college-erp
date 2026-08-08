<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Group Admin') — ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
    body { background: #f1f5f9; min-height: 100vh; }
    .ga-sidebar {
        width: 220px; min-height: 100vh; background: #0f172a;
        position: fixed; top: 0; left: 0; z-index: 100;
        display: flex; flex-direction: column;
    }
    .ga-sidebar .brand {
        padding: 18px 20px 14px;
        border-bottom: 1px solid rgba(255,255,255,.08);
    }
    .ga-sidebar .brand h6 { color: #fff; font-weight: 700; margin: 0; font-size: 14px; }
    .ga-sidebar .brand small { color: #64748b; font-size: 11px; }
    .ga-nav { padding: 12px 0; flex: 1; }
    .ga-nav a {
        display: flex; align-items: center; gap: 10px;
        padding: 9px 20px; color: #94a3b8; text-decoration: none;
        font-size: 13px; transition: all .15s;
    }
    .ga-nav a:hover, .ga-nav a.active { background: rgba(255,255,255,.07); color: #fff; }
    .ga-nav a i { font-size: 15px; width: 18px; text-align: center; }
    .ga-sidebar .ga-footer {
        padding: 12px 20px; border-top: 1px solid rgba(255,255,255,.08);
    }
    .ga-main { margin-left: 220px; min-height: 100vh; }
    .ga-topbar {
        background: #fff; border-bottom: 1px solid #e2e8f0;
        padding: 12px 24px; display: flex; align-items: center;
        justify-content: space-between; position: sticky; top: 0; z-index: 50;
    }
    .ga-topbar .breadcrumb { margin: 0; font-size: 13px; }
    .ga-content { padding: 24px; }
    @media (max-width: 768px) {
        .ga-sidebar { display: none; }
        .ga-main { margin-left: 0; }
    }
</style>
</head>
<body>

<div class="ga-sidebar">
    <div class="brand">
        <h6><i class="bi bi-diagram-3-fill me-2" style="color:#6366f1;"></i> Group Admin</h6>
        <small>{{ Auth::guard('group_admin')->user()->group->name ?? '' }}</small>
    </div>
    <div class="ga-nav">
        <a href="{{ route('group_admin.dashboard') }}" class="{{ request()->routeIs('group_admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="{{ route('group_admin.institutes.index') }}" class="{{ request()->routeIs('group_admin.institutes.*') ? 'active' : '' }}">
            <i class="bi bi-building"></i> Institutes
        </a>
    </div>
    <div class="ga-footer">
        <form method="POST" action="{{ route('group_admin.logout') }}">
            @csrf
            <button class="btn btn-sm btn-outline-danger w-100">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </button>
        </form>
    </div>
</div>

<div class="ga-main">
    <div class="ga-topbar">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('group_admin.dashboard') }}" class="text-decoration-none">Group Admin</a></li>
                @yield('breadcrumb')
            </ol>
        </nav>
        <small class="text-muted">{{ now()->format('d M Y') }}</small>
    </div>

    <div class="ga-content">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
