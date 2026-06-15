<?php

namespace App\Http\Controllers\Institute\Payroll;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Student;
use App\Models\StudentAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StudentAttendanceController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    /**
     * Daily attendance marking
     */
    public function daily(Request $request)
    {
        $instituteId = $this->instituteId();
        $date        = Carbon::parse($request->input('date', now()->toDateString()));
        $sessionId   = $request->input('session_id');

        $sessions = AcademicSession::where('institute_id', $instituteId)->orderByDesc('is_active')->get();
        $activeSession = $sessions->firstWhere('is_active', true);
        $sessionId = $sessionId ?? $activeSession?->id;

        $students = Student::where('institute_id', $instituteId)
            ->where('status', 'active')
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->orderBy('name')
            ->get();

        $attendance = StudentAttendance::where('institute_id', $instituteId)
            ->where('attendance_date', $date->toDateString())
            ->get()
            ->keyBy('student_id');

        return view('institute.payroll.student-attendance.daily', compact(
            'date', 'students', 'attendance', 'sessions', 'sessionId'
        ));
    }

    /**
     * Mark or update a student's attendance
     */
    public function store(Request $request)
    {
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'date'       => 'required|date|before_or_equal:today',
            'status'     => 'required|in:Present,Absent,Half Day,Holiday,Week Off',
            'remarks'    => 'nullable|string|max:255',
        ]);

        Student::where('institute_id', $instituteId)->findOrFail((int) $validated['student_id']);

        StudentAttendance::updateOrCreate(
            [
                'institute_id' => $instituteId,
                'student_id'   => (int) $validated['student_id'],
                'attendance_date' => $validated['date'],
            ],
            [
                'status'    => $validated['status'],
                'remarks'   => $validated['remarks'] ?? null,
                'marked_by' => auth()->id(),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Attendance marked']);
    }

    /**
     * Bulk mark all students for a date
     */
    public function bulkMark(Request $request)
    {
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'date'        => 'required|date|before_or_equal:today',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:students,id',
            'status'      => 'required|in:Present,Absent,Half Day,Holiday,Week Off',
        ]);

        $count    = 0;
        $failures = [];

        foreach ($validated['student_ids'] as $studentId) {
            try {
                Student::where('institute_id', $instituteId)->findOrFail((int) $studentId);

                StudentAttendance::updateOrCreate(
                    [
                        'institute_id'    => $instituteId,
                        'student_id'      => (int) $studentId,
                        'attendance_date' => $validated['date'],
                    ],
                    [
                        'status'    => $validated['status'],
                        'marked_by' => auth()->id(),
                    ]
                );
                $count++;
            } catch (\Exception $e) {
                $failures[] = ['student_id' => $studentId, 'reason' => $e->getMessage()];
            }
        }

        return response()->json([
            'success'  => true,
            'message'  => "Attendance marked for {$count} students",
            'failures' => $failures,
        ]);
    }

    /**
     * Monthly attendance summary
     */
    public function monthly(Request $request)
    {
        $instituteId = $this->instituteId();
        $year        = (int) $request->input('year', now()->year);
        $month       = (int) $request->input('month', now()->month);
        $sessionId   = $request->input('session_id');
        $studentId   = $request->input('student_id');

        $sessions = AcademicSession::where('institute_id', $instituteId)->orderByDesc('is_active')->get();

        if ($studentId) {
            $student    = Student::where('institute_id', $instituteId)->findOrFail((int) $studentId);
            $records    = StudentAttendance::where('institute_id', $instituteId)
                ->forStudent($studentId)
                ->forMonth($year, $month)
                ->orderBy('attendance_date')
                ->get();
            $summary    = StudentAttendance::buildMonthlySummary($studentId, $year, $month, $records);

            return view('institute.payroll.student-attendance.monthly-detail', compact(
                'student', 'records', 'summary', 'year', 'month', 'sessions'
            ));
        }

        $students = Student::where('institute_id', $instituteId)
            ->where('status', 'active')
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->orderBy('name')
            ->get();

        $studentIds = $students->pluck('id')->toArray();

        $allRecords = StudentAttendance::where('institute_id', $instituteId)
            ->forMonth($year, $month)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->groupBy('student_id');

        $summaries = $students->map(fn($s) => [
            'student' => $s,
            'summary' => StudentAttendance::buildMonthlySummary(
                $s->id, $year, $month,
                $allRecords->get($s->id, collect())
            ),
        ]);

        return view('institute.payroll.student-attendance.monthly', compact(
            'summaries', 'year', 'month', 'sessions', 'sessionId'
        ));
    }
}
