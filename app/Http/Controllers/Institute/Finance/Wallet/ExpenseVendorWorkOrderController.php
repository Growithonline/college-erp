<?php

namespace App\Http\Controllers\Institute\Finance\Wallet;

use App\Http\Controllers\Concerns\HasInstituteId;
use App\Http\Controllers\Controller;
use App\Models\ExpenseCategoryL1;
use App\Models\ExpenseCategoryL2;
use App\Models\ExpenseVendor;
use App\Models\ExpenseVendorWorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseVendorWorkOrderController extends Controller
{
    use HasInstituteId;

    private function authorizeVendor(ExpenseCategoryL1 $expenseCategory, ExpenseCategoryL2 $sub, ExpenseVendor $vendor): void
    {
        abort_if($expenseCategory->institute_id !== $this->instituteId(), 403);
        abort_if($sub->l1_id !== $expenseCategory->id, 404);
        abort_if($vendor->l2_id !== $sub->id, 404);
    }

    public function index(ExpenseCategoryL1 $expenseCategory, ExpenseCategoryL2 $sub, ExpenseVendor $vendor)
    {
        $this->authorizeVendor($expenseCategory, $sub, $vendor);

        $workOrders = ExpenseVendorWorkOrder::where('institute_id', $this->instituteId())
            ->where('expense_vendor_id', $vendor->id)
            ->latest('id')
            ->get();

        return view('institute.finance.wallet.work-orders.index', compact('expenseCategory', 'sub', 'vendor', 'workOrders'));
    }

    public function create(ExpenseCategoryL1 $expenseCategory, ExpenseCategoryL2 $sub, ExpenseVendor $vendor)
    {
        $this->authorizeVendor($expenseCategory, $sub, $vendor);

        return view('institute.finance.wallet.work-orders.form', compact('expenseCategory', 'sub', 'vendor'));
    }

    public function store(Request $request, ExpenseCategoryL1 $expenseCategory, ExpenseCategoryL2 $sub, ExpenseVendor $vendor)
    {
        $this->authorizeVendor($expenseCategory, $sub, $vendor);

        $data = $request->validate([
            'title'          => 'required|string|max:150',
            'description'    => 'nullable|string|max:1000',
            'notes'          => 'nullable|string|max:500',
            'initial_budget' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($data, $vendor) {
            $workOrder = ExpenseVendorWorkOrder::create([
                'institute_id'      => $this->instituteId(),
                'expense_vendor_id' => $vendor->id,
                'title'             => $data['title'],
                'description'       => $data['description'] ?? null,
                'status'            => 'open',
                'notes'             => $data['notes'] ?? null,
                'created_by'        => auth()->id(),
            ]);

            $initialBudget = (float) ($data['initial_budget'] ?? 0);
            if ($initialBudget > 0) {
                $workOrder->credit($initialBudget, 'Initial budget allocation', auth()->id());
            }
        });

        return redirect()->route('finance.wallet.expense-categories.sub.vendors.work-orders.index', [$expenseCategory, $sub, $vendor])
            ->with('success', 'Work order created.');
    }

    public function edit(ExpenseCategoryL1 $expenseCategory, ExpenseCategoryL2 $sub, ExpenseVendor $vendor, ExpenseVendorWorkOrder $workOrder)
    {
        $this->authorizeVendor($expenseCategory, $sub, $vendor);
        abort_if($workOrder->expense_vendor_id !== $vendor->id, 404);

        return view('institute.finance.wallet.work-orders.form', compact('expenseCategory', 'sub', 'vendor', 'workOrder'));
    }

    public function update(Request $request, ExpenseCategoryL1 $expenseCategory, ExpenseCategoryL2 $sub, ExpenseVendor $vendor, ExpenseVendorWorkOrder $workOrder)
    {
        $this->authorizeVendor($expenseCategory, $sub, $vendor);
        abort_if($workOrder->expense_vendor_id !== $vendor->id, 404);

        $data = $request->validate([
            'title'       => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'notes'       => 'nullable|string|max:500',
        ]);

        $workOrder->update($data);

        return redirect()->route('finance.wallet.expense-categories.sub.vendors.work-orders.index', [$expenseCategory, $sub, $vendor])
            ->with('success', 'Work order updated.');
    }

    public function topup(Request $request, ExpenseCategoryL1 $expenseCategory, ExpenseCategoryL2 $sub, ExpenseVendor $vendor, ExpenseVendorWorkOrder $workOrder)
    {
        $this->authorizeVendor($expenseCategory, $sub, $vendor);
        abort_if($workOrder->expense_vendor_id !== $vendor->id, 404);

        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'note'   => 'nullable|string|max:255',
        ]);

        $workOrder->credit((float) $data['amount'], $data['note'] ?? 'Budget top-up', auth()->id());

        return back()->with('success', 'Budget top-up added.');
    }

    public function ledger(ExpenseCategoryL1 $expenseCategory, ExpenseCategoryL2 $sub, ExpenseVendor $vendor, ExpenseVendorWorkOrder $workOrder)
    {
        $this->authorizeVendor($expenseCategory, $sub, $vendor);
        abort_if($workOrder->expense_vendor_id !== $vendor->id, 404);

        // Not paginated — mirrors the other ledger views in this module (student wallet,
        // expense-category ledger), since the running Op. Bal/Cl. Bal shown per row is
        // computed live in the view and needs the full ordered set to stay correct.
        $transactions = $workOrder->transactions()->orderBy('id')->get();

        $totalCredit  = (float) $transactions->where('type', 'credit')->sum('amount');
        $totalDebit   = (float) $transactions->where('type', 'debit')->sum('amount');
        $totalEntries = $transactions->count();

        return view('institute.finance.wallet.work-orders.ledger', compact(
            'expenseCategory', 'sub', 'vendor', 'workOrder',
            'transactions', 'totalCredit', 'totalDebit', 'totalEntries'
        ));
    }

    public function close(ExpenseCategoryL1 $expenseCategory, ExpenseCategoryL2 $sub, ExpenseVendor $vendor, ExpenseVendorWorkOrder $workOrder)
    {
        $this->authorizeVendor($expenseCategory, $sub, $vendor);
        abort_if($workOrder->expense_vendor_id !== $vendor->id, 404);

        $workOrder->close(auth()->id());

        return back()->with('success', 'Work order closed.');
    }
}
