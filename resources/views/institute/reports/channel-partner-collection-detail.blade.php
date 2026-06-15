@php
    $entityName     = $partner->name;
    $entitySubtitle = '';
    $backRoute      = route('reports.fee-collection.channel-partner');
    $detailRoute    = route('reports.fee-collection.channel-partner.detail', $partner->id);
@endphp
@extends('institute.layout')
@section('title', 'Partner Receipts — ' . $entityName)
@section('breadcrumb', 'Fee Collection > Fee Collection Report > Channel Partner Collection Report > ' . $entityName)

@section('content')
@include('institute.reports._collection-detail-body', compact(
    'entityName', 'entitySubtitle', 'backRoute', 'detailRoute',
    'invoices', 'totalAmount', 'totalReceipts', 'cashTotal', 'upiTotal', 'onlineTotal',
    'sessions', 'sessionId', 'dateFrom', 'dateTo'
) + ['entityIcon' => 'bi-people', 'entityColor' => 'warning'])
@endsection
