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
.card {
    width: 243pt;
    border: 1pt solid #e2e5ee;
    border-radius: 8pt;
    padding: 3pt 8pt;
}
/* table-layout: fixed + an explicit <colgroup> (see _pass-card.blade.php) stop the
   info column's auto-width from growing to fit its content — without it, dompdf
   sized the table wider than the 225pt content area, pushing the QR cell off the
   right edge of the card entirely. */
.card-table { width: 100%; border-collapse: collapse; table-layout: fixed; }

/* Colored header row — institute seal, name and address, the same identity strip a
   real institutional ID card carries. Deliberately NOT a nested sub-table: an
   earlier version put a width:100% table inside a colspan="3" cell, and dompdf
   mis-resolved that nested percentage width against something far wider than the
   card, pushing content clean off the page. Reusing the outer table's own three
   columns (see .card-table's <colgroup>) for the header row too avoids nesting a
   table inside a colspan cell entirely. Solid navy, not the reference's gradient —
   this dompdf build silently drops linear-gradient backgrounds. */
.header-row td { background: #17307a; vertical-align: middle; }
/* Seal sits on the left of the header with the institute name right after it, matching
   the reference ID card's layout — so it takes the photo-cell's own column. The name
   cell spans the other two columns (colspan="2" in _pass-card.blade.php) instead of
   leaving the third one as an empty blue block next to it — that blank strip is what
   made the header look lopsided once the seal moved off the right side. Padding is set
   per-cell (not on the shared `.header-row td` rule above) so the logo and name can sit
   close together — a single shared padding value left a visible gap between the ring
   and the name text that didn't read as intentional. `text-align: right` (not center)
   is what actually pins the ring — the 21pt ring inside a 42pt-wide column has slack
   room either way; centering it split that slack evenly on both sides, leaving several
   points of dead space between the ring's right edge and the name text no matter how
   low the cell padding was pushed. Right-aligning collapses all the slack onto the
   ring's left side (against the card's own border, which is meant to have breathing
   room) instead of between the ring and the name. */
.header-logo-cell { padding: 3pt 1pt 3pt 3pt; border-top-left-radius: 5pt; border-bottom-left-radius: 5pt; text-align: right; }
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
.header-name-cell {
    padding: 3pt 6pt 3pt 1pt;
    border-top-right-radius: 5pt;
    border-bottom-right-radius: 5pt;
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
/* max-height + overflow:hidden, not just the server-side Str::limit in
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
/* Kept to one line via `nowrap` plus the server-side Str::limit in _pass-card.blade.php
   (not `overflow: hidden` — this dompdf build doesn't reliably clip nowrap text at a
   box's edge, it either paints past it into the next cell or, just as often, clips
   text that already fits its own content width. Str::limit bounds the painted width
   so nothing needs clipping in the first place; `nowrap` just stops it from wrapping
   to a second line and growing this header's fixed height budget. */
.inst-address {
    font-size: 5.5pt;
    line-height: 6.5pt;
    white-space: nowrap;
    color: #c3d0f2;
    letter-spacing: 0.2pt;
    margin-top: 1pt;
}

.body-row td { padding-top: 2pt; vertical-align: top; }
/* Matches .photo-frame's own width exactly (was 46pt against a 42pt frame) — that 4pt
   mismatch left a visible gap between the photo and the student details next to it,
   since a table cell with no explicit padding still left-aligns its content and leaves
   any leftover column width as blank space on the right. */
.photo-cell { width: 42pt; }
/* Portrait 3:4 frame (42x56pt), not the old 42x42 square — matches the reference's
   portrait photo treatment. A small 2pt inner padding leaves a thin light margin
   between the frame's border and the photo itself (the reference's "framed print"
   look) instead of the photo bleeding edge-to-edge into the border. */
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
.info-cell { padding-right: 5pt; }
/* `nowrap` on every value the card prints from live data — route and stop names,
   vehicle numbers, driver names all come from free-text fields with no length cap of
   their own, and this list was never designed to hold a 2-line value. A route/stop/
   driver name long enough to wrap pushed the whole card's height past the fixed 153pt
   page and silently spilled onto a phantom second page (confirmed by rendering the
   card with realistic longer sample data). The actual width guard is the server-side
   Str::limit() in _pass-card.blade.php, not `overflow: hidden` here — this dompdf
   build doesn't reliably clip nowrap text at a box's edge, it either paints past it
   into the next cell or clips text that already fits. `nowrap` alone just blocks the
   2-line wrap that threatens page height; Str::limit keeps the painted width in
   bounds so there's nothing left to clip or bleed. */
.student-name {
    font-size: 9pt;
    font-weight: bold;
    color: #10131c;
    white-space: nowrap;
    margin-bottom: 1.5pt;
}
.student-uid {
    display: inline-block;
    font-size: 6pt;
    font-weight: bold;
    color: #17307a;
    background: #e8edfb;
    padding: 1pt 4pt;
    border-radius: 3pt;
    letter-spacing: 0.2pt;
    white-space: nowrap;
    margin-bottom: 1.5pt;
}
/* A table, not inline-block spans — the label column's width wasn't being reliably
   respected as inline-block, letting a wide label ("Vehicle", "Course") eat into the
   value's space and wrap short values that should fit on one line. 5 rows (Course,
   Father, Mobile, Route, Vehicle) instead of the original 4 one-field-per-row list. */
.info-rows { width: 100%; border-collapse: collapse; }
.info-rows td { font-size: 6.5pt; padding: 1pt 0; vertical-align: top; }
.info-rows td.rlabel { width: 24pt; color: #5d6478; }
.info-rows td.rvalue { font-weight: bold; color: #10131c; white-space: nowrap; }
.qr-cell { width: 48pt; text-align: center; vertical-align: top; }
.qr-frame {
    display: inline-block;
    padding: 2pt;
    background: #f4f6fb;
    border: 1pt solid #e2e5ee;
    border-radius: 4pt;
}
.qr-frame img { width: 38pt; height: 38pt; display: block; }
.qr-caption {
    font-size: 4.5pt;
    color: #5d6478;
    text-transform: uppercase;
    letter-spacing: 0.3pt;
    margin-top: 2pt;
}

/* Route strip — the reference design's start-dot/dashed-line/end-dot transit graphic,
   sitting between the body and footer rows. Built the same way the footer row already
   safely nests a sub-table inside a colspan="3" cell (a plain width:100% table, no
   percentage-width surprises) rather than the header row's flat-divs approach — that
   pattern is proven not to trigger the phantom-page/nested-table bug described above,
   since the width mismatch that bug came from was specific to the header's colored
   background cell, not a plain white row like this one. Every column gets an explicit
   pt width (10+52+101+52+10 = 225, matching the card's content width) instead of
   letting the middle "line" column size itself — the same fixed-layout-needs-every-
   column-pinned lesson from the outer .card-table's own colgroup (see
   _pass-card.blade.php), since an auto column here risked collapsing the dashed line
   to zero width instead of stretching to fill the gap between the two labels. */
.route-row td { padding-top: 2pt; }
.route-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.route-table td { vertical-align: middle; }
.route-dot-cell { text-align: center; }
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
.route-label-start { text-align: left; padding-left: 3pt; }
.route-label-end { text-align: right; padding-right: 3pt; }
.route-line {
    height: 0;
    line-height: 0;
    font-size: 0;
    border-top: 1pt dashed #b9c3e0;
    margin: 0 2pt;
}

/* Footer strip — two equal columns instead of one flat label: validity on the left,
   signatory line on the right, the same "content above a rule, caption below it"
   rhythm a physical institutional ID card uses for its card-holder/authorised-by
   split, matching the reference's 1fr/1fr footer grid. A nested table inside a
   colspan="3" cell — see the .route-row comment above for why that's safe here
   despite the header row avoiding the same pattern. */
.footer-row td { padding-top: 2pt; }
.footer-table { width: 100%; border-collapse: collapse; }
.footer-table td { background: #f4f6fb; padding: 2pt 6pt; text-align: center; vertical-align: top; width: 50%; }
.footer-left { border-top-left-radius: 5pt; border-bottom-left-radius: 5pt; }
.footer-right { border-top-right-radius: 5pt; border-bottom-right-radius: 5pt; border-left: 1pt solid #e2e5ee; }
.footer-value {
    font-size: 6.5pt;
    font-weight: bold;
    color: #10131c;
    white-space: nowrap;
}
.footer-rule { border-top: 1pt solid #e2e5ee; margin: 1.5pt 4pt 1pt; }
.footer-label {
    font-size: 4.5pt;
    font-weight: bold;
    color: #5d6478;
    text-transform: uppercase;
    letter-spacing: 0.5pt;
    white-space: nowrap;
}

/* Final visual refinements: fixed vertical rhythm and balanced photo / QR columns. */
.card { height: 153pt; padding: 5pt; border-color: #dce2ef; }
.header-row td { height: 32pt; background: #18377e; }
.header-logo-cell { padding: 3pt 3pt 3pt 5pt; text-align: center; }
.header-name-cell { padding: 4pt 7pt 4pt 2pt; }
.seal-ring { width: 24pt; height: 24pt; line-height: 21pt; border-color: #e4b34d; }
.logo-img { width: 21pt; height: 21pt; }
.pass-kicker { font-size: 4.8pt; letter-spacing: .65pt; }
.inst-name { font-size: 8.8pt; line-height: 9.5pt; }
.body-row td { padding-top: 4pt; }
.photo-cell { width: 46pt; }
.photo-frame { width: 44pt; height: 58pt; }
.photo-frame img { width: 40pt; height: 54pt; }
.info-cell { padding: 0 4pt 0 2pt; }
.student-name { font-size: 10pt; line-height: 11pt; }
.info-rows { table-layout: fixed; }
.info-rows td { padding: .75pt 0; font-size: 6.3pt; line-height: 7.4pt; }
.info-rows td.rlabel { width: 26pt; }
.qr-cell { width: 59pt; padding-left: 1pt; }
.qr-frame { padding: 2.5pt; }
.qr-frame img { width: 42pt; height: 42pt; }
.qr-caption { margin-top: 1.5pt; }
.route-row td, .footer-row td { padding-top: 3pt; }
.footer-table { table-layout: fixed; }
.footer-table td { padding: 2.5pt 6pt; }
.footer-value { height: 8pt; line-height: 8pt; color: #15295e; }
.footer-rule { margin: 1.2pt 3pt 1.3pt; }
.page-break { page-break-after: always; }
