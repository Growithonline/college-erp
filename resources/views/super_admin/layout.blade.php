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
        @yield('content')
    </div>
</div>

{{-- Toast Notifications --}}
<div id="toast-container" style="position:fixed;bottom:28px;right:28px;z-index:9999;display:flex;flex-direction:column;gap:10px;min-width:320px;max-width:400px;"></div>

@if(session('success'))
<script>window.__flashToast = { type: 'success', message: @json(session('success')) };</script>
@elseif(session('error'))
<script>window.__flashToast = { type: 'error', message: @json(session('error')) };</script>
@elseif($errors->any())
<script>window.__flashToast = { type: 'error', message: @json($errors->first()) };</script>
@endif

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var config = {
        success: { bg: '#f0fdf4', border: '#22c55e', icon: '✓', iconBg: '#22c55e', title: 'Success' },
        error:   { bg: '#fef2f2', border: '#ef4444', icon: '✕', iconBg: '#ef4444', title: 'Error' },
        warning: { bg: '#fffbeb', border: '#f59e0b', icon: '!', iconBg: '#f59e0b', title: 'Warning' },
    };

    window.showToast = function (type, message, duration) {
        duration = duration || 4500;
        var c = config[type] || config.success;
        var container = document.getElementById('toast-container');

        var toast = document.createElement('div');
        toast.style.cssText = [
            'background:' + c.bg,
            'border:1px solid ' + c.border,
            'border-left:4px solid ' + c.border,
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

        toast.innerHTML =
            '<div style="width:28px;height:28px;border-radius:50%;background:' + c.iconBg + ';color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;">' + c.icon + '</div>' +
            '<div style="flex:1;min-width:0;">' +
                '<div style="font-size:13px;font-weight:700;color:#1e293b;margin-bottom:2px;">' + c.title + '</div>' +
                '<div style="font-size:13px;color:#475569;line-height:1.45;word-break:break-word;">' + message + '</div>' +
                '<div class="toast-bar" style="height:3px;border-radius:2px;background:' + c.border + ';margin-top:10px;width:100%;transform-origin:left;transition:width linear ' + duration + 'ms;"></div>' +
            '</div>' +
            '<button onclick="dismissToast(this.closest(\'[data-toast]\'))" style="background:none;border:none;padding:0;cursor:pointer;color:#94a3b8;font-size:16px;line-height:1;flex-shrink:0;margin-top:-2px;">&#x2715;</button>';

        toast.setAttribute('data-toast', '1');
        container.appendChild(toast);

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
                var bar = toast.querySelector('.toast-bar');
                if (bar) { bar.style.width = '0%'; }
            });
        });

        var timer = setTimeout(function () { dismissToast(toast); }, duration);
        toast.__timer = timer;
    };

    window.dismissToast = function (toast) {
        if (!toast || toast.__dismissed) return;
        toast.__dismissed = true;
        clearTimeout(toast.__timer);
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(8px)';
        setTimeout(function () { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 300);
    };

    if (window.__flashToast) {
        showToast(window.__flashToast.type, window.__flashToast.message);
    }
})();
</script>
</body>
</html>
