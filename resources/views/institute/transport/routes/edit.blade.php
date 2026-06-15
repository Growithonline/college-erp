@extends('institute.layout')
@section('title', 'Edit Route')
@section('breadcrumb', 'Transport / Routes / Edit')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Edit Route</div>
    <div class="card-body">
        <form method="POST" action="{{ route('transport.routes.update', $route) }}">
            @csrf @method('PUT')
            @include('institute.transport.routes.form', ['route' => $route])
        </form>
    </div>
</div>
@endsection
