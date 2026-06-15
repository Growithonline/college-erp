@extends('institute.layout')
@section('title', 'New Allocation')
@section('breadcrumb', 'Transport / Allocations / New')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">New Transport Allocation</div>
    <div class="card-body">
        <form method="POST" action="{{ route('transport.allocations.store') }}">
            @csrf
            @include('institute.transport.allocations.form')
        </form>
    </div>
</div>
@endsection
