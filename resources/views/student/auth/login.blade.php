<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login — Gaurangi Technologies</title>
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
        .input-group .form-control{border-radius:10px 0 0 10px;}
        .eye-btn{border:1.5px solid #e5e7eb;border-left:none;border-radius:0 10px 10px 0;background:#f9fafb;color:#9ca3af;padding:0 12px;cursor:pointer;transition:color .15s;}
        .eye-btn:hover{color:#2563EB;}
        .btn-submit{height:46px;border-radius:10px;font-size:14px;font-weight:600;background:linear-gradient(135deg,#1e3a8a,#2563EB);border:none;color:#fff;letter-spacing:.02em;transition:all .18s ease;width:100%;}
        .btn-submit:hover{background:linear-gradient(135deg,#1e3a8a,#1d4ed8);box-shadow:0 6px 20px rgba(37,99,235,.35);transform:translateY(-1px);}
        .btn-submit:active{transform:translateY(0);box-shadow:none;}
        .form-check-input:checked{background-color:#2563EB;border-color:#2563EB;}
        .card-foot{background:#f8fafc;border-top:1px solid #f1f5f9;padding:14px 28px;display:flex;align-items:center;justify-content:space-between;}
        .back-link{font-size:12px;color:#6b7280;text-decoration:none;display:inline-flex;align-items:center;gap:4px;transition:color .15s;}
        .back-link:hover{color:#2563EB;}
        .gt-foot{display:flex;align-items:center;gap:6px;font-size:10px;color:#9ca3af;}
        .gt-foot img{height:16px;opacity:.6;}
        .alert-box{border-radius:10px;border:none;font-size:13px;padding:10px 14px;margin-bottom:16px;}
        .alert-danger-box{background:#fef2f2;color:#dc2626;border-left:3px solid #ef4444;}
        .alert-info-box{background:#eff6ff;color:#1d4ed8;border-left:3px solid #3b82f6;}
        .powered-by{text-align:center;padding:10px 0 8px;font-size:11.5px;color:#94a3b8;background:#f8fafc;margin:0;border-top:1px solid #f1f5f9;}
        .powered-by strong{color:#6366f1;font-weight:600;}
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
            <div class="portal-icon"><i class="bi bi-mortarboard-fill"></i></div>
            <h5>Student Login</h5>
            <p>Login with your Student ID and password</p>
        </div>

        <div class="card-body-wrap">

            @if(session('error'))
            <div class="alert-box alert-danger-box d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
            @endif

            @if(session('info'))
            <div class="alert-box alert-info-box d-flex align-items-center gap-2">
                <i class="bi bi-info-circle-fill flex-shrink-0"></i>
                <span>{{ session('info') }}</span>
            </div>
            @endif

            @if($errors->any())
            <div class="alert-box alert-danger-box d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
                <span>{{ $errors->first() }}</span>
            </div>
            @endif

            <form method="POST" action="{{ route('student.login.submit') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Student ID</label>
                    <input type="text" name="student_uid"
                           class="form-control @error('student_uid') is-invalid @enderror"
                           value="{{ old('student_uid') }}"
                           placeholder="Enter your Student ID" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="pwdInput"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="Enter your password" required>
                        <button type="button" class="eye-btn" onclick="togglePwd()">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember" style="font-size:13px;color:#6b7280;">Remember me</label>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login to Student Portal
                </button>
            </form>

        </div>

        <div class="card-foot">
            <a href="{{ url('/') }}" class="back-link">
                <i class="bi bi-arrow-left"></i> Portal Selection
            </a>
        </div>
        <p class="powered-by">Powered by <strong>Gaurangi Technologies</strong></p>
    </div>
</div>

<script>
function togglePwd(){const i=document.getElementById('pwdInput'),e=document.getElementById('eyeIcon');i.type=i.type==='password'?'text':'password';e.className=i.type==='text'?'bi bi-eye-slash':'bi bi-eye';}
</script>
</body>
</html>
