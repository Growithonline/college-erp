<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff OTP Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body{background:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;}
        .card{width:100%;max-width:420px;border:none;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;}
        .top-bar{height:4px;background:#1D9E75;}
        .otp-chip{display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:999px;background:#1D9E7515;color:#1D9E75;font-size:12px;font-weight:600;}
        .form-control{border-radius:8px;height:48px;border:1.5px solid #e2e8f0;font-size:18px;letter-spacing:.35em;text-align:center;font-weight:700;}
        .form-control:focus{border-color:#1D9E75;box-shadow:0 0 0 3px #1D9E7525;}
        .btn-submit{height:46px;border-radius:8px;font-size:15px;font-weight:500;background:#1D9E75;border:none;transition:background .18s ease,box-shadow .18s ease,transform .15s ease;}
        .btn-submit:hover{background:#178a64;box-shadow:0 4px 14px rgba(29,158,117,.35);transform:translateY(-1px);}
        .btn-submit:active{transform:translateY(0);box-shadow:none;}
    </style>
</head>
<body>
<div class="card">
    <div class="top-bar"></div>
    <div class="p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="otp-chip mb-3"><i class="bi bi-shield-lock"></i> OTP Verification</div>
            <h5 class="fw-bold mb-1" style="color:#1e293b;">Check your email</h5>
            <p class="text-muted mb-0 small">
                A 6-digit OTP has been sent to <strong>{{ $staff->email }}</strong>.
                @if($staff->mobile)
                    <br><span class="text-success" style="font-size:11px;"><i class="bi bi-phone me-1"></i>Also sent via SMS to your registered mobile.</span>
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

        <form method="POST" action="{{ route('staff.otp.verify') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Enter OTP</label>
                <input type="text" name="otp" maxlength="6" inputmode="numeric"
                       class="form-control @error('otp') is-invalid @enderror"
                       placeholder="000000" required autofocus>
            </div>

            <button type="submit" class="btn btn-submit text-white w-100">
                <i class="bi bi-check2-circle me-2"></i>Verify & Login
            </button>
        </form>

        <div class="d-flex justify-content-between align-items-center mt-4">
            <a href="{{ route('staff.login') }}" class="small text-muted text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>Back to login
            </a>
            <form method="POST" action="{{ route('staff.otp.resend') }}">
                @csrf
                <button type="submit" class="btn btn-link text-decoration-none p-0 small">
                    Resend OTP
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
