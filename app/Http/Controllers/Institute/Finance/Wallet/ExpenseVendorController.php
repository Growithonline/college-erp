<?php

namespace App\Http\Controllers\Institute\Finance\Wallet;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategoryL1;
use App\Models\ExpenseCategoryL2;
use App\Models\ExpenseVendor;
use Illuminate\Http\Request;

class ExpenseVendorController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    public function index(ExpenseCategoryL1 $expenseCategory, ExpenseCategoryL2 $sub)
    {
        abort_if($expenseCategory->institute_id !== $this->instituteId(), 403);
        abort_if($sub->l1_id !== $expenseCategory->id, 404);

        $vendors = ExpenseVendor::where('institute_id', $this->instituteId())
            ->where('l2_id', $sub->id)
            ->orderBy('name')
            ->get();

        return view('institute.finance.wallet.vendors.index', compact('expenseCategory', 'sub', 'vendors'));
    }

    public function create(ExpenseCategoryL1 $expenseCategory, ExpenseCategoryL2 $sub)
    {
        abort_if($expenseCategory->institute_id !== $this->instituteId(), 403);
        abort_if($sub->l1_id !== $expenseCategory->id, 404);

        return view('institute.finance.wallet.vendors.form', compact('expenseCategory', 'sub'));
    }

    public function store(Request $request, ExpenseCategoryL1 $expenseCategory, ExpenseCategoryL2 $sub)
    {
        abort_if($expenseCategory->institute_id !== $this->instituteId(), 403);
        abort_if($sub->l1_id !== $expenseCategory->id, 404);

        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'gst_no'        => 'nullable|string|max:20',
            'pan_no'        => 'nullable|string|max:15',
            'contact_name'  => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:100',
            'address'       => 'nullable|string|max:500',
            'notes'         => 'nullable|string|max:500',
        ]);

        ExpenseVendor::create(array_merge($data, [
            'institute_id' => $this->instituteId(),
            'l2_id'        => $sub->id,
            'is_active'    => true,
        ]));

        return redirect()->route('finance.wallet.expense-categories.sub.vendors.index', [$expenseCategory, $sub])
            ->with('success', 'Vendor add ho gaya.');
    }

    public function edit(ExpenseCategoryL1 $expenseCategory, ExpenseCategoryL2 $sub, ExpenseVendor $vendor)
    {
        abort_if($expenseCategory->institute_id !== $this->instituteId(), 403);
        abort_if($sub->l1_id !== $expenseCategory->id, 404);
        abort_if($vendor->l2_id !== $sub->id, 404);

        return view('institute.finance.wallet.vendors.form', compact('expenseCategory', 'sub', 'vendor'));
    }

    public function update(Request $request, ExpenseCategoryL1 $expenseCategory, ExpenseCategoryL2 $sub, ExpenseVendor $vendor)
    {
        abort_if($expenseCategory->institute_id !== $this->instituteId(), 403);
        abort_if($sub->l1_id !== $expenseCategory->id, 404);
        abort_if($vendor->l2_id !== $sub->id, 404);

        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'gst_no'        => 'nullable|string|max:20',
            'pan_no'        => 'nullable|string|max:15',
            'contact_name'  => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:100',
            'address'       => 'nullable|string|max:500',
            'notes'         => 'nullable|string|max:500',
        ]);

        $vendor->update(array_merge($data, [
            'is_active' => $request->boolean('is_active', true),
        ]));

        return redirect()->route('finance.wallet.expense-categories.sub.vendors.index', [$expenseCategory, $sub])
            ->with('success', 'Vendor update ho gaya.');
    }

    public function destroy(ExpenseCategoryL1 $expenseCategory, ExpenseCategoryL2 $sub, ExpenseVendor $vendor)
    {
        abort_if($expenseCategory->institute_id !== $this->instituteId(), 403);
        abort_if($sub->l1_id !== $expenseCategory->id, 404);
        abort_if($vendor->l2_id !== $sub->id, 404);

        $vendor->delete();

        return redirect()->route('finance.wallet.expense-categories.sub.vendors.index', [$expenseCategory, $sub])
            ->with('success', 'Vendor delete ho gaya.');
    }
}
