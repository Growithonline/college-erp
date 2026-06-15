{{--
    Shared notice widget — use on any dashboard.
    Variables:
      $dashboardNotices    — Collection of Notice models
      $noticeViewRoute     — route name string for "View All" link  (e.g. 'notices.index')
      $noticeReaderType    — reader_type string: 'institute' | 'staff' | 'center' | 'partner'
      $noticeReaderId      — integer id of the logged-in user
      $noticeReadUrlPrefix — URL prefix for mark-read (e.g. '/notices/', '/staff/notices/', '/center/notices/')
--}}
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

<div class="card border-0 shadow-sm mb-4">
    <div class="section-header d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
        <h6 class="mb-0 fw-bold" style="font-size:13px;">
            <i class="bi bi-megaphone-fill me-2 text-primary"></i>Notices & Updates
        </h6>
        @if(isset($noticeViewRoute))
        <a href="{{ route($noticeViewRoute) }}" class="btn btn-outline-primary btn-sm" style="font-size:11px;">
            View All <i class="bi bi-arrow-right ms-1"></i>
        </a>
        @endif
    </div>

    <div class="card-body p-0">
        @forelse($dashboardNotices as $notice)
        @php
            $tc = $typeColors[$notice->notice_type] ?? $typeColors['general'];
            $isNew = $notice->created_at->gte(now()->subDays(3));
        @endphp
        <div class="notice-item d-flex gap-3 px-3 py-3 border-bottom align-items-start"
             style="border-left: 3px solid {{ $tc['border'] }} !important;"
             data-notice-id="{{ $notice->id }}"
             data-reader-type="{{ $noticeReaderType ?? '' }}"
             data-reader-id="{{ $noticeReaderId ?? '' }}">
            <div class="flex-shrink-0 mt-1">
                <div class="rounded-2 d-flex align-items-center justify-content-center"
                     style="width:32px;height:32px;background:{{ $tc['bg'] }};">
                    <i class="bi {{ $tc['icon'] }}" style="color:{{ $tc['text'] }};font-size:14px;"></i>
                </div>
            </div>
            <div style="min-width:0;flex:1;">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    @if($notice->is_pinned)
                        <i class="bi bi-pin-angle-fill text-warning" style="font-size:11px;" title="Pinned"></i>
                    @endif
                    <span class="fw-semibold" style="font-size:13px;">{{ $notice->title }}</span>
                    @if($isNew)
                        <span class="badge bg-danger" style="font-size:9px;padding:2px 5px;">NEW</span>
                    @endif
                    <span class="badge rounded-pill px-2"
                          style="background:{{ $tc['bg'] }};color:{{ $tc['text'] }};font-size:10px;">
                        {{ \App\Models\Notice::TYPES[$notice->notice_type] ?? ucfirst($notice->notice_type) }}
                    </span>
                </div>
                <p class="mb-1 text-muted" style="font-size:12px;line-height:1.5;">
                    {{ Str::limit($notice->body, 120) }}
                </p>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <small class="text-muted" style="font-size:11px;">
                        <i class="bi bi-calendar3 me-1"></i>{{ $notice->notice_date->format('d M Y') }}
                    </small>
                    @if($notice->expires_at)
                    <small class="text-muted" style="font-size:11px;">
                        <i class="bi bi-clock me-1"></i>Expires: {{ $notice->expires_at->format('d M Y') }}
                    </small>
                    @endif
                    @if($notice->attachment)
                    <a href="{{ Storage::url($notice->attachment) }}" target="_blank"
                       class="text-decoration-none" style="font-size:11px;color:#2563eb;">
                        <i class="bi bi-paperclip me-1"></i>Attachment
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-5 text-muted">
            <i class="bi bi-megaphone fs-1 d-block mb-2" style="opacity:.3;"></i>
            <small>Koi active notice nahi hai</small>
        </div>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
(function() {
    var baseUrl  = '{{ rtrim($noticeReadUrlPrefix ?? "/notices/", "/") }}/';
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    document.querySelectorAll('.notice-item[data-notice-id]').forEach(function(el) {
        fetch(baseUrl + el.dataset.noticeId + '/read', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({
                reader_type: el.dataset.readerType || '',
                reader_id:   el.dataset.readerId   || ''
            })
        }).catch(function(){});
    });
})();
</script>
@endpush
