<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification — Gaurangi Technologies</title>
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

        .toast-item.toast-error  { background: rgba(30,8,8,0.92);   border: 1px solid rgba(239,68,68,0.5);  color: #fca5a5; }
        .toast-item.toast-error::after  { background: #ef4444; animation-duration: 5s; }
        .toast-item.toast-error  .toast-icon { color: #f87171; font-size: 18px; margin-top: 1px; }

        .toast-item.toast-success { background: rgba(5,24,16,0.92);  border: 1px solid rgba(34,197,94,0.5);  color: #86efac; }
        .toast-item.toast-success::after { background: #22c55e; animation-duration: 5s; }
        .toast-item.toast-success .toast-icon { color: #4ade80; font-size: 18px; margin-top: 1px; }

        .toast-item.toast-info   { background: rgba(5,15,30,0.92);   border: 1px solid rgba(99,179,237,0.5); color: #93c5fd; }
        .toast-item.toast-info::after   { background: #3b82f6; animation-duration: 5s; }
        .toast-item.toast-info   .toast-icon { color: #60a5fa; font-size: 18px; margin-top: 1px; }

        .toast-close {
            margin-left: auto;
            background: none; border: none;
            color: inherit; opacity: 0.6;
            cursor: pointer; padding: 0;
            font-size: 16px; line-height: 1;
            flex-shrink: 0;
        }
        .toast-close:hover { opacity: 1; }

        .toast-item.toast-hide { animation: slideOut 0.3s ease-in forwards; }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(60px) scale(0.9); }
            to   { opacity: 1; transform: translateX(0)    scale(1);   }
        }
        @keyframes slideOut {
            from { opacity: 1; transform: translateX(0)    scale(1);   }
            to   { opacity: 0; transform: translateX(60px) scale(0.9); }
        }
        @keyframes progress { from { width: 100%; } to { width: 0%; } }

        /* ── Card ── */
        .otp-card {
            background: rgba(255,255,255,0.07);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 20px;
            padding: 30px 36px 28px;
            width: calc(100% - 32px);
            max-width: 400px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.1);
            text-align: center;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
        }

        .divider { border: none; border-top: 1px solid rgba(255,255,255,0.12); margin: 0 0 18px; }

        .otp-icon-wrap {
            width: 54px; height: 54px;
            background: linear-gradient(135deg, rgba(124,58,237,0.3), rgba(109,40,217,0.15));
            border: 1px solid rgba(167,139,250,0.35);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
        }

        .otp-icon-wrap i { font-size: 24px; color: #a78bfa; }

        .portal-label {
            font-size: 11px; letter-spacing: 2px;
            text-transform: uppercase; color: rgba(196,181,253,0.6); margin-bottom: 6px;
        }

        .portal-title { font-size: 22px; font-weight: 700; color: #ffffff; margin-bottom: 6px; }

        .portal-subtitle {
            font-size: 12.5px; color: rgba(255,255,255,0.45);
            margin-bottom: 20px; line-height: 1.6;
        }

        /* ── OTP boxes ── */
        .otp-boxes {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 18px;
        }

        .otp-box {
            width: 46px; height: 52px;
            background: rgba(255,255,255,0.92);
            border: 1.5px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            color: #1a0f3c;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            caret-color: #7c3aed;
        }

        .otp-box:focus {
            border-color: #a78bfa;
            box-shadow: 0 0 0 3px rgba(167,139,250,0.25);
            background: #ffffff;
        }

        .otp-box.filled { border-color: #7c3aed; }

        /* hidden real input */
        #otp-hidden { display: none; }

        .btn-verify {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #7c3aed, #6d28d9, #5b21b6);
            color: #fff; border: none; border-radius: 10px;
            font-size: 15px; font-weight: 600; letter-spacing: 0.5px;
            cursor: pointer; transition: all 0.25s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 4px 20px rgba(109,40,217,0.45);
        }

        .btn-verify:hover {
            background: linear-gradient(135deg, #6d28d9, #5b21b6, #4c1d95);
            box-shadow: 0 6px 28px rgba(109,40,217,0.6);
            transform: translateY(-1px);
        }

        .btn-verify:active { transform: translateY(0); }

        .btn-verify:disabled {
            opacity: 0.55; cursor: not-allowed; transform: none;
            box-shadow: none;
        }

        /* ── Resend row ── */
        .resend-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 14px;
            font-size: 13px;
        }

        .resend-row .label { color: rgba(255,255,255,0.4); }

        .btn-resend {
            background: none; border: none; padding: 0;
            color: #a78bfa; font-weight: 600; font-size: 13px;
            cursor: pointer; text-decoration: none;
            transition: color 0.2s;
        }

        .btn-resend:hover:not(:disabled) { color: #c4b5fd; text-decoration: underline; }

        .btn-resend:disabled { color: rgba(167,139,250,0.4); cursor: not-allowed; }

        .timer-badge {
            background: rgba(124,58,237,0.25);
            border: 1px solid rgba(167,139,250,0.3);
            color: #c4b5fd;
            border-radius: 20px;
            padding: 2px 10px;
            font-size: 12px;
            font-weight: 600;
            min-width: 48px;
            transition: opacity 0.3s;
        }

        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            color: rgba(167,139,250,0.6); font-size: 13px;
            text-decoration: none; margin-top: 16px;
            transition: color 0.2s;
        }
        .back-link:hover { color: #a78bfa; }

        .powered-by {
            margin-top: 18px; font-size: 12px; color: rgba(255,255,255,0.25);
        }
        .powered-by span { color: rgba(167,139,250,0.6); font-weight: 500; }

        @media (max-width: 480px) {
            .otp-card { padding: 36px 20px 28px; margin: 16px; }
            .brand-name .name { font-size: 22px; }
            .otp-box { width: 44px; height: 52px; font-size: 20px; }
            #toast-container { right: 12px; left: 12px; }
            .toast-item { min-width: unset; max-width: 100%; }
        }
    </style>
</head>
<body>

<div id="toast-container"></div>

<div class="otp-card">

    <div class="brand-logo">
        <img src="{{ asset('images/logog.png') }}" alt="Gaurangi Technologies" style="height: 64px; width: auto; object-fit: contain;">
    </div>

    <hr class="divider">

    <div class="otp-icon-wrap">
        <i class="bi bi-shield-lock-fill"></i>
    </div>

    <p class="portal-label">Two-step verification</p>
    <h1 class="portal-title">Enter OTP</h1>
    <p class="portal-subtitle">
        A one-time password has been sent to your registered email address.
        @if(session('otp_sms_sent'))
            <br><span style="color:#a78bfa;font-size:12px;"><i class="bi bi-phone me-1"></i>Also sent via SMS to your registered mobile.</span>
        @endif
    </p>

    <form method="POST" action="{{ route('otp.verify') }}" id="otp-form">
        @csrf
        <input type="hidden" name="otp" id="otp-hidden">

        <div class="otp-boxes">
            <input class="otp-box" type="text" inputmode="numeric" maxlength="1" data-index="0">
            <input class="otp-box" type="text" inputmode="numeric" maxlength="1" data-index="1">
            <input class="otp-box" type="text" inputmode="numeric" maxlength="1" data-index="2">
            <input class="otp-box" type="text" inputmode="numeric" maxlength="1" data-index="3">
            <input class="otp-box" type="text" inputmode="numeric" maxlength="1" data-index="4">
            <input class="otp-box" type="text" inputmode="numeric" maxlength="1" data-index="5">
        </div>

        <button type="submit" class="btn-verify" id="verify-btn" disabled>
            <i class="bi bi-shield-check"></i>
            Verify OTP
        </button>
    </form>

    <div class="resend-row">
        <span class="label">Didn't receive it?</span>
        <form method="POST" action="{{ route('otp.resend') }}" style="display:inline" id="resend-form">
            @csrf
            <button type="submit" class="btn-resend" id="resendBtn" disabled>Resend</button>
        </form>
        <span class="timer-badge" id="timerBadge">0:{{ $cooldownSeconds }}</span>
    </div>

    <a href="{{ route('login') }}" class="back-link">
        <i class="bi bi-arrow-left"></i> Back to Sign In
    </a>

    <p class="powered-by">Powered by <span>Gaurangi Technologies</span></p>
</div>

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
    /* ── Toast system ── */
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

    /* ── OTP boxes ── */
    const boxes      = Array.from(document.querySelectorAll('.otp-box'));
    const hiddenOtp  = document.getElementById('otp-hidden');
    const verifyBtn  = document.getElementById('verify-btn');

    function syncHidden() {
        const val = boxes.map(b => b.value).join('');
        hiddenOtp.value = val;
        verifyBtn.disabled = val.length < 6;
        boxes.forEach(b => b.classList.toggle('filled', b.value !== ''));
        if (val.length === 6) {
            boxes.forEach(b => b.disabled = true);
            document.getElementById('otp-form').submit();
        }
    }

    boxes.forEach((box, idx) => {
        box.addEventListener('input', (e) => {
            const v = e.target.value.replace(/\D/g, '');
            box.value = v ? v[0] : '';
            syncHidden();
            if (v && idx < boxes.length - 1) boxes[idx + 1].focus();
        });

        box.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !box.value && idx > 0) {
                boxes[idx - 1].value = '';
                boxes[idx - 1].focus();
                syncHidden();
            }
        });

        box.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            pasted.split('').slice(0, 6).forEach((ch, i) => {
                if (boxes[i]) boxes[i].value = ch;
            });
            syncHidden();
            const next = Math.min(pasted.length, 5);
            boxes[next].focus();
        });
    });

    boxes[0].focus();

    /* ── Resend countdown ── */
    let timeLeft = {{ $cooldownSeconds ?? 30 }};
    const resendBtn  = document.getElementById('resendBtn');
    const timerBadge = document.getElementById('timerBadge');

    const countdown = setInterval(() => {
        timeLeft--;
        const m = Math.floor(timeLeft / 60);
        const s = timeLeft % 60;
        timerBadge.textContent = `${m}:${String(s).padStart(2, '0')}`;
        if (timeLeft <= 0) {
            clearInterval(countdown);
            resendBtn.disabled = false;
            timerBadge.style.opacity = '0';
        }
    }, 1000);
})();
</script>
</body>
</html>
