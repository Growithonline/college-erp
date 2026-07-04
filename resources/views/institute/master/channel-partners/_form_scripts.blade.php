@include('partials._india-geo')

@push('scripts')
<script>
document.getElementById('pcanDiscount')?.addEventListener('change', function () {
    document.getElementById('pdiscountPctRow').classList.toggle('d-none', !this.checked);
});

// Fee type restriction toggle
document.getElementById('prestrictFeeTypes')?.addEventListener('change', function () {
    document.getElementById('pfeeTypeRestrictRow').classList.toggle('d-none', !this.checked);
});

function checkAll(prefix) {
    document.querySelectorAll('[id^="' + prefix + '"]').forEach(function (cb) { cb.checked = true; });
}
function uncheckAll(prefix) {
    document.querySelectorAll('[id^="' + prefix + '"]').forEach(function (cb) { cb.checked = false; });
}

// Course Type filter pills
(function () {
    var activeTypeId = '';
    document.querySelectorAll('#pCourseTypeFilters .p-course-type-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            activeTypeId = this.dataset.typeId;
            document.querySelectorAll('#pCourseTypeFilters .p-course-type-btn').forEach(function (b) {
                b.classList.remove('btn-primary', 'active');
                b.classList.add('btn-outline-primary');
            });
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary', 'active');
            document.querySelectorAll('#pCourseCheckboxes .p-course-item').forEach(function (item) {
                item.style.display = (!activeTypeId || item.dataset.typeId === activeTypeId) ? '' : 'none';
            });
        });
    });
})();

function partnerSelectVisible() {
    document.querySelectorAll('#pCourseCheckboxes .p-course-item').forEach(function (item) {
        if (item.style.display !== 'none') {
            var cb = item.querySelector('input[type="checkbox"]');
            if (cb) cb.checked = true;
        }
    });
}
</script>
@endpush
