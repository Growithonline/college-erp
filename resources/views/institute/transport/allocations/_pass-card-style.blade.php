/* Table-based layout throughout — dompdf's float + absolute-positioning support is
   unreliable at this card's tiny page size and was causing content to spill onto a
   phantom second page with the QR code missing entirely. Tables are dompdf's most
   predictable layout primitive, so every region (header / photo / info / QR / footer)
   is a table cell instead. Also no CSS gradients or box-shadow here on purpose — this
   dompdf build (v3.1.5, checked directly in vendor/) has no renderer for either, so
   they'd just be silently dropped; visual depth comes from solid color blocks, borders
   and a thin accent ring instead. */
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
    border: 1pt solid #cbd5e1;
    border-radius: 8pt;
    padding: 3pt 8pt;
}
/* table-layout: fixed + an explicit <colgroup> (see _pass-card.blade.php) stop the
   info column's auto-width from growing to fit its content — without it, dompdf
   sized the table wider than the 223pt content area, pushing the QR cell off the
   right edge of the card entirely. */
.card-table { width: 100%; border-collapse: collapse; table-layout: fixed; }

/* Colored header row — institute seal, name and address, the same identity strip a
   real institutional ID card carries. Deliberately NOT a nested sub-table: an
   earlier version put a width:100% table inside a colspan="3" cell, and dompdf
   mis-resolved that nested percentage width against something far wider than the
   card, pushing content clean off the page. Reusing the outer table's own three
   columns (see .card-table's <colgroup>) for the header row too avoids nesting a
   table inside a colspan cell entirely. */
.header-row td { background: #1746c9; padding: 3pt 6pt; vertical-align: middle; }
/* Seal sits on the left of the header with the institute name right after it, matching
   the reference ID card's layout — so it takes the photo-cell's own column, and
   header-fill-cell (the qr-cell's column) is the blank one instead. */
.header-logo-cell { border-top-left-radius: 5pt; border-bottom-left-radius: 5pt; text-align: center; }
/* The ring is what reads as an embossed "seal" rather than a flat logo swap — a plain
   circle looked like a placeholder icon rather than part of the card's identity. A
   styled div, not a nested table (see .header-fill-cell comment above for why nested
   tables in this header row are avoided). */
.seal-ring {
    display: inline-block;
    width: 21pt;
    height: 21pt;
    background: #ffffff;
    border: 1.25pt solid #facc15;
    border-radius: 50%;
    text-align: center;
    line-height: 18.5pt;
}
.logo-fallback { color: #1746c9; font-size: 7pt; font-weight: bold; }
.logo-img { width: 17pt; height: 17pt; border-radius: 50%; vertical-align: middle; }
.header-name-cell { padding-left: 5pt; padding-right: 4pt; }
.header-fill-cell { border-top-right-radius: 5pt; border-bottom-right-radius: 5pt; }
/* Small identity kicker so the card reads as a transport pass at a glance even before
   the eye reaches the footer band. Fixed copy (not data-driven), but still needs
   `nowrap` — without it, dompdf wrapped this short line to two anyway, which grew the
   header row's height enough to push the card onto a phantom second page. */
.pass-kicker {
    font-size: 4.5pt;
    line-height: 5pt;
    font-weight: bold;
    color: #bfd2fb;
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
   actually guarantees the header's total height regardless of name length. */
.inst-name {
    font-size: 7.5pt;
    line-height: 8.5pt;
    max-height: 17pt;
    overflow: hidden;
    font-weight: bold;
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: 0.2pt;
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
    color: #bfd2fb;
    letter-spacing: 0.2pt;
    margin-top: 1pt;
}

.body-row td { padding-top: 2pt; vertical-align: top; }
.photo-cell { width: 46pt; }
.photo-frame {
    width: 42pt;
    height: 42pt;
    background: #f8fafc;
    border: 1pt solid #cbd5e1;
    border-radius: 3pt;
}
.photo-frame td {
    text-align: center;
    vertical-align: middle;
    font-size: 5.5pt;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.3pt;
}
.photo-frame img { width: 42pt; height: 42pt; border-radius: 3pt; }
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
    font-size: 8.5pt;
    font-weight: bold;
    white-space: nowrap;
    margin-bottom: 1.5pt;
}
.student-uid {
    display: inline-block;
    font-size: 5.5pt;
    font-weight: bold;
    color: #1746c9;
    background: #e8eefc;
    padding: 1pt 4pt;
    border-radius: 3pt;
    letter-spacing: 0.2pt;
    white-space: nowrap;
    margin-bottom: 1.5pt;
}
/* A table, not inline-block spans — the label column's width wasn't being reliably
   respected as inline-block, letting a wide label ("Vehicle", "Course") eat into the
   value's space and wrap short values that should fit on one line. Up to 5 rows now
   (Course, Father, Mobile, Route, Vehicle) instead of the original 4, so font-size and
   padding are trimmed slightly from the first pass to keep the card on one page. */
.info-rows { width: 100%; border-collapse: collapse; }
.info-rows td { font-size: 5.5pt; padding: 1pt 0; vertical-align: top; }
.info-rows td.rlabel { width: 22pt; color: #64748b; }
.info-rows td.rvalue { font-weight: bold; color: #0f172a; white-space: nowrap; }
.qr-cell { width: 48pt; text-align: center; vertical-align: top; }
.qr-frame {
    display: inline-block;
    padding: 2pt;
    background: #f8fafc;
    border: 1pt solid #cbd5e1;
    border-radius: 3pt;
}
.qr-frame img { width: 38pt; height: 38pt; display: block; }
.qr-caption {
    font-size: 4.5pt;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.3pt;
    margin-top: 2pt;
}
/* Footer strip — two columns instead of one flat label: validity on the left,
   signatory line on the right, the same "content above a rule, caption below it"
   rhythm a physical institutional ID card uses for its card-holder/authorised-by
   split. Still a plain colspan cell, not a nested table, for the same reason the
   header row avoids one (see the .header-row comment above) — each side is built
   from stacked divs instead. */
.footer-row td { padding-top: 2pt; }
.footer-table { width: 100%; border-collapse: collapse; }
.footer-table td { background: #eef1f5; padding: 2pt 6pt; text-align: center; vertical-align: top; }
.footer-left { border-top-left-radius: 5pt; border-bottom-left-radius: 5pt; width: 68%; }
.footer-right { border-top-right-radius: 5pt; border-bottom-right-radius: 5pt; width: 32%; border-left: 1pt solid #dbe1e8; }
.footer-value {
    font-size: 6.5pt;
    font-weight: bold;
    color: #0f172a;
    white-space: nowrap;
}
.footer-rule { border-top: 1pt solid #cbd5e1; margin: 1.5pt 4pt 1pt; }
.footer-label {
    font-size: 4.5pt;
    font-weight: bold;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5pt;
    white-space: nowrap;
}

.page-break { page-break-after: always; }
