@extends('partner.layout')
@section('title', 'Quick Registration')
@section('breadcrumb', 'Admissions / Quick Register')
@section('content')
@php
    $storeRoute    = route('partner.admissions.quick-store');
    $fullFormRoute = '#';
    $indexRoute    = route('partner.students.index');
    $seatsUrl      = route('partner.admissions.stream-seats');
    $subjectsUrl   = route('partner.admissions.stream-subjects');
    $feePreviewUrl = route('partner.admissions.fee-preview');
    $formColClass  = 'col-12';
    $formInnerCols = 4;
    // Lock admission source to this channel partner
    $partnerUser = $partner ?? auth()->guard('partner')->user();
    $admissionSourceLocked     = 'channel_partner';
    $admissionSourceLockedId   = $partnerUser->id;
    $admissionSourceLockedName = $partnerUser->name;
@endphp
@include('institute.admission._quick-create-body')
@endsection
