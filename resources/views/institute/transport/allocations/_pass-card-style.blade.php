/* Table-based layout throughout — dompdf's float + absolute-positioning support is
   unreliable at this card's tiny page size and was causing content to spill onto a
   phantom second page with the QR code missing entirely. Tables are dompdf's most
   predictable layout primitive, so every region (header / photo / info / route / QR /
   footer) is a table cell instead. Also no CSS gradients or box-shadow here on purpose —
   this dompdf build (v3.1.5, checked directly in vendor/) has no renderer for either, so
   they'd just be silently dropped; visual depth comes from solid color blocks, borders
   and a thin accent ring instead. Colors below match the web reference design's navy /
   gold palette; the reference's Google Fonts (Inter, Barlow Condensed) are skipped on
   purpose too — this dompdf build can't reliably fetch/embed remote webfonts, so labels
   lean on Helvetica bold + uppercase + letter-spacing to approximate the condensed look
   instead. */
@page { margin: 0; }
* { box-sizing: border-box; margin: 0; padding: 0; font-family: Helvetica, Arial, sans-serif; }
body { width: 243pt; }
/* No explicit height here on purpose — the page canvas is already sized to exactly
   243x153pt in the controller's setPaper() call. Setting .card's height to the same
   153pt looked harmless but reliably pushed dompdf into treating the block as
   overflowing (even with only the header line inside it), spilling the rest of the
   card onto a phantom second page. Letting the card size to its own content keeps
   it comfortably inside the one real page. */
/* Zero padding, not the old `3pt 8pt` — that padding was insetting the navy header
   band away from the card's own edges, so it read as a floating blue rectangle sitting
   on the card rather than as the card's header (the web reference runs the band edge to
   edge). The 8pt horizontal inset every other region still needs now lives on those
   regions' own cells (see .photo-cell / .qr-cell / .route-row / and the header cells)
   so the header and footer bands can reach the border while the content stays inset. */
.card {
    width: 243pt;
    border: 1pt solid #e2e5ee;
    border-radius: 8pt;
    padding: 0;
}
/* Every width below is declared on a `<td>`, never on a <col>: this dompdf build parses
   <colgroup> but does not apply its widths at all. The evidence was visible in the
   rendered card — the colgroup asked for 42/135/48pt, but column 1 came out at 75pt
   (exactly 225/3, i.e. three equal columns, the fallback when no cell carries a width),
   which left a ~33pt dead gap between the photo and the student details and stranded the
   seal in the middle of the header. Only .qr-cell held its size, because that width was
   already on the td rather than the col. The <colgroup> in _pass-card.blade.php is kept
   as documentation of intent, but the td widths are what actually bind.
   Widths must be on the FIRST row's cells specifically — `table-layout: fixed` resolves
   the whole table's columns from row one, and row one here is the header (a logo cell +
   a colspan="2" name cell), which is exactly why nothing downstream was being honoured.
   Column budget is now 50 + 135 + 56 = 241pt (the card's full inner width, since .card
   no longer has padding of its own); the 8pt page inset lives in .photo-cell's left
   padding and .qr-cell's right padding, leaving the same 225pt of usable content. */
.card-table { width: 241pt; border-collapse: collapse; table-layout: fixed; }

/* Colored header row — institute seal, name and address, the same identity strip a
   real institutional ID card carries. Deliberately NOT a nested sub-table: an
   earlier version put a width:100% table inside a colspan="3" cell, and dompdf
   mis-resolved that nested percentage width against something far wider than the
   card, pushing content clean off the page. Reusing the outer table's own three
   columns for the header row too avoids nesting a table inside a colspan cell
   entirely. Solid navy, not the reference's gradient — this dompdf build silently
   drops linear-gradient backgrounds. */
