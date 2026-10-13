@extends('institute.layout')
@section('title', 'Login Links & Embed Codes')
@section('breadcrumb', 'Login Links & Embed Codes')

@section('content')
    @include('partials.institute-embed-codes', ['institute' => $institute])
@endsection
