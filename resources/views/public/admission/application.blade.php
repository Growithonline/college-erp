<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admission Application — {{ $institute->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('public.admission.partials._brand-style')
    <style>
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f0f4f8; min-height: 100vh; }
        .application-card { max-width: 760px; margin: 40px auto; }
        .institute-logo { max-height: 64px; max-width: 200px; object-fit: contain; }
        .section-title { font-size: 15px; font-weight: 700; margin: 24px 0 12px; padding-bottom: 6px; border-bottom: 2px solid #e2e8f0; }
    </style>
</head>
<body>
@php
    $fieldEnabled  = fn ($key) => (bool) (($formConfig[$key]['enabled'] ?? false) && ($formConfig[$key]['section_enabled'] ?? true));
    $fieldRequired = fn ($key) => (bool) ($fieldEnabled($key) && ($formConfig[$key]['required'] ?? false));
    $invalidClass  = fn ($key) => $errors->has($key) ? 'is-invalid' : '';

    $fieldDefaults = ['nationality' => 'Indian'];

    $personalFields = [
        'father_name'       => ['Father Name', 'text'],
        'father_mobile'     => ['Father Mobile', 'text'],
        'mother_name'       => ['Mother Name', 'text'],
        'dob'               => ['Date of Birth', 'date'],
        'gender'            => ['Gender', 'select', ['male' => 'Male', 'female' => 'Female', 'other' => 'Other']],
        'guardian_name'     => ['Guardian Name', 'text'],
        'guardian_mobile'   => ['Guardian Mobile', 'text'],
        'guardian_relation' => ['Guardian Relation', 'select', ['father' => 'Father', 'mother' => 'Mother', 'uncle' => 'Uncle', 'aunt' => 'Aunt', 'brother' => 'Brother', 'sister' => 'Sister', 'grandfather' => 'Grandfather', 'grandmother' => 'Grandmother', 'others' => 'Others']],
        'religion'          => ['Religion', 'select', ['hindu' => 'Hindu', 'muslim' => 'Muslim', 'sikh' => 'Sikh', 'christian' => 'Christian', 'jain' => 'Jain', 'parsi' => 'Parsi', 'buddhist' => 'Buddhist', 'others' => 'Others']],
        'category'          => ['Category', 'select', ['gen' => 'GEN', 'obc' => 'OBC', 'sc' => 'SC', 'st' => 'ST', 'ews' => 'EWS', 'others' => 'OTHERS']],
        'special_category'  => ['Special Category', 'select', ['none' => 'None / NA', 'pwd' => 'PWD', 'ex_serviceman' => 'Ex Serviceman', 'sports' => 'Sports', 'ncc' => 'NCC', 'others' => 'Others']],
        'nationality'       => ['Nationality', 'text'],
        'aadhar_no'         => ['Aadhar Card No.', 'text'],
        'apaar_no'          => ['APAAR No.', 'text'],
        'marital_status'    => ['Marital Status', 'select', ['single' => 'Single', 'married' => 'Married', 'divorced' => 'Divorced', 'widowed' => 'Widowed']],
    ];

    $addressFields = [
        'perm_village'  => ['Village/City', 'text'],
        'perm_post'     => ['Post', 'text'],
        'perm_thana'    => ['Thana', 'text'],
        'perm_pincode'  => ['Pin Code', 'text'],
        'comm_address'  => ['Communication Address', 'textarea'],
    ];

    $educationFields = [
        'edu_10th'       => '10th Details',
        'edu_12th'       => '12th Details',
        'edu_graduation' => 'Graduation Details',
        'edu_other'      => 'Other Exam Details',
    ];
@endphp

    <div class="container application-card">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    @if($institute->image)
                        <img src="{{ asset('storage/' . $institute->image) }}" alt="{{ $institute->name }}" class="institute-logo mb-2 d-block mx-auto">
                    @endif
                    <h4 class="fw-bold mb-0">{{ $institute->name }}</h4>
                    <div class="text-muted small">Admission Application Form</div>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger">
                        <div class="fw-semibold mb-1">Please fix the following:</div>
                        <ul class="mb-0 small">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="applicationForm" method="POST" action="{{ url()->full() }}" enctype="multipart/form-data">
                    @csrf

                    <div class="section-title">Course Details</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Course *</label>
                            <select name="course_id" id="courseSelect" class="form-select {{ $invalidClass('course_id') }}" required>
                                <option value="">Select a course</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ (int) old('course_id', $enquiry->course_id) === $course->id ? 'selected' : '' }}>
                                        {{ $course->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('course_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Stream *</label>
                            <select name="course_stream_id" id="streamSelect" class="form-select {{ $invalidClass('course_stream_id') }}" required>
                                <option value="">Select a stream</option>
                            </select>
                            @error('course_stream_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <div id="seatHint" class="small mt-1"></div>
                        </div>
                    </div>

                    <div class="section-title">Personal Details</div>
                    <div class="row g-3">
                        @if($fieldEnabled('photo'))
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Photo</label>
                                <input type="file" name="photo" class="form-control {{ $invalidClass('photo') }}" accept="image/*">
                                @error('photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        @endif
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Full Name *</label>
                            <input type="text" name="name" class="form-control {{ $invalidClass('name') }}" value="{{ old('name', $enquiry->name) }}" maxlength="100" required>
                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Mobile Number *</label>
                            <input type="text" name="mobile" class="form-control {{ $invalidClass('mobile') }}" value="{{ old('mobile', $enquiry->mobile) }}" maxlength="10" required>
                            @error('mobile')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Email Address *</label>
                            <input type="email" name="email" class="form-control {{ $invalidClass('email') }}" value="{{ old('email', $enquiry->email) }}" maxlength="100" required>
                            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        @foreach($personalFields as $key => $meta)
                            @if($fieldEnabled($key))
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">{{ $meta[0] }} {{ $fieldRequired($key) ? '*' : '' }}</label>
                                    @if($meta[1] === 'select')
                                        <select name="{{ $key }}" class="form-select {{ $invalidClass($key) }}" {{ $fieldRequired($key) ? 'required' : '' }}>
                                            <option value="">Select</option>
                                            @foreach($meta[2] as $value => $label)
                                                <option value="{{ $value }}" {{ old($key) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="{{ $meta[1] }}" name="{{ $key }}" class="form-control {{ $invalidClass($key) }}" value="{{ old($key, $fieldDefaults[$key] ?? '') }}" {{ $fieldRequired($key) ? 'required' : '' }}>
                                    @endif
                                    @error($key)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            @endif
                        @endforeach
                        @if($fieldEnabled('student_type') && $studentTypes->isNotEmpty())
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Student Type {{ $fieldRequired('student_type') ? '*' : '' }}</label>
                                <select name="student_type" class="form-select {{ $invalidClass('student_type') }}" {{ $fieldRequired('student_type') ? 'required' : '' }}>
                                    @foreach($studentTypes as $st)
                                        <option value="{{ $st->slug }}" {{ old('student_type', $studentTypes->first()->slug) === $st->slug ? 'selected' : '' }}>{{ $st->name }}</option>
                                    @endforeach
                                </select>
                                @error('student_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        @endif
                    </div>

                    @if($fieldEnabled('perm_state') || $fieldEnabled('perm_district') || collect(array_keys($addressFields))->contains(fn ($k) => $fieldEnabled($k)))
                        <div class="section-title">Address Details</div>
                        <div class="row g-3">
                            @if($fieldEnabled('perm_state'))
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">State {{ $fieldRequired('perm_state') ? '*' : '' }}</label>
                                    <select name="perm_state" id="permStateSelect" class="form-select {{ $invalidClass('perm_state') }}" data-saved="{{ old('perm_state') }}" {{ $fieldRequired('perm_state') ? 'required' : '' }}>
                                        <option value="">— Select State —</option>
                                    </select>
                                    @error('perm_state')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            @endif
                            @if($fieldEnabled('perm_district'))
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">District {{ $fieldRequired('perm_district') ? '*' : '' }}</label>
                                    <select name="perm_district" id="permDistrictSelect" class="form-select {{ $invalidClass('perm_district') }}" data-saved="{{ old('perm_district') }}" {{ $fieldRequired('perm_district') ? 'required' : '' }}>
                                        <option value="">— Select District —</option>
                                    </select>
                                    @error('perm_district')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            @endif
                            @foreach($addressFields as $key => $meta)
                                @if($fieldEnabled($key))
                                    <div class="col-md-{{ $meta[1] === 'textarea' ? 12 : 4 }}">
                                        <label class="form-label small fw-semibold">{{ $meta[0] }} {{ $fieldRequired($key) ? '*' : '' }}</label>
                                        @if($meta[1] === 'textarea')
                                            <textarea name="{{ $key }}" class="form-control {{ $invalidClass($key) }}" rows="2" {{ $fieldRequired($key) ? 'required' : '' }}>{{ old($key) }}</textarea>
                                        @else
                                            <input type="text" name="{{ $key }}" class="form-control {{ $invalidClass($key) }}" value="{{ old($key) }}" {{ $fieldRequired($key) ? 'required' : '' }}>
                                        @endif
                                        @error($key)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @foreach($educationFields as $key => $label)
                        @if($fieldEnabled($key))
                            <div class="section-title">{{ $label }}</div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">School/College Name {{ $fieldRequired($key) ? '*' : '' }}</label>
                                    <input type="text" name="education[{{ $key }}][institute_name]" class="form-control {{ $invalidClass("education.$key.institute_name") }}" value="{{ old("education.$key.institute_name") }}" {{ $fieldRequired($key) ? 'required' : '' }}>
                                    @error("education.$key.institute_name")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Board/University {{ $fieldRequired($key) ? '*' : '' }}</label>
                                    <input type="text" name="education[{{ $key }}][board_university]" class="form-control {{ $invalidClass("education.$key.board_university") }}" value="{{ old("education.$key.board_university") }}" {{ $fieldRequired($key) ? 'required' : '' }}>
                                    @error("education.$key.board_university")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Roll Number {{ $fieldRequired($key) ? '*' : '' }}</label>
                                    <input type="text" name="education[{{ $key }}][roll_number]" class="form-control {{ $invalidClass("education.$key.roll_number") }}" value="{{ old("education.$key.roll_number") }}" {{ $fieldRequired($key) ? 'required' : '' }}>
                                    @error("education.$key.roll_number")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Passing Year {{ $fieldRequired($key) ? '*' : '' }}</label>
                                    <input type="number" name="education[{{ $key }}][passing_year]" class="form-control {{ $invalidClass("education.$key.passing_year") }}" value="{{ old("education.$key.passing_year") }}" min="1900" max="{{ date('Y') }}" {{ $fieldRequired($key) ? 'required' : '' }}>
                                    @error("education.$key.passing_year")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">District</label>
                                    <input type="text" name="education[{{ $key }}][district]" class="form-control" value="{{ old("education.$key.district") }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Division</label>
                                    <input type="text" name="education[{{ $key }}][division]" class="form-control" value="{{ old("education.$key.division") }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Obtained Marks {{ $fieldRequired($key) ? '*' : '' }}</label>
                                    <input type="number" step="0.01" name="education[{{ $key }}][obtained_marks]" class="form-control {{ $invalidClass("education.$key.obtained_marks") }}" value="{{ old("education.$key.obtained_marks") }}" {{ $fieldRequired($key) ? 'required' : '' }}>
                                    @error("education.$key.obtained_marks")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Max Marks {{ $fieldRequired($key) ? '*' : '' }}</label>
                                    <input type="number" step="0.01" name="education[{{ $key }}][max_marks]" class="form-control {{ $invalidClass("education.$key.max_marks") }}" value="{{ old("education.$key.max_marks") }}" {{ $fieldRequired($key) ? 'required' : '' }}>
                                    @error("education.$key.max_marks")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Percentage</label>
                                    <input type="number" step="0.01" name="education[{{ $key }}][percentage]" class="form-control" value="{{ old("education.$key.percentage") }}">
                                </div>
                            </div>
                        @endif
                    @endforeach

                    <button type="submit" class="btn btn-primary w-100 mt-4">Submit Application</button>
                </form>
            </div>
        </div>
    </div>

    @include('partials._india-geo')

    <script>
        const coursesData = @json($courses->map(fn ($c) => ['id' => $c->id, 'streams' => $c->streams->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])]));
        const oldStreamId = @json(old('course_stream_id') ? (int) old('course_stream_id') : null);
        const seatAvailability = @json($seatAvailability);

        function populateStreams() {
            const courseId = parseInt(document.getElementById('courseSelect').value, 10);
            const streamSelect = document.getElementById('streamSelect');
            streamSelect.innerHTML = '<option value="">Select a stream</option>';
            const course = coursesData.find(c => c.id === courseId);
            if (!course) return;
            course.streams.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                const seat = seatAvailability[s.id];
                opt.textContent = seat ? (seat.available ? `${s.name} (${seat.remaining} seats left)` : `${s.name} (Full — Waitlist)`) : s.name;
                if (oldStreamId === s.id) opt.selected = true;
                streamSelect.appendChild(opt);
            });
            updateSeatHint();
        }

        function updateSeatHint() {
            const streamId = parseInt(document.getElementById('streamSelect').value, 10);
            const hint = document.getElementById('seatHint');
            const seat = seatAvailability[streamId];
            if (!seat) {
                hint.textContent = '';
            } else if (seat.available) {
                hint.className = 'small mt-1 text-success';
                hint.textContent = `${seat.remaining} seat(s) available.`;
            } else {
                hint.className = 'small mt-1 text-warning';
                hint.textContent = 'This stream is full. Your application will be placed on the waitlist.';
            }
        }

        document.getElementById('courseSelect').addEventListener('change', populateStreams);
        document.getElementById('streamSelect').addEventListener('change', updateSeatHint);
        populateStreams();

        // ── Auto-uppercase all free-text inputs (matches the staff admission form) ──
        (function () {
            const skipFields = ['email', 'aadhar_no', 'apaar_no', 'mobile', 'father_mobile', 'guardian_mobile'];
            const form = document.getElementById('applicationForm');
            form.querySelectorAll('input[type="text"]').forEach(function (el) {
                const n = el.name || '';
                if (skipFields.some(s => n === s || n.endsWith('[' + s + ']'))) return;
                el.style.textTransform = 'uppercase';
                if (el.value) el.value = el.value.toUpperCase();
                el.addEventListener('input', function () {
                    const pos = this.selectionStart;
                    this.value = this.value.toUpperCase();
                    try { this.setSelectionRange(pos, pos); } catch (e) {}
                });
            });
            const commAddr = form.querySelector('textarea[name="comm_address"]');
            if (commAddr) {
                commAddr.style.textTransform = 'uppercase';
                if (commAddr.value) commAddr.value = commAddr.value.toUpperCase();
                commAddr.addEventListener('input', function () {
                    const pos = this.selectionStart;
                    this.value = this.value.toUpperCase();
                    try { this.setSelectionRange(pos, pos); } catch (e) {}
                });
            }
        })();
    </script>
</body>
</html>
