<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gaurangi Technologies — ERP Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            min-height: 100vh;
            background: #f0f4f8;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-x: hidden;
        }

        /* Background layers */
        .bg-hero {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background: linear-gradient(145deg, #0a1c3d 0%, #0f3d2e 60%, #0a2515 100%);
        }
        .bg-mesh {
            position: fixed; inset: 0; z-index: 1; pointer-events: none; opacity: .07;
            background-image:
                linear-gradient(rgba(255,255,255,.5) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.5) 1px, transparent 1px);
            background-size: 48px 48px;
        }
        .bg-glow1 {
            position: fixed; z-index: 2; pointer-events: none;
            width: 600px; height: 600px; border-radius: 50%;
            background: radial-gradient(circle, rgba(15,76,129,.55) 0%, transparent 70%);
            top: -180px; left: -120px;
        }
        .bg-glow2 {
            position: fixed; z-index: 2; pointer-events: none;
            width: 500px; height: 500px; border-radius: 50%;
            background: radial-gradient(circle, rgba(29,158,117,.45) 0%, transparent 70%);
            bottom: -150px; right: -100px;
        }
        .bg-glow3 {
            position: fixed; z-index: 2; pointer-events: none;
            width: 300px; height: 300px; border-radius: 50%;
            background: radial-gradient(circle, rgba(29,158,117,.25) 0%, transparent 70%);
            top: 40%; left: 55%;
        }

        /* Page content wrapper */
        .page-wrap { position: relative; z-index: 10; flex: 1; display: flex; flex-direction: column; }

        /* Header */
        .site-header {
            padding: 18px 32px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid rgba(255,255,255,.08);
            backdrop-filter: blur(8px);
            background: rgba(10,28,61,.35);
        }
        .brand-logo { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .brand-logo img { height: 36px; width: auto; filter: brightness(1.15); }
        .brand-text .name { font-size: 15px; font-weight: 700; color: #fff; line-height: 1.2; }
        .brand-text .tag  { font-size: 11px; color: rgba(255,255,255,.55); }
        .header-badge {
            font-size: 11px; font-weight: 600;
            padding: 5px 14px; border-radius: 20px;
            background: rgba(29,158,117,.18); color: #4ade80;
            border: 1px solid rgba(29,158,117,.35);
            letter-spacing: .4px;
        }

        /* Hero text */
        .hero-section { padding: 52px 24px 36px; text-align: center; }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
            border-radius: 20px; padding: 5px 14px; font-size: 12px; color: rgba(255,255,255,.7);
            margin-bottom: 20px; backdrop-filter: blur(6px);
        }
        .hero-title {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 800; color: #fff;
            letter-spacing: -.5px; line-height: 1.15;
            margin-bottom: 12px;
        }
        .hero-title .accent {
            background: linear-gradient(90deg, #38bdf8, #4ade80);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .hero-sub { font-size: 15px; color: rgba(255,255,255,.6); max-width: 440px; margin: 0 auto; }

        /* Cards grid */
        .portals-section { padding: 20px 24px 48px; }
        .portals-grid { display: grid; gap: 20px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); max-width: 1060px; margin: 0 auto; }

        .portal-card {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 20px;
            padding: 28px 20px 22px;
            text-decoration: none; color: inherit;
            display: flex; flex-direction: column; align-items: center; gap: 12px;
            backdrop-filter: blur(12px);
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease, background .22s ease;
            cursor: pointer;
            position: relative; overflow: hidden;
        }
        .portal-card::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; height: 3px;
            background: var(--card-gradient);
            opacity: 0; transition: opacity .22s;
        }
        .portal-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 48px rgba(0,0,0,.4), 0 0 0 1px var(--card-color);
            background: rgba(255,255,255,.1);
            border-color: var(--card-color);
        }
        .portal-card:hover::before { opacity: 1; }

        .card-icon-wrap {
            width: 64px; height: 64px; border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            background: var(--card-bg);
            font-size: 26px; color: var(--card-color);
            transition: transform .22s ease, background .22s ease;
        }
        .portal-card:hover .card-icon-wrap {
            background: var(--card-color);
            color: #fff;
            transform: scale(1.08) rotate(-4deg);
        }

        .card-name { font-size: 14px; font-weight: 700; color: #fff; text-align: center; }
        .card-desc { font-size: 11px; color: rgba(255,255,255,.5); text-align: center; line-height: 1.4; }

        .card-btn {
            margin-top: auto;
            width: 100%; padding: 9px 0; border-radius: 10px;
            font-size: 13px; font-weight: 600; color: var(--card-color);
            background: var(--card-bg);
            border: 1px solid rgba(255,255,255,.08);
            text-align: center;
            transition: background .18s, color .18s;
        }
        .portal-card:hover .card-btn {
            background: var(--card-color);
            color: #fff;
        }

        /* Footer */
        .site-footer {
            text-align: center; padding: 18px 24px;
            font-size: 12px; color: rgba(255,255,255,.35);
            border-top: 1px solid rgba(255,255,255,.07);
            background: rgba(0,0,0,.15);
            position: relative; z-index: 10;
        }
        .site-footer a { color: rgba(255,255,255,.5); text-decoration: none; }
        .site-footer a:hover { color: #4ade80; }

        /* Floating orbs animation */
        @keyframes floatOrb { 0%,100% { transform: translateY(0) scale(1); } 50% { transform: translateY(-20px) scale(1.05); } }
        .bg-glow1 { animation: floatOrb 8s ease-in-out infinite; }
        .bg-glow2 { animation: floatOrb 10s ease-in-out infinite reverse; }
        .bg-glow3 { animation: floatOrb 6s ease-in-out infinite; }

        @media (max-width: 576px) {
            .portals-grid { grid-template-columns: repeat(2, 1fr); }
            .site-header { padding: 14px 18px; }
            .header-badge { display: none; }
        }
        @media (max-width: 360px) {
            .portals-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

{{-- Background --}}
<div class="bg-hero"></div>
<div class="bg-mesh"></div>
<div class="bg-glow1"></div>
<div class="bg-glow2"></div>
<div class="bg-glow3"></div>

<div class="page-wrap">

    {{-- Header --}}
    <header class="site-header">
        <a class="brand-logo" href="#">
            <img src="{{ asset('images/logog.png') }}" alt="Gaurangi Technologies Logo">
            <div class="brand-text">
                <div class="name">Gaurangi Technologies</div>
                <div class="tag">Education Management Platform</div>
            </div>
        </a>
        <span class="header-badge">
            <i class="bi bi-circle-fill" style="font-size:7px;"></i> ERP Portal
        </span>
    </header>

    {{-- Hero --}}
    <section class="hero-section">
        <div class="hero-badge">
            <i class="bi bi-stars"></i> Integrated College Management System
        </div>
        <h1 class="hero-title">
            Welcome to <span class="accent">ERP Portal</span>
        </h1>
        <p class="hero-sub">Select your role below to access your personalized dashboard and tools.</p>
    </section>

    {{-- Portals --}}
    <section class="portals-section">
        <div class="portals-grid">

            {{-- Institute Admin --}}
            <a href="{{ url('/login') }}" class="portal-card"
               style="--card-color:#818cf8;--card-bg:rgba(129,140,248,.12);--card-gradient:linear-gradient(90deg,#6366f1,#818cf8);">
                <div class="card-icon-wrap">
                    <i class="bi bi-building-fill-gear"></i>
                </div>
                <div>
                    <div class="card-name">Institute Admin</div>
                    <div class="card-desc">Manage your institution, sessions & settings</div>
                </div>
                <div class="card-btn"><i class="bi bi-box-arrow-in-right me-1"></i>Login</div>
            </a>

            {{-- Staff --}}
            <a href="{{ url('/staff/login') }}" class="portal-card"
               style="--card-color:#34d399;--card-bg:rgba(52,211,153,.12);--card-gradient:linear-gradient(90deg,#059669,#34d399);">
                <div class="card-icon-wrap">
                    <i class="bi bi-person-workspace"></i>
                </div>
                <div>
                    <div class="card-name">Staff</div>
                    <div class="card-desc">Teachers, admission & administrative staff</div>
                </div>
                <div class="card-btn"><i class="bi bi-box-arrow-in-right me-1"></i>Login</div>
            </a>

            {{-- Center --}}
            <a href="{{ url('/center/login') }}" class="portal-card"
               style="--card-color:#38bdf8;--card-bg:rgba(56,189,248,.12);--card-gradient:linear-gradient(90deg,#0284c7,#38bdf8);">
                <div class="card-icon-wrap">
                    <i class="bi bi-building-fill"></i>
                </div>
                <div>
                    <div class="card-name">Center</div>
                    <div class="card-desc">Study center portal for admissions & fee</div>
                </div>
                <div class="card-btn"><i class="bi bi-box-arrow-in-right me-1"></i>Login</div>
            </a>

            {{-- Channel Partner --}}
            <a href="{{ url('/partner/login') }}" class="portal-card"
               style="--card-color:#fb923c;--card-bg:rgba(251,146,60,.12);--card-gradient:linear-gradient(90deg,#ea580c,#fb923c);">
                <div class="card-icon-wrap">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
                <div>
                    <div class="card-name">Channel Partner</div>
                    <div class="card-desc">Partner admissions & commission portal</div>
                </div>
                <div class="card-btn"><i class="bi bi-box-arrow-in-right me-1"></i>Login</div>
            </a>

            {{-- Student --}}
            <a href="{{ url('/student/login') }}" class="portal-card"
               style="--card-color:#a78bfa;--card-bg:rgba(167,139,250,.12);--card-gradient:linear-gradient(90deg,#7c3aed,#a78bfa);">
                <div class="card-icon-wrap">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div>
                    <div class="card-name">Student</div>
                    <div class="card-desc">Access your results, notices & profile</div>
                </div>
                <div class="card-btn"><i class="bi bi-box-arrow-in-right me-1"></i>Login</div>
            </a>

        </div>
    </section>

    {{-- Footer --}}
    <footer class="site-footer">
        <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
            <img src="{{ asset('images/logog.png') }}" alt="Gaurangi" style="height:18px;opacity:.5;filter:brightness(1.5);">
            <span>&copy; {{ date('Y') }} <a href="#">Gaurangi Technologies</a>. All rights reserved.</span>
            <span style="opacity:.4;">·</span>
            <span>College ERP v1.0</span>
        </div>
    </footer>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
