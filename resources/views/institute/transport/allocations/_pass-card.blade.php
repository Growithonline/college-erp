{{-- Shared by pass.blade.php (single) and pass-bulk.blade.php (multi-page). Expects
     $institute, $allocation, $qrSvg in scope. --}}
@php
    // Prefer the storage-disk path, fall back to a public path, and fall back further
    // to institute initials if no logo file actually exists. Also requires the gd
    // extension: dompdf's PDF adapter embeds JPEG/PNG via imagecreatefromjpeg()/
    // imagecreatefrompng(), which are gd functions â€” without gd it fails silently and
    // prints the <img>'s alt text ("Logo") in the PDF instead of the picture. Skipping
    // straight to the initials fallback when gd isn't loaded avoids ever showing that
    // broken-looking placeholder.
    //
    // The path itself is a direct public_path() filesystem path, not asset() â€” an
    // asset() URL needs either a live HTTP round-trip (isRemoteEnabled) or dompdf's own
    // chroot-based local resolution to line up with APP_URL, and in production that
    // logo was rendering as the same broken "Logo" alt-text fallback while the student
    // photo below (already loaded via a direct public_path(), never asset()) rendered
    // fine. Matching the photo's approach fixed it.
    $browserPreview = $browserPreview ?? false;
    $logoUrl = null;
    if (!empty($institute->image) && extension_loaded('gd')) {
        if (file_exists(public_path('storage/' . $institute->image))) {
            $logoUrl = public_path('storage/' . $institute->image);
        } elseif (file_exists(public_path($institute->image))) {
            $logoUrl = public_path($institute->image);
        }
    }
    if ($browserPreview && !empty($institute->image)) {
        $storedImage = ltrim((string) $institute->image, '/');
        $storageImage = str_starts_with($storedImage, 'storage/') ? substr($storedImage, 8) : $storedImage;
        if (file_exists(public_path('storage/' . $storageImage))) {
            $logoUrl = asset('storage/' . $storageImage);
        } elseif (file_exists(public_path($storedImage))) {
            $logoUrl = asset($storedImage);
        } else {
            $logoUrl = null;
        }
    }
    $initials = strtoupper(substr($institute->short_name ?: $institute->name, 0, 2));
    $addressLine = implode(', ', array_filter([$institute->city, $institute->state]));

    // Word-safe truncation for every value below that's capped server-side rather than
    // by CSS: Str::limit() cuts at a raw character count, which sliced straight through
    // the middle of a word ("1st Year" â†’ "1st...", "Kapil Muni" â†’ "Kapil M..."). Backing
    // off to the last whole space before the limit keeps every truncated value ending on
    // a full word instead. `white-space: nowrap` in the stylesheet is a separate,
    // necessary guard alongside this â€” it stops a long value from wrapping to a second
    // line (which is what actually blows the fixed-height card onto a phantom second
    // page) â€” but this dompdf build doesn't clip nowrap text at the box edge, it just
    // paints the overflow into the neighbouring cell instead (confirmed by rendering the
    // card with deliberately long values â€” the text visibly bled into the QR column
    // rather than being cut off). $wordSafeLimit bounds the painted width itself so
    // there's nothing left to bleed.
    $wordSafeLimit = function (?string $value, int $limit): string {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if (mb_strlen($value) <= $limit) {
            return $value;
        }
        $cut = mb_substr($value, 0, $limit);
        $lastSpace = mb_strrpos($cut, ' ');
        if ($lastSpace !== false && $lastSpace > 0) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }
        return rtrim($cut, " -") . '...';
    };

    // Hard-capped, not left to wrap naturally: this header row has a fixed height
    // budget on a fixed-size card, and an institute name long enough to wrap past two
    // lines (real coaching-institute names run long) pushed the whole card onto a
    // phantom second page. Truncating server-side keeps the header height predictable
    // regardless of how long the name actually is.
    $instituteName = $wordSafeLimit($institute->name, 34);
    $addressLine = $wordSafeLimit($addressLine, 32);

    $studentName = $wordSafeLimit($allocation->student?->name ?? 'â€”', 22);
    $studentPhotoUrl = null;
    if (!empty($allocation->student?->photo)) {
        $studentPhotoUrl = $browserPreview
            ? asset('storage/' . ltrim((string) $allocation->student->photo, '/'))
            : public_path('storage/' . $allocation->student->photo);
    }

    $studentUid = $wordSafeLimit($allocation->student?->roll_no ?? $allocation->student?->student_uid ?? 'â€”', 24);

    // Course + year, and route + stop / vehicle + driver, are each folded into a single
    // row (rather than one row per field) â€” the card only has room for five info rows
    // before the text starts crowding the QR column, and pairing values that are
    // naturally read together (a route always implies a stop; a vehicle always implies
    // its driver) costs nothing in clarity while halving the row count of the old
    // one-field-per-row layout.
    $courseYear = trim(implode(' - ', array_filter([
        $allocation->student?->stream?->course?->name,
        $allocation->student?->coursePart?->year_label,
    ])));
    $courseYear = $courseYear !== '' ? $wordSafeLimit($courseYear, 28) : null;
    $fatherName = $allocation->student?->father_name;
    $fatherName = $fatherName ? $wordSafeLimit($fatherName, 22) : null;
    $mobile = $allocation->student?->mobile;
    $mobile = $mobile ? $wordSafeLimit($mobile, 16) : null;
    $routeStop = trim(implode(' - ', array_filter([$allocation->route?->name, $allocation->stop?->stop_name])));
    $routeStop = $wordSafeLimit($routeStop !== '' ? $routeStop : 'â€”', 22);
    $vehicleNumber = $wordSafeLimit($allocation->vehicle?->vehicle_no ?: 'â€”', 20);
    $driverName = $allocation->driver?->name ? $wordSafeLimit($allocation->driver->name, 28) : null;

    // Route-strip labels â€” the reference design's start/end transit graphic. Start is
    // the institute's own city (the same locality already printed in the header, since
    // that's where the route effectively originates), end is the student's drop-off
    // stop, falling back to the route name if no stop is recorded. Capped tighter (12
    // chars) than the info-rows values above â€” each label sits in a fixed 52pt column
    // (see .route-label in the stylesheet) with no room to spare next to its dot.
    $routeStripStart = $wordSafeLimit($institute->city ?: ($institute->short_name ?: 'Institute'), 12);
    $routeStripEnd = $wordSafeLimit($allocation->stop?->stop_name ?: ($allocation->route?->name ?? 'â€”'), 12);

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
    <table class="brand-table" cellpadding="0" cellspacing="0">
        <colgroup><col style="width: 185pt;"><col style="width: 46pt;"></colgroup>
        <tr>
            <td class="brand-copy">
                <div class="brand-kicker">Official Student Transport Pass</div>
                <div class="brand-name">{{ $instituteName }}</div>
                @if($addressLine)<div class="brand-address">{{ $addressLine }}</div>@endif
            </td>
            <td class="brand-mark"><span class="seal-ring">
                @if($logoUrl)<img src="{{ $logoUrl }}" alt="Logo" class="logo-img">
                @else<span class="logo-fallback">{{ $initials }}</span>@endif
            </span></td>
        </tr>
    </table>

    <table class="identity-table" cellpadding="0" cellspacing="0">
        <colgroup><col style="width: 43pt;"><col style="width: 145pt;"><col style="width: 43pt;"></colgroup>
        <tr>
            <td class="portrait-cell">
                <table class="photo-frame" cellpadding="0" cellspacing="0"><tr><td>
                    @if($studentPhotoUrl)<img src="{{ $studentPhotoUrl }}" alt="Student photo">
                    @else No Photo @endif
                </td></tr></table>
            </td>
            <td class="holder-cell">
                <div class="holder-name">{{ $studentName }}</div>
                <div class="holder-id">Enrollment No. {{ $studentUid }}</div>
                <table class="detail-table" cellpadding="0" cellspacing="0">
                    @if($courseYear)<tr><td class="label">Course</td><td class="value">{{ $courseYear }}</td></tr>@endif
                    @if($fatherName)<tr><td class="label">Father</td><td class="value">{{ $fatherName }}</td></tr>@endif
                    @if($mobile)<tr><td class="label">Mobile</td><td class="value">{{ $mobile }}</td></tr>@endif
                    <tr><td class="label">Route</td><td class="value">{{ $routeStop }}</td></tr>
                    <tr><td class="label">Vehicle</td><td class="value">{{ $vehicleNumber }}</td></tr>
                    @if($driverName)<tr><td class="label">Driver</td><td class="value">{{ $driverName }}</td></tr>@endif
                </table>
            </td>
            <td class="qr-cell"><span class="qr-frame"><img src="{{ $qrSvg }}" alt="Pass QR"></span><div class="qr-caption">Scan to verify</div></td>
        </tr>
    </table>

    <div class="footer-spacer"></div>
    <table class="footer-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="footer-valid"><div class="footer-value">{{ $validityValue ?? 'Ongoing' }}</div><div class="footer-label">Pass validity</div></td>
            <td class="footer-authority"><div class="footer-value">&nbsp;</div><div class="footer-label">Authorised by</div></td>
        </tr>
    </table>
</div>