@extends('institute.layout')
@section('title', 'New Admission')
@section('breadcrumb', 'Admissions / New')
@section('content')
@php
    $previewRoute = route('admissions.preview');
    $indexRoute   = route('admissions.index');
@endphp
@include('institute.admission._create-body')
@endsection
