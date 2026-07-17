<?php

namespace App\Http\Controllers\Institute\Admission;

use App\Http\Controllers\Controller;
use App\Mail\PaymentRejectedMail;
use App\Mail\PaymentVerifiedMail;
use App\Models\FeeInvoice;
use App\Models\InstituteBankAccount;
use App\Models\PaymentClaim;
use App\Models\Student;
use App\Services\AuditLogService;
use App\Services\InstituteMailer;
use App\Services\StudentIdService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class PaymentClaimController extends Controller
{
    private function actorGuard(): string
    {
        return auth()->guard('staff')->check() ? 'staff' : 'web';
    }

    private function actorUser()
    {
        return auth()->guard($this->actorGuard())->user();
    }

    private function instituteId(): int
    {
        return (int) $this->actorUser()->institute_id;
    }

    private function canApprove(): bool
    {
        if ($this->actorGuard() === 'staff') {
            return (bool) $this->actorUser()->canApproveFee();
        }
        return true;
    }

    private function staffId(): ?int
    {
        return $this->actorGuard() === 'staff' ? $this->actorUser()->id : null;
    }

    private function notifyVerified(Student $student, float $amount): void
    {
        if (!$student->email) {
            return;
        }
        InstituteMailer::send($student->institute_id, $student->email, new PaymentVerifiedMail($student, $amount));
    }

    private function notifyRejected(Student $student, string $reason): void
    {
        if (!$student->email) {
            return;
        }
        $paymentUrl = URL::temporarySignedRoute(
            'public.application.payment.show',
            now()->addDays(30),
            ['shortName' => strtolower($student->institute->short_name), 'student' => $student->id]
        );
        InstituteMailer::send($student->institute_id, $student->email, new PaymentRejectedMail($student, $reason, $paymentUrl));
    }

    private function settle(PaymentClaim $claim, float $amount, string $invoicePaymentMode): void
    {
        $student = $claim->student;
        $year = (int) now()->format('Y');
        $invoiceNo = StudentIdService::generateInvoiceId($this->instituteId(), $year);

        $invoice = FeeInvoice::create([
            'institute_id'          => $this->instituteId(),
            'student_id'            => $student->id,
            'academic_session_id'   => $student->academic_session_id,
            'semester'               => $student->current_semester ?? 1,
            'invoice_no'             => $invoiceNo,
            'total_amount'           => $amount,
            'discount'               => 0,
            'paid_amount'            => $amount,
            'payment_mode'           => $invoicePaymentMode,
            'bank_account_id'        => $claim->bank_account_id,
            'transaction_ref'        => $claim->transaction_ref,
            'payment_date'           => now()->toDateString(),
            'payment_datetime'       => now(),
            'remarks'                => 'Online admission — application payment',
            'collected_by'           => $this->actorUser()->name ?? $this->actorUser()->email ?? 'Institute Admin',
            'collected_by_staff_id'  => auth()->guard('staff')->id(),
            'approval_status'        => FeeInvoice::STATUS_APPROVED,
        ]);

        WalletService::settleApprovedInvoice($invoice, [[
            'fee_type_id' => null,
            'fee_name'    => 'Admission / Registration Fee',
            'amount'      => $amount,
            'discount'    => 0,
            'fine'        => 0,
            'total_fee'   => $amount,
            'item_type'   => 'admission_payment',
        ]]);

        $claim->update([
            'fee_invoice_id' => $invoice->id,
        ]);
    }

    public function verify(Request $request, PaymentClaim $claim)
    {
        abort_if($claim->institute_id !== $this->instituteId(), 403);
        abort_unless($this->canApprove(), 403, 'Fee approval permission required.');
        abort_unless($claim->isPending(), 422, 'This claim has already been reviewed.');

        $validated = $request->validate([
            'confirmed_amount' => ['nullable', 'numeric', 'min:1'],
        ]);
        $amount = (float) ($validated['confirmed_amount'] ?? $claim->amount_claimed);
        $invoicePaymentMode = $claim->payment_mode === 'pay_at_institute' ? 'cash' : 'upi';

        $this->settle($claim, $amount, $invoicePaymentMode);

        $claim->update([
            'amount_claimed'      => $amount,
            'verification_status' => 'approved',
            'verified_by'         => $this->staffId(),
            'verified_at'         => now(),
        ]);

        AuditLogService::log($this->instituteId(), 'payment_claim', 'verified',
            "Payment claim verified for {$claim->student->name} (₹{$amount})", $claim, [
                'student_id' => $claim->student_id,
                'amount'     => $amount,
            ]);

        $this->notifyVerified($claim->student, $amount);

        return back()->with('success', 'Payment verified and recorded.');
    }

    public function reject(Request $request, PaymentClaim $claim)
    {
        abort_if($claim->institute_id !== $this->instituteId(), 403);
        abort_unless($this->canApprove(), 403, 'Fee approval permission required.');
        abort_unless($claim->isPending(), 422, 'This claim has already been reviewed.');

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $claim->update([
            'verification_status' => 'rejected',
            'verified_by'         => $this->staffId(),
            'verified_at'         => now(),
            'rejection_reason'    => $validated['rejection_reason'],
        ]);

        AuditLogService::log($this->instituteId(), 'payment_claim', 'rejected',
            "Payment claim rejected for {$claim->student->name}", $claim, [
                'reason' => $validated['rejection_reason'],
            ]);

        $this->notifyRejected($claim->student, $validated['rejection_reason']);

        return back()->with('success', 'Payment claim rejected.');
    }

    public function recordManual(Request $request, Student $student)
    {
        abort_if($student->institute_id !== $this->instituteId(), 403);
        abort_unless($this->canApprove(), 403, 'Fee approval permission required.');

        $validated = $request->validate([
            'amount'          => ['required', 'numeric', 'min:1'],
            'payment_mode'    => ['required', 'in:cash,upi,neft,rtgs,cheque,dd'],
            'bank_account_id' => ['nullable', 'integer', 'exists:institute_bank_accounts,id'],
            'transaction_ref' => ['nullable', 'string', 'max:100'],
        ]);

        if (!empty($validated['bank_account_id'])) {
            $belongs = InstituteBankAccount::where('id', $validated['bank_account_id'])
                ->where('institute_id', $this->instituteId())->exists();
            abort_unless($belongs, 422);
        }

        $claim = PaymentClaim::create([
            'institute_id'         => $this->instituteId(),
            'student_id'           => $student->id,
            'amount_due'           => $student->feePlan?->dueNowAmount($student) ?? $validated['amount'],
            'amount_claimed'       => $validated['amount'],
            'payment_mode'         => 'pay_at_institute',
            'transaction_ref'      => $validated['transaction_ref'] ?? null,
            'bank_account_id'      => $validated['bank_account_id'] ?? null,
            'verification_status'  => 'pending',
            'recorded_by_staff_id' => $this->staffId(),
        ]);

        $this->settle($claim, (float) $validated['amount'], $validated['payment_mode']);

        $claim->update([
            'verification_status' => 'approved',
            'verified_by'         => $this->staffId(),
            'verified_at'         => now(),
        ]);

        AuditLogService::log($this->instituteId(), 'payment_claim', 'recorded_manually',
            "Payment recorded manually for {$student->name} (₹{$validated['amount']})", $claim, [
                'student_id' => $student->id,
                'amount'     => $validated['amount'],
            ]);

        $this->notifyVerified($student, (float) $validated['amount']);

        return back()->with('success', 'Payment recorded.');
    }
}
