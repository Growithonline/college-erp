{{--
  Reusable partial: Document Upload Settings block
  Variables:
    $model        — Center / ChannelPartner / StaffMember instance (nullable for create)
    $idPrefix     — string prefix for HTML ids to avoid collisions (e.g. 'c', 'p', 'sm')
    $formTypeField — name of the admission_form_type radio field in THIS form (e.g. 'admission_form_type')
    $formTypeValue — current selected value ('both'/'quick'/'full')
--}}

@php
    $fullVal  = old('doc_full_form_upload',  $model?->doc_full_form_upload  ?? 'skip');
    $quickVal = old('doc_quick_form_upload', $model?->doc_quick_form_upload ?? 'skip');
    $idPfx    = $idPrefix ?? 'du';
    $options  = [
        'skip'     => ['label' => 'Skip',     'icon' => 'bi-dash-circle',    'color' => 'secondary', 'desc' => 'No document step'],
        'optional' => ['label' => 'Optional', 'icon' => 'bi-info-circle',    'color' => 'warning',   'desc' => 'Soft reminder, can skip'],
        'required' => ['label' => 'Required', 'icon' => 'bi-exclamation-circle', 'color' => 'danger','desc' => 'Hard block until uploaded'],
    ];
@endphp

<div class="card border-0 bg-light mb-3" id="{{ $idPfx }}_docUploadCard">
    <div class="card-body">
        <label class="form-label fw-semibold d-flex align-items-center gap-2">
            <i class="bi bi-paperclip text-primary"></i>
            Document Upload Settings
        </label>
        <small class="text-muted d-block mb-3">
            Admission submit ke baad document upload step show hoga ya nahi — form type ke hisaab se alag-alag control karo.
        </small>

        {{-- Full Form Row --}}
        <div class="mb-3" id="{{ $idPfx }}_fullFormRow">
            <div class="text-muted small fw-semibold mb-2">
                <i class="bi bi-file-earmark-person me-1"></i>Full Form
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @foreach($options as $val => $opt)
                <div>
                    <input type="radio" class="btn-check" name="doc_full_form_upload"
                           id="{{ $idPfx }}_full_{{ $val }}" value="{{ $val }}"
                           @checked($fullVal === $val)>
                    <label class="btn btn-sm btn-outline-{{ $opt['color'] }}" for="{{ $idPfx }}_full_{{ $val }}"
                           data-bs-toggle="tooltip" title="{{ $opt['desc'] }}">
                        <i class="bi {{ $opt['icon'] }} me-1"></i>{{ $opt['label'] }}
                    </label>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Quick Form Row --}}
        <div id="{{ $idPfx }}_quickFormRow">
            <div class="text-muted small fw-semibold mb-2">
                <i class="bi bi-lightning me-1"></i>Quick Form
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @foreach($options as $val => $opt)
                <div>
                    <input type="radio" class="btn-check" name="doc_quick_form_upload"
                           id="{{ $idPfx }}_quick_{{ $val }}" value="{{ $val }}"
                           @checked($quickVal === $val)>
                    <label class="btn btn-sm btn-outline-{{ $opt['color'] }}" for="{{ $idPfx }}_quick_{{ $val }}"
                           data-bs-toggle="tooltip" title="{{ $opt['desc'] }}">
                        <i class="bi {{ $opt['icon'] }} me-1"></i>{{ $opt['label'] }}
                    </label>
                </div>
                @endforeach
            </div>
        </div>

    </div>
</div>

<script>
(function() {
    // Show/hide rows based on selected form type
    function syncDocRows_{{ $idPfx }}() {
        var selected = document.querySelector('input[name="{{ $formTypeField ?? 'admission_form_type' }}"]:checked');
        var val = selected ? selected.value : 'both';
        var fullRow  = document.getElementById('{{ $idPfx }}_fullFormRow');
        var quickRow = document.getElementById('{{ $idPfx }}_quickFormRow');
        if (!fullRow || !quickRow) return;
        fullRow.style.display  = (val === 'quick') ? 'none' : '';
        quickRow.style.display = (val === 'full')  ? 'none' : '';
    }

    document.addEventListener('DOMContentLoaded', function() {
        syncDocRows_{{ $idPfx }}();
        document.querySelectorAll('input[name="{{ $formTypeField ?? 'admission_form_type' }}"]').forEach(function(el) {
            el.addEventListener('change', syncDocRows_{{ $idPfx }});
        });
    });
})();
</script>
