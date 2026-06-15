<?php

namespace App\Http\Controllers\Institute\Payroll;

use App\Http\Controllers\Controller;
use App\Models\StaffLoan;
use App\Models\StaffMember;
use App\Services\JournalService;
use Illuminate\Http\Request;

class StaffLoanController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    public function index(Request $request)
    {
        $instituteId = $this->instituteId();
        $status      = $request->input('status', 'active');
        $staffId     = $request->input('staff_id');

        $query = StaffLoan::where('institute_id', $instituteId)
            ->with('staffMember');

        if ($status !== 'all') {
            $query->where('status', $status);
        }
        if ($staffId) {
            $query->where('staff_member_id', $staffId);
        }

        $loans     = $query->latest()->get();
        $staffList = StaffMember::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();

        return view('institute.payroll.loans.index', compact('loans', 'staffList', 'status', 'staffId'));
    }

    public function store(Request $request)
    {
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'staff_member_id'   => 'required|integer|exists:staff_members,id',
            'loan_type'         => 'required|in:advance,loan',
            'principal_amount'  => 'required|numeric|min:1',
            'monthly_deduction' => 'required|numeric|min:1',
            'start_month'       => 'required|integer|min:1|max:12',
            'start_year'        => 'required|integer|min:2020|max:2100',
            'purpose'           => 'nullable|string|max:255',
        ]);

        StaffMember::where('institute_id', $instituteId)->findOrFail((int) $validated['staff_member_id']);

        $loan = StaffLoan::create([
            'institute_id'      => $instituteId,
            'staff_member_id'   => (int) $validated['staff_member_id'],
            'loan_type'         => $validated['loan_type'],
            'principal_amount'  => $validated['principal_amount'],
            'outstanding_amount' => $validated['principal_amount'],
            'monthly_deduction' => $validated['monthly_deduction'],
            'start_month'       => (int) $validated['start_month'],
            'start_year'        => (int) $validated['start_year'],
            'status'            => StaffLoan::STATUS_ACTIVE,
            'purpose'           => $validated['purpose'] ?? null,
            'approved_by'       => auth()->id(),
            'created_by'        => auth()->id(),
        ]);

        // Post disbursement journal: Dr Staff Advance Receivable / Cr Cash
        JournalService::safePostStaffLoanDisbursement($loan);

        return back()->with('success', 'Loan/advance record create ho gaya aur accounting entry post ho gayi.');
    }

    public function cancel(StaffLoan $staffLoan)
    {
        abort_if($staffLoan->institute_id !== $this->instituteId(), 403);
        abort_if($staffLoan->status !== StaffLoan::STATUS_ACTIVE, 422, 'Sirf active loan cancel kiya ja sakta hai.');

        $staffLoan->update(['status' => StaffLoan::STATUS_CANCELLED]);

        return back()->with('success', 'Loan cancelled.');
    }
}
