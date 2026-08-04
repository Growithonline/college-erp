<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Before You Continue — Gaurangi Technologies</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ time() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: radial-gradient(ellipse at 60% 20%, #2d1b6b 0%, #1a0f3c 40%, #0d0820 100%);
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        .consent-card {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 20px;
            width: 100%;
            max-width: 720px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
            overflow: hidden;
        }

        .consent-header {
            padding: 24px 28px 18px;
            border-bottom: 1px solid #eef0f4;
        }

        .consent-header .eyebrow {
            font-size: 11px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #8b5cf6;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .consent-header h1 { font-size: 21px; font-weight: 700; color: #1a0f3c; margin: 0 0 4px; }
        .consent-header p  { font-size: 13px; color: #6b7280; margin: 0; }

        .doc-tabs {
            display: flex;
            border-bottom: 1px solid #eef0f4;
            padding: 0 28px;
            gap: 4px;
        }

        .doc-tab {
            border: none;
            background: none;
            padding: 14px 6px;
            font-size: 13.5px;
            font-weight: 600;
            color: #9ca3af;
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-right: 22px;
            transition: color 0.15s, border-color 0.15s;
        }

        .doc-tab i.status-icon { font-size: 15px; color: #d1d5db; }
        .doc-tab.active { color: #1a0f3c; border-color: #7c3aed; }
        .doc-tab.done i.status-icon { color: #22c55e; }
        .doc-tab.done.active { color: #16a34a; }

        .doc-body { padding: 20px 28px 0; }

        .doc-pane { display: none; }
        .doc-pane.active { display: block; }

        .doc-scroll {
            max-height: 320px;
            overflow-y: auto;
            border: 1px solid #eef0f4;
            border-radius: 12px;
            padding: 18px 20px;
            font-size: 13.5px;
            line-height: 1.7;
            color: #374151;
            background: #fafafa;
        }

        .doc-scroll h2 { font-size: 15px; font-weight: 700; color: #1a0f3c; margin: 18px 0 8px; }
        .doc-scroll h2:first-child { margin-top: 0; }
        .doc-scroll p { margin: 0 0 10px; }
        .doc-scroll ul { margin: 0 0 10px; padding-left: 20px; }

        .scroll-hint {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin: 10px 0 0;
            font-size: 12px;
            font-weight: 600;
            color: #7c3aed;
            transition: opacity 0.2s;
        }

        .scroll-hint.hidden { opacity: 0; pointer-events: none; }

        .confirm-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 20px 0;
            padding: 14px 16px;
            background: #f5f3ff;
            border: 1px solid #ddd6fe;
            border-radius: 10px;
            font-size: 12.5px;
            color: #4c1d95;
            line-height: 1.6;
        }

        .confirm-row input { margin-top: 3px; flex-shrink: 0; }

        .consent-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px 26px;
            gap: 12px;
        }

        .progress-note { font-size: 12px; color: #9ca3af; }

        .btn-continue {
            padding: 12px 26px;
            background: linear-gradient(135deg, #7c3aed, #6d28d9, #5b21b6);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 20px rgba(109,40,217,0.35);
        }

        .btn-continue:disabled {
            background: #d1d5db;
            box-shadow: none;
            cursor: not-allowed;
        }

        .btn-continue:not(:disabled):hover { transform: translateY(-1px); }

        .alert-box {
            margin: 16px 28px 0;
            padding: 10px 14px;
            border-radius: 8px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            font-size: 12.5px;
        }
    </style>
</head>
<body>

<div class="consent-card">

    <div class="consent-header">
        <p class="eyebrow">Before you continue</p>
        <h1>Review &amp; accept the platform agreement</h1>
        <p>{{ $institute->name ?? 'Your institute' }} must accept these documents once before the dashboard unlocks.</p>
    </div>

    @if ($errors->any())
        <div class="alert-box">{{ $errors->first() }}</div>
    @endif

    <div class="doc-tabs" id="docTabs">
        @foreach ($documents as $type => $meta)
            <button type="button" class="doc-tab @if($loop->first) active @endif" data-type="{{ $type }}">
                <i class="bi bi-check-circle-fill status-icon"></i>
                {{ $meta['label'] }}
            </button>
        @endforeach
    </div>

    <div class="doc-body">
        @foreach ($documents as $type => $meta)
            <div class="doc-pane @if($loop->first) active @endif" data-type="{{ $type }}">
                <div class="doc-scroll" data-type="{{ $type }}">
                    @include($meta['view'], ['institute' => $institute])
                </div>
                <p class="scroll-hint" data-type="{{ $type }}">
                    <i class="bi bi-arrow-down-circle"></i> Scroll down to read the full document
                </p>
            </div>
        @endforeach

        <div class="confirm-row">
            <input type="checkbox" id="confirmCheck">
            <label for="confirmCheck">
                I confirm that I have read and understood the Privacy Policy, Terms &amp; Conditions and Disclaimer above,
                and I accept them on behalf of {{ $institute->name ?? 'this institute' }}.
            </label>
        </div>
    </div>

    <form method="POST" action="{{ route('institute.consent.accept') }}" id="consentForm">
        @csrf
        <div class="consent-footer">
            <span class="progress-note" id="progressNote">0 of {{ count($documents) }} documents read</span>
            <button type="submit" class="btn-continue" id="continueBtn" disabled>Accept &amp; Continue</button>
        </div>
    </form>
</div>

<script>
(function () {
    const total       = {{ count($documents) }};
    const tabs        = Array.from(document.querySelectorAll('.doc-tab'));
    const panes       = Array.from(document.querySelectorAll('.doc-pane'));
    const scrollBoxes = Array.from(document.querySelectorAll('.doc-scroll'));
    const hints       = Array.from(document.querySelectorAll('.scroll-hint'));
    const confirmCheck = document.getElementById('confirmCheck');
    const continueBtn  = document.getElementById('continueBtn');
    const progressNote = document.getElementById('progressNote');
    const form          = document.getElementById('consentForm');
    const read = new Set();

    function activate(type) {
        tabs.forEach(t => t.classList.toggle('active', t.dataset.type === type));
        panes.forEach(p => p.classList.toggle('active', p.dataset.type === type));
        checkScrollState(type);
    }

    function checkScrollState(type) {
        const box  = scrollBoxes.find(b => b.dataset.type === type);
        const hint = hints.find(h => h.dataset.type === type);
        if (!box || !hint) return;
        const atBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 12;
        // Content that never overflows counts as read immediately.
        const noOverflow = box.scrollHeight <= box.clientHeight + 4;
        if (atBottom || noOverflow) {
            hint.classList.add('hidden');
            markRead(type);
        } else {
            hint.classList.remove('hidden');
        }
    }

    function markRead(type) {
        if (read.has(type)) return;
        read.add(type);
        const tab = tabs.find(t => t.dataset.type === type);
        if (tab) tab.classList.add('done');
        progressNote.textContent = read.size + ' of ' + total + ' documents read';
        updateButton();
    }

    function updateButton() {
        continueBtn.disabled = !(read.size === total && confirmCheck.checked);
    }

    tabs.forEach(tab => tab.addEventListener('click', () => activate(tab.dataset.type)));

    scrollBoxes.forEach(box => {
        box.addEventListener('scroll', () => checkScrollState(box.dataset.type));
        // Give the layout a tick to settle, then check if it already fits without scrolling.
        setTimeout(() => checkScrollState(box.dataset.type), 50);
    });

    confirmCheck.addEventListener('change', updateButton);

    form.addEventListener('submit', function (e) {
        if (continueBtn.disabled) {
            e.preventDefault();
            return;
        }
        Array.from(document.querySelectorAll('.doc-tab')).forEach(t => {
            const input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = 'accepted[]';
            input.value = t.dataset.type;
            form.appendChild(input);
        });
    });
})();
</script>
</body>
</html>
