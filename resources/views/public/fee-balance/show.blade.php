<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Know Your Fee Balance — {{ $institute->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('public.admission.partials._brand-style')
    <style>
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f0f4f8; min-height: 100vh; }
        .fee-balance-card { max-width: 560px; margin: 40px auto; }
        .institute-logo { max-height: 64px; max-width: 200px; object-fit: contain; }
        .honeypot-field { position: absolute; left: -9999px; top: -9999px; }
        .due-amount { font-size: 2.25rem; font-weight: 800; }
        .step { display: none; }
        .step.active { display: block; }
    </style>
</head>
<body>
    <div class="container fee-balance-card">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    @if($institute->image)
                        <img src="{{ asset('storage/' . $institute->image) }}" alt="{{ $institute->name }}" class="institute-logo mb-2 d-block mx-auto">
                    @endif
                    <h4 class="fw-bold mb-0">{{ $institute->name }}</h4>
                    <div class="text-muted small">Know Your Fee Balance</div>
                </div>

                <div id="formAlert" class="alert alert-danger d-none" role="alert"></div>

                {{-- Step 1: academic context + identity + captcha --}}
                <div id="step1" class="step active">
                    <form id="verifyForm" novalidate>
                        @csrf
                        <input type="text" name="website" class="honeypot-field" tabindex="-1" autocomplete="off">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Course Type *</label>
                            <select id="courseTypeSelect" class="form-select" required>
                                <option value="">Select course type</option>
                                @foreach($courseTypes as $courseType)
                                    <option value="{{ $courseType->id }}">{{ $courseType->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Course *</label>
                            <select id="courseSelect" name="course_id" class="form-select" required disabled>
                                <option value="">Select course type first</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Stream *</label>
                            <select id="streamSelect" name="course_stream_id" class="form-select" required disabled>
                                <option value="">Select course first</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Semester</label>
                            <select id="semesterSelect" name="semester" class="form-select" disabled>
                                <option value="">Select course first</option>
                            </select>
                        </div>

                        <input type="hidden" id="courseTypeIdInput" name="course_type_id">

                        <hr>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Search By *</label>
                            <div class="input-group">
                                <select name="identifier_type" id="identifierTypeSelect" class="form-select" style="max-width: 40%;" required>
                                    @foreach($identifierOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="identifier_value" id="identifierValueInput" class="form-control" placeholder="Enter value" required maxlength="100">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Date of Birth *</label>
                            <input type="date" name="dob" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Mobile Number *</label>
                            <input type="text" name="mobile" class="form-control" required maxlength="20" placeholder="As registered with the institute">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold" id="captchaLabel">{{ $captchaQuestion }} *</label>
                            <div class="input-group">
                                <input type="number" name="captcha_answer" id="captchaAnswerInput" class="form-control" required>
                                <button type="button" id="refreshCaptchaBtn" class="btn btn-outline-secondary" title="New question">&#8635;</button>
                            </div>
                        </div>

                        <button type="submit" id="verifyBtn" class="btn btn-primary w-100">Submit</button>
                    </form>
                </div>

                {{-- Step 2: OTP --}}
                <div id="step2" class="step">
                    <p class="text-muted small mb-3" id="otpIntro">An OTP has been sent to the mobile number on file.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Enter OTP *</label>
                        <input type="text" id="otpInput" class="form-control" maxlength="6" required>
                    </div>
                    <button type="button" id="verifyOtpBtn" class="btn btn-primary w-100 mb-2">Verify OTP</button>
                    <button type="button" id="resendOtpBtn" class="btn btn-link w-100 small">Resend OTP</button>
                </div>

                {{-- Step 3: result --}}
                <div id="step3" class="step text-center">
                    <div class="text-muted small mb-2">Your Fee Balance</div>
                    <div class="due-amount text-primary" id="dueAmount">-</div>
                    <div class="text-muted small mt-3">Contact the institute office for payment details.</div>
                </div>
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
                    document.getElementById('captchaLabel').textContent = data.question + ' *';
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
            semesterSelect.innerHTML = '<option value="">Not sure / skip</option>';
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
                document.getElementById('captchaLabel').textContent = data.question + ' *';
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
