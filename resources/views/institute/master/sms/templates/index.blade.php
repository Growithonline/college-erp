@extends('institute.layout')
@section('title', 'Message Templates')
@section('breadcrumb', 'Master / SMS / Message Templates')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('master.sms.index') }}" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Back to SMS Settings
        </a>
        <h4 class="mb-0 fw-bold mt-1">Message Templates</h4>
        <small class="text-muted">DLT-registered templates per message type — used for notices, fee alerts, admission, and more</small>
    </div>
</div>

@if(! $smsConfigured)
<div class="alert alert-warning mb-4">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>SMS Provider not configured.</strong>
    <a href="{{ route('master.sms.index') }}" class="alert-link">Configure SMS provider</a> first — templates below won't be used until then.
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Type</th>
                    <th>Category</th>
                    <th>DLT Template ID</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $type => $row)
                    @php($t = $row['template'])
                    <tr>
                        <td>
                            <div class="fw-semibold small">{{ $row['meta']['label'] }}</div>
                            @if(! $row['meta']['wired'])
                                <span class="badge bg-secondary-subtle text-secondary border small">Feature not built yet — template ready for later</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ ($t->category ?? $row['meta']['category']) === 'promotional' ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-info-subtle text-info border border-info-subtle' }}">
                                {{ ucfirst($t->category ?? $row['meta']['category']) }}
                            </span>
                        </td>
                        <td>
                            @if($t && $t->dlt_template_id)
                                <code class="small">{{ $t->dlt_template_id }}</code>
                            @else
                                <span class="text-muted small">Not set</span>
                            @endif
                        </td>
                        <td>
                            @if(! $t)
                                <span class="badge bg-secondary-subtle text-secondary border">Not configured</span>
                            @elseif($t->is_active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border">Disabled</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal_{{ $type }}">
                                <i class="bi bi-pencil-square me-1"></i>{{ $t ? 'Edit' : 'Set Up' }}
                            </button>
                            @if($t)
                            <form method="POST" action="{{ route('master.sms.templates.toggle') }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="type" value="{{ $type }}">
                                <button type="submit" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi {{ $t->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>

                    {{-- Edit modal --}}
                    <div class="modal fade" id="editModal_{{ $type }}" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('master.sms.templates.save') }}">
                                    @csrf
                                    <input type="hidden" name="type" value="{{ $type }}">
                                    <div class="modal-header">
                                        <h6 class="modal-title fw-bold">{{ $row['meta']['label'] }} Template</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Category</label>
                                            <select name="category" class="form-select form-select-sm">
                                                <option value="transactional" {{ ($t->category ?? $row['meta']['category']) === 'transactional' ? 'selected' : '' }}>Transactional</option>
                                                <option value="promotional" {{ ($t->category ?? $row['meta']['category']) === 'promotional' ? 'selected' : '' }}>Promotional</option>
                                            </select>
                                            <div class="form-text">Promotional uses the Promotional Sender ID from SMS Settings (if set).</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">DLT Template ID <span class="text-muted fw-normal">(Fast2SMS "Message ID")</span></label>
                                            <input type="text" name="dlt_template_id" class="form-control form-control-sm"
                                                   value="{{ $t->dlt_template_id ?? '' }}" placeholder="e.g. 223180">
                                            <div class="form-text">Copy from your Fast2SMS DLT template dashboard. Required for DLT-compliant delivery on Fast2SMS.</div>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">Content <span class="text-danger">*</span></label>
                                            <textarea name="content" class="form-control form-control-sm template-content" rows="4" maxlength="1000">{{ $t->content ?? '' }}</textarea>
                                            <div class="form-text">Must match your DLT registered template exactly, word for word.</div>
                                        </div>
                                        <div class="card border-0 bg-info-subtle p-3 rounded-3">
                                            <p class="small fw-semibold mb-1">Available Variables:</p>
                                            <div class="d-flex flex-wrap gap-2">
                                                @foreach($row['meta']['vars'] as $var)
                                                    <span class="badge bg-white text-dark border insert-var" data-var="{{ '{' . $var . '}' }}" style="cursor:pointer;">{{ '{' . $var . '}' }}</span>
                                                @endforeach
                                            </div>
                                            <div class="form-text mt-1">Click a badge to insert a variable into the content above.</div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="bi bi-save me-1"></i>Save Template
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
document.querySelectorAll('.insert-var').forEach(badge => {
    badge.addEventListener('click', () => {
        const textarea = badge.closest('.modal-body').querySelector('.template-content');
        const pos = textarea.selectionStart;
        const v = badge.dataset.var;
        textarea.value = textarea.value.substring(0, pos) + v + textarea.value.substring(pos);
        textarea.focus();
        textarea.setSelectionRange(pos + v.length, pos + v.length);
    });
});
</script>

@endsection
