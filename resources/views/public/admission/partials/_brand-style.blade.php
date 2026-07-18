@php $brandColor = $institute->primary_color ?: '#2563EB'; @endphp
<style>
    :root { --bs-primary: {{ $brandColor }}; }
    .btn-primary {
        --bs-btn-bg: {{ $brandColor }};
        --bs-btn-border-color: {{ $brandColor }};
        --bs-btn-hover-bg: {{ $brandColor }};
        --bs-btn-hover-border-color: {{ $brandColor }};
        --bs-btn-active-bg: {{ $brandColor }};
        --bs-btn-active-border-color: {{ $brandColor }};
    }
    .text-primary { color: {{ $brandColor }} !important; }
    .border-primary { border-color: {{ $brandColor }} !important; }
    .bg-primary { background-color: {{ $brandColor }} !important; }
</style>
