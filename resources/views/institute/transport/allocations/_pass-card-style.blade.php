/* Fresh, compact transport-pass design. Table layout is intentional for DomPDF. */
@page { margin: 0; }
* { box-sizing: border-box; margin: 0; padding: 0; font-family: Helvetica, Arial, sans-serif; }
body { width: 243pt; }
.card { width: 243pt; padding: 4pt; border: 1pt solid #d9e0ee; border-radius: 7pt; background: #ffffff; }
.card-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.header-row td { height: 27pt; vertical-align: middle; background: #12357d; }
.header-logo-cell { padding: 3pt 2pt 3pt 5pt; text-align: center; border-radius: 5pt 0 0 5pt; }
.header-name-cell { padding: 3pt 6pt 3pt 2pt; border-radius: 0 5pt 5pt 0; }
.seal-ring { display: inline-block; width: 20pt; height: 20pt; overflow: hidden; line-height: 17pt; text-align: center; border: 1pt solid #f0bd4f; border-radius: 50%; background: #ffffff; }
.logo-img { width: 17pt; height: 17pt; vertical-align: middle; border-radius: 50%; }
.logo-fallback { color: #12357d; font-size: 6pt; font-weight: bold; }
.pass-kicker { color: #b9cef8; font-size: 4.3pt; font-weight: bold; letter-spacing: .6pt; text-transform: uppercase; white-space: nowrap; }
.inst-name { max-height: 12pt; overflow: hidden; color: #ffffff; font-size: 7.8pt; line-height: 8.5pt; font-weight: bold; }
.inst-address { margin-top: .5pt; color: #d5e0fa; font-size: 4.8pt; line-height: 5.5pt; white-space: nowrap; }
.body-row td { padding-top: 3pt; vertical-align: top; }
.photo-cell { width: 46pt; }
.photo-frame { width: 42pt; height: 55pt; border: 1pt solid #dce3ef; border-radius: 3pt; background: #f4f6fa; }
.photo-frame td { padding: 2pt; color: #6c7890; font-size: 4pt; text-align: center; vertical-align: middle; text-transform: uppercase; }
.photo-frame img { display: block; width: 38pt; height: 51pt; border-radius: 2pt; }
.info-cell { padding: 0 3pt 0 1pt; }
.student-name { margin-bottom: 1pt; color: #121826; font-size: 8.8pt; line-height: 9.5pt; font-weight: bold; white-space: nowrap; }
.student-uid { display: inline-block; margin-bottom: 1.5pt; padding: 1pt 3pt; border-radius: 2pt; background: #edf2ff; color: #12357d; font-size: 5.1pt; font-weight: bold; white-space: nowrap; }
.info-rows { width: 100%; border-collapse: collapse; table-layout: fixed; }
.info-rows td { padding: .35pt 0; font-size: 5.5pt; line-height: 6.2pt; vertical-align: top; }
.info-rows td.rlabel { width: 23pt; color: #69758a; }
.info-rows td.rvalue { color: #182235; font-weight: bold; white-space: nowrap; }
.qr-cell { width: 59pt; padding-left: 1pt; text-align: center; }
.qr-frame { display: inline-block; padding: 2pt; border: 1pt solid #dce3ef; border-radius: 4pt; background: #ffffff; }
.qr-frame img { display: block; width: 39pt; height: 39pt; }
.qr-caption { margin-top: 1pt; color: #69758a; font-size: 3.8pt; letter-spacing: .35pt; text-transform: uppercase; white-space: nowrap; }
.route-row td { padding-top: 2pt; }
.route-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.route-table td { vertical-align: middle; }
.route-dot-cell { text-align: center; }
.route-dot { display: inline-block; width: 4pt; height: 4pt; border-radius: 50%; background: #12357d; }
.route-dot--end { background: #e5a93d; }
.route-label { color: #12357d; font-size: 4.2pt; font-weight: bold; letter-spacing: .3pt; text-transform: uppercase; white-space: nowrap; }
.route-label-start { padding-left: 2pt; text-align: left; }
.route-label-end { padding-right: 2pt; text-align: right; }
.route-line { margin: 0 2pt; border-top: 1pt dashed #b9c8e7; font-size: 0; line-height: 0; }
.footer-row td { padding-top: 2pt; }
.footer-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.footer-table td { width: 50%; padding: 2pt 5pt; background: #f4f6fb; text-align: center; vertical-align: top; }
.footer-left { border-radius: 4pt 0 0 4pt; }
.footer-right { border-left: 1pt solid #dce3ef; border-radius: 0 4pt 4pt 0; }
.footer-value { height: 7pt; color: #12357d; font-size: 5.8pt; font-weight: bold; line-height: 7pt; white-space: nowrap; }
.footer-rule { margin: 1pt 2pt; border-top: 1pt solid #dce3ef; }
.footer-label { color: #69758a; font-size: 3.8pt; font-weight: bold; letter-spacing: .55pt; text-transform: uppercase; white-space: nowrap; }
.page-break { page-break-after: always; }