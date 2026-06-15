@php
    $entityName     = $centre->name;
    $entitySubtitle = '';
    $backRoute      = route('reports.fee-collection.centre');
    $detailRoute    = route('reports.fee-collection.centre.detail', $centre->id);
@endphp
@extends('institute.layout')
@section('title', 'Centre Receipts — ' . $entityName)
@section('breadcrumb', 'Fee Collection > Fee Collection Report > Centre Collection Report > ' . $entityName)

@section('content')
@include('institute.reports._collection-detail-body', compact(
    'entityName', 'entitySubtitle', 'backRoute', 'detailRoute',
    'invoices', 'totalAmount', 'totalReceipts', 'cashTotal', 'upiTotal', 'onlineTotal',
    'sessions', 'sessionId', 'dateFrom', 'dateTo'
) + ['entityIcon' => 'bi-building', 'entityColor' => 'success'])
@endsection
