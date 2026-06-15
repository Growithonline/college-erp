@extends($libraryLayout)
@section('title', $pageTitle)
@section('breadcrumb', 'Library / ' . $pageTitle)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi {{ $pageIcon }} me-2 text-primary"></i>{{ $pageTitle }}</h4>
        <small class="text-muted">{{ $pageDescription }}</small>
    </div>
    <a href="{{ route($libraryRoutePrefix . '.dashboard') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>{{ $libraryPortalLabel }} Dashboard</a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom"><span class="fw-semibold">Add New</span></div>
            <div class="card-body">
                <form method="POST" action="{{ route($routePrefix . '.store') }}">
                    @csrf
                    @foreach($fields as $field)
                        <div class="mb-3">
                            @if(($field['type'] ?? 'text') !== 'checkbox')
                                <label class="form-label small fw-semibold">{{ $field['label'] }} @if(!empty($field['required']))<span class="text-danger">*</span>@endif</label>
                            @endif

                            @if(($field['type'] ?? 'text') === 'select')
                                <select name="{{ $field['name'] }}" class="form-select">
                                    <option value="">Select</option>
                                    @foreach($field['options'] as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                    @endforeach
                                </select>
                            @elseif(($field['type'] ?? 'text') === 'checkbox')
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="{{ $field['name'] }}" id="create_{{ $field['name'] }}" value="1">
                                    <label class="form-check-label" for="create_{{ $field['name'] }}">{{ $field['label'] }}</label>
                                </div>
                            @else
                                <input type="{{ $field['type'] ?? 'text' }}"
                                       name="{{ $field['name'] }}"
                                       value="{{ old($field['name']) }}"
                                       class="form-control"
                                       @if(isset($field['step'])) step="{{ $field['step'] }}" @endif
                                       placeholder="{{ $field['placeholder'] ?? '' }}">
                            @endif
                        </div>
                    @endforeach

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-circle me-1"></i>Save
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Configured Records</span>
                <span class="badge bg-secondary-subtle text-secondary border">{{ $records->count() }}</span>
            </div>
            <div class="card-body">
                @forelse($records as $record)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                @foreach($columns as $column)
                                    <div class="small text-muted">{{ $column['label'] }}</div>
                                    <div class="fw-semibold mb-1">{{ $column['value']($record) }}</div>
                                @endforeach
                            </div>
                            <form method="POST" action="{{ route($routePrefix . '.toggle', $record) }}" class="ms-3">
                                @csrf
                                <button class="btn btn-sm {{ $record->is_active ? 'btn-success' : 'btn-secondary' }}">{{ $record->is_active ? 'Active' : 'Inactive' }}</button>
                            </form>
                        </div>

                        <form method="POST" action="{{ route($routePrefix . '.update', $record) }}" class="row g-2 align-items-end" id="update_{{ $routePrefix }}_{{ $record->id }}">
                            @csrf
                            @method('PUT')
                            @foreach($fields as $field)
                                <div class="col-md-{{ ($field['type'] ?? 'text') === 'checkbox' ? 12 : 6 }}">
                                    @if(($field['type'] ?? 'text') !== 'checkbox')
                                        <label class="form-label small">{{ $field['label'] }}</label>
                                    @endif

                                    @if(($field['type'] ?? 'text') === 'select')
                                        <select name="{{ $field['name'] }}" class="form-select form-select-sm">
                                            <option value="">Select</option>
                                            @foreach($field['options'] as $optionValue => $optionLabel)
                                                <option value="{{ $optionValue }}" @selected($record->{$field['name']} == $optionValue)>{{ $optionLabel }}</option>
                                            @endforeach
                                        </select>
                                    @elseif(($field['type'] ?? 'text') === 'checkbox')
                                        <div class="form-check mt-1">
                                            <input type="hidden" name="{{ $field['name'] }}" value="0">
                                            <input class="form-check-input" type="checkbox" name="{{ $field['name'] }}" id="edit_{{ $field['name'] }}_{{ $record->id }}" value="1" @checked($record->{$field['name']})>
                                            <label class="form-check-label" for="edit_{{ $field['name'] }}_{{ $record->id }}">{{ $field['label'] }}</label>
                                        </div>
                                    @else
                                        <input type="{{ $field['type'] ?? 'text' }}"
                                               name="{{ $field['name'] }}"
                                               value="{{ old($field['name'], $record->{$field['name']}) }}"
                                               class="form-control form-control-sm"
                                               @if(isset($field['step'])) step="{{ $field['step'] }}" @endif>
                                    @endif
                                </div>
                            @endforeach

                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Update</button>
                                <button type="submit"
                                        class="btn btn-outline-danger btn-sm"
                                        form="delete_{{ $routePrefix }}_{{ $record->id }}"
                                        onclick="return confirm('Delete karna chahte ho?')">
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route($routePrefix . '.destroy', $record) }}" id="delete_{{ $routePrefix }}_{{ $record->id }}" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">Abhi koi record nahi hai.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
