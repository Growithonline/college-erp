<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 — Page Expired | Gaurangi Technologies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
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
                radial-gradient(ellipse 700px 500px at 10% 20%, rgba(239,68,68,0.07) 0%, transparent 65%),
                radial-gradient(ellipse 600px 500px at 90% 80%, rgba(124,58,237,0.06) 0%, transparent 65%),
                radial-gradient(ellipse 400px 300px at 50% 110%, rgba(16,185,129,0.04) 0%, transparent 60%),
                linear-gradient(160deg, #0c0612 0%, #06090f 60%, #080d18 100%);
        }

        .bg-grid {
            position: fixed;
            inset: 0;
            opacity: 0.022;
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
        .orb-1 { width: 450px; height: 450px; background: rgba(239,68,68,0.055); top: -140px; left: -100px; animation-duration: 9s; }
        .orb-2 { width: 380px; height: 380px; background: rgba(124,58,237,0.045); bottom: -120px; right: -80px; animation-duration: 12s; animation-direction: reverse; }
        .orb-3 { width: 280px; height: 280px; background: rgba(239,68,68,0.03); top: 40%; right: 10%; animation-duration: 7s; animation-delay: -3s; }

        @keyframes floatOrb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33%       { transform: translate(12px, -18px) scale(1.04); }
            66%       { transform: translate(-8px, 10px) scale(0.97); }
        }

        /* ── Card ── */
        .wrap {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 460px;
            padding: 20px;
            animation: enterCard 0.55s cubic-bezier(0.34, 1.4, 0.64, 1) forwards;
            opacity: 0;
        }

        @keyframes enterCard {
            from { opacity: 0; transform: translateY(28px) scale(0.96); }
            to   { opacity: 1; transform: translateY(0)    scale(1); }
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.032);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.065);
            border-radius: 26px;
            padding: 52px 44px 44px;
            text-align: center;
            box-shadow:
                0 0 0 1px rgba(239,68,68,0.06),
                0 40px 100px rgba(0, 0, 0, 0.65),
                inset 0 1px 0 rgba(255, 255, 255, 0.055);
        }

        /* ── Icon ── */
        .icon-wrap {
            width: 104px;
            height: 104px;
            border-radius: 50%;
            background: radial-gradient(circle at 35% 35%, rgba(239,68,68,0.2), rgba(239,68,68,0.06));
            border: 1px solid rgba(239,68,68,0.22);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 32px;
            position: relative;
            animation: iconPulse 3s ease-in-out infinite;
        }

        .icon-wrap::before {
            content: '';
            position: absolute;
            inset: -10px;
            border-radius: 50%;
            border: 1px solid rgba(239,68,68,0.1);
            animation: ringExpand 2.5s ease-out infinite;
        }

        .icon-wrap::after {
            content: '';
            position: absolute;
            inset: -22px;
            border-radius: 50%;
            border: 1px solid rgba(239,68,68,0.05);
            animation: ringExpand 2.5s ease-out infinite 0.4s;
        }

        .icon-wrap i {
            font-size: 42px;
            color: #f87171;
            filter: drop-shadow(0 0 12px rgba(239,68,68,0.5));
        }

        @keyframes iconPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.15), 0 0 24px rgba(239,68,68,0.08); }
            50%       { box-shadow: 0 0 0 16px rgba(239,68,68,0), 0 0 40px rgba(239,68,68,0.15); }
        }

        @keyframes ringExpand {
            0%   { transform: scale(1); opacity: 0.6; }
            100% { transform: scale(1.3); opacity: 0; }
        }

        /* ── Badge ── */
        .err-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.22);
            color: #f87171;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 2.5px;
            padding: 5px 14px;
            border-radius: 20px;
            margin-bottom: 16px;
        }

        /* ── Typography ── */
        h1 {
            font-size: 27px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }

        .sub-text {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.42);
            line-height: 1.7;
            margin-bottom: 36px;
        }

        /* ── Divider ── */
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(239,68,68,0.15), transparent);
            margin-bottom: 28px;
        }

        /* ── Buttons ── */
        .btn-row {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 12px 26px;
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

        .btn-primary {
            background: linear-gradient(135deg, rgba(239,68,68,0.25), rgba(239,68,68,0.15));
            border: 1px solid rgba(239,68,68,0.35);
            color: #f87171;
            box-shadow: 0 4px 16px rgba(239,68,68,0.12);
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, rgba(239,68,68,0.35), rgba(239,68,68,0.22));
            color: #fca5a5;
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(239,68,68,0.22);
        }

        .btn-ghost {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.09);
            color: rgba(255,255,255,0.45);
        }
        .btn-ghost:hover {
            background: rgba(255,255,255,0.09);
            color: rgba(255,255,255,0.75);
            transform: translateY(-1px);
        }

        /* ── Footer ── */
        .footer {
            margin-top: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .footer img {
            height: 15px;
            opacity: 0.22;
            filter: grayscale(1);
        }

        .footer span {
            font-size: 11px;
            color: rgba(255,255,255,0.18);
            letter-spacing: 0.4px;
        }
    </style>
</head>
<body>
    <div class="bg-scene"></div>
    <div class="bg-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="wrap">
        <div class="glass-card">

            <div class="icon-wrap">
                <i class="bi bi-shield-exclamation"></i>
            </div>

            <div class="err-badge">
                <i class="bi bi-exclamation-triangle-fill" style="font-size:9px;"></i>
                ERROR 419
            </div>

            <h1>Page Expired</h1>
            <p class="sub-text">
                Your session token has expired.<br>
                Please go back and try submitting the form again.
            </p>

            <div class="divider"></div>

            <div class="btn-row">
                <button class="btn btn-primary" onclick="history.back()">
                    <i class="bi bi-arrow-left"></i> Go Back
                </button>
                <button class="btn btn-ghost" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh Page
                </button>
            </div>

            <div class="footer">
                <img src="{{ asset('images/logog.png') }}" alt="Gaurangi" onerror="this.style.display='none'">
                <span>Gaurangi Technologies</span>
            </div>

        </div>
    </div>
</body>
</html>
