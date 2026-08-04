<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #000; margin: 0; }
    h1 { text-align:center; font-size:16px; margin:0 0 2px; color:#000; }
    h2 { text-align:center; font-size:12px; margin:0 0 10px; color:#000; font-weight:normal; }
    .meta { width:100%; border-collapse:collapse; margin-bottom:14px; }
    .meta td { padding:4px 6px; font-size:9.5px; border:1px solid #ddd; color:#000; }
    .meta td.label { background:#f1f5f9; font-weight:bold; width:110px; }
    .doc-section { page-break-inside:avoid; margin-bottom:18px; }
    .doc-section + .doc-section { page-break-before:always; }
    .doc-title { font-size:13px; font-weight:bold; color:#000; border-bottom:2px solid #1a1a2e; padding-bottom:4px; margin-bottom:8px; }
    .doc-title .ver { font-weight:normal; color:#555; font-size:10px; }
    .doc-content { font-size:9.5px; line-height:1.55; color:#111; }
    .doc-content h2 { text-align:left; font-size:10.5px; font-weight:bold; margin:10px 0 4px; }
    .doc-content h2:first-child { margin-top:0; }
    .doc-content p { margin:0 0 6px; }
    .doc-content ul { margin:0 0 6px; padding-left:16px; }
    .signature { width:100%; border-collapse:collapse; margin-top:10px; }
    .signature td { padding:6px 8px; border:1px solid #ccc; font-size:9px; color:#000; }
    .signature td.label { background:#f0fdf4; font-weight:bold; width:130px; }
    .signature-note { font-size:8.5px; color:#555; margin-top:4px; }
</style>
</head>
<body>

<h1>{{ $institute->name }}</h1>
<h2>Policy Consent Certificate</h2>

<table class="meta">
    <tr>
        <td class="label">Institute UID</td><td>{{ $institute->institute_uid }}</td>
        <td class="label">Certificate Generated</td><td>{{ now()->format('d M Y, h:i A') }}</td>
    </tr>
</table>

@foreach($documents as $type => $meta)
    <div class="doc-section">
        <div class="doc-title">
            {{ $meta['label'] }} <span class="ver">— version {{ $meta['acceptance']->version }}</span>
        </div>
        <div class="doc-content">
            @include($meta['view'], ['institute' => $institute])
        </div>

        <table class="signature">
            <tr>
                <td class="label">Digitally Accepted By</td>
                <td>{{ $meta['acceptance']->acceptedBy?->name ?? '—' }} ({{ $meta['acceptance']->acceptedBy?->email ?? '—' }})</td>
            </tr>
            <tr>
                <td class="label">Accepted On</td>
                <td>{{ $meta['acceptance']->accepted_at?->format('d M Y, h:i:s A') }}</td>
            </tr>
            <tr>
                <td class="label">IP Address</td>
                <td>{{ $meta['acceptance']->ip_address ?? '—' }}</td>
            </tr>
        </table>
        <p class="signature-note">
            This acceptance was recorded electronically when the above user clicked "Accept &amp; Continue" on the
            platform's consent screen, after scrolling through the full document text shown above.
        </p>
    </div>
@endforeach

</body>
</html>
