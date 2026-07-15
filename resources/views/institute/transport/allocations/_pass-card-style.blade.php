/* Table-based layout throughout — dompdf's float + absolute-positioning support is
   unreliable at this card's tiny page size and was causing content to spill onto a
   phantom second page with the QR code missing entirely. Tables are dompdf's most
   predictable layout primitive, so every region (header / photo / info / QR) is a
   table cell instead. */
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
    padding: 5pt 10pt;
}
/* table-layout: fixed + an explicit <colgroup> (see _pass-card.blade.php) stop the
   info column's auto-width from growing to fit its content — without it, dompdf
   sized the table wider than the 223pt content area, pushing the QR cell off the
   right edge of the card entirely. */
.card-table { width: 100%; border-collapse: collapse; table-layout: fixed; }

/* Colored header row — institute logo, name and address, the same identity strip a
   real institutional ID card carries. Deliberately NOT a nested sub-table: an
   earlier version put a width:100% table inside a colspan="3" cell, and dompdf
   mis-resolved that nested percentage width against something far wider than the
   card, pushing content clean off the page. Reusing the outer table's own three
   columns (see .card-table's <colgroup>) for the header row too avoids nesting a
   table inside a colspan cell entirely. */
.header-row td { background: #1d4ed8; padding: 4pt 6pt; vertical-align: middle; }
/* Logo/seal sits on the right of the header (institute name on the left), matching
   a real institutional ID card's layout, so it takes the qr-cell's own column —
   header-fill-cell (photo-cell's column) is the blank one instead. */
.header-logo-cell { border-top-right-radius: 5pt; border-bottom-right-radius: 5pt; text-align: center; }
/* A styled inline-block, not a nested table (see .header-fill-cell comment above for
   why nested tables in this header row are avoided) — line-height equal to the box's
   own height is a simple, reliable way to vertically center the single-line initials
   text without another table. Circular, not rounded-square, to read as a seal. */
.logo-fallback {
    display: inline-block;
    width: 20pt;
    height: 20pt;
    line-height: 20pt;
    background: #ffffff;
    border-radius: 50%;
    color: #1d4ed8;
    font-size: 7pt;
    font-weight: bold;
    text-align: center;
}
.logo-img { width: 20pt; height: 20pt; border-radius: 50%; }
.header-name-cell { padding-left: 4pt; padding-right: 4pt; }
.header-fill-cell { border-top-left-radius: 5pt; border-bottom-left-radius: 5pt; }
/* max-height + overflow:hidden, not just the server-side Str::limit in
   _pass-card.blade.php — truncating by character count can't guarantee a line
   count, since word-wrapping is uneven (a name that measures well under the char
   cap can still wrap to 3 lines if its words happen to break awkwardly, which is
   exactly what put this card back onto a phantom second page after the first
   truncation attempt). Clipping to a hard-coded 2-line pixel height is what
   actually guarantees the header's total height regardless of name length. */
.inst-name {
    font-size: 8pt;
    line-height: 9pt;
    max-height: 18pt;
    overflow: hidden;
    font-weight: bold;
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: 0.2pt;
}
/* Clamped to one line the same way .inst-name is clamped to two — a real
   institute's city/state can run long enough to wrap on its own, and this header's
   height budget is fixed regardless of how long that text turns out to be. */
.inst-address {
    font-size: 6pt;
    line-height: 7pt;
    max-height: 7pt;
    overflow: hidden;
    color: #cbdafe;
    letter-spacing: 0.2pt;
    margin-top: 1.5pt;
}

.body-row td { padding-top: 3pt; vertical-align: top; }
.photo-cell { width: 48pt; }
.photo-frame {
    width: 44pt;
    height: 44pt;
    border: 1pt solid #cbd5e1;
}
.photo-frame td {
    text-align: center;
    vertical-align: middle;
    font-size: 6pt;
    color: #94a3b8;
}
.photo-frame img { width: 44pt; height: 44pt; }
.info-cell { padding-right: 6pt; }
.student-name { font-size: 9.5pt; font-weight: bold; margin-bottom: 1pt; }
.student-roll { font-size: 7pt; color: #64748b; margin-bottom: 4pt; }
/* A table, not inline-block spans — the label column's width wasn't being reliably
   respected as inline-block, letting a wide label ("Vehicle", "Driver") eat into the
   value's space and wrap short values that should fit on one line. */
.info-rows { width: 100%; border-collapse: collapse; }
.info-rows td { font-size: 7.5pt; padding: 1pt 0; vertical-align: top; }
.info-rows td.rlabel { color: #64748b; }
.info-rows td.rvalue { font-weight: bold; }
.qr-cell { width: 52pt; text-align: center; vertical-align: top; }
.qr img { width: 44pt; height: 44pt; }
.qr-caption {
    font-size: 5pt;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.3pt;
    margin-top: 2pt;
}
/* Footer strip — a plain colspan cell with a single text line, not a nested table,
   for the same reason the header row avoids one (see the .header-row comment above). */
.footer-row td { background: #eef1f5; padding: 3pt 6pt 2pt; border-radius: 5pt; text-align: center; }
.footer-cell { font-size: 6pt; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 0.6pt; }

.page-break { page-break-after: always; }
