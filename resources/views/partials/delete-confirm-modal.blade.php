{{--
  Shared Delete Confirmation Modal
  Usage: @include('partials.delete-confirm-modal')
  JS:    deleteConfirm(formElement, 'Title', 'Subtext')
         deleteConfirm('form-id',   'Title', 'Subtext')
  Handles 422 errors inline (no full-page error).
--}}
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center pt-4 pb-2 px-4">
                <div class="mb-3">
                    <span class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width:56px;height:56px;">
                        <i class="bi bi-trash text-danger fs-4"></i>
                    </span>
                </div>
                <h6 class="fw-semibold mb-1" id="dcmTitle">Delete?</h6>
                <p class="text-muted mb-0" id="dcmSubtext" style="font-size:13px;"></p>
            </div>
            <div id="dcmError" class="alert alert-danger mx-3 mt-2 mb-0 py-2 d-none" style="font-size:12px;">
                <i class="bi bi-exclamation-circle me-1"></i>
                <span id="dcmErrorMsg"></span>
            </div>
            <div class="modal-footer border-0 justify-content-center gap-2 pb-4 pt-2">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger px-4" id="dcmConfirmBtn">
                    <span id="dcmBtnLabel">Delete</span>
                    <span id="dcmBtnSpinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('deleteConfirmModal');
    if (!modalEl) return;

    const modal   = new bootstrap.Modal(modalEl);
    let _action   = null;
    let _token    = null;
    let _onSuccess = null;

    window.deleteConfirm = function (formOrId, title, subtext, onSuccess) {
        const form = (typeof formOrId === 'string')
            ? document.getElementById(formOrId)
            : formOrId;
        if (!form) return;

        _action    = form.action;
        _token     = form.querySelector('[name="_token"]')?.value;
        _onSuccess = onSuccess || null;

        document.getElementById('dcmTitle').textContent   = title   || 'Delete this item?';
        document.getElementById('dcmSubtext').textContent = subtext || '';
        document.getElementById('dcmError').classList.add('d-none');
        document.getElementById('dcmBtnLabel').textContent = 'Delete';
        document.getElementById('dcmBtnSpinner').classList.add('d-none');
        document.getElementById('dcmConfirmBtn').disabled  = false;
        modal.show();
    };

    document.getElementById('dcmConfirmBtn').addEventListener('click', async function () {
        if (!_action) return;

        const btn     = this;
        const spinner = document.getElementById('dcmBtnSpinner');
        const label   = document.getElementById('dcmBtnLabel');
        const errBox  = document.getElementById('dcmError');
        const errMsg  = document.getElementById('dcmErrorMsg');

        btn.disabled = true;
        label.textContent = 'Deleting…';
        spinner.classList.remove('d-none');
        errBox.classList.add('d-none');

        try {
            const res = await fetch(_action, {
                method : 'POST',
                headers: { 'X-CSRF-TOKEN': _token, 'Accept': 'application/json' },
                body   : new URLSearchParams({ _token: _token, _method: 'DELETE' }),
            });

            if (res.ok) {
                modal.hide();
                if (_onSuccess) _onSuccess();
                else window.location.reload();
            } else {
                const data = await res.json().catch(() => ({}));
                errMsg.textContent = data.message || 'Could not delete. Please try again.';
                errBox.classList.remove('d-none');
                btn.disabled = false;
                label.textContent = 'Delete';
                spinner.classList.add('d-none');
            }
        } catch (e) {
            errMsg.textContent = 'Network error. Please try again.';
            errBox.classList.remove('d-none');
            btn.disabled = false;
            label.textContent = 'Delete';
            spinner.classList.add('d-none');
        }
    });
});
</script>
