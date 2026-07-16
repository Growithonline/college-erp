/* Institutional transport ID card — deliberately compact for DomPDF's 243 × 153pt page. */
@page { margin: 0; }
* { box-sizing: border-box; margin: 0; padding: 0; font-family: Helvetica, Arial, sans-serif; }
body { width: 243pt; }
.card { width: 243pt; height: 145pt; padding: 4pt; border: 1pt solid #d8dde7; background: #ffffff; }
.pass-shell { width: 100%; height: 135pt; border-collapse: collapse; table-layout: fixed; }

.brand-row td { height: 29pt; background: #a93631; vertical-align: middle; }
.brand-copy { padding: 3pt 4pt 3pt 7pt; border-radius: 4pt 0 0 0; }
.brand-kicker { color: #f6d7d4; font-size: 4.2pt; font-weight: bold; letter-spacing: .55pt; text-transform: uppercase; white-space: nowrap; }
.brand-name { color: #ffffff; font-size: 8.2pt; font-weight: bold; line-height: 9pt; }
.brand-address { color: #ffffff; font-size: 4.4pt; line-height: 5.2pt; white-space: nowrap; }
.brand-mark { width: 29pt; padding: 3pt 5pt 3pt 1pt; text-align: center; border-radius: 0 4pt 0 0; }
.seal-ring { display: inline-block; width: 20pt; height: 20pt; overflow: hidden; line-height: 17pt; text-align: center; border: 1pt solid #ffffff; border-radius: 50%; background: #ffffff; }
.logo-img { width: 17pt; height: 17pt; vertical-align: middle; border-radius: 50%; }
.logo-fallback { color: #a93631; font-size: 6pt; font-weight: bold; }

.identity-row td { padding-top: 5pt; vertical-align: top; }
.portrait-cell { width: 43pt; padding-left: 4pt; }
.photo-frame { width: 38pt; height: 50pt; background: #f1f2f4; }
.photo-frame td { padding: 0; color: #697386; font-size: 4pt; text-align: center; vertical-align: middle; text-transform: uppercase; }
.photo-frame img { display: block; width: 38pt; height: 50pt; }
.holder-cell { padding: 1pt 4pt 0 2pt; }
.holder-name { color: #161616; font-family: Georgia, 'Times New Roman', serif; font-size: 8.2pt; font-weight: bold; line-height: 9pt; text-transform: uppercase; white-space: nowrap; }
.holder-id { margin: 1pt 0 2pt; color: #8d2e2a; font-size: 5pt; font-weight: bold; white-space: nowrap; }
.detail-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.detail-table td { padding: .55pt 0; font-size: 5.3pt; line-height: 6.1pt; vertical-align: top; }
.detail-table .label { width: 22pt; color: #626b7a; }
.detail-table .value { color: #161616; font-weight: bold; white-space: nowrap; }
.qr-cell { width: 43pt; padding-right: 4pt; text-align: center; }
.qr-frame { display: inline-block; padding: 1.5pt; border: 1pt solid #d8dde7; background: #ffffff; }
.qr-frame img { display: block; width: 32pt; height: 32pt; }
.qr-caption { margin-top: 1pt; color: #7b838f; font-size: 3.5pt; letter-spacing: .25pt; text-transform: uppercase; white-space: nowrap; }

.footer-row td { height: 20pt; padding: 2pt 4pt; background: #eef0f3; vertical-align: middle; text-align: center; }
.footer-valid { border-top: 1pt solid #d7dce4; }
.footer-authority { border-top: 1pt solid #d7dce4; border-left: 1pt solid #d7dce4; }
.footer-value { color: #20242b; font-size: 5.5pt; font-weight: bold; line-height: 6.5pt; white-space: nowrap; }
.footer-label { color: #596170; font-size: 3.7pt; font-weight: bold; letter-spacing: .45pt; text-transform: uppercase; white-space: nowrap; }
.footer-authority .footer-value { height: 6.5pt; border-bottom: 1pt solid #9aa3b0; }
.page-break { page-break-after: always; }