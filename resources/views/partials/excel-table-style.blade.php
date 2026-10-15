{{-- Excel-style grid borders for every list table. Add class "no-grid" to a table to opt out. --}}
<style>
    table.table:not(.no-grid), table.table:not(.no-grid) > :not(caption) > * > * {
        border: 1px solid #9ca3af !important;
    }
    table.table:not(.no-grid) { border-collapse: collapse; }
</style>
