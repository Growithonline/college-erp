@extends('partner.layout')
@section('title', 'Full Admission Form')
@section('breadcrumb', 'Admissions / Full Form')
@section('content')
@php
    $previewRoute = route('partner.admissions.store');
    $indexRoute   = route('partner.students.index');
    // Lock admission source to this partner
    $partnerUser = $partner ?? auth()->guard('partner')->user();
    $admissionSourceLocked     = 'channel_partner';
    $admissionSourceLockedId   = $partnerUser->id;
    $admissionSourceLockedName = $partnerUser->name;
@endphp
@include('institute.admission._create-body')
@endsection
