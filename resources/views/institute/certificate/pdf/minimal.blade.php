<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @php $color = $settings->primary_color ?? '#1e3a5f'; @endphp
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 13px; color: #222; background: #fff; }

    .page { width: 100%; min-height: 271mm; padding: 16mm; border: 1px solid #eee; position: relative; }
    .inner { padding: 8mm 10mm; }

    /* ─── Header ─────────────────────────────── */
    .header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 16px; border-bottom: 3px solid {{ $color }}; margin-bottom: 26px; }
    .header-left { display: flex; align-items: center; gap: 14px; }
    .header-left img { max-height: 62px; max-width: 140px; }
    .inst-name  { font-size: 17px; font-weight: bold; color: {{ $color }}; letter-spacing: 0.3px; }
    .inst-line2 { font-size: 11px; color: #555; margin-top: 3px; }
    .inst-line3 { font-size: 10px; color: #777; margin-top: 2px; }
    .header-seal { width: 56px; height: 56px; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; }
    .header-seal img { max-height: 56px; max-width: 56px; opacity: 0.9; }

    /* ─── Certificate Title ─────────────────── */
    .cert-title {
        text-align: center;
        font-size: 19px;
        font-weight: bold;
        letter-spacing: 4px;
        text-transform: uppercase;
        color: {{ $color }};
        margin: 0 0 8px;
    }
    .cert-title-underline {
        text-align: center;
        margin-bottom: 26px;
    }
    .cert-title-underline span {
        display: inline-block;
        width: 90px;
        border-bottom: 2px solid {{ $color }};
    }

    .meta-row { display: flex; justify-content: space-between; font-size: 10.5px; color: #666; margin-bottom: 22px; text-transform: uppercase; letter-spacing: 0.3px; }
    .meta-row strong { color: #333; text-transform: none; letter-spacing: 0; }

    /* ─── Body ───────────────────────────────── */
    .body-content { line-height: 2.05; font-size: 13.5px; text-align: justify; }
    .body-content p { margin-bottom: 15px; }

    /* ─── Signature Row ──────────────────────── */
    .sig-row { display: flex; justify-content: space-between; margin-top: 60px; }
    .sig-left { width: 40%; }
    .sig-right { width: 40%; text-align: right; }
    .sig-block img { max-height: 46px; max-width: 150px; margin-bottom: 4px; }
    .sig-left img { display: block; }
    .sig-right img { display: block; margin-left: auto; }
    .sig-line { border-top: 1px solid #555; padding-top: 6px; font-size: 12.5px; font-weight: bold; color: #222; }
    .sig-desig { font-size: 10.5px; color: #666; margin-top: 2px; text-transform: uppercase; letter-spacing: 1px; }
    .sig-right .sig-line { text-align: right; }
    .sig-right .sig-desig { text-align: right; }

    .seal-center { width: 20%; text-align: center; }
    .seal-placeholder {
        width: 62px; height: 62px; border: 1.5px dashed {{ $color }}88; border-radius: 50%;
        margin: 0 auto; display: flex; align-items: center; justify-content: center;
        font-size: 7.5px; color: {{ $color }}99; letter-spacing: 1px; text-transform: uppercase; text-align: center;
    }

    .footer-note { text-align: center; margin-top: 40px; font-size: 8.5px; color: #999; letter-spacing: 0.2px; }
</style>
</head>
<body>
<div class="page">
<div class="inner">

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
        <span>Certificate No.: <strong>{{ $certificate->certificate_number }}</strong></span>
        <span>Date of Issue: <strong>{{ \Carbon\Carbon::parse($certificate->issued_at)->format('d F, Y') }}</strong></span>
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
        <div class="seal-center">
            @if($settings->seal_image)
                <img src="{{ public_path('storage/' . $settings->seal_image) }}" alt="Seal" style="max-height:60px;max-width:60px;opacity:.9;">
            @else
                <div class="seal-placeholder">Official<br>Seal</div>
            @endif
        </div>
        <div class="sig-right">
            @if($settings->registrar_signature)
                <img src="{{ public_path('storage/' . $settings->registrar_signature) }}" alt="Signature">
            @endif
            <div class="sig-line">{{ $settings->registrar_name ?: '________________________' }}</div>
            <div class="sig-desig">{{ $settings->registrar_designation ?: 'Registrar' }}</div>
        </div>
    </div>

    <div class="footer-note">This is a computer-generated certificate and does not require a physical signature to be valid.</div>
</div>
</div>
</body>
</html>
