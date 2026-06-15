@extends('institute.layout')
@section('title', 'Add Vehicle')
@section('breadcrumb', 'Transport / Vehicles / Add')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Add Vehicle</div>
    <div class="card-body">
        <form method="POST" action="{{ route('transport.vehicles.store') }}">
            @csrf
            @include('institute.transport.vehicles.form')
        </form>
    </div>
</div>
@endsection
