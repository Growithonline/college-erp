@extends('center.layout')
@section('title', 'Quick Registration')
@section('breadcrumb', 'Admissions / Quick Registration')
@section('content')
@php
    $storeRoute    = route('center.admissions.quick-store');
    $fullFormRoute = route('center.admissions.create');
    $indexRoute    = route('center.students.index');
    $seatsUrl      = route('center.admissions.stream-seats');
    $subjectsUrl   = route('center.admissions.stream-subjects');
    $feePreviewUrl = route('center.admissions.fee-preview');
    // Lock admission source to this center
    $centerUser = $center ?? auth()->guard('center')->user();
    $admissionSourceLocked     = 'center';
    $admissionSourceLockedId   = $centerUser->id;
    $admissionSourceLockedName = $centerUser->name;
    // Full-width 3-column compact layout for center portal
    $formColClass  = 'col-12';
    $formInnerCols = 4;
@endphp
@include('institute.admission._quick-create-body')
@endsection
