@extends($layout ?? 'institute.layout')
@section('title', 'Global Student Search')
@section('breadcrumb', 'Students / Global Search')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-search-heart me-2 text-primary"></i>Global Student Search</h4>
        <small class="text-muted">Search student through name, father, mother, mobile, email, student ID, roll no, enrollment no</small>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <select id="globalSearchSessionFilter" class="form-select form-select-sm" style="min-width:180px;">
            <option value="">All Sessions</option>
            @foreach($sessions as $session)
                <option value="{{ $session->id }}" {{ (string) $sessionId === (string) $session->id ? 'selected' : '' }}>
                    {{ $session->name }}{{ $session->is_active ? ' (Active)' : '' }}
                </option>
            @endforeach
        </select>
        <a href="{{ route($searchRoute ?? 'students.search') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-clockwise"></i>
        </a>
        <a href="{{ route($indexRoute ?? 'students.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-people me-1"></i> {{ $listLabel ?? 'All Students' }}
        </a>
    </div>
</div>

<div id="search-results" data-search-url="{{ route($searchRoute ?? 'students.search') }}">
    @include('institute.students._global-search-results')
</div>
@endsection

@push('scripts')
<script>
let globalSearchTimer;
let globalSearchController = null;
let lastFocusedInputName = null;

function updateQueryParam(key, value) {
    const url = new URL(window.location.href);
    url.searchParams.set(key, value);
    url.searchParams.set('page', '1');
    return url.toString();
}

document.addEventListener('focus', function (e) {
    if (e.target.classList.contains('global-search-input')) {
        lastFocusedInputName = e.target.name;
    }
}, true);

function fetchSearchResults() {
    const container = document.getElementById('search-results');
    if (!container) return;

    if (globalSearchController) globalSearchController.abort();
    globalSearchController = new AbortController();

    const params = new URLSearchParams();
    document.querySelectorAll('.global-search-input').forEach(function (input) {
        if (input.value.trim()) params.set(input.name, input.value.trim());
    });
    const sessionFilter = document.getElementById('globalSearchSessionFilter');
    if (sessionFilter && sessionFilter.value) params.set('session_id', sessionFilter.value);

    const url = container.dataset.searchUrl + '?' + params.toString();
    history.replaceState(null, '', url);

    fetch(url, {
        signal: globalSearchController.signal,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) { return r.text(); })
    .then(function (html) {
        container.innerHTML = html;
        if (lastFocusedInputName) {
            const input = container.querySelector('.global-search-input[name="' + lastFocusedInputName + '"]');
            if (input) {
                input.focus();
                const len = input.value.length;
                input.setSelectionRange(len, len);
            }
        }
        globalSearchController = null;
    })
    .catch(function (err) {
        if (err.name !== 'AbortError') console.error(err);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('global-search-input')) {
            clearTimeout(globalSearchTimer);
            globalSearchTimer = setTimeout(function () {
                var hasEnough = false;
                var anyFilled = false;
                document.querySelectorAll('.global-search-input').forEach(function (inp) {
                    var len = inp.value.trim().length;
                    if (len >= 2) hasEnough = true;
                    if (len > 0) anyFilled = true;
                });
                // Fire if any field has 2+ chars, OR all fields are empty (reset list)
                if (hasEnough || !anyFilled) fetchSearchResults();
            }, 550);
        }
    });

    const sessionFilter = document.getElementById('globalSearchSessionFilter');
    if (sessionFilter) {
        sessionFilter.addEventListener('change', function () {
            clearTimeout(globalSearchTimer);
            fetchSearchResults();
        });
    }
});
</script>
@endpush
