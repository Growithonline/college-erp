<?php

namespace App\Http\Controllers\Institute\Fee;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CoursePart;
use App\Models\CourseStream;
use App\Models\CourseType;
use App\Models\FeeInvoice;
use App\Models\FeeInvoiceItem;
use App\Models\FeeType;
use App\Models\PracticalFeeTokenBatch;
use App\Models\PracticalFeeTokenEntry;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectFeeRule;
use App\Services\StudentIdService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PracticalFeeTokenController extends Controller
{
    private function authenticatedUser()
    {
        foreach (['staff', 'web'] as $guard) {
            if (auth()->guard($guard)->check()) {
                return auth()->guard($guard)->user();
            }
        }

        return auth()->user();
    }

    private function actorType(): string
    {
        return auth()->guard('staff')->check() ? 'staff' : 'admin';
    }

    private function actorId(): ?int
    {
        return $this->authenticatedUser()?->id;
    }

    private function actorName(): ?string
    {
        return $this->authenticatedUser()?->name;
    }

    private function instituteId(): int
    {
        $user = $this->authenticatedUser();
        abort_if(!$user || !$user->institute_id, 403, 'Institute context missing.');

        if (auth()->guard('staff')->check() && method_exists($user, 'canManagePracticalTokens')) {
            abort_if(!$user->canManagePracticalTokens(), 403, 'Practical tokens permission required.');
        }

        return (int) $user->institute_id;
    }

    private function routePrefix(): string
    {
        return auth()->guard('staff')->check() ? 'staff.fee.practical-tokens' : 'fee.practical-tokens';
    }

    public function index(Request $request)
    {
        $instituteId = $this->instituteId();
        $activeSession = AcademicSession::viewSession($instituteId);
        $query = PracticalFeeTokenBatch::with(['session', 'course', 'subject', 'coursePart'])
            ->withSum('entries as posted_amount', 'amount')
            ->where('institute_id', $instituteId);

        if ($request->session_id) {
            $query->where('academic_session_id', $request->session_id);
        } elseif ($activeSession) {
            $query->where('academic_session_id', $activeSession->id);
        }

        $batches = $query->latest()->paginate(20)->withQueryString();
        $sessions = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();

        return view('institute.fee.practical-tokens.index', [
            'batches' => $batches,
            'sessions' => $sessions,
            'activeSession' => $activeSession,
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function create()
    {
        $instituteId = $this->instituteId();
        $activeSession = AcademicSession::viewSession($instituteId);

        $courses = Course::with([
                'parts' => fn ($q) => $q->orderBy('part_number'),
                'streams.subjects' => fn ($q) => $q->where('subjects.status', true)->orderBy('subjects.name'),
            ])
            ->where('institute_id', $instituteId)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        foreach ($courses as $course) {
            $subjectOptions = [];
            $seenSubjectIds = [];
            foreach ($course->streams as $stream) {
                foreach ($stream->subjects as $subject) {
                    if (in_array($subject->id, $seenSubjectIds, true)) {
                        continue;
                    }
                    $seenSubjectIds[] = $subject->id;
                    $subjectOptions[] = ['id' => $subject->id, 'name' => $subject->name];
                }
            }
            usort($subjectOptions, fn ($a, $b) => strcmp($a['name'], $b['name']));
            $course->subject_options = $subjectOptions;

            $partOptions = [];
            foreach ($course->parts as $part) {
                $partOptions[] = [
                    'id' => $part->id,
                    'part_number' => $part->part_number,
                    'part_name' => $part->part_name,
                    'year_number' => $part->year_number,
                ];
            }
            $course->part_options = $partOptions;
        }

        return view('institute.fee.practical-tokens.create', [
            'sessions' => AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get(),
            'courseTypes' => CourseType::forInstitute($instituteId)->active()->orderBy('sort_order')->orderBy('name')->get(),
            'courses' => $courses,
            'activeSession' => $activeSession,
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function store(Request $request)
    {
        $instituteId = $this->instituteId();
        $data = $request->validate([
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'course_id' => 'required|exists:courses,id',
            'subject_id' => 'required|exists:subjects,id',
            'course_part_id' => 'nullable|exists:course_parts,id',
            'year_number' => 'required|integer|min:1|max:10',
            'semester' => 'required|integer|min:1|max:20',
            'token_amount' => 'required|numeric|min:1',
            'payment_mode' => 'required|in:cash,online,cheque,dd,upi,neft,rtgs',
            'collection_date' => 'required|date',
            'title' => 'nullable|string|max:150',
            'remarks' => 'nullable|string|max:1000',
        ]);

        foreach (['academic_session_id' => AcademicSession::class, 'course_id' => Course::class, 'subject_id' => Subject::class] as $field => $model) {
            abort_if(!$model::where('id', $data[$field])->where('institute_id', $instituteId)->exists(), 403);
        }

        if (!empty($data['course_part_id'])) {
            abort_if(!CoursePart::where('id', $data['course_part_id'])->where('course_id', $data['course_id'])->exists(), 422);
        }

        $batch = PracticalFeeTokenBatch::create($data + [
            'institute_id' => $instituteId,
            'status' => 'open',
            'created_by_type' => $this->actorType(),
            'created_by_id' => $this->actorId(),
        ]);

        return redirect()->route($this->routePrefix() . '.show', $batch)->with('success', 'Practical fee token batch created.');
    }

    public function show(Request $request, PracticalFeeTokenBatch $batch)
    {
        $instituteId = $this->instituteId();
        abort_if($batch->institute_id !== $instituteId, 403);

        $students = $this->batchStudentQuery($batch)
            ->with(['stream.course', 'coursePart'])
            ->orderBy('name')
            ->get();

        $entries = PracticalFeeTokenEntry::where('batch_id', $batch->id)->with('invoice')->get()->keyBy('student_id');
        $postedAmount = (float) $entries->sum('amount');
        $remainingAmount = max(0, (float) $batch->token_amount - $postedAmount);

        $studentYearNumbers = $students->map(fn($student) => $student->coursePart?->year_number ?? $batch->year_number)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($studentYearNumbers)) {
            $studentYearNumbers = [$batch->year_number];
        }

        $subjectRules = SubjectFeeRule::where('institute_id', $instituteId)
            ->where('academic_session_id', $batch->academic_session_id)
            ->where('course_id', $batch->course_id)
            ->where('subject_id', $batch->subject_id)
            ->whereIn('course_part', $studentYearNumbers)
            ->where(function ($query) use ($batch) {
                $query->where('semester', $batch->semester)
                    ->orWhere('semester', 0);
            })
            ->orderByRaw('case when semester = ? then 0 else 1 end', [$batch->semester])
            ->get()
            ->groupBy('course_part')
            ->map(fn($rules) => $rules->first());

        $studentPracticalFees = $students->mapWithKeys(fn($student) => [
            $student->id => (float) ($subjectRules[$student->coursePart?->year_number ?? $batch->year_number]?->practical_fee ?? 0),
        ])->all();

        $alreadyPaidStudentIds = $this->resolveAlreadyPaidPracticalStudents($batch, $students->pluck('id')->all());

        return view('institute.fee.practical-tokens.show', [
            'batch' => $batch->load(['session', 'course', 'subject', 'coursePart']),
            'students' => $students,
            'entries' => $entries,
            'postedAmount' => $postedAmount,
            'remainingAmount' => $remainingAmount,
            'studentPracticalFees' => $studentPracticalFees,
            'alreadyPaidStudentIds' => $alreadyPaidStudentIds,
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    private function resolveAlreadyPaidPracticalStudents(PracticalFeeTokenBatch $batch, array $studentIds): array
    {
        if (empty($studentIds) || !$batch->subject_id) {
            return [];
        }

        $lowerSubjectName = strtolower(trim((string) $batch->subject->name));

        return FeeInvoiceItem::join('fee_invoices', 'fee_invoice_items.fee_invoice_id', '=', 'fee_invoices.id')
            ->whereIn('fee_invoices.student_id', $studentIds)
            ->where('fee_invoices.academic_session_id', $batch->academic_session_id)
            ->where('fee_invoices.is_cancelled', false)
            ->where(function ($query) use ($batch, $lowerSubjectName) {
                $query->where(function ($query) use ($batch) {
                    $query->where('fee_invoice_items.item_type', 'practical')
                        ->where('fee_invoice_items.subject_id', $batch->subject_id);
                })->orWhere(function ($query) use ($lowerSubjectName) {
                    $query->whereNull('fee_invoice_items.subject_id')
                        ->whereRaw('LOWER(fee_invoice_items.fee_name) LIKE ?', ["%{$lowerSubjectName}%"])
                        ->whereRaw('LOWER(fee_invoice_items.fee_name) LIKE ?', ['%practical fee%']);
                });
            })
            ->distinct()
            ->pluck('fee_invoices.student_id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    public function postEntries(Request $request, PracticalFeeTokenBatch $batch)
    {
        $instituteId = $this->instituteId();
        abort_if($batch->institute_id !== $instituteId, 403);

        $data = $request->validate([
            'amounts' => 'nullable|array',
            'amounts.*' => 'nullable|numeric|min:0',
            'fines' => 'nullable|array',
            'fines.*' => 'nullable|numeric|min:0',
            'discounts' => 'nullable|array',
            'discounts.*' => 'nullable|numeric|min:0',
        ]);

        $allowedStudentIds = $this->batchStudentQuery($batch)->pluck('students.id')->map(fn($id) => (int) $id)->all();
        $existingEntries = PracticalFeeTokenEntry::where('batch_id', $batch->id)->pluck('student_id')->map(fn($id) => (int) $id)->all();
        $alreadyPaidStudentIds = $this->resolveAlreadyPaidPracticalStudents($batch, $allowedStudentIds);

        $amounts = collect($data['amounts'] ?? [])->mapWithKeys(fn($amount, $studentId) => [
            (int) $studentId => round((float) $amount, 2),
        ]);
        $fines = collect($data['fines'] ?? [])->mapWithKeys(fn($amount, $studentId) => [
            (int) $studentId => round((float) $amount, 2),
        ]);
        $discounts = collect($data['discounts'] ?? [])->mapWithKeys(fn($amount, $studentId) => [
            (int) $studentId => round((float) $amount, 2),
        ]);

        $entries = $amounts->mapWithKeys(fn($amount, $studentId) => [
            $studentId => [
                'collect' => $amount,
                'fine' => $fines[$studentId] ?? 0.0,
                'discount' => $discounts[$studentId] ?? 0.0,
            ],
        ])->filter(fn($entry, $studentId) =>
            in_array((int) $studentId, $allowedStudentIds, true)
            && !in_array((int) $studentId, $existingEntries, true)
            && !in_array((int) $studentId, $alreadyPaidStudentIds, true)
            && ($entry['collect'] > 0 || $entry['fine'] > 0 || $entry['discount'] > 0)
        );

        if ($entries->isEmpty()) {
            return back()->withErrors(['amounts' => 'Kisi new student ke liye amount, fine ya discount enter karo.'])->withInput();
        }

        $invalidNetAmount = $entries->filter(fn($entry) => ($entry['collect'] + $entry['fine'] - $entry['discount']) <= 0);
        if ($invalidNetAmount->isNotEmpty()) {
            return back()->withErrors(['amounts' => 'Practical amount, fine aur discount ka net total 0 se zyada hona chahiye.'])->withInput();
        }

        $studentPracticalFees = $this->studentPracticalFeeAmounts(
            $batch,
            Student::whereIn('id', $entries->keys())->get()
        );

        $invalidCollect = $entries->filter(fn($entry, $studentId) => $entry['collect'] > ($studentPracticalFees[$studentId] ?? 0));
        if ($invalidCollect->isNotEmpty()) {
            return back()->withErrors(['amounts' => 'Collect amount cannot exceed the charged practical fee.'])->withInput();
        }

        $invalidDiscount = $entries->filter(fn($entry, $studentId) => $entry['discount'] > ($studentPracticalFees[$studentId] ?? 0));
        if ($invalidDiscount->isNotEmpty()) {
            return back()->withErrors(['amounts' => 'Discount cannot exceed the charged practical fee.'])->withInput();
        }

        $alreadyPosted = (float) PracticalFeeTokenEntry::where('batch_id', $batch->id)->sum('amount');
        if ($alreadyPosted + (float) $entries->sum(fn($entry) => $entry['collect']) > (float) $batch->token_amount) {
            return back()->withErrors(['amounts' => 'Entered total practical collection amount se zyada ho raha hai.'])->withInput();
        }

        $students = Student::whereIn('id', $entries->keys())->get()->keyBy('id');
        $feeType = FeeType::where(function ($query) use ($instituteId) {
                $query->where('institute_id', $instituteId)->orWhere('is_system', true);
            })
            ->where('name', 'like', '%Practical%')
            ->first();
        $year = StudentIdService::getYearFromSession($batch->session?->name ?? now()->format('Y'));

        DB::transaction(function () use ($entries, $students, $batch, $instituteId, $feeType, $year, $studentPracticalFees) {
            foreach ($entries as $studentId => $entry) {
                $student = $students[$studentId] ?? null;
                if (!$student) {
                    continue;
                }

                $collectAmount = $entry['collect'];
                $fine = $entry['fine'];
                $discount = $entry['discount'];
                $invoiceAmount = $collectAmount + $fine - $discount;

                if ($invoiceAmount <= 0) {
                    continue;
                }

                $invoiceNo = StudentIdService::generateInvoiceId($instituteId, $year);
                $feeName = ($batch->subject?->name ?? 'Subject') . ' — Practical Fee';
                $practicalDate = optional($batch->collection_date)->format('d-m-Y') ?? $batch->collection_date;
                $title = trim((string) ($batch->title ?? ''));
                $batchRemarks = trim((string) ($batch->remarks ?? ''));
                $invoiceRemarks = 'Practical Date: ' . $practicalDate;
                $invoiceRemarks .= ' | Title: ' . ($title !== '' ? $title : 'N/A');
                $invoiceRemarks .= ' | Remarks: ' . ($batchRemarks !== '' ? $batchRemarks : 'N/A');

                $invoice = FeeInvoice::create([
                    'institute_id' => $instituteId,
                    'student_id' => $student->id,
                    'academic_session_id' => $batch->academic_session_id,
                    'semester' => $batch->semester,
                    'invoice_no' => $invoiceNo,
                    'total_amount' => $invoiceAmount,
                    'discount' => $discount,
                    'paid_amount' => $collectAmount,
                    'payment_mode' => $batch->payment_mode,
                    'payment_date' => now()->toDateString(),
                    'remarks' => $invoiceRemarks,
                    'collected_by' => $this->actorName(),
                    'collected_by_staff_id' => auth()->guard('staff')->id(),
                ]);

                $invoice->load('student');
                FeeInvoiceItem::create([
                    'fee_invoice_id' => $invoice->id,
                    'fee_type_id' => $feeType?->id,
                    'subject_id' => $batch->subject_id,
                    'item_type' => 'practical',
                    'fee_name' => $feeName,
                    'amount' => $collectAmount,
                    'discount' => $discount,
                    'fine' => $fine,
                    'total_fee' => (float) ($studentPracticalFees[$studentId] ?? 0),
                ]);

                $items = collect([[
                    'checked' => 1,
                    'fee_type_id' => $feeType?->id,
                    'subject_id' => $batch->subject_id,
                    'item_type' => 'practical',
                    'fee_name' => $feeName,
                    'amount' => $collectAmount,
                    'discount' => $discount,
                    'fine' => $fine,
                    'total_fee' => (float) ($studentPracticalFees[$studentId] ?? 0),
                    'is_custom' => 0,
                ]]);

                WalletService::chargeFineItems($invoice, $items);
                WalletService::onFeeCollection($invoice);

                PracticalFeeTokenEntry::create([
                    'batch_id' => $batch->id,
                    'student_id' => $student->id,
                    'fee_invoice_id' => $invoice->id,
                    'amount' => $collectAmount,
                    'fine' => $fine,
                    'discount' => $discount,
                    'status' => 'posted',
                    'entered_by_type' => $this->actorType(),
                    'entered_by_id' => $this->actorId(),
                    'posted_at' => now(),
                ]);
            }
        });

        return redirect()->route($this->routePrefix() . '.show', $batch)->with('success', 'Practical fee amounts posted to student wallets.');
    }

    private function studentPracticalFeeAmounts(PracticalFeeTokenBatch $batch, $students): array
    {
        $studentYearNumbers = $students->map(fn($student) => $student->coursePart?->year_number ?? $batch->year_number)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($studentYearNumbers)) {
            $studentYearNumbers = [$batch->year_number];
        }

        $subjectRules = SubjectFeeRule::where('institute_id', $batch->institute_id)
            ->where('academic_session_id', $batch->academic_session_id)
            ->where('course_id', $batch->course_id)
            ->where('subject_id', $batch->subject_id)
            ->whereIn('course_part', $studentYearNumbers)
            ->where(function ($query) use ($batch) {
                $query->where('semester', $batch->semester)
                    ->orWhere('semester', 0);
            })
            ->orderByRaw('case when semester = ? then 0 else 1 end', [$batch->semester])
            ->get()
            ->groupBy('course_part')
            ->map(fn($rules) => $rules->first());

        return $students->mapWithKeys(fn($student) => [
            $student->id => (float) ($subjectRules[$student->coursePart?->year_number ?? $batch->year_number]?->practical_fee ?? 0),
        ])->all();
    }

    private function batchStudentQuery(PracticalFeeTokenBatch $batch)
    {
        $streamIds = CourseStream::where('course_id', $batch->course_id)->pluck('id');

        return Student::where('institute_id', $batch->institute_id)
            ->where('academic_session_id', $batch->academic_session_id)
            ->where('status', '!=', 'pending')
            ->whereIn('course_stream_id', $streamIds)
            ->when($batch->course_part_id, fn($query) => $query->where('course_part_id', $batch->course_part_id))
            ->where('current_semester', $batch->semester)
            ->whereHas('studentSubjects', function ($query) use ($batch) {
                $query->where('subject_id', $batch->subject_id)
                    ->where('academic_session_id', $batch->academic_session_id)
                    ->where('year_number', $batch->year_number);
            });
    }
}
