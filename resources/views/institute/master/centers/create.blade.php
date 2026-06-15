@extends('institute.layout')
@section('title', 'Add Center')
@section('breadcrumb', 'Master / Centers / New')
@section('content')

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-building-add me-2 text-primary"></i>Add Center
        </h5>
        <a href="{{ route('master.centers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
    <div class="card-body p-4">
        <form id="centerForm" method="POST" action="{{ route('master.centers.store') }}">
            @csrf
            @include('institute.master.centers._form', ['isEdit' => false])
        </form>
    </div>
</div>

@include('institute.master.centers._form_scripts')
@endsection
