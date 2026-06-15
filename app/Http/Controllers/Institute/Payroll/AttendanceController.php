<?php

namespace App\Http\Controllers\Institute\Payroll;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use App\Models\StaffMember;
use App\Services\AttendanceService;
use App\Services\PayrollService; // used in lockMonth auto-draft
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Daily attendance view
     */
    public function daily(Request $request)
    {
        $instituteId = auth()->user()->institute_id;
        $date = $request->input('date', now()->toDateString());
        $category = $request->input('category');

        $date = Carbon::parse($date);

        // Get staff for the selected category
        $staff = AttendanceService::getActiveStaff($instituteId, $category);

        // Get existing attendance records for the date
        $attendance = StaffAttendance::where('institute_id', $instituteId)
            ->where('attendance_date', $date)
            ->get()
            ->keyBy('staff_member_id');

        $isLocked = AttendanceService::isMonthLocked($instituteId, $date->year, $date->month);

        return view('institute.payroll.attendance.daily', [
            'date'       => $date,
            'category'   => $category,
            'categories' => ['Teaching', 'Office', 'Non-Teaching', 'Guest'],
            'staff'      => $staff,
            'attendance' => $attendance,
            'isLocked'   => $isLocked,
        ]);
    }

    /**
     * Store attendance
     */
    public function store(Request $request)
    {
        $instituteId = auth()->user()->institute_id;

        $validated = $request->validate([
            'staff_id' => 'required|integer',
            'date'     => 'required|date|before_or_equal:today',
            'status'   => 'required|in:Present,Absent,Half Day,Paid Leave,Unpaid Leave,Holiday,Week Off',
            'in_time'  => 'nullable|date_format:H:i',
            'out_time' => 'nullable|date_format:H:i|after:in_time',
            'remarks'  => 'nullable|string|max:500',
        ]);

        try {
            $attendance = AttendanceService::markAttendance(
                instituteId: $instituteId,
                staffId: $validated['staff_id'],
                date: $validated['date'],
                status: $validated['status'],
                inTime: $validated['in_time'] ?? null,
                outTime: $validated['out_time'] ?? null,
                remarks: $validated['remarks'] ?? null,
                markedBy: auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Attendance marked successfully',
                'data' => $attendance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Bulk mark attendance
     */
    public function bulkMark(Request $request)
    {
        $instituteId = auth()->user()->institute_id;

        $validated = $request->validate([
            'date' => 'required|date',
            'staff_ids' => 'required|array|min:1',
            'staff_ids.*' => 'integer|exists:staff_members,id',
            'status' => 'required|in:Present,Absent,Half Day,Paid Leave,Unpaid Leave,Holiday,Week Off',
        ]);

        try {
            $result = AttendanceService::bulkMarkAttendance(
                $instituteId,
                $validated['date'],
                $validated['staff_ids'],
                $validated['status'],
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => "Attendance marked for {$result['count']} staff members",
                'count' => $result['count'],
                'failures' => $result['failures'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Monthly attendance summary
     */
    public function monthly(Request $request)
    {
        $instituteId = auth()->user()->institute_id;
        $staffId = $request->input('staff_id');
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $category = $request->input('category');

        $isLocked = AttendanceService::isMonthLocked($instituteId, $year, $month);

        if ($staffId) {
            // Single staff summary
            $staff = StaffMember::where('institute_id', $instituteId)->findOrFail($staffId);
            $summary = AttendanceService::getMonthlyAttendanceSummary($instituteId, $staffId, $year, $month);
            $attendances = AttendanceService::getMonthlyAttendanceRecords($instituteId, $staffId, $year, $month);

            $salaryEstimate = null;
            if ($staff->monthly_salary || $staff->daily_wage) {
                $salaryEstimate = PayrollService::calculateSalary($staff, $summary, $year, $month);
            }

            return view('institute.payroll.attendance.monthly-detail', [
                'summary'        => $summary,
                'staff'          => $staff,
                'attendances'    => $attendances,
                'year'           => $year,
                'month'          => $month,
                'isLocked'       => $isLocked,
                'salaryEstimate' => $salaryEstimate,
            ]);
        } else {
            // All staff in category summary
            $summaries = AttendanceService::getCategoryMonthlyAttendance($instituteId, $category, $year, $month);

            return view('institute.payroll.attendance.monthly', [
                'summaries'  => $summaries,
                'category'   => $category,
                'categories' => ['Teaching', 'Office', 'Non-Teaching', 'Guest'],
                'year'       => $year,
                'month'      => $month,
                'isLocked'   => $isLocked,
            ]);
        }
    }

    /**
     * Lock attendance month
     */
    public function lockMonth(Request $request)
    {
        $instituteId = auth()->user()->institute_id;

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'reason' => 'nullable|in:month_closed,salary_generated,manual',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            AttendanceService::lockMonth(
                $instituteId,
                $validated['year'],
                $validated['month'],
                $validated['reason'] ?? 'manual',
                auth()->id(),
                $validated['remarks'] ?? null
            );

            // Auto-generate salary draft after locking
            $draftResult = PayrollService::generateSalaryDraft($instituteId, $validated['year'], $validated['month']);
            $draftCount  = count($draftResult['records']);
            $warnings    = $draftResult['warnings'];

            return response()->json([
                'success'      => true,
                'message'      => "Attendance locked. Salary draft {$draftCount} staff ke liye auto-generate ho gaya.",
                'draft_count'  => $draftCount,
                'warnings'     => $warnings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Unlock attendance month
     */
    public function unlockMonth(Request $request)
    {
        $instituteId = auth()->user()->institute_id;

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $unlocked = AttendanceService::unlockMonth($instituteId, $validated['year'], $validated['month']);

        if (!$unlocked) {
            return response()->json([
                'success' => false,
                'message' => 'No lock record found for this month',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance unlocked for the month',
        ]);
    }
}
