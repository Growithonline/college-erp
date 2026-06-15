<?php

namespace App\Services;

use App\Models\StaffAttendance;
use App\Models\AttendanceLockRecord;
use App\Models\StaffMember;
use Carbon\Carbon;

class AttendanceService
{
    /**
     * Mark attendance for staff
     */
    // Standard working hours used for auto-calculating late & overtime
    private const STANDARD_START = '09:00';
    private const STANDARD_END   = '17:00';

    public static function markAttendance(
        $instituteId,
        $staffId,
        $date,
        $status,
        $inTime = null,
        $outTime = null,
        $lateMinutes = 0,
        $overtimeHours = 0,
        $remarks = null,
        $markedBy = null
    ): StaffAttendance {
        $date = Carbon::parse($date)->toDateString();

        // Block future-date attendance
        if (Carbon::parse($date)->isAfter(Carbon::today())) {
            throw new \Exception("Future date ki attendance mark nahi ki ja sakti");
        }

        $staff = StaffMember::where('institute_id', $instituteId)->findOrFail($staffId);

        // Validate joining date
        if ($staff->joining_date && Carbon::parse($date)->isBefore($staff->joining_date)) {
            throw new \Exception("Cannot mark attendance before staff joining date");
        }

        // Check if month is locked
        $dateObj = Carbon::parse($date);
        if (self::isMonthLocked($staff->institute_id, $dateObj->year, $dateObj->month)) {
            throw new \Exception("Attendance for this month is locked");
        }

        // Validate out_time is after in_time
        if ($inTime && $outTime) {
            $in  = Carbon::createFromFormat('H:i', substr($inTime, 0, 5));
            $out = Carbon::createFromFormat('H:i', substr($outTime, 0, 5));
            if ($out->lte($in)) {
                throw new \Exception("Out time, in time se pehle ya barabar nahi ho sakta");
            }
        }

        // Auto-calculate late_minutes from in_time (ignores client-submitted value)
        if ($inTime) {
            $standardStart = Carbon::createFromFormat('H:i', self::STANDARD_START);
            $actualIn      = Carbon::createFromFormat('H:i', substr($inTime, 0, 5));
            $lateMinutes   = (int) max(0, $standardStart->diffInMinutes($actualIn, false));
        }

        // Auto-calculate overtime_hours from out_time (ignores client-submitted value)
        if ($outTime) {
            $standardEnd    = Carbon::createFromFormat('H:i', self::STANDARD_END);
            $actualOut      = Carbon::createFromFormat('H:i', substr($outTime, 0, 5));
            $overtimeMinutes = max(0, $standardEnd->diffInMinutes($actualOut, false));
            $overtimeHours   = round($overtimeMinutes / 60, 2);
        }

        // Create or update attendance
        return StaffAttendance::updateOrCreate(
            [
                'institute_id'    => $staff->institute_id,
                'staff_member_id' => $staffId,
                'attendance_date' => $date,
            ],
            [
                'staff_category_snapshot' => $staff->staff_category,
                'status'          => $status,
                'in_time'         => $inTime,
                'out_time'        => $outTime,
                'late_minutes'    => $lateMinutes,
                'overtime_hours'  => $overtimeHours,
                'remarks'         => $remarks,
                'marked_by'       => $markedBy,
            ]
        );
    }

    /**
     * Bulk mark attendance for multiple staff
     */
    public static function bulkMarkAttendance($instituteId, $date, $staffIds, $status, $markedBy = null): array
    {
        $date = Carbon::parse($date)->toDateString();
        $dateObj = Carbon::parse($date);

        if (self::isMonthLocked($instituteId, $dateObj->year, $dateObj->month)) {
            throw new \Exception("Attendance for this month is locked");
        }

        $count = 0;
        $failures = [];
        foreach ($staffIds as $staffId) {
            try {
                self::markAttendance($instituteId, $staffId, $date, $status, markedBy: $markedBy);
                $count++;
            } catch (\Exception $e) {
                $failures[] = ['staff_id' => $staffId, 'reason' => $e->getMessage()];
            }
        }

        return ['count' => $count, 'failures' => $failures];
    }

    /**
     * Get attendance for a specific date
     */
    public static function getAttendanceForDate($instituteId, $date, $category = null)
    {
        $query = StaffAttendance::where('institute_id', $instituteId)
            ->forDate($date);

        if ($category) {
            $query->forCategory($category);
        }

        return $query->with('staff', 'markedBy')->get();
    }

