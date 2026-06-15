{{--
    Usage:
    @include('institute.components.pagination', [
        'paginator' => $students,
        'perPage'   => $perPage ?? 20,
    ])
--}}
@php
    $pg      = $paginator;
    $pp      = $perPage ?? 20;
    $options = [10, 20, 50, 100];
@endphp

<div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">

    {{-- Left: showing X-Y of Z --}}
    <div class="small text-muted">
        @if($pg->total() > 0)
            Showing
            <span class="fw-semibold">{{ $pg->firstItem() }}</span>–<span class="fw-semibold">{{ $pg->lastItem() }}</span>
            of <span class="fw-semibold">{{ number_format($pg->total()) }}</span> records
        @else
            No records found
        @endif
    </div>

    {{-- Right: per-page selector + links --}}
    <div class="d-flex align-items-center gap-3 flex-wrap">

        {{-- Per page selector --}}
        <div class="d-flex align-items-center gap-2 small">
            <span class="text-muted">Show</span>
            <select class="form-select form-select-sm py-0" style="width:70px;"
                    onchange="window.location.href=updateQueryParam('per_page', this.value)">
                @foreach($options as $opt)
                    <option value="{{ $opt }}" {{ $pp == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
            <span class="text-muted">per page</span>
        </div>

        {{-- Pagination links --}}
        @if($pg->hasPages())
        <nav>
            <ul class="pagination pagination-sm mb-0">
                {{-- Prev --}}
                <li class="page-item {{ $pg->onFirstPage() ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ $pg->previousPageUrl() }}&per_page={{ $pp }}">
                        <i class="bi bi-chevron-left" style="font-size:10px;"></i>
                    </a>
                </li>

                {{-- Page numbers --}}
                @foreach($pg->getUrlRange(max(1, $pg->currentPage()-2), min($pg->lastPage(), $pg->currentPage()+2)) as $page => $url)
                <li class="page-item {{ $page == $pg->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $url }}&per_page={{ $pp }}">{{ $page }}</a>
                </li>
                @endforeach

                {{-- Next --}}
                <li class="page-item {{ !$pg->hasMorePages() ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ $pg->nextPageUrl() }}&per_page={{ $pp }}">
                        <i class="bi bi-chevron-right" style="font-size:10px;"></i>
                    </a>
                </li>
            </ul>
        </nav>
        @endif
    </div>
</div>

<script>
function updateQueryParam(key, value) {
    const url = new URL(window.location.href);
    url.searchParams.set(key, value);
    url.searchParams.set('page', '1'); // reset to page 1
    return url.toString();
}
</script>
