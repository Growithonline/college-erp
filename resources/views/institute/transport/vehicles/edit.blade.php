@extends('institute.layout')
@section('title', 'Edit Vehicle')
@section('breadcrumb', 'Transport / Vehicles / Edit')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Edit Vehicle</div>
    <div class="card-body">
        <form method="POST" action="{{ route('transport.vehicles.update', $vehicle) }}">
            @csrf @method('PUT')
            @include('institute.transport.vehicles.form', ['vehicle' => $vehicle])
        </form>
    </div>
</div>
@endsection