    /**
     * Get monthly attendance summary for a single staff member
     */
    public static function getMonthlyAttendanceSummary($instituteId, $staffId, $year, $month): array
    {
        $attendances = StaffAttendance::where('institute_id', $instituteId)
            ->forStaff($staffId)
            ->forMonth($year, $month)
            ->get();

        return self::buildSummary($staffId, $year, $month, $attendances);
    }

    /**
     * Get monthly summaries for multiple staff in one query (avoids N+1)
     */
    public static function getBulkAttendanceSummaries($instituteId, $year, $month, array $staffIds): array
    {
        $allAttendances = StaffAttendance::where('institute_id', $instituteId)
            ->forMonth($year, $month)
            ->whereIn('staff_member_id', $staffIds)
            ->get()
            ->groupBy('staff_member_id');

        $summaries = [];
        foreach ($staffIds as $staffId) {
            $summaries[$staffId] = self::buildSummary(
                $staffId, $year, $month,
                $allAttendances->get($staffId, collect())
            );
        }

        return $summaries;
    }

    /**
     * Get monthly summary for all staff in category
     */
    public static function getCategoryMonthlyAttendance($instituteId, $category, $year, $month): array
    {
        $query = StaffMember::where('institute_id', $instituteId)
            ->where('status', true);

        if ($category) {
            $query->where('staff_category', $category);
        }

        $staffMembers = $query->get();
        $staffIds = $staffMembers->pluck('id')->toArray();

        // Single query for all attendance records this month
        $allAttendances = StaffAttendance::where('institute_id', $instituteId)
            ->forMonth($year, $month)
            ->whereIn('staff_member_id', $staffIds)
            ->get()
            ->groupBy('staff_member_id');

        $summaries = [];
        foreach ($staffMembers as $staff) {
            $summaries[] = [
                'staff' => $staff,
                'summary' => self::buildSummary(
                    $staff->id, $year, $month,
                    $allAttendances->get($staff->id, collect())
                ),
            ];
        }

        return $summaries;
    }

    private static function buildSummary($staffId, $year, $month, $attendances): array
    {
        $summary = [
            'staff_id' => $staffId,
            'year' => $year,
            'month' => $month,
            'marked_days' => $attendances->count(),
            'present' => $attendances->where('status', 'Present')->count(),
            'absent' => $attendances->where('status', 'Absent')->count(),
            'half_day' => $attendances->where('status', 'Half Day')->count(),
            'paid_leave' => $attendances->where('status', 'Paid Leave')->count(),
            'unpaid_leave' => $attendances->where('status', 'Unpaid Leave')->count(),
            'holiday' => $attendances->where('status', 'Holiday')->count(),
            'week_off' => $attendances->where('status', 'Week Off')->count(),
            'total_overtime' => $attendances->sum('overtime_hours'),
            'total_late_minutes' => $attendances->sum('late_minutes'),
        ];

        $summary['payable_days'] = $summary['present'] + ($summary['half_day'] * 0.5) + $summary['paid_leave'];
        // worked_days = physically worked (present + half_day) — excludes paid_leave
        $summary['worked_days'] = $summary['present'] + ($summary['half_day'] * 0.5);

        return $summary;
    }

    public static function getMonthlyAttendanceRecords($instituteId, $staffId, $year, $month)
    {
        return StaffAttendance::where('institute_id', $instituteId)
            ->forStaff($staffId)
            ->forMonth($year, $month)
            ->orderBy('attendance_date')
            ->get();
    }

    /**
     * Lock attendance for a month
     */
    public static function lockMonth($instituteId, $year, $month, $reason = 'manual', $lockedBy = null, $remarks = null): AttendanceLockRecord
    {
        return AttendanceLockRecord::lockMonth($instituteId, $year, $month, $reason, $lockedBy, $remarks);
    }

    /**
     * Unlock attendance for a month
     */
    public static function unlockMonth($instituteId, $year, $month): bool
    {
        return (bool) AttendanceLockRecord::where('institute_id', $instituteId)
            ->forMonth($year, $month)
            ->delete();
    }

    /**
     * Check if month is locked
     */
    public static function isMonthLocked($instituteId, $year, $month): bool
    {
        return AttendanceLockRecord::isMonthLocked($instituteId, $year, $month);
    }

    /**
     * Validate attendance before editing
     */
    public static function canEditAttendance($attendance): bool
    {
        return !$attendance->isLocked();
    }

    /**
     * Get active staff for attendance marking
     */
    public static function getActiveStaff($instituteId, $category = null)
    {
        $query = StaffMember::where('institute_id', $instituteId)
            ->where('status', true);

        if ($category) {
            $query->where('staff_category', $category);
        }

        return $query->with('role')->get();
    }
}
