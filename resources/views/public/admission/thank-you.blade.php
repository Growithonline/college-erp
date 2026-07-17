<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You — {{ $institute->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f0f4f8; min-height: 100vh; display: flex; align-items: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card border-0 shadow-sm mx-auto" style="max-width: 480px;">
            <div class="card-body p-5 text-center">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 48px;"></i>
                <h4 class="fw-bold mt-3">Enquiry Submitted</h4>
                <p class="text-muted mb-0">
                    Thank you for your interest in <strong>{{ $institute->name }}</strong>.
                    Our admission team will contact you soon.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
