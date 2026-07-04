<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @php $color = $settings->primary_color ?? '#1e3a5f'; @endphp
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "DejaVu Serif", serif; font-size: 13px; color: #1a1a1a; background: #fff; }

    .page {
        width: 100%;
        min-height: 271mm;
        padding: 10mm;
        position: relative;
    }

    /* ─── Ornamental double frame ─────────────── */
    .frame-outer {
        border: 2px solid {{ $color }};
        padding: 6px;
        min-height: 261mm;
        position: relative;
    }
    .frame-inner {
        border: 1px solid {{ $color }};
        padding: 16mm 18mm;
        min-height: 255mm;
        position: relative;
    }
    .corner {
        position: absolute;
        width: 16px;
        height: 16px;
        border: 2px solid {{ $color }}cc;
    }
    .corner-tl { top: -3px; left: -3px; border-right: none; border-bottom: none; }
    .corner-tr { top: -3px; right: -3px; border-left: none; border-bottom: none; }
    .corner-bl { bottom: -3px; left: -3px; border-right: none; border-top: none; }
    .corner-br { bottom: -3px; right: -3px; border-left: none; border-top: none; }

    /* ─── Watermark ───────────────────────────── */
    .watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 300px;
        height: 300px;
        margin-top: -150px;
        margin-left: -150px;
        opacity: 0.05;
        text-align: center;
        z-index: 0;
    }
    .watermark img { width: 100%; height: 100%; object-fit: contain; }
    .content { position: relative; z-index: 1; }

    /* ─── Header ─────────────────────────────── */
    .header { text-align: center; margin-bottom: 6px; }
    .logo-area { margin-bottom: 8px; }
    .logo-area img { max-height: 68px; max-width: 150px; }
    .inst-name  { font-size: 22px; font-weight: bold; color: {{ $color }}; letter-spacing: 1.5px; text-transform: uppercase; }
    .inst-line2 { font-size: 12px; color: #555; margin-top: 4px; letter-spacing: 0.3px; }
    .inst-line3 { font-size: 11px; color: #777; margin-top: 1px; }

    .divider-orn { text-align: center; margin: 14px 0 18px; color: {{ $color }}; }
    .divider-orn .line { display: inline-block; vertical-align: middle; width: 150px; height: 1px; background: {{ $color }}; }
    .divider-orn .diamond { display: inline-block; vertical-align: middle; width: 7px; height: 7px; background: {{ $color }}; transform: rotate(45deg); margin: 0 10px; }

    /* ─── Certificate Title ─────────────────── */
    .cert-title {
        text-align: center;
        font-size: 21px;
        font-weight: bold;
        letter-spacing: 5px;
        text-transform: uppercase;
        color: {{ $color }};
        margin: 0 0 22px;
    }

    /* ─── Cert Number & Date ─────────────────── */
    .meta-row { display: flex; justify-content: space-between; font-size: 10.5px; color: #666; margin-bottom: 22px; letter-spacing: 0.3px; text-transform: uppercase; }
    .meta-row strong { color: #333; text-transform: none; letter-spacing: 0; }

    /* ─── Body ───────────────────────────────── */
    .body-content { line-height: 2.1; font-size: 14px; text-align: justify; padding: 0 6px; }
    .body-content p { margin-bottom: 14px; }
    .body-content strong { color: #1a1a1a; }

    /* ─── Signature Row ──────────────────────── */
    .sig-row { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 55px; }
    .sig-block { text-align: center; width: 38%; }
    .sig-block .sig-img-wrap { height: 46px; margin-bottom: 4px; display: flex; align-items: flex-end; justify-content: center; }
    .sig-block img { max-height: 46px; max-width: 160px; }
    .sig-line { border-top: 1px solid #555; padding-top: 6px; font-size: 12.5px; font-weight: bold; color: #222; letter-spacing: 0.4px; }
    .sig-desig { font-size: 10.5px; color: #666; margin-top: 2px; text-transform: uppercase; letter-spacing: 1px; }

    /* ─── Seal ───────────────────────────────── */
    .seal-area { text-align: center; width: 22%; }
    .seal-area img { max-height: 78px; max-width: 78px; opacity: 0.9; }
    .seal-placeholder {
        width: 74px; height: 74px; border: 1.5px dashed {{ $color }}88; border-radius: 50%;
        margin: 0 auto; display: flex; align-items: center; justify-content: center;
        font-size: 8px; color: {{ $color }}99; letter-spacing: 1px; text-transform: uppercase; text-align: center;
    }

    /* ─── Footer ──────────────────────────────── */
    .footer-note { text-align: center; margin-top: 32px; font-size: 9px; color: #999; letter-spacing: 0.3px; }
</style>
</head>
<body>
<div class="page">
<div class="frame-outer">
<div class="frame-inner">
    <div class="corner corner-tl"></div>
    <div class="corner corner-tr"></div>
    <div class="corner corner-bl"></div>
    <div class="corner corner-br"></div>

    @if($settings->logo)
    <div class="watermark"><img src="{{ public_path('storage/' . $settings->logo) }}" alt=""></div>
    @endif

    <div class="content">
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

        <div class="divider-orn"><span class="line"></span><span class="diamond"></span><span class="line"></span></div>

        <div class="cert-title">{{ $type->name }}</div>

        <div class="meta-row">
            <span>Certificate No.: <strong>{{ $certificate->certificate_number }}</strong></span>
            <span>Date of Issue: <strong>{{ \Carbon\Carbon::parse($certificate->issued_at)->format('d/m/Y') }}</strong></span>
        </div>

        <div class="body-content">
            {!! $bodyHtml !!}
        </div>

        <div class="sig-row">
            <div class="sig-block">
                <div class="sig-img-wrap">
                    @if($settings->principal_signature)
                        <img src="{{ public_path('storage/' . $settings->principal_signature) }}" alt="Signature">
                    @endif
                </div>
                <div class="sig-line">{{ $settings->principal_name ?: '________________________' }}</div>
                <div class="sig-desig">{{ $settings->principal_designation ?: 'Principal' }}</div>
            </div>

            <div class="seal-area">
                @if($settings->seal_image)
                    <img src="{{ public_path('storage/' . $settings->seal_image) }}" alt="Seal">
                @else
                    <div class="seal-placeholder">Official<br>Seal</div>
                @endif
            </div>

            <div class="sig-block">
                <div class="sig-img-wrap">
                    @if($settings->registrar_signature)
                        <img src="{{ public_path('storage/' . $settings->registrar_signature) }}" alt="Signature">
                    @endif
                </div>
                <div class="sig-line">{{ $settings->registrar_name ?: '________________________' }}</div>
                <div class="sig-desig">{{ $settings->registrar_designation ?: 'Registrar' }}</div>
            </div>
        </div>

        <div class="footer-note">This is a computer-generated certificate and does not require a physical signature to be valid.</div>
    </div>
</div>
</div>
</div>
</body>
</html>
