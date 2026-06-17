<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseStream;
use App\Models\CourseStreamSubject;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CourseSubjectController extends Controller
{
    // ── Authorization helper ──────────────────────────────────────────────
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    private function authorizeStream(CourseStream $stream): void
    {
        abort_if(
            $stream->course->institute_id !== $this->instituteId(),
            403,
            'Unauthorized access to this stream.'
        );
    }

    // ── Index — show subjects for a stream ───────────────────────────────
    public function index(CourseStream $stream): \Illuminate\View\View
    {
        $this->authorizeStream($stream);

        $stream->load(['course', 'yearRules']);

        // Subjects grouped by year — no raw SQL, Eloquent only
        $subjectsByYear = CourseStreamSubject::with(['subject.components'])
            ->where('course_stream_id', $stream->id)
            ->orderBy('year_number')
            ->orderBy('sort_order')
            ->orderBy('subject_role')
            ->get()
            ->groupBy('year_number');

        // All available subjects for this institute (for add form)
        $availableSubjects = Subject::where('institute_id', $this->instituteId())
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'has_practical']);

        // Already mapped subject+year combinations
        // Format: ["subject_id_year" => true] e.g. ["1_1" => true, "1_2" => true]
        // Same subject alag years mein allowed hai — isliye year bhi include karo
        $mappedSubjectYears = CourseStreamSubject::where('course_stream_id', $stream->id)
            ->get(['subject_id', 'year_number'])
            ->mapWithKeys(fn($m) => [$m->subject_id . '_' . $m->year_number => true])
            ->toArray();

        $years = range(1, $stream->course->duration ?? 3);
        $roles = CourseStreamSubject::roles();

        return view('institute.master.courses.subjects.index', compact(
            'stream', 'subjectsByYear', 'availableSubjects',
            'mappedSubjectYears', 'years', 'roles'
        ));
    }

    // ── Store — add a single subject to stream ───────────────────────────
    public function store(Request $request, CourseStream $stream): RedirectResponse
    {
        $this->authorizeStream($stream);

        // Strict validation — SQL injection protection via Laravel validation
        $validated = $request->validate([
            'subject_id'   => [
                'required',
                'integer',
                // Only subjects from THIS institute allowed — prevents injection
                Rule::exists('subjects', 'id')->where('institute_id', $this->instituteId()),
            ],
            'year_number'  => [
                'required',
                'integer',
                'min:1',
                'max:' . ($stream->course->duration ?? 10),
            ],
            'subject_role' => [
                'required',
                Rule::in(array_keys(CourseStreamSubject::roles())),
            ],
            'is_chooseable' => 'boolean',
            'sort_order'    => 'nullable|integer|min:0|max:999',
        ]);

        // Check duplicate — same subject+year already exists?
        $exists = CourseStreamSubject::where('course_stream_id', $stream->id)
            ->where('subject_id',  $validated['subject_id'])
            ->where('year_number', $validated['year_number'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'subject_id' => 'This subject is already mapped for this year in this stream.',
            ]);
        }

        CourseStreamSubject::create([
            'course_stream_id' => $stream->id,
            'subject_id'       => $validated['subject_id'],
            'year_number'      => $validated['year_number'],
            'subject_role'     => $validated['subject_role'],
            'is_chooseable'    => $request->boolean('is_chooseable', true),
            'sort_order'       => $validated['sort_order'] ?? 0,
            'is_active'        => true,
        ]);

        return redirect()
            ->route('master.streams.subjects.index', $stream)
            ->with('success', 'Subject mapped successfully!');
    }

    // ── Bulk Store — save full year's subjects at once ───────────────────
    public function bulkStore(Request $request, CourseStream $stream): RedirectResponse
    {
        $this->authorizeStream($stream);

        $validated = $request->validate([
            'year_number'            => [
                'required', 'integer', 'min:1',
                'max:' . ($stream->course->duration ?? 10),
            ],
            'subjects'               => 'required|array|min:1|max:50',
            'subjects.*.subject_id'  => [
                'required',
                'integer',
                Rule::exists('subjects', 'id')->where('institute_id', $this->instituteId()),
            ],
            'subjects.*.subject_role' => [
                'required',
                Rule::in(array_keys(CourseStreamSubject::roles())),
            ],
            'subjects.*.is_chooseable' => 'boolean',
            'subjects.*.sort_order'    => 'nullable|integer|min:0|max:999',
        ]);

        $yearNumber = (int) $validated['year_number'];

        DB::transaction(function () use ($stream, $yearNumber, $validated) {
            foreach ($validated['subjects'] as $idx => $item) {
                CourseStreamSubject::updateOrCreate(
                    [
                        'course_stream_id' => $stream->id,
                        'subject_id'       => (int) $item['subject_id'],
                        'year_number'      => $yearNumber,
                    ],
                    [
                        'subject_role'  => $item['subject_role'],
                        'is_chooseable' => isset($item['is_chooseable'])
                            ? (bool) $item['is_chooseable']
                            : true,
                        'sort_order'    => isset($item['sort_order'])
                            ? (int) $item['sort_order']
                            : $idx,
                        'is_active'     => true,
                    ]
                );
            }
        });

        return redirect()
            ->route('master.streams.subjects.index', $stream)
            ->with('success', "Year {$yearNumber} subjects saved!");
    }

    // ── Update — change role/order of a mapped subject ───────────────────
    public function update(Request $request, CourseStream $stream, CourseStreamSubject $mapping): RedirectResponse
    {
        $this->authorizeStream($stream);

        // Ensure mapping belongs to this stream
        abort_if($mapping->course_stream_id !== $stream->id, 403);

        $validated = $request->validate([
            'subject_role'  => ['required', Rule::in(array_keys(CourseStreamSubject::roles()))],
            'is_chooseable' => 'boolean',
            'sort_order'    => 'nullable|integer|min:0|max:999',
            'is_active'     => 'boolean',
        ]);

        $mapping->update([
            'subject_role'  => $validated['subject_role'],
            'is_chooseable' => $request->boolean('is_chooseable', $mapping->is_chooseable),
            'sort_order'    => $validated['sort_order'] ?? $mapping->sort_order,
            'is_active'     => $request->boolean('is_active', $mapping->is_active),
        ]);

        return redirect()
            ->route('master.streams.subjects.index', $stream)
            ->with('success', 'Subject mapping updated!');
    }

    // ── Destroy — remove subject from stream ─────────────────────────────
    public function destroy(CourseStream $stream, CourseStreamSubject $mapping): RedirectResponse
    {
        $this->authorizeStream($stream);
        abort_if($mapping->course_stream_id !== $stream->id, 403);

        $mapping->delete();

        return redirect()
            ->route('master.streams.subjects.index', $stream)
            ->with('success', 'Subject removed from stream.');
    }

    // ── Toggle active status ──────────────────────────────────────────────
    public function toggle(CourseStream $stream, CourseStreamSubject $mapping): RedirectResponse
    {
        $this->authorizeStream($stream);
        abort_if($mapping->course_stream_id !== $stream->id, 403);

        $mapping->update(['is_active' => ! $mapping->is_active]);

        return back()->with('success', 'Status updated!');
    }

    // ── AJAX — get subjects for a stream+year (used in admission form) ────
    public function getSubjectsForAdmission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stream_id'   => [
                'required',
                'integer',
                // Only streams from this institute
                Rule::exists('course_streams', 'id')
                    ->whereIn('course_id', function ($q) {
                        $q->select('id')
                          ->from('courses')
                          ->where('institute_id', $this->instituteId());
                    }),
            ],
            'year_number' => 'required|integer|min:1|max:10',
        ]);

        $subjects = CourseStreamSubject::with(['subject:id,name,code,has_practical'])
            ->where('course_stream_id', (int) $validated['stream_id'])
            ->where('year_number',      (int) $validated['year_number'])
            ->where('is_active',        true)
            ->orderBy('sort_order')
            ->orderBy('subject_role')
            ->get()
            ->map(fn ($m) => [
                'id'             => $m->id,
                'subject_id'     => $m->subject_id,
                'name'           => $m->subject->name ?? '',
                'code'           => $m->subject->code ?? '',
                'has_practical'  => (bool) ($m->subject->has_practical ?? false),
                'subject_role'   => $m->subject_role,
                'role_label'     => $m->role_label,
                'is_chooseable'  => $m->is_chooseable,
            ]);

        // Group by role
        $grouped = [
            'major'      => $subjects->where('subject_role', 'major')->values(),
            'minor'      => $subjects->where('subject_role', 'minor')->values(),
            'compulsory' => $subjects->where('subject_role', 'compulsory')->values(),
            'optional'   => $subjects->where('subject_role', 'optional')->values(),
        ];

        // Stream year rules (min/max major & minor count)
        $yearRule = \App\Models\StreamYearSubjectRule::where('course_stream_id', (int) $validated['stream_id'])
            ->where('year_number', (int) $validated['year_number'])
            ->first();

        return response()->json([
            'success'   => true,
            'grouped'   => $grouped,
            'year_rule' => $yearRule ? [
                'major_min' => $yearRule->major_min,
                'major_max' => $yearRule->major_max,
                'minor_min' => $yearRule->minor_optional_min,
                'minor_max' => $yearRule->minor_optional_max,
            ] : null,
        ]);
    }
}