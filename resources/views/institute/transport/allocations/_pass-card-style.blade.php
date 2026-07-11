* { box-sizing: border-box; margin: 0; padding: 0; font-family: Helvetica, Arial, sans-serif; }
body { width: 243pt; }
.card {
    width: 243pt;
    height: 153pt;
    border: 1pt solid #cbd5e1;
    border-radius: 8pt;
    padding: 10pt;
    position: relative;
}
.header {
    font-size: 8pt;
    font-weight: bold;
    color: #1d4ed8;
    text-transform: uppercase;
    letter-spacing: 0.5pt;
    border-bottom: 1pt solid #e2e8f0;
    padding-bottom: 4pt;
    margin-bottom: 6pt;
}
.body { display: block; }
.photo {
    float: left;
    width: 50pt;
    height: 60pt;
    border: 1pt solid #cbd5e1;
    text-align: center;
    font-size: 6pt;
    color: #94a3b8;
    padding-top: 24pt;
}
.photo img { width: 50pt; height: 60pt; }
.info { margin-left: 58pt; }
.student-name { font-size: 11pt; font-weight: bold; margin-bottom: 1pt; }
.student-roll { font-size: 7.5pt; color: #64748b; margin-bottom: 5pt; }
.row { font-size: 7.5pt; margin-bottom: 2pt; }
.row .label { color: #64748b; display: inline-block; width: 34pt; }
.row .value { font-weight: bold; }
.qr {
    position: absolute;
    right: 10pt;
    bottom: 10pt;
    width: 46pt;
    height: 46pt;
}
.qr svg { width: 46pt; height: 46pt; }
.page-break { page-break-after: always; }
