@extends('institute.layout')
@section('title', $title)
@section('breadcrumb', 'Students / ' . $title)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">{{ $title }}</h4>
        <small class="text-muted">{{ $desc }}</small>
    </div>
    <a href="{{ route('students.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-people me-1"></i> All Students
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 text-center">

                <div class="mb-3">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center"
                         style="width:64px;height:64px;">
                        <i class="bi {{ $icon }} text-primary fs-3"></i>
                    </div>
                </div>

                <h5 class="fw-bold mb-1">{{ $title }}</h5>
                <p class="text-muted small mb-4">{{ $desc }}</p>

                <div class="position-relative text-start">
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" id="studentSearch"
                               class="form-control border-start-0 ps-0"
                               placeholder="Name, Father, Mother, Mobile, Email, Roll No, Student ID..."
                               autocomplete="off">
                    </div>
                    <div id="searchResults"
                         class="list-group position-absolute w-100 shadow"
                         style="z-index:1050; display:none; max-height:320px; overflow-y:auto; top:100%; margin-top:4px;">
                    </div>
                </div>

                <p class="text-muted small mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Only students from the current active session will be shown
                </p>
            </div>
        </div>

        <div class="d-flex justify-content-center gap-3 mt-3">
            @if($mode !== 'profile')
            <a href="{{ route('students.search') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-person-badge me-1"></i> Search Student
            </a>
            @endif
            @if($mode !== 'wallet')
            <a href="{{ route('students.wallet') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-wallet2 me-1"></i> Student Wallet
            </a>
            @endif
            @if($mode !== 'history')
            <a href="{{ route('students.history') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-receipt me-1"></i> Fee History
            </a>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const mode = @json($mode);
    const ajaxUrl = @json(route('students.ajax-search'));
    const routeMap = {
        profile: @json(route('admissions.show', '__ID__')),
        wallet: @json(route('fee.wallet.student', '__ID__')),
        history: @json(route('fee.student-history', '__ID__')),
    };

    function redirectTo(id) {
        window.location.href = routeMap[mode].replace('__ID__', id);
    }

    const input = document.getElementById('studentSearch');
    const results = document.getElementById('searchResults');
    let timer;

    input.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) {
            results.style.display = 'none';
            return;
        }

        timer = setTimeout(() => {
            fetch(ajaxUrl + '?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    if (!data.length) {
                        results.innerHTML = '<div class="list-group-item text-muted text-center py-3">'
                            + '<i class="bi bi-search me-2"></i>No student found</div>';
                    } else {
                        results.innerHTML = data.map(s => {
                            const parents = [
                                s.father_name ? `<span>Father: <strong>${s.father_name}</strong></span>` : '',
                                s.mother_name ? `<span>Mother: <strong>${s.mother_name}</strong></span>` : '',
                            ].filter(Boolean).join(' &nbsp;|&nbsp; ');

                            return `
                            <button type="button"
                                    class="list-group-item list-group-item-action py-2 px-3"
                                    onclick="selectStudent(${s.id})">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div style="min-width:0;">
                                        <div class="fw-semibold" style="font-size:13px;">${s.name}</div>
                                        <div class="text-muted" style="font-size:11px;">
                                            ${s.student_uid}${s.course ? ' &nbsp;•&nbsp; ' + s.course : ''}${s.stream ? ' &nbsp;•&nbsp; ' + s.stream : ''}
                                        </div>
                                        ${parents ? `<div class="text-secondary" style="font-size:11px;">${parents}</div>` : ''}
                                    </div>
                                    <small class="text-muted flex-shrink-0 mt-1">${s.mobile ?? ''}</small>
                                </div>
                            </button>`;
                        }).join('');
                    }
                    results.style.display = 'block';
                });
        }, 300);
    });

    window.selectStudent = function (id) {
        redirectTo(id);
    };

    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !results.contains(e.target)) {
            results.style.display = 'none';
        }
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            results.style.display = 'none';
        }
    });
})();
</script>
@endpush
@endsection
