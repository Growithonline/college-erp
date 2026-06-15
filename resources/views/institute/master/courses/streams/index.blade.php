@extends('institute.layout')
@section('title', 'Streams — '.$course->name)
@section('breadcrumb', 'Master / Course / '.$course->name.' / Streams')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">{{ $course->name }} — Streams</h4>
        <small class="text-muted">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">{{ $course->code }}</span>
            {{ ucfirst($course->structure_type) }} •
            {{ $course->duration }} {{ $course->duration_type == 'year' ? 'Year(s)' : 'Month(s)' }}
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('master.courses.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <a href="{{ route('master.courses.streams.create', $course) }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Stream
        </a>
    </div>
</div>

<div class="alert alert-info py-2 small mb-4">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Stream = Major Subject.</strong>
    Example: For a BA course, streams could be — BA English, BA Hindi, BA Geography.
    Each stream has a fixed major subject; students choose their minor subjects.
</div>

@if($streams->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <div class="card-body">
            <i class="bi bi-diagram-3" style="font-size:3rem; color:#94a3b8;"></i>
            <h5 class="mt-3 text-muted">No Streams Yet</h5>
            <p class="text-muted small">Add streams (major subjects) for this course.</p>
            <a href="{{ route('master.courses.streams.create', $course) }}" class="btn btn-primary mt-2">
                <i class="bi bi-plus-lg me-1"></i> Add First Stream
            </a>
        </div>
    </div>
@else
    <div class="row g-3">
        @foreach($streams as $stream)
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="fw-bold mb-0">{{ $stream->name }}</h6>
                            <small class="text-muted">{{ $stream->code }}</small>
                        </div>
                        <span class="badge bg-{{ $stream->status ? 'success' : 'secondary' }}-subtle
                                           text-{{ $stream->status ? 'success' : 'secondary' }}
                                           border border-{{ $stream->status ? 'success' : 'secondary' }}-subtle">
                            {{ $stream->status ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    {{-- Stats --}}
                    <div class="d-flex gap-3 mt-3 pt-2 border-top" style="font-size:12px;">
                        <div class="text-center">
                            <div class="fw-bold text-primary">{{ $stream->year_rules_count ?? 0 }}</div>
                            <div class="text-muted">Year Rules</div>
                        </div>
                        <div class="text-center">
                            <div class="fw-bold text-success">{{ $stream->total_subjects_count ?? 0 }}</div>
                            <div class="text-muted">Subjects</div>
                        </div>
                        {{-- Seat limit — session wise --}}
                        @php
                            $activeSessionId = \App\Models\AcademicSession::where('institute_id', auth()->user()->institute_id)
                                ->where('is_active', true)->value('id');
                            $sessionLimit = \App\Models\StreamSessionLimit::where('course_stream_id', $stream->id)
                                ->where('academic_session_id', $activeSessionId)->first();
                        @endphp
                        @if($sessionLimit)
                        @php
                            $filled    = \App\Models\Student::where('course_stream_id', $stream->id)
                                ->where('academic_session_id', $activeSessionId)
                                ->where('status', '!=', 'cancelled')->count();
                            $remaining = $sessionLimit->student_limit - $filled;
                        @endphp
                        <div class="text-center">
                            <div class="fw-bold {{ $remaining <= 0 ? 'text-danger' : ($remaining <= 5 ? 'text-warning' : 'text-info') }}">
                                {{ $filled }}/{{ $sessionLimit->student_limit }}
                            </div>
                            <div class="text-muted">Seats</div>
                        </div>
                        @endif
                    </div>

                    @if($sessionLimit ?? false)
                    <div class="mt-2">
                        @php $pct = min(100, round(($filled / $sessionLimit->student_limit) * 100)); @endphp
                        <div class="progress" style="height:4px;">
                            <div class="progress-bar {{ $pct >= 100 ? 'bg-danger' : ($pct >= 80 ? 'bg-warning' : 'bg-success') }}"
                                 style="width:{{ $pct }}%"></div>
                        </div>
                        <div style="font-size:10px;" class="text-muted mt-1">
                            {{ $remaining <= 0 ? 'Full — No more admissions' : "{$remaining} seats remaining" }}
                        </div>
                    </div>
                    @endif

                    {{-- Set/Edit Limit for current session --}}
                    <div class="mt-2">
                        <button type="button"
                            class="btn btn-outline-info btn-sm w-100"
                            onclick="showLimitModal({{ $stream->id }}, '{{ addslashes($stream->name) }}', {{ $sessionLimit?->student_limit ?? 0 }})">
                            <i class="bi bi-people me-1"></i>
                            {{ $sessionLimit ? 'Edit Seat Limit ('.$sessionLimit->student_limit.')' : '+ Set Seat Limit' }}
                        </button>
                    </div>

                    <div class="mt-3 pt-2 border-top d-flex gap-2 flex-wrap">
                        {{-- Year Rules --}}
                        <a href="{{ route('master.streams.year-rules', $stream) }}"
                           class="btn btn-outline-primary btn-sm flex-fill text-center">
                            <i class="bi bi-sliders me-1"></i>Year Rules
                            <span class="badge bg-primary ms-1">{{ $stream->year_rules_count ?? 0 }}</span>
                        </a>

                        {{-- Subject Mapping --}}
                        <a href="{{ route('master.streams.subjects.index', $stream) }}"
                           class="btn btn-outline-success btn-sm flex-fill text-center">
                            <i class="bi bi-book me-1"></i>Subjects
                            @if(($stream->total_subjects_count ?? 0) > 0)
                                <span class="badge bg-success ms-1">{{ $stream->total_subjects_count }}</span>
                            @endif
                        </a>

                        {{-- Edit --}}
                        <a href="{{ route('master.streams.edit', $stream) }}"
                           class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-pencil"></i>
                        </a>

                        {{-- Delete --}}
                        <form method="POST"
                              action="{{ route('master.streams.destroy', $stream) }}"
                              onsubmit="return confirm('Delete {{ $stream->name }} stream? All linked subjects will also be deleted.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
@endif

{{-- Set Seat Limit Modal --}}
<div class="modal fade" id="limitModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background:#0f4c81;color:white;">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-people me-2"></i>Set Seat Limit
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('master.streams.set-limit') }}">
                @csrf
                <input type="hidden" name="stream_id" id="limitStreamId">
                <div class="modal-body">
                    <div class="alert alert-info py-2 small border-0 mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        This limit applies only to the <strong>current session ({{ $activeSession->name ?? '' }})</strong>.
                        A different limit can be set for each new session.
                    </div>
                    <label class="form-label fw-semibold">
                        Stream: <span id="limitStreamName" class="text-primary"></span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-people"></i></span>
                        <input type="number" name="student_limit" id="limitInput"
                               class="form-control" placeholder="e.g. 60"
                               min="1" max="9999" required>
                        <span class="input-group-text">students</span>
                    </div>
                    <div class="form-text">Blank = unlimited. 0 = fully blocked.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-lg me-1"></i>Save Limit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showLimitModal(streamId, streamName, currentLimit) {
    document.getElementById('limitStreamId').value   = streamId;
    document.getElementById('limitStreamName').textContent = streamName;
    document.getElementById('limitInput').value      = currentLimit || '';
    new bootstrap.Modal(document.getElementById('limitModal')).show();
}
</script>

@endsection