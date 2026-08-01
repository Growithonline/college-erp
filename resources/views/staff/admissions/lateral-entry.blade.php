@extends('staff.layout')
@section('title', 'Lateral Entry Admission')
@section('breadcrumb', 'Admissions / Lateral Entry')
@section('content')
@php
    $previewRoute = route('staff.admissions.store');
    $indexRoute   = route('staff.admissions.index');
    $isLateralEntry = true;
@endphp
@include('institute.admission._create-body')
@endsection
