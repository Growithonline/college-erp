<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Models\AcademicSession;
use App\Models\FeeInvoice;
use App\Models\FeeInvoiceItem;
use App\Models\StudentTransaction;
use App\Models\StudentWallet;
use App\Models\TransportAllocation;
use App\Services\StudentIdService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransportBillingController extends TransportBaseController
{
    public function index(Request $request)
    {
        $instituteId   = $this->instituteId();
        $sessions      = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $activeSession = $sessions->firstWhere('is_active', true);

        $sessionId = $request->filled('session_id') ? (int) $request->session_id : $activeSession?->id;

        // Semester allocations (only remaining recurring type after billing simplification)
        $allocations = TransportAllocation::with(['student:id,name,roll_no', 'route:id,name,billing_frequency,fee_amount'])
            ->where('institute_id', $instituteId)
            ->where('is_active', true)
            ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->whereHas('route', fn ($q) => $q->where('billing_frequency', 'semester'))
            ->get();

        // already_billed = charged_amount > 0 (semester is charged once per session)
        $allocations = $allocations->map(function ($a) {
            $a->already_billed = (float) $a->charged_amount > 0;
            return $a;
        });

        $pendingCount = $allocations->where('already_billed', false)->count();

        // One-time allocations (separate section — not recurring billing)
        $oneTimeAllocations = TransportAllocation::with(['student:id,name,roll_no', 'route:id,name'])
            ->where('institute_id', $instituteId)
            ->where('is_active', true)
            ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->whereHas('route', fn ($q) => $q->where('billing_frequency', 'one_time'))
            ->orderBy('student_id')
            ->get();

        return view('institute.transport.billing.index', compact(
            'allocations', 'sessions', 'sessionId',
            'pendingCount', 'oneTimeAllocations'
        ));
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'academic_session_id' => [
                'required',
                Rule::exists('academic_sessions', 'id')->where('institute_id', $this->instituteId()),
            ],
        ]);

        $instituteId = $this->instituteId();

        $allocationIds = TransportAllocation::where('institute_id', $instituteId)
            ->where('is_active', true)
            ->where('academic_session_id', $data['academic_session_id'])
            ->whereHas('route', fn ($q) => $q->where('billing_frequency', 'semester'))
            ->with('route:id,name')
            ->pluck('id');

        if ($allocationIds->isEmpty()) {
            return back()->with('success', 'No active semester allocations found for this session.');
        }

        $generated = 0;
        $skipped   = 0;

        DB::transaction(function () use ($allocationIds, $instituteId, &$generated, &$skipped) {
            foreach ($allocationIds as $allocationId) {
                // Lock the allocation row before reading charged_amount — prevents double-charge
                // if two staff members click Generate simultaneously.
                $locked = TransportAllocation::where('id', $allocationId)
                    ->lockForUpdate()
                    ->with('route:id,name')
                    ->first();

                if (!$locked || (float) $locked->charged_amount > 0) {
                    $skipped++;
                    continue;
                }

                $amount = round((float) $locked->fee_amount, 2);
                if ($amount <= 0) {
                    $skipped++;
                    continue;
                }

                $label = 'Transport semester charge — ' . ($locked->route?->name ?? '');

                $locked->charged_amount = $amount;
                $this->updateAllocationStatus($locked);
                $locked->save();

                $wallet = StudentWallet::firstOrCreate(
                    ['student_id' => $locked->student_id, 'academic_session_id' => $locked->academic_session_id],
                    ['institute_id' => $instituteId, 'main_b' => 0.00]
                );
                $wallet = StudentWallet::where('id', $wallet->id)->lockForUpdate()->first();

                $opBal = (float) $wallet->main_b;
                $clBal = round($opBal - $amount, 2);

                StudentTransaction::create([
                    'student_id'              => $locked->student_id,
                    'institute_id'            => $instituteId,
                    'academic_session_id'     => $locked->academic_session_id,
                    'des'                     => $label,
                    'credit'                  => 0.00,
                    'debit'                   => $amount,
                    'type'                    => StudentTransaction::DEBIT,
                    'date'                    => now()->toDateString(),
                    'op_bal'                  => $opBal,
                    'cl_bal'                  => $clBal,
                    'transport_allocation_id' => $locked->id,
                ]);

                $wallet->main_b = $clBal;
                $wallet->save();

                $generated++;
            }
        });

        $msg = "Generated {$generated} semester charges.";
        if ($skipped) {
            $msg .= " {$skipped} skipped (already charged or zero fee).";
        }

        return back()->with('success', $msg);
    }

    public function collectOneTime(Request $request, TransportAllocation $allocation)
    {
        $this->assertInstituteModel($allocation);

        if ($allocation->route?->billing_frequency !== 'one_time') {
            return back()->with('error', 'Only one-time allocations can use this action.');
        }

        $balance = round((float) $allocation->fee_amount - (float) $allocation->paid_amount, 2);

        if ($balance <= 0) {
            return back()->with('success', 'Fee already fully collected.');
        }

        $data = $request->validate([
            'amount'       => ['required', 'numeric', 'min:0.01', 'max:' . $balance],
            'payment_mode' => ['required', 'in:cash,upi,online,cheque'],
            'payment_date' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:100'],
        ], [
            'amount.max' => "Amount cannot exceed balance ₹{$balance}.",
        ]);

        $requestedAmount = round((float) $data['amount'], 2);

        $allocation->loadMissing(['student', 'route']);

        $invoice = null;
        $amount  = 0.0;
        DB::transaction(function () use ($allocation, $data, $requestedAmount, &$invoice, &$amount) {
            $locked      = TransportAllocation::where('id', $allocation->id)->lockForUpdate()->first();
            $liveBalance = round((float) $locked->fee_amount - (float) $locked->paid_amount, 2);
            $amount      = min($requestedAmount, max(0, $liveBalance));
            if ($amount <= 0) {
                return;
            }

            $instituteId = $allocation->institute_id;
            $studentId   = $allocation->student_id;
            $sessionId   = $allocation->academic_session_id;
            $routeName   = $allocation->route?->name ?? '';

            $invoiceNo = StudentIdService::generateInvoiceId($instituteId, now()->year);

            $invoice = FeeInvoice::create([
                'institute_id'          => $instituteId,
                'student_id'            => $studentId,
                'academic_session_id'   => $sessionId,
                'semester'              => $allocation->student?->current_semester ?? 1,
                'invoice_no'            => $invoiceNo,
                'total_amount'          => $amount,
                'discount'              => 0,
                'paid_amount'           => $amount,
                'payment_mode'          => $data['payment_mode'],
                'transaction_ref'       => $data['reference_no'] ?? null,
                'payment_date'          => $data['payment_date'],
                'payment_datetime'      => now(),
                'remarks'               => 'Transport fee (one-time) — ' . $routeName,
                'collected_by'          => auth()->guard('staff')->user()?->name ?? auth()->user()?->name ?? 'Staff',
                'collected_by_staff_id' => auth()->guard('staff')->id(),
                'is_cancelled'          => false,
                'remaining_due'         => 0,
            ]);

            FeeInvoiceItem::create([
                'fee_invoice_id' => $invoice->id,
                'item_type'      => 'transport',
                'fee_name'       => 'Transport Fee — ' . $routeName,
                'amount'         => $amount,
                'discount'       => 0,
                'fine'           => 0,
                'total_fee'      => $amount,
            ]);

            WalletService::onFeeCollection($invoice);
            WalletService::settleTransportFromInvoice($allocation->id, $amount, $invoice->id, auth()->id());
        });

        if (!$invoice) {
            return back()->with('success', 'Fee already fully collected.');
        }

        $allocation->refresh();
        $newBalance  = round((float) $allocation->fee_amount - (float) $allocation->paid_amount, 2);
        $studentName = $allocation->student?->name ?? 'Student';

        $txnId = StudentTransaction::where('fee_invoice_id', $invoice->id)
            ->where('type', StudentTransaction::CREDIT)
            ->latest('id')
            ->value('id');

        $msg = $newBalance <= 0
            ? '₹' . number_format($amount, 2) . " collected from {$studentName}. Fee fully paid."
            : '₹' . number_format($amount, 2) . " collected from {$studentName}. Balance ₹" . number_format(max(0, $newBalance), 2) . ' remaining.';

        return back()->with('success', $msg)->with('receipt_txn_id', $txnId);
    }

    public function receipt(StudentTransaction $transaction)
    {
        $this->assertInstituteModel($transaction);

        $transaction->load([
            'student',
            'session',
            'invoice',
            'transportAllocation.route',
            'transportAllocation.stop',
            'transportAllocation.vehicle',
            'transportAllocation.driver',
        ]);

        $allocation = $transaction->transportAllocation
            ?? \App\Models\TransportPayment::with(['allocation.route', 'allocation.stop', 'allocation.vehicle', 'allocation.driver'])
                ->where('fee_invoice_id', $transaction->fee_invoice_id)
                ->latest('id')
                ->first()?->allocation;

        return view('institute.transport.billing.receipt', compact('transaction', 'allocation'));
    }

    private function updateAllocationStatus(TransportAllocation $allocation): void
    {
        $balance = (float) $allocation->charged_amount - (float) $allocation->paid_amount;
        if ($balance <= 0) {
            $allocation->status = 'paid';
        } elseif ((float) $allocation->paid_amount > 0) {
            $allocation->status = 'partial';
        } else {
            $allocation->status = 'active';
        }
    }
}
