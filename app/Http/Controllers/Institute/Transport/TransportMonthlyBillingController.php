<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Models\AcademicSession;
use App\Models\InstituteTransportSetting;
use App\Models\StudentTransaction;
use App\Models\StudentWallet;
use App\Models\TransportAllocation;
use App\Models\TransportMonthlyCharge;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransportMonthlyBillingController extends TransportBaseController
{
    public function index(Request $request)
    {
        $instituteId   = $this->instituteId();
        $sessions      = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $activeSession = $sessions->firstWhere('is_active', true);

        $chargeMonth = $request->input('charge_month', now()->format('Y-m'));
        $sessionId   = $request->filled('session_id') ? (int) $request->session_id : $activeSession?->id;
        $freqFilter  = $request->input('freq', 'all');

        // Active recurring allocations (excluding one_time and yearly)
        $allocationQuery = TransportAllocation::with(['student:id,name,roll_no', 'route:id,name,billing_frequency,fee_amount'])
            ->where('institute_id', $instituteId)
            ->where('is_active', true)
            ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->whereHas('route', fn ($q) => $q->whereNotIn('billing_frequency', ['one_time', 'yearly']));

        if ($freqFilter !== 'all') {
            $allocationQuery->whereHas('route', fn ($q) => $q->where('billing_frequency', $freqFilter));
        }

        $allocations = $allocationQuery->get();

        // Pre-fetch billed IDs for each frequency type — avoids N+1
        [$qStart, $qEnd] = $this->quarterWindow($chargeMonth);

        $monthlyBilledIds = TransportMonthlyCharge::where('institute_id', $instituteId)
            ->where('charge_month', $chargeMonth)
            ->pluck('transport_allocation_id')->toArray();

        $quarterBilledIds = TransportMonthlyCharge::where('institute_id', $instituteId)
            ->whereBetween('charge_month', [$qStart, $qEnd])
            ->pluck('transport_allocation_id')->unique()->toArray();

        // HIGH-4 fix: scope semester check only to current allocation IDs (not all-time institute charges)
        $currentAllocationIds = $allocations->pluck('id');
        $semesterBilledIds = TransportMonthlyCharge::where('institute_id', $instituteId)
            ->whereIn('transport_allocation_id', $currentAllocationIds)
            ->pluck('transport_allocation_id')->unique()->toArray();

        $allocations = $allocations->map(function ($a) use ($monthlyBilledIds, $quarterBilledIds, $semesterBilledIds) {
            $freq = $a->route?->billing_frequency ?? 'monthly';
            $a->already_billed = match ($freq) {
                'semester' => in_array($a->id, $semesterBilledIds),
                'quarterly' => in_array($a->id, $quarterBilledIds),
                default    => in_array($a->id, $monthlyBilledIds),
            };
            return $a;
        });

        $pendingCount = $allocations->where('already_billed', false)->count();
        $quarterLabel = $this->quarterLabel($chargeMonth);

        return view('institute.transport.billing.index', compact(
            'allocations', 'sessions', 'sessionId', 'chargeMonth',
            'pendingCount', 'freqFilter', 'quarterLabel'
        ));
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'charge_month'        => ['required', 'regex:/^\d{4}-\d{2}$/'],
            // CRIT-1 fix: verify academic_session_id belongs to current institute
            'academic_session_id' => [
                'required',
                Rule::exists('academic_sessions', 'id')->where('institute_id', $this->instituteId()),
            ],
        ]);

        $instituteId = $this->instituteId();

        $allocations = TransportAllocation::with(['route', 'student'])
            ->where('institute_id', $instituteId)
            ->where('is_active', true)
            ->where('academic_session_id', $data['academic_session_id'])
            ->whereHas('route', fn ($q) => $q->whereNotIn('billing_frequency', ['one_time', 'yearly']))
            ->get();

        if ($allocations->isEmpty()) {
            return back()->with('success', 'No active recurring allocations found for this session.');
        }

        $setting         = InstituteTransportSetting::forInstitute($instituteId);
        [$qStart, $qEnd] = $this->quarterWindow($data['charge_month']);

        // MED-3 fix: pre-fetch all already-billed IDs before the loop (eliminates N+1)
        $allocationIds = $allocations->pluck('id');

        $monthlyBilledSet = TransportMonthlyCharge::where('institute_id', $instituteId)
            ->whereIn('transport_allocation_id', $allocationIds)
            ->where('charge_month', $data['charge_month'])
            ->pluck('transport_allocation_id')->flip()->all();

        $quarterBilledSet = TransportMonthlyCharge::where('institute_id', $instituteId)
            ->whereIn('transport_allocation_id', $allocationIds)
            ->whereBetween('charge_month', [$qStart, $qEnd])
            ->pluck('transport_allocation_id')->unique()->flip()->all();

        $semesterBilledSet = TransportMonthlyCharge::where('institute_id', $instituteId)
            ->whereIn('transport_allocation_id', $allocationIds)
            ->pluck('transport_allocation_id')->unique()->flip()->all();

        $generated = 0;
        $skipped   = 0;

        DB::transaction(function () use (
            $allocations, $data, $instituteId, $setting,
            $qStart, $monthlyBilledSet, $quarterBilledSet, $semesterBilledSet,
            &$generated, &$skipped
        ) {
            foreach ($allocations as $allocation) {
                $freq = $allocation->route?->billing_frequency ?? 'monthly';

                // Frequency-aware duplicate check using pre-fetched sets
                $alreadyBilled = match ($freq) {
                    'semester' => isset($semesterBilledSet[$allocation->id]),
                    'quarterly' => isset($quarterBilledSet[$allocation->id]),
                    default    => isset($monthlyBilledSet[$allocation->id]),
                };

                if ($alreadyBilled) {
                    $skipped++;
                    continue;
                }

                $fullAmount = round((float) $allocation->fee_amount, 2);
                if ($fullAmount <= 0) {
                    $skipped++;
                    continue;
                }

                // Prorate only for monthly billing — quarterly/semester always charge in full
                $startDate = $allocation->start_date?->toDateString();
                $amount    = ($freq === 'monthly' && $startDate)
                    ? $setting->calculateProratedFee($fullAmount, $startDate, $data['charge_month'])
                    : $fullAmount;

                $label = match ($freq) {
                    'quarterly' => "Transport quarterly charge — {$allocation->route?->name} (Q: {$qStart})",
                    'semester'  => "Transport semester charge — {$allocation->route?->name}",
                    default     => "Transport monthly charge — {$allocation->route?->name} ({$data['charge_month']})",
                };

                TransportMonthlyCharge::create([
                    'transport_allocation_id' => $allocation->id,
                    'institute_id'            => $instituteId,
                    'charge_month'            => $data['charge_month'],
                    'amount'                  => $amount,
                    'generated_by'            => auth()->id(),
                ]);

                $allocation->charged_amount = round((float) $allocation->charged_amount + $amount, 2);
                $this->updateAllocationStatus($allocation);
                $allocation->save();

                // MED-4 fix: lockForUpdate before balance read to prevent concurrent payment race
                $wallet = StudentWallet::firstOrCreate(
                    ['student_id' => $allocation->student_id, 'academic_session_id' => $allocation->academic_session_id],
                    ['institute_id' => $instituteId, 'main_b' => 0.00]
                );
                $wallet = StudentWallet::where('id', $wallet->id)->lockForUpdate()->first();

                $opBal = (float) $wallet->main_b;
                $clBal = round($opBal - $amount, 2);

                StudentTransaction::create([
                    'student_id'              => $allocation->student_id,
                    'institute_id'            => $instituteId,
                    'academic_session_id'     => $allocation->academic_session_id,
                    'des'                     => $label,
                    'credit'                  => 0.00,
                    'debit'                   => $amount,
                    'type'                    => StudentTransaction::DEBIT,
                    'date'                    => now()->toDateString(),
                    'op_bal'                  => $opBal,
                    'cl_bal'                  => $clBal,
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

    // Returns [start, end] of the calendar quarter containing $chargeMonth
    // Q1=Jan-Mar, Q2=Apr-Jun, Q3=Jul-Sep, Q4=Oct-Dec
    private function quarterWindow(string $chargeMonth): array
    {
        $dt      = Carbon::parse($chargeMonth . '-01');
        $month   = $dt->month;
        $year    = $dt->year;
        $qStartM = (int) ((ceil($month / 3) - 1) * 3 + 1);
        $qEndM   = $qStartM + 2;

        return [
            sprintf('%04d-%02d', $year, $qStartM),
            sprintf('%04d-%02d', $year, $qEndM),
        ];
    }

    private function quarterLabel(string $chargeMonth): string
    {
        $dt   = Carbon::parse($chargeMonth . '-01');
        $q    = (int) ceil($dt->month / 3);
        $year = $dt->year;
        return "Q{$q} {$year}";
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
