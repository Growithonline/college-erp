@extends('center.layout')
@section('title', 'Full Admission Form')
@section('breadcrumb', 'Admissions / Full Form')
@section('content')
@php
    $previewRoute = route('center.admissions.store');
    $indexRoute   = route('center.students.index');
    // Lock admission source to this center
    $centerUser = $center ?? auth()->guard('center')->user();
    $admissionSourceLocked     = 'center';
    $admissionSourceLockedId   = $centerUser->id;
    $admissionSourceLockedName = $centerUser->name;
@endphp
@include('institute.admission._create-body')
@endsection
