@extends('institute.layout')
@section('title', ($wallet ? 'Add Tokens' : 'Create Wallet') . ' — ' . ($center->name ?? $entity->name))
@section('breadcrumb', 'Fee / ' . ($type === 'center' ? 'Center' : 'Channel') . ' Wallets / ' . ($wallet ? 'Add Tokens' : 'Create'))

@section('content')
@php
    $entityObj  = $center ?? $entity;
    $backRoute  = $type === 'center' ? 'fee-wallets.centers' : 'fee-wallets.channels';
    $storeRoute = $type === 'center'
        ? route('fee-wallets.center.store', $entityObj)
        : route('fee-wallets.channel.store', $entityObj);
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">{{ $wallet ? 'Add Tokens' : 'Create Wallet' }}</h4>
        <small class="text-muted">{{ $entityObj->name }}</small>
    </div>
    <a href="{{ route($backRoute) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

@if($wallet)
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="small text-muted">Total Tokens</div>
                <div class="fw-bold text-primary">₹{{ number_format($wallet->total_tokens, 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="small text-muted">Used</div>
                <div class="fw-bold text-warning">₹{{ number_format($wallet->used_tokens, 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="small text-muted">Remaining</div>
                <div class="fw-bold {{ (float)$wallet->remaining_tokens <= 0 ? 'text-danger' : 'text-success' }}">
                    ₹{{ number_format($wallet->remaining_tokens, 0) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="small text-muted">Expiry</div>
                <div class="fw-bold {{ $wallet->isExpired() ? 'text-danger' : 'text-dark' }}">
                    {{ $wallet->expires_at?->format('d M Y') ?? '—' }}
                    @if($wallet->isExpired()) <span class="badge bg-danger ms-1">Expired</span> @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="card border-0 shadow-sm" style="max-width:500px;">
    <div class="card-header bg-white fw-semibold py-2">
        {{ $wallet ? 'Add New Tokens + Update Expiry' : 'Create Fee Collection Wallet' }}
    </div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger py-2 small">
                @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
        @endif

        <form method="POST" action="{{ $storeRoute }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold small">
                    {{ $wallet ? 'Add Token Amount (₹)' : 'Token Amount (₹)' }}
                </label>
                <input type="number" name="amount" class="form-control"
                    placeholder="e.g. 100000" min="1" step="1"
                    value="{{ old('amount') }}" required>
                @if($wallet)
                    <div class="form-text">This amount will be added to the existing balance.</div>
                @endif
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Expiry Date</label>
                <input type="date" name="expires_at" class="form-control"
                    min="{{ now()->addDay()->toDateString() }}"
                    value="{{ old('expires_at', $wallet?->expires_at?->toDateString()) }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Notes (optional)</label>
                <textarea name="notes" class="form-control" rows="2"
                    placeholder="Internal notes about this wallet...">{{ old('notes', $wallet?->notes) }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i>
                {{ $wallet ? 'Add Tokens' : 'Create Wallet' }}
            </button>
        </form>
    </div>
</div>
@endsection
