<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CoursePart;
use App\Models\CourseStream;
use App\Models\CourseType;
use App\Models\DocumentBatch;
use App\Models\DocumentBatchStudent;
use App\Models\Student;
use App\Models\StudentAcademicIdentity;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentBatchController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    public function index(Request $request)
    {
        $instituteId = $this->instituteId();

        $sessions    = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $courseTypes = CourseType::forInstitute($instituteId)->active()->orderBy('sort_order')->orderBy('name')->get();

        $courseTypeId = $request->integer('course_type_id') ?: null;
        $courseId     = $request->integer('course_id') ?: null;

        $courses = Course::where('institute_id', $instituteId)
            ->when($courseTypeId, fn ($q) => $q->where('course_type_id', $courseTypeId))
            ->orderBy('name')
            ->get();

        $streams = CourseStream::whereHas('course', fn ($q) => $q->where('institute_id', $instituteId))
            ->when($courseId, fn ($q) => $q->where('course_id', $courseId))
            ->orderBy('name')
            ->get();

        $courseParts = CoursePart::whereHas('course', fn ($q) => $q->where('institute_id', $instituteId))
            ->when($courseId, fn ($q) => $q->where('course_id', $courseId))
            ->orderBy('part_number')
            ->get();

        $query = DocumentBatch::where('institute_id', $instituteId)
            ->with(['session', 'course', 'courseStream', 'coursePart'])
            ->withCount([
                'students',
                'students as found_count' => fn ($q) => $q->whereNotNull('found_at'),
                'students as distributed_count' => fn ($q) => $q->whereNotNull('distributed_at'),
            ])
            ->orderByDesc('id');

        if ($request->filled('session_id')) {
            $query->where('academic_session_id', (int) $request->session_id);
        }
        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }
        if ($courseTypeId) {
            $query->whereHas('course', fn ($q) => $q->where('course_type_id', $courseTypeId));
        }
        if ($courseId) {
            $query->where('course_id', $courseId);
        }
        if ($request->filled('course_stream_id')) {
            $query->where('course_stream_id', (int) $request->course_stream_id);
        }
        if ($request->filled('course_part_id')) {
            $query->where('course_part_id', (int) $request->course_part_id);
        }
        if ($request->filled('status')) {
            match ($request->status) {
                'pending'    => $query->whereNull('dispatch_date'),
                'dispatched' => $query->whereNotNull('dispatch_date')->whereNull('received_date'),
                'received'   => $query->whereNotNull('received_date'),
                default      => null,
            };
        }

        $batches = $query->paginate(20)->withQueryString();

        return view('institute.master.document-batches.index', compact(
            'batches', 'sessions', 'courseTypes', 'courses', 'streams', 'courseParts'
        ));
    }

    public function create(Request $request)
    {
        $instituteId = $this->instituteId();

        $sessions    = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $courseTypes = CourseType::forInstitute($instituteId)->active()->orderBy('sort_order')->orderBy('name')->get();

        $sessionId    = $request->integer('session_id') ?: null;
        $documentType = $request->input('document_type', 'marksheet');
        $courseTypeId = $request->integer('course_type_id') ?: null;
        $courseId     = $request->integer('course_id') ?: null;
        $streamId     = $request->integer('course_stream_id') ?: null;
        $coursePartId = $request->integer('course_part_id') ?: null;

        $courses = $courseTypeId
            ? Course::where('institute_id', $instituteId)->where('course_type_id', $courseTypeId)->orderBy('name')->get()
            : collect();

        $streams = $courseId
            ? CourseStream::where('course_id', $courseId)->orderBy('name')->get()
            : collect();

        $courseParts = $courseId
            ? CoursePart::where('course_id', $courseId)->orderBy('part_number')->get()
            : collect();

        $students = collect();

        $withSubjects = ['session', 'subjects' => fn ($q) => $q->wherePivot('academic_session_id', $sessionId)];

        if ($documentType === 'degree' && $sessionId && $courseId && $streamId) {
            // Degree = whole course completed — only students who have actually passed out,
            // scoped to the specific stream (different streams' degrees can arrive separately).
            $students = Student::where('institute_id', $instituteId)
                ->where('academic_session_id', $sessionId)
                ->where('status', 'passed_out')
                ->where('course_stream_id', $streamId)
                ->with($withSubjects)
                ->orderBy('name')
                ->get();
        } elseif ($documentType === 'marksheet' && $sessionId && $courseId && $streamId && $coursePartId) {
            // Marksheet = one specific semester/year's exam, for one specific stream — anyone
            // who was enrolled in that part+stream during that session, regardless of what
            // happened to them since (promoted, backlog, even later passed out). Live student
            // rows won't reflect this anymore once time has passed, so we look at the
            // academic-identity history instead.
            $studentIds = StudentAcademicIdentity::where('institute_id', $instituteId)
                ->where('academic_session_id', $sessionId)
                ->where('course_id', $courseId)
                ->where('course_stream_id', $streamId)
                ->where('course_part_id', $coursePartId)
                ->realOnly()
                ->distinct()
                ->pluck('student_id');

            $students = Student::where('institute_id', $instituteId)
                ->whereIn('id', $studentIds)
                ->with($withSubjects)
                ->orderBy('name')
                ->get();
        }

        // Due is computed live (not stored), so it has to be resolved per student here.
        $students->each(function (Student $student) {
            $student->totalDue = WalletService::getStudentSummary($student, (int) $student->academic_session_id)['total_due'] ?? 0;
        });

        return view('institute.master.document-batches.create', compact(
            'sessions', 'courseTypes', 'courses', 'streams', 'courseParts', 'students',
            'sessionId', 'documentType', 'courseTypeId', 'courseId', 'streamId', 'coursePartId'
        ));
    }

    public function store(Request $request)
    {
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'course_id'           => 'required|exists:courses,id',
            'course_stream_id'    => 'required|exists:course_streams,id',
            'course_part_id'      => 'nullable|required_if:document_type,marksheet|exists:course_parts,id',
            'document_type'       => ['required', Rule::in(array_keys(DocumentBatch::$documentTypes))],
            'batch_label'         => 'nullable|string|max:100',
            'student_ids'         => 'required|array|min:1',
            'student_ids.*'       => 'exists:students,id',
        ]);

        AcademicSession::where('id', $validated['academic_session_id'])->where('institute_id', $instituteId)->firstOrFail();
        Course::where('id', $validated['course_id'])->where('institute_id', $instituteId)->firstOrFail();
        CourseStream::where('id', $validated['course_stream_id'])->where('course_id', $validated['course_id'])->firstOrFail();

        $coursePartId = null;
        if ($validated['document_type'] === 'marksheet') {
            $coursePartId = CoursePart::where('id', $validated['course_part_id'])
                ->where('course_id', $validated['course_id'])
                ->firstOrFail()->id;
        }

        $studentIds = Student::where('institute_id', $instituteId)
            ->whereIn('id', $validated['student_ids'])
            ->pluck('id');

        $batch = DocumentBatch::create([
            'institute_id'        => $instituteId,
            'academic_session_id' => $validated['academic_session_id'],
            'course_id'           => $validated['course_id'],
            'course_stream_id'    => $validated['course_stream_id'],
            'course_part_id'      => $coursePartId,
            'document_type'       => $validated['document_type'],
            'batch_label'         => $validated['batch_label'] ?? null,
        ]);

        $batch->students()->createMany(
            $studentIds->map(fn ($id) => ['student_id' => $id])->all()
        );

        return redirect()->route('master.document-batches.show', $batch)->with('success', 'Batch created.');
    }

    public function show(DocumentBatch $documentBatch)
    {
        abort_if($documentBatch->institute_id !== $this->instituteId(), 403);

        $documentBatch->load(['session', 'course', 'courseStream', 'coursePart', 'students.student.session']);

        $totalCount      = $documentBatch->students->count();
        $foundCount      = $documentBatch->students->whereNotNull('found_at')->count();
        $distributedCount = $documentBatch->students->whereNotNull('distributed_at')->count();

        return view('institute.master.document-batches.show', compact(
            'documentBatch', 'totalCount', 'foundCount', 'distributedCount'
        ));
    }

    public function markDispatched(Request $request, DocumentBatch $documentBatch)
    {
        abort_if($documentBatch->institute_id !== $this->instituteId(), 403);

        $validated = $request->validate([
            'dispatch_date'    => 'required|date',
            'dispatch_remarks' => 'nullable|string|max:300',
        ]);

        $documentBatch->update($validated);

        return back()->with('success', 'Dispatch status updated.');
    }

    public function markReceived(Request $request, DocumentBatch $documentBatch)
    {
        abort_if($documentBatch->institute_id !== $this->instituteId(), 403);

        $validated = $request->validate([
            'received_date'  => 'required|date',
            'received_count' => 'nullable|integer|min:0',
        ]);

        $documentBatch->update($validated);

        return back()->with('success', 'Receive status updated.');
    }

    public function sort(DocumentBatch $documentBatch)
    {
        abort_if($documentBatch->institute_id !== $this->instituteId(), 403);

        $documentBatch->load(['course', 'courseStream', 'coursePart', 'session']);

        $students = $documentBatch->students()
            ->with('student')
            ->get()
            ->sortBy(fn ($row) => $row->student?->name);

        $foundCount = $students->filter(fn ($row) => $row->is_found)->count();

        return view('institute.master.document-batches.sort', compact('documentBatch', 'students', 'foundCount'));
    }

    public function toggleFound(DocumentBatch $documentBatch, DocumentBatchStudent $documentBatchStudent)
    {
        abort_if($documentBatch->institute_id !== $this->instituteId(), 403);
        abort_if($documentBatchStudent->document_batch_id !== $documentBatch->id, 404);

        $markingAsFound = !$documentBatchStudent->found_at;
        $foundCount     = $documentBatch->students()->whereNotNull('found_at')->count();

        if ($markingAsFound && $documentBatch->received_count !== null && $foundCount >= $documentBatch->received_count) {
            return response()->json([
                'error' => "Package count is {$documentBatch->received_count} — that many documents are already marked Found. Update the Package Count on the batch page if more actually arrived.",
            ], 422);
        }

        $documentBatchStudent->update([
            'found_at' => $markingAsFound ? now() : null,
        ]);

        return response()->json([
            'found'       => $documentBatchStudent->is_found,
            'found_at'    => optional($documentBatchStudent->found_at)->format('d M Y, h:i A'),
            'found_count' => $documentBatch->students()->whereNotNull('found_at')->count(),
        ]);
    }
}
