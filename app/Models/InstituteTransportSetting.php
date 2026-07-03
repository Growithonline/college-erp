<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstituteTransportSetting extends Model
{
    protected $fillable = [
        'institute_id',
        'on_route_transfer',
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

}
