<script>
window.admissionLiveValidation = window.admissionLiveValidation || (() => {
    const TODAY = new Date().toISOString().slice(0, 10);
    const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const NAME_REGEX = /^[A-Za-z][A-Za-z\s.'-]*$/;

    function digitsOnly(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function trimValue(element) {
        return String(element?.value || '').trim();
    }

    function ensureFeedback(element) {
        if (!element || !element.parentElement) {
            return null;
        }

        let feedback = element.parentElement.querySelector('.live-validation-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback live-validation-feedback';
            element.insertAdjacentElement('afterend', feedback);
        }

        return feedback;
    }

    function setInvalid(element, message) {
        if (!element) {
            return false;
        }

        const feedback = ensureFeedback(element);
        element.classList.add('is-invalid');
        element.setCustomValidity(message);
        if (feedback) {
            feedback.textContent = message;
            feedback.style.display = 'block';
        }
        return false;
    }

    function clearInvalid(element) {
        if (!element) {
            return true;
        }

        const feedback = ensureFeedback(element);
        element.classList.remove('is-invalid');
        element.setCustomValidity('');
        if (feedback) {
            feedback.textContent = '';
            feedback.style.display = '';
        }
        return true;
    }

    function normalizeDigitField(element, maxLength) {
        if (!element) {
            return;
        }

        const normalized = digitsOnly(element.value).slice(0, maxLength);
        if (element.value !== normalized) {
            element.value = normalized;
        }
    }

    function validateExactDigits(element, length, message) {
        if (!element) {
            return true;
        }

        const value = trimValue(element);
        if (!value) {
            return clearInvalid(element);
        }

        if (!new RegExp(`^\\d{${length}}$`).test(value)) {
            return setInvalid(element, message);
        }

        return clearInvalid(element);
    }

    function validateEmail(element) {
        if (!element) {
            return true;
        }

        const value = trimValue(element);
        if (!value) {
            return clearInvalid(element);
        }

        if (!EMAIL_REGEX.test(value)) {
            return setInvalid(element, 'Enter a valid email address.');
        }

        return clearInvalid(element);
    }

    function validateNameField(element, label) {
        if (!element) {
            return true;
        }

        const value = trimValue(element);
        if (!value) {
            return clearInvalid(element);
        }

        if (!NAME_REGEX.test(value)) {
            return setInvalid(element, `${label} can contain only letters, spaces, dot, apostrophe, and hyphen.`);
        }

        return clearInvalid(element);
    }

    function validateDateNotFuture(element, message) {
        if (!element) {
            return true;
        }

        const value = trimValue(element);
        if (!value) {
            return clearInvalid(element);
        }

        if (value > TODAY) {
            return setInvalid(element, message);
        }

        return clearInvalid(element);
    }

    function validateDateOrder(laterField, earlierField, message) {
        if (!laterField || !earlierField) {
            return true;
        }

        const laterValue = trimValue(laterField);
        const earlierValue = trimValue(earlierField);
        if (!laterValue || !earlierValue) {
            return clearInvalid(laterField);
        }

        if (laterValue < earlierValue) {
            return setInvalid(laterField, message);
        }

        return clearInvalid(laterField);
    }

    function validatePassingYear(element) {
        if (!element) {
            return true;
        }

        normalizeDigitField(element, 4);
        const value = trimValue(element);
        if (!value) {
            return clearInvalid(element);
        }

        const year = Number(value);
        const currentYear = new Date().getFullYear();
        if (!/^\d{4}$/.test(value) || year < 1900 || year > currentYear) {
            return setInvalid(element, `Passing year must be between 1900 and ${currentYear}.`);
        }

        return clearInvalid(element);
    }

    function validateEducationRow(row) {
        if (!row) {
            return true;
        }

        let isValid = true;
        const examName = trimValue(row.querySelector('[name$="[exam_name]"]')).toUpperCase();
        const streamField = row.querySelector('[name$="[education_stream]"]');
        const instituteField = row.querySelector('[name$="[institute_name]"]');
        const rollField = row.querySelector('[name$="[roll_number]"]');
        const yearField = row.querySelector('[name$="[passing_year]"]');
        const districtField = row.querySelector('[name$="[district]"]');
        const boardField = row.querySelector('[name$="[board_university]"]');
        const obtainedField = row.querySelector('.edu-obtained');
        const maxField = row.querySelector('.edu-max');
        const percentageField = row.querySelector('.edu-percent');

        if (yearField && !validatePassingYear(yearField)) {
            isValid = false;
        }

        const obtainedValue = trimValue(obtainedField);
        const maxValue = trimValue(maxField);
        const percentageValue = trimValue(percentageField);

        if (obtainedValue && Number(obtainedValue) < 0) {
            setInvalid(obtainedField, 'Obtained marks cannot be negative.');
            isValid = false;
        } else {
            clearInvalid(obtainedField);
        }

        if (maxValue && Number(maxValue) <= 0) {
            setInvalid(maxField, 'Max marks must be greater than 0.');
            isValid = false;
        } else if (obtainedValue && maxValue && Number(obtainedValue) > Number(maxValue)) {
            setInvalid(maxField, 'Max marks must be greater than or equal to obtained marks.');
            isValid = false;
        } else {
            clearInvalid(maxField);
        }

        if (percentageValue && Number(percentageValue) > 100) {
            setInvalid(percentageField, 'Percentage cannot be more than 100.');
            isValid = false;
        } else {
            clearInvalid(percentageField);
        }

        const rowHasContent = [streamField, instituteField, rollField, yearField, districtField, boardField, obtainedField, maxField]
            .some(field => trimValue(field) !== '');

        if (examName === '12TH' && rowHasContent && streamField && !trimValue(streamField)) {
            setInvalid(streamField, 'Select stream for 12TH.');
            isValid = false;
        } else if (streamField) {
            clearInvalid(streamField);
        }

        return isValid;
    }

    function validateAdmissionSource(form) {
        const sourceField = form.querySelector('[name="admission_source"]');
        const sourceIdField = form.querySelector('[name="admission_source_id"]:not([disabled])');
        const sourceValue = sourceField ? trimValue(sourceField) : trimValue(form.querySelector('input[name="admission_source"]:checked'));

        if (!sourceIdField) {
            return true;
        }

        if (['center', 'channel_partner'].includes(sourceValue) && !trimValue(sourceIdField)) {
            return setInvalid(sourceIdField, 'Please select the admission source.');
        }

        return clearInvalid(sourceIdField);
    }

    function validateForm(form, options = {}) {
        if (!form) {
            return true;
        }

        const report = options.report === true;
        let isValid = true;

        [
            ['mobile', 10, 'Mobile number must be 10 digits.'],
            ['father_mobile', 10, 'Father mobile number must be 10 digits.'],
            ['mother_mobile', 10, 'Mother mobile number must be 10 digits.'],
            ['guardian_mobile', 10, 'Guardian mobile number must be 10 digits.'],
            ['aadhar_no', 12, 'Aadhar number must be 12 digits.'],
            ['perm_pincode', 6, 'Pin code must be 6 digits.'],
            ['comm_pincode', 6, 'Pin code must be 6 digits.'],
        ].forEach(([name, length, message]) => {
            const field = form.querySelector(`[name="${name}"]`);
            if (field && !validateExactDigits(field, length, message)) {
                isValid = false;
            }
        });

        const emailField = form.querySelector('[name="email"]');
        if (emailField && !validateEmail(emailField)) {
            isValid = false;
        }

        [
            ['name', 'Student name'],
            ['father_name', 'Father name'],
            ['mother_name', 'Mother name'],
            ['guardian_name', 'Guardian name'],
        ].forEach(([name, label]) => {
            const field = form.querySelector(`[name="${name}"]`);
            if (field && !validateNameField(field, label)) {
                isValid = false;
            }
        });

        const dobField = form.querySelector('[name="dob"]');
        if (dobField && !validateDateNotFuture(dobField, 'Date of birth cannot be in the future.')) {
            isValid = false;
        }

        const admissionDateField = form.querySelector('[name="admission_date"]');
        if (admissionDateField && !validateDateNotFuture(admissionDateField, 'Admission date cannot be in the future.')) {
            isValid = false;
        }

        const submittedDateField = form.querySelector('[name="submitted_date"]');
        if (submittedDateField && !validateDateNotFuture(submittedDateField, 'Submitted date cannot be in the future.')) {
            isValid = false;
        }

        if (admissionDateField && submittedDateField && !validateDateOrder(submittedDateField, admissionDateField, 'Submitted date cannot be earlier than admission date.')) {
            isValid = false;
        }

        if (admissionDateField && dobField && !validateDateOrder(admissionDateField, dobField, 'Admission date cannot be earlier than date of birth.')) {
            isValid = false;
        }

        if (!validateAdmissionSource(form)) {
            isValid = false;
        }

        form.querySelectorAll('tr').forEach(row => {
            if (row.querySelector('[name^="education["]') && !validateEducationRow(row)) {
                isValid = false;
            }
        });

        if (!form.checkValidity()) {
            isValid = false;
        }

        if (report) {
            form.classList.add('was-validated');
        }
        const firstInvalid = form.querySelector('.is-invalid, :invalid');
        if (report && !isValid && firstInvalid && typeof firstInvalid.reportValidity === 'function') {
            firstInvalid.reportValidity();
            firstInvalid.focus({ preventScroll: false });
        }

        return isValid;
    }

    function bindFieldListeners(form) {
        if (!form || form.dataset.liveValidationBound === '1') {
            return;
        }

        form.dataset.liveValidationBound = '1';

        const exactDigitMap = {
            mobile: 10,
            father_mobile: 10,
            mother_mobile: 10,
            guardian_mobile: 10,
            aadhar_no: 12,
            perm_pincode: 6,
            comm_pincode: 6,
        };

        Object.entries(exactDigitMap).forEach(([name, length]) => {
            const field = form.querySelector(`[name="${name}"]`);
            if (!field) {
                return;
            }

            field.addEventListener('input', () => {
                normalizeDigitField(field, length);
                validateForm(form);
            });
            field.addEventListener('blur', () => validateForm(form));
        });

        ['email', 'dob', 'admission_date', 'submitted_date', 'admission_source', 'admission_source_id'].forEach(name => {
            form.querySelectorAll(`[name="${name}"]`).forEach(field => {
                field.addEventListener('input', () => validateForm(form));
                field.addEventListener('change', () => validateForm(form));
                field.addEventListener('blur', () => validateForm(form));
            });
        });

        ['name', 'father_name', 'mother_name', 'guardian_name'].forEach(name => {
            form.querySelectorAll(`[name="${name}"]`).forEach(field => {
                field.addEventListener('input', () => validateForm(form));
                field.addEventListener('blur', () => validateForm(form));
            });
        });

        form.querySelectorAll('[name$="[passing_year]"], .edu-obtained, .edu-max, .edu-percent, [name$="[education_stream]"]').forEach(field => {
            field.addEventListener('input', () => validateForm(form));
            field.addEventListener('change', () => validateForm(form));
            field.addEventListener('blur', () => validateForm(form));
        });
    }

    return {
        initForm(form) {
            bindFieldListeners(form);
        },
        validateForm,
    };
})();
</script>
