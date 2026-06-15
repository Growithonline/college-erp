<?php

namespace App\Http\Controllers\Institute\Admission;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CoursePart;
use App\Models\CourseStreamSubject;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\StudentTransaction;
use App\Models\StudentWallet;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentPromoteController extends Controller
{
    private function instituteId(): int
    {
        foreach (['web', 'staff'] as $guard) {
            $user = auth()->guard($guard)->user();
            if ($user && $user->institute_id) {
                return (int) $user->institute_id;
            }
        }
        abort(403, 'Not authenticated');
    }

    public function index(Request $request)
    {
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->first();

        $sessions = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('is_active')->orderBy('name')->get();

        $nextSessions = AcademicSession::where('institute_id', $instituteId)
            ->orderBy('name')->get();

        $courses = Course::where('institute_id', $instituteId)
            ->where('status', true)->orderBy('name')->get();

        $selectedSession = $request->session_id ?? $activeSession?->id;
        $selectedCourse  = $request->course_id;
        $perPage = in_array($request->integer('per_page', 20), [10,20,50,100])
                 ? $request->integer('per_page', 20) : 20;

        $query = Student::with(['stream.course', 'session', 'coursePart'])
            ->where('institute_id', $instituteId)
            ->where('status', 'active');

        if ($selectedSession) $query->where('academic_session_id', $selectedSession);
        if ($selectedCourse)  $query->whereHas('stream', fn($q) => $q->where('course_id', $selectedCourse));

        if ($request->search) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name','like',"%$s%")
                ->orWhere('mobile','like',"%$s%")
                ->orWhere('student_uid','like',"%$s%"));
        }

        $students = $query->orderBy('name')->paginate($perPage)->withQueryString();

        $walletData = [];
        if ($selectedSession) {
            $wallets = StudentWallet::whereIn('student_id', $students->pluck('id'))
                ->where('academic_session_id', $selectedSession)
                ->get()->keyBy('student_id');
            foreach ($students->pluck('id') as $sid) {
                $w = $wallets->get($sid);
                $walletData[$sid] = [
                    'balance'   => $w ? (float)$w->main_b : 0.00,
                    'total_due' => $w ? abs(min(0,(float)$w->main_b)) : 0.00,
                ];
            }
        }

        $promoteBase = '/admissions/promote';

        return view('institute.admission.promote.index', compact(
            'students','sessions','nextSessions','courses',
            'activeSession','selectedSession','selectedCourse',
            'walletData','perPage','promoteBase'
        ));
    }

    public function preview(Request $request)
    {
        $instituteId = $this->instituteId();
        $validated = $request->validate([
            'student_id'     => 'required|exists:students,id',
            'new_session_id' => 'required|exists:academic_sessions,id',
            'new_part_id'    => 'required|exists:course_parts,id',
        ]);

        $student    = Student::with(['stream.course','session','coursePart'])
            ->where('institute_id', $instituteId)->findOrFail($validated['student_id']);
        $newSession = AcademicSession::findOrFail($validated['new_session_id']);
        $newPart    = CoursePart::findOrFail($validated['new_part_id']);

        $oldWallet = StudentWallet::where('student_id', $student->id)
            ->where('academic_session_id', $student->academic_session_id)->first();
        $oldDue = $oldWallet ? abs(min(0,(float)$oldWallet->main_b)) : 0;

        $newSubjects = CourseStreamSubject::with('subject')
            ->where('course_stream_id', $student->course_stream_id)
            ->where('year_number', $newPart->year_number)
            ->where('is_active', true)->get();

        return response()->json([
            'success'      => true,
            'student'      => [
                'name'        => $student->name,
                'student_uid' => $student->student_uid,
                'course'      => $student->stream->course->name ?? '',
                'stream'      => $student->stream->name ?? '',
                'old_part'    => $student->coursePart?->part_name ?? 'Year 1',
                'old_session' => $student->session->name ?? '',
            ],
            'new_session'  => $newSession->name,
            'new_part'     => $newPart->part_name,
            'old_due'      => $oldDue,
            'new_subjects' => $newSubjects->map(fn($s) => [
                'name' => $s->subject->name ?? '',
                'role' => ucfirst($s->subject_role),
            ]),
        ]);
    }

    public function promote(Request $request)
    {
        $instituteId = $this->instituteId();
        $validated = $request->validate([
            'student_id'     => 'required|exists:students,id',
            'new_session_id' => 'required|exists:academic_sessions,id',
            'new_part_id'    => 'required|exists:course_parts,id',
        ]);

        $student    = Student::with(['stream.course','coursePart','session'])
            ->where('institute_id', $instituteId)->findOrFail($validated['student_id']);
        $newSession = AcademicSession::findOrFail($validated['new_session_id']);
        $newPart    = CoursePart::findOrFail($validated['new_part_id']);

        DB::transaction(function () use ($student, $newSession, $newPart, $instituteId) {

            $oldSessionId = $student->academic_session_id;

            $oldWallet = StudentWallet::where('student_id', $student->id)
                ->where('academic_session_id', $oldSessionId)->first();
            $oldDue = $oldWallet ? abs(min(0,(float)$oldWallet->main_b)) : 0;

            $newWallet = StudentWallet::firstOrCreate(
                ['student_id' => $student->id, 'academic_session_id' => $newSession->id],
                ['institute_id' => $instituteId, 'main_b' => 0.00]
            );

            if ($oldDue > 0) {
                $opBal = $newWallet->main_b;
                $clBal = $opBal - $oldDue;
                StudentTransaction::create([
                    'student_id'          => $student->id,
                    'institute_id'        => $instituteId,
                    'academic_session_id' => $newSession->id,
                    'des'                 => 'Previous Due (' . ($student->session->name ?? 'Last Session') . ')',
                    'credit'              => 0.00,
                    'debit'               => $oldDue,
                    'type'                => StudentTransaction::DEBIT,
                    'date'                => now()->toDateString(),
                    'op_bal'              => $opBal,
                    'cl_bal'              => $clBal,
                    'by_user_id'          => auth()->id(),
                ]);
                $newWallet->main_b = $clBal;
                $newWallet->save();
            }

            $student->update([
                'academic_session_id' => $newSession->id,
                'course_part_id'      => $newPart->id,
            ]);
            $student->refresh();
            $student->load(['stream.course','coursePart','session']);

            $newSubjects = CourseStreamSubject::where('course_stream_id', $student->course_stream_id)
                ->where('year_number', $newPart->year_number)
                ->where('is_active', true)->get();

            foreach ($newSubjects as $cs) {
                StudentSubject::updateOrCreate(
                    [
                        'student_id'          => $student->id,
                        'subject_id'          => $cs->subject_id,
                        'academic_session_id' => $newSession->id,
                        'year_number'         => $newPart->year_number,
                    ],
                    [
                        'subject_role'     => $cs->subject_role === 'both' ? 'minor' : $cs->subject_role,
                        'is_auto_included' => !$cs->is_chooseable,
                    ]
                );
            }

            WalletService::onAdmission($student);
        });

        return response()->json([
            'success' => true,
            'message' => "{$student->name} promoted to {$newSession->name} — {$newPart->part_name}!",
        ]);
    }

    public function bulkPromote(Request $request)
    {
        $instituteId = $this->instituteId();
        $validated = $request->validate([
            'student_ids'    => 'required|array|min:1',
            'student_ids.*'  => 'exists:students,id',
            'new_session_id' => 'required|exists:academic_sessions,id',
            'new_part_id'    => 'required|exists:course_parts,id',
        ]);

        $success = 0;
        $errors  = [];

        foreach ($validated['student_ids'] as $sid) {
            try {
                $fakeReq = new Request([
                    'student_id'     => $sid,
                    'new_session_id' => $validated['new_session_id'],
                    'new_part_id'    => $validated['new_part_id'],
                ]);
                $this->promote($fakeReq);
                $success++;
            } catch (\Exception $e) {
                $errors[] = "#{$sid}: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$success} students promoted." . (count($errors) ? " " . count($errors) . " failed." : ""),
            'errors'  => $errors,
        ]);
    }

    public function getParts(Request $request)
    {
        $streamId = $request->integer('stream_id');
        $stream   = \App\Models\CourseStream::with('course')->find($streamId);

        if (!$stream) {
            return response()->json(['parts' => []]);
        }

        $parts = CoursePart::where('course_id', $stream->course_id)
            ->where('status', true)
            ->orderBy('part_number')
            ->get(['id', 'part_name', 'part_number', 'year_number']);

        return response()->json(['parts' => $parts]);
    }
}