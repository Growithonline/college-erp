<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class StaffAttendance extends Model
{
    protected $table = 'staff_attendance';

    protected $fillable = [
        'institute_id',
        'staff_member_id',
        'attendance_date',
        'staff_category_snapshot',
        'status',
        'in_time',
        'out_time',
        'late_minutes',
        'overtime_hours',
        'remarks',
        'marked_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'in_time' => 'datetime:H:i',
        'out_time' => 'datetime:H:i',
        'late_minutes' => 'integer',
        'overtime_hours' => 'decimal:2',
    ];

    public const STATUSES = [
        'Present' => 'Present',
        'Absent' => 'Absent',
        'Half Day' => 'Half Day',
        'Paid Leave' => 'Paid Leave',
        'Unpaid Leave' => 'Unpaid Leave',
        'Holiday' => 'Holiday',
        'Week Off' => 'Week Off',
    ];

    public const LEAVE_STATUSES = ['Paid Leave', 'Unpaid Leave'];
    public const NON_WORKING_STATUSES = ['Holiday', 'Week Off'];

    // Relationships
    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function staff()
    {
        return $this->belongsTo(StaffMember::class, 'staff_member_id');
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    // Scopes
    public function scopeForDate($query, $date)
    {
        return $query->where('attendance_date', $date);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month);
    }

    public function scopeForStaff($query, $staffId)
    {
        return $query->where('staff_member_id', $staffId);
    }

    public function scopeForCategory($query, $category)
    {
        return $query->where('staff_category_snapshot', $category);
    }

    public function scopePresent($query)
    {
        return $query->where('status', 'Present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'Absent');
    }

    public function scopeLeave($query)
    {
        return $query->whereIn('status', self::LEAVE_STATUSES);
    }

    public function scopePaidLeave($query)
    {
        return $query->where('status', 'Paid Leave');
    }

    public function scopeUnpaidLeave($query)
    {
        return $query->where('status', 'Unpaid Leave');
    }

    // Helpers
    public function isWorkingDay(): bool
    {
        return !in_array($this->status, array_merge(self::NON_WORKING_STATUSES, ['Absent', 'Unpaid Leave']));
    }

    public function isPaidDay(): bool
    {
        return !in_array($this->status, ['Unpaid Leave', 'Absent']);
    }

    public function isLocked(): bool
    {
        static $cache = [];
        $key = "{$this->institute_id}-{$this->attendance_date->year}-{$this->attendance_date->month}";
        if (!array_key_exists($key, $cache)) {
            $cache[$key] = AttendanceLockRecord::where('institute_id', $this->institute_id)
                ->where('lock_year', $this->attendance_date->year)
                ->where('lock_month', $this->attendance_date->month)
                ->exists();
        }
        return $cache[$key];
    }
}
