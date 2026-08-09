<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Student ID</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;}
        body{margin:0;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;font-family:'Inter','Segoe UI',sans-serif;padding:20px;}
        .bg-hero{position:fixed;inset:0;z-index:0;background:linear-gradient(145deg,#0a1c3d 0%,#0f3d2e 60%,#0a2515 100%);}
        .bg-mesh{position:fixed;inset:0;z-index:1;opacity:.06;background-image:linear-gradient(rgba(255,255,255,.5) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.5) 1px,transparent 1px);background-size:48px 48px;}
        .bg-glow1{position:fixed;z-index:2;pointer-events:none;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(16,185,129,.35) 0%,transparent 70%);top:-150px;left:-100px;animation:floatOrb 8s ease-in-out infinite;}
        .bg-glow2{position:fixed;z-index:2;pointer-events:none;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(37,99,235,.35) 0%,transparent 70%);bottom:-120px;right:-80px;animation:floatOrb 10s ease-in-out infinite reverse;}
        @keyframes floatOrb{0%,100%{transform:translateY(0);}50%{transform:translateY(-18px);}}
        .login-wrap{position:relative;z-index:10;width:100%;max-width:420px;}
        .login-card{border-radius:20px;overflow:hidden;border:none;box-shadow:0 24px 60px rgba(0,0,0,.5),0 0 0 1px rgba(255,255,255,.07);}
        .card-head{background:linear-gradient(135deg,#065f46 0%,#059669 60%,#10b981 100%);padding:18px 28px 16px;position:relative;overflow:hidden;text-align:center;}
        .card-head::before{content:'';position:absolute;right:-30px;top:-30px;width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,.07);}
        .portal-icon{width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.15);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;margin:0 auto 12px;border:1px solid rgba(255,255,255,.2);}
        .card-head h5{color:#fff;font-size:18px;font-weight:700;margin:0 0 4px;position:relative;z-index:1;}
        .card-head p{color:rgba(255,255,255,.7);font-size:12px;margin:0;position:relative;z-index:1;}
        .card-body-wrap{background:#fff;padding:28px 28px 20px;text-align:center;}
        .uid-box{background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:12px;padding:22px;margin-bottom:18px;}
        .uid-label{font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;}
        .uid-value{font-size:22px;font-weight:800;color:#059669;letter-spacing:1px;word-break:break-all;}
        .btn-submit{height:46px;border-radius:10px;font-size:14px;font-weight:600;background:linear-gradient(135deg,#1e3a8a,#2563EB);border:none;color:#fff;letter-spacing:.02em;transition:all .18s ease;width:100%;text-decoration:none;display:flex;align-items:center;justify-content:center;}
        .btn-submit:hover{background:linear-gradient(135deg,#1e3a8a,#1d4ed8);box-shadow:0 6px 20px rgba(37,99,235,.35);transform:translateY(-1px);color:#fff;}
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
            <div class="portal-icon"><i class="bi bi-check-circle-fill"></i></div>
            <h5>Identity Verified</h5>
            <p>Here is your Student ID</p>
        </div>

        <div class="card-body-wrap">
            <div class="uid-box">
                <div class="uid-label">Your Student ID</div>
                <div class="uid-value">{{ session('recovered_student_uid') }}</div>
            </div>

            <a href="{{ route('student.login') }}" class="btn-submit">
                <i class="bi bi-box-arrow-in-right me-2"></i>Continue to Login
            </a>
        </div>

        <p class="powered-by">Powered by <strong>Gaurangi Technologies</strong></p>
    </div>
</div>

</body>
</html>
