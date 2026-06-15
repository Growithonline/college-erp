@extends('staff.layout')
@section('title','Change Password')
@section('breadcrumb','Change Password')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-lock me-2 text-primary"></i>Change Password</h6>
            </div>
            <div class="card-body p-4">
                @if(session('success'))<div class="alert alert-success border-0 py-2 small">{{ session('success') }}</div>@endif
                <form method="POST" action="{{ route('staff.change-password.update') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Current Password</label>
                        <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">New Password</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required minlength="8">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i>Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection