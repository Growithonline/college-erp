@extends('partner.layout')
@section('title', 'My Profile')
@section('breadcrumb', 'My Profile')
@section('content')

<style>
.profile-avatar { width:80px;height:80px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:700;color:#fff;background:linear-gradient(135deg,#5c3109,#854F0B);margin:0 auto 12px;flex-shrink:0; }
.info-row { display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-bottom:1px solid #f1f5f9; }
.info-row:last-child { border-bottom:none; }
.info-icon { width:32px;height:32px;border-radius:8px;background:#fef3e2;color:#854F0B;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0; }
.info-label { font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.4px;line-height:1; }
.info-val { font-size:13px;color:#1e293b;font-weight:500;margin-top:2px; }
.pwd-eye { position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;padding:0;line-height:1; }
.strength-bar { height:4px;border-radius:2px;background:#e2e8f0;overflow:hidden; }
.strength-fill { height:100%;border-radius:2px;width:0;transition:width .3s,background .3s; }
</style>

<div class="row g-4">
    {{-- Left: Profile Info --}}
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4 text-center">
                <div class="profile-avatar">{{ strtoupper(substr($partner->name, 0, 1)) }}</div>
                <h5 class="fw-bold mb-1" style="color:#1e293b;">{{ $partner->name }}</h5>
                <span class="badge px-3 py-1 rounded-pill mb-3"
                      style="background:#fef3e2;color:#854F0B;font-size:12px;">
                    <i class="bi bi-person-badge me-1"></i>Channel Partner
                </span>

                <div class="text-start mt-3">
                    <div class="info-row">
                        <div class="info-icon"><i class="bi bi-envelope-fill"></i></div>
                        <div>
                            <div class="info-label">Email</div>
                            <div class="info-val">{{ $partner->email ?: '—' }}</div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="bi bi-telephone-fill"></i></div>
                        <div>
                            <div class="info-label">Mobile</div>
                            <div class="info-val">{{ $partner->mobile ?: '—' }}</div>
                        </div>
                    </div>
                    @if($partner->commission_percent > 0)
                    <div class="info-row">
                        <div class="info-icon"><i class="bi bi-percent"></i></div>
                        <div>
                            <div class="info-label">Commission Rate</div>
                            <div class="info-val">{{ $partner->commission_percent }}%</div>
                        </div>
                    </div>
                    @endif
                    <div class="info-row">
                        <div class="info-icon"><i class="bi bi-mortarboard-fill"></i></div>
                        <div>
                            <div class="info-label">Institute</div>
                            <div class="info-val">{{ $partner->institute?->name ?? '—' }}</div>
                        </div>
                    </div>
                    @if($partner->city || $partner->state)
                    <div class="info-row">
                        <div class="info-icon"><i class="bi bi-geo-alt-fill"></i></div>
                        <div>
                            <div class="info-label">Location</div>
                            <div class="info-val">{{ collect([$partner->city, $partner->state])->filter()->implode(', ') }}</div>
                        </div>
                    </div>
                    @endif
                    @if($partner->address)
                    <div class="info-row">
                        <div class="info-icon"><i class="bi bi-house-fill"></i></div>
                        <div>
                            <div class="info-label">Address</div>
                            <div class="info-val">{{ $partner->address }}</div>
                        </div>
                    </div>
                    @endif
                    <div class="info-row">
                        <div class="info-icon"><i class="bi bi-shield-check"></i></div>
                        <div>
                            <div class="info-label">Account Status</div>
                            <div class="info-val">
                                @if($partner->status)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:11px;">Active</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:11px;">Inactive</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Change Password --}}
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:34px;height:34px;border-radius:8px;background:#fef3e2;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-lock-fill" style="color:#854F0B;font-size:15px;"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:14px;color:#1e293b;">Change Password</div>
                        <div class="text-muted" style="font-size:11px;">Update your login password</div>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('partner.change-password.update') }}" id="pwdForm">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">Current Password</label>
                        <div class="position-relative">
                            <input type="password" name="current_password" id="curPwd" class="form-control @error('current_password') is-invalid @enderror"
                                   placeholder="Enter current password" autocomplete="current-password">
                            <button type="button" class="pwd-eye" onclick="togglePwd('curPwd',this)"><i class="bi bi-eye"></i></button>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">New Password</label>
                        <div class="position-relative">
                            <input type="password" name="password" id="newPwd" class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Min 8 characters" autocomplete="new-password"
                                   oninput="checkStrength(this.value)">
                            <button type="button" class="pwd-eye" onclick="togglePwd('newPwd',this)"><i class="bi bi-eye"></i></button>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mt-2">
                            <div class="d-flex gap-1 mb-1">
                                <div class="strength-bar flex-fill"><div class="strength-fill" id="sb1"></div></div>
                                <div class="strength-bar flex-fill"><div class="strength-fill" id="sb2"></div></div>
                                <div class="strength-bar flex-fill"><div class="strength-fill" id="sb3"></div></div>
                                <div class="strength-bar flex-fill"><div class="strength-fill" id="sb4"></div></div>
                            </div>
                            <div id="strLabel" style="font-size:11px;color:#94a3b8;"></div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size:13px;">Confirm New Password</label>
                        <div class="position-relative">
                            <input type="password" name="password_confirmation" id="confPwd" class="form-control"
                                   placeholder="Re-enter new password" autocomplete="new-password"
                                   oninput="checkMatch()">
                            <button type="button" class="pwd-eye" onclick="togglePwd('confPwd',this)"><i class="bi bi-eye"></i></button>
                        </div>
                        <div id="matchMsg" style="font-size:11px;margin-top:4px;"></div>
                    </div>
                    <button type="submit" class="btn w-100 text-white fw-semibold" style="background:#854F0B;border-radius:10px;padding:10px;">
                        <i class="bi bi-shield-lock-fill me-2"></i>Update Password
                    </button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body p-4">
                <div class="fw-bold mb-2" style="font-size:13px;color:#1e293b;"><i class="bi bi-info-circle-fill me-1" style="color:#854F0B;"></i>Password Tips</div>
                <ul class="mb-0 ps-3" style="font-size:12px;color:#64748b;line-height:1.9;">
                    <li>Use at least 8 characters</li>
                    <li>Mix uppercase, lowercase, numbers and symbols</li>
                    <li>Don't reuse passwords from other sites</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function togglePwd(id,btn){var i=document.getElementById(id);if(i.type==='password'){i.type='text';btn.innerHTML='<i class="bi bi-eye-slash"></i>';}else{i.type='password';btn.innerHTML='<i class="bi bi-eye"></i>';}}
function checkStrength(v){var s=0;if(v.length>=8)s++;if(/[A-Z]/.test(v)&&/[a-z]/.test(v))s++;if(/\d/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;var colors=['#ef4444','#f59e0b','#3b82f6','#10b981'];var labels=['Weak','Fair','Good','Strong'];for(var i=1;i<=4;i++){var el=document.getElementById('sb'+i);el.style.width=i<=s?'100%':'0';el.style.background=i<=s?colors[s-1]:'transparent';}document.getElementById('strLabel').textContent=v.length?labels[s-1]+' password':'';}
function checkMatch(){var np=document.getElementById('newPwd').value,cp=document.getElementById('confPwd').value,m=document.getElementById('matchMsg');if(!cp)return m.textContent='';if(np===cp){m.style.color='#10b981';m.textContent='Passwords match';}else{m.style.color='#ef4444';m.textContent='Passwords do not match';}}
</script>
@endpush
@endsection
