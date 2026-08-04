<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseStream;
use App\Models\StreamYearSubjectRule;
use Illuminate\Http\Request;

class CourseStreamController extends Controller
{
    public function index(Course $course)
    {
        $this->authorizeCourse($course);

        $streams = $course->streams()
            ->withCount([
                'yearRules',
                'streamSubjects as total_subjects_count' => fn($q) => $q->where('is_active', true),
            ])
            ->get();

        return view('institute.master.courses.streams.index', compact('course', 'streams'));
    }

    public function create(Course $course)
    {
        $this->authorizeCourse($course);
        return view('institute.master.courses.streams.create', compact('course'));
    }

    public function store(Request $request, Course $course)
    {
        $this->authorizeCourse($course);

        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20',
        ]);

        $stream = CourseStream::create([
            'course_id' => $course->id,
            'name'      => strtoupper($request->name),
            'code'      => strtoupper($request->code),
            'status'    => true,
        ]);

        $this->autoCreateYearRules($stream, $course);

        return redirect()->route('master.courses.streams.index', $course)
            ->with('success', "Stream '{$stream->name}' created! Year rules auto-generated.");
    }

    public function edit(CourseStream $stream)
    {
        $this->authorizeStream($stream);
        $stream->load('course');
        return view('institute.master.courses.streams.create', compact('stream') + ['course' => $stream->course]);
    }

    public function update(Request $request, CourseStream $stream)
    {
        $this->authorizeStream($stream);

        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20',
        ]);

        $stream->update([
            'name' => strtoupper($request->name),
            'code' => strtoupper($request->code),
        ]);

        return redirect()->route('master.courses.streams.index', $stream->course)
            ->with('success', "Stream '{$stream->name}' updated!");
    }

    // ── Set seat limit for current session ───────────────────────────
    // Also used to set the optional, separate Lateral Entry quota (admission_type=lateral) —
    // same form/action, just an extra flag. Leaving admission_type off keeps setting the
    // general pooled limit exactly as before.
    public function setLimit(Request $request)
    {
        $request->validate([
            'stream_id'     => 'required|exists:course_streams,id',
            'student_limit' => 'required|integer|min:1|max:9999',
            'admission_type'=> ['nullable', 'in:lateral'],
        ]);

        $stream = CourseStream::findOrFail($request->stream_id);
        $this->authorizeStream($stream);

        $instituteId   = auth()->user()->institute_id;
        $activeSession = \App\Models\AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->firstOrFail();

        $admissionType = $request->input('admission_type') ?: 'all';

        \App\Models\StreamSessionLimit::updateOrCreate(
            [
                'course_stream_id'    => $stream->id,
                'academic_session_id' => $activeSession->id,
                'admission_type'      => $admissionType,
            ],
            ['student_limit' => $request->student_limit]
        );

        $label = $admissionType === 'lateral' ? 'Lateral Entry seat limit' : 'Seat limit';

        return redirect()->back()
            ->with('success', "{$label} of {$request->student_limit} set for {$stream->name} — {$activeSession->name}.");
    }
    // Lateral Entry seats are an OPTIONAL, separate quota. If a stream+session never
    // has a 'lateral' row configured, lateral admissions are checked against (and
    // counted within) the same general pool as everyone else — identical to the
    // behaviour before this quota existed. Only once an institute explicitly sets a
    // lateral-specific limit do lateral admissions move to their own pool, and get
    // excluded from the general pool's filled-count so the two quotas don't double-count.
    // $lock=true must only be called from inside an open DB::transaction(), right before
    // the student insert — the lockForUpdate() takes an InnoDB gap lock on the matching
    // row range so a concurrent admission for the same stream/session can't also pass
    // this check before either transaction commits (closes the seat-overbooking race).
    public static function checkSeatAvailability(int $streamId, int $sessionId, ?string $admissionType = null, bool $lock = false): array
    {
        $limits = \App\Models\StreamSessionLimit::where('course_stream_id', $streamId)
            ->where('academic_session_id', $sessionId)
            ->get()
            ->keyBy('admission_type');

        $lateralLimit = $limits->get('lateral');

        if ($admissionType === 'lateral' && $lateralLimit) {
            $limit  = (int) $lateralLimit->student_limit;
            $filledQuery = \App\Models\Student::where('course_stream_id', $streamId)
                ->where('academic_session_id', $sessionId)
                ->where('admission_type', 'lateral')
                ->where('status', '!=', 'cancelled');
            if ($lock) {
                $filledQuery->lockForUpdate();
            }
            $filled = $filledQuery->count();

            return [
                'available' => ($limit - $filled) > 0,
                'limit'     => $limit,
                'filled'    => $filled,
                'remaining' => $limit - $filled,
            ];
        }

        $generalLimit = $limits->get('all');
        if (!$generalLimit) {
            return ['available' => true, 'limit' => null, 'filled' => 0, 'remaining' => null];
        }

        $limit       = (int) $generalLimit->student_limit;
        $filledQuery = \App\Models\Student::where('course_stream_id', $streamId)
            ->where('academic_session_id', $sessionId)
            ->where('status', '!=', 'cancelled');

        if ($lateralLimit) {
            $filledQuery->where('admission_type', '!=', 'lateral');
        }

        if ($lock) {
            $filledQuery->lockForUpdate();
        }

        $filled = $filledQuery->count();

        return [
            'available' => ($limit - $filled) > 0,
            'limit'     => $limit,
            'filled'    => $filled,
            'remaining' => $limit - $filled,
        ];
    }

    public function destroy(CourseStream $stream)
    {
        $this->authorizeStream($stream);
        $courseId = $stream->course_id;
        $stream->delete();
        return redirect()->route('master.courses.streams.index', $courseId)
            ->with('success', 'Stream deleted!');
    }

    // Year rules page
    public function yearRules(CourseStream $stream)
    {
        $this->authorizeStream($stream);
        $stream->load('course', 'yearRules');
        return view('institute.master.courses.streams.year-rules', compact('stream'));
    }

    public function saveYearRules(Request $request, CourseStream $stream)
    {
        $this->authorizeStream($stream);

        $request->validate([
            'rules'                      => 'required|array',
            'rules.*.year_number'        => 'required|integer|min:1',
            'rules.*.minor_optional_min' => 'required|integer|min:0',
            'rules.*.minor_optional_max' => 'required|integer|min:0',
            'rules.*.major_min'          => 'required|integer|min:0',
            'rules.*.major_max'          => 'required|integer|min:0',
        ]);

        foreach ($request->rules as $rule) {
            StreamYearSubjectRule::updateOrCreate(
                [
                    'course_stream_id' => $stream->id,
                    'year_number'      => $rule['year_number'],
                ],
                [
                    'minor_optional_min' => $rule['minor_optional_min'],
                    'minor_optional_max' => $rule['minor_optional_max'],
                    'major_min'          => $rule['major_min'],
                    'major_max'          => $rule['major_max'],
                ]
            );
        }

        return redirect()->route('master.streams.year-rules', $stream)
            ->with('success', 'Year rules saved!');
    }

    private function autoCreateYearRules(CourseStream $stream, Course $course): void
    {
        $years = $course->duration_type === 'year'
            ? $course->duration
            : max(1, (int) ceil($course->duration / 12));

        $isUG = $course->type && in_array(strtoupper($course->type->name), ['UG', 'UNDERGRADUATE']);
        $isPG = $course->type && in_array(strtoupper($course->type->name), ['PG', 'POSTGRADUATE']);

        for ($y = 1; $y <= $years; $y++) {
            $isLastYear = ($y === $years);

            if ($isPG) {
                $min = $isLastYear ? 0 : 1;
                $max = $isLastYear ? 0 : 1;
            } elseif ($isUG && $isLastYear) {
                $min = 1; $max = 1;
            } else {
                $min = 2; $max = 2;
            }

            StreamYearSubjectRule::create([
                'course_stream_id'   => $stream->id,
                'year_number'        => $y,
                'minor_optional_min' => $min,
                'minor_optional_max' => $max,
                'major_min'          => 1,
                'major_max'          => 3,
            ]);
        }
    }

    private function authorizeCourse(Course $course): void
    {
        abort_if($course->institute_id !== auth()->user()->institute_id, 403);
    }

    private function authorizeStream(CourseStream $stream): void
    {
        abort_if($stream->course->institute_id !== auth()->user()->institute_id, 403);
    }
}