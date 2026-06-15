<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body{background:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;}
        .card{width:100%;max-width:420px;border:none;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;}
        .top-bar{height:4px;background:#2563EB;}
        .brand-circle{width:52px;height:52px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center;font-size:22px;color:#2563EB;margin:0 auto 12px;}
        .form-control{border-radius:8px;height:44px;border:1.5px solid #e2e8f0;font-size:14px;}
        .form-control:focus{border-color:#2563EB;box-shadow:0 0 0 3px #2563eb25;}
        .btn-submit{height:46px;border-radius:8px;font-size:15px;font-weight:500;background:#2563EB;border:none;letter-spacing:.01em;transition:background .18s ease,box-shadow .18s ease,transform .15s ease;}
        .btn-submit:hover{background:#1d4ed8;box-shadow:0 4px 14px rgba(37,99,235,.35);transform:translateY(-1px);}
        .btn-submit:active{transform:translateY(0);box-shadow:none;}
    </style>
</head>
<body>
<div class="card">
    <div class="top-bar"></div>
    <div class="p-4 p-md-5">

        <div class="text-center mb-4">
            <div class="brand-circle"><i class="bi bi-mortarboard-fill"></i></div>
            <h5 class="fw-bold mb-1" style="color:#1e293b;">Student Portal</h5>
            <p class="text-muted mb-0" style="font-size:13px;">Login with your Student ID and password</p>
        </div>

        @if(session('error'))
        <div class="alert alert-danger py-2 small border-0 rounded-3">
            <i class="bi bi-exclamation-circle me-1"></i>{{ session('error') }}
        </div>
        @endif

        @if(session('info'))
        <div class="alert alert-info py-2 small border-0 rounded-3">
            <i class="bi bi-info-circle me-1"></i>{{ session('info') }}
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger py-2 small border-0 rounded-3">
            <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('student.login.submit') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Student ID</label>
                <input type="text" name="student_uid" id="student_uid"
                       class="form-control @error('student_uid') is-invalid @enderror"
                       value="{{ old('student_uid') }}"
                       placeholder="Enter your Student ID" required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="pwdInput"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Enter your password" required>
                    <button type="button" class="btn btn-outline-secondary border-start-0"
                            style="border-radius:0 8px 8px 0;border:1.5px solid #e2e8f0;border-left:none;"
                            onclick="togglePwd()">
                        <i class="bi bi-eye" id="eyeIcon" style="font-size:14px;"></i>
                    </button>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember" style="font-size:13px;">Remember me</label>
                </div>
            </div>

            <button type="submit" class="btn btn-submit text-white w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login
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
function togglePwd() {
    const inp = document.getElementById('pwdInput');
    const icon = document.getElementById('eyeIcon');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    icon.className = inp.type === 'text' ? 'bi bi-eye-slash' : 'bi bi-eye';
}
</script>
</body>
</html>
