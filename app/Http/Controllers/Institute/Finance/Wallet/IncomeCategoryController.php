<?php

namespace App\Http\Controllers\Institute\Finance\Wallet;

use App\Http\Controllers\Controller;
use App\Models\InstituteIncomeCategory;
use Illuminate\Http\Request;

class IncomeCategoryController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

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
            ->with('success', 'Income category create ho gayi.');
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
            ->with('success', 'Income category update ho gayi.');
    }

    public function destroy(InstituteIncomeCategory $incomeCategory)
    {
        abort_if($incomeCategory->institute_id !== $this->instituteId(), 403);

        if ($incomeCategory->manualIncomes()->exists()) {
            return back()->with('error', 'Is category me incomes hain, delete nahi ho sakti.');
        }

        $incomeCategory->delete();

        return redirect()->route('finance.wallet.income-categories.index')
            ->with('success', 'Income category delete ho gayi.');
    }
}
