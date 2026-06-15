<?php

namespace App\Http\Controllers\Institute\Finance\Wallet;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategoryL1;
use App\Models\ExpenseCategoryL2;
use Illuminate\Http\Request;

class ExpenseCategoryL2Controller extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    public function index(ExpenseCategoryL1 $expenseCategory)
    {
        abort_if($expenseCategory->institute_id !== $this->instituteId(), 403);

        $subCategories = ExpenseCategoryL2::where('institute_id', $this->instituteId())
            ->where('l1_id', $expenseCategory->id)
            ->withCount('vendors')
            ->orderBy('name')
            ->get();

        return view('institute.finance.wallet.expense-categories.sub-index', compact('expenseCategory', 'subCategories'));
    }

    public function create(ExpenseCategoryL1 $expenseCategory)
    {
        abort_if($expenseCategory->institute_id !== $this->instituteId(), 403);

        return view('institute.finance.wallet.expense-categories.form-l2', compact('expenseCategory'));
    }

    public function store(Request $request, ExpenseCategoryL1 $expenseCategory)
    {
        abort_if($expenseCategory->institute_id !== $this->instituteId(), 403);

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        ExpenseCategoryL2::create([
            'institute_id' => $this->instituteId(),
            'l1_id'        => $expenseCategory->id,
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'is_active'    => true,
        ]);

        return redirect()->route('finance.wallet.expense-categories.sub.index', $expenseCategory)
            ->with('success', 'Sub-category create ho gayi.');
    }

    public function edit(ExpenseCategoryL1 $expenseCategory, ExpenseCategoryL2 $sub)
    {
        abort_if($expenseCategory->institute_id !== $this->instituteId(), 403);
        abort_if($sub->l1_id !== $expenseCategory->id, 404);

        return view('institute.finance.wallet.expense-categories.form-l2', compact('expenseCategory', 'sub'));
    }

    public function update(Request $request, ExpenseCategoryL1 $expenseCategory, ExpenseCategoryL2 $sub)
    {
        abort_if($expenseCategory->institute_id !== $this->instituteId(), 403);
        abort_if($sub->l1_id !== $expenseCategory->id, 404);

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $sub->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return redirect()->route('finance.wallet.expense-categories.sub.index', $expenseCategory)
            ->with('success', 'Sub-category update ho gayi.');
    }

    public function destroy(ExpenseCategoryL1 $expenseCategory, ExpenseCategoryL2 $sub)
    {
        abort_if($expenseCategory->institute_id !== $this->instituteId(), 403);
        abort_if($sub->l1_id !== $expenseCategory->id, 404);

        if ($sub->vendors()->exists()) {
            return back()->with('error', 'Pehle is sub-category ke vendors delete karo.');
        }

        $sub->delete();

        return redirect()->route('finance.wallet.expense-categories.sub.index', $expenseCategory)
            ->with('success', 'Sub-category delete ho gayi.');
    }
}
