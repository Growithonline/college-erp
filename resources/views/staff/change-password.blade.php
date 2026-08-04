@extends('staff.layout')
@section('title','Change Password')
@section('breadcrumb','Change Password')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-lock me-2 text-primary"></i>Change Password</h6>
            </div>
            <div class="card-body p-4">
                @if(session('success'))<div class="alert alert-success border-0 py-2 small">{{ session('success') }}</div>@endif
                <form method="POST" action="{{ route('staff.change-password.update') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Current Password</label>
                        <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">New Password</label>
                        <input type="password" id="newPassword" name="password" class="form-control @error('password') is-invalid @enderror"
                               required minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}"
                               title="At least 8 characters, including a letter and a number"
                               autocomplete="new-password">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <ul class="list-unstyled small mt-2 mb-0" id="pwRules">
                            <li data-rule="length" class="text-muted"><i class="bi bi-circle me-1"></i>At least 8 characters</li>
                            <li data-rule="letter" class="text-muted"><i class="bi bi-circle me-1"></i>Contains a letter</li>
                            <li data-rule="number" class="text-muted"><i class="bi bi-circle me-1"></i>Contains a number</li>
                        </ul>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Confirm New Password</label>
                        <input type="password" id="confirmPassword" name="password_confirmation" class="form-control" required autocomplete="new-password">
                        <div class="small mt-2" id="pwMatch"></div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="pwSubmit"><i class="bi bi-check-lg me-1"></i>Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
#pwRules li.valid { color: #198754; }
#pwMatch.valid { color: #198754; }
#pwMatch.invalid { color: #dc3545; }
</style>

<script>
(function () {
    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    const rules = document.getElementById('pwRules');
    const matchHint = document.getElementById('pwMatch');

    function updateRules() {
        const value = newPassword.value;
        const checks = {
            length: value.length >= 8,
            letter: /[A-Za-z]/.test(value),
            number: /[0-9]/.test(value),
        };
        rules.querySelectorAll('li').forEach(function (li) {
            const passed = !!checks[li.dataset.rule];
            li.classList.toggle('valid', passed);
            const icon = li.querySelector('i');
            icon.classList.toggle('bi-circle', !passed);
            icon.classList.toggle('bi-check-circle-fill', passed);
        });
        updateMatch();
    }

    function updateMatch() {
        if (!confirmPassword.value) {
            matchHint.textContent = '';
            matchHint.className = 'small mt-2';
            return;
        }
        const isMatch = newPassword.value === confirmPassword.value;
        matchHint.textContent = isMatch ? 'Passwords match' : 'Passwords do not match';
        matchHint.className = 'small mt-2 ' + (isMatch ? 'valid' : 'invalid');
    }

    newPassword.addEventListener('input', updateRules);
    confirmPassword.addEventListener('input', updateMatch);
})();
</script>
@endsection