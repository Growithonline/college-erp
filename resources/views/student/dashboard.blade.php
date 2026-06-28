<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal — {{ $student->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-w: 260px;
            --topbar-h: 60px;
            --primary: #3b82f6;
            --primary-dark: #1d4ed8;
            --sidebar-bg: #0f172a;
            --sidebar-hover: rgba(59,130,246,.12);
            --sidebar-active: rgba(59,130,246,.18);
            --body-bg: #f1f5f9;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --success: #16a34a;
            --danger: #dc2626;
            --warning: #d97706;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--body-bg); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: var(--text-main); overflow-x: hidden; }

        /* ─── TOPBAR ─── */
        .topbar {
            position: fixed; top: 0; left: 0; right: 0; height: var(--topbar-h); z-index: 200;
            background: #fff; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; padding: 0 24px 0 calc(var(--sidebar-w) + 24px);
            gap: 16px;
        }
        .topbar-title { font-size: 15px; font-weight: 600; color: var(--text-main); flex: 1; }
        .topbar-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: #eff6ff; border: 1px solid #bfdbfe;
            color: var(--primary-dark); border-radius: 20px;
            padding: 5px 14px; font-size: 13px; font-weight: 600;
        }
        .topbar-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 14px; font-weight: 700; cursor: pointer;
            border: 2px solid #dbeafe; flex-shrink: 0;
        }
        .logout-btn {
            display: inline-flex; align-items: center; gap: 6px;
            background: #fef2f2; border: 1px solid #fecaca; color: #dc2626;
            border-radius: 8px; padding: 6px 14px; font-size: 13px; font-weight: 500;
            cursor: pointer; text-decoration: none; transition: background .15s;
        }
        .logout-btn:hover { background: #fee2e2; }

        /* ─── SIDEBAR ─── */
        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0; width: var(--sidebar-w); z-index: 300;
            background: var(--sidebar-bg);
            display: flex; flex-direction: column;
            box-shadow: 4px 0 24px rgba(0,0,0,.15);
        }
        .sidebar-brand {
            height: var(--topbar-h); display: flex; align-items: center; gap: 10px;
            padding: 0 20px; border-bottom: 1px solid rgba(255,255,255,.06);
            flex-shrink: 0;
        }
        .sidebar-brand-icon {
            width: 34px; height: 34px; border-radius: 8px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex; align-items: center; justify-content: center; color: #fff; font-size: 16px;
        }
        .sidebar-brand-text { font-size: 15px; font-weight: 700; color: #fff; }
        .sidebar-brand-sub { font-size: 10px; color: rgba(255,255,255,.4); }

        /* Student mini-card inside sidebar */
        .sidebar-profile {
            margin: 16px 14px; border-radius: 12px;
            background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.08);
            padding: 14px; display: flex; align-items: center; gap: 10px; flex-shrink: 0;
        }
        .sidebar-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border: 2px solid rgba(255,255,255,.2);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 15px; font-weight: 700; flex-shrink: 0; overflow: hidden;
        }
        .sidebar-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .sidebar-name { font-size: 13px; font-weight: 600; color: #fff; line-height: 1.3; }
        .sidebar-uid { font-size: 10px; color: rgba(255,255,255,.45); margin-top: 1px; }

        /* Nav */
        .sidebar-nav { flex: 1; overflow-y: auto; padding: 6px 10px 10px; }
        .sidebar-nav::-webkit-scrollbar { width: 4px; }
        .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 4px; }

        .nav-label {
            font-size: 9px; font-weight: 700; color: rgba(255,255,255,.25);
            text-transform: uppercase; letter-spacing: .1em;
            padding: 14px 10px 6px;
        }
        .nav-link-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: 9px;
            color: rgba(255,255,255,.55); font-size: 13.5px; font-weight: 500;
            text-decoration: none; transition: all .15s; cursor: pointer;
            border: 1px solid transparent; margin-bottom: 2px; position: relative;
        }
        .nav-link-item i { font-size: 16px; width: 18px; text-align: center; flex-shrink: 0; }
        .nav-link-item:hover { color: #fff; background: var(--sidebar-hover); }
        .nav-link-item.active {
            color: #fff; background: var(--sidebar-active);
            border-color: rgba(59,130,246,.3);
        }
        .nav-link-item.active::before {
            content: ''; position: absolute; left: 0; top: 20%; bottom: 20%;
            width: 3px; border-radius: 0 3px 3px 0; background: var(--primary);
        }
        .nav-badge {
            margin-left: auto; background: #dc2626; color: #fff;
            border-radius: 10px; font-size: 10px; font-weight: 700;
            padding: 1px 7px; min-width: 20px; text-align: center;
        }
        .sidebar-footer {
            padding: 12px 14px; border-top: 1px solid rgba(255,255,255,.06); flex-shrink: 0;
        }

        /* ─── MAIN CONTENT ─── */
        .main {
            margin-left: var(--sidebar-w);
            padding: calc(var(--topbar-h) + 24px) 28px 32px;
            min-height: 100vh;
        }

        /* ─── HERO BANNER ─── */
        .hero-banner {
            background: linear-gradient(135deg, #1e3a5f 0%, #1d4ed8 60%, #3b82f6 100%);
            border-radius: 18px; padding: 28px 32px;
            display: flex; align-items: center; gap: 24px;
            margin-bottom: 24px; position: relative; overflow: hidden;
        }
        .hero-banner::before {
            content: ''; position: absolute; top: -40px; right: -40px;
            width: 240px; height: 240px; border-radius: 50%;
            background: rgba(255,255,255,.06);
        }
        .hero-banner::after {
            content: ''; position: absolute; bottom: -60px; right: 120px;
            width: 160px; height: 160px; border-radius: 50%;
            background: rgba(255,255,255,.04);
        }
        .hero-avatar {
            width: 76px; height: 76px; border-radius: 50%;
            border: 3px solid rgba(255,255,255,.35);
            background: rgba(255,255,255,.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 30px; color: #fff; flex-shrink: 0; overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,.2);
        }
        .hero-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .hero-name { font-size: 22px; font-weight: 700; color: #fff; letter-spacing: -.2px; }
        .hero-course { font-size: 13px; color: rgba(255,255,255,.75); margin-top: 3px; }
        .hero-badges { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
        .hero-chip {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.2);
            color: #fff; border-radius: 20px; padding: 4px 12px; font-size: 12px; font-weight: 500;
        }
        .hero-chip.green { background: rgba(22,163,74,.25); border-color: rgba(22,163,74,.4); }
        .hero-chip.red   { background: rgba(220,38,38,.25); border-color: rgba(220,38,38,.4); }

        /* ─── STAT CARDS ─── */
        .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card {
            background: #fff; border-radius: 14px; padding: 18px 20px;
            border: 1px solid var(--border);
            display: flex; align-items: center; gap: 14px;
            transition: transform .15s, box-shadow .15s; cursor: default;
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.08); }
        .stat-card.clickable { cursor: pointer; }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; flex-shrink: 0;
        }
        .stat-icon.blue   { background: #eff6ff; color: #2563eb; }
        .stat-icon.green  { background: #f0fdf4; color: #16a34a; }
        .stat-icon.orange { background: #fff7ed; color: #ea580c; }
        .stat-icon.purple { background: #faf5ff; color: #7c3aed; }
        .stat-icon.red    { background: #fef2f2; color: #dc2626; }
        .stat-num { font-size: 22px; font-weight: 700; color: var(--text-main); line-height: 1; }
        .stat-label { font-size: 12px; color: var(--text-muted); margin-top: 4px; }

        /* ─── SECTION CARD ─── */
        .section-card {
            background: #fff; border-radius: 14px; border: 1px solid var(--border);
            box-shadow: 0 1px 4px rgba(0,0,0,.04); overflow: hidden; margin-bottom: 24px;
        }
        .section-header {
            padding: 16px 24px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 10px;
        }
        .section-header-icon {
            width: 32px; height: 32px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center; font-size: 15px;
        }
        .section-header-title { font-size: 14px; font-weight: 700; color: var(--text-main); }
        .section-header-sub { font-size: 12px; color: var(--text-muted); margin-top: 1px; }
        .section-body { padding: 20px 24px; }

        /* ─── INFO GRID ─── */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
        .info-item {
            padding: 13px 0; border-bottom: 1px solid #f1f5f9;
            display: flex; flex-direction: column; gap: 3px;
        }
        .info-item:nth-last-child(-n+2) { border-bottom: none; }
        .info-item-label { font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
        .info-item-value { font-size: 14px; color: var(--text-main); font-weight: 500; }
        .info-divider { border: none; border-left: 1px solid #f1f5f9; margin: 0 24px; }

        /* full-width info row */
        .info-row-full {
            padding: 12px 0; border-bottom: 1px solid #f1f5f9;
            display: flex; flex-direction: column; gap: 3px;
        }
        .info-row-full:last-child { border-bottom: none; }

        /* ─── SUBJECT CHIPS ─── */
        .subject-chip {
            display: inline-flex; align-items: center; gap: 5px;
            background: #eff6ff; border: 1px solid #bfdbfe;
            color: #1d4ed8; border-radius: 8px;
            padding: 6px 12px; font-size: 12px; font-weight: 500; margin: 4px;
        }
        .subject-chip .code { font-size: 10px; background: #dbeafe; color: #1e40af; border-radius: 4px; padding: 1px 5px; }

        /* ─── TAB NAV ─── */
        .tab-nav-wrapper {
            background: #fff; border-radius: 14px 14px 0 0;
            border: 1px solid var(--border); border-bottom: none;
            padding: 0 16px;
        }
        .tab-nav { display: flex; gap: 4px; border: none; flex-wrap: wrap; }
        .tab-nav .nav-link {
            color: var(--text-muted); border: none; border-radius: 0;
            padding: 14px 16px; font-size: 13px; font-weight: 500;
            border-bottom: 2px solid transparent;
            display: flex; align-items: center; gap: 6px;
            transition: color .15s, border-color .15s;
        }
        .tab-nav .nav-link:hover { color: var(--primary); background: none; }
        .tab-nav .nav-link.active {
            color: var(--primary); background: none; border: none;
            border-bottom: 2px solid var(--primary); font-weight: 600;
        }
        .tab-content-wrapper {
            background: #fff; border-radius: 0 0 14px 14px;
            border: 1px solid var(--border); border-top: none;
            box-shadow: 0 2px 8px rgba(0,0,0,.04);
        }
        .tab-pane { padding: 24px; }

        /* ─── FEE CARDS ─── */
        .fee-summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 24px; }
        .fee-summary-card { border-radius: 12px; padding: 18px 20px; text-align: center; }
        .fee-summary-card .amount { font-size: 22px; font-weight: 700; }
        .fee-summary-card .lbl { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
        .fee-progress { height: 6px; border-radius: 3px; background: #e2e8f0; margin: 16px 0; overflow: hidden; }
        .fee-progress-bar { height: 100%; border-radius: 3px; background: linear-gradient(90deg, #22c55e, #16a34a); transition: width .6s ease; }

        .invoice-card {
            border: 1px solid var(--border); border-radius: 12px; margin-bottom: 12px; overflow: hidden;
            transition: box-shadow .15s;
        }
        .invoice-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.07); }
        .invoice-head {
            padding: 14px 18px; background: #f8fafc; cursor: pointer;
            display: flex; justify-content: space-between; align-items: center;
            user-select: none;
        }
        .invoice-head:hover { background: #f1f5f9; }
        .invoice-no { font-size: 13px; font-weight: 700; color: var(--text-main); }
        .invoice-meta { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
        .invoice-amount { font-size: 16px; font-weight: 700; color: var(--success); }
        .invoice-body { padding: 0 18px 14px; }
        .invoice-table { font-size: 13px; width: 100%; border-collapse: collapse; margin-top: 12px; }
        .invoice-table th { font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; padding: 6px 0; border-bottom: 1px solid var(--border); }
        .invoice-table td { padding: 8px 0; border-bottom: 1px solid #f8fafc; }
        .invoice-table tr:last-child td { border-bottom: none; }

        /* ─── NOTICE CARDS ─── */
        .notice-card {
            border-radius: 12px; padding: 16px 18px; margin-bottom: 10px;
            border: 1px solid var(--border); background: #fff;
            border-left: 4px solid var(--border); cursor: pointer;
            transition: box-shadow .15s, border-left-color .15s;
        }
        .notice-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.07); }
        .notice-card.unread { background: #fafcff; border-left-color: var(--primary); }
        .notice-card.pinned { border-left-color: var(--warning) !important; }
        .notice-card.type-exam   { border-left-color: #f59e0b; }
        .notice-card.type-fee    { border-left-color: #16a34a; }
        .notice-card.type-urgent { border-left-color: #dc2626; }
        .notice-card.type-holiday { border-left-color: #7c3aed; }
        .notice-card.type-event  { border-left-color: #0891b2; }
        .notice-type-badge {
            display: inline-block; border-radius: 6px; font-size: 11px;
            font-weight: 600; padding: 3px 9px; text-transform: capitalize;
        }
        .badge-general  { background: #f1f5f9; color: #475569; }
        .badge-exam     { background: #fef3c7; color: #92400e; }
        .badge-fee      { background: #dcfce7; color: #166534; }
        .badge-urgent   { background: #fee2e2; color: #991b1b; }
        .badge-holiday  { background: #f3e8ff; color: #6b21a8; }
        .badge-event    { background: #cffafe; color: #155e75; }

        /* ─── TRANSPORT ─── */
        .route-strip {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            border-radius: 12px; padding: 20px 24px; color: #fff; margin-bottom: 18px;
        }
        .route-line {
            display: flex; align-items: center; gap: 10px; margin-top: 12px;
        }
        .route-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }
        .route-line-bar { flex: 1; height: 2px; background: rgba(255,255,255,.2); position: relative; }
        .route-line-bar::after {
            content: ''; position: absolute; top: -3px; left: 50%; transform: translateX(-50%);
            width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,.4);
        }
        .info-chip {
            display: inline-flex; align-items: center; gap: 8px;
            background: #f8fafc; border: 1px solid var(--border);
            border-radius: 10px; padding: 10px 14px; font-size: 13px; width: 100%;
        }
        .chip-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

        /* ─── ALERTS ─── */
        .alert-pro {
            border-radius: 10px; padding: 12px 16px; font-size: 13px;
            display: flex; align-items: flex-start; gap: 10px; margin-bottom: 20px;
        }
        .alert-pro.success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
        .alert-pro.info    { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }

        /* ─── EDU TABLE ─── */
        .edu-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .edu-table th { background: #f8fafc; font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: .04em; padding: 10px 14px; border-bottom: 1px solid var(--border); }
        .edu-table td { padding: 11px 14px; border-bottom: 1px solid #f8fafc; color: var(--text-main); }
        .edu-table tr:last-child td { border-bottom: none; }
        .edu-table tr:hover td { background: #f8fafc; }

        /* ─── MOBILE ─── */
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 250; }
        .mobile-menu-btn {
            display: none; position: fixed; top: 12px; left: 14px; z-index: 400;
            width: 36px; height: 36px; background: var(--primary); border: none;
            border-radius: 9px; color: #fff; font-size: 16px; cursor: pointer;
            align-items: center; justify-content: center;
        }

        @media (max-width: 991px) {
            .topbar { padding-left: 62px; }
            .sidebar { left: calc(-1 * var(--sidebar-w)); transition: left .25s ease; }
            .sidebar.open { left: 0; }
            .sidebar-overlay.open { display: block; }
            .mobile-menu-btn { display: flex; }
            .main { margin-left: 0; padding: calc(var(--topbar-h) + 20px) 16px 24px; }
            .stat-grid { grid-template-columns: repeat(2, 1fr); }
            .info-grid { grid-template-columns: 1fr; }
            .fee-summary-grid { grid-template-columns: repeat(3, 1fr); gap: 8px; }
            .hero-banner { padding: 20px; }
            .hero-name { font-size: 18px; }
        }
        @media (max-width: 576px) {
            .stat-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .fee-summary-grid { grid-template-columns: 1fr; }
            .tab-nav .nav-link { padding: 10px 12px; font-size: 12px; }
        }
    </style>
</head>
<body>

{{-- Mobile Menu Button --}}
<button class="mobile-menu-btn" id="menuBtn"><i class="bi bi-list"></i></button>
<div class="sidebar-overlay" id="overlay"></div>

{{-- SIDEBAR --}}
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon"><i class="bi bi-mortarboard-fill"></i></div>
        <div>
            <div class="sidebar-brand-text">Student Portal</div>
            <div class="sidebar-brand-sub">{{ $student->institute?->name ?? config('app.name') }}</div>
        </div>
    </div>

    {{-- Student mini card --}}
    <div class="sidebar-profile">
        <div class="sidebar-avatar">
            @if($student->photo)
                <img src="{{ asset('storage/' . $student->photo) }}" alt="">
            @else
                {{ strtoupper(substr($student->name, 0, 1)) }}
            @endif
        </div>
        <div style="min-width:0;">
            <div class="sidebar-name text-truncate">{{ $student->name }}</div>
            <div class="sidebar-uid">{{ $student->student_uid }}</div>
        </div>
    </div>

    <div class="sidebar-nav">
        <div class="nav-label">Navigation</div>

        <a class="nav-link-item active" data-tab="__hero__">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>
        <a class="nav-link-item" data-tab="profile">
            <i class="bi bi-person-lines-fill"></i> My Profile
        </a>
        <a class="nav-link-item" data-tab="academic">
            <i class="bi bi-book-fill"></i> Academic
        </a>
        <a class="nav-link-item" data-tab="fee">
            <i class="bi bi-receipt-cutoff"></i> Fee Details
        </a>
        <a class="nav-link-item" data-tab="notices">
            <i class="bi bi-megaphone-fill"></i> Notices
            @php $unread = collect($notices ?? [])->filter(fn($n) => !in_array($n->id, $readNoticeIds ?? []))->count(); @endphp
            @if($unread > 0)<span class="nav-badge">{{ $unread }}</span>@endif
        </a>
        <a class="nav-link-item" data-tab="transport">
            <i class="bi bi-bus-front-fill"></i> Transport
        </a>

        <div class="nav-label">Account</div>
        <a class="nav-link-item" href="{{ route('student.change-password') }}">
            <i class="bi bi-key-fill"></i> Change Password
        </a>
    </div>

    <div class="sidebar-footer">
        <form method="POST" action="{{ route('student.logout') }}">
            @csrf
            <button type="submit" class="logout-btn w-100" style="border:none;">
                <i class="bi bi-box-arrow-left"></i> Sign Out
            </button>
        </form>
    </div>
</div>

{{-- TOPBAR --}}
<div class="topbar">
    <div class="topbar-title" id="topbarTitle">Dashboard</div>
    <div class="topbar-badge">
        <i class="bi bi-calendar3"></i>
        {{ $student->session?->name ?? '—' }}
    </div>
    <div class="topbar-avatar" title="{{ $student->name }}">
        @if($student->photo)
            <img src="{{ asset('storage/' . $student->photo) }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="">
        @else
            {{ strtoupper(substr($student->name, 0, 1)) }}
        @endif
    </div>
</div>

{{-- MAIN --}}
<div class="main">

    @if(session('success'))
    <div class="alert-pro success">
        <i class="bi bi-check-circle-fill" style="margin-top:1px;flex-shrink:0;"></i>
        <div>{{ session('success') }}</div>
    </div>
    @endif
    @if(session('info'))
    <div class="alert-pro info">
        <i class="bi bi-info-circle-fill" style="margin-top:1px;flex-shrink:0;"></i>
        <div>{{ session('info') }}</div>
    </div>
    @endif

    {{-- ═══ HERO BANNER ═══ --}}
    <div class="hero-banner" id="section-hero">
        <div class="hero-avatar">
            @if($student->photo)
                <img src="{{ asset('storage/' . $student->photo) }}" alt="Photo">
            @else
                <i class="bi bi-person-fill" style="font-size:34px;"></i>
            @endif
        </div>
        <div style="flex:1;min-width:0;position:relative;z-index:1;">
            <div class="hero-name">{{ $student->name }}</div>
            <div class="hero-course">
                {{ $student->stream?->course?->name ?? '—' }}
                @if($student->stream?->name) · {{ $student->stream->name }}@endif
            </div>
            <div class="hero-badges">
                <span class="hero-chip green"><i class="bi bi-patch-check-fill" style="font-size:11px;"></i> {{ ucfirst($student->status ?? 'Active') }}</span>
                <span class="hero-chip"><i class="bi bi-mortarboard" style="font-size:11px;"></i> Semester {{ $student->current_semester }}</span>
                @if($student->enrollment_no)
                <span class="hero-chip"><i class="bi bi-hash" style="font-size:11px;"></i> {{ $student->enrollment_no }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══ STAT CARDS ═══ --}}
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-layers-fill"></i></div>
            <div>
                <div class="stat-num">{{ $student->current_semester ?? '—' }}</div>
                <div class="stat-label">Current Semester</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="bi bi-journals"></i></div>
            <div>
                <div class="stat-num">{{ $student->subjects->count() }}</div>
                <div class="stat-label">Enrolled Subjects</div>
            </div>
        </div>
        <div class="stat-card clickable" onclick="switchSection('fee')">
            @if($totalDue > 0)
            <div class="stat-icon red"><i class="bi bi-exclamation-circle-fill"></i></div>
            <div>
                <div class="stat-num" style="color:var(--danger);">₹{{ number_format($totalDue, 0) }}</div>
                <div class="stat-label">Fee Due</div>
            </div>
            @else
            <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="stat-num" style="color:var(--success);">Cleared</div>
                <div class="stat-label">Fee Status</div>
            </div>
            @endif
        </div>
        <div class="stat-card clickable" onclick="switchSection('notices')">
            <div class="stat-icon orange"><i class="bi bi-megaphone-fill"></i></div>
            <div>
                <div class="stat-num">
                    {{ $notices->count() }}
                    @if($unread > 0)<span style="font-size:13px;color:var(--danger);font-weight:700;"> +{{ $unread }}</span>@endif
                </div>
                <div class="stat-label">Notices@if($unread > 0) · {{ $unread }} Unread@endif</div>
            </div>
        </div>
    </div>

    {{-- ═══ TABBED SECTIONS ═══ --}}
    <div class="tab-nav-wrapper">
        <ul class="nav tab-nav" id="mainTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#tab-profile">
                    <i class="bi bi-person"></i> Profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-academic">
                    <i class="bi bi-book"></i> Academic
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-fee">
                    <i class="bi bi-receipt"></i> Fee Details
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-notices">
                    <i class="bi bi-megaphone"></i> Notices
                    @if($unread > 0)<span class="ms-1" style="background:#dc2626;color:#fff;border-radius:10px;font-size:10px;font-weight:700;padding:1px 6px;">{{ $unread }}</span>@endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-transport">
                    <i class="bi bi-bus-front"></i> Transport
                </a>
            </li>
        </ul>
    </div>

    <div class="tab-content-wrapper">
        <div class="tab-content">

            {{-- ══════════════════════════════════════════════════
                 TAB 1 · PROFILE
            ═══════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade show active" id="tab-profile">

                {{-- Personal Info --}}
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-header-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-person-fill"></i></div>
                        <div>
                            <div class="section-header-title">Personal Information</div>
                            <div class="section-header-sub">Basic personal details on record</div>
                        </div>
                    </div>
                    <div class="section-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-item-label">Full Name</span>
                                <span class="info-item-value">{{ $student->name }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Date of Birth</span>
                                <span class="info-item-value">{{ $student->dob?->format('d M Y') ?? '—' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Gender</span>
                                <span class="info-item-value">{{ ucfirst($student->gender ?? '—') }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Mobile</span>
                                <span class="info-item-value">
                                    @if($student->mobile)
                                        <a href="tel:{{ $student->mobile }}" style="color:var(--primary);text-decoration:none;">{{ $student->mobile }}</a>
                                    @else — @endif
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Email</span>
                                <span class="info-item-value" style="word-break:break-all;">{{ $student->email ?? '—' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Aadhaar No.</span>
                                <span class="info-item-value">{{ $student->aadhar_no ? '●●●●●●●●' . substr($student->aadhar_no, -4) : '—' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Category</span>
                                <span class="info-item-value">{{ strtoupper($student->category ?? '—') }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Nationality</span>
                                <span class="info-item-value">{{ $student->nationality ?? '—' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Religion</span>
                                <span class="info-item-value">{{ ucfirst($student->religion ?? '—') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Family Info --}}
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-header-icon" style="background:#faf5ff;color:#7c3aed;"><i class="bi bi-people-fill"></i></div>
                        <div>
                            <div class="section-header-title">Family Information</div>
                            <div class="section-header-sub">Parent & guardian details</div>
                        </div>
                    </div>
                    <div class="section-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-item-label">Father's Name</span>
                                <span class="info-item-value">{{ $student->father_name ?? '—' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Father's Mobile</span>
                                <span class="info-item-value">
                                    @if($student->father_mobile)
                                        <a href="tel:{{ $student->father_mobile }}" style="color:var(--primary);text-decoration:none;">{{ $student->father_mobile }}</a>
                                    @else — @endif
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Father's Occupation</span>
                                <span class="info-item-value">{{ $student->father_occupation ?? '—' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Mother's Name</span>
                                <span class="info-item-value">{{ $student->mother_name ?? '—' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Mother's Mobile</span>
                                <span class="info-item-value">
                                    @if($student->mother_mobile)
                                        <a href="tel:{{ $student->mother_mobile }}" style="color:var(--primary);text-decoration:none;">{{ $student->mother_mobile }}</a>
                                    @else — @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Address --}}
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-header-icon" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-geo-alt-fill"></i></div>
                        <div>
                            <div class="section-header-title">Address</div>
                            <div class="section-header-sub">Permanent & communication address</div>
                        </div>
                    </div>
                    <div class="section-body">
                        <div class="info-row-full">
                            <span class="info-item-label">Permanent Address</span>
                            <span class="info-item-value" style="margin-top:4px;">
                                {{ collect([$student->perm_address, $student->perm_village, $student->perm_post, $student->perm_district, $student->perm_state, $student->perm_pincode])->filter()->implode(', ') ?: '—' }}
                            </span>
                        </div>
                        @if($student->comm_same_as_perm)
                        <div class="info-row-full">
                            <span class="info-item-label">Communication Address</span>
                            <span class="info-item-value text-muted fst-italic" style="margin-top:4px;">Same as permanent address</span>
                        </div>
                        @else
                        <div class="info-row-full">
                            <span class="info-item-label">Communication Address</span>
                            <span class="info-item-value" style="margin-top:4px;">
                                {{ collect([$student->comm_address, $student->comm_city, $student->comm_district, $student->comm_state, $student->comm_pincode])->filter()->implode(', ') ?: '—' }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Disclaimer --}}
                <div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:12px 16px;font-size:13px;color:var(--text-muted);display:flex;align-items:flex-start;gap:8px;">
                    <i class="bi bi-shield-lock-fill" style="color:#94a3b8;margin-top:1px;flex-shrink:0;"></i>
                    Profile information is managed by your college administration. For any changes, please contact your college office.
                </div>

            </div>

            {{-- ══════════════════════════════════════════════════
                 TAB 2 · ACADEMIC
            ═══════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="tab-academic">

                {{-- Course Card --}}
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-header-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-mortarboard-fill"></i></div>
                        <div>
                            <div class="section-header-title">Admission & Course Details</div>
                            <div class="section-header-sub">Enrolled programme information</div>
                        </div>
                    </div>
                    <div class="section-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-item-label">Institute</span>
                                <span class="info-item-value">{{ $student->institute?->name ?? '—' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Academic Session</span>
                                <span class="info-item-value">{{ $student->session?->name ?? '—' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Course</span>
                                <span class="info-item-value fw-bold">{{ $student->stream?->course?->name ?? '—' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Stream / Branch</span>
                                <span class="info-item-value">{{ $student->stream?->name ?? '—' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Year / Part</span>
                                <span class="info-item-value">{{ $student->coursePart?->name ?? '—' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Current Semester</span>
                                <span class="info-item-value">
                                    <span style="background:#dbeafe;color:#1e40af;padding:3px 10px;border-radius:6px;font-weight:600;font-size:13px;">
                                        Semester {{ $student->current_semester }}
                                    </span>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Admission Date</span>
                                <span class="info-item-value">{{ $student->admission_date?->format('d M Y') ?? '—' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Admission Type</span>
                                <span class="info-item-value">{{ ucfirst($student->admission_type ?? '—') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Academic IDs --}}
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-header-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-hash"></i></div>
                        <div>
                            <div class="section-header-title">Academic Identifiers</div>
                            <div class="section-header-sub">Your roll numbers and registration details</div>
                        </div>
                    </div>
                    <div class="section-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-item-label">Student UID</span>
                                <span class="info-item-value fw-bold" style="letter-spacing:.5px;">{{ $student->student_uid }}</span>
                            </div>
                            @if($student->enrollment_no)
                            <div class="info-item">
                                <span class="info-item-label">Enrollment No.</span>
                                <span class="info-item-value">{{ $student->enrollment_no }}</span>
                            </div>
                            @endif
                            @if($student->roll_no)
                            <div class="info-item">
                                <span class="info-item-label">Roll No.</span>
                                <span class="info-item-value">{{ $student->roll_no }}</span>
                            </div>
                            @endif
                            @if($student->sr_no)
                            <div class="info-item">
                                <span class="info-item-label">SR No.</span>
                                <span class="info-item-value">{{ $student->sr_no }}</span>
                            </div>
                            @endif
                            @if($student->exam_form_no)
                            <div class="info-item">
                                <span class="info-item-label">Exam Form No.</span>
                                <span class="info-item-value">{{ $student->exam_form_no }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Subjects --}}
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-header-icon" style="background:#faf5ff;color:#7c3aed;"><i class="bi bi-book-half"></i></div>
                        <div>
                            <div class="section-header-title">Enrolled Subjects</div>
                            <div class="section-header-sub">{{ $student->subjects->count() }} subject(s) this semester</div>
                        </div>
                    </div>
                    <div class="section-body">
                        @if($student->subjects->count())
                            <div>
                                @foreach($student->subjects as $subject)
                                    <span class="subject-chip">
                                        @if($subject->code)
                                            <span class="code">{{ $subject->code }}</span>
                                        @else
                                            <i class="bi bi-journal-text" style="font-size:11px;opacity:.6;"></i>
                                        @endif
                                        {{ $subject->name }}
                                        @if($subject->pivot->subject_role && $subject->pivot->subject_role !== 'regular')
                                            <span style="font-size:10px;opacity:.65;">({{ ucfirst($subject->pivot->subject_role) }})</span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4" style="color:var(--text-muted);">
                                <i class="bi bi-journal-x" style="font-size:32px;opacity:.3;"></i>
                                <p class="mt-2 mb-0" style="font-size:13px;">No subjects assigned yet.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Previous Education --}}
                @if($student->educationDetails->count())
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-header-icon" style="background:#fff7ed;color:#ea580c;"><i class="bi bi-award-fill"></i></div>
                        <div>
                            <div class="section-header-title">Previous Education</div>
                            <div class="section-header-sub">Academic history on record</div>
                        </div>
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="edu-table">
                            <thead>
                                <tr>
                                    <th>Exam / Degree</th>
                                    <th>Institute / Board</th>
                                    <th>Year</th>
                                    <th>Marks</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($student->educationDetails as $edu)
                                <tr>
                                    <td class="fw-semibold">{{ $edu->exam_name }}</td>
                                    <td>
                                        {{ $edu->institute_name ?? '—' }}
                                        @if($edu->board_university)
                                            <br><span style="font-size:11px;color:var(--text-muted);">{{ $edu->board_university }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $edu->passing_year ?? '—' }}</td>
                                    <td>{{ $edu->obtained_marks ?? '—' }} / {{ $edu->max_marks ?? '—' }}</td>
                                    <td>
                                        @if($edu->percentage)
                                            <span style="background:#f0fdf4;color:#16a34a;border-radius:6px;padding:2px 8px;font-size:12px;font-weight:600;">
                                                {{ number_format($edu->percentage, 1) }}%
                                            </span>
                                        @else — @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                {{-- Scholarship --}}
                @if($student->has_scholarship)
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-header-icon" style="background:#fef3c7;color:#d97706;"><i class="bi bi-star-fill"></i></div>
                        <div>
                            <div class="section-header-title">Scholarship</div>
                            <div class="section-header-sub">Financial assistance details</div>
                        </div>
                    </div>
                    <div class="section-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-item-label">Scholarship Name</span>
                                <span class="info-item-value">{{ $student->scholarship_name ?? '—' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Type</span>
                                <span class="info-item-value">{{ ucfirst($student->scholarship_type ?? '—') }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-item-label">Amount</span>
                                <span class="info-item-value fw-bold" style="color:var(--success);">₹{{ number_format($student->scholarship_amount ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

            </div>

            {{-- ══════════════════════════════════════════════════
                 TAB 3 · FEE DETAILS
            ═══════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="tab-fee">

                {{-- Summary --}}
                @php
                    $paidPct = $totalFee > 0 ? min(100, round(($totalPaid / $totalFee) * 100)) : 0;
                @endphp
                <div class="section-card" style="margin-bottom:20px;">
                    <div class="section-header">
                        <div class="section-header-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-wallet2"></i></div>
                        <div>
                            <div class="section-header-title">Fee Summary</div>
                            <div class="section-header-sub">{{ $student->session?->name }} · Current Session</div>
                        </div>
                    </div>
                    <div class="section-body">
                        <div class="fee-summary-grid">
                            <div class="fee-summary-card" style="background:#f8fafc;border:1px solid var(--border);">
                                <div class="amount" style="color:var(--text-main);">₹{{ number_format($totalFee, 2) }}</div>
                                <div class="lbl">Total Fee</div>
                            </div>
                            <div class="fee-summary-card" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                                <div class="amount" style="color:var(--success);">₹{{ number_format($totalPaid, 2) }}</div>
                                <div class="lbl">Paid</div>
                            </div>
                            <div class="fee-summary-card" style="background:{{ $totalDue > 0 ? '#fef2f2' : '#f0fdf4' }};border:1px solid {{ $totalDue > 0 ? '#fecaca' : '#bbf7d0' }};">
                                <div class="amount" style="color:{{ $totalDue > 0 ? 'var(--danger)' : 'var(--success)' }};">₹{{ number_format(abs($totalDue), 2) }}</div>
                                <div class="lbl">{{ $totalDue > 0 ? 'Remaining Due' : 'Advance' }}</div>
                            </div>
                        </div>

                        {{-- Progress bar --}}
                        @if($totalFee > 0)
                        <div style="margin-top:16px;">
                            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-muted);margin-bottom:6px;">
                                <span>Payment Progress</span>
                                <span style="font-weight:600;color:{{ $paidPct >= 100 ? 'var(--success)' : 'var(--primary)' }};">{{ $paidPct }}% Paid</span>
                            </div>
                            <div class="fee-progress">
                                <div class="fee-progress-bar" style="width:{{ $paidPct }}%;background:{{ $paidPct >= 100 ? 'linear-gradient(90deg,#22c55e,#16a34a)' : 'linear-gradient(90deg,#60a5fa,#2563eb)' }};"></div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Invoices --}}
                @if($invoices->isEmpty())
                    <div class="text-center py-5" style="color:var(--text-muted);">
                        <i class="bi bi-receipt" style="font-size:44px;opacity:.25;"></i>
                        <p class="mt-3 mb-0">No fee records found for this session.</p>
                    </div>
                @else
                    <div style="font-size:12px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;">
                        Payment History ({{ $invoices->count() }} Invoice{{ $invoices->count() > 1 ? 's' : '' }})
                    </div>
                    @foreach($invoices as $invoice)
                    <div class="invoice-card">
                        <div class="invoice-head" onclick="toggleInvoice(this)">
                            <div>
                                <div class="invoice-no">
                                    <i class="bi bi-file-earmark-text me-1" style="color:var(--text-muted);"></i>
                                    Invoice #{{ $invoice->invoice_no }}
                                </div>
                                <div class="invoice-meta">
                                    {{ $invoice->payment_date?->format('d M Y') ?? '—' }}
                                    @if($invoice->payment_mode)
                                        · <span style="background:#e0e7ff;color:#3730a3;border-radius:4px;padding:1px 7px;font-size:11px;font-weight:600;">{{ ucfirst($invoice->payment_mode) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div class="text-end">
                                    <div class="invoice-amount">₹{{ number_format($invoice->paid_amount, 2) }}</div>
                                    @if($invoice->total_amount != $invoice->paid_amount)
                                        <div style="font-size:11px;color:var(--text-muted);">of ₹{{ number_format($invoice->total_amount, 2) }}</div>
                                    @endif
                                </div>
                                <i class="bi bi-chevron-down" style="color:var(--text-muted);font-size:13px;transition:transform .2s;" class="toggle-icon"></i>
                            </div>
                        </div>
                        @if($invoice->items->count())
                        <div class="invoice-body" style="display:none;">
                            <table class="invoice-table">
                                <thead>
                                    <tr>
                                        <th style="width:50%;">Fee Head</th>
                                        <th class="text-end">Amount</th>
                                        @if($invoice->items->sum('discount') > 0)<th class="text-end">Discount</th>@endif
                                        <th class="text-end">Net</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->items as $item)
                                    <tr>
                                        <td>{{ $item->fee_name }}</td>
                                        <td class="text-end">₹{{ number_format($item->amount, 2) }}</td>
                                        @if($invoice->items->sum('discount') > 0)
                                            <td class="text-end" style="color:var(--success);">{{ $item->discount > 0 ? '−₹'.number_format($item->discount,2) : '—' }}</td>
                                        @endif
                                        <td class="text-end fw-semibold">₹{{ number_format($item->total_fee, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if($invoice->remarks)
                            <div style="margin-top:10px;font-size:12px;color:var(--text-muted);background:#f8fafc;border-radius:6px;padding:8px 12px;">
                                <i class="bi bi-chat-left-text me-1"></i>{{ $invoice->remarks }}
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>
                    @endforeach
                @endif

            </div>

            {{-- ══════════════════════════════════════════════════
                 TAB 4 · NOTICES
            ═══════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="tab-notices">

                @if($notices->isEmpty())
                    <div class="text-center py-5" style="color:var(--text-muted);">
                        <i class="bi bi-megaphone" style="font-size:44px;opacity:.25;"></i>
                        <p class="mt-3 mb-0">No notices available at the moment.</p>
                    </div>
                @else
                    @if($unread > 0)
                    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:10px 14px;font-size:13px;color:#1e40af;display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                        <i class="bi bi-bell-fill"></i>
                        You have <strong>{{ $unread }} unread notice{{ $unread > 1 ? 's' : '' }}</strong>. Click to mark as read.
                    </div>
                    @endif

                    @foreach($notices as $notice)
                    @php
                        $isRead  = in_array($notice->id, $readNoticeIds);
                        $typeMap = ['general'=>'badge-general','exam'=>'badge-exam','fee'=>'badge-fee','urgent'=>'badge-urgent','holiday'=>'badge-holiday','event'=>'badge-event'];
                        $bdgCls  = $typeMap[$notice->notice_type] ?? 'badge-general';
                        $cardCls = 'type-' . ($notice->notice_type ?? 'general');
                    @endphp
                    <div class="notice-card {{ $cardCls }} {{ $notice->is_pinned ? 'pinned' : '' }} {{ !$isRead ? 'unread' : '' }}"
                         data-notice-id="{{ $notice->id }}"
                         onclick="markRead({{ $notice->id }}, this)">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:8px;">
                            <div style="display:flex;align-items:center;gap-8px;flex-wrap:wrap;gap:6px;">
                                @if($notice->is_pinned)
                                    <i class="bi bi-pin-angle-fill" style="color:var(--warning);" title="Pinned"></i>
                                @endif
                                <span class="notice-type-badge {{ $bdgCls }}">{{ ucfirst($notice->notice_type ?? 'General') }}</span>
                                @if(!$isRead)
                                    <span style="background:#dbeafe;color:#1d4ed8;border-radius:6px;font-size:10px;font-weight:700;padding:2px 8px;">NEW</span>
                                @endif
                            </div>
                            <span style="font-size:12px;color:var(--text-muted);white-space:nowrap;flex-shrink:0;">
                                <i class="bi bi-calendar3 me-1"></i>{{ $notice->notice_date?->format('d M Y') }}
                            </span>
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--text-main);margin-bottom:5px;">{{ $notice->title }}</div>
                        @if($notice->body)
                            <div style="font-size:13px;color:#475569;line-height:1.6;">{{ Str::limit($notice->body, 220) }}</div>
                        @endif
                        @if($notice->attachment)
                            <div style="margin-top:10px;">
                                <a href="{{ asset('storage/' . $notice->attachment) }}" target="_blank"
                                   style="display:inline-flex;align-items:center;gap:5px;background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;border-radius:6px;padding:5px 12px;font-size:12px;font-weight:500;text-decoration:none;"
                                   onclick="event.stopPropagation()">
                                    <i class="bi bi-paperclip"></i> View Attachment
                                </a>
                            </div>
                        @endif
                    </div>
                    @endforeach
                @endif

            </div>

            {{-- ══════════════════════════════════════════════════
                 TAB 5 · TRANSPORT
            ═══════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="tab-transport">

                @if(!$transport)
                    <div class="text-center py-5" style="color:var(--text-muted);">
                        <i class="bi bi-bus-front" style="font-size:44px;opacity:.25;"></i>
                        <p class="mt-3 mb-0">No transport allocation found for this session.</p>
                    </div>
                @else

                {{-- Route Strip --}}
                <div class="route-strip mb-4">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:20px;">
                            <i class="bi bi-bus-front-fill"></i>
                        </div>
                        <div>
                            <div style="font-size:16px;font-weight:700;">{{ $transport->route?->name ?? 'Route' }}</div>
                            <div style="font-size:12px;opacity:.65;margin-top:2px;">Assigned Transport Route</div>
                        </div>
                    </div>
                    @if($transport->route?->start_point || $transport->route?->end_point)
                    <div class="route-line" style="margin-top:14px;">
                        <div class="route-dot" style="background:#22c55e;"></div>
                        <div style="font-size:12px;color:rgba(255,255,255,.8);white-space:nowrap;">{{ $transport->route?->start_point ?? 'Start' }}</div>
                        <div class="route-line-bar"></div>
                        <div style="font-size:12px;color:rgba(255,255,255,.8);white-space:nowrap;">{{ $transport->route?->end_point ?? 'End' }}</div>
                        <div class="route-dot" style="background:#f87171;"></div>
                    </div>
                    @endif
                </div>

                {{-- Fee Summary --}}
                @php $tBal = $transport->balance; @endphp
                <div class="section-card mb-4">
                    <div class="section-header">
                        <div class="section-header-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-wallet2"></i></div>
                        <div><div class="section-header-title">Transport Fee</div></div>
                    </div>
                    <div class="section-body">
                        <div class="fee-summary-grid">
                            <div class="fee-summary-card" style="background:#f8fafc;border:1px solid var(--border);">
                                <div class="amount" style="font-size:18px;color:var(--text-main);">₹{{ number_format($transport->charged_amount ?: $transport->fee_amount, 2) }}</div>
                                <div class="lbl">Total Fee</div>
                            </div>
                            <div class="fee-summary-card" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                                <div class="amount" style="font-size:18px;color:var(--success);">₹{{ number_format($transport->paid_amount, 2) }}</div>
                                <div class="lbl">Paid</div>
                            </div>
                            <div class="fee-summary-card" style="background:{{ $tBal > 0 ? '#fef2f2' : '#f0fdf4' }};border:1px solid {{ $tBal > 0 ? '#fecaca' : '#bbf7d0' }};">
                                <div class="amount" style="font-size:18px;color:{{ $tBal > 0 ? 'var(--danger)' : 'var(--success)' }};">₹{{ number_format(abs($tBal), 2) }}</div>
                                <div class="lbl">{{ $tBal > 0 ? 'Due' : 'Advance' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Stop & Vehicle Details --}}
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-header-icon" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-geo-alt-fill"></i></div>
                        <div><div class="section-header-title">Stop & Vehicle Details</div></div>
                    </div>
                    <div class="section-body">
                        <div class="row g-3">
                            @if($transport->stop?->stop_name)
                            <div class="col-sm-6">
                                <div class="info-chip">
                                    <div class="chip-icon" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-geo-alt-fill"></i></div>
                                    <div>
                                        <div style="font-size:11px;color:var(--text-muted);font-weight:600;">Your Stop</div>
                                        <div style="font-weight:600;font-size:13px;">{{ $transport->stop->stop_name }}</div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if($transport->stop?->landmark)
                            <div class="col-sm-6">
                                <div class="info-chip">
                                    <div class="chip-icon" style="background:#f1f5f9;color:#475569;"><i class="bi bi-building"></i></div>
                                    <div>
                                        <div style="font-size:11px;color:var(--text-muted);font-weight:600;">Landmark</div>
                                        <div style="font-weight:600;font-size:13px;">{{ $transport->stop->landmark }}</div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if($transport->stop?->pickup_time)
                            <div class="col-sm-6">
                                <div class="info-chip">
                                    <div class="chip-icon" style="background:#fef3c7;color:#d97706;"><i class="bi bi-sunrise-fill"></i></div>
                                    <div>
                                        <div style="font-size:11px;color:var(--text-muted);font-weight:600;">Morning Pickup</div>
                                        <div style="font-weight:600;font-size:13px;">{{ \Carbon\Carbon::parse($transport->stop->pickup_time)->format('h:i A') }}</div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if($transport->stop?->drop_time)
                            <div class="col-sm-6">
                                <div class="info-chip">
                                    <div class="chip-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-sunset-fill"></i></div>
                                    <div>
                                        <div style="font-size:11px;color:var(--text-muted);font-weight:600;">Evening Drop</div>
                                        <div style="font-weight:600;font-size:13px;">{{ \Carbon\Carbon::parse($transport->stop->drop_time)->format('h:i A') }}</div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if($transport->vehicle)
                            <div class="col-sm-6">
                                <div class="info-chip">
                                    <div class="chip-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-truck-front-fill"></i></div>
                                    <div>
                                        <div style="font-size:11px;color:var(--text-muted);font-weight:600;">Vehicle No.</div>
                                        <div style="font-weight:700;font-size:13px;letter-spacing:.5px;">{{ $transport->vehicle->vehicle_no }}</div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if($transport->driver)
                            <div class="col-sm-6">
                                <div class="info-chip">
                                    <div class="chip-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-person-badge-fill"></i></div>
                                    <div>
                                        <div style="font-size:11px;color:var(--text-muted);font-weight:600;">Driver</div>
                                        <div style="font-weight:600;font-size:13px;">{{ $transport->driver->name }}</div>
                                    </div>
                                </div>
                            </div>
                            @if($transport->driver->mobile)
                            <div class="col-sm-6">
                                <div class="info-chip">
                                    <div class="chip-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-telephone-fill"></i></div>
                                    <div>
                                        <div style="font-size:11px;color:var(--text-muted);font-weight:600;">Driver Mobile</div>
                                        <a href="tel:{{ $transport->driver->mobile }}" style="font-weight:600;font-size:13px;color:var(--primary);text-decoration:none;">{{ $transport->driver->mobile }}</a>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if($transport->driver->helper_name)
                            <div class="col-sm-6">
                                <div class="info-chip">
                                    <div class="chip-icon" style="background:#f8fafc;color:#64748b;"><i class="bi bi-person-fill"></i></div>
                                    <div>
                                        <div style="font-size:11px;color:var(--text-muted);font-weight:600;">Helper</div>
                                        <div style="font-weight:600;font-size:13px;">{{ $transport->driver->helper_name }}
                                            @if($transport->driver->helper_mobile)
                                                <a href="tel:{{ $transport->driver->helper_mobile }}" style="color:var(--primary);text-decoration:none;font-size:11px;margin-left:4px;">{{ $transport->driver->helper_mobile }}</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @endif
                            @if($transport->start_date || $transport->end_date)
                            <div class="col-sm-6">
                                <div class="info-chip">
                                    <div class="chip-icon" style="background:#faf5ff;color:#7c3aed;"><i class="bi bi-calendar-range-fill"></i></div>
                                    <div>
                                        <div style="font-size:11px;color:var(--text-muted);font-weight:600;">Validity</div>
                                        <div style="font-weight:600;font-size:13px;">
                                            {{ $transport->start_date?->format('d M Y') ?? '—' }} → {{ $transport->end_date?->format('d M Y') ?? 'Ongoing' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                @endif
            </div>

        </div>{{-- .tab-content --}}
    </div>{{-- .tab-content-wrapper --}}

</div>{{-- .main --}}

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const MARK_READ_URL = '{{ route("student.notices.read", ["id" => "__ID__"]) }}';
const CSRF_TOKEN    = '{{ csrf_token() }}';

const TAB_TITLES = {
    'tab-profile':   'My Profile',
    'tab-academic':  'Academic',
    'tab-fee':       'Fee Details',
    'tab-notices':   'Notices',
    'tab-transport': 'Transport',
};

const sidebarTabMap = {
    'profile':   'tab-profile',
    'academic':  'tab-academic',
    'fee':       'tab-fee',
    'notices':   'tab-notices',
    'transport': 'tab-transport',
};

function switchSection(tabId) {
    const targetId = sidebarTabMap[tabId] || tabId;
    const el = document.querySelector(`a[href="#${targetId}"]`);
    if (el) {
        new bootstrap.Tab(el).show();
        document.querySelector('.main').scrollIntoView({ behavior: 'smooth' });
    }
}

// Sidebar nav links
document.querySelectorAll('.nav-link-item[data-tab]').forEach(link => {
    link.addEventListener('click', e => {
        e.preventDefault();
        const tab = link.dataset.tab;
        if (tab === '__hero__') {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            setActiveSidebarLink(link);
            document.getElementById('topbarTitle').textContent = 'Dashboard';
        } else {
            switchSection(tab);
            closeSidebar();
        }
    });
});

// Update sidebar active + topbar title on tab change
document.querySelectorAll('#mainTabs a[data-bs-toggle="tab"]').forEach(el => {
    el.addEventListener('shown.bs.tab', e => {
        const tabId = e.target.getAttribute('href').replace('#', '');
        document.getElementById('topbarTitle').textContent = TAB_TITLES[tabId] || 'Dashboard';
        history.replaceState(null, '', '#' + tabId);

        // Update sidebar
        const sideKey = Object.keys(sidebarTabMap).find(k => sidebarTabMap[k] === tabId);
        if (sideKey) {
            const sideLink = document.querySelector(`.nav-link-item[data-tab="${sideKey}"]`);
            if (sideLink) setActiveSidebarLink(sideLink);
        }
    });
});

function setActiveSidebarLink(el) {
    document.querySelectorAll('.nav-link-item').forEach(l => l.classList.remove('active'));
    el.classList.add('active');
}

// Restore tab from hash
const hash = window.location.hash.replace('#', '');
if (hash && document.querySelector(`#${hash}`)) {
    const tab = document.querySelector(`#mainTabs a[href="#${hash}"]`);
    if (tab) new bootstrap.Tab(tab).show();
}

// Invoice toggle
function toggleInvoice(head) {
    const body = head.nextElementSibling;
    const icon = head.querySelector('.bi-chevron-down, .bi-chevron-up');
    if (!body) return;
    const isOpen = body.style.display !== 'none';
    body.style.display = isOpen ? 'none' : 'block';
    if (icon) {
        icon.classList.toggle('bi-chevron-down', isOpen);
        icon.classList.toggle('bi-chevron-up', !isOpen);
    }
}

// Mark notice read
function markRead(noticeId, card) {
    if (card.classList.contains('unread')) {
        card.classList.remove('unread');
        const badge = card.querySelector('[style*="NEW"], .badge');
        if (badge && badge.textContent.trim() === 'NEW') badge.remove();
        fetch(MARK_READ_URL.replace('__ID__', noticeId), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }
        });
    }
}

// Mobile sidebar
const sidebar  = document.getElementById('sidebar');
const overlay  = document.getElementById('overlay');
const menuBtn  = document.getElementById('menuBtn');
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('open'); }
menuBtn.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay.addEventListener('click', closeSidebar);
</script>
</body>
</html>
