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

    $personalFields = [
        'father_name'       => ['Father Name', 'text'],
        'father_mobile'     => ['Father Mobile', 'text'],
        'mother_name'       => ['Mother Name', 'text'],
        'dob'               => ['Date of Birth', 'date'],
        'gender'            => ['Gender', 'select', ['male' => 'Male', 'female' => 'Female', 'other' => 'Other']],
        'guardian_name'     => ['Guardian Name', 'text'],
        'guardian_mobile'   => ['Guardian Mobile', 'text'],
        'guardian_relation' => ['Guardian Relation', 'text'],
        'religion'          => ['Religion', 'text'],
        'category'          => ['Category', 'text'],
        'special_category'  => ['Special Category', 'text'],
        'nationality'       => ['Nationality', 'text'],
        'aadhar_no'         => ['Aadhar Card No.', 'text'],
        'apaar_no'          => ['APAAR No.', 'text'],
        'student_type'      => ['Student Type', 'text'],
        'marital_status'    => ['Marital Status', 'select', ['single' => 'Single', 'married' => 'Married']],
    ];

    $addressFields = [
        'perm_village'  => ['Village/City', 'text'],
        'perm_post'     => ['Post', 'text'],
        'perm_thana'    => ['Thana', 'text'],
        'perm_district' => ['District', 'text'],
        'perm_state'    => ['State', 'text'],
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
                            <select name="course_id" id="courseSelect" class="form-select" required>
                                <option value="">Select a course</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ (int) old('course_id', $enquiry->course_id) === $course->id ? 'selected' : '' }}>
                                        {{ $course->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Stream *</label>
                            <select name="course_stream_id" id="streamSelect" class="form-select" required>
                                <option value="">Select a stream</option>
                            </select>
                        </div>
                    </div>

                    <div class="section-title">Personal Details</div>
                    <div class="row g-3">
                        @if($fieldEnabled('photo'))
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Photo</label>
                                <input type="file" name="photo" class="form-control" accept="image/*">
                            </div>
                        @endif
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Full Name *</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $enquiry->name) }}" required maxlength="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Mobile Number *</label>
                            <input type="text" name="mobile" class="form-control" value="{{ old('mobile', $enquiry->mobile) }}" required maxlength="10">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Email Address *</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $enquiry->email) }}" required maxlength="100">
                        </div>
                        @foreach($personalFields as $key => $meta)
                            @if($fieldEnabled($key))
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">{{ $meta[0] }} {{ $fieldRequired($key) ? '*' : '' }}</label>
                                    @if($meta[1] === 'select')
                                        <select name="{{ $key }}" class="form-select" {{ $fieldRequired($key) ? 'required' : '' }}>
                                            <option value="">Select</option>
                                            @foreach($meta[2] as $value => $label)
                                                <option value="{{ $value }}" {{ old($key) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="{{ $meta[1] }}" name="{{ $key }}" class="form-control" value="{{ old($key) }}" {{ $fieldRequired($key) ? 'required' : '' }}>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>

                    @if(collect(array_keys($addressFields))->contains(fn ($k) => $fieldEnabled($k)))
                        <div class="section-title">Address Details</div>
                        <div class="row g-3">
                            @foreach($addressFields as $key => $meta)
                                @if($fieldEnabled($key))
                                    <div class="col-md-{{ $meta[1] === 'textarea' ? 12 : 4 }}">
                                        <label class="form-label small fw-semibold">{{ $meta[0] }} {{ $fieldRequired($key) ? '*' : '' }}</label>
                                        @if($meta[1] === 'textarea')
                                            <textarea name="{{ $key }}" class="form-control" rows="2" {{ $fieldRequired($key) ? 'required' : '' }}>{{ old($key) }}</textarea>
                                        @else
                                            <input type="text" name="{{ $key }}" class="form-control" value="{{ old($key) }}" {{ $fieldRequired($key) ? 'required' : '' }}>
                                        @endif
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
                                    <input type="text" name="education[{{ $key }}][institute_name]" class="form-control" value="{{ old("education.$key.institute_name") }}" {{ $fieldRequired($key) ? 'required' : '' }}>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Board/University {{ $fieldRequired($key) ? '*' : '' }}</label>
                                    <input type="text" name="education[{{ $key }}][board_university]" class="form-control" value="{{ old("education.$key.board_university") }}" {{ $fieldRequired($key) ? 'required' : '' }}>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Roll Number {{ $fieldRequired($key) ? '*' : '' }}</label>
                                    <input type="text" name="education[{{ $key }}][roll_number]" class="form-control" value="{{ old("education.$key.roll_number") }}" {{ $fieldRequired($key) ? 'required' : '' }}>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Passing Year {{ $fieldRequired($key) ? '*' : '' }}</label>
                                    <input type="number" name="education[{{ $key }}][passing_year]" class="form-control" value="{{ old("education.$key.passing_year") }}" min="1900" max="{{ date('Y') }}" {{ $fieldRequired($key) ? 'required' : '' }}>
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
                                    <input type="number" step="0.01" name="education[{{ $key }}][obtained_marks]" class="form-control" value="{{ old("education.$key.obtained_marks") }}" {{ $fieldRequired($key) ? 'required' : '' }}>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Max Marks {{ $fieldRequired($key) ? '*' : '' }}</label>
                                    <input type="number" step="0.01" name="education[{{ $key }}][max_marks]" class="form-control" value="{{ old("education.$key.max_marks") }}" {{ $fieldRequired($key) ? 'required' : '' }}>
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

    <script>
        const coursesData = @json($courses->map(fn ($c) => ['id' => $c->id, 'streams' => $c->streams->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])]));
        const oldStreamId = @json(old('course_stream_id') ? (int) old('course_stream_id') : null);

        function populateStreams() {
            const courseId = parseInt(document.getElementById('courseSelect').value, 10);
            const streamSelect = document.getElementById('streamSelect');
            streamSelect.innerHTML = '<option value="">Select a stream</option>';
            const course = coursesData.find(c => c.id === courseId);
            if (!course) return;
            course.streams.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.name;
                if (oldStreamId === s.id) opt.selected = true;
                streamSelect.appendChild(opt);
            });
        }

        document.getElementById('courseSelect').addEventListener('change', populateStreams);
        populateStreams();
    </script>
</body>
</html>
