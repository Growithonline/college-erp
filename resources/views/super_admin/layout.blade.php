<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Super Admin') — ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
    body { background: #f1f5f9; min-height: 100vh; }
    .sa-sidebar {
        width: 220px; min-height: 100vh; background: #0f172a;
        position: fixed; top: 0; left: 0; z-index: 100;
        display: flex; flex-direction: column;
    }
    .sa-sidebar .brand {
        padding: 18px 20px 14px;
        border-bottom: 1px solid rgba(255,255,255,.08);
    }
    .sa-sidebar .brand h6 { color: #fff; font-weight: 700; margin: 0; font-size: 14px; }
    .sa-sidebar .brand small { color: #64748b; font-size: 11px; }
    .sa-nav { padding: 12px 0; flex: 1; }
    .sa-nav a {
        display: flex; align-items: center; gap: 10px;
        padding: 9px 20px; color: #94a3b8; text-decoration: none;
        font-size: 13px; transition: all .15s;
    }
    .sa-nav a:hover, .sa-nav a.active { background: rgba(255,255,255,.07); color: #fff; }
    .sa-nav a i { font-size: 15px; width: 18px; text-align: center; }
    .sa-sidebar .sa-footer {
        padding: 12px 20px; border-top: 1px solid rgba(255,255,255,.08);
    }
    .sa-main { margin-left: 220px; min-height: 100vh; }
    .sa-topbar {
        background: #fff; border-bottom: 1px solid #e2e8f0;
        padding: 12px 24px; display: flex; align-items: center;
        justify-content: space-between; position: sticky; top: 0; z-index: 50;
    }
    .sa-topbar .breadcrumb { margin: 0; font-size: 13px; }
    .sa-content { padding: 24px; }
    @media (max-width: 768px) {
        .sa-sidebar { display: none; }
        .sa-main { margin-left: 0; }
    }
</style>
</head>
<body>

<div class="sa-sidebar">
    <div class="brand">
        <h6><i class="bi bi-shield-fill-check text-indigo me-2" style="color:#6366f1;"></i> Super Admin</h6>
        <small>ERP Management</small>
    </div>
    <div class="sa-nav">
        <a href="{{ route('super_admin.dashboard') }}" class="{{ request()->routeIs('super_admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="{{ route('super_admin.institutes.index') }}" class="{{ request()->routeIs('super_admin.institutes.*') ? 'active' : '' }}">
            <i class="bi bi-building"></i> Institutes
        </a>
        <a href="{{ route('super_admin.institutes.create') }}" class="{{ request()->routeIs('super_admin.institutes.create') ? 'active' : '' }}">
            <i class="bi bi-plus-circle"></i> Add Institute
        </a>
        <a href="{{ route('super_admin.sms.index') }}" class="{{ request()->routeIs('super_admin.sms.*') ? 'active' : '' }}">
            <i class="bi bi-phone"></i> SMS Management
        </a>
    </div>
    <div class="sa-footer">
        <form method="POST" action="{{ route('super_admin.logout') }}">
            @csrf
            <button class="btn btn-sm btn-outline-danger w-100">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </button>
        </form>
    </div>
</div>

<div class="sa-main">
    <div class="sa-topbar">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('super_admin.dashboard') }}" class="text-decoration-none">Super Admin</a></li>
                @yield('breadcrumb')
            </ol>
        </nav>
        <small class="text-muted">{{ now()->format('d M Y') }}</small>
    </div>

    <div class="sa-content">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 mb-3">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show py-2 mb-3">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
