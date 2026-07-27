<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\DocumentBatch;
use App\Models\DocumentBatchStudent;
use App\Models\Student;
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

        $sessions = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $courses  = Course::where('institute_id', $instituteId)->orderBy('name')->get();

        $query = DocumentBatch::where('institute_id', $instituteId)
            ->with(['session', 'course'])
            ->withCount([
                'students',
                'students as found_count' => fn ($q) => $q->whereNotNull('found_at'),
                'students as distributed_count' => fn ($q) => $q->whereNotNull('distributed_at'),
            ])
            ->orderByDesc('id');

        if ($request->filled('session_id')) {
            $query->where('academic_session_id', (int) $request->session_id);
        }
        if ($request->filled('course_id')) {
            $query->where('course_id', (int) $request->course_id);
        }
        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
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

        return view('institute.master.document-batches.index', compact('batches', 'sessions', 'courses'));
    }

    public function create(Request $request)
    {
        $instituteId = $this->instituteId();

        $sessions = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $courses  = Course::where('institute_id', $instituteId)->orderBy('name')->get();

        $sessionId    = $request->integer('session_id') ?: null;
        $courseId     = $request->integer('course_id') ?: null;
        $documentType = $request->input('document_type', 'marksheet');

        $students = collect();

        if ($sessionId && $courseId) {
            $students = Student::where('institute_id', $instituteId)
                ->where('academic_session_id', $sessionId)
                ->where('status', 'passed_out')
                ->whereHas('stream', fn ($q) => $q->where('course_id', $courseId))
                ->orderBy('name')
                ->get();
        }

        return view('institute.master.document-batches.create', compact(
            'sessions', 'courses', 'students', 'sessionId', 'courseId', 'documentType'
        ));
    }

    public function store(Request $request)
    {
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'course_id'           => 'required|exists:courses,id',
            'document_type'       => ['required', Rule::in(array_keys(DocumentBatch::$documentTypes))],
            'batch_label'         => 'nullable|string|max:100',
            'student_ids'         => 'required|array|min:1',
            'student_ids.*'       => 'exists:students,id',
        ]);

        AcademicSession::where('id', $validated['academic_session_id'])->where('institute_id', $instituteId)->firstOrFail();
        Course::where('id', $validated['course_id'])->where('institute_id', $instituteId)->firstOrFail();

        $studentIds = Student::where('institute_id', $instituteId)
            ->whereIn('id', $validated['student_ids'])
            ->pluck('id');

        $batch = DocumentBatch::create([
            'institute_id'        => $instituteId,
            'academic_session_id' => $validated['academic_session_id'],
            'course_id'           => $validated['course_id'],
            'document_type'       => $validated['document_type'],
            'batch_label'         => $validated['batch_label'] ?? null,
        ]);

        $batch->students()->createMany(
            $studentIds->map(fn ($id) => ['student_id' => $id])->all()
        );

        return redirect()->route('master.document-batches.show', $batch)->with('success', 'Batch ban gaya.');
    }

    public function show(DocumentBatch $documentBatch)
    {
        abort_if($documentBatch->institute_id !== $this->instituteId(), 403);

        $documentBatch->load(['session', 'course', 'students.student']);

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

        return back()->with('success', 'Dispatch status update ho gaya.');
    }

    public function markReceived(Request $request, DocumentBatch $documentBatch)
    {
        abort_if($documentBatch->institute_id !== $this->instituteId(), 403);

        $validated = $request->validate([
            'received_date'  => 'required|date',
            'received_count' => 'nullable|integer|min:0',
        ]);

        $documentBatch->update($validated);

        return back()->with('success', 'Receive status update ho gaya.');
    }

    public function sort(DocumentBatch $documentBatch)
    {
        abort_if($documentBatch->institute_id !== $this->instituteId(), 403);

        $students = $documentBatch->students()
            ->with('student')
            ->get()
            ->sortBy(fn ($row) => $row->student?->name);

        return view('institute.master.document-batches.sort', compact('documentBatch', 'students'));
    }

    public function toggleFound(DocumentBatch $documentBatch, DocumentBatchStudent $documentBatchStudent)
    {
        abort_if($documentBatch->institute_id !== $this->instituteId(), 403);
        abort_if($documentBatchStudent->document_batch_id !== $documentBatch->id, 404);

        $documentBatchStudent->update([
            'found_at' => $documentBatchStudent->found_at ? null : now(),
        ]);

        return response()->json([
            'found'    => $documentBatchStudent->is_found,
            'found_at' => optional($documentBatchStudent->found_at)->format('d M Y, h:i A'),
        ]);
    }
}
