@extends('institute.layout')
@section('title','Form Builder')
@section('breadcrumb','Master / Form Builder')

@section('content')
<div class="mb-4">
    <h4 class="mb-0 fw-bold">Form Builder</h4>
    <small class="text-muted">Har form ke fields aur layout configure karo</small>
</div>

<div class="row g-4">
    @foreach($formTypes as $type => $info)
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 border-top border-{{ $info['color'] }} border-3">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-{{ $info['color'] }}-subtle flex-shrink-0"
                         style="width:48px;height:48px;">
                        <i class="bi {{ $info['icon'] }} text-{{ $info['color'] }}" style="font-size:1.3rem;"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">{{ $info['label'] }}</h5>
                        <p class="text-muted small mb-0">{{ $info['description'] }}</p>
                    </div>
                </div>

                {{-- Info per type --}}
                @if($type === 'admission')
                    <div class="mb-3 d-flex flex-wrap gap-1">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:10px;"><i class="bi bi-person me-1"></i>Staff fills</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:10px;"><i class="bi bi-check-circle me-1"></i>Direct approve</span>
                        <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:10px;">4 sections</span>
                    </div>
                @elseif($type === 'quick')
                    <div class="mb-3 d-flex flex-wrap gap-1">
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:10px;"><i class="bi bi-lightning me-1"></i>Fast registration</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:10px;"><i class="bi bi-check-circle me-1"></i>Direct approve</span>
                        <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:10px;">Basic fields only</span>
                    </div>
                @elseif($type === 'online')
                    <div class="mb-3 d-flex flex-wrap gap-1">
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:10px;"><i class="bi bi-globe me-1"></i>Student fills</span>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:10px;"><i class="bi bi-clock me-1"></i>Pending approval</span>
                        <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:10px;">Public URL</span>
                    </div>
                @elseif($type === 'receipt')
                    <div class="mb-3 d-flex flex-wrap gap-1">
                        <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:10px;"><i class="bi bi-layout-text-window me-1"></i>Layout config</span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:10px;"><i class="bi bi-printer me-1"></i>Print format</span>
                    </div>
                @endif

                <a href="{{ route('master.forms.builder', $type) }}"
                   class="btn btn-{{ $info['color'] }} w-100">
                    <i class="bi bi-sliders me-1"></i> Configure {{ $info['label'] }}
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
