<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class InstituteTransportSetting extends Model
{
    protected $fillable = [
        'institute_id',
        'on_route_transfer',
        'yearly_fee_cross_session',
        'semester_duration_months',
    ];

    protected $casts = [
        'yearly_fee_cross_session' => 'boolean',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    // Get settings for an institute, creating defaults if not exist.
    // try/catch handles rare race condition where two concurrent requests both attempt insert.
    public static function forInstitute(int $instituteId): self
    {
        try {
            return static::firstOrCreate(
                ['institute_id' => $instituteId],
                [
                    'on_route_transfer'         => 'full_charge',
                    'yearly_fee_cross_session'  => true,
                    'semester_duration_months'  => 6,
                ]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            // Duplicate insert race — another request created it first
            return static::where('institute_id', $instituteId)->firstOrFail();
        }
    }

    public function chargesOnTransfer(): bool
    {
        return $this->on_route_transfer === 'full_charge';
    }

    public function noChargeOnTransfer(): bool
    {
        return $this->on_route_transfer === 'no_charge';
    }

    public function proratesOnTransfer(): bool
    {
        return $this->on_route_transfer === 'prorated_charge';
    }

    /**
     * Suggested amount to credit back for the unused remaining portion of a semester
     * allocation's charged fee, as of a given date (defaults to today). This is only a
     * starting suggestion for the staff member reviewing a route transfer or
     * cancellation — it is never applied automatically and can be freely overridden
     * before confirming.
     *
     * academic_sessions.start_date/end_date cannot be used as the period reference
     * here — a session row spans an entire academic year (every semester within it),
     * not a single semester — so semester_duration_months (configured per institute),
     * anchored on the allocation's own start_date, is used instead.
     */
    public function proratedUnusedAmount(TransportAllocation $allocation, ?Carbon $asOf = null): float
    {
        $allocStart = $allocation->start_date;
        if (!$allocStart) {
            return 0.0;
        }

        $outstanding = max(0.0, $allocation->balance);
        if ($outstanding <= 0) {
            return 0.0;
        }

        $asOf = ($asOf ?? Carbon::now())->copy()->startOfDay();
        $months = max(1, (int) $this->semester_duration_months);
        $semesterEnd = $allocStart->copy()->addMonths($months);

        $totalDays = max(1, $allocStart->diffInDays($semesterEnd));
        $usedDays = min($totalDays, max(0, $allocStart->diffInDays($asOf)));
        $remainingDays = max(0, $totalDays - $usedDays);

        $prorated = round(($remainingDays / $totalDays) * $allocation->effective_charged, 2);

        return min($outstanding, $prorated);
    }
}
