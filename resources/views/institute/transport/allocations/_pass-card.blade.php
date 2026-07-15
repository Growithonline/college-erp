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
    $addressLine = \Illuminate\Support\Str::limit($addressLine, 32);

    // Every value below is capped server-side, the same way $instituteName is above —
    // not with CSS. `white-space: nowrap` on these cells does stop a long value from
    // wrapping to a second line (which is what actually blows the fixed-height card
    // onto a phantom second page), but a value still wider than its column doesn't get
    // cropped there: this dompdf build doesn't clip nowrap text at the box edge, it
    // just paints the overflow into the neighbouring cell instead (confirmed by
    // rendering the card with deliberately long route/stop/driver names — the text
    // visibly bled into the QR column rather than being cut off). Str::limit bounds
    // the painted width itself so there's nothing left to bleed.
    $studentName = \Illuminate\Support\Str::limit($allocation->student?->name ?? '—', 20);
    $studentUid = \Illuminate\Support\Str::limit($allocation->student?->roll_no ?? $allocation->student?->student_uid ?? '—', 22);
    $routeName = \Illuminate\Support\Str::limit($allocation->route?->name ?? '—', 14);
    $stopName = \Illuminate\Support\Str::limit($allocation->stop?->stop_name ?? '—', 14);
    $vehicleNo = \Illuminate\Support\Str::limit($allocation->vehicle?->vehicle_no ?? '—', 16);
    $driverName = \Illuminate\Support\Str::limit($allocation->driver?->name ?? '—', 16);

    // Validity shown in the footer: an explicit end date is the clearest "expires on"
    // signal, falling back to the academic session label when an allocation has no
    // end date yet (open-ended transport allocations are common mid-session).
    $validityLabel = 'Valid';
    $validityValue = null;
    if ($allocation->end_date) {
        $validityLabel = 'Valid Upto';
        $validityValue = $allocation->end_date->format('d M Y');
    } elseif ($allocation->session?->academic_year) {
        $validityLabel = 'Valid For';
        $validityValue = $allocation->session->academic_year;
    }
@endphp
<div class="card">
    <table class="card-table" cellpadding="0" cellspacing="0">
        <colgroup>
            <col style="width: 46pt;">
            <col>
            <col style="width: 48pt;">
        </colgroup>
        <tr class="header-row">
            <td class="header-fill-cell"></td>
            <td class="header-name-cell">
                <div class="pass-kicker">Transport Pass</div>
                <div class="inst-name">{{ $instituteName }}</div>
                @if($addressLine)
                    <div class="inst-address">{{ $addressLine }}</div>
                @endif
            </td>
            <td class="header-logo-cell">
                <div class="seal-ring">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo" class="logo-img">
                    @else
                        <span class="logo-fallback">{{ $initials }}</span>
                    @endif
                </div>
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
                <div class="student-name">{{ $studentName }}</div>
                <div class="student-uid">{{ $studentUid }}</div>
                <table class="info-rows" cellpadding="0" cellspacing="0">
                    <tr><td class="rlabel">Route</td><td class="rvalue">{{ $routeName }}</td></tr>
                    <tr><td class="rlabel">Stop</td><td class="rvalue">{{ $stopName }}</td></tr>
                    <tr><td class="rlabel">Vehicle</td><td class="rvalue">{{ $vehicleNo }}</td></tr>
                    <tr><td class="rlabel">Driver</td><td class="rvalue">{{ $driverName }}</td></tr>
                </table>
            </td>
            <td class="qr-cell">
                <div class="qr-frame"><img src="{{ $qrSvg }}" alt="QR"></div>
                <div class="qr-caption">Scan for status</div>
            </td>
        </tr>
        <tr class="footer-row">
            <td colspan="3">
                <table class="footer-table" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="footer-left">
                            <div class="footer-value">{{ $validityValue ?? 'Ongoing' }}</div>
                            <div class="footer-rule"></div>
                            <div class="footer-label">{{ $validityLabel }}</div>
                        </td>
                        <td class="footer-right">
                            <div class="footer-value">&nbsp;</div>
                            <div class="footer-rule"></div>
                            <div class="footer-label">Signatory</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
