@extends('institute.layout')
@section('title', 'Custom Student Report')
@section('breadcrumb', 'Reports / Custom Report')

@section('content')
@php
    $isStaff = auth()->guard('staff')->check();
    $reportRoute = $isStaff ? 'staff.reports.custom-student' : 'reports.custom-student';
    $streamsRoute = $isStaff ? 'staff.reports.streams' : 'reports.streams';
    $valueResolver = app(\App\Http\Controllers\Institute\Reports\ReportController::class);
    $groupedColumns = collect($columns)->groupBy('section', preserveKeys: true);
    $activeColumnFilters = request('column_filters', []);
    $activeColumnFilterCount = collect($activeColumnFilters)->filter(function ($filter) {
        $values = array_filter((array) ($filter['values'] ?? []), fn($value) => $value !== null && $value !== '');
        return !empty($values)
            || trim((string) ($filter['value'] ?? '')) !== ''
            || trim((string) ($filter['from'] ?? '')) !== ''
            || trim((string) ($filter['to'] ?? '')) !== '';
    })->count();
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Custom Student Report</h4>
        <small class="text-muted">{{ $sessionObj?->name ?? 'All Sessions' }} - {{ number_format($totalStudents) }} students</small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#columnModal">
            <i class="bi bi-layout-three-columns me-1"></i> Column Visibility
        </button>
        <button type="submit" form="reportForm" name="export" value="excel" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Excel
        </button>
        <button type="submit" form="reportForm" name="export" value="csv" class="btn btn-outline-success btn-sm">
            <i class="bi bi-filetype-csv me-1"></i> CSV
        </button>
        <button type="submit" form="reportForm" name="export" value="pdf" formtarget="_blank" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-filetype-pdf me-1"></i> PDF
        </button>
    </div>
</div>

