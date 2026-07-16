/* Fresh, single-page transport pass. Tables keep DomPDF rendering stable. */
@page { margin: 0; }
* { box-sizing: border-box; margin: 0; padding: 0; font-family: Helvetica, Arial, sans-serif; }
body { width: 243pt; }
.card { width: 243pt; padding: 4pt; border: 1pt solid #d9e1ef; border-radius: 7pt; background: #ffffff; }
.card-table { width: 100%; border-collapse: collapse; table-layout: fixed; }

.header-row > td.header-cell { height: 25pt; padding: 0; vertical-align: middle; background: #153b86; border-radius: 5pt; }
.header-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.header-logo-cell { width: 30pt; padding: 3pt 1pt 3pt 4pt; text-align: center; vertical-align: middle; }
.header-name-cell { padding: 3pt 6pt 3pt 1pt; vertical-align: middle; }
.seal-ring { display: inline-block; width: 19pt; height: 19pt; overflow: hidden; line-height: 16pt; text-align: center; border: 1pt solid #efbb4e; border-radius: 50%; background: #ffffff; }
.logo-img { width: 16pt; height: 16pt; vertical-align: middle; border-radius: 50%; }
.logo-fallback { color: #153b86; font-size: 6pt; font-weight: bold; }
.pass-kicker { color: #bed0f5; font-size: 4.2pt; font-weight: bold; letter-spacing: .65pt; text-transform: uppercase; white-space: nowrap; }
.inst-name { max-height: 11pt; overflow: hidden; color: #ffffff; font-size: 7.6pt; line-height: 8.2pt; font-weight: bold; }
.inst-address { margin-top: .4pt; color: #d5e1f9; font-size: 4.7pt; line-height: 5.2pt; white-space: nowrap; }

.body-row > td { padding-top: 3pt; vertical-align: top; }
.photo-cell { width: 42pt; }
.photo-frame { width: 40pt; height: 52pt; border: 1pt solid #dce4f0; border-radius: 3pt; background: #f4f6fa; }
.photo-frame td { padding: 2pt; color: #6c7890; font-size: 4pt; text-align: center; vertical-align: middle; text-transform: uppercase; }
.photo-frame img { display: block; width: 36pt; height: 48pt; border-radius: 2pt; }
.info-cell { padding: 0 3pt 0 1pt; }
.student-name { margin-bottom: 1pt; color: #101828; font-size: 8.8pt; line-height: 9.4pt; font-weight: bold; white-space: nowrap; }
.student-uid { display: inline-block; margin-bottom: 1.3pt; padding: 1pt 3pt; border-radius: 2pt; background: #ecf2ff; color: #153b86; font-size: 5.1pt; font-weight: bold; white-space: nowrap; }
.info-rows { width: 100%; border-collapse: collapse; table-layout: fixed; }
.info-rows td { padding: .45pt 0; font-size: 5.6pt; line-height: 6.4pt; vertical-align: top; }
.info-rows td.rlabel { width: 23pt; color: #66758d; }
.info-rows td.rvalue { color: #111c30; font-weight: bold; white-space: nowrap; }
.qr-cell { width: 54pt; padding-left: 1pt; text-align: center; }
.qr-frame { display: inline-block; padding: 2pt; border: 1pt solid #dce4f0; border-radius: 4pt; background: #ffffff; }
.qr-frame img { display: block; width: 35pt; height: 35pt; }
.qr-caption { margin-top: 1pt; color: #66758d; font-size: 3.7pt; letter-spacing: .3pt; text-transform: uppercase; white-space: nowrap; }

.route-row td { padding-top: 2pt; }
.route-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.route-table td { vertical-align: middle; }
.route-dot-cell { text-align: center; }
.route-dot { display: inline-block; width: 4pt; height: 4pt; border-radius: 50%; background: #153b86; }
.route-dot--end { background: #e5aa3d; }
.route-label { color: #153b86; font-size: 4.2pt; font-weight: bold; letter-spacing: .3pt; text-transform: uppercase; white-space: nowrap; }
.route-label-start { padding-left: 2pt; text-align: left; }
.route-label-end { padding-right: 2pt; text-align: right; }
.route-line { margin: 0 2pt; border-top: 1pt dashed #b8c8e8; font-size: 0; line-height: 0; }
.pass-spacer td { height: 13pt; }

.footer-row td { padding-top: 1pt; }
.footer-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.footer-table td { width: 50%; padding: 2pt 5pt; background: #f3f6fc; text-align: center; vertical-align: top; }
.footer-left { border-radius: 4pt 0 0 4pt; }
.footer-right { border-left: 1pt solid #dce4f0; border-radius: 0 4pt 4pt 0; }
.footer-value { height: 7pt; color: #153b86; font-size: 5.8pt; font-weight: bold; line-height: 7pt; white-space: nowrap; }
.footer-rule { margin: 1pt 2pt; border-top: 1pt solid #dce4f0; }
.footer-label { color: #66758d; font-size: 3.8pt; font-weight: bold; letter-spacing: .55pt; text-transform: uppercase; white-space: nowrap; }
.page-break { page-break-after: always; }