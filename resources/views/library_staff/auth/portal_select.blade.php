<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background:linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #f8fafc 100%);
            min-height:100vh; display:flex; align-items:center; justify-content:center;
            font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
            padding:20px;
        }
        .wrap { width:100%; max-width:520px; }
        .header-card {
            background:linear-gradient(135deg,#0c4a6e,#0ea5e9);
            border-radius:20px 20px 0 0; padding:28px 28px 20px;
            color:#fff; text-align:center;
        }
        .portal-card {
            border:2px solid #e2e8f0; border-radius:16px; padding:22px;
            cursor:pointer; transition:all .2s ease; text-decoration:none; color:inherit;
            display:block;
        }
        .portal-card:hover { border-color:#0ea5e9; box-shadow:0 4px 20px rgba(14,165,233,.15); transform:translateY(-2px); }
        .portal-icon {
            width:52px; height:52px; border-radius:14px;
            display:flex; align-items:center; justify-content:center; font-size:22px;
            margin-bottom:12px;
        }
        .portal-lib { background:#f0f9ff; color:#0ea5e9; }
        .portal-staff { background:#f0fdf4; color:#16a34a; }
        .divider-card { background:#fff; padding:20px 28px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="header-card">
        <i class="bi bi-person-badge-fill" style="font-size:40px; opacity:.9;"></i>
        <h5 class="fw-bold mt-3 mb-1">Welcome, {{ $staff->name }}</h5>
        <p class="mb-0 opacity-75" style="font-size:13px;">
            Your account has access to two portals. Please select where you'd like to go.
        </p>
    </div>

    <div class="divider-card rounded-bottom shadow-sm">
        <div class="row g-3">
            <div class="col-sm-6">
                <a href="{{ route('library_staff.dashboard') }}" class="portal-card">
                    <div class="portal-icon portal-lib"><i class="bi bi-journals"></i></div>
                    <div class="fw-bold mb-1" style="font-size:15px;color:#0c4a6e;">Library Portal</div>
                    <p class="text-muted mb-0" style="font-size:12px;">
                        Manage books, issue &amp; return, members, fines, and library reports.
                    </p>
                    <div class="mt-2" style="font-size:12px;color:#0ea5e9;font-weight:600;">
                        Enter <i class="bi bi-arrow-right ms-1"></i>
                    </div>
                </a>
            </div>
            <div class="col-sm-6">
                <a href="{{ route('staff.login') }}" class="portal-card">
                    <div class="portal-icon portal-staff"><i class="bi bi-person-workspace"></i></div>
                    <div class="fw-bold mb-1" style="font-size:15px;color:#166534;">Staff Portal</div>
                    <p class="text-muted mb-0" style="font-size:12px;">
                        Access admissions, fee collection, payroll, and staff operations.
                    </p>
                    <div class="mt-2" style="font-size:12px;color:#16a34a;font-weight:600;">
                        Login separately <i class="bi bi-box-arrow-up-right ms-1"></i>
                    </div>
                </a>
            </div>
        </div>

        <div class="text-center mt-4">
            <form method="POST" action="{{ route('library_staff.logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-box-arrow-left me-1"></i>Logout
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
