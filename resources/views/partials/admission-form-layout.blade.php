{{-- Admission forms (full + quick + edit): label and input side by side, fields on a 12-col grid. Scope: form.adm-form --}}
<style>
    /* Grid replaces the Bootstrap row; every existing col-* keeps working through the span map below */
    form.adm-form .card-body .row:not(#subjectContent .row):not(#editSubjectSection .row) {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 12px 28px;
        margin: 0;
        --bs-gutter-x: 0;
        --bs-gutter-y: 0;
    }
    form.adm-form .card-body .row:not(#subjectContent .row):not(#editSubjectSection .row) > * {
        width: auto;
        max-width: none;
        margin: 0;
        padding: 0;
        grid-column: span 12;
    }

    /* Wide screens: 3 fields per row */
    @media (min-width: 1400px) {
        form.adm-form .card-body .row:not(#subjectContent .row):not(#editSubjectSection .row) > .col-md-2,
        form.adm-form .card-body .row:not(#subjectContent .row):not(#editSubjectSection .row) > .col-md-3,
        form.adm-form .card-body .row:not(#subjectContent .row):not(#editSubjectSection .row) > .col-md-4 { grid-column: span 4; }
        form.adm-form .card-body .row:not(#subjectContent .row):not(#editSubjectSection .row) > .col-md-5,
        form.adm-form .card-body .row:not(#subjectContent .row):not(#editSubjectSection .row) > .col-md-6,
        form.adm-form .card-body .row:not(#subjectContent .row):not(#editSubjectSection .row) > .col-6 { grid-column: span 6; }
    }
    /* Laptops: 2 fields per row */
    @media (min-width: 768px) and (max-width: 1399.98px) {
        form.adm-form .card-body .row:not(#subjectContent .row):not(#editSubjectSection .row) > .col-md-2,
        form.adm-form .card-body .row:not(#subjectContent .row):not(#editSubjectSection .row) > .col-md-3,
        form.adm-form .card-body .row:not(#subjectContent .row):not(#editSubjectSection .row) > .col-md-4,
        form.adm-form .card-body .row:not(#subjectContent .row):not(#editSubjectSection .row) > .col-md-5,
        form.adm-form .card-body .row:not(#subjectContent .row):not(#editSubjectSection .row) > .col-md-6,
        form.adm-form .card-body .row:not(#subjectContent .row):not(#editSubjectSection .row) > .col-6 { grid-column: span 6; }
    }

    /* Photo column sits beside the fields instead of above them */
    @media (min-width: 768px) {
        form.adm-form .card-body .row:not(#subjectContent .row):not(#editSubjectSection .row):has(> .col-md-2.text-center) {
            grid-template-columns: 170px minmax(0, 1fr);
        }
        form.adm-form .card-body .row:not(#subjectContent .row):not(#editSubjectSection .row):has(> .col-md-2.text-center) > .col-md-2,
        form.adm-form .card-body .row:not(#subjectContent .row):not(#editSubjectSection .row):has(> .col-md-2.text-center) > .col-md-10 {
            grid-column: auto;
        }
    }

    /* Quick form payment section: 4 summary cards in one row, payment fields 3 per row */
    form.adm-form #quickPaymentSection .quick-summary-row > * { grid-column: span 6; }
    form.adm-form #quickPaymentSection .quick-summary-row .rounded-4 { padding: 12px 16px !important; }
    form.adm-form #quickPaymentSection .quick-pay-row > .col-md-4,
    form.adm-form #quickPaymentSection .quick-pay-row > .col-md-6 { grid-column: span 6; }
    form.adm-form #quickPaymentSection .quick-pay-row > .col-12 { grid-column: span 12; }
    form.adm-form #quickPaymentSection .table td,
    form.adm-form #quickPaymentSection .table th { padding: 6px 10px; }
    form.adm-form #quickPaymentSection .table td .form-control { text-align: right; min-width: 90px; }
    @media (min-width: 1200px) {
        form.adm-form #quickPaymentSection .quick-summary-row > * { grid-column: span 3; }
    }
    @media (min-width: 1400px) {
        form.adm-form #quickPaymentSection .quick-pay-row > .col-md-4,
        form.adm-form #quickPaymentSection .quick-pay-row > .col-md-6 { grid-column: span 4; }
    }

    /* Label on the left, control (plus hint / error text) on the right */
    @media (min-width: 768px) {
        form.adm-form .card-body .row:not(#subjectContent .row):not(#editSubjectSection .row) > [class*="col-"]:not(.text-center):has(> label.form-label) {
            display: grid;
            grid-template-columns: 150px minmax(0, 1fr);
            column-gap: 10px;
            align-items: center;
        }
        form.adm-form .card-body .row:not(#subjectContent .row):not(#editSubjectSection .row) > [class*="col-"]:not(.text-center):has(> label.form-label) > label.form-label {
            grid-column: 1;
            margin: 0;
            line-height: 1.25;
        }
        form.adm-form .card-body .row:not(#subjectContent .row):not(#editSubjectSection .row) > [class*="col-"]:not(.text-center):has(> label.form-label) > :not(label) {
            grid-column: 2;
        }
    }
</style>
