<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\SalaryRecord;
use App\Models\StaffAttendance;
use App\Models\StaffMember;
use App\Services\AttendanceService;
use App\Services\PayrollService;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffPayrollController extends Controller
{
    private function staff()
    {
        return Auth::guard('staff')->user();
    }

    private function permCheck(string $perm): void
    {
        if (!$this->staff()->hasPermission($perm)) {
            abort(403, 'Permission denied.');
        }
    }

    private function instituteId(): int
    {
        return $this->staff()->institute_id;
    }

    private function ensureAllowedPayrollCategory(?string $category): void
    {
        if (!$category) {
            return;
        }

        abort_if(!$this->staff()->canAccessPayrollCategory($category), 403, 'This staff category is outside your payroll scope.');
    }

    // ── Daily Attendance ──────────────────────────────────────────────
    public function daily(Request $request)
    {
        if (!$this->staff()->canViewAttendance()) {
            abort(403, 'Permission denied.');
        }

        $instituteId = $this->instituteId();
        $date = Carbon::parse($request->input('date', now()->toDateString()));
        $category = $request->input('category');
        $this->ensureAllowedPayrollCategory($category);

        $staffList = AttendanceService::getActiveStaff($instituteId, $category);
        $staffList = $this->staff()->scopePayrollStaff($staffList->toQuery())->with('role')->get();

        $attendance = StaffAttendance::where('institute_id', $instituteId)
            ->where('attendance_date', $date)->get()->keyBy('staff_member_id');

        $isLocked = AttendanceService::isMonthLocked($instituteId, $date->year, $date->month);

        return view('institute.payroll.attendance.daily', [
            'date'       => $date,
            'category'   => $category,
            'categories' => ['Teaching', 'Office', 'Non-Teaching', 'Guest'],
            'staff'      => $staffList,
            'attendance' => $attendance,
            'isLocked'   => $isLocked,
            'layout'     => 'staff.layout',
            'rp'         => 'staff.finance',
        ]);
    }

    public function store(Request $request)
    {
        $this->permCheck('attendance_mark');
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'staff_id' => 'required|integer',
            'date'     => 'required|date|before_or_equal:today',
            'status'   => 'required|in:Present,Absent,Half Day,Paid Leave,Unpaid Leave,Holiday,Week Off',
            'in_time'  => 'nullable|date_format:H:i',
            'out_time' => 'nullable|date_format:H:i|after:in_time',
            'remarks'  => 'nullable|string|max:500',
        ]);

        try {
            $targetStaff = StaffMember::where('institute_id', $instituteId)->findOrFail((int) $validated['staff_id']);
            $this->ensureAllowedPayrollCategory($targetStaff->staff_category);
            // marked_by references users table; staff authenticate via staff_members — store null to avoid FK violation.
            // Audit trail is preserved via AuditLogService below.
            $attendance = AttendanceService::markAttendance(
                instituteId: $instituteId,
                staffId:     $validated['staff_id'],
                date:        $validated['date'],
                status:      $validated['status'],
                inTime:      $validated['in_time'] ?? null,
                outTime:     $validated['out_time'] ?? null,
                remarks:     $validated['remarks'] ?? null,
                markedBy:    null
            );

            AuditLogService::log($instituteId, 'payroll', 'attendance_marked', 'Staff attendance marked.', $attendance, [
                'staff_member_id' => $targetStaff->id,
                'attendance_date' => $validated['date'],
                'status' => $validated['status'],
            ]);

            return response()->json(['success' => true, 'message' => 'Attendance marked', 'data' => $attendance]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function bulkMark(Request $request)
    {
        if (!$this->staff()->hasAnyPermission(['attendance_mark', 'attendance_bulk_mark'])) {
            abort(403, 'Permission denied.');
        }

        $instituteId = $this->instituteId();
        $validated = $request->validate([
            'date'        => 'required|date',
            'staff_ids'   => 'required|array|min:1',
            'staff_ids.*' => 'integer|exists:staff_members,id',
            'status'      => 'required|in:Present,Absent,Half Day,Paid Leave,Unpaid Leave,Holiday,Week Off',
        ]);

        try {
            $allowedStaffIds = StaffMember::where('institute_id', $instituteId)
                ->whereIn('id', $validated['staff_ids'])
                ->get()
                ->filter(fn (StaffMember $member) => $this->staff()->canAccessPayrollCategory($member->staff_category))
                ->pluck('id')
                ->all();

            // marked_by references users table; staff authenticate via staff_members — store null
            $result = AttendanceService::bulkMarkAttendance(
                $instituteId, $validated['date'], $allowedStaffIds, $validated['status'], null
            );
            AuditLogService::log($instituteId, 'payroll', 'attendance_bulk_marked', 'Bulk attendance marked.', null, [
                'attendance_date' => $validated['date'],
                'status' => $validated['status'],
                'count' => $result['count'],
            ]);
            return response()->json([
                'success'  => true,
                'message'  => "Attendance marked for {$result['count']} staff members",
                'count'    => $result['count'],
                'failures' => $result['failures'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function monthly(Request $request)
    {
        if (!$this->staff()->canViewAttendance()) {
            abort(403, 'Permission denied.');
        }

        $instituteId = $this->instituteId();
        $staffId = $request->input('staff_id');
        $year    = $request->input('year', now()->year);
        $month   = $request->input('month', now()->month);
        $category = $request->input('category');
        $this->ensureAllowedPayrollCategory($category);

        $layout = 'staff.layout';
        $rp     = 'staff.finance';

        $isLocked = AttendanceService::isMonthLocked($instituteId, $year, $month);

        if ($staffId) {
            $staff       = StaffMember::where('institute_id', $instituteId)->findOrFail($staffId);
            $this->ensureAllowedPayrollCategory($staff->staff_category);
            $summary     = AttendanceService::getMonthlyAttendanceSummary($instituteId, $staffId, $year, $month);
            $attendances = AttendanceService::getMonthlyAttendanceRecords($instituteId, $staffId, $year, $month);

            $salaryEstimate = null;
            if ($staff->monthly_salary || $staff->daily_wage) {
                $salaryEstimate = PayrollService::calculateSalary($staff, $summary, $year, $month);
            }

            return view('institute.payroll.attendance.monthly-detail', compact(
                'summary', 'staff', 'attendances', 'year', 'month', 'layout', 'isLocked', 'salaryEstimate'
            ));
        }

        $summaries = AttendanceService::getCategoryMonthlyAttendance($instituteId, $category, $year, $month);
        $summaries = collect($summaries)
            ->filter(fn ($row) => $this->staff()->canAccessPayrollCategory($row['staff']->staff_category))
            ->values()
            ->all();

        return view('institute.payroll.attendance.monthly', [
            'summaries'  => $summaries,
            'category'   => $category,
            'categories' => ['Teaching', 'Office', 'Non-Teaching', 'Guest'],
            'year'       => $year,
            'month'      => $month,
            'isLocked'   => $isLocked,
            'layout'     => $layout,
            'rp'         => $rp,
        ]);
    }

    public function lockMonth(Request $request)
    {
        $this->permCheck('attendance_lock');
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'year'    => 'required|integer|min:2020|max:2100',
            'month'   => 'required|integer|min:1|max:12',
            'reason'  => 'nullable|in:month_closed,salary_generated,manual',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            // lockedBy must reference users table — staff_members IDs are not valid here, so null
            AttendanceService::lockMonth(
                $instituteId,
                $validated['year'],
                $validated['month'],
                $validated['reason'] ?? 'manual',
                null,
                $validated['remarks'] ?? null
            );
            AuditLogService::log($instituteId, 'payroll', 'attendance_month_locked', 'Attendance month locked.', null, [
                'year' => $validated['year'],
                'month' => $validated['month'],
                'reason' => $validated['reason'] ?? 'manual',
            ]);
            return response()->json(['success' => true, 'message' => 'Month locked successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function unlockMonth(Request $request)
    {
        $this->permCheck('attendance_lock');
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'year'  => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $unlocked = AttendanceService::unlockMonth($instituteId, $validated['year'], $validated['month']);

        if (!$unlocked) {
            return response()->json(['success' => false, 'message' => 'No lock record found for this month'], 404);
        }

        AuditLogService::log($instituteId, 'payroll', 'attendance_month_unlocked', 'Attendance month unlocked.', null, [
            'year' => $validated['year'],
            'month' => $validated['month'],
        ]);
        return response()->json(['success' => true, 'message' => 'Attendance unlocked for the month']);
    }

    // ── Payroll ───────────────────────────────────────────────────────
    public function generateDraft(Request $request)
    {
        $this->permCheck('payroll_generate');
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'year'     => 'required|integer|min:2020|max:2100',
            'month'    => 'required|integer|min:1|max:12',
            'category' => 'nullable|in:Teaching,Office,Non-Teaching,Guest',
        ]);
        $this->ensureAllowedPayrollCategory($validated['category'] ?? null);

        try {
            $result = PayrollService::generateSalaryDraft(
                $instituteId, $validated['year'], $validated['month'], $validated['category'] ?? null
            );
            if ($this->staff()->hasRestrictedPayrollCategories()) {
                $result['records'] = collect($result['records'])
                    ->filter(fn ($record) => $this->staff()->canAccessPayrollCategory($record->staffMember?->staff_category))
                    ->values()
                    ->all();
            }
            $count = count($result['records']);
            AuditLogService::log($instituteId, 'payroll', 'salary_draft_generated', 'Payroll draft generated.', null, [
                'year' => $validated['year'],
                'month' => $validated['month'],
                'category' => $validated['category'] ?? null,
                'count' => $count,
            ]);
            return response()->json([
                'success'  => true,
                'message'  => "Salary draft generated for {$count} staff members",
                'count'    => $count,
                'warnings' => $result['warnings'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function draftView(Request $request)
    {
        if (!$this->staff()->canGeneratePayroll() && !$this->staff()->canApprovePayroll()) {
            abort(403, 'Permission denied.');
        }

        $instituteId = $this->instituteId();
        $year     = $request->input('year', now()->year);
        $month    = $request->input('month', now()->month);
        $category = $request->input('category');
        $this->ensureAllowedPayrollCategory($category);

        // Only show actionable records in draft view (exclude paid/reversed)
        $actionableStatuses = [
            SalaryRecord::STATUS_DRAFT,
            SalaryRecord::STATUS_APPROVED,
            SalaryRecord::STATUS_PENDING,
        ];
        $summary = PayrollService::getPayrollSummary($instituteId, $year, $month, $category, $actionableStatuses);
        if ($this->staff()->hasRestrictedPayrollCategories()) {
            $summary['records'] = $summary['records']
                ->filter(fn ($record) => $this->staff()->canAccessPayrollCategory($record->staffMember?->staff_category))
                ->values();
            $summary['total_records'] = $summary['records']->count();
            $summary['draft_count'] = $summary['records']->where('status', SalaryRecord::STATUS_DRAFT)->count();
            $summary['approved_count'] = $summary['records']->where('status', SalaryRecord::STATUS_APPROVED)->count();
            $summary['pending_count'] = $summary['records']->where('status', SalaryRecord::STATUS_PENDING)->count();
            $summary['paid_count'] = $summary['records']->where('status', SalaryRecord::STATUS_PAID)->count();
            $summary['reversed_count'] = $summary['records']->where('status', SalaryRecord::STATUS_REVERSED)->count();
            $summary['total_basic'] = round($summary['records']->sum('basic_salary'), 2);
            $summary['total_allowances'] = round($summary['records']->sum('allowances'), 2);
            $summary['total_deductions'] = round($summary['records']->sum('deductions'), 2);
            $summary['total_net_payable'] = round($summary['records']->sum('net_payable'), 2);
            $summary['total_paid'] = round($summary['records']->where('status', SalaryRecord::STATUS_PAID)->sum('paid_amount'), 2);
        }

        $staffIds = $summary['records']->pluck('staff_member_id')->toArray();
        $attendanceSummaries = [];
        if (!empty($staffIds)) {
            $attendanceSummaries = AttendanceService::getBulkAttendanceSummaries(
                $instituteId, $year, $month, $staffIds
            );
        }

        return view('institute.payroll.payroll.draft', [
            'summary'             => $summary,
            'attendanceSummaries' => $attendanceSummaries,
            'year'                => $year,
            'month'               => $month,
            'category'            => $category,
            'categories'          => ['Teaching', 'Office', 'Non-Teaching', 'Guest'],
            'layout'              => 'staff.layout',
            'rp'                  => 'staff.finance',
        ]);
    }

    public function approveSalary(Request $request, SalaryRecord $salaryRecord)
    {
        $this->permCheck('payroll_approve');
        abort_if($salaryRecord->institute_id !== $this->instituteId(), 403);
        $salaryRecord->loadMissing('staffMember');
        $this->ensureAllowedPayrollCategory($salaryRecord->staffMember?->staff_category);

        try {
            PayrollService::approveSalary($salaryRecord);
            AuditLogService::log($this->instituteId(), 'payroll', 'salary_approved', 'Salary approved.', $salaryRecord, [
                'staff_member_id' => $salaryRecord->staff_member_id,
                'period' => sprintf('%04d-%02d', $salaryRecord->salary_year, $salaryRecord->salary_month),
            ]);
            return response()->json(['success' => true, 'message' => 'Salary approved']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
