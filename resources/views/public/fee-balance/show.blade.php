<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Know Your Fee Balance — {{ $institute->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('public.admission.partials._brand-style')
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #eef2f7;
            min-height: 100vh;
            color: #1e293b;
        }
        .fb-wrap { max-width: 480px; margin: 32px auto; padding: 0 16px; }
        .fb-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 8px 24px rgba(15,23,42,.06);
            overflow: hidden;
        }
        .fb-header {
            padding: 18px 28px 14px;
            text-align: center;
            border-bottom: 1px solid #f1f5f9;
        }
        .fb-logo { max-height: 42px; max-width: 160px; object-fit: contain; margin-bottom: 6px; }
        .fb-institute-name { font-size: .98rem; font-weight: 700; margin: 0; color: #0f172a; }
        .fb-subtitle {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: .68rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase;
            color: var(--bs-primary); margin-top: 5px;
        }

        .fb-steps { display: flex; align-items: center; padding: 12px 28px 0; }
        .fb-step-dot {
            width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: .68rem; font-weight: 700;
            background: #e2e8f0; color: #94a3b8;
            transition: background .2s, color .2s;
        }
        .fb-step-dot.done { background: var(--bs-primary); color: #fff; font-size: .76rem; }
        .fb-step-dot.done::before { content: "\2713"; }
        .fb-step-line { flex: 1; height: 2px; background: #e2e8f0; margin: 0 6px; transition: background .2s; }
        .fb-step-line.done { background: var(--bs-primary); }
        .fb-step-label { font-size: .68rem; color: #94a3b8; text-align: center; margin-top: 6px; }

        .fb-body { padding: 12px 24px 20px; }
        .fb-section-label {
            font-size: .66rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
            color: #94a3b8; display: flex; align-items: center; gap: 6px; margin: 12px 0 6px;
        }
        .fb-section-label:first-child { margin-top: 0; }
        .fb-section-label i { font-size: .8rem; }

        .form-label.small { color: #334155; font-weight: 600; margin-bottom: 3px; font-size: .82rem; }
        .form-select, .form-control {
            border-color: #e2e8f0; border-radius: 8px; padding: .4rem .7rem; font-size: .87rem;
        }
        .form-select:focus, .form-control:focus {
            border-color: var(--bs-primary); box-shadow: 0 0 0 .18rem color-mix(in srgb, var(--bs-primary) 18%, transparent);
        }
        .form-select:disabled { background-color: #f8fafc; color: #94a3b8; }
        .input-group .btn-outline-secondary { border-color: #e2e8f0; color: #64748b; }

        .fb-honeypot { position: absolute; left: -9999px; top: -9999px; }

        .fb-captcha-box {
            background: #f8fafc; border: 1px solid #eef2f7; border-radius: 10px;
            padding: 9px 12px; display: flex; align-items: center; gap: 10px; margin-top: 2px;
        }
        .fb-captcha-box i { color: var(--bs-primary); font-size: 1.05rem; }
        .fb-captcha-box .fb-captcha-q { font-weight: 700; font-size: .92rem; color: #0f172a; white-space: nowrap; }
        .fb-captcha-box input { border-radius: 8px; text-align: center; }
        .fb-refresh-btn {
            border: none; background: transparent; color: #94a3b8; padding: 4px 6px; line-height: 1;
        }
        .fb-refresh-btn:hover { color: var(--bs-primary); }

        .fb-submit-btn {
            width: 100%; padding: .55rem 1rem; border-radius: 10px; font-weight: 600; font-size: .92rem;
            margin-top: 14px; display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 4px 10px color-mix(in srgb, var(--bs-primary) 25%, transparent);
        }

        .fb-otp-icon {
            width: 44px; height: 44px; border-radius: 50%; background: color-mix(in srgb, var(--bs-primary) 12%, white);
            display: flex; align-items: center; justify-content: center; margin: 2px auto 12px;
        }
        .fb-otp-icon i { color: var(--bs-primary); font-size: 1.2rem; }
        #otpInput {
            text-align: center; font-size: 1.3rem; font-weight: 700; letter-spacing: .5em;
            padding-left: .5em;
        }
        #otpInput::placeholder { letter-spacing: normal; font-size: .85rem; font-weight: 400; }

        .fb-result-icon {
            width: 50px; height: 50px; border-radius: 50%; margin: 2px auto 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .fb-result-icon i { font-size: 1.4rem; }
        .fb-result-icon.due { background: #fef3ee; }
        .fb-result-icon.due i { color: #ea580c; }
        .fb-result-icon.clear { background: #ecfdf5; }
        .fb-result-icon.clear i { color: #059669; }
        .due-amount { font-size: 1.9rem; font-weight: 800; letter-spacing: -.02em; }

        .fb-footer-note { text-align: center; font-size: .7rem; color: #cbd5e1; margin-top: 12px; }

        .step { display: none; }
        .step.active { display: block; animation: fbFadeIn .25s ease; }
        @keyframes fbFadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }

        #formAlert { border-radius: 10px; font-size: .87rem; }
    </style>
</head>
<body>
    <div class="fb-wrap">
        <div class="fb-card">
            <div class="fb-header">
                @if($institute->image)
                    <img src="{{ asset('storage/' . $institute->image) }}" alt="{{ $institute->name }}" class="fb-logo d-block mx-auto">
                @endif
                <p class="fb-institute-name">{{ $institute->name }}</p>
                <div class="fb-subtitle"><i class="bi bi-wallet2"></i> Know Your Fee Balance</div>
            </div>

            <div class="fb-steps" id="fbSteps">
                <div class="fb-step-dot" id="dot1">1</div>
                <div class="fb-step-line" id="line1"></div>
                <div class="fb-step-dot" id="dot2">2</div>
                <div class="fb-step-line" id="line2"></div>
                <div class="fb-step-dot" id="dot3">3</div>
            </div>

            <div class="fb-body">
                <div id="formAlert" class="alert alert-danger py-2 px-3 d-none" role="alert"></div>

                {{-- Step 1: academic context + identity + captcha --}}
                <div id="step1" class="step active">
                    <form id="verifyForm" novalidate>
                        @csrf
                        <input type="text" name="website" class="fb-honeypot" tabindex="-1" autocomplete="off">

                        <div class="fb-section-label"><i class="bi bi-mortarboard"></i> Academic Details</div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small">Course Type *</label>
                                <select id="courseTypeSelect" class="form-select" required>
                                    <option value="">Select</option>
                                    @foreach($courseTypes as $courseType)
                                        <option value="{{ $courseType->id }}">{{ $courseType->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Course *</label>
                                <select id="courseSelect" name="course_id" class="form-select" required disabled>
                                    <option value="">Select type first</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-7">
                                <label class="form-label small">Stream *</label>
                                <select id="streamSelect" name="course_stream_id" class="form-select" required disabled>
                                    <option value="">Select course first</option>
                                </select>
                            </div>
                            <div class="col-5">
                                <label class="form-label small">Semester</label>
                                <select id="semesterSelect" name="semester" class="form-select" disabled>
                                    <option value="">Skip</option>
                                </select>
                            </div>
                        </div>

                        <input type="hidden" id="courseTypeIdInput" name="course_type_id">

                        <div class="fb-section-label"><i class="bi bi-person-badge"></i> Identity Verification</div>

                        <div class="mb-2">
                            <label class="form-label small">Search By *</label>
                            <div class="input-group">
                                <select name="identifier_type" id="identifierTypeSelect" class="form-select" style="max-width: 42%;" required>
                                    @foreach($identifierOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="identifier_value" id="identifierValueInput" class="form-control" placeholder="Enter value" required maxlength="100">
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small">Date of Birth *</label>
                                <input type="date" name="dob" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Mobile Number *</label>
                                <input type="text" name="mobile" class="form-control" required maxlength="20" placeholder="Registered no.">
                            </div>
                        </div>

                        <div class="fb-section-label"><i class="bi bi-shield-check"></i> Security Check</div>

                        <div class="fb-captcha-box">
                            <i class="bi bi-calculator"></i>
                            <span class="fb-captcha-q" id="captchaLabel">{{ $captchaQuestion }}</span>
                            <input type="number" name="captcha_answer" id="captchaAnswerInput" class="form-control form-control-sm" placeholder="?" required style="max-width:80px;">
                            <button type="button" id="refreshCaptchaBtn" class="fb-refresh-btn ms-auto" title="New question">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                        </div>

                        <button type="submit" id="verifyBtn" class="btn btn-primary fb-submit-btn">
                            <span>Continue</span> <i class="bi bi-arrow-right"></i>
                        </button>
                    </form>
                </div>

                {{-- Step 2: OTP --}}
                <div id="step2" class="step text-center">
                    <div class="fb-otp-icon"><i class="bi bi-shield-lock"></i></div>
                    <p class="text-muted small mb-2 px-2" id="otpIntro">An OTP has been sent to the mobile number on file.</p>
                    <div class="mb-2 text-start">
                        <label class="form-label small">Enter 6-digit OTP *</label>
                        <input type="text" id="otpInput" class="form-control" maxlength="6" inputmode="numeric" placeholder="——————" required>
                    </div>
                    <button type="button" id="verifyOtpBtn" class="btn btn-primary fb-submit-btn mb-1">
                        <span>Verify OTP</span> <i class="bi bi-check2"></i>
                    </button>
                    <button type="button" id="resendOtpBtn" class="btn btn-link btn-sm text-decoration-none">Resend OTP</button>
                </div>

                {{-- Step 3: result --}}
                <div id="step3" class="step text-center">
                    <div class="fb-result-icon due" id="resultIcon"><i class="bi bi-cash-coin"></i></div>
                    <div class="text-muted small mb-1">Your Fee Balance</div>
                    <div class="due-amount text-primary" id="dueAmount">-</div>
                    <div class="text-muted small mt-3">Contact the institute office for payment details.</div>
                </div>

                <div class="fb-footer-note"><i class="bi bi-lock-fill"></i> Verified with OTP · No data stored on this device</div>
            </div>
        </div>
    </div>

    @php
        // NOTE: @json() splits its argument on every top-level comma (see
        // Illuminate\View\Compilers\Concerns\CompilesJson::compileJson), so it
        // silently breaks on any expression containing commas of its own — an
        // array literal with multiple keys must be built into a plain variable
        // first and passed to @json() as a bare reference (zero commas).
        $courseTypesForJs = $courseTypes->map(fn ($ct) => [
            'id'      => $ct->id,
            'courses' => $ct->courses->map(fn ($c) => [
                'id'              => $c->id,
                'name'            => $c->name,
                'semesterOptions' => $c->semesterOptions(),
                'streams'         => $c->streams->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
            ]),
        ]);
    @endphp
    <script>
        const baseUrl = @json(url('/fee-balance/' . $institute->short_name));
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const alertBox = document.getElementById('formAlert');
        const courseTypesData = @json($courseTypesForJs);

        let otpToken = null;

        function showError(message) {
            alertBox.textContent = message;
            alertBox.classList.remove('d-none');
        }

        function clearError() {
            alertBox.classList.add('d-none');
        }

        function showStep(id) {
            document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
            document.getElementById(id).classList.add('active');

            const stepNum = { step1: 1, step2: 2, step3: 3 }[id];
            [1, 2, 3].forEach(n => {
                document.getElementById('dot' + n).classList.toggle('done', n <= stepNum);
                document.getElementById('dot' + n).textContent = n < stepNum ? '' : n;
            });
            [1, 2].forEach(n => {
                document.getElementById('line' + n).classList.toggle('done', n < stepNum);
            });
        }

        async function postJson(path, body) {
            const response = await fetch(baseUrl + path, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify(body),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                if (data.field === 'captcha' && data.question) {
                    document.getElementById('captchaLabel').textContent = data.question;
                    document.getElementById('captchaAnswerInput').value = '';
                }
                throw new Error(data.message || 'Something went wrong.');
            }
            return data;
        }

        // ── Cascading dropdowns ──────────────────────────────────────────
        function populateCourses() {
            const courseTypeId = parseInt(document.getElementById('courseTypeSelect').value, 10);
            const courseSelect = document.getElementById('courseSelect');
            courseSelect.innerHTML = '<option value="">Select a course</option>';
            courseSelect.disabled = !courseTypeId;
            document.getElementById('courseTypeIdInput').value = courseTypeId || '';

            const courseType = courseTypesData.find(ct => ct.id === courseTypeId);
            if (!courseType) { populateStreams(); return; }

            courseType.courses.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name;
                courseSelect.appendChild(opt);
            });
            populateStreams();
        }

        function currentCourse() {
            const courseTypeId = parseInt(document.getElementById('courseTypeSelect').value, 10);
            const courseId = parseInt(document.getElementById('courseSelect').value, 10);
            const courseType = courseTypesData.find(ct => ct.id === courseTypeId);
            return courseType ? courseType.courses.find(c => c.id === courseId) : null;
        }

        function populateStreams() {
            const streamSelect = document.getElementById('streamSelect');
            streamSelect.innerHTML = '<option value="">Select a stream</option>';
            const course = currentCourse();
            streamSelect.disabled = !course;
            if (course) {
                course.streams.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.name;
                    streamSelect.appendChild(opt);
                });
            }
            populateSemesters();
        }

        function populateSemesters() {
            const semesterSelect = document.getElementById('semesterSelect');
            semesterSelect.innerHTML = '<option value="">Skip</option>';
            const course = currentCourse();
            semesterSelect.disabled = !course;
            if (course) {
                Object.entries(course.semesterOptions).forEach(([value, label]) => {
                    const opt = document.createElement('option');
                    opt.value = value;
                    opt.textContent = label;
                    semesterSelect.appendChild(opt);
                });
            }
        }

        document.getElementById('courseTypeSelect').addEventListener('change', populateCourses);
        document.getElementById('courseSelect').addEventListener('change', populateStreams);

        // ── Captcha refresh ──────────────────────────────────────────────
        document.getElementById('refreshCaptchaBtn').addEventListener('click', async () => {
            try {
                const response = await fetch(baseUrl + '/captcha', { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                document.getElementById('captchaLabel').textContent = data.question;
                document.getElementById('captchaAnswerInput').value = '';
            } catch (err) { /* silent — user can still submit and get a fresh one on mismatch */ }
        });

        // ── Step 1: verify identity, send OTP ────────────────────────────
        document.getElementById('verifyForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            clearError();

            const btn = document.getElementById('verifyBtn');
            btn.disabled = true;

            const form = e.target;
            const payload = {
                course_type_id:   document.getElementById('courseTypeIdInput').value,
                course_id:        form.course_id.value,
                course_stream_id: form.course_stream_id.value,
                semester:         form.semester.value || null,
                identifier_type:  form.identifier_type.value,
                identifier_value: form.identifier_value.value,
                dob:              form.dob.value,
                mobile:           form.mobile.value,
                captcha_answer:   form.captcha_answer.value,
                website:          form.website.value,
            };

            try {
                const data = await postJson('/verify', payload);
                otpToken = data.token;
                document.getElementById('otpIntro').textContent = data.message;
                document.getElementById('otpInput').value = '';
                showStep('step2');
            } catch (err) {
                showError(err.message);
            } finally {
                btn.disabled = false;
            }
        });

        // ── Step 2: verify OTP ────────────────────────────────────────────
        document.getElementById('verifyOtpBtn').addEventListener('click', async () => {
            clearError();
            const otp = document.getElementById('otpInput').value.trim();
            if (!otp) { showError('Please enter the OTP.'); return; }
            try {
                const data = await postJson('/verify-otp', { token: otpToken, otp });
                document.getElementById('dueAmount').textContent = '₹ ' + data.due;

                const icon = document.getElementById('resultIcon');
                const hasDue = parseFloat(String(data.due).replace(/,/g, '')) > 0;
                icon.classList.toggle('due', hasDue);
                icon.classList.toggle('clear', !hasDue);
                icon.querySelector('i').className = hasDue ? 'bi bi-cash-coin' : 'bi bi-check-circle';

                showStep('step3');
            } catch (err) {
                showError(err.message);
            }
        });

        document.getElementById('resendOtpBtn').addEventListener('click', async () => {
            clearError();
            try {
                const data = await postJson('/resend-otp', { token: otpToken });
                document.getElementById('otpIntro').textContent = data.message;
            } catch (err) {
                showError(err.message);
            }
        });
    </script>
</body>
</html>
