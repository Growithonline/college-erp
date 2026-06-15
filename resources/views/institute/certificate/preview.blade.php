@extends('institute.layout')
@section('title', 'Certificate Preview')
@section('breadcrumb', 'Certificates / Preview')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-eye me-2 text-primary"></i>Certificate Preview</h4>
        <small class="text-muted">Yeh preview hai — issue karne ke baad actual certificate number assign hoga</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('certificate.create', ['student_id' => $student->id]) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row g-4">

{{-- Preview Box --}}
<div class="col-lg-8">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
            <span class="fw-semibold">{{ $type->name }} — Preview</span>
            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Draft — Not Issued</span>
        </div>
        <div class="card-body p-0" style="background:#f8f9fa;">
            {{-- Certificate HTML preview --}}
            <div class="p-4">
                @php
                    $color = $settings->primary_color ?? '#1e3a5f';
                @endphp

                @if($settings->theme === 'classic')
                <div style="border: 3px double {{ $color }}; outline: 1px solid {{ $color }}; outline-offset: -6px; padding: 30px; background: #fff; font-family: Georgia, serif;">
                    {{-- Header --}}
                    <div style="text-align:center; margin-bottom:12px;">
                        @if($settings->logo)
                            <img src="{{ Storage::url($settings->logo) }}" style="max-height:65px; max-width:140px; margin-bottom:8px; display:block; margin-left:auto; margin-right:auto;">
                        @endif
                        @if($settings->header_line1) <div style="font-size:18px; font-weight:bold; color:{{ $color }};">{{ $settings->header_line1 }}</div> @endif
                        @if($settings->header_line2) <div style="font-size:12px; color:#555; margin-top:3px;">{{ $settings->header_line2 }}</div> @endif
                        @if($settings->header_line3) <div style="font-size:11px; color:#777; margin-top:2px;">{{ $settings->header_line3 }}</div> @endif
                    </div>
                    <hr style="border: none; border-top: 2px solid {{ $color }}; margin: 10px 0;">
                    <div style="text-align:center; font-size:15px; font-weight:bold; letter-spacing:2px; text-transform:uppercase; color:{{ $color }}; border-top:1px solid #ccc; border-bottom:1px solid #ccc; padding:6px 0; margin:10px 0 18px;">{{ $type->name }}</div>
                    <div style="display:flex; justify-content:space-between; font-size:11px; color:#666; margin-bottom:16px;">
                        <span>No.: <strong>{{ $tempCert->certificate_number }}</strong> <em>(draft)</em></span>
                        <span>Date: <strong>{{ now()->format('d/m/Y') }}</strong></span>
                    </div>
                    <div style="line-height:1.9; font-size:13px; text-align:justify;">{!! $bodyHtml !!}</div>
                    <div style="display:flex; justify-content:space-between; margin-top:40px;">
                        <div style="text-align:center; width:40%;">
                            @if($settings->principal_signature) <img src="{{ Storage::url($settings->principal_signature) }}" style="max-height:50px; display:block; margin:auto;"> @endif
                            <div style="border-top:1px solid #555; padding-top:4px; font-size:12px; font-weight:bold;">{{ $settings->principal_name ?: '________________________' }}</div>
                            <div style="font-size:11px; color:#666;">{{ $settings->principal_designation ?: 'Principal' }}</div>
                        </div>
                        <div style="text-align:center; width:20%;">
                            @if($settings->seal_image) <img src="{{ Storage::url($settings->seal_image) }}" style="max-height:65px;"> @else <div style="font-size:10px;color:#aaa;">[SEAL]</div> @endif
                        </div>
                        <div style="text-align:center; width:40%;">
                            @if($settings->registrar_signature) <img src="{{ Storage::url($settings->registrar_signature) }}" style="max-height:50px; display:block; margin:auto;"> @endif
                            <div style="border-top:1px solid #555; padding-top:4px; font-size:12px; font-weight:bold;">{{ $settings->registrar_name ?: '________________________' }}</div>
                            <div style="font-size:11px; color:#666;">{{ $settings->registrar_designation ?: 'Registrar' }}</div>
                        </div>
                    </div>
                </div>

                @elseif($settings->theme === 'colored')
                <div style="border:1px solid #ddd; background:#fff; overflow:hidden;">
                    <div style="background:{{ $color }}; color:#fff; padding:14px 20px; display:flex; align-items:center; gap:14px;">
                        @if($settings->logo) <img src="{{ Storage::url($settings->logo) }}" style="max-height:60px; max-width:120px; background:rgba(255,255,255,.15); padding:3px; border-radius:3px;"> @endif
                        <div style="flex:1;">
                            @if($settings->header_line1) <div style="font-size:17px; font-weight:bold;">{{ $settings->header_line1 }}</div> @endif
                            @if($settings->header_line2) <div style="font-size:11px; opacity:.85; margin-top:2px;">{{ $settings->header_line2 }}</div> @endif
                            @if($settings->header_line3) <div style="font-size:10px; opacity:.7; margin-top:1px;">{{ $settings->header_line3 }}</div> @endif
                        </div>
                        @if($settings->seal_image) <img src="{{ Storage::url($settings->seal_image) }}" style="max-height:55px;max-width:55px;opacity:.9;"> @endif
                    </div>
                    <div style="height:4px; background:linear-gradient(90deg, {{ $color }}99, {{ $color }}22);"></div>
                    <div style="padding:18px 24px;">
                        <div style="text-align:center; font-size:15px; font-weight:bold; letter-spacing:2px; text-transform:uppercase; color:{{ $color }}; border-bottom:2px solid {{ $color }}; padding-bottom:6px; margin:0 0 14px;">{{ $type->name }}</div>
                        <div style="display:flex; justify-content:space-between; font-size:11px; color:#666; margin-bottom:16px;">
                            <span>Certificate No.: <strong>{{ $tempCert->certificate_number }}</strong> <em>(draft)</em></span>
                            <span>Date: <strong>{{ now()->format('d/m/Y') }}</strong></span>
                        </div>
                        <div style="line-height:1.9; font-size:13px; text-align:justify;">{!! $bodyHtml !!}</div>
                        <div style="display:flex; justify-content:space-between; margin-top:40px; padding-top:16px; border-top:1px solid #eee;">
                            <div style="text-align:center; width:40%;">
                                @if($settings->principal_signature) <img src="{{ Storage::url($settings->principal_signature) }}" style="max-height:50px; display:block; margin:auto;"> @endif
                                <div style="border-top:1px solid #555; padding-top:4px; font-size:12px; font-weight:bold;">{{ $settings->principal_name ?: '________________________' }}</div>
                                <div style="font-size:11px; color:#666;">{{ $settings->principal_designation ?: 'Principal' }}</div>
                            </div>
                            <div style="text-align:center; width:20%;"></div>
                            <div style="text-align:center; width:40%;">
                                @if($settings->registrar_signature) <img src="{{ Storage::url($settings->registrar_signature) }}" style="max-height:50px; display:block; margin:auto;"> @endif
                                <div style="border-top:1px solid #555; padding-top:4px; font-size:12px; font-weight:bold;">{{ $settings->registrar_name ?: '________________________' }}</div>
                                <div style="font-size:11px; color:#666;">{{ $settings->registrar_designation ?: 'Registrar' }}</div>
                            </div>
                        </div>
                    </div>
                    <div style="background:{{ $color }}18; border-top:2px solid {{ $color }}44; padding:6px 20px; font-size:10px; color:#666; text-align:center;">
                        {{ $settings->header_line1 }}@if($settings->header_line2) &nbsp;•&nbsp; {{ $settings->header_line2 }} @endif
                    </div>
                </div>

                @else {{-- minimal --}}
                <div style="border:1px solid #eee; padding:28px; background:#fff; font-family: Arial, sans-serif;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; padding-bottom:14px; border-bottom:3px solid {{ $color }}; margin-bottom:18px;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            @if($settings->logo) <img src="{{ Storage::url($settings->logo) }}" style="max-height:60px; max-width:120px;"> @endif
                            <div>
                                @if($settings->header_line1) <div style="font-size:16px; font-weight:bold; color:{{ $color }};">{{ $settings->header_line1 }}</div> @endif
                                @if($settings->header_line2) <div style="font-size:11px; color:#555; margin-top:3px;">{{ $settings->header_line2 }}</div> @endif
                                @if($settings->header_line3) <div style="font-size:10px; color:#777; margin-top:2px;">{{ $settings->header_line3 }}</div> @endif
                            </div>
                        </div>
                        @if($settings->seal_image) <img src="{{ Storage::url($settings->seal_image) }}" style="max-height:60px;max-width:60px;opacity:.8;"> @endif
                    </div>
                    <div style="text-align:center; font-size:17px; font-weight:bold; letter-spacing:3px; text-transform:uppercase; color:{{ $color }}; margin-bottom:4px;">{{ $type->name }}</div>
                    <div style="text-align:center; margin-bottom:18px;"><span style="display:inline-block; width:70px; border-bottom:2px solid {{ $color }};"></span></div>
                    <div style="display:flex; justify-content:space-between; font-size:11px; color:#666; margin-bottom:18px;">
                        <span>Cert. No.: <strong>{{ $tempCert->certificate_number }}</strong> <em>(draft)</em></span>
                        <span>Issued: <strong>{{ now()->format('d F, Y') }}</strong></span>
                    </div>
                    <div style="line-height:1.9; font-size:13px; text-align:justify;">{!! $bodyHtml !!}</div>
                    <div style="display:flex; justify-content:space-between; margin-top:48px;">
                        <div style="width:45%;">
                            @if($settings->principal_signature) <img src="{{ Storage::url($settings->principal_signature) }}" style="max-height:48px; display:block; margin-bottom:4px;"> @endif
                            <div style="border-top:1px solid #555; padding-top:4px; font-size:12px; font-weight:bold;">{{ $settings->principal_name ?: '________________________' }}</div>
                            <div style="font-size:11px; color:#666;">{{ $settings->principal_designation ?: 'Principal' }}</div>
                        </div>
                        <div style="width:45%; text-align:right;">
                            @if($settings->registrar_signature) <img src="{{ Storage::url($settings->registrar_signature) }}" style="max-height:48px; display:block; margin-left:auto; margin-bottom:4px;"> @endif
                            <div style="border-top:1px solid #555; padding-top:4px; font-size:12px; font-weight:bold;">{{ $settings->registrar_name ?: '________________________' }}</div>
                            <div style="font-size:11px; color:#666;">{{ $settings->registrar_designation ?: 'Registrar' }}</div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Actions Panel --}}
