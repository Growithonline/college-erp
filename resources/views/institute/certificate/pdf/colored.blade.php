<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 13px; color: #1a1a1a; background: #fff; }

    .page { width: 100%; min-height: 270mm; position: relative; }

    /* ─── Colored Header Band ────────────────── */
    .header-band {
        background: {{ $settings->primary_color ?? '#1e3a5f' }};
        color: #fff;
        padding: 16px 24px;
        display: flex;
        align-items: center;
        gap: 18px;
    }
    .header-band .logo img { max-height: 65px; max-width: 130px; background: rgba(255,255,255,.15); padding: 4px; border-radius: 4px; }
    .header-band .inst-info { flex: 1; }
    .inst-name  { font-size: 18px; font-weight: bold; letter-spacing: 0.3px; }
    .inst-line2 { font-size: 12px; opacity: .85; margin-top: 2px; }
    .inst-line3 { font-size: 11px; opacity: .70; margin-top: 1px; }

    /* ─── Accent strip ───────────────────────── */
    .accent-strip { height: 5px; background: linear-gradient(90deg, {{ $settings->primary_color ?? '#1e3a5f' }}99, {{ $settings->primary_color ?? '#1e3a5f' }}22); }

    /* ─── Content area ───────────────────────── */
    .content { padding: 18px 24px; }

    /* ─── Certificate Title ─────────────────── */
    .cert-title {
        text-align: center;
        font-size: 16px;
        font-weight: bold;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: {{ $settings->primary_color ?? '#1e3a5f' }};
        margin: 16px 0;
        padding: 8px 0;
        border-bottom: 2px solid {{ $settings->primary_color ?? '#1e3a5f' }};
    }

    .meta-row { display: flex; justify-content: space-between; font-size: 11px; color: #555; margin-bottom: 18px; }

    /* ─── Body ───────────────────────────────── */
    .body-content { line-height: 1.9; font-size: 13px; text-align: justify; }
    .body-content p { margin-bottom: 12px; }

    /* ─── Signature Row ──────────────────────── */
    .sig-row { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 40px; padding-top: 20px; border-top: 1px solid #e0e0e0; }
    .sig-block { text-align: center; width: 40%; }
    .sig-block img { max-height: 55px; max-width: 160px; margin-bottom: 4px; display: block; margin-left: auto; margin-right: auto; }
    .sig-line { border-top: 1px solid #555; padding-top: 5px; font-size: 12px; font-weight: bold; }
    .sig-desig { font-size: 11px; color: #666; margin-top: 2px; }

    .seal-area { text-align: center; width: 20%; }
    .seal-area img { max-height: 70px; max-width: 70px; opacity: 0.85; }

    /* ─── Footer band ────────────────────────── */
    .footer-band {
        background: {{ $settings->primary_color ?? '#1e3a5f' }}18;
        border-top: 2px solid {{ $settings->primary_color ?? '#1e3a5f' }}44;
        padding: 8px 24px;
        font-size: 10px;
        color: #666;
        text-align: center;
        margin-top: 30px;
    }
</style>
</head>
<body>
<div class="page">

    <div class="header-band">
        @if($settings->logo)
            <div class="logo"><img src="{{ public_path('storage/' . $settings->logo) }}" alt="Logo"></div>
        @endif
        <div class="inst-info">
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
        @if($settings->seal_image)
            <div><img src="{{ public_path('storage/' . $settings->seal_image) }}" alt="Seal" style="max-height:60px;max-width:60px;opacity:.9;"></div>
        @endif
    </div>
    <div class="accent-strip"></div>

    <div class="content">
        <div class="cert-title">{{ $type->name }}</div>

        <div class="meta-row">
            <span>Certificate No.: <strong>{{ $certificate->certificate_number }}</strong></span>
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
                @if(!$settings->seal_image)
                    <div style="font-size:10px;color:#aaa;">[ SEAL ]</div>
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

    <div class="footer-band">
        {{ $settings->header_line1 }}
        @if($settings->header_line2) &nbsp;•&nbsp; {{ $settings->header_line2 }} @endif
    </div>
</div>
</body>
</html>
