<?php

namespace App\Traits;

use App\Models\AcademicSession;
use App\Models\FeeInvoice;
use App\Models\Student;
use App\Models\StudentWallet;
use App\Services\WalletService;

trait BuildsStudentStatements
{
    protected function buildBalances(Student $student, int $instituteId): \Illuminate\Support\Collection
    {
        $wallets = StudentWallet::where('student_id', $student->id)
            ->where('institute_id', $instituteId)
            ->get()
            ->keyBy('academic_session_id');

        $invoiceSessionIds = FeeInvoice::where('student_id', $student->id)
            ->where('is_cancelled', false)
            ->pluck('academic_session_id')
            ->filter()
            ->unique();

        $allSessionIds = $wallets->keys()
            ->merge($invoiceSessionIds)
            ->unique()
            ->sort()
            ->values();

        if ($allSessionIds->isEmpty()) {
            return collect();
        }

        $sessions = AcademicSession::whereIn('id', $allSessionIds)
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        $currentSessionId = (int) $student->academic_session_id;

        return $allSessionIds->map(function ($sessId) use ($wallets, $sessions, $student, $currentSessionId) {
            $session = $sessions[$sessId] ?? null;
            if (!$session) return null;

            $invoiceIds = FeeInvoice::where('student_id', $student->id)
                ->where('academic_session_id', $sessId)
                ->where('is_cancelled', false)
                ->pluck('id');

            $paid     = (float) FeeInvoice::whereIn('id', $invoiceIds)->sum('paid_amount');
            $discount = (float) FeeInvoice::whereIn('id', $invoiceIds)->sum('discount');
            $fine     = (float) \App\Models\FeeInvoiceItem::whereIn('fee_invoice_id', $invoiceIds)->sum('fine');

            $summary       = WalletService::getStudentSummary($student, (int) $sessId);
            $walletBalance = (float) ($wallets[$sessId]?->main_b ?? 0);

            $isOldSession      = ($sessId < $currentSessionId);
            $wasCarriedForward = $isOldSession && ($walletBalance < 0);
            $due = !$wasCarriedForward ? (float) ($summary['total_due'] ?? 0) : 0;

            return [
                'session'         => $session,
                'paid'            => $paid,
                'discount'        => $discount,
                'fine'            => $fine,
                'due'             => $due,
                'carried_forward' => $wasCarriedForward,
            ];
        })->filter()->values();
    }

    protected function buildHistory(Student $student, int $instituteId): \Illuminate\Support\Collection
    {
        $wallets = StudentWallet::where('student_id', $student->id)
            ->where('institute_id', $instituteId)
            ->get()
            ->keyBy('academic_session_id');

        $invoiceSessionIds = FeeInvoice::where('student_id', $student->id)
            ->where('is_cancelled', false)
            ->pluck('academic_session_id')
            ->filter()
            ->unique();

        $allSessionIds = $wallets->keys()
            ->merge($invoiceSessionIds)
            ->unique()
            ->sort()
            ->values();

        if ($allSessionIds->isEmpty()) {
            return collect();
        }

        $sessions = AcademicSession::whereIn('id', $allSessionIds)
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        $currentSessionId = (int) $student->academic_session_id;

        return $allSessionIds->map(function ($sessId) use ($wallets, $sessions, $student, $currentSessionId) {
            $session = $sessions[$sessId] ?? null;
            if (!$session) return null;

            $invoices = FeeInvoice::where('student_id', $student->id)
                ->where('academic_session_id', $sessId)
                ->where('is_cancelled', false)
                ->orderBy('payment_date')
                ->orderBy('id')
                ->get();

            $summary           = WalletService::getStudentSummary($student, (int) $sessId);
            $walletBalance     = (float) ($wallets[$sessId]?->main_b ?? 0);
            $isOldSession      = ($sessId < $currentSessionId);
            $wasCarriedForward = $isOldSession && ($walletBalance < 0);

            if ($invoices->isEmpty() && $walletBalance == 0) {
                return null;
            }

            $due         = !$wasCarriedForward ? (float) ($summary['total_due'] ?? 0) : 0;
            $total_fee   = max(
                (float) ($summary['total_charged'] ?? 0),
                (float) $invoices->sum('total_amount')
            );

            return [
                'session'        => $session,
                'invoices'       => $invoices,
                'total_paid'     => $invoices->sum('paid_amount'),
                'total_discount' => $invoices->sum('discount'),
                'total_fee'      => $total_fee,
                'due'            => $due,
                'carried_forward'=> $wasCarriedForward,
            ];
        })->filter()->values();
    }
}
