<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\ExpenseCategoryL1;
use App\Models\FeeType;
use App\Models\InstituteIncomeCategory;
use App\Models\ReportParticular;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReportParticularController extends Controller
{
    private const SOURCE_TYPES_BY_SECTION = [
        'income'  => ['fee_invoice', 'manual_income', 'library_fine'],
        'expense' => ['expense', 'salary'],
    ];

    private const SOURCE_LABELS = [
        'fee_invoice'    => 'Fee Collection',
        'manual_income'  => 'Manual Income',
        'library_fine'   => 'Library Fine',
        'expense'        => 'Expense',
        'salary'         => 'Salary Payout',
    ];

    // Invoice items created without a fee_type_id (e.g. one-time transport fee,
    // practical exam fee) — only identifiable via FeeInvoiceItem.item_type.
    private const ITEM_TYPES = [
        'transport' => 'Transport Fee',
        'practical' => 'Practical Fee',
    ];

    public function index()
    {
        $instituteId = auth()->user()->institute_id;

        $particulars = ReportParticular::where('institute_id', $instituteId)
            ->with(['feeType', 'course', 'incomeCategory', 'expenseCategoryL1'])
            ->orderBy('section')->orderBy('sort_order')->orderBy('name')
            ->get()
            ->groupBy('section');

        $sourceLabels = self::SOURCE_LABELS;

        return view('institute.master.report-particulars.index', compact('particulars', 'sourceLabels'));
    }

    public function create()
    {
        $formData = $this->formData();
        return view('institute.master.report-particulars.create', $formData);
    }

    public function store(Request $request)
    {
        $data = $this->validateParticular($request);

        ReportParticular::create($data + [
            'institute_id' => auth()->user()->institute_id,
            'is_active'    => true,
            'sort_order'   => ReportParticular::where('institute_id', auth()->user()->institute_id)
                ->where('section', $data['section'])->max('sort_order') + 1,
        ]);

        return redirect()->route('master.report-particulars.index')->with('success', 'Report particular created!');
    }

    public function edit(ReportParticular $reportParticular)
    {
        abort_if($reportParticular->institute_id !== auth()->user()->institute_id, 403);
        $formData = $this->formData();
        return view('institute.master.report-particulars.edit', $formData + ['reportParticular' => $reportParticular]);
    }

    public function update(Request $request, ReportParticular $reportParticular)
    {
        abort_if($reportParticular->institute_id !== auth()->user()->institute_id, 403);
        $data = $this->validateParticular($request);
        $reportParticular->update($data);

        return redirect()->route('master.report-particulars.index')->with('success', 'Report particular updated!');
    }

    public function destroy(ReportParticular $reportParticular)
    {
        abort_if($reportParticular->institute_id !== auth()->user()->institute_id, 403);
        abort_if($reportParticular->is_system, 403, 'System particulars cannot be deleted.');
        $reportParticular->delete();

        return redirect()->route('master.report-particulars.index')->with('success', 'Deleted!');
    }

    public function toggle(ReportParticular $reportParticular)
    {
        abort_if($reportParticular->institute_id !== auth()->user()->institute_id, 403);
        $reportParticular->update(['is_active' => !$reportParticular->is_active]);

        return back()->with('success', 'Status updated!');
    }

    private function formData(): array
    {
        $instituteId = auth()->user()->institute_id;

        return [
            'sourceTypesBySection' => self::SOURCE_TYPES_BY_SECTION,
            'sourceLabels'         => self::SOURCE_LABELS,
            'itemTypes'            => self::ITEM_TYPES,
            'feeTypes'             => FeeType::where('institute_id', $instituteId)->where('is_active', true)->orderBy('name')->get(),
            'courses'              => Course::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get(),
            'incomeCategories'     => InstituteIncomeCategory::where('institute_id', $instituteId)->active()->orderBy('name')->get(),
            'expenseCategories'    => ExpenseCategoryL1::where('institute_id', $instituteId)->active()->orderBy('name')->get(),
        ];
    }

    private function validateParticular(Request $request): array
    {
        $instituteId = auth()->user()->institute_id;

        $request->validate([
            'section'     => ['required', Rule::in(array_keys(self::SOURCE_TYPES_BY_SECTION))],
            'source_type' => ['required', Rule::in(array_merge(...array_values(self::SOURCE_TYPES_BY_SECTION)))],
            'name'        => 'required|string|max:150',
        ]);

        $section = $request->input('section');
        $sourceType = $request->input('source_type');

        if (!in_array($sourceType, self::SOURCE_TYPES_BY_SECTION[$section] ?? [], true)) {
            throw ValidationException::withMessages(['source_type' => 'This source type is not valid for the selected section.']);
        }

        $data = [
            'section'     => $section,
            'source_type' => $sourceType,
            'name'        => strtoupper($request->input('name')),
            'fee_type_id' => null,
            'item_type'   => null,
            'course_id'   => null,
            'year_number' => null,
            'income_category_id'     => null,
            'expense_category_l1_id' => null,
            'salary_scope'           => null,
        ];

        switch ($sourceType) {
            case 'fee_invoice':
                $mode = $request->input('fee_mode', 'flat'); // 'flat' | 'course' | 'item_type'
                if ($mode === 'course') {
                    // One row covers every year of the course — the report itself
                    // expands it into a "Year" column per year, so no year_number
                    // is collected here.
                    $request->validate([
                        'course_id' => ['required', Rule::exists('courses', 'id')->where('institute_id', $instituteId)],
                    ]);
                    $data['course_id'] = $request->input('course_id');
                } elseif ($mode === 'item_type') {
                    $request->validate([
                        'item_type' => ['required', Rule::in(array_keys(self::ITEM_TYPES))],
                    ]);
                    $data['item_type'] = $request->input('item_type');
                } else {
                    $request->validate([
                        'fee_type_id' => ['required', Rule::exists('fee_types', 'id')->where('institute_id', $instituteId)],
                    ]);
                    $data['fee_type_id'] = $request->input('fee_type_id');
                }
                break;

            case 'manual_income':
                $request->validate([
                    'income_category_id' => ['required', Rule::exists('institute_income_categories', 'id')->where('institute_id', $instituteId)],
                ]);
                $data['income_category_id'] = $request->input('income_category_id');
                break;

            case 'expense':
                // Left blank = catch-all for expenses saved with no category (the expense
                // form allows that) — without this row those amounts are invisible to every
                // other expense particular, since none of them can match a null category.
                $request->validate([
                    'expense_category_l1_id' => ['nullable', Rule::exists('expense_categories_l1', 'id')->where('institute_id', $instituteId)],
                ]);
                $data['expense_category_l1_id'] = $request->input('expense_category_l1_id') ?: null;
                break;

            case 'salary':
                $request->validate([
                    'salary_scope' => ['required', Rule::in(['both', 'teaching', 'non_teaching'])],
                ]);
                $data['salary_scope'] = $request->input('salary_scope');
                break;

            case 'library_fine':
                // no reference field needed
                break;
        }

        return $data;
    }
}
