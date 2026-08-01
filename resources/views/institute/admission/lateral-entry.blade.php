@extends('institute.layout')
@section('title', 'Lateral Entry Admission')
@section('breadcrumb', 'Admissions / Lateral Entry')
@section('content')
@php
    $previewRoute = route('admissions.preview');
    $indexRoute   = route('admissions.index');
    $isLateralEntry = true;
@endphp
@include('institute.admission._create-body')
@endsection
