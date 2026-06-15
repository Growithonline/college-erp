@extends('institute.layout')
@section('title', 'Add Route')
@section('breadcrumb', 'Transport / Routes / Add')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Add Route</div>
    <div class="card-body">
        <form method="POST" action="{{ route('transport.routes.store') }}">
            @csrf
            @include('institute.transport.routes.form')
        </form>
    </div>
</div>
@endsection
