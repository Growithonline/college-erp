<?php

namespace App\Http\Controllers\Institute\Fee;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\FeeInvoice;
use App\Models\Student;
use App\Models\StudentTransaction;
use App\Models\StudentWallet;
use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    private function authenticatedUser()
    {
        foreach (['staff', 'center', 'partner', 'web'] as $guard) {
            if (auth()->guard($guard)->check()) {
                return auth()->guard($guard)->user();
            }
        }

        return auth()->user();
    }

    private function instituteId(): int
    {
        $user = $this->authenticatedUser();

        abort_if(!$user || !$user->institute_id, 403, 'Institute context missing.');

        return (int) $user->institute_id;
    }

    private function currentStaff(): ?\App\Models\StaffMember
    {
        return auth()->guard('staff')->user();
    }

    public function studentWallet(Student $student, Request $request)
    {
        if ($student->institute_id !== $this->instituteId()) {
            abort(403);
        }
        if ($staff = $this->currentStaff()) {
            abort_if(!$staff->canAccessStudentForOperations($student), 403, 'This student is outside your access scope.');
        }

        if ($student->status === 'pending') {
            return redirect()->back()->with('error', "This student's admission is pending approval. Wallet access is not allowed until the admission is approved.");
        }

        $student->load(['stream.course', 'coursePart', 'feePlan.installments']);
        $instituteId = $this->instituteId();

        $sessions = AcademicSession::where('institute_id', $instituteId)
            ->orderBy('name')
            ->get();

        $selectedSessionId = $request->session_id
            ?? $student->academic_session_id
            ?? AcademicSession::where('institute_id', $instituteId)
                ->where('is_active', true)
                ->value('id');

        $selectedSession = $sessions->firstWhere('id', $selectedSessionId);

        $transactions = StudentTransaction::where('student_id', $student->id)
            ->where('academic_session_id', $selectedSessionId)
            ->orderBy('created_at', 'asc')
            ->get();

        $summary = WalletService::getStudentSummary($student, (int) $selectedSessionId);
        $pendingFees = WalletService::buildPendingRows($student, (int) $selectedSessionId);

        // Fee plan installment info
        $feePlanInfo = null;
        $plan = $student->feePlan;
        if ($plan && $plan->installments->count() > 0) {
            // Use raw "Fee charged:" debits as the plan base — stable across semester promotions.
            // After carry-forward promotions, total_charged reflects only the carried amount
            // (e.g. ₹5,500) rather than the original plan total (e.g. ₹14,000).
            $originalCharged    = WalletService::getOriginalFeeCharged($student->id, (int) $selectedSessionId);
            $totalFeeForPlan    = $originalCharged > 0 ? $originalCharged : (float) ($summary['total_charged'] ?? 0);

            // Use ledger_collection (raw cash received) so carry-forward sems show correct paid
            // amount. total_paid from summary can be 0 when only previous_due items exist.
            $totalPaid          = (float) ($summary['ledger_collection'] ?? $summary['total_paid'] ?? 0);

            $installmentAmounts = $plan->installmentAmounts($totalFeeForPlan);

            $cumulativeDue  = 0.0;
            $nextDueInst    = null;
            $nextDueAmount  = 0.0;
            $totalDueSoFar  = 0.0;

            foreach ($plan->installments as $inst) {
                $amt = (float) ($installmentAmounts[$inst->installment_number] ?? 0);
                if ($inst->isDue($student)) {
                    $totalDueSoFar += $amt;
                    $cumulativeDue += $amt;
                    if ($nextDueInst === null && $totalPaid < $cumulativeDue - 0.5) {
                        $nextDueInst   = $inst;
                        // Net amount still needed to complete this installment (not the full amt)
                        $nextDueAmount = min($amt, $cumulativeDue - $totalPaid);
                    }
                }
            }

            $feePlanInfo = [
                'plan'               => $plan,
                'installmentAmounts' => $installmentAmounts,
                'totalFee'           => $totalFeeForPlan,
                'totalPaid'          => $totalPaid,
                'totalDueSoFar'      => $totalDueSoFar,
                'nextDueInst'        => $nextDueInst,
                'nextDueAmount'      => $nextDueAmount,
                // All triggered installments minus paid — used by the Collect button to pre-fill
                // the full outstanding amount across every due installment, not just the next one.
                'fillAmount'         => max(0.0, $totalDueSoFar - $totalPaid),
                'overdue'            => $totalPaid < $totalDueSoFar - 0.5,
            ];
        }

        $sessionBalances = $sessions->map(function ($session) use ($student) {
            $hasWallet = StudentWallet::where('student_id', $student->id)
                ->where('academic_session_id', $session->id)
                ->exists();

            $hasTransactions = StudentTransaction::where('student_id', $student->id)
                ->where('academic_session_id', $session->id)
                ->exists();

            $hasInvoices = FeeInvoice::where('student_id', $student->id)
                ->where('academic_session_id', $session->id)
                ->where('is_cancelled', false)
                ->exists();

            if (!$hasWallet && !$hasTransactions && !$hasInvoices) {
                return [
                    'session' => $session,
                    'balance' => null,
                ];
            }

            $sessionSummary = WalletService::getStudentSummary($student, (int) $session->id);

            return [
                'session' => $session,
                'balance' => $sessionSummary['balance'],
            ];
        });

        return view('institute.fee.student-wallet', compact(
            'student',
            'sessions',
            'selectedSession',
            'selectedSessionId',
            'transactions',
            'summary',
            'pendingFees',
            'sessionBalances',
            'feePlanInfo'
        ));
    }
}
