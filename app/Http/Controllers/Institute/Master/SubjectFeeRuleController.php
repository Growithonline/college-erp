<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Subject;
use App\Models\SubjectFeeRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SubjectFeeRuleController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    // ── Index — Bulk entry table ──────────────────────────────────────────
    public function index(Request $request)
    {
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::viewSession($instituteId);

        $sessionId  = $request->integer('session_id') ?: $activeSession?->id;
        $coursePart = $request->integer('course_part') ?: null;
        $semester   = $request->filled('semester') ? $request->integer('semester') : null;

        $courses  = Course::where('institute_id', $instituteId)
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'duration', 'duration_type']);

        $sessions = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $rules          = collect();
        $subjects       = collect();
        $selectedCourse = null;

        if ($request->filled('course_id')) {
            $selectedCourse = Course::where('institute_id', $instituteId)
                ->findOrFail($request->integer('course_id'));

            if ($coursePart) {
                $subjects = Subject::whereIn('id', function ($q) use ($selectedCourse, $coursePart) {
                    $q->select('subject_id')
                      ->from('course_stream_subjects')
                      ->whereIn('course_stream_id', function ($sq) use ($selectedCourse) {
                          $sq->select('id')->from('course_streams')
                             ->where('course_id', $selectedCourse->id)
                             ->where('status', true);
                      })
                      ->where('year_number', $coursePart)
                      ->where('is_active', true);
                })
                ->where('status', true)
                ->orderBy('name')
                ->get();
            }

            $rulesQuery = SubjectFeeRule::with(['subject'])
                ->where('institute_id',        $instituteId)
                ->where('academic_session_id', $sessionId)
                ->where('course_id',           $selectedCourse->id)
                ->where('is_active',           true);

            if ($coursePart) $rulesQuery->where('course_part', $coursePart);
            if ($semester !== null) $rulesQuery->where('semester', $semester);

            $rules = $rulesQuery->orderBy('course_part')
                ->orderBy('semester')
                ->orderBy('subject_id')
                ->get();
        }

        return view('institute.master.fee.subject-fee-rules.index', compact(
            'courses', 'sessions', 'rules', 'subjects', 'selectedCourse',
            'sessionId', 'activeSession', 'coursePart', 'semester'
        ));
    }

    // ── Summary — All subject fee rules list with edit/delete ─────────────
    public function summary(Request $request)
    {
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::viewSession($instituteId);

        $sessionId = $request->integer('session_id') ?: $activeSession?->id;

        $courses  = Course::where('institute_id', $instituteId)
            ->where('status', true)->orderBy('name')
            ->get(['id', 'name', 'code']);

        $sessions = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('is_active')->orderBy('name')->get();

        $rulesQuery = SubjectFeeRule::with(['subject', 'course', 'session'])
            ->where('institute_id',        $instituteId)
            ->where('academic_session_id', $sessionId)
            ->where('is_active',           true);

        if ($request->filled('course_id')) {
            $rulesQuery->where('course_id', $request->integer('course_id'));
        }
        if ($request->filled('course_part')) {
            $rulesQuery->where('course_part', $request->integer('course_part'));
        }
        if ($request->filled('semester')) {
            $rulesQuery->where('semester', $request->integer('semester'));
        }

        $perPage  = $request->integer('per_page', 20);
        $perPage  = in_array($perPage, [10, 20, 50, 100]) ? $perPage : 20;

        $allRules = $rulesQuery->orderBy('course_id')
            ->orderBy('course_part')
            ->orderBy('semester')
            ->orderBy('subject_id')
            ->paginate($perPage)
            ->withQueryString();

        $editRule = null;
        if ($request->filled('edit_id')) {
            $editRule = SubjectFeeRule::where('institute_id', $instituteId)
                ->findOrFail($request->integer('edit_id'));
        }

        // Total stats (full query without pagination)
        $statsQuery = clone $rulesQuery;
        $totalRules        = $allRules->total();
        $totalSubjectFee   = SubjectFeeRule::where('institute_id', $instituteId)
            ->where('academic_session_id', $sessionId)->where('is_active', true)
            ->sum('subject_fee');
        $totalPracticalFee = SubjectFeeRule::where('institute_id', $instituteId)
            ->where('academic_session_id', $sessionId)->where('is_active', true)
            ->sum('practical_fee');

        return view('institute.master.fee.subject-fee-rules.summary', compact(
            'courses', 'sessions', 'allRules', 'sessionId', 'activeSession',
            'totalRules', 'totalSubjectFee', 'totalPracticalFee', 'editRule', 'perPage'
        ));
    }

    // ── Store single ──────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'course_id'           => ['required', Rule::exists('courses', 'id')->where('institute_id', $instituteId)],
            'academic_session_id' => ['required', Rule::exists('academic_sessions', 'id')->where('institute_id', $instituteId)],
            'subject_id'          => ['required', Rule::exists('subjects', 'id')->where('institute_id', $instituteId)],
            'course_part'         => 'required|integer|min:1|max:6',
            'semester'            => 'required|integer|min:0|max:12',
            'subject_fee'         => 'required|numeric|min:0|max:999999',
            'practical_fee'       => 'nullable|numeric|min:0|max:999999',
        ]);

        SubjectFeeRule::updateOrCreate(
            [
                'institute_id'        => $instituteId,
                'academic_session_id' => $validated['academic_session_id'],
                'course_id'           => $validated['course_id'],
                'subject_id'          => $validated['subject_id'],
                'course_part'         => $validated['course_part'],
                'semester'            => $validated['semester'],
            ],
            [
                'subject_fee'   => $validated['subject_fee'],
                'practical_fee' => $validated['practical_fee'] ?? 0,
                'is_active'     => true,
            ]
        );

        return back()->with('success', 'Subject fee rule saved!');
    }

    // ── Bulk Store ────────────────────────────────────────────────────────
    public function bulkStore(Request $request)
    {
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'academic_session_id'  => ['required', Rule::exists('academic_sessions', 'id')->where('institute_id', $instituteId)],
            'course_id'            => ['required', Rule::exists('courses', 'id')->where('institute_id', $instituteId)],
            'course_part'          => 'required|integer|min:1|max:6',
            'semester'             => 'required|integer|min:0|max:12',
            'fees'                 => 'required|array|min:1|max:100',
            'fees.*.subject_id'    => ['required', Rule::exists('subjects', 'id')->where('institute_id', $instituteId)],
            'fees.*.subject_fee'   => 'required|numeric|min:0|max:999999',
            'fees.*.practical_fee' => 'nullable|numeric|min:0|max:999999',
        ]);

        DB::transaction(function () use ($validated, $instituteId) {
            foreach ($validated['fees'] as $fee) {
                SubjectFeeRule::updateOrCreate(
                    [
                        'institute_id'        => $instituteId,
                        'academic_session_id' => $validated['academic_session_id'],
                        'course_id'           => $validated['course_id'],
                        'subject_id'          => (int) $fee['subject_id'],
                        'course_part'         => $validated['course_part'],
                        'semester'            => $validated['semester'],
                    ],
                    [
                        'subject_fee'   => (float) $fee['subject_fee'],
                        'practical_fee' => (float) ($fee['practical_fee'] ?? 0),
                        'is_active'     => true,
                    ]
                );
            }
        });

        return redirect()
            ->route('master.fee-structure.subject-fees.summary', [
                'session_id'  => $validated['academic_session_id'],
                'course_id'   => $validated['course_id'],
                'course_part' => $validated['course_part'],
                'semester'    => $validated['semester'],
            ])
            ->with('success', count($validated['fees']) . ' subject fee rules saved!');
    }

    // ── Update single rule ────────────────────────────────────────────────
    public function update(Request $request, SubjectFeeRule $subjectFeeRule)
    {
        abort_if($subjectFeeRule->institute_id !== $this->instituteId(), 403);

        $validated = $request->validate([
            'subject_fee'   => 'required|numeric|min:0|max:999999',
            'practical_fee' => 'nullable|numeric|min:0|max:999999',
        ]);

        $subjectFeeRule->update([
            'subject_fee'   => $validated['subject_fee'],
            'practical_fee' => $validated['practical_fee'] ?? 0,
        ]);

        return redirect()
            ->route('master.fee-structure.subject-fees.summary', [
                'session_id'  => $subjectFeeRule->academic_session_id,
                'course_id'   => $subjectFeeRule->course_id,
                'course_part' => $subjectFeeRule->course_part,
                'semester'    => $subjectFeeRule->semester,
            ])
            ->with('success', 'Subject fee updated!');
    }

    // ── Delete ────────────────────────────────────────────────────────────
    public function destroy(SubjectFeeRule $subjectFeeRule)
    {
        abort_if($subjectFeeRule->institute_id !== $this->instituteId(), 403);

        $sessionId  = $subjectFeeRule->academic_session_id;
        $courseId   = $subjectFeeRule->course_id;
        $coursePart = $subjectFeeRule->course_part;
        $semester   = $subjectFeeRule->semester;

        $subjectFeeRule->delete();

        return redirect()
            ->route('master.fee-structure.subject-fees.summary', [
                'session_id'  => $sessionId,
                'course_id'   => $courseId,
                'course_part' => $coursePart,
                'semester'    => $semester,
            ])
            ->with('success', 'Subject fee rule deleted.');
    }

    // ── AJAX ──────────────────────────────────────────────────────────────
    public function getSubjectFees(Request $request): JsonResponse
    {
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'session_id'    => ['required', Rule::exists('academic_sessions', 'id')->where('institute_id', $instituteId)],
            'course_id'     => ['required', Rule::exists('courses', 'id')->where('institute_id', $instituteId)],
            'course_part'   => 'required|integer|min:1|max:6',
            'semester'      => 'required|integer|min:0|max:12',
            'subject_ids'   => 'required|array',
            'subject_ids.*' => ['integer', Rule::exists('subjects', 'id')->where('institute_id', $instituteId)],
        ]);

        $rules = SubjectFeeRule::with('subject:id,name,has_practical')
            ->where('institute_id',        $instituteId)
            ->where('academic_session_id', $validated['session_id'])
            ->where('course_id',           $validated['course_id'])
            ->where('course_part',         $validated['course_part'])
            ->where(fn($q) =>
                $q->where('semester', $validated['semester'])
                  ->orWhere('semester', 0)
            )
            ->where('is_active', true)
            ->whereIn('subject_id', $validated['subject_ids'])
            ->get();

        return response()->json([
            'success' => true,
            'fees'    => $rules->map(fn($r) => [
                'subject_id'    => $r->subject_id,
                'subject_name'  => $r->subject->name ?? '',
                'has_practical' => (bool) ($r->subject->has_practical ?? false),
                'subject_fee'   => (float) $r->subject_fee,
                'practical_fee' => (float) $r->practical_fee,
                'total'         => (float) $r->total_fee,
            ]),
        ]);
    }
}