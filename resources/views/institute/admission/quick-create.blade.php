@extends('institute.layout')
@section('title', 'Quick Registration')
@section('breadcrumb', 'Admissions / Quick Registration')
@section('content')
@php
    $storeRoute = route('admissions.quick-store');
    $fullFormRoute = route('admissions.create');
    $indexRoute = route('admissions.index');
    $seatsUrl = route('admissions.stream-seats');
    $subjectsUrl = route('admissions.stream-subjects');
    $feePreviewUrl = route('admissions.fee-preview');
    $formColClass  = 'col-12';
    $formInnerCols = 4;
@endphp
@include('institute.admission._quick-create-body')
@endsection
