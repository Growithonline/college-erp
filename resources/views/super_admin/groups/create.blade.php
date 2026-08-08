@extends('super_admin.layout')
@section('title', 'Add Group')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('super_admin.groups.index') }}" class="text-decoration-none">Groups / Trusts</a></li>
    <li class="breadcrumb-item active">Add Group</li>
@endsection

@section('content')

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pb-0 pt-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-diagram-3 text-primary me-2"></i>New Group / Trust</h6>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('super_admin.groups.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Group / Trust Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check2 me-1"></i> Create Group
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
