<?php

namespace App\Http\Controllers\Institute\Finance\Wallet;

use App\Exceptions\InsufficientWalletBalanceException;
use App\Http\Controllers\Concerns\HasInstituteId;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Expense;
use App\Models\FinanceSetting;
use App\Services\InstituteWalletService;
use App\Services\JournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseApprovalController extends Controller
{
    use HasInstituteId;

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
        abort_if($expense->approval_status !== Expense::STATUS_PENDING, 422, 'Expense is not pending.');
        abort_if($expense->is_reversed, 422, 'A reversed expense cannot be approved.');
        abort_if(
            FinanceSetting::isDateLocked($this->instituteId(), $expense->expense_date),
            422,
            'This expense falls in a locked accounting period (' . $expense->expense_date?->format('d M Y') . ') and cannot be approved.'
        );

        $instituteId     = $this->instituteId();
        $walletSessionId = $expense->academic_session_id
            ?? AcademicSession::viewSessionId($instituteId);

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
        try {
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
        } catch (InsufficientWalletBalanceException $e) {
            return back()->with('error',
                'Wallet balance insufficient. Available: Rs ' . number_format($e->available, 2) .
                ', Required: Rs ' . number_format($e->required, 2));
        }

        // GL journal posted outside the locked transaction (safe — idempotent via entry_key)
        $freshExpense = $expense->fresh(['expenseAccount', 'paymentAccount', 'bankAccount']);
        if ($freshExpense && $freshExpense->approval_status === Expense::STATUS_APPROVED) {
            $journalEntry = JournalService::safePostExpense($freshExpense);
            if ($journalEntry && !$freshExpense->journal_entry_id) {
                $freshExpense->update(['journal_entry_id' => $journalEntry->id]);
            }
        }

        return back()->with('success', 'Expense approved, wallet debited and GL entry posted.');
    }

    public function reject(Request $request, Expense $expense)
    {
        abort_if($expense->institute_id !== $this->instituteId(), 403);
        abort_if($expense->approval_status !== Expense::STATUS_PENDING, 422, 'Expense is not pending.');

        $data = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $rejectorId = auth()->guard('staff')->id() ?? auth()->id();

        DB::transaction(function () use ($expense, $data, $rejectorId) {
            // Re-check status under lock — prevents a race with a concurrent approve()
            $fresh = Expense::where('id', $expense->id)->lockForUpdate()->first();

            if (!$fresh || $fresh->approval_status !== Expense::STATUS_PENDING) {
                return; // Already processed by a concurrent request
            }

            $fresh->update([
                'approval_status'           => Expense::STATUS_REJECTED,
                'approval_rejection_reason' => $data['rejection_reason'],
                'approved_by_staff_id'      => $rejectorId,
                'approved_at'               => now(),
            ]);
        });

        return back()->with('success', 'Expense rejected.');
    }
}
