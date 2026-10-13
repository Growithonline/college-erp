{{-- Shared by the super admin institute page and the institute panel. Requires $institute (an Institute model). --}}
@php
    $embedSlug = $institute->short_name;
@endphp

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pb-0 pt-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-code-slash text-primary me-2"></i>Login Links &amp; Website Embed Codes</h6>
        <p class="small text-muted mb-0 mt-1">
            The direct link opens this institute's own login page. The embed code shows a small launcher card (no password fields) that opens the real login in a new tab.
        </p>
    </div>
    <div class="card-body">
        @if(blank($embedSlug) || $institute->status !== 'active')
            <div class="alert alert-warning small mb-0">
                Login links need an <strong>active</strong> institute with a <strong>Short Name</strong>.
            </div>
        @else
            @foreach(\App\Support\LoginScope::portals() as $portalKey => $portalCfg)
                @php
                    $directUrl = \App\Support\LoginScope::loginUrl($portalKey, $institute);
                    $iframeSrc = route('embed.login', ['slug' => $embedSlug, 'portal' => $portalKey]);
                    $iframeCode = '<iframe src="' . $iframeSrc . '" width="100%" height="260" style="border:0;display:block;max-width:460px;margin:0 auto;" loading="lazy"></iframe>';
                @endphp
                <div class="border rounded-3 p-3 {{ $loop->last ? '' : 'mb-3' }}">
                    <div class="fw-semibold mb-2">{{ $portalCfg['label'] }} Login</div>

                    <label class="form-label small text-muted mb-1">Direct link</label>
                    <div class="input-group input-group-sm mb-2">
                        <input type="text" class="form-control" id="embed-link-{{ $portalKey }}" value="{{ $directUrl }}" readonly>
                        <button type="button" class="btn btn-outline-primary" data-embed-copy="embed-link-{{ $portalKey }}">
                            <i class="bi bi-clipboard me-1"></i>Copy
                        </button>
                    </div>

                    <label class="form-label small text-muted mb-1">Iframe embed code</label>
                    <div class="input-group input-group-sm">
                        <textarea class="form-control font-monospace" rows="3" id="embed-code-{{ $portalKey }}" readonly style="font-size:12px;">{{ $iframeCode }}</textarea>
                        <button type="button" class="btn btn-outline-primary" data-embed-copy="embed-code-{{ $portalKey }}">
                            <i class="bi bi-clipboard me-1"></i>Copy
                        </button>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>

<script>
document.querySelectorAll('[data-embed-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var field = document.getElementById(btn.getAttribute('data-embed-copy'));
        if (!field) return;
        var done = function () {
            var old = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Copied';
            setTimeout(function () { btn.innerHTML = old; }, 1500);
        };
        field.select();
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(field.value).then(done);
        } else {
            document.execCommand('copy');
            done();
        }
    });
});
</script>
