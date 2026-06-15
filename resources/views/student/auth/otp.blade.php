<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal — OTP Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body{background:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;}
        .card{width:100%;max-width:420px;border:none;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;}
        .top-bar{height:4px;background:#2563EB;}
        .brand-circle{width:52px;height:52px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center;font-size:22px;color:#2563EB;margin:0 auto 12px;}
        .otp-input{letter-spacing:10px;font-size:22px;font-weight:700;text-align:center;border-radius:8px;height:56px;border:1.5px solid #e2e8f0;}
        .otp-input:focus{border-color:#2563EB;box-shadow:0 0 0 3px #2563eb25;}
        .btn-submit{height:46px;border-radius:8px;font-size:15px;font-weight:500;background:#2563EB;border:none;transition:background .18s ease;}
        .btn-submit:hover{background:#1d4ed8;}
    </style>
</head>
<body>
<div class="card">
    <div class="top-bar"></div>
    <div class="p-4 p-md-5">

        <div class="text-center mb-4">
            <div class="brand-circle"><i class="bi bi-shield-lock-fill"></i></div>
            <h5 class="fw-bold mb-1" style="color:#1e293b;">OTP Verification</h5>
            <p class="text-muted mb-0" style="font-size:13px;">
                OTP sent to
                @if($student->email)
                    <strong>{{ substr($student->email, 0, 3) }}***@{{ explode('@', $student->email)[1] }}</strong>
                @endif
                @if($student->mobile)
                    & mobile <strong>******{{ substr($student->mobile, -4) }}</strong>
                @endif
            </p>
        </div>

        @if(session('success'))
        <div class="alert alert-success py-2 small border-0 rounded-3">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger py-2 small border-0 rounded-3">
            <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('student.otp.verify') }}">
            @csrf
            <div class="mb-4">
                <label class="form-label fw-semibold" style="font-size:13px;">Enter 6-Digit OTP</label>
                <input type="text" name="otp" maxlength="6" inputmode="numeric" autocomplete="one-time-code"
                       class="form-control otp-input @error('otp') is-invalid @enderror"
                       placeholder="_ _ _ _ _ _" required autofocus>
            </div>
            <button type="submit" class="btn btn-submit text-white w-100">
                <i class="bi bi-check2-circle me-2"></i>Verify OTP
            </button>
        </form>

        <form method="POST" action="{{ route('student.otp.resend') }}" class="mt-3 text-center">
            @csrf
            <button type="submit" class="btn btn-link text-decoration-none p-0" style="font-size:13px;color:#2563EB;">
                <i class="bi bi-arrow-clockwise me-1"></i>Resend OTP
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="{{ route('student.login') }}" class="text-muted text-decoration-none" style="font-size:12px;">
                <i class="bi bi-arrow-left me-1"></i>Back to Login
            </a>
        </div>
    </div>
</div>
</body>
</html>
