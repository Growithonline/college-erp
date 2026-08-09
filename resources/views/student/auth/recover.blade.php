<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Recovery — Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;}
        body{margin:0;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;font-family:'Inter','Segoe UI',sans-serif;padding:20px;}
        .bg-hero{position:fixed;inset:0;z-index:0;background:linear-gradient(145deg,#0a1c3d 0%,#0f3d2e 60%,#0a2515 100%);}
        .bg-mesh{position:fixed;inset:0;z-index:1;opacity:.06;background-image:linear-gradient(rgba(255,255,255,.5) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.5) 1px,transparent 1px);background-size:48px 48px;}
        .bg-glow1{position:fixed;z-index:2;pointer-events:none;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(124,58,237,.4) 0%,transparent 70%);top:-150px;left:-100px;animation:floatOrb 8s ease-in-out infinite;}
        .bg-glow2{position:fixed;z-index:2;pointer-events:none;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(37,99,235,.35) 0%,transparent 70%);bottom:-120px;right:-80px;animation:floatOrb 10s ease-in-out infinite reverse;}
        @keyframes floatOrb{0%,100%{transform:translateY(0);}50%{transform:translateY(-18px);}}
        .login-wrap{position:relative;z-index:10;width:100%;max-width:420px;}
        .login-card{border-radius:20px;overflow:hidden;border:none;box-shadow:0 24px 60px rgba(0,0,0,.5),0 0 0 1px rgba(255,255,255,.07);}
        .card-head{background:linear-gradient(135deg,#1e3a8a 0%,#1d4ed8 60%,#2563EB 100%);padding:18px 28px 16px;position:relative;overflow:hidden;}
        .card-head::before{content:'';position:absolute;right:-30px;top:-30px;width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,.07);}
        .card-head::after{content:'';position:absolute;left:20px;bottom:-50px;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,.04);}
        .portal-icon{width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.15);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;margin-bottom:12px;border:1px solid rgba(255,255,255,.2);}
        .card-head h5{color:#fff;font-size:18px;font-weight:700;margin:0 0 4px;position:relative;z-index:1;}
        .card-head p{color:rgba(255,255,255,.7);font-size:12px;margin:0;position:relative;z-index:1;}
        .card-body-wrap{background:#fff;padding:24px 28px 20px;}
        .form-label{font-size:12px;font-weight:600;color:#374151;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px;}
        .form-control{border-radius:10px;height:44px;border:1.5px solid #e5e7eb;font-size:14px;color:#111827;background:#f9fafb;transition:border-color .18s,box-shadow .18s,background .18s;}
        .form-control:focus{border-color:#2563EB;box-shadow:0 0 0 3px rgba(37,99,235,.12);background:#fff;outline:none;}
        .form-control.is-invalid{border-color:#ef4444;}
        .btn-submit{height:46px;border-radius:10px;font-size:14px;font-weight:600;background:linear-gradient(135deg,#1e3a8a,#2563EB);border:none;color:#fff;letter-spacing:.02em;transition:all .18s ease;width:100%;}
        .btn-submit:hover{background:linear-gradient(135deg,#1e3a8a,#1d4ed8);box-shadow:0 6px 20px rgba(37,99,235,.35);transform:translateY(-1px);}
        .btn-submit:active{transform:translateY(0);box-shadow:none;}
        .card-foot{background:#f8fafc;border-top:1px solid #f1f5f9;padding:14px 28px;display:flex;align-items:center;justify-content:center;}
        .back-link{font-size:12px;color:#6b7280;text-decoration:none;display:inline-flex;align-items:center;gap:4px;transition:color .15s;}
        .back-link:hover{color:#2563EB;}
        .gt-foot{display:flex;align-items:center;gap:6px;font-size:10px;color:#9ca3af;}
        .alert-box{border-radius:10px;border:none;font-size:13px;padding:10px 14px;margin-bottom:16px;}
        .alert-danger-box{background:#fef2f2;color:#dc2626;border-left:3px solid #ef4444;}
        .powered-by{text-align:center;padding:10px 0 8px;font-size:11.5px;color:#94a3b8;background:#f8fafc;margin:0;border-top:1px solid #f1f5f9;}
        .powered-by strong{color:#6366f1;font-weight:600;}
        .mode-tabs{display:flex;background:#f1f5f9;border-radius:10px;padding:4px;margin-bottom:20px;gap:4px;}
        .mode-tab{flex:1;text-align:center;padding:9px 8px;border-radius:8px;font-size:12.5px;font-weight:600;color:#64748b;cursor:pointer;transition:all .18s ease;user-select:none;}
        .mode-tab.active{background:#fff;color:#1d4ed8;box-shadow:0 2px 6px rgba(0,0,0,.08);}
        .hint-text{font-size:12px;color:#6b7280;margin-bottom:18px;line-height:1.5;}
    </style>
</head>
<body>

<div class="bg-hero"></div>
<div class="bg-mesh"></div>
<div class="bg-glow1"></div>
<div class="bg-glow2"></div>

<div class="login-wrap">
    <div class="login-card">

        <div class="card-head">
            <div style="text-align:center;margin-bottom:10px;position:relative;z-index:2;">
                <img src="{{ asset('images/logog.png') }}" alt="Gaurangi Technologies" style="height:38px;width:auto;filter:brightness(0) invert(1);opacity:.88;">
            </div>
            <hr style="border:none;border-top:1px solid rgba(255,255,255,.15);margin:0 0 14px;position:relative;z-index:2;">
            <div class="portal-icon"><i class="bi bi-shield-lock-fill"></i></div>
            <h5>Account Recovery</h5>
            <p>Verify your identity to continue</p>
        </div>

        <div class="card-body-wrap">

            @if($errors->any())
            <div class="alert-box alert-danger-box d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
                <span>{{ $errors->first() }}</span>
            </div>
            @endif

            <div class="mode-tabs">
                <div class="mode-tab" data-mode="know_id" onclick="setMode('know_id')">
                    <i class="bi bi-person-badge me-1"></i>Know My Student ID
                </div>
                <div class="mode-tab" data-mode="forgot_password" onclick="setMode('forgot_password')">
                    <i class="bi bi-key me-1"></i>Forgot Password
                </div>
            </div>

            <p class="hint-text" id="modeHint"></p>

            <form method="POST" action="{{ route('student.recover.send-otp') }}">
                @csrf
                <input type="hidden" name="mode" id="modeInput" value="{{ old('mode', request('mode', 'know_id')) }}">

                <div class="mb-3">
                    <label class="form-label">Registered Email</label>
                    <input type="email" name="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}"
                           placeholder="you@example.com" required autofocus>
                </div>

                <div class="mb-4">
                    <label class="form-label">Aadhar Number</label>
                    <input type="text" name="aadhar_no" maxlength="12" inputmode="numeric"
                           class="form-control @error('aadhar_no') is-invalid @enderror"
                           value="{{ old('aadhar_no') }}"
                           placeholder="12-digit Aadhar number" required>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="bi bi-send-check me-2"></i>Send OTP
                </button>
            </form>

        </div>

        <div class="card-foot">
            <a href="{{ route('student.login') }}" class="back-link">
                <i class="bi bi-arrow-left"></i> Back to login
            </a>
        </div>
        <p class="powered-by">Powered by <strong>Gaurangi Technologies</strong></p>
    </div>
</div>

<script>
function setMode(mode) {
    document.getElementById('modeInput').value = mode;
    document.querySelectorAll('.mode-tab').forEach(function (el) {
        el.classList.toggle('active', el.dataset.mode === mode);
    });
    document.getElementById('modeHint').textContent = mode === 'know_id'
        ? 'We will verify your email and Aadhar number, then show your Student ID.'
        : 'We will verify your email and Aadhar number, then let you set a new password.';
}
setMode(document.getElementById('modeInput').value || 'know_id');
</script>
</body>
</html>
