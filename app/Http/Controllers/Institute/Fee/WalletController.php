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

        $student->load(['stream.course', 'coursePart']);
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
            'sessionBalances'
        ));
    }
}