.header-row td { background: #17307a; vertical-align: middle; }
/* Seal sits on the left of the header with the institute name right after it, matching
   the reference ID card's layout — so it takes the photo-cell's own column. The name
   cell spans the other two columns (colspan="2" in _pass-card.blade.php) instead of
   leaving the third one as an empty blue block next to it — that blank strip is what
   made the header look lopsided once the seal moved off the right side. Padding is set
   per-cell (not on the shared `.header-row td` rule above) so the logo and name can sit
   close together — a single shared padding value left a visible gap between the ring
   and the name text that didn't read as intentional.
   The explicit 50pt width is what actually pins column 1 for the entire table (see
   .card-table's comment) — without a width on this first-row cell dompdf fell back to
   equal thirds. 50pt = 8pt page inset + the 42pt the photo below occupies; `text-align:
   right` then collapses the ring's slack toward the name instead of splitting it evenly
   on both sides, so the ring sits against the card's own left breathing room rather than
   leaving dead space between itself and the name text. Radius is 7pt, not 5 — the band
   now meets the card's border, so it has to match the card's own 8pt radius minus its
   1pt border to sit flush in the corner instead of showing a pale notch. */
.header-logo-cell {
    width: 50pt;
    padding: 3.5pt 1pt 3.5pt 8pt;
    border-top-left-radius: 7pt;
    border-bottom-left-radius: 7pt;
    text-align: right;
}
/* The ring is what reads as an embossed "seal" rather than a flat logo swap — a plain
   circle looked like a placeholder icon rather than part of the card's identity. A
   styled div, not a nested table (see .header-row comment above for why nested
   tables in this header row are avoided). The image is sized to the ring's own inner
   diameter (ring width minus its border on both sides) so it fills the ring edge to
   edge — sized noticeably smaller than that, it read as floating in the middle of a
   mostly-white circle instead of as a seal. Gold border matches the reference's crest
   accent color. */
.seal-ring {
    display: inline-block;
    width: 21pt;
    height: 21pt;
    background: #ffffff;
    border: 1.25pt solid #d9a441;
    border-radius: 50%;
    text-align: center;
    line-height: 18.5pt;
    overflow: hidden;
}
.logo-fallback { color: #17307a; font-size: 7pt; font-weight: bold; }
.logo-img { width: 18.5pt; height: 18.5pt; border-radius: 50%; vertical-align: middle; }
/* 191pt = the remaining two columns (135 + 56). Needed here for the same reason as
   .header-logo-cell's width: a colspan cell with no width left dompdf guessing. */
.header-name-cell {
    width: 191pt;
    padding: 3.5pt 8pt 3.5pt 1pt;
    border-top-right-radius: 7pt;
    border-bottom-right-radius: 7pt;
}
/* Small identity kicker so the card reads as a transport pass at a glance even before
   the eye reaches the footer band. Fixed copy (not data-driven), but still needs
   `nowrap` — without it, dompdf wrapped this short line to two anyway, which grew the
   header row's height enough to push the card onto a phantom second page. */
.pass-kicker {
    font-size: 4.5pt;
    line-height: 5pt;
    font-weight: bold;
    color: #a9bcf0;
    text-transform: uppercase;
    letter-spacing: 0.3pt;
    white-space: nowrap;
    margin-bottom: 1.5pt;
}
/* max-height + overflow:hidden, not just the server-side word-safe cap in
   _pass-card.blade.php — truncating by character count can't guarantee a line
   count, since word-wrapping is uneven (a name that measures well under the char
   cap can still wrap to 3 lines if its words happen to break awkwardly, which is
   exactly what put this card back onto a phantom second page after the first
   truncation attempt). Clipping to a hard-coded line-count pixel height is what
   actually guarantees the header's total height regardless of name length. No
   `text-transform: uppercase` here (unlike the rest of the card's labels) — the web
   reference prints the institute name in its own natural case, not forced caps. */
.inst-name {
    font-size: 7.5pt;
    line-height: 8.5pt;
    max-height: 17pt;
    overflow: hidden;
    font-weight: bold;
    color: #ffffff;
}
/* Kept to one line via `nowrap` plus the server-side word-safe cap in _pass-card.blade.php
   (not `overflow: hidden` — this dompdf build doesn't reliably clip nowrap text at a
   box's edge, it either paints past it into the next cell or, just as often, clips
   text that already fits its own content width. The server-side cap bounds the painted
   width so nothing needs clipping in the first place; `nowrap` just stops it from
   wrapping to a second line and growing this header's fixed height budget. */
.inst-address {
    font-size: 5.5pt;
    line-height: 6.5pt;
    white-space: nowrap;
    color: #c3d0f2;
    letter-spacing: 0.2pt;
    margin-top: 1pt;
}

/* Horizontal padding is applied per-cell below rather than in this shared rule, because
   a shorthand `padding` on a lower-specificity class (.photo-cell, 0-1-0) would lose to
   this rule's 0-1-1 selector and be silently dropped. The `td.<class>` selectors below
   (0-2-1) win cleanly. */
.body-row td { padding: 4pt 0 0; vertical-align: top; }
/* 50pt = 8pt page inset + the photo frame's own 42pt. The frame is left-aligned in the
   cell and the padding takes up the rest, so the info column's left edge lands exactly
   on the photo's right edge — the old 42pt width plus an unpinned column 1 was what left
   a ~33pt trench between the photo and the student's name. */
.body-row td.photo-cell { width: 50pt; padding-left: 8pt; }
/* Portrait 3:4 frame (42x56pt), not a square — matches the reference's portrait photo
   treatment. A small 2pt inner padding leaves a thin light margin between the frame's
   border and the photo itself (the reference's "framed print" look) instead of the photo
   bleeding edge-to-edge into the border. */
.photo-frame {
    width: 42pt;
    height: 56pt;
    background: #f4f6fb;
    border: 1pt solid #e2e5ee;
    border-radius: 4pt;
}
.photo-frame td {
    text-align: center;
    vertical-align: middle;
    font-size: 5pt;
    color: #5d6478;
    text-transform: uppercase;
    letter-spacing: 0.3pt;
    padding: 2pt;
}
.photo-frame img { width: 38pt; height: 52pt; border-radius: 3pt; }
/* 135pt with 5pt of right padding leaves 130pt of painted width for the info rows —
   up from roughly 97pt before column 1 was pinned. That extra ~33pt is what stops the
   longer values (course names, vehicle + driver) from hitting their truncation cap. */
.body-row td.info-cell { width: 135pt; padding-right: 5pt; }
/* 56pt = the QR's own 48pt column + the 8pt page inset on the right. */
.body-row td.qr-cell { width: 56pt; padding-right: 8pt; text-align: center; }
/* `nowrap` on every value the card prints from live data — route and stop names,
   vehicle numbers, driver names all come from free-text fields with no length cap of
   their own, and this list was never designed to hold a 2-line value. A route/stop/
   driver name long enough to wrap pushed the whole card's height past the fixed 153pt
   page and silently spilled onto a phantom second page. The actual width guard is the
   server-side word-safe cap in _pass-card.blade.php, not `overflow: hidden` here — this
   dompdf build doesn't reliably clip nowrap text at a box's edge, it either paints past
   it into the next cell or clips text that already fits. `nowrap` alone just blocks the
   2-line wrap that threatens page height; the cap keeps the painted width in bounds so
   there's nothing left to clip or bleed. */
.student-name {
    font-size: 9.5pt;
    line-height: 11pt;
    font-weight: bold;
    color: #10131c;
    letter-spacing: -0.1pt;
    white-space: nowrap;
    margin-bottom: 2pt;
}
.student-uid {
    display: inline-block;
    font-size: 6pt;
    font-weight: bold;
    color: #17307a;
    background: #e8edfb;
    padding: 1.5pt 4pt;
    border-radius: 3pt;
    letter-spacing: 0.2pt;
    white-space: nowrap;
    margin-bottom: 3pt;
}
/* A table, not inline-block spans — the label column's width wasn't being reliably
   respected as inline-block, letting a wide label ("Vehicle", "Course") eat into the
   value's space and wrap short values that should fit on one line. 5 rows (Course,
   Father, Mobile, Route, Vehicle) instead of the original 4 one-field-per-row list.
   Widths sit on the td rules below, not on a <col> — same colgroup-is-ignored constraint
   as the outer table. */
.info-rows { width: 130pt; border-collapse: collapse; table-layout: fixed; }
.info-rows td { font-size: 6.5pt; line-height: 8pt; padding: 1.25pt 0; vertical-align: baseline; }
/* Labels are the card's utility voice, not the data — uppercase + tracking separates
   them from the bold values at a glance without needing a second font weight, the same
   trick the footer and route labels use. Widened 24pt -> 27pt to fit "VEHICLE" once the
   letter-spacing is added. */
.info-rows td.rlabel {
    width: 27pt;
    font-size: 5.5pt;
    font-weight: bold;
    color: #5d6478;
    text-transform: uppercase;
    letter-spacing: 0.3pt;
    white-space: nowrap;
}
.info-rows td.rvalue { width: 103pt; font-weight: bold; color: #10131c; white-space: nowrap; }
.qr-frame {
    display: inline-block;
    padding: 2pt;
    background: #ffffff;
    border: 1pt solid #e2e5ee;
    border-radius: 4pt;
}
.qr-frame img { width: 42pt; height: 42pt; display: block; }
/* nowrap so the caption can't break to a second line and add height to the body row. */
.qr-caption {
    font-size: 4.5pt;
    line-height: 5.5pt;
    font-weight: bold;
    color: #5d6478;
    text-transform: uppercase;
    letter-spacing: 0.3pt;
    white-space: nowrap;
    margin-top: 2.5pt;
}

/* Route strip — the reference design's start-dot/dashed-line/end-dot transit graphic,
   sitting between the body and footer rows. Built the same way the footer row already
   safely nests a sub-table inside a colspan="3" cell (a plain table, no percentage-width
   surprises) rather than the header row's flat-divs approach — that pattern is proven
   not to trigger the phantom-page/nested-table bug described above, since the width
   mismatch that bug came from was specific to the header's colored background cell, not
   a plain white row like this one. The 8pt horizontal padding here is what keeps the
   strip inset while the header and footer bands run full-bleed.
   Every column width is on a td (see .route-dot-cell etc.), not the <col> — the strip
   was rendering as five equal 45pt columns, which collapsed the dashed line to a stub
   and stranded both dots well away from their labels. */
.route-row td { padding: 5pt 8pt 0; }
.route-table { width: 225pt; border-collapse: collapse; table-layout: fixed; }
.route-table td { vertical-align: middle; }
.route-dot-cell { width: 7pt; text-align: left; }
.route-dot-cell--end { text-align: right; }
.route-dot {
    display: inline-block;
    width: 4pt;
    height: 4pt;
    border-radius: 50%;
    background: #17307a;
}
.route-dot--end { background: #d9a441; }
.route-label {
    font-size: 5pt;
    line-height: 6pt;
    font-weight: bold;
    color: #17307a;
    text-transform: uppercase;
    letter-spacing: 0.4pt;
    white-space: nowrap;
}
.route-label-start { width: 60pt; text-align: left; padding-left: 3pt; }
.route-label-end { width: 60pt; text-align: right; padding-right: 3pt; }
/* 91pt = 225 - (7 + 60 + 60 + 7). An unpinned middle column risked collapsing the line
   to zero width instead of stretching to fill the gap between the two labels. */
.route-line-cell { width: 91pt; }
.route-line {
    height: 0;
    line-height: 0;
    font-size: 0;
    border-top: 1pt dashed #b9c3e0;
    margin: 0 4pt;
}

/* Footer strip — two equal columns instead of one flat label: validity on the left,
   signatory line on the right, the same "content above a rule, caption below it"
   rhythm a physical institutional ID card uses for its card-holder/authorised-by
   split, matching the reference's 1fr/1fr footer grid. A nested table inside a
   colspan="3" cell — see the .route-row comment above for why that's safe here
   despite the header row avoiding the same pattern.
   Runs full-bleed to the card's border (no 8pt inset, matching the header band) so the
   card reads as header / body / footer rather than as three floating rectangles. Widths
   are explicit pt rather than the old `width: 50%` — the percentages were resolving
   unevenly once the right cell's 1pt border-left was counted, putting the divider ~4pt
   left of true centre. */
.footer-row td { padding: 5pt 0 0; }
.footer-table { width: 241pt; border-collapse: collapse; table-layout: fixed; }
.footer-table td { background: #f4f6fb; padding: 3pt 8pt 3.5pt; text-align: center; vertical-align: top; }
.footer-left { width: 120pt; border-top-left-radius: 7pt; border-bottom-left-radius: 7pt; }
.footer-right { width: 121pt; border-top-right-radius: 7pt; border-bottom-right-radius: 7pt; border-left: 1pt solid #e2e5ee; }
.footer-value {
    font-size: 6.5pt;
    line-height: 8pt;
    font-weight: bold;
    color: #10131c;
    white-space: nowrap;
}
.footer-rule { border-top: 1pt solid #c8cede; margin: 2pt 10pt 1.5pt; }
.footer-label {
    font-size: 4.5pt;
    line-height: 5.5pt;
    font-weight: bold;
    color: #5d6478;
    text-transform: uppercase;
    letter-spacing: 0.5pt;
    white-space: nowrap;
}

.page-break { page-break-after: always; }