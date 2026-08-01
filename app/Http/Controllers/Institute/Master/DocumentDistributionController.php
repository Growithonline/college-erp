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
use App\Models\InstituteIncomeCategory;
use App\Models\InstituteManualIncome;
use App\Services\InstituteWalletService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentDistributionController extends Controller
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
        $categories  = InstituteIncomeCategory::where('institute_id', $instituteId)->active()->orderBy('name')->get();

        $documentType = $request->input('document_type', 'marksheet');
        $sessionId    = $request->integer('session_id') ?: AcademicSession::where('institute_id', $instituteId)->where('is_active', true)->value('id');
        $courseTypeId = $request->integer('course_type_id') ?: null;
        $courseId     = $request->integer('course_id') ?: null;
        $streamId     = $request->integer('course_stream_id') ?: null;
        $coursePartId = $request->integer('course_part_id') ?: null;

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

        $defaultCategoryId = $categories->first(
            fn ($category) => str_contains(strtolower($category->name), $documentType === 'degree' ? 'degree' : 'marksheet')
        )?->id;

        $query = DocumentBatchStudent::whereNotNull('found_at')
            ->whereHas('batch', function ($q) use ($instituteId, $documentType, $sessionId, $courseTypeId, $courseId, $streamId, $coursePartId) {
                $q->where('institute_id', $instituteId)->where('document_type', $documentType);
                if ($sessionId) {
                    $q->where('academic_session_id', $sessionId);
                }
                if ($courseTypeId) {
                    $q->whereHas('course', fn ($cq) => $cq->where('course_type_id', $courseTypeId));
                }
                if ($courseId) {
                    $q->where('course_id', $courseId);
                }
                if ($streamId) {
                    $q->where('course_stream_id', $streamId);
                }
                if ($coursePartId) {
                    $q->where('course_part_id', $coursePartId);
                }
            })
            ->with(['student', 'batch.course.documentFee', 'batch.courseStream', 'batch.coursePart']);

        if ($request->input('distribution_status') === 'distributed') {
            $query->whereNotNull('distributed_at');
        } elseif ($request->input('distribution_status') === 'pending') {
            $query->whereNull('distributed_at');
        }

        $rows = $query->orderByDesc('found_at')->paginate(30)->withQueryString();

        $rows->getCollection()->transform(function (DocumentBatchStudent $row) use ($documentType) {
            $row->due = $row->student
                ? (WalletService::getStudentSummary($row->student, (int) $row->student->academic_session_id)['total_due'] ?? 0)
                : 0;

            $row->defaultFeeAmount = $row->batch?->course?->documentFee?->feeFor($documentType);

            return $row;
        });

        return view('institute.master.document-distribution.index', compact(
            'rows', 'sessions', 'courseTypes', 'courses', 'streams', 'courseParts', 'categories',
            'documentType', 'sessionId', 'courseTypeId', 'courseId', 'streamId', 'coursePartId', 'defaultCategoryId'
        ));
    }

    public function distribute(Request $request, DocumentBatchStudent $documentBatchStudent)
    {
        $instituteId = $this->instituteId();
        abort_if($documentBatchStudent->batch->institute_id !== $instituteId, 403);

        $validated = $request->validate([
            'received_by_name'    => 'required|string|max:150',
            'distributed_date'    => 'required|date',
            'fee_amount'          => 'nullable|numeric|min:0.01',
            'income_category_id'  => 'required_with:fee_amount|nullable|exists:institute_income_categories,id',
            'remarks'             => 'nullable|string|max:300',
        ]);

        DB::transaction(function () use ($validated, $documentBatchStudent, $instituteId) {
            $documentBatchStudent->received_by_name = $validated['received_by_name'];
            $documentBatchStudent->distributed_at   = $validated['distributed_date'];
            $documentBatchStudent->remarks          = $validated['remarks'] ?? $documentBatchStudent->remarks;

            if (!empty($validated['fee_amount'])) {
                $category = InstituteIncomeCategory::where('id', $validated['income_category_id'])
                    ->where('institute_id', $instituteId)
                    ->firstOrFail();

                $batch   = $documentBatchStudent->batch;
                $student = $documentBatchStudent->student;

                $income = InstituteManualIncome::create([
                    'institute_id'        => $instituteId,
                    'academic_session_id' => $batch->academic_session_id,
                    'income_category_id'  => $category->id,
                    'amount'              => $validated['fee_amount'],
                    'date'                => $validated['distributed_date'],
                    'description'         => $batch->document_type_label . ' fee - ' . ($student->name ?? 'Student'),
                    'created_by'          => auth()->id(),
                ]);
                $income->setRelation('category', $category);

                InstituteWalletService::creditManualIncome($income);

                $documentBatchStudent->fee_amount        = $validated['fee_amount'];
                $documentBatchStudent->income_category_id = $category->id;
                $documentBatchStudent->manual_income_id   = $income->id;
            }

            $documentBatchStudent->save();
        });

        return back()->with('success', 'Distribution recorded.');
    }
}