<form method="GET" action="{{ route($reportRoute) }}" id="reportForm" autocomplete="off">
    <input type="hidden" name="columns_csv" id="columnsCsv" value="{{ implode(',', $selectedColumns) }}">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Session</label>
                    <select name="session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($sessions as $s)
                            <option value="{{ $s->id }}" {{ (string) $sessionId === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Course</label>
                    <select name="course_id" id="courseFilter" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Class / Stream</label>
                    <select name="stream_id" id="streamFilter" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($streams ?? collect() as $stream)
                            <option value="{{ $stream->id }}" {{ request('stream_id') == $stream->id ? 'selected' : '' }}>{{ $stream->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Gender</label>
                    <select name="gender" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="male" {{ request('gender') === 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ request('gender') === 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ request('gender') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Category</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach(['gen' => 'GEN', 'obc' => 'OBC', 'sc' => 'SC', 'st' => 'ST', 'ews' => 'EWS', 'others' => 'Others'] as $key => $label)
                            <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach(['active', 'inactive', 'detained', 'passed_out', 'transferred', 'cancelled'] as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Name, mobile, roll no, UID...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Rows</label>
                    <select name="per_page" class="form-select form-select-sm">
                        @foreach([20, 50, 100, 500, 1000] as $size)
                            <option value="{{ $size }}" {{ ($perPage ?? 20) == $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#deepFilters">
                        <i class="bi bi-sliders me-1"></i> Deep Filters
                    </button>
                    <a href="{{ route($reportRoute) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-lg me-1"></i> Reset
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <button class="card-header bg-white border-0 d-flex justify-content-between align-items-center text-start w-100"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#deepFilters"
                aria-expanded="{{ $activeColumnFilterCount > 0 ? 'true' : 'false' }}">
            <span class="fw-semibold small">
                Deep Column Filters
                @if($activeColumnFilterCount > 0)
                    <span class="badge bg-primary ms-2">{{ $activeColumnFilterCount }}</span>
                @endif
            </span>
            <span class="text-muted small">
                Visible columns ke basis par <i class="bi bi-chevron-down ms-1"></i>
            </span>
        </button>
        <div class="collapse {{ $activeColumnFilterCount > 0 ? 'show' : '' }}" id="deepFilters">
            <div class="card-body">
                <div class="row g-3">
                    @foreach($selectedColumns as $key)
                        @php
                            $filter = $columnFilters[$key] ?? null;
                            $filterValue = $activeColumnFilters[$key] ?? [];
                        @endphp
                        @continue(!$filter)

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">{{ $filter['label'] }}</label>

                            @if($filter['type'] === 'multi')
                                @php $selectedValues = (array) ($filterValue['values'] ?? []); @endphp
                                <select name="column_filters[{{ $key }}][values][]" class="form-select form-select-sm" multiple size="3">
                                    @foreach($filter['options'] as $option)
                                        <option value="{{ $option['value'] }}" {{ in_array((string) $option['value'], array_map('strval', $selectedValues), true) ? 'selected' : '' }}>
                                            {{ $option['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            @elseif(in_array($filter['type'], ['date', 'number', 'amount'], true))
                                <div class="d-flex gap-2">
                                    <input type="{{ $filter['type'] === 'date' ? 'date' : 'number' }}"
                                           step="{{ $filter['type'] === 'amount' ? '0.01' : '1' }}"
                                           name="column_filters[{{ $key }}][from]"
                                           value="{{ $filterValue['from'] ?? '' }}"
                                           class="form-control form-control-sm"
                                           placeholder="From">
                                    <input type="{{ $filter['type'] === 'date' ? 'date' : 'number' }}"
                                           step="{{ $filter['type'] === 'amount' ? '0.01' : '1' }}"
                                           name="column_filters[{{ $key }}][to]"
                                           value="{{ $filterValue['to'] ?? '' }}"
                                           class="form-control form-control-sm"
                                           placeholder="To">
                                </div>
                            @else
                                <input type="text"
                                       name="column_filters[{{ $key }}][value]"
                                       value="{{ $filterValue['value'] ?? '' }}"
                                       class="form-control form-control-sm"
                                       placeholder="Contains...">
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check2 me-1"></i> Apply Deep Filters
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearDeepFilters()">
                        <i class="bi bi-eraser me-1"></i> Clear Deep Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="columnModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Select Report Columns</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleColumns(true)">Select All</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleColumns(false)">Clear</button>
                    </div>
                    <div class="row g-3">
                        @foreach($groupedColumns as $section => $sectionColumns)
                            <div class="col-md-4">
                                <div class="border rounded bg-white h-100">
                                    <div class="px-3 py-2 border-bottom bg-light fw-semibold small">{{ $section }}</div>
                                    <div class="p-2">
                                        @foreach($sectionColumns as $key => $column)
                                            <label class="form-check small mb-2">
                                                <input class="form-check-input report-column" type="checkbox" value="1" data-column-key="{{ $key }}" {{ in_array($key, $selectedColumns, true) ? 'checked' : '' }}>
                                                <span class="form-check-label">{{ $column['label'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm">Apply Columns</button>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:70vh;">
            <table class="table table-hover table-bordered align-middle mb-0 small">
                <thead class="table-light" style="position:sticky;top:0;z-index:2;">
                    <tr>
                        <th style="width:55px;">#</th>
                        @foreach($selectedColumns as $key)
                            <th class="text-nowrap">{{ $columns[$key]['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $index => $student)
                        <tr>
                            <td class="text-muted">{{ method_exists($students, 'firstItem') ? $students->firstItem() + $index : $index + 1 }}</td>
                            @foreach($selectedColumns as $key)
                                @php $value = $valueResolver->customReportValue($student, $key, $columns[$key]); @endphp
                                <td class="text-nowrap">
                                    @if($key === 'photo' && $value)
                                        <img src="{{ $value }}" alt="" style="width:42px;height:48px;object-fit:cover;border-radius:4px;">
                                    @else
                                        {{ $value !== '' ? $value : '-' }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($selectedColumns) + 1 }}" class="text-center text-muted py-5">Koi student nahi mila.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($students, 'links'))
            <div class="px-3 pb-3">
                @include('institute.components.pagination', ['paginator' => $students, 'perPage' => $perPage])
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleColumns(checked) {
    document.querySelectorAll('.report-column').forEach(input => input.checked = checked);
}

function syncSelectedColumns() {
    const selected = Array.from(document.querySelectorAll('.report-column:checked')).map(input => input.dataset.columnKey);
    document.getElementById('columnsCsv').value = selected.join(',');
}

document.getElementById('reportForm').addEventListener('submit', syncSelectedColumns);
document.querySelectorAll('.report-column').forEach(input => input.addEventListener('change', syncSelectedColumns));

function clearDeepFilters() {
    document.querySelectorAll('#deepFilters input').forEach(input => input.value = '');
    document.querySelectorAll('#deepFilters select').forEach(select => Array.from(select.options).forEach(option => option.selected = false));
}

document.getElementById('courseFilter').addEventListener('change', function() {
    const cid = this.value;
    const sel = document.getElementById('streamFilter');
    sel.innerHTML = '<option value="">Loading...</option>';
    if (!cid) {
        sel.innerHTML = '<option value="">All</option>';
        return;
    }
    fetch(`{{ route($streamsRoute) }}?course_id=${cid}`)
        .then(response => response.json())
        .then(data => {
            sel.innerHTML = '<option value="">All</option>';
            data.forEach(stream => sel.innerHTML += `<option value="${stream.id}">${stream.name}</option>`);
        });
});
</script>
@endpush
