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
    padding: 8pt 10pt;
}
/* table-layout: fixed + an explicit <colgroup> (see _pass-card.blade.php) stop the
   info column's auto-width from growing to fit its content — without it, dompdf
   sized the table wider than the 223pt content area, pushing the QR cell off the
   right edge of the card entirely. */
.card-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.header-cell {
    font-size: 8pt;
    font-weight: bold;
    color: #1d4ed8;
    text-transform: uppercase;
    letter-spacing: 0.5pt;
    border-bottom: 1pt solid #e2e8f0;
    padding-bottom: 4pt;
}
.body-row td { padding-top: 7pt; vertical-align: top; }
.photo-cell { width: 48pt; }
.photo-frame {
    width: 44pt;
    height: 56pt;
    border: 1pt solid #cbd5e1;
}
.photo-frame td {
    text-align: center;
    vertical-align: middle;
    font-size: 6pt;
    color: #94a3b8;
}
.photo-frame img { width: 44pt; height: 56pt; }
.info-cell { padding-right: 6pt; }
.student-name { font-size: 9.5pt; font-weight: bold; margin-bottom: 1pt; }
.student-roll { font-size: 7pt; color: #64748b; margin-bottom: 5pt; }
/* A table, not inline-block spans — the label column's width wasn't being reliably
   respected as inline-block, letting a wide label ("Vehicle", "Driver") eat into the
   value's space and wrap short values that should fit on one line. */
.info-rows { width: 100%; border-collapse: collapse; }
.info-rows td { font-size: 7.5pt; padding: 1.5pt 0; vertical-align: top; }
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
.page-break { page-break-after: always; }
