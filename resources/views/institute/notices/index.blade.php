@extends($layout ?? 'institute.layout')
@section('title', 'Notices')
@section('breadcrumb', 'Notices')

@section('content')
@php
    $canManage = auth()->guard('web')->check() ||
                 (auth()->guard('staff')->check() && auth()->guard('staff')->user()->can_manage_notices);
@endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-megaphone-fill me-2 text-primary"></i>Notices</h4>
        <small class="text-muted">Institute notices manage karo</small>
    </div>
    @if($canManage)
    <a href="{{ route(($rp ?? 'notices') . '.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Notice
    </a>
    @endif
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($notices->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-megaphone fs-1 d-block mb-2"></i>
                Koi notice nahi hai. Pehla notice add karo!
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Title</th>
                        <th>Type</th>
                        <th>Visible To</th>
                        <th>Date</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th>Reads</th>
                        <th>Email Sent</th>
                        <th>Posted By</th>
                        <th class="pe-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notices as $notice)
                    @php
                        $typeColors = [
                            'exam'    => ['bg' => '#fef2f2', 'text' => '#dc2626'],
                            'fee'     => ['bg' => '#fffbeb', 'text' => '#d97706'],
                            'holiday' => ['bg' => '#f0fdf4', 'text' => '#16a34a'],
                            'urgent'  => ['bg' => '#fef2f2', 'text' => '#dc2626'],
                            'event'   => ['bg' => '#f0fdf4', 'text' => '#16a34a'],
                            'general' => ['bg' => '#eff6ff', 'text' => '#2563eb'],
                        ];
                        $tc = $typeColors[$notice->notice_type] ?? $typeColors['general'];
                    @endphp
                    <tr>
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-2">
                                @if($notice->is_pinned)
                                    <i class="bi bi-pin-angle-fill text-warning" title="Pinned"></i>
                                @endif
                                <div>
                                    <div class="fw-semibold">{{ $notice->title }}</div>
                                    @if($notice->attachment)
                                        <small>
                                            <a href="{{ Storage::url($notice->attachment) }}" target="_blank" class="text-muted text-decoration-none">
                                                <i class="bi bi-paperclip"></i> Attachment
                                            </a>
                                        </small>
                                    @endif
                                    @if($notice->scheduled_at && $notice->scheduled_at->gt(now()))
                                        <br><small class="text-warning">
                                            <i class="bi bi-clock me-1"></i>Scheduled: {{ $notice->scheduled_at->format('d M Y, H:i') }}
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge rounded-pill px-2 py-1"
                                  style="background:{{ $tc['bg'] }};color:{{ $tc['text'] }};font-size:11px;">
                                {{ \App\Models\Notice::TYPES[$notice->notice_type] ?? ucfirst($notice->notice_type) }}
                            </span>
                        </td>
                        <td>
                            <small>{{ collect((array) $notice->visible_to)->map(fn($v) => \App\Models\Notice::VISIBLE_TO[$v] ?? $v)->implode(', ') }}</small>
                        </td>
                        <td><small>{{ $notice->notice_date->format('d M Y') }}</small></td>
                        <td><small>{{ $notice->expires_at ? $notice->expires_at->format('d M Y') : '—' }}</small></td>
                        <td>
                            @if($notice->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success">Active</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            @php $rc = $notice->reads_count ?? 0; @endphp
                            <button type="button"
                                    class="btn btn-link btn-sm p-0 text-decoration-none reads-btn"
                                    data-url="{{ route(($rp ?? 'notices') . '.reads', $notice) }}"
                                    data-title="{{ $notice->title }}">
                                <i class="bi bi-eye me-1 {{ $rc > 0 ? 'text-success' : 'text-muted' }}"></i>
                                <span class="{{ $rc > 0 ? 'text-success fw-semibold' : 'text-muted' }}">{{ $rc }}</span>
                            </button>
                        </td>
                        <td>
                            @if($notice->email_to)
                                <small class="text-success">
                                    <i class="bi bi-envelope-check me-1"></i>
                                    {{ implode(', ', array_map('ucfirst', explode(',', $notice->email_to))) }}
                                </small>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                        <td><small>{{ $notice->postedByStaff?->name ?? 'Admin' }}</small></td>
                        <td class="text-end pe-3">
                            @if($canManage)
                            <div class="d-flex gap-1 justify-content-end">
                                {{-- Pin / Unpin --}}
                                <form method="POST"
                                      action="{{ route(($rp ?? 'notices') . '.pin', $notice) }}"
                                      class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="btn btn-sm {{ $notice->is_pinned ? 'btn-warning' : 'btn-outline-warning' }}"
                                            title="{{ $notice->is_pinned ? 'Unpin' : 'Pin' }}">
                                        <i class="bi bi-pin-angle{{ $notice->is_pinned ? '-fill' : '' }}"></i>
                                    </button>
                                </form>
                                {{-- Toggle Active --}}
                                <form method="POST"
                                      action="{{ route(($rp ?? 'notices') . '.toggle', $notice) }}"
                                      class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="btn btn-sm {{ $notice->is_active ? 'btn-success' : 'btn-outline-secondary' }}"
                                            title="{{ $notice->is_active ? 'Deactivate' : 'Activate' }}">
                                        <i class="bi bi-{{ $notice->is_active ? 'toggle-on' : 'toggle-off' }}"></i>
                                    </button>
                                </form>
                                {{-- Edit --}}
                                <a href="{{ route(($rp ?? 'notices') . '.edit', $notice) }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                {{-- Delete --}}
                                <form method="POST"
                                      action="{{ route(($rp ?? 'notices') . '.destroy', $notice) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('Is notice ko delete karein?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

@if($notices->hasPages())
<div class="mt-3">{{ $notices->links() }}</div>
@endif

{{-- Reads Detail Modal --}}
<div class="modal fade" id="readsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-eye me-1 text-success"></i>
                    <span id="readsModalTitle">Who read this?</span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="readsModalBody">
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm me-2"></div> Loading...
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.reads-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var url   = this.dataset.url;
        var title = this.dataset.title;
        document.getElementById('readsModalTitle').textContent = title;
        var body  = document.getElementById('readsModalBody');
        body.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div> Loading...</div>';
        new bootstrap.Modal(document.getElementById('readsModal')).show();

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(rows) {
                // Update the count badge on the button
                var countEl = btn.querySelector('span');
                if (countEl) {
                    countEl.textContent = rows.length;
                    countEl.className = rows.length > 0 ? 'text-success fw-semibold' : 'text-muted';
                    btn.querySelector('i').className = 'bi bi-eye me-1 ' + (rows.length > 0 ? 'text-success' : 'text-muted');
                }

                if (!rows.length) {
                    body.innerHTML = '<div class="text-center py-4 text-muted"><i class="bi bi-eye-slash fs-4 d-block mb-1"></i>Abhi kisi ne nahi padha.</div>';
                    return;
                }
                var html = '<table class="table table-sm table-hover align-middle mb-0" style="font-size:13px;"><thead class="table-light"><tr><th class="ps-3">Type</th><th>Name</th><th>Read At</th></tr></thead><tbody>';
                rows.forEach(function(r) {
                    html += '<tr><td class="ps-3"><span class="badge bg-secondary-subtle text-secondary border">' + r.type + '</span></td><td>' + r.name + '</td><td class="text-muted">' + (r.read_at || '—') + '</td></tr>';
                });
                html += '</tbody></table>';
                body.innerHTML = html;
            })
            .catch(function() {
                body.innerHTML = '<div class="text-center py-4 text-danger">Error loading data.</div>';
            });
    });
});
</script>
@endpush
@endsection
