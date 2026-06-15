@extends('institute.layout')
@section('title','Staff Roles')
@section('breadcrumb','Master / Staff / Roles')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Staff Roles</h4>
        <small class="text-muted">{{ $roles->count() }} roles configured</small>
    </div>
    <a href="{{ route('master.staff-roles.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Custom Role
    </a>
</div>

<div class="row g-3">
    @foreach($roles as $role)
    @php
        $labels       = \App\Models\StaffRole::permissionLabels();
        $enabledPerms = collect((array) ($role->permissions ?? []))
            ->filter()
            ->map(fn($v, $k) => $labels[$k] ?? ucwords(str_replace('_', ' ', $k)));
        $totalEnabled = $enabledPerms->count();
        $visible      = $enabledPerms->take(5);
        $hidden       = $enabledPerms->skip(5);
    @endphp
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">

                {{-- Header --}}
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="fw-bold mb-0">
                            {{ $role->name }}
                            @if($role->is_system)
                                <span class="badge bg-secondary-subtle text-secondary border ms-1 fw-normal">System</span>
                            @endif
                        </h6>
                        <small class="text-muted">
                            {{ $role->staff_members_count }} staff
                            &nbsp;·&nbsp;
                            <span class="{{ $totalEnabled > 0 ? 'text-success fw-semibold' : '' }}">
                                {{ $totalEnabled }} permission{{ $totalEnabled !== 1 ? 's' : '' }}
                            </span>
                        </small>
                    </div>
                    <div class="d-flex gap-1">
                        <a href="{{ route('master.staff-roles.edit', $role) }}"
                           class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @if($role->staff_members_count == 0)
                        <form method="POST" action="{{ route('master.staff-roles.destroy', $role) }}"
                              onsubmit="return confirm('Delete this role?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>

                {{-- Permissions --}}
                @if($totalEnabled === 0)
                    <small class="text-muted fst-italic">No permissions assigned.</small>
                @else
                <div class="d-flex flex-wrap gap-1 align-items-center">
                    @foreach($visible as $label)
                        <span class="badge bg-success-subtle text-success border border-success-subtle"
                              style="font-size:10px;">{{ $label }}</span>
                    @endforeach

                    @if($hidden->isNotEmpty())
                    <div class="d-none" id="perm-extra-{{ $role->id }}">
                        @foreach($hidden as $label)
                            <span class="badge bg-success-subtle text-success border border-success-subtle"
                                  style="font-size:10px;">{{ $label }}</span>
                        @endforeach
                    </div>
                    <button type="button"
                            class="btn btn-link btn-sm p-0 perm-toggle"
                            style="font-size:11px;text-decoration:none;color:#6b7280;"
                            data-target="perm-extra-{{ $role->id }}"
                            data-more="+{{ $hidden->count() }} more"
                            data-less="Show less">
                        +{{ $hidden->count() }} more
                    </button>
                    @endif
                </div>
                @endif

            </div>
        </div>
    </div>
    @endforeach
</div>

@push('scripts')
<script>
document.querySelectorAll('.perm-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var el = document.getElementById(this.dataset.target);
        if (!el) return;
        var isOpen = !el.classList.contains('d-none');
        el.classList.toggle('d-none', isOpen);
        this.textContent = isOpen ? this.dataset.more : this.dataset.less;
    });
});
</script>
@endpush
@endsection
