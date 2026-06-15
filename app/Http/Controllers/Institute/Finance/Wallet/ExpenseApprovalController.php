<?php

namespace App\Http\Controllers\Institute\Finance\Wallet;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Expense;
use App\Services\InstituteWalletService;
use App\Services\JournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseApprovalController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    public function index(Request $request)
    {
        $instituteId = $this->instituteId();

        $pending = Expense::with(['expenseAccount', 'categoryL1', 'categoryL2', 'vendor'])
            ->where('institute_id', $instituteId)
            ->where('approval_status', Expense::STATUS_PENDING)
            ->where('is_reversed', false)
            ->latest('expense_date')
            ->latest('id')
            ->paginate(30);

        $totalPendingAmount = Expense::where('institute_id', $instituteId)
            ->where('approval_status', Expense::STATUS_PENDING)
            ->where('is_reversed', false)
            ->sum('amount');

        return view('institute.finance.expenses.pending-approvals', compact('pending', 'totalPendingAmount'));
    }

    public function approve(Request $request, Expense $expense)
    {
        abort_if($expense->institute_id !== $this->instituteId(), 403);
        abort_if($expense->approval_status !== Expense::STATUS_PENDING, 422, 'Expense pending nahi hai.');
        abort_if($expense->is_reversed, 422, 'Reversed expense ko approve nahi kar sakte.');

        $instituteId     = $this->instituteId();
        $walletSessionId = $expense->academic_session_id
            ?? AcademicSession::where('institute_id', $instituteId)->where('is_active', true)->value('id');

        // Pre-check wallet balance (non-locked, for user feedback)
        if ($walletSessionId) {
            $balance = InstituteWalletService::getBalance($instituteId, $walletSessionId);
            if ($balance < (float) $expense->amount) {
                return back()->with('error',
                    'Wallet balance insufficient. Available: Rs ' . number_format($balance, 2) .
                    ', Required: Rs ' . number_format($expense->amount, 2));
            }
        }

        $approverId = auth()->guard('staff')->id() ?? auth()->id();

        // Wrap approval + wallet debit + GL posting in one transaction
        DB::transaction(function () use ($expense, $walletSessionId, $approverId) {
            // Re-check status inside transaction to prevent double-approve
            $fresh = Expense::where('id', $expense->id)->lockForUpdate()->first();

            if (!$fresh || $fresh->approval_status !== Expense::STATUS_PENDING) {
                return; // Already processed by a concurrent request
            }

            if ($walletSessionId && !$fresh->academic_session_id) {
                $fresh->update(['academic_session_id' => $walletSessionId]);
            }

            $fresh->update([
                'approval_status'      => Expense::STATUS_APPROVED,
                'approved_by_staff_id' => $approverId,
                'approved_at'          => now(),
            ]);

            // Debit wallet (inside this transaction — wallet service uses its own nested tx)
            if ($walletSessionId) {
                InstituteWalletService::debitExpense($fresh->fresh(['categoryL2', 'vendor']));
            }
        });

        // GL journal posted outside the locked transaction (safe — idempotent via entry_key)
        $freshExpense = $expense->fresh(['expenseAccount', 'paymentAccount', 'bankAccount']);
        if ($freshExpense && $freshExpense->approval_status === Expense::STATUS_APPROVED) {
            $journalEntry = JournalService::safePostExpense($freshExpense);
            if ($journalEntry && !$freshExpense->journal_entry_id) {
                $freshExpense->update(['journal_entry_id' => $journalEntry->id]);
            }
        }

        return back()->with('success', 'Expense approved, wallet se debit ho gaya aur GL entry post ho gayi.');
    }

    public function reject(Request $request, Expense $expense)
    {
        abort_if($expense->institute_id !== $this->instituteId(), 403);
        abort_if($expense->approval_status !== Expense::STATUS_PENDING, 422, 'Expense pending nahi hai.');

        $data = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $expense->update([
            'approval_status'           => Expense::STATUS_REJECTED,
            'approval_rejection_reason' => $data['rejection_reason'],
            'approved_by_staff_id'      => auth()->guard('staff')->id() ?? auth()->id(),
            'approved_at'               => now(),
        ]);

        return back()->with('success', 'Expense reject kar diya gaya.');
    }
}
