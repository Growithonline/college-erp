@extends('staff.layout')
@section('title', 'Quick Registration')
@section('breadcrumb', 'Admissions / Quick Registration')
@section('content')
@php
    $storeRoute    = route('staff.admissions.quick-store');
    $fullFormRoute = route('staff.admissions.create');
    $indexRoute    = route('staff.admissions.index');
    $seatsUrl      = route('staff.admissions.stream-seats');
    $subjectsUrl   = route('staff.admissions.stream-subjects');
    $feePreviewUrl = route('staff.admissions.fee-preview');
    $formColClass  = 'col-12';
    $formInnerCols = 4;
@endphp
@include('institute.admission._quick-create-body')
@endsection
