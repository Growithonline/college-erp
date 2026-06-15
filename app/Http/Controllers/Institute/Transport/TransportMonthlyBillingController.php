<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Models\AcademicSession;
use App\Models\StudentTransaction;
use App\Models\StudentWallet;
use App\Models\TransportAllocation;
use App\Models\TransportMonthlyCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransportMonthlyBillingController extends TransportBaseController
{
    public function index(Request $request)
    {
        $instituteId  = $this->instituteId();
        $sessions     = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $activeSession = $sessions->firstWhere('is_active', true);

        $chargeMonth = $request->input('charge_month', now()->format('Y-m'));
        $sessionId   = $request->filled('session_id') ? (int) $request->session_id : $activeSession?->id;

        // Active allocations on monthly/quarterly/semester routes
        $allocations = TransportAllocation::with(['student:id,name,roll_no', 'route:id,name,billing_frequency,fee_amount'])
            ->where('institute_id', $instituteId)
            ->where('is_active', true)
            ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->whereHas('route', fn ($q) => $q->where('billing_frequency', '!=', 'one_time'))
            ->get();

        // Which are already billed for this month
        $billedIds = TransportMonthlyCharge::where('institute_id', $instituteId)
            ->where('charge_month', $chargeMonth)
            ->pluck('transport_allocation_id')
            ->toArray();

        $allocations = $allocations->map(function ($a) use ($billedIds) {
            $a->already_billed = in_array($a->id, $billedIds);
            return $a;
        });

        $pendingCount = $allocations->where('already_billed', false)->count();

        return view('institute.transport.billing.index', compact(
            'allocations', 'sessions', 'sessionId', 'chargeMonth', 'pendingCount'
        ));
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'charge_month'     => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'academic_session_id' => ['required', 'integer'],
        ]);

        $instituteId = $this->instituteId();

        $allocations = TransportAllocation::with(['route', 'student'])
            ->where('institute_id', $instituteId)
            ->where('is_active', true)
            ->where('academic_session_id', $data['academic_session_id'])
            ->whereHas('route', fn ($q) => $q->where('billing_frequency', '!=', 'one_time'))
            ->get();

        $generated = 0;
        $skipped   = 0;

        DB::transaction(function () use ($allocations, $data, $instituteId, &$generated, &$skipped) {
            foreach ($allocations as $allocation) {
                $exists = TransportMonthlyCharge::where('transport_allocation_id', $allocation->id)
                    ->where('charge_month', $data['charge_month'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $amount = round((float) $allocation->fee_amount, 2);
                if ($amount <= 0) {
                    $skipped++;
                    continue;
                }

                TransportMonthlyCharge::create([
                    'transport_allocation_id' => $allocation->id,
                    'institute_id'            => $instituteId,
                    'charge_month'            => $data['charge_month'],
                    'amount'                  => $amount,
                    'generated_by'            => auth()->id(),
                ]);

                // Update allocation charged_amount
                $allocation->charged_amount = round((float) $allocation->charged_amount + $amount, 2);
                $this->updateAllocationStatus($allocation);
                $allocation->save();

                // Student wallet DEBIT
                $wallet = StudentWallet::firstOrCreate(
                    ['student_id' => $allocation->student_id, 'academic_session_id' => $allocation->academic_session_id],
                    ['institute_id' => $instituteId, 'main_b' => 0.00]
                );
                $wallet = StudentWallet::where('id', $wallet->id)->lockForUpdate()->first();

                $opBal = (float) $wallet->main_b;
                $clBal = round($opBal - $amount, 2);

                StudentTransaction::create([
                    'student_id'             => $allocation->student_id,
                    'institute_id'           => $instituteId,
                    'academic_session_id'    => $allocation->academic_session_id,
                    'des'                    => sprintf('Transport monthly charge — %s (%s)', $allocation->route?->name ?? 'Route', $data['charge_month']),
                    'credit'                 => 0.00,
                    'debit'                  => $amount,
                    'type'                   => StudentTransaction::DEBIT,
                    'date'                   => now()->toDateString(),
                    'op_bal'                 => $opBal,
                    'cl_bal'                 => $clBal,
                    'transport_allocation_id' => $allocation->id,
                ]);

                $wallet->main_b = $clBal;
                $wallet->save();

                $generated++;
            }
        });

        $msg = "Generated {$generated} charges for {$data['charge_month']}.";
        if ($skipped) {
            $msg .= " {$skipped} skipped (already billed or zero fee).";
        }

        return back()->with('success', $msg);
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
