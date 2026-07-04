<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @php $color = $settings->primary_color ?? '#1e3a5f'; @endphp
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 13px; color: #1a1a1a; background: #fff; }

    .page { width: 100%; min-height: 271mm; border: 1px solid #e2e2e2; position: relative; }

    /* ─── Colored Header Band ────────────────── */
    .header-band {
        background: {{ $color }};
        color: #fff;
        padding: 20px 28px;
        display: flex;
        align-items: center;
        gap: 18px;
    }
    .header-band .logo img { max-height: 62px; max-width: 130px; background: rgba(255,255,255,.95); padding: 5px; border-radius: 4px; }
    .header-band .inst-info { flex: 1; }
    .inst-name  { font-size: 20px; font-weight: bold; letter-spacing: 0.4px; }
    .inst-line2 { font-size: 11.5px; opacity: .88; margin-top: 3px; }
    .inst-line3 { font-size: 10.5px; opacity: .72; margin-top: 1px; }
    .header-band .seal-mini { width: 58px; height: 58px; border-radius: 50%; background: rgba(255,255,255,.95); display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .header-band .seal-mini img { max-height: 50px; max-width: 50px; }

    /* ─── Accent strip ───────────────────────── */
    .accent-strip { height: 6px; background: linear-gradient(90deg, {{ $color }}, {{ $color }}66, {{ $color }}); }

    /* ─── Content area ───────────────────────── */
    .content { padding: 26px 34px 20px; }

    /* ─── Certificate Title ─────────────────── */
    .cert-title-wrap { text-align: center; margin: 6px 0 20px; }
    .cert-title {
        display: inline-block;
        font-size: 18px;
        font-weight: bold;
        letter-spacing: 3.5px;
        text-transform: uppercase;
        color: {{ $color }};
        padding-bottom: 8px;
        border-bottom: 3px solid {{ $color }};
    }

    .meta-row { display: flex; justify-content: space-between; font-size: 10.5px; color: #666; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.3px; }
    .meta-row strong { color: #333; text-transform: none; letter-spacing: 0; }

    /* ─── Body ───────────────────────────────── */
    .body-content { line-height: 2; font-size: 13.5px; text-align: justify; }
    .body-content p { margin-bottom: 12px; }

    /* ─── Signature Row ──────────────────────── */
    .sig-row { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 48px; padding-top: 22px; border-top: 1px solid #ececec; }
    .sig-block { text-align: center; width: 38%; }
    .sig-block .sig-img-wrap { height: 44px; margin-bottom: 4px; display: flex; align-items: flex-end; justify-content: center; }
    .sig-block img { max-height: 44px; max-width: 160px; }
    .sig-line { border-top: 1px solid #555; padding-top: 6px; font-size: 12.5px; font-weight: bold; }
    .sig-desig { font-size: 10.5px; color: #666; margin-top: 2px; text-transform: uppercase; letter-spacing: 1px; }

    .seal-area { text-align: center; width: 22%; }
    .seal-area img { max-height: 74px; max-width: 74px; opacity: 0.9; }
    .seal-placeholder {
        width: 68px; height: 68px; border: 1.5px dashed {{ $color }}88; border-radius: 50%;
        margin: 0 auto; display: flex; align-items: center; justify-content: center;
        font-size: 8px; color: {{ $color }}99; letter-spacing: 1px; text-transform: uppercase; text-align: center;
    }

    /* ─── Footer band ────────────────────────── */
    .footer-band {
        background: {{ $color }}12;
        border-top: 2px solid {{ $color }}44;
        padding: 10px 28px;
        font-size: 10px;
        color: #666;
        text-align: center;
        margin-top: 26px;
    }
    .footer-note { text-align: center; margin-top: 10px; font-size: 8.5px; color: #999; }
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
            <div class="seal-mini"><img src="{{ public_path('storage/' . $settings->seal_image) }}" alt="Seal"></div>
        @endif
    </div>
    <div class="accent-strip"></div>

    <div class="content">
        <div class="cert-title-wrap"><span class="cert-title">{{ $type->name }}</span></div>

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
                @if(!$settings->seal_image)
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
    </div>

    <div class="footer-band">
        {{ $settings->header_line1 }}
        @if($settings->header_line2) &nbsp;•&nbsp; {{ $settings->header_line2 }} @endif
    </div>
    <div class="footer-note">This is a computer-generated certificate and does not require a physical signature to be valid.</div>
</div>
</body>
</html>
