<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    protected $table = 'student_attendance';

    protected $fillable = [
        'institute_id', 'student_id', 'academic_session_id',
        'attendance_date', 'status', 'remarks', 'marked_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public const STATUSES = [
        'Present'  => 'Present',
        'Absent'   => 'Absent',
        'Half Day' => 'Half Day',
        'Holiday'  => 'Holiday',
        'Week Off' => 'Week Off',
    ];

    // Relationships
    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
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

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    // Build monthly summary array for a student
    public static function buildMonthlySummary(int $studentId, int $year, int $month, $records): array
    {
        $totalDays = \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $marked    = $records->count();
        $present   = $records->where('status', 'Present')->count();
        $absent    = $records->where('status', 'Absent')->count();
        $halfDay   = $records->where('status', 'Half Day')->count();
        $holiday   = $records->where('status', 'Holiday')->count();
        $weekOff   = $records->where('status', 'Week Off')->count();

        $workingDays = $totalDays - $holiday - $weekOff;
        $effectiveDays = $present + ($halfDay * 0.5);
        $percentage = $workingDays > 0 ? round(($effectiveDays / $workingDays) * 100, 1) : 0;

        return [
            'student_id'    => $studentId,
            'year'          => $year,
            'month'         => $month,
            'total_days'    => $totalDays,
            'marked_days'   => $marked,
            'present'       => $present,
            'absent'        => $absent,
            'half_day'      => $halfDay,
            'holiday'       => $holiday,
            'week_off'      => $weekOff,
            'working_days'  => $workingDays,
            'attended_days' => $effectiveDays,
            'percentage'    => $percentage,
            'is_shortage'   => $percentage < 75,
        ];
    }
}
