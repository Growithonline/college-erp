@php
    $entityName     = $staff->name;
    $entitySubtitle = $staff->designation ?? '';
    $backRoute      = route('reports.fee-collection.staff');
    $detailRoute    = route('reports.fee-collection.staff.detail', $staff->id);
@endphp
@extends('institute.layout')
@section('title', 'Staff Receipts — ' . $entityName)
@section('breadcrumb', 'Fee Collection > Fee Collection Report > Staff Collection Report > ' . $entityName)

@section('content')
@include('institute.reports._collection-detail-body', compact(
    'entityName', 'entitySubtitle', 'backRoute', 'detailRoute',
    'invoices', 'totalAmount', 'totalReceipts', 'cashTotal', 'upiTotal', 'onlineTotal',
    'sessions', 'sessionId', 'dateFrom', 'dateTo'
) + ['entityIcon' => 'bi-person-badge', 'entityColor' => 'primary'])
@endsection
