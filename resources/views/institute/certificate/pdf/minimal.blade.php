<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 13px; color: #222; background: #fff; }

    .page { width: 100%; min-height: 270mm; padding: 20mm 22mm; }

    /* ─── Header ─────────────────────────────── */
    .header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 14px; border-bottom: 3px solid {{ $settings->primary_color ?? '#1e3a5f' }}; margin-bottom: 20px; }
    .header-left { display: flex; align-items: center; gap: 14px; }
    .header-left img { max-height: 65px; max-width: 140px; }
    .inst-name  { font-size: 17px; font-weight: bold; color: {{ $settings->primary_color ?? '#1e3a5f' }}; }
    .inst-line2 { font-size: 11px; color: #555; margin-top: 3px; }
    .inst-line3 { font-size: 10px; color: #777; margin-top: 2px; }
    .header-seal img { max-height: 65px; max-width: 65px; opacity: 0.8; }

    /* ─── Certificate Title ─────────────────── */
    .cert-title {
        text-align: center;
        font-size: 18px;
        font-weight: bold;
        letter-spacing: 3px;
        text-transform: uppercase;
        color: {{ $settings->primary_color ?? '#1e3a5f' }};
        margin: 0 0 6px;
    }
    .cert-title-underline {
        text-align: center;
        margin-bottom: 22px;
    }
    .cert-title-underline span {
        display: inline-block;
        width: 80px;
        border-bottom: 2px solid {{ $settings->primary_color ?? '#1e3a5f' }};
    }

    .meta-row { display: flex; justify-content: space-between; font-size: 11px; color: #666; margin-bottom: 20px; }

    /* ─── Body ───────────────────────────────── */
    .body-content { line-height: 1.95; font-size: 13px; text-align: justify; }
    .body-content p { margin-bottom: 14px; }

    /* ─── Signature Row ──────────────────────── */
    .sig-row { display: flex; justify-content: space-between; margin-top: 50px; }
    .sig-left { width: 45%; }
    .sig-right { width: 45%; text-align: right; }
    .sig-block img { max-height: 50px; max-width: 150px; margin-bottom: 4px; }
    .sig-left img { display: block; }
    .sig-right img { display: block; margin-left: auto; }
    .sig-line { border-top: 1px solid #555; padding-top: 5px; font-size: 12px; font-weight: bold; color: #222; }
    .sig-desig { font-size: 11px; color: #666; margin-top: 2px; }
    .sig-right .sig-line { text-align: right; }
    .sig-right .sig-desig { text-align: right; }
</style>
</head>
<body>
<div class="page">

    <div class="header">
        <div class="header-left">
            @if($settings->logo)
                <img src="{{ public_path('storage/' . $settings->logo) }}" alt="Logo">
            @endif
            <div>
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
        </div>
        @if($settings->seal_image)
            <div class="header-seal"><img src="{{ public_path('storage/' . $settings->seal_image) }}" alt="Seal"></div>
        @endif
    </div>

    <div class="cert-title">{{ $type->name }}</div>
    <div class="cert-title-underline"><span></span></div>

    <div class="meta-row">
        <span>Cert. No.: <strong>{{ $certificate->certificate_number }}</strong></span>
        <span>Issued: <strong>{{ \Carbon\Carbon::parse($certificate->issued_at)->format('d F, Y') }}</strong></span>
    </div>

    <div class="body-content">
        {!! $bodyHtml !!}
    </div>

    <div class="sig-row">
        <div class="sig-left">
            @if($settings->principal_signature)
                <img src="{{ public_path('storage/' . $settings->principal_signature) }}" alt="Signature">
            @endif
            <div class="sig-line">{{ $settings->principal_name ?: '________________________' }}</div>
            <div class="sig-desig">{{ $settings->principal_designation ?: 'Principal' }}</div>
        </div>
        <div class="sig-right">
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
