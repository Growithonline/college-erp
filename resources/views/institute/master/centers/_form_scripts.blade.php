@push('scripts')
<script>
// Discount toggle
document.getElementById('canDiscount')?.addEventListener('change', function () {
    document.getElementById('discountPctRow').classList.toggle('d-none', !this.checked);
});

// Fee type restriction toggle
document.getElementById('restrictFeeTypes')?.addEventListener('change', function () {
    document.getElementById('feeTypeRestrictRow').classList.toggle('d-none', !this.checked);
});

// Bulk check helpers for course / session / mode checkboxes
function checkAll(prefix) {
    document.querySelectorAll('[id^="' + prefix + '"]').forEach(function (cb) {
        cb.checked = true;
    });
}
function uncheckAll(prefix) {
    document.querySelectorAll('[id^="' + prefix + '"]').forEach(function (cb) {
        cb.checked = false;
    });
}

// Course Type filter pills
(function () {
    var activeTypeId = '';
    document.querySelectorAll('#courseTypeFilters .course-type-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            activeTypeId = this.dataset.typeId;
            document.querySelectorAll('#courseTypeFilters .course-type-btn').forEach(function (b) {
                b.classList.remove('btn-primary', 'active');
                b.classList.add('btn-outline-primary');
            });
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary', 'active');
            document.querySelectorAll('#courseCheckboxes .course-item').forEach(function (item) {
                item.style.display = (!activeTypeId || item.dataset.typeId === activeTypeId) ? '' : 'none';
            });
        });
    });
})();

function centerSelectVisible() {
    document.querySelectorAll('#courseCheckboxes .course-item').forEach(function (item) {
        if (item.style.display !== 'none') {
            var cb = item.querySelector('input[type="checkbox"]');
            if (cb) cb.checked = true;
        }
    });
}

// AJAX submit (create only — edit uses normal POST)
var form = document.getElementById('centerForm');
if (form && form.action.includes('/centers') && !form.action.includes('/centers/')) {
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearFormErrors();

        var btn = document.getElementById('centerSubmitBtn');
        var orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…';

        try {
            var res = await fetch(this.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: new FormData(this),
            });

            var data = await res.json();

            if (res.ok) {
                window.location.href = data.redirect;
                return;
            }

            if (res.status === 422 && data.errors) {
                showFieldErrors(data.errors);
            } else {
                window.showToast?.(data.message || 'Something went wrong.', 'danger');
            }
        } catch (err) {
            window.showToast?.('Network error. Please check your connection.', 'danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    });
}

function clearFormErrors() {
    document.querySelectorAll('.ajax-field-error').forEach(function (el) { el.remove(); });
    document.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
}

function showFieldErrors(errors) {
    for (var field in errors) {
        var input = document.querySelector('[name="' + field + '"]');
        if (!input) continue;
        input.classList.add('is-invalid');
        var div = document.createElement('div');
        div.className = 'invalid-feedback ajax-field-error';
        div.textContent = errors[field][0];
        input.parentNode.appendChild(div);
    }
    var first = document.querySelector('.is-invalid');
    if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
</script>
@endpush
