@extends('staff.layout')
@section('title', 'Full Admission Form')
@section('breadcrumb', 'Admissions / Full Form')
@section('content')
@php
    $previewRoute = route('staff.admissions.store');
    $indexRoute   = route('staff.admissions.index');
@endphp
@include('institute.admission._create-body')
@endsection
