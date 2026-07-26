@extends('institute.layout')
@section('title', isset($reportParticular) ? 'Edit Report Particular' : 'Add Report Particular')
@section('breadcrumb','Master / Daily Register / ' . (isset($reportParticular) ? 'Edit' : 'New'))
@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-list-columns me-2 text-primary"></i>{{ isset($reportParticular) ? 'Edit Report Particular' : 'Add Report Particular' }}</h5>
            </div>
            <div class="card-body p-4">
                @if(isset($reportParticular))
                    <form method="POST" action="{{ route('master.report-particulars.update', $reportParticular) }}">@method('PUT')
                @else
                    <form method="POST" action="{{ route('master.report-particulars.store') }}">
                @endif
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $reportParticular->name ?? '') }}"
                           class="form-control @error('name') is-invalid @enderror" placeholder="e.g. B.A. 1st Year, TC Fee, Library Fine"
                           style="text-transform:uppercase">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Section <span class="text-danger">*</span></label>
                    <select name="section" id="rpSection" class="form-select @error('section') is-invalid @enderror">
                        <option value="">Select Section</option>
                        <option value="income"  {{ old('section', $reportParticular->section ?? '') == 'income'  ? 'selected' : '' }}>Income</option>
                        <option value="expense" {{ old('section', $reportParticular->section ?? '') == 'expense' ? 'selected' : '' }}>Expense</option>
                    </select>
                    @error('section') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Source <span class="text-danger">*</span></label>
                    <select name="source_type" id="rpSourceType" class="form-select @error('source_type') is-invalid @enderror">
                        <option value="">Select Section First</option>
                    </select>
                    @error('source_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- fee_invoice: flat FeeType vs item-type vs course-wise --}}
                @php
                    $currentFeeMode = old('fee_mode', ($reportParticular->course_id ?? null) ? 'course' : (($reportParticular->item_type ?? null) ? 'item_type' : 'flat'));
                @endphp
                <div id="rpFeeInvoiceFields" class="d-none">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fee Row Type</label>
                        <div class="d-flex gap-3 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="fee_mode" id="rpFeeModeFlat" value="flat"
                                       {{ $currentFeeMode == 'flat' ? 'checked' : '' }}>
                                <label class="form-check-label" for="rpFeeModeFlat">Flat Fee Type (e.g. TC Fee)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="fee_mode" id="rpFeeModeCourse" value="course"
                                       {{ $currentFeeMode == 'course' ? 'checked' : '' }}>
                                <label class="form-check-label" for="rpFeeModeCourse">Course-wise (with semester split)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="fee_mode" id="rpFeeModeItemType" value="item_type"
                                       {{ $currentFeeMode == 'item_type' ? 'checked' : '' }}>
                                <label class="form-check-label" for="rpFeeModeItemType">Transport / Practical Fee</label>
                            </div>
                        </div>
                        <small class="text-muted">Transport &amp; Practical fee invoice items aren't linked to a Fee Type — use this option for those.</small>
                    </div>

                    <div id="rpFeeTypeField" class="mb-3">
                        <label class="form-label fw-semibold">Fee Type <span class="text-danger">*</span></label>
                        <select name="fee_type_id" class="form-select @error('fee_type_id') is-invalid @enderror">
                            <option value="">Select Fee Type</option>
                            @foreach($feeTypes as $ft)
                                <option value="{{ $ft->id }}" {{ old('fee_type_id', $reportParticular->fee_type_id ?? '') == $ft->id ? 'selected' : '' }}>{{ $ft->name }}</option>
                            @endforeach
                        </select>
                        @error('fee_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div id="rpItemTypeField" class="mb-3 d-none">
                        <label class="form-label fw-semibold">Item Type <span class="text-danger">*</span></label>
                        <select name="item_type" class="form-select @error('item_type') is-invalid @enderror">
                            <option value="">Select Item Type</option>
                            @foreach($itemTypes as $key => $label)
                                <option value="{{ $key }}" {{ old('item_type', $reportParticular->item_type ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('item_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div id="rpCourseFields" class="d-none">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Course <span class="text-danger">*</span></label>
                            <select name="course_id" class="form-select @error('course_id') is-invalid @enderror">
                                <option value="">Select Course</option>
                                @foreach($courses as $c)
                                    <option value="{{ $c->id }}" {{ old('course_id', $reportParticular->course_id ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                            @error('course_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Covers every year of this course — the report shows a "Year" column per year, each with its own semester/trimester breakdown.</small>
                        </div>
                    </div>
                </div>

                <div id="rpManualIncomeFields" class="mb-3 d-none">
                    <label class="form-label fw-semibold">Income Category <span class="text-danger">*</span></label>
                    <select name="income_category_id" class="form-select @error('income_category_id') is-invalid @enderror">
                        <option value="">Select Category</option>
                        @foreach($incomeCategories as $ic)
                            <option value="{{ $ic->id }}" {{ old('income_category_id', $reportParticular->income_category_id ?? '') == $ic->id ? 'selected' : '' }}>{{ $ic->name }}</option>
                        @endforeach
                    </select>
                    @error('income_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div id="rpExpenseFields" class="mb-3 d-none">
                    <label class="form-label fw-semibold">Expense Category</label>
                    <select name="expense_category_l1_id" class="form-select @error('expense_category_l1_id') is-invalid @enderror">
                        <option value="">— Uncategorized (expenses saved with no category) —</option>
                        @foreach($expenseCategories as $ec)
                            <option value="{{ $ec->id }}" {{ old('expense_category_l1_id', $reportParticular->expense_category_l1_id ?? '') == $ec->id ? 'selected' : '' }}>{{ $ec->name }}</option>
                        @endforeach
                    </select>
                    @error('expense_category_l1_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small class="text-muted">Leave as "Uncategorized" to create a catch-all row for expenses with no category set.</small>
                </div>

                <div id="rpSalaryFields" class="mb-3 d-none">
                    <label class="form-label fw-semibold">Salary Scope <span class="text-danger">*</span></label>
                    <select name="salary_scope" class="form-select @error('salary_scope') is-invalid @enderror">
                        <option value="both"         {{ old('salary_scope', $reportParticular->salary_scope ?? 'both') == 'both'         ? 'selected' : '' }}>Both (Teaching + Non-Teaching)</option>
                        <option value="teaching"     {{ old('salary_scope', $reportParticular->salary_scope ?? '') == 'teaching'     ? 'selected' : '' }}>Teaching Staff Only</option>
                        <option value="non_teaching" {{ old('salary_scope', $reportParticular->salary_scope ?? '') == 'non_teaching' ? 'selected' : '' }}>Non-Teaching Staff Only</option>
                    </select>
                    @error('salary_scope') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Save</button>
                    <a href="{{ route('master.report-particulars.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const sourceTypesBySection = @json($sourceTypesBySection);
    const sourceLabels = @json($sourceLabels);
    const currentSourceType = @json(old('source_type', $reportParticular->source_type ?? ''));

    const sectionSel = document.getElementById('rpSection');
    const sourceSel  = document.getElementById('rpSourceType');

    function populateSourceTypes() {
        const section = sectionSel.value;
        sourceSel.innerHTML = '';
        if (!section) {
            sourceSel.innerHTML = '<option value="">Select Section First</option>';
            toggleFields();
            return;
        }
        const opt0 = document.createElement('option');
        opt0.value = ''; opt0.textContent = 'Select Source';
        sourceSel.appendChild(opt0);
        (sourceTypesBySection[section] || []).forEach(st => {
            const opt = document.createElement('option');
            opt.value = st;
            opt.textContent = sourceLabels[st] || st;
            if (st === currentSourceType) opt.selected = true;
            sourceSel.appendChild(opt);
        });
        toggleFields();
    }

    function toggleFields() {
        const st = sourceSel.value;
        document.getElementById('rpFeeInvoiceFields').classList.toggle('d-none', st !== 'fee_invoice');
        document.getElementById('rpManualIncomeFields').classList.toggle('d-none', st !== 'manual_income');
        document.getElementById('rpExpenseFields').classList.toggle('d-none', st !== 'expense');
        document.getElementById('rpSalaryFields').classList.toggle('d-none', st !== 'salary');
        if (st === 'fee_invoice') toggleFeeMode();
    }

    function toggleFeeMode() {
        const mode = document.querySelector('input[name="fee_mode"]:checked')?.value || 'flat';
        document.getElementById('rpCourseFields').classList.toggle('d-none', mode !== 'course');
        document.getElementById('rpFeeTypeField').classList.toggle('d-none', mode !== 'flat');
        document.getElementById('rpItemTypeField').classList.toggle('d-none', mode !== 'item_type');
    }

    sectionSel.addEventListener('change', populateSourceTypes);
    sourceSel.addEventListener('change', toggleFields);
    document.getElementById('rpFeeModeFlat').addEventListener('change', toggleFeeMode);
    document.getElementById('rpFeeModeCourse').addEventListener('change', toggleFeeMode);
    document.getElementById('rpFeeModeItemType').addEventListener('change', toggleFeeMode);

    populateSourceTypes();
})();
</script>
@endpush
@endsection
