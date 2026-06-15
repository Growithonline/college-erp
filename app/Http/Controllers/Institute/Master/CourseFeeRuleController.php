<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseFeeRule;
use App\Models\FeeType;
use App\Models\StudentType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseFeeRuleController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    // ── List ─────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->first();

        $sessionId = $request->integer('session_id') ?: $activeSession?->id;

        $courses  = Course::where('institute_id', $instituteId)
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'duration', 'duration_type']);

        $sessions = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $rules          = collect();
        $selectedCourse = null;
        $editRule       = null;

        // Edit mode — query param se rule load karo
        if ($request->filled('edit_id')) {
            $editRule = CourseFeeRule::where('institute_id', $instituteId)
                ->findOrFail($request->integer('edit_id'));
        }

        if ($request->filled('course_id')) {
            $selectedCourse = Course::where('institute_id', $instituteId)
                ->findOrFail($request->integer('course_id'));

            $rules = CourseFeeRule::with(['feeType'])
                ->where('institute_id',        $instituteId)
                ->where('academic_session_id', $sessionId)
                ->where('course_id',           $selectedCourse->id)
                ->where('is_active',           true)
                ->orderBy('course_part')
                ->orderBy('semester')
                ->orderBy('fee_type_id')
                ->get();
        }

        $feeTypes = FeeType::where(function ($q) use ($instituteId) {
            $q->where('institute_id', $instituteId)->orWhere('is_system', true);
        })->where('is_active', true)->orderBy('name')->get();

        $studentTypes = StudentType::forInstitute($instituteId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('institute.master.fee.course-fee-rules.index', compact(
            'courses', 'sessions', 'rules', 'selectedCourse',
            'sessionId', 'activeSession', 'feeTypes', 'editRule', 'studentTypes'
        ));
    }

    // ── Store ─────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $instituteId  = $this->instituteId();
        $validSlugs   = StudentType::forInstitute($instituteId)->pluck('slug')->push('all')->toArray();

        $validated = $request->validate([
            'course_id'           => ['required', Rule::exists('courses', 'id')->where('institute_id', $instituteId)],
            'academic_session_id' => ['required', Rule::exists('academic_sessions', 'id')->where('institute_id', $instituteId)],
            'fee_type_id'         => ['required', Rule::exists('fee_types', 'id')->where(fn($q) => $q->where('institute_id', $instituteId)->orWhere('is_system', true))],
            'course_part'         => 'required|integer|min:0|max:6',
            'semester'            => 'required|integer|min:0|max:2',
            'student_type'        => ['required', Rule::in($validSlugs)],
            'admission_source'    => ['required', Rule::in(['direct', 'center', 'channel_partner', 'all'])],
            'category'            => ['required', Rule::in(['general', 'obc', 'sc', 'st', 'all'])],
            'gender'              => ['required', Rule::in(['male', 'female', 'other', 'all'])],
            'amount'              => 'required|numeric|min:0|max:999999',
            'remarks'             => 'nullable|string|max:255',
        ]);

        CourseFeeRule::updateOrCreate(
            [
                'institute_id'        => $instituteId,
                'academic_session_id' => $validated['academic_session_id'],
                'course_id'           => $validated['course_id'],
                'fee_type_id'         => $validated['fee_type_id'],
                'course_part'         => $validated['course_part'],
                'semester'            => $validated['semester'],
                'student_type'        => $validated['student_type'],
                'admission_source'    => $validated['admission_source'],
                'category'            => $validated['category'],
                'gender'              => $validated['gender'],
            ],
            [
                'amount'    => $validated['amount'],
                'remarks'   => $validated['remarks'] ?? null,
                'is_active' => true,
            ]
        );

        return redirect()
            ->route('master.fee-structure.course-fees', [
                'course_id'  => $validated['course_id'],
                'session_id' => $validated['academic_session_id'],
            ])
            ->with('success', 'Fee rule saved!');
    }

    // ── Update (amount + remarks only) ────────────────────────────────────
    public function update(Request $request, CourseFeeRule $courseFeeRule)
    {
        abort_if($courseFeeRule->institute_id !== $this->instituteId(), 403);

        $validated = $request->validate([
            'amount'  => 'required|numeric|min:0|max:999999',
            'remarks' => 'nullable|string|max:255',
        ]);

        $courseFeeRule->update([
            'amount'  => $validated['amount'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect()
            ->route('master.fee-structure.course-fees', [
                'course_id'  => $courseFeeRule->course_id,
                'session_id' => $courseFeeRule->academic_session_id,
            ])
            ->with('success', 'Fee rule updated!');
    }

    // ── Delete ────────────────────────────────────────────────────────────
    public function destroy(CourseFeeRule $courseFeeRule)
    {
        abort_if($courseFeeRule->institute_id !== $this->instituteId(), 403);

        $courseId  = $courseFeeRule->course_id;
        $sessionId = $courseFeeRule->academic_session_id;
        $courseFeeRule->delete();

        return redirect()
            ->route('master.fee-structure.course-fees', [
                'course_id'  => $courseId,
                'session_id' => $sessionId,
            ])
            ->with('success', 'Fee rule deleted.');
    }
}