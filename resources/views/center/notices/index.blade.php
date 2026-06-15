@extends('center.layout')
@section('title', 'Notices')
@section('breadcrumb', 'Notices')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-megaphone-fill me-2 text-primary"></i>Notices</h4>
        <small class="text-muted">Institute ki taraf se aaye hue notices</small>
    </div>
</div>

@php
$typeColors = [
    'exam'    => ['border' => '#dc2626', 'bg' => '#fef2f2', 'text' => '#dc2626', 'icon' => 'bi-pencil-square'],
    'fee'     => ['border' => '#d97706', 'bg' => '#fffbeb', 'text' => '#d97706', 'icon' => 'bi-cash'],
    'holiday' => ['border' => '#16a34a', 'bg' => '#f0fdf4', 'text' => '#16a34a', 'icon' => 'bi-calendar-check'],
    'urgent'  => ['border' => '#dc2626', 'bg' => '#fef2f2', 'text' => '#dc2626', 'icon' => 'bi-exclamation-triangle-fill'],
    'event'   => ['border' => '#16a34a', 'bg' => '#f0fdf4', 'text' => '#16a34a', 'icon' => 'bi-star'],
    'general' => ['border' => '#2563eb', 'bg' => '#eff6ff', 'text' => '#2563eb', 'icon' => 'bi-megaphone'],
];
@endphp

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @forelse($notices as $notice)
        @php
            $tc     = $typeColors[$notice->notice_type] ?? $typeColors['general'];
            $isNew  = $notice->created_at->gte(now()->subDays(3));
            $isRead = $notice->isReadBy('center', $center->id);
        @endphp
        <div class="p-3 border-bottom notice-item"
             style="border-left:4px solid {{ $tc['border'] }} !important;
                    background:{{ $isRead ? '#fff' : '#fafbff' }};
                    cursor:pointer;transition:background .15s;"
             data-notice-id="{{ $notice->id }}"
             onclick="markRead(this)">
            <div class="d-flex align-items-start gap-3">
                <div class="flex-shrink-0">
                    <div class="rounded-2 d-flex align-items-center justify-content-center"
                         style="width:36px;height:36px;background:{{ $tc['bg'] }};">
                        <i class="bi {{ $tc['icon'] }}" style="color:{{ $tc['text'] }};font-size:16px;"></i>
                    </div>
                </div>
                <div style="flex:1;min-width:0;">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        @if($notice->is_pinned)
                            <i class="bi bi-pin-angle-fill text-warning" title="Pinned"></i>
                        @endif
                        <span class="fw-bold" style="font-size:14px;">{{ $notice->title }}</span>
                        @if($isNew && !$isRead)
                            <span class="badge bg-danger new-badge" style="font-size:9px;">NEW</span>
                        @endif
                        @if($isRead)
                            <span class="badge bg-success-subtle text-success border" style="font-size:9px;"><i class="bi bi-check2"></i> Read</span>
                        @endif
                        <span class="badge rounded-pill"
                              style="background:{{ $tc['bg'] }};color:{{ $tc['text'] }};font-size:10px;">
                            {{ \App\Models\Notice::TYPES[$notice->notice_type] ?? ucfirst($notice->notice_type) }}
                        </span>
                    </div>
                    <p class="mb-2 text-muted" style="font-size:13px;line-height:1.6;white-space:pre-line;">{{ $notice->body }}</p>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <small class="text-muted" style="font-size:11px;">
                            <i class="bi bi-calendar3 me-1"></i>{{ $notice->notice_date->format('d M Y') }}
                        </small>
                        <small class="text-muted" style="font-size:11px;">
                            <i class="bi bi-person me-1"></i>{{ $notice->postedByStaff?->name ?? 'Institute Admin' }}
                        </small>
                        @if($notice->expires_at)
                        <small class="text-{{ $notice->expires_at->lt(now()->addDays(3)) ? 'warning' : 'muted' }}" style="font-size:11px;">
                            <i class="bi bi-clock me-1"></i>Expires: {{ $notice->expires_at->format('d M Y') }}
                        </small>
                        @endif
                        @if($notice->attachment)
                        <a href="{{ Storage::url($notice->attachment) }}" target="_blank"
                           class="text-decoration-none" style="font-size:12px;color:#2563eb;"
                           onclick="event.stopPropagation()">
                            <i class="bi bi-paperclip me-1"></i>View Attachment
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-5 text-muted">
            <i class="bi bi-megaphone fs-1 d-block mb-2" style="opacity:.3;"></i>
            Abhi koi notice nahi hai.
        </div>
        @endforelse
    </div>
</div>

@if($notices->hasPages())
<div class="mt-3">{{ $notices->links() }}</div>
@endif

@push('scripts')
<script>
var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

// Auto-mark all on page load
document.querySelectorAll('.notice-item[data-notice-id]').forEach(function(el) {
    markRead(el);
});

function markRead(el) {
    fetch('/center/notices/' + el.dataset.noticeId + '/read', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({})
    }).then(function(r) {
        if (!r.ok) return;
        el.style.background = '#fff';
        var b = el.querySelector('.new-badge'); if (b) b.remove();
        var row = el.querySelector('.fw-bold')?.parentElement;
        if (row && !row.querySelector('.read-badge')) {
            row.insertAdjacentHTML('beforeend', '<span class="badge bg-success-subtle text-success border read-badge" style="font-size:9px;"><i class="bi bi-check2"></i> Read</span>');
        }
    }).catch(function(){});
}
</script>
@endpush
@endsection
