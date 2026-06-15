<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class AttendanceLockRecord extends Model
{
    protected $table = 'attendance_lock_records';

    protected $fillable = [
        'institute_id',
        'lock_year',
        'lock_month',
        'lock_reason',
        'locked_by',
        'lock_remarks',
    ];

    protected $casts = [
        'lock_year' => 'integer',
        'lock_month' => 'integer',
    ];

    public const LOCK_REASONS = [
        'month_closed' => 'Month Closed',
        'salary_generated' => 'Salary Generated',
        'manual' => 'Manual Lock',
    ];

    // Relationships
    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function lockedByUser()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    // Scopes
    public function scopeForMonth($query, $year, $month)
    {
        return $query->where('lock_year', $year)
            ->where('lock_month', $month);
    }

    public function scopeForInstitute($query, $instituteId)
    {
        return $query->where('institute_id', $instituteId);
    }

    public function scopeByReason($query, $reason)
    {
        return $query->where('lock_reason', $reason);
    }

    // Helpers
    public static function isMonthLocked($instituteId, $year, $month): bool
    {
        return self::where('institute_id', $instituteId)
            ->forMonth($year, $month)
            ->exists();
    }

    public static function lockMonth($instituteId, $year, $month, $reason = 'manual', $lockedBy = null, $remarks = null): self
    {
        return self::updateOrCreate(
            [
                'institute_id' => $instituteId,
                'lock_year' => $year,
                'lock_month' => $month,
            ],
            [
                'lock_reason' => $reason,
                'locked_by' => $lockedBy,
                'lock_remarks' => $remarks,
            ]
        );
    }
}
