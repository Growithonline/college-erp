@extends('institute.layout')
@section('title', 'Year Rules')
@section('breadcrumb', 'Master / Course / '.$stream->course->name.' / '.$stream->name.' / Year Rules')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Year Rules — {{ $stream->name }}</h4>
        <small class="text-muted">
            {{ $stream->course->name }} •
            {{ ucfirst($stream->course->structure_type) }} •
            {{ $stream->course->duration }} {{ $stream->course->duration_type == 'year' ? 'Year(s)' : 'Month(s)' }}
        </small>
    </div>
    <a href="{{ route('master.courses.streams.index', $stream->course) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="alert alert-info py-2 small mb-4">
    <i class="bi bi-info-circle me-1"></i>
    Define how many <strong>Major</strong> and <strong>Minor</strong> subjects a student can choose each year.
    <strong>The same rule applies to both semesters within a year.</strong>
</div>

<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-sliders me-2 text-primary"></i>Subject Count Rules Per Year
                </h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('master.streams.year-rules.save', $stream) }}">
                    @csrf

                    {{-- Header --}}
                    <div class="row fw-semibold text-muted small mb-2 px-1">
                        <div class="col-3">Year</div>
                        <div class="col-2">Semesters</div>
                        <div class="col-2 text-center" style="color:#0d6efd;">
                            <i class="bi bi-star-fill me-1" style="font-size:10px;"></i>Major Min
                        </div>
                        <div class="col-2 text-center" style="color:#0d6efd;">
                            <i class="bi bi-star-fill me-1" style="font-size:10px;"></i>Major Max
                        </div>
                        <div class="col-1 text-center" style="color:#198754;">Minor Min</div>
                        <div class="col-1 text-center" style="color:#198754;">Minor Max</div>
                        <div class="col-1"></div>
                    </div>

                    @php
                        $course  = $stream->course;
                        $years   = $course->duration_type === 'year'
                            ? $course->duration
                            : max(1, (int) ceil($course->duration / 12));
                        $rulesMap     = $stream->yearRules->keyBy('year_number');
                        $partsPerYear = $course->structure_type === 'semester' ? 2 : 1;
                    @endphp

                    @for($y = 1; $y <= $years; $y++)
                        @php
                            $rule     = $rulesMap->get($y);
                            $semStart = (($y - 1) * $partsPerYear) + 1;
                            $semEnd   = $y * $partsPerYear;
                            $semLabel = $course->structure_type === 'semester'
                                ? "Sem $semStart & $semEnd"
                                : ($course->structure_type === 'yearly' ? "Year $y" : "Month $semStart-$semEnd");

                            $defMajorMin = $rule->major_min ?? 1;
                            $defMajorMax = $rule->major_max ?? 3;
                            $defMinorMin = $rule->minor_optional_min ?? ($y == $years ? 1 : 2);
                            $defMinorMax = $rule->minor_optional_max ?? ($y == $years ? 1 : 2);
                        @endphp

                        <div class="row align-items-center mb-3 p-2 rounded
                            {{ $y == $years ? 'bg-warning-subtle border border-warning-subtle' : 'bg-light' }}">

                            <div class="col-3">
                                <span class="fw-semibold">Year {{ $y }}</span>
                                @if($y == $years)
                                    <br><small class="text-warning fw-semibold">Last Year</small>
                                @endif
                                <input type="hidden" name="rules[{{ $y }}][year_number]" value="{{ $y }}">
                            </div>

                            <div class="col-2">
                                <small class="text-muted">{{ $semLabel }}</small>
                            </div>

                            {{-- Major Min --}}
                            <div class="col-2">
                                <input type="number"
                                       name="rules[{{ $y }}][major_min]"
                                       value="{{ old("rules.$y.major_min", $defMajorMin) }}"
                                       min="0" max="10"
                                       class="form-control form-control-sm text-center"
                                       style="border-color:#0d6efd30;">
                            </div>

                            {{-- Major Max --}}
                            <div class="col-2">
                                <input type="number"
                                       name="rules[{{ $y }}][major_max]"
                                       value="{{ old("rules.$y.major_max", $defMajorMax) }}"
                                       min="0" max="10"
                                       class="form-control form-control-sm text-center"
                                       style="border-color:#0d6efd30;">
                            </div>

                            {{-- Minor Min --}}
                            <div class="col-1">
                                <input type="number"
                                       name="rules[{{ $y }}][minor_optional_min]"
                                       value="{{ old("rules.$y.minor_optional_min", $defMinorMin) }}"
                                       min="0" max="5"
                                       class="form-control form-control-sm text-center"
                                       style="border-color:#19875430;">
                            </div>

                            {{-- Minor Max --}}
                            <div class="col-1">
                                <input type="number"
                                       name="rules[{{ $y }}][minor_optional_max]"
                                       value="{{ old("rules.$y.minor_optional_max", $defMinorMax) }}"
                                       min="0" max="5"
                                       class="form-control form-control-sm text-center"
                                       style="border-color:#19875430;">
                            </div>

                            <div class="col-1 text-center">
                                @if($y == $years)
                                    <i class="bi bi-star-fill text-warning small" title="Last Year — drop rule applies"></i>
                                @endif
                            </div>
                        </div>
                    @endfor

                    {{-- Legend --}}
                    <div class="d-flex gap-4 mb-3 mt-2 small text-muted">
                        <div><span class="badge bg-primary bg-opacity-10 text-primary me-1">Major</span>
                            Allowed range for major subjects (e.g. min 1, max 3)
                        </div>
                        <div><span class="badge bg-success bg-opacity-10 text-success me-1">Minor</span>
                            Allowed range for optional/minor subjects
                        </div>
                    </div>

                    <hr class="mt-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i> Save Rules
                        </button>
                        <a href="{{ route('master.courses.streams.index', $stream->course) }}"
                           class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection