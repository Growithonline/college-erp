@extends('institute.layout')

@section('title', 'Academic Sessions')
@section('breadcrumb', 'Master / Academic Session')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Academic Sessions</h4>
        <small class="text-muted">Manage your academic year sessions</small>
    </div>
    <a href="{{ route('master.sessions.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Session
    </a>
</div>

@if($sessions->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <div class="card-body">
            <i class="bi bi-calendar-x" style="font-size:3rem; color:#94a3b8;"></i>
            <h5 class="mt-3 text-muted">No Sessions Yet</h5>
            <p class="text-muted small">Create your first academic session to get started.</p>
            <a href="{{ route('master.sessions.create') }}" class="btn btn-primary mt-2">
                <i class="bi bi-plus-lg me-1"></i> Create Session
            </a>
        </div>
    </div>
@else
    <div class="row g-3">
        @foreach($sessions as $session)
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 {{ $session->is_active ? 'border-start border-success border-3' : '' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="fw-bold mb-0">{{ $session->name }}</h5>
                            <small class="text-muted">
                                {{ $session->start_date->format('d M Y') }} —
                                {{ $session->end_date->format('d M Y') }}
                            </small>
                        </div>
                        @if($session->is_active)
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2">
                                <i class="bi bi-check-circle me-1"></i>Active
                            </span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2">
                                Inactive
                            </span>
                        @endif
                    </div>

                    <div class="d-flex gap-2 mt-3 flex-wrap">
                        @if(!$session->is_active)
                            <form method="POST" action="{{ route('master.sessions.activate', $session) }}">
                                @csrf
                                <button class="btn btn-success btn-sm">
                                    <i class="bi bi-check2-circle me-1"></i>Activate
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('master.sessions.edit', $session) }}"
                           class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </a>
                        @if(!$session->is_active)
                            <form method="POST" action="{{ route('master.sessions.destroy', $session) }}"
                                  onsubmit="return confirm('Delete this session?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
@endif
@endsection
