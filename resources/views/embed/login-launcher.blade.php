<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>{{ $institute->name }} — {{ $portalLabel }} Login</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body {
            display: flex; align-items: center; justify-content: center;
            background: #f8fafc; color: #0f172a;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .card { width: 100%; max-width: 440px; padding: 18px 20px; text-align: center; }
        .logo { max-height: 56px; max-width: 100%; width: auto; object-fit: contain; margin-bottom: 8px; }
        .name { font-size: 18px; font-weight: 700; line-height: 1.3; }
        .portal { font-size: 12px; letter-spacing: 1.5px; text-transform: uppercase; color: #64748b; margin: 4px 0 14px; }
        .btn {
            display: inline-block; padding: 11px 26px; border-radius: 10px;
            background: #4f46e5; color: #fff; text-decoration: none;
            font-size: 14px; font-weight: 600;
        }
        .btn:hover { background: #4338ca; }
        @media (prefers-color-scheme: dark) {
            body { background: #0f172a; color: #f1f5f9; }
            .portal { color: #94a3b8; }
        }
    </style>
</head>
<body>
    <div class="card">
        @if($logoUrl)
            <img class="logo" src="{{ $logoUrl }}" alt="{{ $institute->name }}">
        @endif
        <div class="name">{{ $institute->name }}</div>
        <div class="portal">{{ $portalLabel }} Login</div>
        <a class="btn" href="{{ $loginUrl }}" target="_blank" rel="noopener">Open {{ $portalLabel }} Login</a>
    </div>
</body>
</html>
