<?php

namespace App\Http\Controllers\Institute\Finance\Wallet;

use App\Http\Controllers\Concerns\HasInstituteId;
use App\Http\Controllers\Controller;
use App\Models\InstituteIncomeCategory;
use Illuminate\Http\Request;

class IncomeCategoryController extends Controller
{
    use HasInstituteId;

    public function index()
    {
        $categories = InstituteIncomeCategory::where('institute_id', $this->instituteId())
            ->orderBy('name')
            ->get();

        return view('institute.finance.wallet.income-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('institute.finance.wallet.income-categories.form');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        InstituteIncomeCategory::create([
            'institute_id' => $this->instituteId(),
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'is_active'    => true,
        ]);

        return redirect()->route('finance.wallet.income-categories.index')
            ->with('success', 'Income category created.');
    }

    public function edit(InstituteIncomeCategory $incomeCategory)
    {
        abort_if($incomeCategory->institute_id !== $this->instituteId(), 403);

        return view('institute.finance.wallet.income-categories.form', compact('incomeCategory'));
    }

    public function update(Request $request, InstituteIncomeCategory $incomeCategory)
    {
        abort_if($incomeCategory->institute_id !== $this->instituteId(), 403);

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);

        $incomeCategory->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return redirect()->route('finance.wallet.income-categories.index')
            ->with('success', 'Income category updated.');
    }

    public function destroy(InstituteIncomeCategory $incomeCategory)
    {
        abort_if($incomeCategory->institute_id !== $this->instituteId(), 403);

        if ($incomeCategory->manualIncomes()->exists()) {
            return back()->with('error', 'This category has incomes attached; it cannot be deleted.');
        }

        $incomeCategory->delete();

        return redirect()->route('finance.wallet.income-categories.index')
            ->with('success', 'Income category deleted.');
    }
}
