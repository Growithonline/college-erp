<div class="row g-4">

    {{-- Left: sticky panel --}}
    <div class="col-md-4 col-xl-3">
        <div style="position:sticky;top:68px;" class="d-flex flex-column gap-3">

            <div class="card border-0 bg-light">
                <div class="card-body">
                    <label class="form-label fw-semibold small">Role Name <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                           value="{{ old('name', $staffRole->name ?? '') }}"
                           class="form-control @error('name') is-invalid @enderror"
                           placeholder="e.g. ADMISSION COORDINATOR"
                           style="text-transform:uppercase">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="card border-0 bg-light text-center">
                <div class="card-body">
                    <div class="small text-muted mb-1">Permissions enabled</div>
                    <div class="fw-bold fs-2 text-primary" id="perm-count-display">
                        @php
                            $initialCount = collect($permissions)
                                ->filter(fn($label, $key) => old("perm_{$key}", $staffRole->permissions[$key] ?? false))
                                ->count();
                        @endphp
                        {{ $initialCount }}
                    </div>
                    <div class="small text-muted">out of {{ count($permissions) }}</div>
                    <div class="progress mt-2" style="height:4px;">
                        <div class="progress-bar bg-primary" id="perm-progress"
                             style="width:{{ count($permissions) > 0 ? round($initialCount / count($permissions) * 100) : 0 }}%">
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>
                    {{ isset($staffRole) ? 'Update Role' : 'Save Role' }}
                </button>
                <a href="{{ route('master.staff-roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </div>
    </div>

    {{-- Right: permissions accordion --}}
    <div class="col-md-8 col-xl-9">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="fw-semibold mb-0">Permissions</h6>
                <small class="text-muted">{{ count($permissionGroups) }} groups &nbsp;·&nbsp; {{ count($permissions) }} total</small>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectAllPermissions(true)">
                    <i class="bi bi-check-all me-1"></i>Select All
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectAllPermissions(false)">
                    <i class="bi bi-x me-1"></i>Clear All
                </button>
            </div>
        </div>

        <div class="accordion" id="permissionAccordion">
            @foreach($permissionGroups as $groupName => $groupPermissions)
                @php
                    $groupId      = \Illuminate\Support\Str::slug($groupName, '-');
                    $enabledCount = collect($groupPermissions)
                        ->filter(fn($label, $key) => old("perm_{$key}", $staffRole->permissions[$key] ?? false))
                        ->count();
                @endphp
                <div class="accordion-item border-0 shadow-sm mb-2 rounded overflow-hidden">
                    <h2 class="accordion-header" id="heading-{{ $groupId }}">
                        <button class="accordion-button collapsed py-2" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapse-{{ $groupId }}"
                                aria-expanded="false" aria-controls="collapse-{{ $groupId }}">
                            <span class="fw-semibold small">{{ $groupName }}</span>
                            <span class="ms-2 badge bg-primary-subtle text-primary border border-primary-subtle perm-group-badge-{{ $groupId }}"
                                  style="font-size:10px;">
                                {{ $enabledCount }}/{{ count($groupPermissions) }}
                            </span>
                        </button>
                    </h2>
                    <div id="collapse-{{ $groupId }}" class="accordion-collapse collapse"
                         aria-labelledby="heading-{{ $groupId }}">
                        <div class="accordion-body bg-white pt-2 pb-3">
                            <div class="d-flex justify-content-end gap-2 mb-2">
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                        onclick="togglePermissionGroup('{{ $groupId }}', true)">Select All</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                        onclick="togglePermissionGroup('{{ $groupId }}', false)">Clear</button>
                            </div>
                            <div class="row g-2">
                                @foreach($groupPermissions as $key => $label)
                                    <div class="col-md-6 col-xl-4">
                                        <div class="d-flex align-items-center p-2 rounded border bg-light">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input permission-group-{{ $groupId }} permission-checkbox"
                                                       type="checkbox"
                                                       name="perm_{{ $key }}" id="perm_{{ $key }}" value="1"
                                                       data-group="{{ $groupId }}"
                                                       {{ old("perm_{$key}", $staffRole->permissions[$key] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="perm_{{ $key }}">
                                                    {{ $label }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</div>

@push('scripts')
<script>
(function () {
    var totalPerms = {{ count($permissions) }};

    function updateCounts() {
        var checked = document.querySelectorAll('.permission-checkbox:checked').length;
        document.getElementById('perm-count-display').textContent = checked;
        var pct = totalPerms > 0 ? Math.round(checked / totalPerms * 100) : 0;
        document.getElementById('perm-progress').style.width = pct + '%';

        var groups = {};
        document.querySelectorAll('.permission-checkbox').forEach(function (cb) {
            var g = cb.dataset.group;
            if (!groups[g]) groups[g] = { checked: 0, total: 0 };
            groups[g].total++;
            if (cb.checked) groups[g].checked++;
        });
        Object.keys(groups).forEach(function (g) {
            var badge = document.querySelector('.perm-group-badge-' + g);
            if (badge) badge.textContent = groups[g].checked + '/' + groups[g].total;
        });
    }

    window.togglePermissionGroup = function (groupId, checked) {
        document.querySelectorAll('.permission-group-' + groupId).forEach(function (cb) {
            cb.checked = checked;
        });
        updateCounts();
    };

    window.selectAllPermissions = function (checked) {
        document.querySelectorAll('.permission-checkbox').forEach(function (cb) {
            cb.checked = checked;
        });
        updateCounts();
    };

    document.querySelectorAll('.permission-checkbox').forEach(function (cb) {
        cb.addEventListener('change', updateCounts);
    });

    updateCounts();
})();
</script>
@endpush
