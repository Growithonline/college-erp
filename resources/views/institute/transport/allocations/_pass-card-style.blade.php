/* Single-page institutional transport pass. Separate tables prevent DomPDF column drift. */
@page { margin: 0; }
* { box-sizing: border-box; margin: 0; padding: 0; font-family: Helvetica, Arial, sans-serif; }
body { width: 243pt; }
.card { position: relative; width: 243pt; height: 145pt; padding: 4pt; border: 1pt solid #d8dde7; background: #ffffff; }

.brand-table, .identity-table, .footer-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.brand-table td { height: 30pt; background: #a93631; vertical-align: middle; }
.brand-copy { padding: 3pt 4pt 3pt 7pt; }
.brand-kicker { color: #f6d7d4; font-size: 4pt; font-weight: bold; letter-spacing: .55pt; text-transform: uppercase; white-space: nowrap; }
.brand-name { color: #ffffff; font-size: 7.8pt; font-weight: bold; line-height: 8.5pt; }
.brand-address { color: #ffffff; font-size: 4.3pt; line-height: 5pt; white-space: nowrap; }
.brand-mark { padding: 3pt 5pt 3pt 1pt; text-align: center; }
.seal-ring { display: inline-block; width: 24pt; height: 24pt; overflow: hidden; line-height: 21pt; text-align: center; border: 1pt solid #ffffff; border-radius: 50%; background: #ffffff; }
.logo-img { width: 20pt; height: 20pt; vertical-align: middle; border-radius: 50%; }
.logo-fallback { color: #a93631; font-size: 5pt; font-weight: bold; }

.identity-table { margin-top: 4pt; }
.identity-table td { vertical-align: top; }
.portrait-cell { padding-left: 3pt; }
.photo-frame { width: 36pt; height: 47pt; background: #f1f2f4; }
.photo-frame td { padding: 0; color: #697386; font-size: 4pt; text-align: center; vertical-align: middle; text-transform: uppercase; }
.photo-frame img { display: block; width: 36pt; height: 47pt; }
.holder-cell { padding: 0 3pt 0 1pt; }
.holder-name { color: #161616; font-family: Georgia, 'Times New Roman', serif; font-size: 7.8pt; font-weight: bold; line-height: 8.5pt; text-transform: uppercase; white-space: nowrap; }
.holder-id { margin: 1pt 0 1.5pt; color: #8d2e2a; font-size: 4.7pt; font-weight: bold; white-space: nowrap; }
.detail-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.detail-table td { padding: .8pt 0; font-size: 5pt; line-height: 7.2pt; vertical-align: top; }
.detail-table .label { width: 21pt; color: #626b7a; }
.detail-table .value { color: #161616; font-weight: bold; white-space: nowrap; }
.qr-cell { padding-right: 3pt; text-align: center; }
.qr-frame { display: inline-block; padding: 1pt; border: 1pt solid #d8dde7; background: #ffffff; }
.qr-frame img { display: block; width: 29pt; height: 29pt; }
.qr-caption { margin-top: .6pt; color: #7b838f; font-size: 3.2pt; letter-spacing: .2pt; text-transform: uppercase; white-space: nowrap; }

.footer-spacer { display: none; }
.footer-table { position: absolute; left: 4pt; bottom: 4pt; width: 231pt; }
.footer-table td { padding: 2pt 4pt; background: #eef0f3; vertical-align: middle; text-align: center; }
.footer-valid { border-top: 1pt solid #d7dce4; }
.footer-authority { border-top: 1pt solid #d7dce4; border-left: 1pt solid #d7dce4; }
.footer-value { color: #20242b; font-size: 5pt; font-weight: bold; line-height: 6pt; white-space: nowrap; }
.footer-label { color: #596170; font-size: 3.4pt; font-weight: bold; letter-spacing: .4pt; text-transform: uppercase; white-space: nowrap; }
.footer-authority .footer-value { height: 5.5pt; border-bottom: 1pt solid #9aa3b0; }
.page-break { page-break-after: always; }