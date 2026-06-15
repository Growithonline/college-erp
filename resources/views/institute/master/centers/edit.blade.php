@extends('institute.layout')
@section('title', 'Edit Center')
@section('breadcrumb', 'Master / Centers / Edit')
@section('content')

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-building me-2 text-primary"></i>Edit Center &mdash; {{ $center->name }}
        </h5>
        <a href="{{ route('master.centers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
    <div class="card-body p-4">
        <form id="centerForm" method="POST" action="{{ route('master.centers.update', $center) }}">
            @csrf
            @method('PUT')
            @include('institute.master.centers._form', ['isEdit' => true])
        </form>
    </div>
</div>

@include('institute.master.centers._form_scripts')
@endsection
