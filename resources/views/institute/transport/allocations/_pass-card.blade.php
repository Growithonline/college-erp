{{-- Shared by pass.blade.php (single) and pass-bulk.blade.php (multi-page). Expects
     $institute, $allocation, $qrSvg in scope. --}}
<div class="card">
    <div class="header">{{ $institute->name }} — Transport Pass</div>
    <div class="body">
        <div class="photo">
            @if($allocation->student?->photo)
                <img src="{{ public_path('storage/' . $allocation->student->photo) }}">
            @else
                No Photo
            @endif
        </div>
        <div class="info">
            <div class="student-name">{{ $allocation->student?->name ?? '—' }}</div>
            <div class="student-roll">{{ $allocation->student?->roll_no ?? $allocation->student?->student_uid ?? '—' }}</div>
            <div class="row"><span class="label">Route</span><span class="value">{{ $allocation->route?->name ?? '—' }}</span></div>
            <div class="row"><span class="label">Stop</span><span class="value">{{ $allocation->stop?->stop_name ?? '—' }}</span></div>
            <div class="row"><span class="label">Vehicle</span><span class="value">{{ $allocation->vehicle?->vehicle_no ?? '—' }}</span></div>
            <div class="row"><span class="label">Driver</span><span class="value">{{ $allocation->driver?->name ?? '—' }}</span></div>
        </div>
    </div>
    <div class="qr">
        {{-- Raw SVG markup, not a data: URI — DomPDF does not reliably render
             data:image/svg+xml inside an <img> tag, but does render an inline <svg>
             element directly. $qrSvg is server-generated (never user input), so
             unescaped output here is safe. --}}
        {!! $qrSvg !!}
    </div>
</div>
