<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstituteTransportSetting extends Model
{
    protected $fillable = [
        'institute_id',
        'on_route_transfer',
        'prorated_billing',
        'yearly_fee_cross_session',
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
                    'on_route_transfer'        => 'full_charge',
                    'prorated_billing'         => 'disabled',
                    'yearly_fee_cross_session' => true,
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

    // Calculate prorated fee for a monthly allocation starting mid-month
    public function calculateProratedFee(float $fullFee, string $startDate, string $chargeMonth): float
    {
        if ($this->prorated_billing === 'disabled') {
            return $fullFee;
        }

        $start        = \Carbon\Carbon::parse($startDate);
        $monthStart   = \Carbon\Carbon::parse($chargeMonth . '-01');
        $monthEnd     = $monthStart->copy()->endOfMonth();

        // Only prorate if start date is within this charge month
        if (!$start->between($monthStart, $monthEnd)) {
            return $fullFee;
        }

        if ($this->prorated_billing === 'after_midmonth') {
            return $start->day > 15 ? round($fullFee / 2, 2) : $fullFee;
        }

        // daily_basis: (remaining days including start / total days) × fee
        $totalDays     = $monthEnd->day;
        $remainingDays = $totalDays - $start->day + 1;

        return round(($remainingDays / $totalDays) * $fullFee, 2);
    }
}
