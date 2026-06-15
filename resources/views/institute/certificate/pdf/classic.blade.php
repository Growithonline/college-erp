<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "DejaVu Serif", serif; font-size: 13px; color: #1a1a1a; background: #fff; }

    .page {
        width: 100%;
        min-height: 270mm;
        padding: 18mm 20mm;
        border: 3px double {{ $settings->primary_color ?? '#1e3a5f' }};
        outline: 1px solid {{ $settings->primary_color ?? '#1e3a5f' }};
        outline-offset: -6px;
        position: relative;
    }

    /* ─── Header ─────────────────────────────── */
    .header { text-align: center; margin-bottom: 10px; }
    .header-inner { display: inline-block; }
    .logo-area { margin-bottom: 8px; }
    .logo-area img { max-height: 72px; max-width: 160px; }
    .inst-name  { font-size: 19px; font-weight: bold; color: {{ $settings->primary_color ?? '#1e3a5f' }}; letter-spacing: 0.5px; }
    .inst-line2 { font-size: 12px; color: #444; margin-top: 2px; }
    .inst-line3 { font-size: 11px; color: #666; margin-top: 1px; }
    .header-divider { border: none; border-top: 2px solid {{ $settings->primary_color ?? '#1e3a5f' }}; margin: 10px 0 6px; }

    /* ─── Certificate Title ─────────────────── */
    .cert-title {
        text-align: center;
        font-size: 17px;
        font-weight: bold;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: {{ $settings->primary_color ?? '#1e3a5f' }};
        border-top: 1px solid #ccc;
        border-bottom: 1px solid #ccc;
        padding: 7px 0;
        margin: 12px 0 20px;
    }

    /* ─── Cert Number & Date ─────────────────── */
    .meta-row { display: flex; justify-content: space-between; font-size: 11px; color: #555; margin-bottom: 18px; }

    /* ─── Body ───────────────────────────────── */
    .body-content { line-height: 1.9; font-size: 13px; text-align: justify; }
    .body-content p { margin-bottom: 12px; }
    .body-content strong { color: #1a1a1a; }

    /* ─── Signature Row ──────────────────────── */
    .sig-row { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 40px; }
    .sig-block { text-align: center; width: 40%; }
    .sig-block img { max-height: 55px; max-width: 160px; margin-bottom: 4px; display: block; margin-left: auto; margin-right: auto; }
    .sig-line { border-top: 1px solid #555; padding-top: 5px; font-size: 12px; font-weight: bold; color: #333; }
    .sig-desig { font-size: 11px; color: #666; margin-top: 2px; }

    /* ─── Seal ───────────────────────────────── */
    .seal-area { text-align: center; width: 20%; }
    .seal-area img { max-height: 70px; max-width: 70px; opacity: 0.85; }
    .seal-label { font-size: 10px; color: #888; margin-top: 3px; }
</style>
</head>
<body>
<div class="page">

    <div class="header">
        @if($settings->logo)
            <div class="logo-area">
                <img src="{{ public_path('storage/' . $settings->logo) }}" alt="Logo">
            </div>
        @endif
        @if($settings->header_line1)
            <div class="inst-name">{{ $settings->header_line1 }}</div>
        @endif
        @if($settings->header_line2)
            <div class="inst-line2">{{ $settings->header_line2 }}</div>
        @endif
        @if($settings->header_line3)
            <div class="inst-line3">{{ $settings->header_line3 }}</div>
        @endif
    </div>

    <hr class="header-divider">

    <div class="cert-title">{{ $type->name }}</div>

    <div class="meta-row">
        <span>No.: <strong>{{ $certificate->certificate_number }}</strong></span>
        <span>Date: <strong>{{ \Carbon\Carbon::parse($certificate->issued_at)->format('d/m/Y') }}</strong></span>
    </div>

    <div class="body-content">
        {!! $bodyHtml !!}
    </div>

    <div class="sig-row">
        <div class="sig-block">
            @if($settings->principal_signature)
                <img src="{{ public_path('storage/' . $settings->principal_signature) }}" alt="Signature">
            @endif
            <div class="sig-line">{{ $settings->principal_name ?: '________________________' }}</div>
            <div class="sig-desig">{{ $settings->principal_designation ?: 'Principal' }}</div>
        </div>

        <div class="seal-area">
            @if($settings->seal_image)
                <img src="{{ public_path('storage/' . $settings->seal_image) }}" alt="Seal">
            @else
                <div class="seal-label">[ SEAL ]</div>
            @endif
        </div>

        <div class="sig-block">
            @if($settings->registrar_signature)
                <img src="{{ public_path('storage/' . $settings->registrar_signature) }}" alt="Signature">
            @endif
            <div class="sig-line">{{ $settings->registrar_name ?: '________________________' }}</div>
            <div class="sig-desig">{{ $settings->registrar_designation ?: 'Registrar' }}</div>
        </div>
    </div>

</div>
</body>
</html>
