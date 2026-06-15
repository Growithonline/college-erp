@extends('institute.layout')
@section('title', 'Add Driver')
@section('breadcrumb', 'Transport / Drivers / Add')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Add Driver</div>
    <div class="card-body">
        <form method="POST" action="{{ route('transport.drivers.store') }}">
            @csrf
            @include('institute.transport.drivers.form')
        </form>
    </div>
</div>
@endsection
