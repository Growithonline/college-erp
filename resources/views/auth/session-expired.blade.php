@php
    $portals = [
        'web'           => ['route' => 'login',               'name' => 'Institute Portal'],
        'center'        => ['route' => 'center.login',        'name' => 'Center Portal'],
        'staff'         => ['route' => 'staff.login',         'name' => 'Staff Portal'],
        'partner'       => ['route' => 'partner.login',       'name' => 'Channel Partner Portal'],
        'student'       => ['route' => 'student.login',       'name' => 'Student Portal'],
        'library_staff' => ['route' => 'library_staff.login', 'name' => 'Library Staff Portal'],
        'super_admin'   => ['route' => 'super_admin.login',   'name' => 'Super Admin'],
    ];

    $reasons = [
        'inactivity'      => ['icon' => 'bi-hourglass-split',  'color' => '#f59e0b', 'rgb' => '245,158,11', 'title' => 'Session Expired',    'msg' => 'You were automatically logged out due to inactivity.',          'redirect' => true],
        'kicked'          => ['icon' => 'bi-shield-x',         'color' => '#f97316', 'rgb' => '249,115,22', 'title' => 'Session Terminated',  'msg' => 'Your account was signed in from another device or location.',   'redirect' => true],
        'disabled'        => ['icon' => 'bi-person-fill-slash', 'color' => '#ef4444', 'rgb' => '239,68,68',  'title' => 'Account Disabled',   'msg' => 'Your account has been disabled. Please contact your admin.',    'redirect' => false],
        'unauthenticated' => ['icon' => 'bi-lock-fill',        'color' => '#818cf8', 'rgb' => '129,140,248','title' => 'Session Expired',     'msg' => 'Your session has expired. Please log in again to continue.',   'redirect' => true],
    ];

    $g      = array_key_exists($guard ?? '', $portals) ? $guard : 'web';
    $r      = array_key_exists($reason ?? '', $reasons) ? $reason : 'unauthenticated';
    $portal = $portals[$g];
    $cfg    = $reasons[$r];

    $loginUrl   = route($portal['route']);
    $portalName = $portal['name'];
    $homeUrl    = url('/');
    $countdownSec = 10;
    $circumference = 263.9; // 2π × 42
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $cfg['title'] }} — Gaurangi Technologies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --accent:  {{ $cfg['color'] }};
            --accent-rgb: {{ $cfg['rgb'] }};
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #06090f;
            overflow: hidden;
        }

        /* ── Background ── */
        .bg-scene {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 700px 500px at 10% 15%, rgba(var(--accent-rgb),0.07) 0%, transparent 60%),
                radial-gradient(ellipse 600px 400px at 88% 85%, rgba(79,70,229,0.06) 0%, transparent 60%),
                radial-gradient(ellipse 400px 300px at 55% 105%, rgba(16,185,129,0.03) 0%, transparent 60%),
                linear-gradient(160deg, #0c0810 0%, #06090f 55%, #080d1a 100%);
        }

        .bg-grid {
            position: fixed;
            inset: 0;
            opacity: 0.02;
            background-image:
                linear-gradient(rgba(255,255,255,0.8) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.8) 1px, transparent 1px);
            background-size: 44px 44px;
        }

        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            pointer-events: none;
            animation: floatOrb ease-in-out infinite;
        }
        .orb-a { width: 500px; height: 500px; background: rgba(var(--accent-rgb),0.055); top: -150px; left: -120px; animation-duration: 10s; }
        .orb-b { width: 400px; height: 400px; background: rgba(79,70,229,0.045); bottom: -130px; right: -100px; animation-duration: 13s; animation-direction: reverse; }
        .orb-c { width: 260px; height: 260px; background: rgba(var(--accent-rgb),0.03); bottom: 20%; left: 5%; animation-duration: 8s; animation-delay: -4s; }

        @keyframes floatOrb {
            0%, 100% { transform: translate(0,0) scale(1); }
            33%       { transform: translate(14px,-20px) scale(1.05); }
            66%       { transform: translate(-10px,12px) scale(0.96); }
        }

        /* ── Particles ── */
        .particle {
            position: fixed;
            width: 2px;
            height: 2px;
            border-radius: 50%;
            background: var(--accent);
            opacity: 0;
            animation: floatParticle linear infinite;
        }

        @keyframes floatParticle {
            0%   { transform: translateY(100vh) scale(0); opacity: 0; }
            10%  { opacity: 0.4; }
            90%  { opacity: 0.2; }
            100% { transform: translateY(-10vh) scale(1.5); opacity: 0; }
        }

        /* ── Card ── */
        .wrap {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 460px;
            padding: 20px;
            animation: enterCard 0.6s cubic-bezier(0.34, 1.4, 0.64, 1) forwards;
            opacity: 0;
        }

        @keyframes enterCard {
            from { opacity: 0; transform: translateY(32px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0)    scale(1); }
        }

        .glass-card {
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(32px);
            -webkit-backdrop-filter: blur(32px);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 28px;
            padding: 48px 44px 44px;
            text-align: center;
            box-shadow:
                0 0 0 1px rgba(var(--accent-rgb),0.07),
                0 40px 100px rgba(0,0,0,0.65),
                inset 0 1px 0 rgba(255,255,255,0.05);
        }

        /* ── Portal badge ── */
        .portal-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(var(--accent-rgb),0.1);
            border: 1px solid rgba(var(--accent-rgb),0.2);
            color: var(--accent);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 2px;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 28px;
            text-transform: uppercase;
        }

        /* ── SVG Countdown Ring + Icon ── */
        .ring-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 28px;
        }

        .ring-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin-bottom: 20px;
        }

        .ring-svg {
            transform: rotate(-90deg);
            width: 120px;
            height: 120px;
        }

        .ring-track {
            fill: none;
            stroke: rgba(255,255,255,0.05);
            stroke-width: 3;
        }

        .ring-fill {
            fill: none;
            stroke: var(--accent);
            stroke-width: 3;
            stroke-linecap: round;
            stroke-dasharray: {{ $circumference }};
            stroke-dashoffset: 0;
            filter: drop-shadow(0 0 6px rgba(var(--accent-rgb),0.6));
            transition: stroke-dashoffset 0.95s linear;
        }

        .ring-inner {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
        }

        .ring-icon {
            font-size: 30px;
            color: var(--accent);
            filter: drop-shadow(0 0 8px rgba(var(--accent-rgb),0.5));
            animation: iconBounce 2.5s ease-in-out infinite;
        }

        @keyframes iconBounce {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-3px); }
        }

        .ring-num {
            font-size: 13px;
            font-weight: 700;
            color: var(--accent);
            line-height: 1;
            opacity: 0.85;
        }

        /* ── Title & message ── */
        h1 {
            font-size: 26px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 10px;
            letter-spacing: -0.4px;
        }

        .msg-text {
            font-size: 14px;
            color: rgba(255,255,255,0.4);
            line-height: 1.7;
            margin-bottom: 28px;
        }

        /* ── Redirect info ── */
        .redirect-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 12px;
            color: rgba(255,255,255,0.28);
            margin-bottom: 10px;
        }

        .redirect-row .rd-num {
            font-weight: 700;
            color: var(--accent);
            opacity: 0.8;
        }

        /* ── Progress bar ── */
        .prog-wrap {
            width: 100%;
            height: 2px;
            background: rgba(255,255,255,0.06);
            border-radius: 2px;
            margin-bottom: 28px;
            overflow: hidden;
        }

        .prog-bar {
            height: 100%;
            width: 100%;
            border-radius: 2px;
            background: linear-gradient(90deg, rgba(var(--accent-rgb),0.5), var(--accent));
            box-shadow: 0 0 8px rgba(var(--accent-rgb),0.4);
            transition: width 0.95s linear;
        }

        /* ── Divider ── */
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(var(--accent-rgb),0.12), transparent);
            margin-bottom: 24px;
        }

        /* ── Buttons ── */
        .btn-row {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.22s ease;
            line-height: 1;
            letter-spacing: 0.01em;
        }

        .btn-login {
            background: linear-gradient(135deg, rgba(var(--accent-rgb),0.22), rgba(var(--accent-rgb),0.12));
            border: 1px solid rgba(var(--accent-rgb),0.35);
            color: var(--accent);
            box-shadow: 0 4px 16px rgba(var(--accent-rgb),0.1);
        }
        .btn-login:hover {
            background: linear-gradient(135deg, rgba(var(--accent-rgb),0.35), rgba(var(--accent-rgb),0.2));
            color: var(--accent);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(var(--accent-rgb),0.2);
            text-decoration: none;
        }

        .btn-ghost {
            background: rgba(255,255,255,0.045);
            border: 1px solid rgba(255,255,255,0.085);
            color: rgba(255,255,255,0.38);
        }
        .btn-ghost:hover {
            background: rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.65);
            transform: translateY(-1px);
            text-decoration: none;
        }

        /* ── Footer ── */
        .footer {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .footer img {
            height: 14px;
            opacity: 0.2;
            filter: grayscale(1);
        }

        .footer span {
            font-size: 11px;
            color: rgba(255,255,255,0.16);
            letter-spacing: 0.4px;
        }
    </style>
</head>
<body>
    <div class="bg-scene"></div>
    <div class="bg-grid"></div>
    <div class="orb orb-a"></div>
    <div class="orb orb-b"></div>
    <div class="orb orb-c"></div>

    {{-- Floating particles --}}
    @for($i = 0; $i < 8; $i++)
        <div class="particle" style="left:{{ 5 + $i * 12 }}%; animation-duration: {{ 8 + $i * 1.5 }}s; animation-delay: {{ -$i * 1.2 }}s; width: {{ $i % 3 == 0 ? 3 : 2 }}px; height: {{ $i % 3 == 0 ? 3 : 2 }}px; opacity: 0;"></div>
    @endfor

    <div class="wrap">
        <div class="glass-card">

            {{-- Portal badge --}}
            <div class="portal-badge">
                <i class="bi bi-building-fill" style="font-size:8px;"></i>
                {{ $portalName }}
            </div>

            {{-- SVG Countdown Ring --}}
            <div class="ring-section">
                <div class="ring-container">
                    <svg class="ring-svg" viewBox="0 0 120 120">
                        <circle class="ring-track" cx="60" cy="60" r="42"/>
                        @if($cfg['redirect'])
                            <circle class="ring-fill" id="countRing" cx="60" cy="60" r="42"/>
                        @else
                            <circle class="ring-track" cx="60" cy="60" r="42" style="stroke: rgba(var(--accent-rgb),0.15);"/>
                        @endif
                    </svg>
                    <div class="ring-inner">
                        <i class="bi {{ $cfg['icon'] }} ring-icon"></i>
                        @if($cfg['redirect'])
                            <span class="ring-num" id="countNum">{{ $countdownSec }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <h1>{{ $cfg['title'] }}</h1>
            <p class="msg-text">{{ $cfg['msg'] }}</p>

            @if($cfg['redirect'])
                <div class="redirect-row">
                    <i class="bi bi-arrow-right-circle" style="font-size:12px;"></i>
                    <span>Auto-redirecting to login in <span class="rd-num" id="rdNum">{{ $countdownSec }}</span> seconds</span>
                </div>
                <div class="prog-wrap">
                    <div class="prog-bar" id="progBar"></div>
                </div>
            @else
                <div style="height:28px;"></div>
            @endif

            <div class="divider"></div>

            <div class="btn-row">
                @if(!($r === 'disabled'))
                    <a href="{{ $loginUrl }}" class="btn btn-login" id="loginBtn">
                        <i class="bi bi-box-arrow-in-right"></i> Login Again
                    </a>
                @endif
                <a href="{{ $homeUrl }}" class="btn btn-ghost">
                    <i class="bi bi-house-door"></i> Go Home
                </a>
            </div>

            <div class="footer">
                <img src="{{ asset('images/logog.png') }}" alt="Gaurangi" onerror="this.style.display='none'">
                <span>Gaurangi Technologies</span>
            </div>

        </div>
    </div>

@if($cfg['redirect'])
<script>
(function() {
    const TOTAL = {{ $countdownSec }};
    const LOGIN = @json($loginUrl);
    const CIRC  = {{ $circumference }};

    const ring   = document.getElementById('countRing');
    const numEl  = document.getElementById('countNum');
    const rdNum  = document.getElementById('rdNum');
    const bar    = document.getElementById('progBar');

    let left = TOTAL;

    function update() {
        left--;
        if (left <= 0) {
            window.location.href = LOGIN;
            return;
        }
        const ratio = left / TOTAL;
        if (ring) ring.style.strokeDashoffset = CIRC * (1 - ratio);
        if (numEl) numEl.textContent = left;
        if (rdNum) rdNum.textContent = left;
        if (bar)   bar.style.width = (ratio * 100) + '%';
    }

    setInterval(update, 1000);
})();
</script>
@endif
</body>
</html>
