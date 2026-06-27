<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Staff — Verify OTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --lib-color:#0ea5e9; }
        body {
            background:linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #f8fafc 100%);
            min-height:100vh; display:flex; align-items:center; justify-content:center;
            font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
            padding:20px;
        }
        .card { width:100%; max-width:420px; border:none; border-radius:20px; box-shadow:0 8px 40px rgba(14,165,233,.15); overflow:hidden; }
        .top-bar { height:4px; background:linear-gradient(90deg,var(--lib-color),#38bdf8); }
        .brand-icon {
            width:56px; height:56px; border-radius:16px;
            background:linear-gradient(135deg,var(--lib-color),#38bdf8);
            display:flex; align-items:center; justify-content:center;
            font-size:24px; color:#fff; margin:0 auto 14px;
            box-shadow:0 4px 16px rgba(14,165,233,.35);
        }
        /* OTP digit boxes */
        .otp-group { display:flex; gap:10px; justify-content:center; margin:20px 0; }
        .otp-digit {
            width:52px; height:60px; text-align:center; font-size:22px; font-weight:700;
            border:2px solid #e2e8f0; border-radius:12px; outline:none;
            transition:border-color .15s, box-shadow .15s; color:#0c4a6e;
            caret-color:transparent;
        }
        .otp-digit:focus { border-color:var(--lib-color); box-shadow:0 0 0 3px rgba(14,165,233,.18); }
        .otp-digit.filled { border-color:var(--lib-color); background:#f0f9ff; }
        .otp-digit.error-shake {
            border-color:#dc2626; box-shadow:0 0 0 3px rgba(220,38,38,.12);
            animation:shake .35s ease;
        }
        @keyframes shake {
            0%,100%{transform:translateX(0)}20%{transform:translateX(-6px)}
            40%{transform:translateX(6px)}60%{transform:translateX(-4px)}80%{transform:translateX(4px)}
        }
        .btn-verify {
            height:48px; border-radius:10px; font-size:15px; font-weight:600;
            background:linear-gradient(135deg,var(--lib-color),#38bdf8); border:none;
            box-shadow:0 4px 14px rgba(14,165,233,.3); transition:all .2s ease;
        }
        .btn-verify:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(14,165,233,.45); }
        .resend-btn { background:none; border:none; color:var(--lib-color); font-size:13px; font-weight:600; cursor:pointer; padding:0; }
        .resend-btn:disabled { color:#94a3b8; cursor:not-allowed; }
        .email-mask { font-weight:600; color:#0c4a6e; }

        /* Toast system */
        .toast-wrap { position:fixed; top:20px; right:20px; z-index:9999; display:flex; flex-direction:column; gap:10px; min-width:300px; }
        .notif { display:flex; align-items:flex-start; gap:10px; padding:13px 16px; border-radius:12px; border:1px solid; box-shadow:0 4px 20px rgba(0,0,0,.1); animation:slideIn .3s ease; position:relative; overflow:hidden; }
        @keyframes slideIn { from{transform:translateX(60px);opacity:0} to{transform:translateX(0);opacity:1} }
        .notif.error   { background:#fef2f2; border-color:#fecaca; color:#991b1b; }
        .notif.success { background:#f0fdf4; border-color:#bbf7d0; color:#166534; }
        .notif.warning { background:#fffbeb; border-color:#fde68a; color:#92400e; }
        .notif .notif-icon { font-size:16px; flex-shrink:0; margin-top:1px; }
        .notif .notif-body { flex:1; font-size:13px; font-weight:500; line-height:1.5; }
        .notif .notif-close { background:none; border:none; opacity:.5; cursor:pointer; font-size:14px; padding:0; line-height:1; flex-shrink:0; }
        .notif-progress { position:absolute; bottom:0; left:0; height:3px; background:currentColor; opacity:.25; animation:progress 5s linear forwards; }
        @keyframes progress { from{width:100%} to{width:0} }
    </style>
</head>
<body>

<div class="toast-wrap" id="toastWrap"></div>

<div class="card">
    <div class="top-bar"></div>
    <div class="p-4 p-md-5">

        <div class="text-center mb-4">
            <div class="brand-icon"><i class="bi bi-shield-lock"></i></div>
            <h5 class="fw-bold mb-1" style="color:#0c4a6e;">Verify OTP</h5>
            <p class="text-muted mb-0" style="font-size:13px;">
                We sent a 6-digit OTP to
                <span class="email-mask">{{ substr($staff->email, 0, 3) . '***@' . explode('@', $staff->email)[1] }}</span>
            </p>
        </div>

        @if($errors->has('otp'))
        <div class="alert border-0 rounded-3 py-2 mb-3"
             style="background:#fef2f2;border:1px solid #fecaca !important;color:#991b1b;font-size:13px;">
            <i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first('otp') }}
        </div>
        @endif

        <form method="POST" action="{{ route('library_staff.otp.verify') }}" id="otpForm" novalidate>
            @csrf

            {{-- Hidden combined OTP field --}}
            <input type="hidden" name="otp" id="otpHidden">

            {{-- 6 visual digit boxes --}}
            <div class="otp-group" id="otpGroup">
                @for($i = 0; $i < 6; $i++)
                <input type="text" class="otp-digit @error('otp') error-shake @enderror"
                       maxlength="1" inputmode="numeric" pattern="[0-9]"
                       data-index="{{ $i }}" autocomplete="off">
                @endfor
            </div>

            <button type="submit" class="btn btn-verify text-white w-100 mb-3" id="verifyBtn">
                <i class="bi bi-unlock me-2"></i>Verify & Login
            </button>
        </form>

        <div class="text-center">
            <span class="text-muted" style="font-size:13px;">Didn't receive the OTP?</span>
            <form method="POST" action="{{ route('library_staff.otp.resend') }}" class="d-inline ms-2" id="resendForm">
                @csrf
                <button type="submit" class="resend-btn" id="resendBtn" disabled>
                    Resend OTP <span id="resendTimer">(60s)</span>
                </button>
            </form>
        </div>

        <div class="text-center mt-3">
            <a href="{{ route('library_staff.login') }}" class="text-muted text-decoration-none" style="font-size:12px;">
                <i class="bi bi-arrow-left me-1"></i>Back to Login
            </a>
        </div>

    </div>
</div>

<script>
// ── OTP digit box logic ───────────────────────────────────────────
const digits  = Array.from(document.querySelectorAll('.otp-digit'));
const hidden  = document.getElementById('otpHidden');
const form    = document.getElementById('otpForm');
const verifyBtn = document.getElementById('verifyBtn');

digits.forEach(function(input, idx) {
    input.addEventListener('input', function(e) {
        const val = e.target.value.replace(/\D/g, '');
        e.target.value = val.slice(-1);
        e.target.classList.toggle('filled', val.length > 0);
        if (val && idx < 5) digits[idx + 1].focus();
        syncHidden();
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && !e.target.value && idx > 0) {
            digits[idx - 1].value = '';
            digits[idx - 1].classList.remove('filled');
            digits[idx - 1].focus();
            syncHidden();
        }
    });

    input.addEventListener('paste', function(e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
        paste.slice(0, 6).split('').forEach(function(ch, i) {
            if (digits[i]) { digits[i].value = ch; digits[i].classList.add('filled'); }
        });
        const next = Math.min(paste.length, 5);
        digits[next].focus();
        syncHidden();
    });
});

function syncHidden() {
    hidden.value = digits.map(d => d.value).join('');
}

// Focus first digit on load
digits[0].focus();

// Auto-submit when all 6 filled
function checkAutoSubmit() {
    if (digits.every(d => d.value.match(/\d/))) {
        syncHidden();
        setTimeout(function() { form.requestSubmit(); }, 200);
    }
}
digits.forEach(d => d.addEventListener('input', checkAutoSubmit));

form.addEventListener('submit', function() {
    syncHidden();
    if (hidden.value.length < 6) return;
    verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifying...';
    verifyBtn.disabled = true;
});

// ── Resend countdown ─────────────────────────────────────────────
let seconds = 60;
const timerEl = document.getElementById('resendTimer');
const resendBtn = document.getElementById('resendBtn');

const interval = setInterval(function() {
    seconds--;
    timerEl.textContent = '(' + seconds + 's)';
    if (seconds <= 0) {
        clearInterval(interval);
        resendBtn.disabled = false;
        timerEl.textContent = '';
    }
}, 1000);

// ── Toast notifications ──────────────────────────────────────────
function showToast(type, message) {
    const wrap = document.getElementById('toastWrap');
    const el = document.createElement('div');
    const icons = { error:'exclamation-circle-fill', success:'check-circle-fill', warning:'exclamation-triangle-fill' };
    el.className = 'notif ' + type;
    el.innerHTML = `
        <span class="notif-icon"><i class="bi bi-${icons[type] || 'info-circle'}"></i></span>
        <span class="notif-body">${message}</span>
        <button class="notif-close" onclick="this.closest('.notif').remove()"><i class="bi bi-x"></i></button>
        <div class="notif-progress"></div>
    `;
    wrap.appendChild(el);
    setTimeout(() => el.style.opacity = '0', 4800);
    setTimeout(() => el.remove(), 5100);
}

(function() {
    @if(session('success'))
        showToast('success', '{{ addslashes(session('success')) }}');
    @endif
    @if(session('error'))
        showToast('error', '{{ addslashes(session('error')) }}');
    @endif
})();
</script>
</body>
</html>
