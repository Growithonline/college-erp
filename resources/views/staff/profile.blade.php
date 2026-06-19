@extends('staff.layout')
@section('title', 'My Profile')
@section('breadcrumb', 'My Profile')

@section('content')
<style>
.profile-avatar { width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg,#1D9E75,#0f4c81); display:flex; align-items:center; justify-content:center; font-size:32px; font-weight:700; color:#fff; flex-shrink:0; }
.info-row { display:flex; align-items:flex-start; gap:10px; padding:12px 0; border-bottom:1px solid #f1f5f9; }
.info-row:last-child { border-bottom:none; }
.info-icon { width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0; }
.info-label { font-size:11px; color:#94a3b8; font-weight:600; text-transform:uppercase; letter-spacing:.4px; }
.info-val { font-size:14px; color:#1e293b; font-weight:500; margin-top:2px; }
.section-card { border:none; border-radius:14px; box-shadow:0 1px 10px rgba(0,0,0,.07); }
.section-card .card-header { background:#fff; border-bottom:1px solid #f1f5f9; border-radius:14px 14px 0 0 !important; padding:16px 20px; }
</style>

<div class="row g-4">

    {{-- LEFT: Profile Info --}}
    <div class="col-12 col-lg-4">
        <div class="card section-card">
            <div class="card-body p-4">
                {{-- Avatar + Name --}}
                <div class="d-flex flex-column align-items-center text-center mb-4">
                    <div class="profile-avatar mb-3">
                        {{ strtoupper(substr($staff->name, 0, 1)) }}
                    </div>
                    <h5 class="fw-bold mb-1" style="font-size:1.1rem;">{{ $staff->name }}</h5>
                    <span class="badge rounded-pill px-3 py-1" style="background:#f0fdf4;color:#1D9E75;font-size:12px;">
                        <i class="bi bi-shield-check me-1"></i>{{ $staff->role?->name ?? 'Staff' }}
                    </span>
                </div>

                {{-- Details --}}
                <div class="info-row">
                    <div class="info-icon" style="background:#eff6ff;">
                        <i class="bi bi-envelope-fill text-primary" style="font-size:14px;"></i>
                    </div>
                    <div>
                        <div class="info-label">Email</div>
                        <div class="info-val">{{ $staff->email ?? '—' }}</div>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-icon" style="background:#fff7ed;">
                        <i class="bi bi-telephone-fill text-warning" style="font-size:14px;color:#f97316 !important;"></i>
                    </div>
                    <div>
                        <div class="info-label">Mobile</div>
                        <div class="info-val">{{ $staff->mobile ?? '—' }}</div>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-icon" style="background:#f5f3ff;">
                        <i class="bi bi-building-fill" style="font-size:14px;color:#8b5cf6;"></i>
                    </div>
                    <div>
                        <div class="info-label">Institute</div>
                        <div class="info-val">{{ $staff->institute?->name ?? '—' }}</div>
                    </div>
                </div>

                @if($staff->joining_date)
                <div class="info-row">
                    <div class="info-icon" style="background:#f0fdf4;">
                        <i class="bi bi-calendar-check-fill text-success" style="font-size:14px;"></i>
                    </div>
                    <div>
                        <div class="info-label">Joining Date</div>
                        <div class="info-val">{{ \Carbon\Carbon::parse($staff->joining_date)->format('d M Y') }}</div>
                    </div>
                </div>
                @endif

                @if($staff->address)
                <div class="info-row">
                    <div class="info-icon" style="background:#fef2f2;">
                        <i class="bi bi-geo-alt-fill" style="font-size:14px;color:#ef4444;"></i>
                    </div>
                    <div>
                        <div class="info-label">Address</div>
                        <div class="info-val">{{ $staff->address }}</div>
                    </div>
                </div>
                @endif

                <div class="info-row">
                    <div class="info-icon" style="background:#f8fafc;">
                        <i class="bi bi-circle-fill" style="font-size:10px;color:{{ $staff->status ? '#22c55e' : '#ef4444' }};"></i>
                    </div>
                    <div>
                        <div class="info-label">Status</div>
                        <div class="info-val" style="color:{{ $staff->status ? '#16a34a' : '#dc2626' }};">
                            {{ $staff->status ? 'Active' : 'Inactive' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT: Change Password --}}
    <div class="col-12 col-lg-8">
        <div class="card section-card">
            <div class="card-header d-flex align-items-center gap-2">
                <div style="width:34px;height:34px;border-radius:9px;background:#fffbeb;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-key-fill" style="color:#f59e0b;font-size:15px;"></i>
                </div>
                <div>
                    <div class="fw-bold" style="font-size:14px;">Change Password</div>
                    <div class="text-muted" style="font-size:11px;">Update your login password</div>
                </div>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('staff.change-password.update') }}" id="pwdForm">
                    @csrf
                    <input type="hidden" name="_from_profile" value="1">

                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size:13px;">Current Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-lock text-muted"></i>
                            </span>
                            <input type="password" name="current_password" id="currentPwd"
                                   class="form-control border-start-0 @error('current_password') is-invalid @enderror"
                                   placeholder="Enter current password">
                            <button type="button" class="input-group-text bg-light border-start-0" onclick="togglePwd('currentPwd', this)">
                                <i class="bi bi-eye text-muted"></i>
                            </button>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size:13px;">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-lock-fill text-muted"></i>
                            </span>
                            <input type="password" name="password" id="newPwd"
                                   class="form-control border-start-0 @error('password') is-invalid @enderror"
                                   placeholder="Minimum 8 characters">
                            <button type="button" class="input-group-text bg-light border-start-0" onclick="togglePwd('newPwd', this)">
                                <i class="bi bi-eye text-muted"></i>
                            </button>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mt-2 d-flex gap-1" id="strengthBars">
                            <div class="flex-fill rounded" style="height:4px;background:#e2e8f0;" id="s1"></div>
                            <div class="flex-fill rounded" style="height:4px;background:#e2e8f0;" id="s2"></div>
                            <div class="flex-fill rounded" style="height:4px;background:#e2e8f0;" id="s3"></div>
                            <div class="flex-fill rounded" style="height:4px;background:#e2e8f0;" id="s4"></div>
                        </div>
                        <div class="text-muted mt-1" id="strengthLabel" style="font-size:11px;"></div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size:13px;">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-lock-fill text-muted"></i>
                            </span>
                            <input type="password" name="password_confirmation" id="confirmPwd"
                                   class="form-control border-start-0"
                                   placeholder="Repeat new password">
                            <button type="button" class="input-group-text bg-light border-start-0" onclick="togglePwd('confirmPwd', this)">
                                <i class="bi bi-eye text-muted"></i>
                            </button>
                        </div>
                        <div class="mt-1" id="matchMsg" style="font-size:11px;"></div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4" style="font-size:13px;">
                            <i class="bi bi-check-lg me-1"></i> Update Password
                        </button>
                        <button type="reset" class="btn btn-light px-3" style="font-size:13px;">
                            Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Security Tips --}}
        <div class="card section-card mt-4">
            <div class="card-body px-4 py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-shield-lock-fill text-success"></i>
                    <span class="fw-bold" style="font-size:13px;">Password Tips</span>
                </div>
                <ul class="mb-0 ps-3" style="font-size:12px;color:#64748b;line-height:2;">
                    <li>Use at least <strong>8 characters</strong></li>
                    <li>Mix <strong>uppercase, lowercase, numbers &amp; symbols</strong></li>
                    <li>Avoid using your name or email in the password</li>
                    <li>Don't reuse passwords from other accounts</li>
                </ul>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function togglePwd(id, btn) {
    var el = document.getElementById(id);
    var icon = btn.querySelector('i');
    if (el.type === 'password') {
        el.type = 'text';
        icon.className = 'bi bi-eye-slash text-muted';
    } else {
        el.type = 'password';
        icon.className = 'bi bi-eye text-muted';
    }
}

document.getElementById('newPwd').addEventListener('input', function () {
    var val = this.value;
    var score = 0;
    if (val.length >= 8)  score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    var colors  = ['#ef4444','#f97316','#f59e0b','#22c55e'];
    var labels  = ['Weak','Fair','Good','Strong'];
    var barEls  = ['s1','s2','s3','s4'];

    barEls.forEach(function(id, i) {
        document.getElementById(id).style.background = i < score ? colors[score - 1] : '#e2e8f0';
    });

    var lbl = document.getElementById('strengthLabel');
    lbl.textContent = val.length ? labels[score - 1] || '' : '';
    lbl.style.color = score ? colors[score - 1] : '#94a3b8';
    checkMatch();
});

document.getElementById('confirmPwd').addEventListener('input', checkMatch);

function checkMatch() {
    var np = document.getElementById('newPwd').value;
    var cp = document.getElementById('confirmPwd').value;
    var msg = document.getElementById('matchMsg');
    if (!cp) { msg.textContent = ''; return; }
    if (np === cp) {
        msg.textContent = '✓ Passwords match';
        msg.style.color = '#16a34a';
    } else {
        msg.textContent = '✕ Passwords do not match';
        msg.style.color = '#dc2626';
    }
}
</script>
@endpush

@endsection
