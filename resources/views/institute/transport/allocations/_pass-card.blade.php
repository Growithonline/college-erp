{{-- Shared by pass.blade.php (single) and pass-bulk.blade.php (multi-page). Expects
     $institute, $allocation, $qrSvg in scope. --}}
@php
    // Same logo-resolution pattern used by the other institute PDF exports (student
    // list, fee export): prefer the storage-disk path, fall back to a public path,
    // and fall back further to institute initials if no logo file actually exists.
    // Also requires the gd extension: dompdf's PDF adapter embeds JPEG/PNG via
    // imagecreatefromjpeg()/imagecreatefrompng(), which are gd functions — without
    // gd it fails silently and prints the <img>'s alt text ("Logo") in the PDF
    // instead of the picture. Skipping straight to the initials fallback when gd
    // isn't loaded avoids ever showing that broken-looking placeholder.
    $logoUrl = null;
    if (!empty($institute->image) && extension_loaded('gd')) {
        if (file_exists(public_path('storage/' . $institute->image))) {
            $logoUrl = asset('storage/' . $institute->image);
        } elseif (file_exists(public_path($institute->image))) {
            $logoUrl = asset($institute->image);
        }
    }
    $initials = strtoupper(substr($institute->short_name ?: $institute->name, 0, 2));
    $addressLine = implode(', ', array_filter([$institute->city, $institute->state]));
    // Hard-capped, not left to wrap naturally: this header row has a fixed height
    // budget on a fixed-size card, and an institute name long enough to wrap past two
    // lines (real coaching-institute names run long) pushed the whole card onto a
    // phantom second page. Truncating server-side keeps the header height predictable
    // regardless of how long the name actually is.
    $instituteName = \Illuminate\Support\Str::limit($institute->name, 34);
@endphp
<div class="card">
    <table class="card-table" cellpadding="0" cellspacing="0">
        <colgroup>
            <col style="width: 48pt;">
            <col>
            <col style="width: 50pt;">
        </colgroup>
        <tr class="header-row">
            <td class="header-fill-cell"></td>
            <td class="header-name-cell">
                <div class="inst-name">{{ $instituteName }}</div>
                @if($addressLine)
                    <div class="inst-address">{{ $addressLine }}</div>
                @endif
            </td>
            <td class="header-logo-cell">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Logo" class="logo-img">
                @else
                    <div class="logo-fallback">{{ $initials }}</div>
                @endif
            </td>
        </tr>
        <tr class="body-row">
            <td class="photo-cell">
                <table class="photo-frame" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            @if($allocation->student?->photo)
                                <img src="{{ public_path('storage/' . $allocation->student->photo) }}">
                            @else
                                No Photo
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
            <td class="info-cell">
                <div class="student-name">{{ $allocation->student?->name ?? '—' }}</div>
                <div class="student-roll">{{ $allocation->student?->roll_no ?? $allocation->student?->student_uid ?? '—' }}</div>
                <table class="info-rows" cellpadding="0" cellspacing="0">
                    <colgroup>
                        <col style="width: 32pt;">
                        <col>
                    </colgroup>
                    <tr><td class="rlabel">Route</td><td class="rvalue">{{ $allocation->route?->name ?? '—' }}</td></tr>
                    <tr><td class="rlabel">Stop</td><td class="rvalue">{{ $allocation->stop?->stop_name ?? '—' }}</td></tr>
                    <tr><td class="rlabel">Vehicle</td><td class="rvalue">{{ $allocation->vehicle?->vehicle_no ?? '—' }}</td></tr>
                    <tr><td class="rlabel">Driver</td><td class="rvalue">{{ $allocation->driver?->name ?? '—' }}</td></tr>
                </table>
            </td>
            <td class="qr-cell">
                <div class="qr"><img src="{{ $qrSvg }}" alt="QR"></div>
                <div class="qr-caption">Scan for status</div>
            </td>
        </tr>
        <tr class="footer-row">
            <td colspan="3" class="footer-cell">Student Transport Pass</td>
        </tr>
    </table>
</div>
