<?php

namespace App\Http\Controllers\Institute\Finance\Wallet;

use App\Http\Controllers\Controller;
use App\Models\ExpenseApprovalLimit;
use App\Models\StaffRole;
use Illuminate\Http\Request;

class ExpenseApprovalLimitController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    public function index()
    {
        $instituteId = $this->instituteId();

        $roles = StaffRole::where('institute_id', $instituteId)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $limits = ExpenseApprovalLimit::where('institute_id', $instituteId)
            ->pluck('max_auto_approve_amount', 'staff_role_id');

        return view('institute.finance.wallet.approval-limits.index', compact('roles', 'limits'));
    }

    public function update(Request $request)
    {
        $instituteId = $this->instituteId();

        $data = $request->validate([
            'limits'               => 'nullable|array',
            'limits.*'             => 'nullable|numeric|min:0',
            'expense_create'       => 'nullable|array',
        ]);

        $roles = StaffRole::where('institute_id', $instituteId)->where('status', true)->get();

        foreach ($roles as $role) {
            $roleId = $role->id;

            // Update expense_create permission only
            // Note: finance_manage is a broader permission controlled via Staff Role settings
            $canCreate = isset($data['expense_create'][$roleId]);
            $permissions = (array) ($role->permissions ?? []);
            $permissions['expense_create'] = $canCreate;
            $role->update(['permissions' => $permissions]);

            // Update approval limit amount
            $amount = (float) ($data['limits'][$roleId] ?? 0);
            ExpenseApprovalLimit::updateOrCreate(
                ['institute_id' => $instituteId, 'staff_role_id' => $roleId],
                ['max_auto_approve_amount' => round($amount, 2)]
            );
        }

        return back()->with('success', 'Permissions and approval limits saved successfully.');
    }
}
