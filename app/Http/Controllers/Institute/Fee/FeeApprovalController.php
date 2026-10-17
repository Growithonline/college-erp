<?php

namespace App\Http\Controllers\Institute\Fee;

use App\Http\Controllers\Controller;
use App\Models\FeeInvoice;
use App\Models\Student;
use App\Services\StudentIdService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeApprovalController extends Controller
{
    private function instituteId(): int
    {
        // Staff routes run on the staff guard; the default (web) guard is only the institute owner.
        $user = auth()->guard('staff')->user() ?? auth()->user();
        abort_if(!$user || !$user->institute_id, 403, 'Institute context missing.');

        return (int) $user->institute_id;
    }

    private function currentStaff()
    {
        return auth()->guard('staff')->user();
    }

    // Approvers see/act only on invoices inside their own session/course scope.
    private function ensureInvoiceInScope(FeeInvoice $invoice): void
    {
        $staff = $this->currentStaff();
        if (!$staff) {
            return;
        }

        abort_if(
            !$staff->canAccessAcademicSession((int) $invoice->academic_session_id)
            || !$invoice->student
            || !$staff->canAccessStudentForOperations($invoice->student),
            403,
            'This invoice is outside your access scope.'
        );
    }

    private function scopePendingForStaff($query)
    {
        $staff = $this->currentStaff();
        if (!$staff) {
            return $query;
        }

        if ($staff->hasRestrictedSessionAccess()) {
            $query->whereIn('academic_session_id', $staff->allowedSessionIds() ?: [-1]);
        }

        return $query->whereHas('student', fn ($studentQuery) => $staff->scopeOperationalStudents($studentQuery));
    }

    public function index(Request $request)
    {
        $staff = $this->currentStaff();
        abort_if($staff && !$staff->canApproveFee(), 403, 'Fee approval permission required.');

        $instituteId = $this->instituteId();

        $pending = $this->scopePendingForStaff(
            FeeInvoice::with(['student:id,name,roll_no', 'collectedByStaff:id,name'])
                ->where('institute_id', $instituteId)
                ->pendingApproval()
        )->latest('id')->paginate(30);

        $totalPendingAmount = $this->scopePendingForStaff(
            FeeInvoice::where('institute_id', $instituteId)->pendingApproval()
        )->get()
            ->sum(fn ($invoice) => (float) ($invoice->pending_settlement_data['paid_amount'] ?? 0));

        return view('institute.fee.pending-approvals', compact('pending', 'totalPendingAmount'));
    }

    public function approve(Request $request, FeeInvoice $invoice)
    {
        $staff = $this->currentStaff();
        abort_if($staff && !$staff->canApproveFee(), 403, 'Fee approval permission required.');
        abort_if($invoice->institute_id !== $this->instituteId(), 403);
        abort_if(!$invoice->isPendingApproval(), 422, 'This invoice is not awaiting approval.');
        $this->ensureInvoiceInScope($invoice);

        // Maker-checker: whoever collected the fee cannot also approve it.
        abort_if(
            $staff && (int) $invoice->collected_by_staff_id === (int) $staff->id,
            403,
            'You cannot approve a collection you made yourself — another approver must review it.'
        );

        $instituteId = $this->instituteId();
        $approverId = auth()->guard('staff')->id() ?? auth()->id();

        try {
            DB::transaction(function () use ($invoice, $instituteId, $approverId) {
                // Re-fetch + lock to prevent double-approve from two concurrent clicks/requests.
                $fresh = FeeInvoice::where('id', $invoice->id)->lockForUpdate()->first();
                if (!$fresh || $fresh->approval_status !== FeeInvoice::STATUS_PENDING) {
                    return;
                }

                $payload = $fresh->pending_settlement_data ?? [];
                $validItems = collect($payload['valid_items'] ?? []);
                $paidAmount = (float) ($payload['paid_amount'] ?? 0);
                $totalCleared = (float) ($payload['total_cleared'] ?? $paidAmount);
                $totalDiscount = (float) ($payload['total_discount'] ?? 0);
                $year = (int) ($payload['year'] ?? now()->year);

                if ($validItems->isEmpty() || $paidAmount <= 0) {
                    $fresh->update([
                        'approval_status'           => FeeInvoice::STATUS_REJECTED,
                        'approval_rejection_reason' => 'Auto-rejected: no settlement data found.',
                        'approved_by_staff_id'      => $approverId,
                        'approved_at'               => now(),
                    ]);
                    return;
                }

                $invoiceNo = StudentIdService::generateInvoiceId($instituteId, $year);

                $fresh->update([
                    'invoice_no'           => $invoiceNo,
                    'total_amount'         => $totalCleared,
                    'discount'             => $totalDiscount,
                    'paid_amount'          => $paidAmount,
                    'approval_status'      => FeeInvoice::STATUS_APPROVED,
                    'approved_by_staff_id' => $approverId,
                    'approved_at'          => now(),
                ]);

                $fresh->load('student');

                WalletService::settleApprovedInvoice($fresh, $validItems->all());
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Could not approve this invoice. Please try again or contact support.');
        }

        $freshStudent = Student::find($invoice->student_id);
        if ($freshStudent) {
            try {
                $remainingDueSnapshot = WalletService::buildPendingRows($freshStudent, (int) $freshStudent->academic_session_id)->sum('pending');
                FeeInvoice::where('id', $invoice->id)->update(['remaining_due' => max(0, (float) $remainingDueSnapshot)]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with('success', 'Fee collection approved — wallet and income records updated.');
    }

    public function reject(Request $request, FeeInvoice $invoice)
    {
        $staff = $this->currentStaff();
        abort_if($staff && !$staff->canApproveFee(), 403, 'Fee approval permission required.');
        abort_if($invoice->institute_id !== $this->instituteId(), 403);
        abort_if(!$invoice->isPendingApproval(), 422, 'This invoice is not awaiting approval.');
        $this->ensureInvoiceInScope($invoice);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $invoice->update([
            'approval_status'           => FeeInvoice::STATUS_REJECTED,
            'approval_rejection_reason' => $data['rejection_reason'],
            'approved_by_staff_id'      => auth()->guard('staff')->id() ?? auth()->id(),
            'approved_at'               => now(),
        ]);

        return back()->with('success', 'Fee collection request rejected — nothing was charged.');
    }
}
