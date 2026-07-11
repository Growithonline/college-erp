<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @include('institute.transport.allocations._pass-card-style')
    </style>
</head>
<body>
    @foreach($passes as $pass)
        {{-- $loop->last, not a :last-child CSS selector — DomPDF's CSS3 pseudo-class
             support is inconsistent, so a stray blank trailing page is easy to end up
             with if the "don't break after the last card" rule lives in CSS. --}}
        <div class="page{{ $loop->last ? '' : ' page-break' }}">
            @php $allocation = $pass['allocation']; $qrSvg = $pass['qrSvg']; @endphp
            @include('institute.transport.allocations._pass-card')
        </div>
    @endforeach
</body>
</html>
