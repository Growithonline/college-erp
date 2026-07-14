{{-- Shared by pass.blade.php (single) and pass-bulk.blade.php (multi-page). Expects
     $institute, $allocation, $qrSvg in scope. --}}
<div class="card">
    <table class="card-table" cellpadding="0" cellspacing="0">
        <colgroup>
            <col style="width: 48pt;">
            <col>
            <col style="width: 50pt;">
        </colgroup>
        <tr>
            <td colspan="3" class="header-cell">{{ $institute->name }} — Transport Pass</td>
        </tr>
        <tr class="body-row">
            <td class="photo-cell">
                <table class="photo-frame" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            @if($allocation->student?->photo)
                                <img src="{{ public_path('storage/' . $allocation->student->photo) }}">
                            @else
                                No Photo
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
            <td class="info-cell">
                <div class="student-name">{{ $allocation->student?->name ?? '—' }}</div>
                <div class="student-roll">{{ $allocation->student?->roll_no ?? $allocation->student?->student_uid ?? '—' }}</div>
                <table class="info-rows" cellpadding="0" cellspacing="0">
                    <colgroup>
                        <col style="width: 32pt;">
                        <col>
                    </colgroup>
                    <tr><td class="rlabel">Route</td><td class="rvalue">{{ $allocation->route?->name ?? '—' }}</td></tr>
                    <tr><td class="rlabel">Stop</td><td class="rvalue">{{ $allocation->stop?->stop_name ?? '—' }}</td></tr>
                    <tr><td class="rlabel">Vehicle</td><td class="rvalue">{{ $allocation->vehicle?->vehicle_no ?? '—' }}</td></tr>
                    <tr><td class="rlabel">Driver</td><td class="rvalue">{{ $allocation->driver?->name ?? '—' }}</td></tr>
                </table>
            </td>
            <td class="qr-cell">
                <div class="qr"><img src="{{ $qrSvg }}" alt="QR"></div>
                <div class="qr-caption">Scan for status</div>
            </td>
        </tr>
    </table>
</div>
