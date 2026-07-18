<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admission Enquiry — {{ $institute->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('public.admission.partials._brand-style')
    <style>
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f0f4f8; min-height: 100vh; }
        .enquiry-card { max-width: 560px; margin: 40px auto; }
        .institute-logo { max-height: 64px; max-width: 200px; object-fit: contain; }
        .honeypot-field { position: absolute; left: -9999px; top: -9999px; }
    </style>
</head>
<body>
    <div class="container enquiry-card">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    @if($institute->image)
                        <img src="{{ asset('storage/' . $institute->image) }}" alt="{{ $institute->name }}" class="institute-logo mb-2 d-block mx-auto">
                    @endif
                    <h4 class="fw-bold mb-0">{{ $institute->name }}</h4>
                    <div class="text-muted small">Admission Enquiry Form</div>
                </div>

                <div id="formAlert" class="alert alert-danger d-none" role="alert"></div>
                <div id="formSuccess" class="alert alert-success d-none" role="alert"></div>

                <form id="enquiryForm" novalidate>
                    @csrf
                    <input type="text" name="website" class="honeypot-field" tabindex="-1" autocomplete="off">

                    @foreach(['utm_source', 'utm_medium', 'utm_campaign'] as $utmKey)
                        <input type="hidden" name="{{ $utmKey }}" value="{{ $utm[$utmKey] ?? '' }}">
                    @endforeach

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Full Name *</label>
                        <input type="text" name="name" class="form-control" required maxlength="150">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Mobile Number *</label>
                        <input type="text" name="mobile" class="form-control" required maxlength="20">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email Address *</label>
                        <div class="input-group">
                            <input type="email" name="email" id="emailInput" class="form-control" required maxlength="255">
                            <button type="button" id="sendOtpBtn" class="btn btn-outline-primary">Send OTP</button>
                        </div>
                    </div>

                    <div class="mb-3 d-none" id="otpGroup">
                        <label class="form-label small fw-semibold">Enter OTP *</label>
                        <div class="input-group">
                            <input type="text" name="otp" id="otpInput" class="form-control" maxlength="6">
                            <button type="button" id="verifyOtpBtn" class="btn btn-outline-success">Verify</button>
                        </div>
                        <div class="form-text" id="otpStatus"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Course Interested In</label>
                        <select name="course_id" class="form-select">
                            <option value="">Select a course</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}">{{ $course->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">City</label>
                        <input type="text" name="city" class="form-control" maxlength="100">
                    </div>

                    <button type="submit" id="submitBtn" class="btn btn-primary w-100" disabled>Submit Enquiry</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const baseUrl = @json(url('/apply/' . $institute->short_name));
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const alertBox = document.getElementById('formAlert');
        const successBox = document.getElementById('formSuccess');
        let emailVerified = false;

        function showError(message) {
            alertBox.textContent = message;
            alertBox.classList.remove('d-none');
            successBox.classList.add('d-none');
        }

        async function postJson(path, body) {
            const response = await fetch(baseUrl + path, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify(body),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const firstError = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Something went wrong.');
                throw new Error(firstError);
            }
            return data;
        }

        document.getElementById('sendOtpBtn').addEventListener('click', async () => {
            alertBox.classList.add('d-none');
            const email = document.getElementById('emailInput').value.trim();
            if (!email) { showError('Please enter your email address first.'); return; }
            try {
                await postJson('/send-otp', { email });
                document.getElementById('otpGroup').classList.remove('d-none');
                document.getElementById('otpStatus').textContent = 'OTP sent. Please check your inbox.';
            } catch (err) {
                showError(err.message);
            }
        });

        document.getElementById('verifyOtpBtn').addEventListener('click', async () => {
            alertBox.classList.add('d-none');
            const email = document.getElementById('emailInput').value.trim();
            const otp = document.getElementById('otpInput').value.trim();
            if (!otp) { showError('Please enter the OTP.'); return; }
            try {
                await postJson('/verify-otp', { email, otp });
                emailVerified = true;
                document.getElementById('otpStatus').textContent = 'Email verified.';
                document.getElementById('submitBtn').disabled = false;
            } catch (err) {
                showError(err.message);
            }
        });

        document.getElementById('enquiryForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            alertBox.classList.add('d-none');
            if (!emailVerified) { showError('Please verify your email with OTP first.'); return; }

            const form = e.target;
            const formData = new FormData(form);
            try {
                const response = await fetch(baseUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: formData,
                });
                if (!response.ok) {
                    const data = await response.json().catch(() => ({}));
                    const firstError = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Something went wrong.');
                    throw new Error(firstError);
                }
                document.open();
                document.write(await response.text());
                document.close();
            } catch (err) {
                showError(err.message);
            }
        });
    </script>
</body>
</html>
