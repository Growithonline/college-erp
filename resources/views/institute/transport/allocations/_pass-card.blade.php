{{-- Shared by pass.blade.php (single) and pass-bulk.blade.php (multi-page). Expects
     $institute, $allocation, $qrSvg in scope. --}}
@php
    // Prefer the storage-disk path, fall back to a public path, and fall back further
    // to institute initials if no logo file actually exists. Also requires the gd
    // extension: dompdf's PDF adapter embeds JPEG/PNG via imagecreatefromjpeg()/
    // imagecreatefrompng(), which are gd functions — without gd it fails silently and
    // prints the <img>'s alt text ("Logo") in the PDF instead of the picture. Skipping
    // straight to the initials fallback when gd isn't loaded avoids ever showing that
    // broken-looking placeholder.
    //
    // The path itself is a direct public_path() filesystem path, not asset() — an
    // asset() URL needs either a live HTTP round-trip (isRemoteEnabled) or dompdf's own
    // chroot-based local resolution to line up with APP_URL, and in production that
    // logo was rendering as the same broken "Logo" alt-text fallback while the student
    // photo below (already loaded via a direct public_path(), never asset()) rendered
    // fine. Matching the photo's approach fixed it.
    $logoUrl = null;
    if (!empty($institute->image) && extension_loaded('gd')) {
        if (file_exists(public_path('storage/' . $institute->image))) {
            $logoUrl = public_path('storage/' . $institute->image);
        } elseif (file_exists(public_path($institute->image))) {
            $logoUrl = public_path($institute->image);
        }
    }
    $initials = strtoupper(substr($institute->short_name ?: $institute->name, 0, 2));
    $addressLine = implode(', ', array_filter([$institute->city, $institute->state]));

    // Word-safe truncation for every value below that's capped server-side rather than
    // by CSS: Str::limit() cuts at a raw character count, which sliced straight through
    // the middle of a word ("1st Year" → "1st...", "Kapil Muni" → "Kapil M..."). Backing
    // off to the last whole space before the limit keeps every truncated value ending on
    // a full word instead. `white-space: nowrap` in the stylesheet is a separate,
    // necessary guard alongside this — it stops a long value from wrapping to a second
    // line (which is what actually blows the fixed-height card onto a phantom second
    // page) — but this dompdf build doesn't clip nowrap text at the box edge, it just
    // paints the overflow into the neighbouring cell instead (confirmed by rendering the
    // card with deliberately long values — the text visibly bled into the QR column
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
        return rtrim($cut, " ·") . '…';
    };

    // Hard-capped, not left to wrap naturally: this header row has a fixed height
    // budget on a fixed-size card, and an institute name long enough to wrap past two
    // lines (real coaching-institute names run long) pushed the whole card onto a
    // phantom second page. Truncating server-side keeps the header height predictable
    // regardless of how long the name actually is. Raised 34 -> 44 / 32 -> 42: the
    // header's name cell is now pinned to an explicit 191pt (see .header-name-cell)
    // instead of being handed roughly two-thirds of an evenly-split table, so there is
    // measurably more painted width to spend before anything reaches the card's edge.
    $instituteName = $wordSafeLimit($institute->name, 44);
    $addressLine = $wordSafeLimit($addressLine, 42);

    $studentName = $wordSafeLimit($allocation->student?->name ?? '—', 22);
    $studentUid = $wordSafeLimit($allocation->student?->roll_no ?? $allocation->student?->student_uid ?? '—', 24);

    // Course + year, and route + stop / vehicle + driver, are each folded into a single
    // row (rather than one row per field) — the card only has room for five info rows
    // before the text starts crowding the QR column, and pairing values that are
    // naturally read together (a route always implies a stop; a vehicle always implies
    // its driver) costs nothing in clarity while halving the row count of the old
    // one-field-per-row layout.
    //
    // Caps raised 22 -> 28. These are painted-width guards, not business rules, and the
    // width they were guarding changed: pinning column 1 to the photo's real 42pt (see
    // .card-table in the stylesheet) handed the info column ~33pt it was previously
    // losing to a dead gap, so a value cell now paints ~103pt instead of ~70pt. At
    // 6.5pt Helvetica Bold that is ~28 characters before the text would reach the QR
    // frame. 28 is what stops "Bachelor of Arts" and "UP-58-T-7565 · Kapil Yadav" from
    // rendering with an ellipsis on a pass a conductor has to actually read.
    $courseYear = trim(implode(' · ', array_filter([
        $allocation->student?->stream?->course?->name,
        $allocation->student?->coursePart?->year_label,
    ])));
    $courseYear = $courseYear !== '' ? $wordSafeLimit($courseYear, 28) : null;
    $fatherName = $allocation->student?->father_name;
    $fatherName = $fatherName ? $wordSafeLimit($fatherName, 28) : null;
    $mobile = $allocation->student?->mobile;
    $mobile = $mobile ? $wordSafeLimit($mobile, 16) : null;
    $routeStop = trim(implode(' · ', array_filter([$allocation->route?->name, $allocation->stop?->stop_name])));
    $routeStop = $wordSafeLimit($routeStop !== '' ? $routeStop : '—', 28);
    $vehicleDriver = trim(implode(' · ', array_filter([$allocation->vehicle?->vehicle_no, $allocation->driver?->name])));
    $vehicleDriver = $wordSafeLimit($vehicleDriver !== '' ? $vehicleDriver : '—', 28);

    // Route-strip labels — the reference design's start/end transit graphic. Start is
    // the institute's own city (the same locality already printed in the header, since
    // that's where the route effectively originates), end is the student's drop-off
    // stop, falling back to the route name if no stop is recorded. Still capped tighter
    // (16 chars) than the info-rows values above — each label sits in a fixed 60pt cell
    // (see .route-label-start / .route-label-end) with the dashed line needing whatever
    // is left over. Raised 12 -> 16 alongside the cell widening from 52pt to 60pt.
    $routeStripStart = $wordSafeLimit($institute->city ?: ($institute->short_name ?: 'Institute'), 16);
    $routeStripEnd = $wordSafeLimit($allocation->stop?->stop_name ?: ($allocation->route?->name ?? '—'), 16);

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
        {{-- Kept as a statement of intent only — this dompdf build parses <colgroup> but
             never applies its widths, which is what left column 1 rendering at 225/3 =
             75pt (three equal columns) no matter what these values said, stranding the
             photo in a column nearly twice its own width. The widths that actually bind
             live on the <td> rules in _pass-card-style.blade.php, and specifically on
             this first row's cells, since `table-layout: fixed` resolves every column
             from row one. Totals here mirror those: 50 + 135 + 56 = 241pt. --}}
        <colgroup>
            <col style="width: 50pt;">
            <col style="width: 135pt;">
            <col style="width: 56pt;">
        </colgroup>
        <tr class="header-row">
            <td class="header-logo-cell">
                <div class="seal-ring">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo" class="logo-img">
                    @else
                        <span class="logo-fallback">{{ $initials }}</span>
                    @endif
                </div>
            </td>
            <td class="header-name-cell" colspan="2">
                <div class="pass-kicker">Transport Pass</div>
                <div class="inst-name">{{ $instituteName }}</div>
                @if($addressLine)
                    <div class="inst-address">{{ $addressLine }}</div>
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
                <div class="student-name">{{ $studentName }}</div>
                <div class="student-uid">{{ $studentUid }}</div>
                <table class="info-rows" cellpadding="0" cellspacing="0">
                    @if($courseYear)
                        <tr><td class="rlabel">Course</td><td class="rvalue">{{ $courseYear }}</td></tr>
                    @endif
                    @if($fatherName)
                        <tr><td class="rlabel">Father</td><td class="rvalue">{{ $fatherName }}</td></tr>
                    @endif
                    @if($mobile)
                        <tr><td class="rlabel">Mobile</td><td class="rvalue">{{ $mobile }}</td></tr>
                    @endif
                    <tr><td class="rlabel">Route</td><td class="rvalue">{{ $routeStop }}</td></tr>
                    <tr><td class="rlabel">Vehicle</td><td class="rvalue">{{ $vehicleDriver }}</td></tr>
                </table>
            </td>
            <td class="qr-cell">
                <div class="qr-frame"><img src="{{ $qrSvg }}" alt="QR"></div>
                <div class="qr-caption">Scan for status</div>
            </td>
        </tr>
        <tr class="route-row">
            <td colspan="3">
                <table class="route-table" cellpadding="0" cellspacing="0">
                    {{-- Same story as the outer table's colgroup: documentation, not
                         binding. Without widths on these tds the strip rendered as five
                         equal 45pt columns, which shrank the dashed line to a stub and
                         pushed both dots away from the labels they belong to. --}}
                    <colgroup>
                        <col style="width: 7pt;">
                        <col style="width: 60pt;">
                        <col style="width: 91pt;">
                        <col style="width: 60pt;">
                        <col style="width: 7pt;">
                    </colgroup>
                    <tr>
                        <td class="route-dot-cell"><span class="route-dot"></span></td>
                        <td class="route-label route-label-start">{{ $routeStripStart }}</td>
                        <td class="route-line-cell"><div class="route-line"></div></td>
                        <td class="route-label route-label-end">{{ $routeStripEnd }}</td>
                        <td class="route-dot-cell route-dot-cell--end"><span class="route-dot route-dot--end"></span></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr class="footer-row">
            <td colspan="3">
                <table class="footer-table" cellpadding="0" cellspacing="0">
                    <colgroup>
                        <col style="width: 120pt;">
                        <col style="width: 121pt;">
                    </colgroup>
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