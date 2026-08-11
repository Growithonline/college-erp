<?php

namespace App\Http\Controllers\Institute\Finance\Wallet;

use App\Http\Controllers\Concerns\HasInstituteId;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategoryL1;
use Illuminate\Http\Request;

class ExpenseCategoryL1Controller extends Controller
{
    use HasInstituteId;

    public function index()
    {
        $categories = ExpenseCategoryL1::where('institute_id', $this->instituteId())
            ->withCount('subCategories')
            ->orderBy('name')
            ->get();

        return view('institute.finance.wallet.expense-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('institute.finance.wallet.expense-categories.form-l1');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        ExpenseCategoryL1::create([
            'institute_id' => $this->instituteId(),
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'is_active'    => true,
        ]);

        return redirect()->route('finance.wallet.expense-categories.index')
            ->with('success', 'Category created.');
    }

    public function edit(ExpenseCategoryL1 $expenseCategory)
    {
        abort_if($expenseCategory->institute_id !== $this->instituteId(), 403);

        return view('institute.finance.wallet.expense-categories.form-l1', ['category' => $expenseCategory]);
    }

    public function update(Request $request, ExpenseCategoryL1 $expenseCategory)
    {
        abort_if($expenseCategory->institute_id !== $this->instituteId(), 403);

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $expenseCategory->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return redirect()->route('finance.wallet.expense-categories.index')
            ->with('success', 'Category updated.');
    }

    public function destroy(ExpenseCategoryL1 $expenseCategory)
    {
        abort_if($expenseCategory->institute_id !== $this->instituteId(), 403);

        if ($expenseCategory->subCategories()->exists()) {
            return back()->with('error', 'Please delete this category\'s sub-categories first.');
        }

        if (Expense::where('expense_category_l1_id', $expenseCategory->id)->exists()) {
            return back()->with('error', 'This category is used on existing expenses and cannot be deleted.');
        }

        $expenseCategory->delete();

        return redirect()->route('finance.wallet.expense-categories.index')
            ->with('success', 'Category deleted.');
    }
}
