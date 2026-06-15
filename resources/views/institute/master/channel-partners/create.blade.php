@extends('institute.layout')
@section('title', 'Add Channel Partner')
@section('breadcrumb', 'Master / Channel Partners / New')
@section('content')

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-people me-2 text-primary"></i>Add Channel Partner
        </h5>
        <a href="{{ route('master.channel-partners.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('master.channel-partners.store') }}">
            @csrf
            @include('institute.master.channel-partners._form', ['isEdit' => false])
        </form>
    </div>
</div>

@include('institute.master.channel-partners._form_scripts')
@endsection
