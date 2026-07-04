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
                <div style="border:2px solid {{ $color }}; padding:6px; background:#fff; font-family: Georgia, serif; position:relative;">
                <div style="border:1px solid {{ $color }}; padding:26px 30px; position:relative;">
                    @if($settings->logo)
                        <div style="position:absolute; top:50%; left:50%; width:220px; height:220px; margin:-110px 0 0 -110px; opacity:.05; text-align:center; z-index:0;">
                            <img src="{{ Storage::url($settings->logo) }}" style="width:100%; height:100%; object-fit:contain;">
                        </div>
                    @endif
                    <div style="position:relative; z-index:1;">
                    {{-- Header --}}
                    <div style="text-align:center; margin-bottom:6px;">
                        @if($settings->logo)
                            <img src="{{ Storage::url($settings->logo) }}" style="max-height:65px; max-width:140px; margin-bottom:8px; display:block; margin-left:auto; margin-right:auto;">
                        @endif
                        @if($settings->header_line1) <div style="font-size:20px; font-weight:bold; color:{{ $color }}; letter-spacing:1.5px; text-transform:uppercase;">{{ $settings->header_line1 }}</div> @endif
                        @if($settings->header_line2) <div style="font-size:12px; color:#555; margin-top:4px;">{{ $settings->header_line2 }}</div> @endif
                        @if($settings->header_line3) <div style="font-size:11px; color:#777; margin-top:2px;">{{ $settings->header_line3 }}</div> @endif
                    </div>
                    <div style="text-align:center; margin:14px 0 16px; color:{{ $color }};">
                        <span style="display:inline-block; vertical-align:middle; width:110px; height:1px; background:{{ $color }};"></span>
                        <span style="display:inline-block; vertical-align:middle; width:7px; height:7px; background:{{ $color }}; transform:rotate(45deg); margin:0 10px;"></span>
                        <span style="display:inline-block; vertical-align:middle; width:110px; height:1px; background:{{ $color }};"></span>
                    </div>
                    <div style="text-align:center; font-size:18px; font-weight:bold; letter-spacing:4px; text-transform:uppercase; color:{{ $color }}; margin:0 0 18px;">{{ $type->name }}</div>
                    <div style="display:flex; justify-content:space-between; font-size:10.5px; color:#666; margin-bottom:18px; text-transform:uppercase; letter-spacing:.3px;">
                        <span>Certificate No.: <strong style="color:#333; text-transform:none;">{{ $tempCert->certificate_number }}</strong> <em style="text-transform:none;">(draft)</em></span>
                        <span>Date of Issue: <strong style="color:#333; text-transform:none;">{{ now()->format('d/m/Y') }}</strong></span>
                    </div>
                    <div style="line-height:2; font-size:13.5px; text-align:justify;">{!! $bodyHtml !!}</div>
                    <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-top:48px;">
                        <div style="text-align:center; width:38%;">
                            <div style="height:44px; display:flex; align-items:flex-end; justify-content:center;">
                                @if($settings->principal_signature) <img src="{{ Storage::url($settings->principal_signature) }}" style="max-height:44px; max-width:150px;"> @endif
                            </div>
                            <div style="border-top:1px solid #555; padding-top:5px; font-size:12.5px; font-weight:bold;">{{ $settings->principal_name ?: '________________________' }}</div>
                            <div style="font-size:10.5px; color:#666; text-transform:uppercase; letter-spacing:1px;">{{ $settings->principal_designation ?: 'Principal' }}</div>
                        </div>
                        <div style="text-align:center; width:22%;">
                            @if($settings->seal_image) <img src="{{ Storage::url($settings->seal_image) }}" style="max-height:74px; max-width:74px;">
                            @else <div style="width:70px; height:70px; border:1.5px dashed {{ $color }}88; border-radius:50%; margin:0 auto; display:flex; align-items:center; justify-content:center; font-size:8px; color:{{ $color }}99; letter-spacing:1px; text-transform:uppercase;">Official<br>Seal</div> @endif
                        </div>
                        <div style="text-align:center; width:38%;">
                            <div style="height:44px; display:flex; align-items:flex-end; justify-content:center;">
                                @if($settings->registrar_signature) <img src="{{ Storage::url($settings->registrar_signature) }}" style="max-height:44px; max-width:150px;"> @endif
                            </div>
                            <div style="border-top:1px solid #555; padding-top:5px; font-size:12.5px; font-weight:bold;">{{ $settings->registrar_name ?: '________________________' }}</div>
                            <div style="font-size:10.5px; color:#666; text-transform:uppercase; letter-spacing:1px;">{{ $settings->registrar_designation ?: 'Registrar' }}</div>
                        </div>
                    </div>
                    <div style="text-align:center; margin-top:28px; font-size:9px; color:#999;">This is a computer-generated certificate and does not require a physical signature to be valid.</div>
                    </div>
                </div>
                </div>

                @elseif($settings->theme === 'colored')
                <div style="border:1px solid #e2e2e2; background:#fff; overflow:hidden;">
                    <div style="background:{{ $color }}; color:#fff; padding:18px 24px; display:flex; align-items:center; gap:16px;">
                        @if($settings->logo) <img src="{{ Storage::url($settings->logo) }}" style="max-height:58px; max-width:120px; background:rgba(255,255,255,.95); padding:4px; border-radius:4px;"> @endif
                        <div style="flex:1;">
                            @if($settings->header_line1) <div style="font-size:18px; font-weight:bold;">{{ $settings->header_line1 }}</div> @endif
                            @if($settings->header_line2) <div style="font-size:11.5px; opacity:.88; margin-top:3px;">{{ $settings->header_line2 }}</div> @endif
                            @if($settings->header_line3) <div style="font-size:10.5px; opacity:.72; margin-top:1px;">{{ $settings->header_line3 }}</div> @endif
                        </div>
                        @if($settings->seal_image) <div style="width:52px; height:52px; border-radius:50%; background:rgba(255,255,255,.95); display:flex; align-items:center; justify-content:center;"><img src="{{ Storage::url($settings->seal_image) }}" style="max-height:46px;max-width:46px;"></div> @endif
                    </div>
                    <div style="height:5px; background:linear-gradient(90deg, {{ $color }}, {{ $color }}66, {{ $color }});"></div>
                    <div style="padding:22px 28px 18px;">
                        <div style="text-align:center; margin:4px 0 18px;"><span style="font-size:16px; font-weight:bold; letter-spacing:3px; text-transform:uppercase; color:{{ $color }}; padding-bottom:7px; border-bottom:3px solid {{ $color }};">{{ $type->name }}</span></div>
                        <div style="display:flex; justify-content:space-between; font-size:10.5px; color:#666; margin-bottom:18px; text-transform:uppercase; letter-spacing:.3px;">
                            <span>Certificate No.: <strong style="color:#333; text-transform:none;">{{ $tempCert->certificate_number }}</strong> <em style="text-transform:none;">(draft)</em></span>
                            <span>Date of Issue: <strong style="color:#333; text-transform:none;">{{ now()->format('d/m/Y') }}</strong></span>
                        </div>
                        <div style="line-height:2; font-size:13.5px; text-align:justify;">{!! $bodyHtml !!}</div>
                        <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-top:44px; padding-top:18px; border-top:1px solid #ececec;">
                            <div style="text-align:center; width:38%;">
                                <div style="height:42px; display:flex; align-items:flex-end; justify-content:center;">
                                    @if($settings->principal_signature) <img src="{{ Storage::url($settings->principal_signature) }}" style="max-height:42px; max-width:150px;"> @endif
                                </div>
                                <div style="border-top:1px solid #555; padding-top:5px; font-size:12.5px; font-weight:bold;">{{ $settings->principal_name ?: '________________________' }}</div>
                                <div style="font-size:10.5px; color:#666; text-transform:uppercase; letter-spacing:1px;">{{ $settings->principal_designation ?: 'Principal' }}</div>
                            </div>
                            <div style="text-align:center; width:22%;">
                                @if(!$settings->seal_image) <div style="width:64px; height:64px; border:1.5px dashed {{ $color }}88; border-radius:50%; margin:0 auto; display:flex; align-items:center; justify-content:center; font-size:8px; color:{{ $color }}99; letter-spacing:1px; text-transform:uppercase;">Official<br>Seal</div> @endif
                            </div>
                            <div style="text-align:center; width:38%;">
                                <div style="height:42px; display:flex; align-items:flex-end; justify-content:center;">
                                    @if($settings->registrar_signature) <img src="{{ Storage::url($settings->registrar_signature) }}" style="max-height:42px; max-width:150px;"> @endif
                                </div>
                                <div style="border-top:1px solid #555; padding-top:5px; font-size:12.5px; font-weight:bold;">{{ $settings->registrar_name ?: '________________________' }}</div>
                                <div style="font-size:10.5px; color:#666; text-transform:uppercase; letter-spacing:1px;">{{ $settings->registrar_designation ?: 'Registrar' }}</div>
                            </div>
                        </div>
                    </div>
                    <div style="background:{{ $color }}12; border-top:2px solid {{ $color }}44; padding:10px 24px; font-size:10px; color:#666; text-align:center;">
                        {{ $settings->header_line1 }}@if($settings->header_line2) &nbsp;•&nbsp; {{ $settings->header_line2 }} @endif
                    </div>
                    <div style="text-align:center; padding:8px 0 12px; font-size:8.5px; color:#999;">This is a computer-generated certificate and does not require a physical signature to be valid.</div>
                </div>

                @else {{-- minimal --}}
                <div style="border:1px solid #eee; padding:12px; background:#fff; font-family: Arial, sans-serif;">
                <div style="padding:16px 18px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; padding-bottom:16px; border-bottom:3px solid {{ $color }}; margin-bottom:24px;">
                        <div style="display:flex; align-items:center; gap:14px;">
                            @if($settings->logo) <img src="{{ Storage::url($settings->logo) }}" style="max-height:58px; max-width:130px;"> @endif
                            <div>
                                @if($settings->header_line1) <div style="font-size:16px; font-weight:bold; color:{{ $color }};">{{ $settings->header_line1 }}</div> @endif
                                @if($settings->header_line2) <div style="font-size:11px; color:#555; margin-top:3px;">{{ $settings->header_line2 }}</div> @endif
                                @if($settings->header_line3) <div style="font-size:10px; color:#777; margin-top:2px;">{{ $settings->header_line3 }}</div> @endif
                            </div>
                        </div>
                        @if($settings->seal_image) <img src="{{ Storage::url($settings->seal_image) }}" style="max-height:54px;max-width:54px;opacity:.9;"> @endif
                    </div>
                    <div style="text-align:center; font-size:18px; font-weight:bold; letter-spacing:4px; text-transform:uppercase; color:{{ $color }}; margin-bottom:6px;">{{ $type->name }}</div>
                    <div style="text-align:center; margin-bottom:22px;"><span style="display:inline-block; width:80px; border-bottom:2px solid {{ $color }};"></span></div>
                    <div style="display:flex; justify-content:space-between; font-size:10.5px; color:#666; margin-bottom:18px; text-transform:uppercase; letter-spacing:.3px;">
                        <span>Certificate No.: <strong style="color:#333; text-transform:none;">{{ $tempCert->certificate_number }}</strong> <em style="text-transform:none;">(draft)</em></span>
                        <span>Date of Issue: <strong style="color:#333; text-transform:none;">{{ now()->format('d F, Y') }}</strong></span>
                    </div>
                    <div style="line-height:2; font-size:13.5px; text-align:justify;">{!! $bodyHtml !!}</div>
                    <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-top:52px;">
                        <div style="width:40%;">
                            @if($settings->principal_signature) <img src="{{ Storage::url($settings->principal_signature) }}" style="max-height:44px; display:block; margin-bottom:4px;"> @endif
                            <div style="border-top:1px solid #555; padding-top:5px; font-size:12.5px; font-weight:bold;">{{ $settings->principal_name ?: '________________________' }}</div>
                            <div style="font-size:10.5px; color:#666; text-transform:uppercase; letter-spacing:1px;">{{ $settings->principal_designation ?: 'Principal' }}</div>
                        </div>
                        <div style="width:20%; text-align:center;">
                            @if($settings->seal_image) <img src="{{ Storage::url($settings->seal_image) }}" style="max-height:56px;max-width:56px;opacity:.9;">
                            @else <div style="width:56px; height:56px; border:1.5px dashed {{ $color }}88; border-radius:50%; margin:0 auto; display:flex; align-items:center; justify-content:center; font-size:7px; color:{{ $color }}99; letter-spacing:1px; text-transform:uppercase;">Official<br>Seal</div> @endif
                        </div>
                        <div style="width:40%; text-align:right;">
                            @if($settings->registrar_signature) <img src="{{ Storage::url($settings->registrar_signature) }}" style="max-height:44px; display:block; margin-left:auto; margin-bottom:4px;"> @endif
                            <div style="border-top:1px solid #555; padding-top:5px; font-size:12.5px; font-weight:bold;">{{ $settings->registrar_name ?: '________________________' }}</div>
                            <div style="font-size:10.5px; color:#666; text-transform:uppercase; letter-spacing:1px;">{{ $settings->registrar_designation ?: 'Registrar' }}</div>
                        </div>
                    </div>
                    <div style="text-align:center; margin-top:36px; font-size:8.5px; color:#999;">This is a computer-generated certificate and does not require a physical signature to be valid.</div>
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
