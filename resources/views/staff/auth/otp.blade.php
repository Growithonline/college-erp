<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification — Staff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;}
        body{margin:0;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;font-family:'Inter','Segoe UI',sans-serif;padding:20px;}
        .bg-hero{position:fixed;inset:0;z-index:0;background:linear-gradient(145deg,#0a1c3d 0%,#0f3d2e 60%,#0a2515 100%);}
        .bg-mesh{position:fixed;inset:0;z-index:1;opacity:.06;background-image:linear-gradient(rgba(255,255,255,.5) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.5) 1px,transparent 1px);background-size:48px 48px;}
        .bg-glow1{position:fixed;z-index:2;pointer-events:none;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(15,76,129,.5) 0%,transparent 70%);top:-150px;left:-100px;animation:floatOrb 8s ease-in-out infinite;}
        .bg-glow2{position:fixed;z-index:2;pointer-events:none;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(29,158,117,.4) 0%,transparent 70%);bottom:-120px;right:-80px;animation:floatOrb 10s ease-in-out infinite reverse;}
        @keyframes floatOrb{0%,100%{transform:translateY(0);}50%{transform:translateY(-18px);}}
        .login-wrap{position:relative;z-index:10;width:100%;max-width:420px;}
        .login-card{border-radius:20px;overflow:hidden;border:none;box-shadow:0 24px 60px rgba(0,0,0,.5),0 0 0 1px rgba(255,255,255,.07);}
        .card-head{background:linear-gradient(135deg,#0a3d20 0%,#0f4c81 50%,#1D9E75 100%);padding:28px 28px 22px;position:relative;overflow:hidden;}
        .card-head::before{content:'';position:absolute;right:-30px;top:-30px;width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,.06);}
        .card-head::after{content:'';position:absolute;left:20px;bottom:-50px;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,.04);}
        .portal-icon{width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.15);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;margin-bottom:12px;border:1px solid rgba(255,255,255,.2);}
        .card-head h5{color:#fff;font-size:18px;font-weight:700;margin:0 0 4px;position:relative;z-index:1;}
        .card-head p{color:rgba(255,255,255,.7);font-size:12px;margin:0;position:relative;z-index:1;}
        .gt-badge{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);border-radius:20px;padding:3px 10px;font-size:10px;color:rgba(255,255,255,.8);position:absolute;top:16px;right:16px;z-index:1;}
        .email-chip{display:inline-flex;align-items:center;gap:6px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:6px 12px;font-size:12px;color:#166534;font-weight:600;margin-top:8px;word-break:break-all;}
        .card-body-wrap{background:#fff;padding:24px 28px 20px;}
        .form-label{font-size:12px;font-weight:600;color:#374151;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px;}
        .otp-input{border-radius:10px;height:54px;border:1.5px solid #e5e7eb;font-size:22px;letter-spacing:.4em;text-align:center;font-weight:700;color:#111827;background:#f9fafb;transition:border-color .18s,box-shadow .18s,background .18s;width:100%;padding:0 10px;}
        .otp-input:focus{border-color:#1D9E75;box-shadow:0 0 0 3px rgba(29,158,117,.12);background:#fff;outline:none;}
        .otp-input.is-invalid{border-color:#ef4444;}
        .btn-submit{height:46px;border-radius:10px;font-size:14px;font-weight:600;background:linear-gradient(135deg,#147a5a,#1D9E75);border:none;color:#fff;letter-spacing:.02em;transition:all .18s ease;width:100%;}
        .btn-submit:hover{background:linear-gradient(135deg,#0f6349,#178a64);box-shadow:0 6px 20px rgba(29,158,117,.35);transform:translateY(-1px);}
        .btn-submit:active{transform:translateY(0);box-shadow:none;}
        .card-foot{background:#f8fafc;border-top:1px solid #f1f5f9;padding:14px 28px;display:flex;align-items:center;justify-content:space-between;}
        .back-link{font-size:12px;color:#6b7280;text-decoration:none;display:inline-flex;align-items:center;gap:4px;transition:color .15s;}
        .back-link:hover{color:#1D9E75;}
        .resend-btn{font-size:12px;color:#1D9E75;background:none;border:none;padding:0;cursor:pointer;font-weight:600;text-decoration:none;}
        .resend-btn:hover{color:#147a5a;}
        .gt-foot{display:flex;align-items:center;gap:6px;font-size:10px;color:#9ca3af;}
        .gt-foot img{height:16px;opacity:.6;}
        .alert-box{border-radius:10px;border:none;font-size:13px;padding:10px 14px;margin-bottom:16px;}
        .alert-danger-box{background:#fef2f2;color:#dc2626;border-left:3px solid #ef4444;}
        .alert-success-box{background:#f0fdf4;color:#166534;border-left:3px solid #22c55e;}
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
            <span class="gt-badge"><i class="bi bi-shield-lock-fill"></i> OTP Verification</span>
            <div class="portal-icon"><i class="bi bi-envelope-open-fill"></i></div>
            <h5>Check Your Email</h5>
            <p style="margin-bottom:6px;">A 6-digit OTP was sent to</p>
            <div class="email-chip"><i class="bi bi-envelope-fill"></i>{{ $staff->email }}</div>
            @if($staff->mobile)
            <div style="margin-top:6px;font-size:11px;color:rgba(255,255,255,.7);position:relative;z-index:1;">
                <i class="bi bi-phone me-1"></i>Also sent via SMS to registered mobile
            </div>
            @endif
        </div>

        <div class="card-body-wrap">

            @if(session('success'))
            <div class="alert-box alert-success-box d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
            @endif

            @if($errors->any())
            <div class="alert-box alert-danger-box d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
                <span>{{ $errors->first() }}</span>
            </div>
            @endif

            <form method="POST" action="{{ route('staff.otp.verify') }}">
                @csrf
                <div class="mb-4">
                    <label class="form-label">Enter 6-Digit OTP</label>
                    <input type="text" name="otp" maxlength="6" inputmode="numeric" autocomplete="one-time-code"
                           class="otp-input @error('otp') is-invalid @enderror"
                           placeholder="• • • • • •" required autofocus>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="bi bi-check2-circle me-2"></i>Verify & Login
                </button>
            </form>

        </div>

        <div class="card-foot">
            <a href="{{ route('staff.login') }}" class="back-link">
                <i class="bi bi-arrow-left"></i> Back to login
            </a>
            <div class="d-flex align-items-center gap-3">
                <form method="POST" action="{{ route('staff.otp.resend') }}" class="mb-0">
                    @csrf
                    <button type="submit" class="resend-btn">
                        <i class="bi bi-arrow-clockwise me-1"></i>Resend OTP
                    </button>
                </form>
                <div class="gt-foot">
                    <img src="{{ asset('images/logog.png') }}" alt="Gaurangi">
                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>