<div class="col-lg-4">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-bottom py-3">
            <span class="fw-semibold">Student Details</span>
        </div>
        <div class="card-body py-2">
            <table class="table table-sm table-borderless mb-0">
                <tr><td class="text-muted small">Name</td><td class="fw-semibold small">{{ $student->name }}</td></tr>
                <tr><td class="text-muted small">ID</td><td class="small">{{ $student->student_uid }}</td></tr>
                @if($student->enrollment_no)
                <tr><td class="text-muted small">Enrollment</td><td class="small">{{ $student->enrollment_no }}</td></tr>
                @endif
                @if($student->stream?->course)
                <tr><td class="text-muted small">Course</td><td class="small">{{ $student->stream->course->name }}</td></tr>
                @endif
                @if($student->stream)
                <tr><td class="text-muted small">Stream</td><td class="small">{{ $student->stream->name }}</td></tr>
                @endif
                @if($student->father_name)
                <tr><td class="text-muted small">Father</td><td class="small">{{ $student->father_name }}</td></tr>
                @endif
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <span class="fw-semibold">Issue Certificate</span>
        </div>
        <div class="card-body">
            <div class="alert alert-info py-2 small mb-3">
                <i class="bi bi-info-circle me-1"></i>
                Issue karne ke baad <strong>{{ $tempCert->certificate_number }}</strong> ya similar number assign hoga aur PDF download shuru ho jaayega.
            </div>
            <form method="POST" action="{{ route('certificate.store') }}">
                @csrf
                <input type="hidden" name="student_id" value="{{ $validated['student_id'] }}">
                <input type="hidden" name="certificate_type_id" value="{{ $validated['certificate_type_id'] }}">
                <input type="hidden" name="remarks" value="{{ $validated['remarks'] ?? '' }}">
                <button type="submit" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-award me-1"></i> Issue & Download PDF
                </button>
            </form>
            <a href="{{ route('certificate.create', ['student_id' => $student->id]) }}" class="btn btn-outline-secondary w-100">
                <i class="bi bi-arrow-left me-1"></i> Back / Edit
            </a>
        </div>
    </div>
</div>

</div>
@endsection
