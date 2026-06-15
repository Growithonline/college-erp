@extends('institute.layout')
@section('title', 'Edit Driver')
@section('breadcrumb', 'Transport / Drivers / Edit')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Edit Driver</div>
    <div class="card-body">
        <form method="POST" action="{{ route('transport.drivers.update', $driver) }}">
            @csrf @method('PUT')
            @include('institute.transport.drivers.form', ['driver' => $driver])
        </form>
    </div>
</div>
@endsection
