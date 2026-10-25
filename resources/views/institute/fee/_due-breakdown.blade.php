{{-- Which fee items a carried-forward "Previous Due" is made of, as they stood at promotion. $breakdown: array|null of ['label','amount'] --}}
@if(!empty($breakdown))
<div class="text-muted" style="font-size:11px;line-height:1.35;">
    <span class="fw-semibold">Due at promotion:</span>
    @foreach($breakdown as $b)
        {{ $b['label'] }} ₹{{ number_format((float) $b['amount'], 2) }}@if(!$loop->last) · @endif
    @endforeach
</div>
@endif
