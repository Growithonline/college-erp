<div class="card border-0 bg-light mb-4">
    <div class="card-body p-3">
        <div class="fw-semibold text-muted mb-2" style="font-size:11px; letter-spacing:.05em; text-transform:uppercase;">Find Student</div>
        <div class="row g-2 mb-2">
            <div class="col-md-3">
                <label class="form-label" style="font-size:12px;">Course Type</label>
                <select class="form-select form-select-sm" id="pickerCourseType">
                    <option value="">All Types</option>
                    @foreach($courseTypes as $ct)
                        <option value="{{ $ct->id }}">{{ $ct->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" style="font-size:12px;">Course</label>
                <select class="form-select form-select-sm" id="pickerCourse">
                    <option value="">All Courses</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" data-type="{{ $course->course_type_id }}">{{ $course->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" style="font-size:12px;">Stream</label>
                <select class="form-select form-select-sm" id="pickerStream">
                    <option value="">All Streams</option>
                    @foreach($streams as $stream)
                        <option value="{{ $stream->id }}" data-course="{{ $stream->course_id }}">{{ $stream->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" style="font-size:12px;">Semester</label>
                <select class="form-select form-select-sm" id="pickerSemester">
                    <option value="">All Sem</option>
                    @for($s = 1; $s <= 10; $s++)
                        <option value="{{ $s }}">Sem {{ $s }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <label class="form-label" style="font-size:12px;">Search Student *</label>
        <div class="position-relative">
            <input type="text" id="studentSearchInput" class="form-control"
                   placeholder="Name, Father, Mother, Mobile, Roll No, Student ID..." autocomplete="off">
            <div id="studentSearchResults" class="list-group position-absolute w-100 shadow"
                 style="z-index:1050; display:none; max-height:280px; overflow-y:auto; top:100%; margin-top:4px;"></div>
        </div>
        <input type="hidden" name="student_id" id="studentIdInput" value="{{ old('student_id') }}" required>

        <div id="selectedStudentCard" class="mt-2 p-2 rounded border border-primary-subtle bg-white {{ old('student_id') ? '' : 'd-none' }}">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="fw-semibold" id="selectedStudentName" style="font-size:13px;"></div>
                    <div class="text-muted" id="selectedStudentMeta" style="font-size:11px;"></div>
                    <div class="text-secondary" id="selectedStudentParents" style="font-size:11px;"></div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.clearSelectedStudent()">Change</button>
            </div>
        </div>
        @error('student_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Academic Session *</label>
        <select class="form-select" name="academic_session_id" required>
            <option value="">Select Session</option>
            @foreach($sessions as $session)
                <option value="{{ $session->id }}" @selected(old('academic_session_id') == $session->id)>{{ $session->name ?? ('Session ' . $session->id) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Route *</label>
        <select class="form-select" name="transport_route_id" id="routeSelect" required>
            <option value="">Select Route</option>
            @foreach($routes as $route)
                @php
                    $billingLabel = match($route->billing_frequency ?? 'one_time') {
                        'semester' => 'Per Semester',
                        'yearly'   => 'Yearly',
                        default    => 'One Time',
                    };
                @endphp
                <option value="{{ $route->id }}"
                    @selected(old('transport_route_id') == $route->id)
                    data-fee="{{ $route->fee_amount }}">
                    {{ $route->name }} — {{ $billingLabel }} (₹{{ number_format((float) $route->fee_amount, 2) }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Stop <small class="text-muted">(fee auto-fills if set)</small></label>
        <select class="form-select" name="transport_route_stop_id" id="stopSelect">
            <option value="">No Stop / Select Stop</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label d-flex align-items-center gap-2">Vehicle
            <span id="autoFillTag" class="badge bg-success d-none" style="font-size:10px;font-weight:500;"></span>
        </label>
        <select class="form-select" name="transport_vehicle_id">
            <option value="">Select Vehicle</option>
            @foreach($vehicles as $vehicle)
                <option value="{{ $vehicle->id }}" @selected(old('transport_vehicle_id') == $vehicle->id)>{{ $vehicle->vehicle_no }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Driver</label>
        <select class="form-select" name="transport_driver_id">
            <option value="">Select Driver</option>
            @foreach($drivers as $driver)
                <option value="{{ $driver->id }}" @selected(old('transport_driver_id') == $driver->id)>{{ $driver->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Fee Amount <small class="text-muted">(auto from stop/route)</small></label>
        <input type="number" step="0.01" min="0" class="form-control" name="fee_amount" id="feeAmountInput" value="{{ old('fee_amount') }}">
    </div>
    <div class="col-md-3"><label class="form-label">Start Date *</label><input type="date" class="form-control" name="start_date" value="{{ old('start_date', now()->toDateString()) }}" required></div>
    <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="charge_now" value="1" {{ old('charge_now', true) ? 'checked' : '' }}><label class="form-check-label">Charge wallet now</label></div></div>
    <div class="col-12"><label class="form-label">Remarks</label><textarea class="form-control" name="remarks" rows="3">{{ old('remarks') }}</textarea></div>
</div>
<div class="mt-4 d-flex justify-content-end">
    <button class="btn btn-primary">Save Allocation</button>
</div>

<script>
(() => {
    const routeSelect   = document.getElementById('routeSelect');
    const stopSelect    = document.getElementById('stopSelect');
    const feeInput      = document.getElementById('feeAmountInput');

    if (!routeSelect || !stopSelect || !feeInput) return;

    const oldRouteId = "{{ old('transport_route_id') }}";
    const oldStopId  = "{{ old('transport_route_stop_id') }}";

    function loadStops(routeId, preselectStopId) {
        stopSelect.innerHTML = '<option value="">No Stop / Select Stop</option>';
        if (!routeId) return;

        fetch(`/transport/routes/${routeId}/stops`)
            .then(r => r.json())
            .then(data => {
                (data.stops ?? []).forEach(stop => {
                    const opt = document.createElement('option');
                    opt.value = stop.id;
                    opt.dataset.fee = stop.fee_amount;
                    const feeLabel = stop.fee_amount > 0
                        ? ` — ₹${parseFloat(stop.fee_amount).toFixed(2)}`
                        : '';
                    opt.textContent = `${stop.stop_name}${feeLabel}`;
                    if (String(stop.id) === String(preselectStopId)) opt.selected = true;
                    stopSelect.appendChild(opt);
                });
                updateFee();
            });
    }

    function updateFee() {
        const selectedStop = stopSelect.options[stopSelect.selectedIndex];
        const stopFee = parseFloat(selectedStop?.dataset?.fee ?? 0);

        if (stopFee > 0) {
            feeInput.value = stopFee.toFixed(2);
            return;
        }

        // Fall back to route fee
        const selectedRoute = routeSelect.options[routeSelect.selectedIndex];
        const routeFee = parseFloat(selectedRoute?.dataset?.fee ?? 0);
        if (routeFee > 0) feeInput.value = routeFee.toFixed(2);
    }

    // Auto-fill vehicle + driver from route assignment
    const vehicleSelect = document.querySelector('[name="transport_vehicle_id"]');
    const driverSelect  = document.querySelector('[name="transport_driver_id"]');
    const sessionSelect = document.querySelector('[name="academic_session_id"]');
    const autoTag       = document.getElementById('autoFillTag');

    function fetchRouteAssignment(routeId) {
        if (!routeId || !vehicleSelect || !driverSelect) return;
        const sessionId = sessionSelect?.value ?? '';
        fetch(`/transport/route-assignments/for-route?route_id=${routeId}&session_id=${sessionId}`)
            .then(r => r.json())
            .then(data => {
                if (data.vehicle_id) {
                    vehicleSelect.value = data.vehicle_id;
                    if (autoTag) { autoTag.textContent = `Auto: ${data.vehicle_no} / ${data.driver_name ?? '—'}`; autoTag.classList.remove('d-none'); }
                }
                if (data.driver_id) driverSelect.value = data.driver_id;
            });
    }

    routeSelect.addEventListener('change', () => {
        loadStops(routeSelect.value, null);
        updateFee();
        fetchRouteAssignment(routeSelect.value);
    });

    stopSelect.addEventListener('change', updateFee);

    // On page load — restore old selections (validation failure redirect)
    if (oldRouteId) {
        loadStops(oldRouteId, oldStopId);
    }
})();

(() => {
    const searchUrl = '{{ route('transport.allocations.search-students') }}';

    const typeSel   = document.getElementById('pickerCourseType');
    const courseSel = document.getElementById('pickerCourse');
    const streamSel = document.getElementById('pickerStream');
    const semSel    = document.getElementById('pickerSemester');
    const input     = document.getElementById('studentSearchInput');
    const results    = document.getElementById('studentSearchResults');
    const idInput    = document.getElementById('studentIdInput');
    const card       = document.getElementById('selectedStudentCard');
    const nameEl     = document.getElementById('selectedStudentName');
    const metaEl     = document.getElementById('selectedStudentMeta');
    const parentsEl  = document.getElementById('selectedStudentParents');

    if (!input) return;

    // Course Type -> hide/disable non-matching Course options (same technique as the
    // Student Directory filter bar), then cascade the same narrowing down to Stream.
    function filterOptions(select, dataAttr, value) {
        let stillVisible = false;
        Array.from(select.options).forEach(opt => {
            if (opt.value === '') return;
            const match = !value || opt.dataset[dataAttr] === String(value);
            opt.hidden = !match;
            opt.disabled = !match;
            if (match && opt.selected) stillVisible = true;
        });
        if (!stillVisible) select.value = '';
    }

    typeSel?.addEventListener('change', () => {
        filterOptions(courseSel, 'type', typeSel.value);
        filterOptions(streamSel, 'course', '');
        runSearch();
    });
    courseSel?.addEventListener('change', () => {
        filterOptions(streamSel, 'course', courseSel.value);
        runSearch();
    });
    streamSel?.addEventListener('change', runSearch);
    semSel?.addEventListener('change', runSearch);

    function renderResults(students) {
        if (!students.length) {
            results.innerHTML = '<div class="list-group-item text-muted text-center py-3">'
                + '<i class="bi bi-search me-2"></i>No student found</div>';
            results.style.display = 'block';
            return;
        }

        results.innerHTML = students.map(s => {
            const parents = [
                s.father_name ? `Father: <strong>${s.father_name}</strong>` : '',
                s.mother_name ? `Mother: <strong>${s.mother_name}</strong>` : '',
            ].filter(Boolean).join(' &nbsp;|&nbsp; ');

            return `
            <button type="button" class="list-group-item list-group-item-action py-2 px-3"
                    data-student='${JSON.stringify(s).replace(/'/g, "&#39;")}'>
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div style="min-width:0;">
                        <div class="fw-semibold" style="font-size:13px;">${s.name}</div>
                        <div class="text-muted" style="font-size:11px;">
                            ${s.student_uid ?? ''}${s.roll_no ? ' &nbsp;•&nbsp; Roll ' + s.roll_no : ''}${s.course ? ' &nbsp;•&nbsp; ' + s.course : ''}${s.stream ? ' &nbsp;•&nbsp; ' + s.stream : ''}
                        </div>
                        ${parents ? `<div class="text-secondary" style="font-size:11px;">${parents}</div>` : ''}
                    </div>
                </div>
            </button>`;
        }).join('');
        results.style.display = 'block';

        results.querySelectorAll('[data-student]').forEach(btn => {
            btn.addEventListener('click', () => selectStudent(JSON.parse(btn.dataset.student)));
        });
    }

    function selectStudent(s) {
        idInput.value = s.id;
        nameEl.textContent = s.name;
        metaEl.textContent = [s.student_uid, s.roll_no ? 'Roll ' + s.roll_no : '', s.course, s.stream].filter(Boolean).join(' • ');
        const parents = [
            s.father_name ? `Father: ${s.father_name}` : '',
            s.mother_name ? `Mother: ${s.mother_name}` : '',
        ].filter(Boolean).join(' | ');
        parentsEl.textContent = parents;
        card.classList.remove('d-none');
        input.value = '';
        results.style.display = 'none';
    }

    window.clearSelectedStudent = function () {
        idInput.value = '';
        card.classList.add('d-none');
        input.value = '';
        input.focus();
    };

    let timer;
    function runSearch() {
        const q = input.value.trim();
        clearTimeout(timer);
        timer = setTimeout(() => {
            const params = new URLSearchParams({
                q,
                course_type_id: typeSel?.value ?? '',
                course_id: courseSel?.value ?? '',
                course_stream_id: streamSel?.value ?? '',
                current_semester: semSel?.value ?? '',
            });
            fetch(`${searchUrl}?${params.toString()}`)
                .then(r => r.json())
                .then(renderResults);
        }, 300);
    }

    input.addEventListener('input', () => {
        if (input.value.trim().length < 1 && !typeSel?.value && !courseSel?.value && !streamSel?.value && !semSel?.value) {
            results.style.display = 'none';
            return;
        }
        runSearch();
    });
    input.addEventListener('focus', () => { if (results.innerHTML) results.style.display = 'block'; });

    document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !results.contains(e.target)) {
            results.style.display = 'none';
        }
    });

    // On page load — restore old selection (validation failure redirect)
    if (idInput.value) {
        fetch(`${searchUrl}?id=${encodeURIComponent(idInput.value)}`)
            .then(r => r.json())
            .then(students => { if (students.length) selectStudent(students[0]); });
    }
})();
</script>
