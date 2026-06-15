<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Staff — Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --lib-color:#0ea5e9; --lib-dark:#0c4a6e; }
        * { box-sizing:border-box; }
        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #f8fafc 100%);
            min-height:100vh; display:flex; align-items:center; justify-content:center;
            font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
            padding:20px;
        }
        .card {
            width:100%; max-width:420px; border:none; border-radius:20px;
            box-shadow:0 8px 40px rgba(14,165,233,.15); overflow:hidden;
        }
        .top-bar { height:4px; background:linear-gradient(90deg,var(--lib-color),#38bdf8); }
        .brand-icon {
            width:56px; height:56px; border-radius:16px;
            background:linear-gradient(135deg,var(--lib-color),#38bdf8);
            display:flex; align-items:center; justify-content:center;
            font-size:24px; color:#fff; margin:0 auto 14px;
            box-shadow:0 4px 16px rgba(14,165,233,.35);
        }
        .form-label { font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
        .form-control {
            border-radius:10px; height:46px; border:1.5px solid #e2e8f0;
            font-size:14px; transition:border-color .18s, box-shadow .18s;
        }
        .form-control:focus { border-color:var(--lib-color); box-shadow:0 0 0 3px rgba(14,165,233,.18); }
        .form-control.is-invalid {
            border-color:#dc2626; box-shadow:0 0 0 3px rgba(220,38,38,.12);
            animation: shake .3s ease;
        }
        @keyframes shake {
            0%,100%{transform:translateX(0)}20%{transform:translateX(-5px)}
            40%{transform:translateX(5px)}60%{transform:translateX(-3px)}80%{transform:translateX(3px)}
        }
        .btn-submit {
            height:48px; border-radius:10px; font-size:15px; font-weight:600;
            background:linear-gradient(135deg,var(--lib-color),#38bdf8); border:none;
            letter-spacing:.02em; transition:all .2s ease;
            box-shadow:0 4px 14px rgba(14,165,233,.3);
        }
        .btn-submit:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(14,165,233,.45); }
        .btn-submit:active { transform:translateY(0); box-shadow:0 2px 8px rgba(14,165,233,.25); }

        /* ── Toast notification system ── */
        .toast-wrap {
            position:fixed; top:20px; right:20px; z-index:9999;
            display:flex; flex-direction:column; gap:10px; min-width:300px;
        }
        .notif {
            display:flex; align-items:flex-start; gap:10px;
            padding:13px 16px; border-radius:12px; border:1px solid;
            box-shadow:0 4px 20px rgba(0,0,0,.1); animation:slideIn .3s ease;
            position:relative; overflow:hidden;
        }
        @keyframes slideIn { from{transform:translateX(60px);opacity:0} to{transform:translateX(0);opacity:1} }
        .notif.error   { background:#fef2f2; border-color:#fecaca; color:#991b1b; }
        .notif.success { background:#f0fdf4; border-color:#bbf7d0; color:#166534; }
        .notif .notif-icon { font-size:16px; flex-shrink:0; margin-top:1px; }
        .notif .notif-body { flex:1; font-size:13px; font-weight:500; line-height:1.5; }
        .notif .notif-close { background:none; border:none; opacity:.5; cursor:pointer; font-size:14px; padding:0; line-height:1; flex-shrink:0; }
        .notif .notif-close:hover { opacity:1; }
        .notif-progress {
            position:absolute; bottom:0; left:0; height:3px;
            background:currentColor; opacity:.25;
            animation:progress 5s linear forwards;
        }
        @keyframes progress { from{width:100%} to{width:0} }

        .invalid-feedback { font-size:12px; display:flex; align-items:center; gap:4px; }
        .phone-prefix { background:#f8fafc; border:1.5px solid #e2e8f0; border-right:none; border-radius:10px 0 0 10px; padding:0 12px; display:flex; align-items:center; color:#64748b; font-size:14px; }
        .phone-prefix + .form-control { border-radius:0 10px 10px 0; }
    </style>
</head>
<body>

{{-- Toast container --}}
<div class="toast-wrap" id="toastWrap"></div>

<div class="card">
    <div class="top-bar"></div>
    <div class="p-4 p-md-5">

        <div class="text-center mb-4">
            <div class="brand-icon"><i class="bi bi-journals"></i></div>
            <h5 class="fw-bold mb-1" style="color:#0c4a6e;">Library Staff Portal</h5>
            <p class="text-muted mb-0" style="font-size:13px;">
                Enter your registered mobile number to receive a login OTP.
            </p>
        </div>

        <form method="POST" action="{{ route('library_staff.login.submit') }}" id="loginForm" novalidate>
            @csrf

            <div class="mb-4">
                <label class="form-label">Mobile Number</label>
                <div class="input-group">
                    <span class="phone-prefix"><i class="bi bi-phone me-1"></i></span>
                    <input type="text" name="phone" id="phone"
                           class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone') }}"
                           placeholder="Enter your registered mobile number"
                           autocomplete="tel" autofocus>
                    @error('phone')
                        <div class="invalid-feedback d-block">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-submit text-white w-100" id="submitBtn">
                <i class="bi bi-send me-2"></i>Send OTP
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="{{ route('login') }}" class="text-muted text-decoration-none" style="font-size:12px;">
                <i class="bi bi-arrow-left me-1"></i>Back to Institute Login
            </a>
        </div>

    </div>
</div>

<script>
// Show existing flash messages as toasts
(function() {
    @if(session('error'))
        showToast('error', '{{ addslashes(session('error')) }}');
    @endif
    @if(session('success'))
        showToast('success', '{{ addslashes(session('success')) }}');
    @endif
    @if($errors->any() && !$errors->has('phone'))
        showToast('error', '{{ addslashes($errors->first()) }}');
    @endif
})();

function showToast(type, message) {
    const wrap = document.getElementById('toastWrap');
    const el = document.createElement('div');
    el.className = 'notif ' + type;
    el.innerHTML = `
        <span class="notif-icon"><i class="bi bi-${type === 'error' ? 'exclamation-circle-fill' : 'check-circle-fill'}"></i></span>
        <span class="notif-body">${message}</span>
        <button class="notif-close" onclick="this.closest('.notif').remove()"><i class="bi bi-x"></i></button>
        <div class="notif-progress"></div>
    `;
    wrap.appendChild(el);
    setTimeout(() => el.style.opacity = '0', 4800);
    setTimeout(() => el.remove(), 5100);
}

// Submit button loading state
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending OTP...';
    btn.disabled = true;
});
</script>
</body>
</html>
