<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Institute Login — Gaurangi Technologies</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ time() }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v={{ time() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(ellipse at 60% 20%, #2d1b6b 0%, #1a0f3c 40%, #0d0820 100%);
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        /* ── Toast ── */
        #toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }

        .toast-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 18px;
            border-radius: 12px;
            min-width: 300px;
            max-width: 380px;
            font-size: 13.5px;
            font-weight: 500;
            line-height: 1.5;
            pointer-events: all;
            box-shadow: 0 8px 30px rgba(0,0,0,0.4);
            animation: slideIn 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            position: relative;
            overflow: hidden;
        }

        .toast-item::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0;
            height: 3px;
            width: 100%;
            animation: progress linear forwards;
        }

        .toast-item.toast-error {
            background: rgba(30, 8, 8, 0.92);
            border: 1px solid rgba(239, 68, 68, 0.5);
            color: #fca5a5;
        }
        .toast-item.toast-error::after { background: #ef4444; animation-duration: 5s; }
        .toast-item.toast-error .toast-icon { color: #f87171; font-size: 18px; margin-top: 1px; }

        .toast-item.toast-success {
            background: rgba(5, 24, 16, 0.92);
            border: 1px solid rgba(34, 197, 94, 0.5);
            color: #86efac;
        }
        .toast-item.toast-success::after { background: #22c55e; animation-duration: 5s; }
        .toast-item.toast-success .toast-icon { color: #4ade80; font-size: 18px; margin-top: 1px; }

        .toast-item.toast-info {
            background: rgba(5, 15, 30, 0.92);
            border: 1px solid rgba(99, 179, 237, 0.5);
            color: #93c5fd;
        }
        .toast-item.toast-info::after { background: #3b82f6; animation-duration: 5s; }
        .toast-item.toast-info .toast-icon { color: #60a5fa; font-size: 18px; margin-top: 1px; }

        .toast-close {
            margin-left: auto;
            background: none;
            border: none;
            color: inherit;
            opacity: 0.6;
            cursor: pointer;
            padding: 0;
            font-size: 16px;
            line-height: 1;
            flex-shrink: 0;
        }
        .toast-close:hover { opacity: 1; }

        .toast-item.toast-hide {
            animation: slideOut 0.3s ease-in forwards;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(60px) scale(0.9); }
            to   { opacity: 1; transform: translateX(0)   scale(1);   }
        }
        @keyframes slideOut {
            from { opacity: 1; transform: translateX(0)   scale(1);   }
            to   { opacity: 0; transform: translateX(60px) scale(0.9); }
        }
        @keyframes progress {
            from { width: 100%; }
            to   { width: 0%; }
        }

        /* ── Card ── */
        .login-card {
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            padding: 36px 40px 32px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.1);
        }

        .brand-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 22px;
        }

        .divider {
            border: none;
            border-top: 1px solid rgba(255,255,255,0.12);
            margin: 0 0 22px;
        }

        .portal-label {
            text-align: center;
            font-size: 12px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: rgba(196,181,253,0.6);
            margin-bottom: 6px;
        }

        .portal-title {
            text-align: center;
            font-size: 26px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
        }

        .portal-subtitle {
            text-align: center;
            font-size: 13px;
            color: rgba(255,255,255,0.45);
            margin-bottom: 22px;
            line-height: 1.6;
        }

        .form-floating { margin-bottom: 14px; }

        .form-floating .form-control {
            background: rgba(255,255,255,0.92);
            border: 1.5px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            color: #1a0f3c;
            font-size: 14px;
            height: 52px;
            padding: 14px 44px 0 16px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-floating .form-control:focus {
            background: #ffffff;
            border-color: #a78bfa;
            box-shadow: 0 0 0 3px rgba(167,139,250,0.25);
            outline: none;
        }

        .form-floating label { color: #6b7280; font-size: 13px; padding: 14px 16px; }

        .input-wrapper { position: relative; }

        .input-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 16px;
            pointer-events: none;
            z-index: 5;
        }

        .btn-signin {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #7c3aed, #6d28d9, #5b21b6);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.25s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
            box-shadow: 0 4px 20px rgba(109,40,217,0.45);
        }

        .btn-signin:hover {
            background: linear-gradient(135deg, #6d28d9, #5b21b6, #4c1d95);
            box-shadow: 0 6px 28px rgba(109,40,217,0.6);
            transform: translateY(-1px);
        }

        .btn-signin:active { transform: translateY(0); }

        .login-footer { text-align: center; margin-top: 22px; }

        .login-footer a {
            color: #a78bfa;
            font-size: 13px;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .login-footer a:hover { color: #c4b5fd; text-decoration: underline; }

        .powered-by {
            text-align: center;
            margin-top: 28px;
            font-size: 12px;
            color: rgba(255,255,255,0.25);
        }

        .powered-by span { color: rgba(167,139,250,0.6); font-weight: 500; }

        @media (max-width: 480px) {
            .login-card { padding: 36px 24px 28px; margin: 16px; }
            .brand-name .name { font-size: 22px; }
            #toast-container { right: 12px; left: 12px; }
            .toast-item { min-width: unset; max-width: 100%; }
        }
    </style>
</head>
<body>

{{-- Toast Container --}}
<div id="toast-container"></div>

<div class="login-card">

    <div class="brand-logo">
        <img src="{{ asset('images/logog.png') }}" alt="Gaurangi Technologies" style="height: 64px; width: auto; object-fit: contain;">
    </div>

    <hr class="divider">

    <p class="portal-label">Secure access portal</p>
    <h1 class="portal-title">Institute Sign In</h1>
    <p class="portal-subtitle">Enter your Institute ID, email and password to access the management portal.</p>

    <form method="POST" action="{{ route('login.submit') }}">
        @csrf

        <div class="input-wrapper">
            <div class="form-floating">
                <input type="text" name="institute_id" id="institute_id"
                       class="form-control" placeholder="Institute ID"
                       value="{{ old('institute_id') }}" required autocomplete="off">
                <label for="institute_id">Institute ID</label>
            </div>
            <i class="bi bi-building input-icon"></i>
        </div>

        <div class="input-wrapper">
            <div class="form-floating">
                <input type="email" name="email" id="email"
                       class="form-control" placeholder="Email address"
                       value="{{ old('email') }}" required autocomplete="email">
                <label for="email">Email Address</label>
            </div>
            <i class="bi bi-envelope input-icon"></i>
        </div>

        <div class="input-wrapper">
            <div class="form-floating">
                <input type="password" name="password" id="password"
                       class="form-control" placeholder="Password" required>
                <label for="password">Password</label>
            </div>
            <i class="bi bi-lock input-icon"></i>
        </div>

        <button type="submit" class="btn-signin">
            <i class="bi bi-box-arrow-in-right"></i>
            Sign In
        </button>
    </form>

    @if(\Illuminate\Support\Facades\Route::has('password.request'))
    <div class="login-footer">
        <a href="{{ route('password.request') }}">Forgot password?</a>
    </div>
    @endif

    <p class="powered-by">Powered by <span>Gaurangi Technologies</span></p>
</div>

{{-- PHP data → JS --}}
@php
    $toasts = [];
    if ($errors->any()) {
        foreach ($errors->all() as $msg) {
            $toasts[] = ['type' => 'error', 'msg' => $msg];
        }
    }
    if (session('success')) $toasts[] = ['type' => 'success', 'msg' => session('success')];
    if (session('error'))   $toasts[] = ['type' => 'error',   'msg' => session('error')];
    if (session('info'))    $toasts[] = ['type' => 'info',     'msg' => session('info')];
@endphp

<script>
(function () {
    const toasts = @json($toasts);
    const icons = { error: 'bi-x-circle-fill', success: 'bi-check-circle-fill', info: 'bi-info-circle-fill' };

    function showToast({ type, msg }) {
        const container = document.getElementById('toast-container');
        const el = document.createElement('div');
        el.className = `toast-item toast-${type}`;
        el.innerHTML = `
            <i class="bi ${icons[type] || icons.info} toast-icon"></i>
            <span>${msg}</span>
            <button class="toast-close" aria-label="Close"><i class="bi bi-x"></i></button>
        `;
        el.querySelector('.toast-close').addEventListener('click', () => dismiss(el));
        container.appendChild(el);
        setTimeout(() => dismiss(el), 5200);
    }

    function dismiss(el) {
        if (el.classList.contains('toast-hide')) return;
        el.classList.add('toast-hide');
        el.addEventListener('animationend', () => el.remove(), { once: true });
    }

    toasts.forEach((t, i) => setTimeout(() => showToast(t), i * 150));
})();
</script>
</body>
</html>
