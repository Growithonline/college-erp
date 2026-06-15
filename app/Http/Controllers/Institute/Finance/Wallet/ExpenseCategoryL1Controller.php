<?php

namespace App\Http\Controllers\Institute\Finance\Wallet;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategoryL1;
use Illuminate\Http\Request;

class ExpenseCategoryL1Controller extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

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
            ->with('success', 'Category create ho gayi.');
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
            ->with('success', 'Category update ho gayi.');
    }

    public function destroy(ExpenseCategoryL1 $expenseCategory)
    {
        abort_if($expenseCategory->institute_id !== $this->instituteId(), 403);

        if ($expenseCategory->subCategories()->exists()) {
            return back()->with('error', 'Pehle is category ki sub-categories delete karo.');
        }

        $expenseCategory->delete();

        return redirect()->route('finance.wallet.expense-categories.index')
            ->with('success', 'Category delete ho gayi.');
    }
}
