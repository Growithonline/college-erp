@extends('institute.layout')
@section('title', 'Add Role')
@section('breadcrumb', 'Master / Staff / Roles / New')
@section('content')

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 px-4">
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-shield-plus me-2 text-primary"></i>Add Custom Role
        </h5>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('master.staff-roles.store') }}">
            @csrf
            @include('institute.master.staff.roles._form')
        </form>
    </div>
</div>

@endsection
