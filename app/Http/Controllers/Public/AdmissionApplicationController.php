<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Institute\Admission\AdmissionDocumentController;
use App\Http\Controllers\Institute\Master\AdmissionFormController;
use App\Mail\ApplicationDocumentsLinkMail;
use App\Models\AcademicSession;
use App\Models\AdmissionDocument;
use App\Models\Course;
use App\Models\CoursePart;
use App\Models\CourseStream;
use App\Models\DocumentType;
use App\Models\Enquiry;
use App\Models\FeePlan;
use App\Models\Institute;
use App\Models\InstituteBankAccount;
use App\Models\PaymentClaim;
use App\Models\Student;
use App\Models\StudentAcademicIdentity;
use App\Models\StudentType;
use App\Services\InstituteMailer;
use App\Services\StudentIdService;
use App\Services\WalletService;
use App\Support\StudentSnapshotBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class AdmissionApplicationController extends Controller
{
    private const FIELD_RULES = [
        'father_name'       => ['string', 'max:100', 'regex:/^[A-Za-z][A-Za-z\s\.\'\-]*$/'],
        'father_mobile'     => ['regex:/^\d{10}$/'],
        'mother_name'       => ['string', 'max:100'],
        'dob'               => ['date', 'before_or_equal:today'],
        'gender'            => ['in:male,female,other'],
        'guardian_mobile'   => ['regex:/^\d{10}$/'],
        'guardian_name'     => ['string', 'max:100'],
        'guardian_relation' => ['string', 'max:50'],
        'religion'          => ['string', 'max:50'],
        'category'          => ['string', 'max:50'],
        'special_category'  => ['string', 'max:50'],
        'nationality'       => ['string', 'max:50'],
        'aadhar_no'         => ['regex:/^\d{12}$/'],
        'apaar_no'          => ['string', 'max:50'],
        'student_type'      => ['string', 'max:30'],
        'marital_status'    => ['in:single,married'],
        'perm_village'      => ['string', 'max:100'],
        'perm_post'         => ['string', 'max:100'],
        'perm_thana'        => ['string', 'max:100'],
        'perm_district'     => ['string', 'max:100'],
        'perm_state'        => ['string', 'max:100'],
        'perm_pincode'      => ['regex:/^\d{6}$/'],
        'comm_address'      => ['string', 'max:255'],
    ];

    private const EDUCATION_FIELDS = [
        'edu_10th'       => '10th',
        'edu_12th'       => '12th',
        'edu_graduation' => 'Graduation',
        'edu_other'      => 'Other',
    ];

    private const DOCUMENT_MIME_MAP = [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
        'gif'  => ['image/gif'],
        'bmp'  => ['image/bmp'],
        'tiff' => ['image/tiff'],
        'tif'  => ['image/tiff'],
        'txt'  => ['text/plain'],
        'doc'  => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls'  => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
    ];

    private function resolveInstitute(string $shortName): Institute
    {
        $institute = Institute::where('short_name', strtoupper($shortName))->first();

        abort_if(!$institute || $institute->status !== 'active', 404);

        return $institute;
    }

    private function documentsUrl(Student $student, string $shortName): string
    {
        return URL::temporarySignedRoute(
            'public.application.documents.show',
            now()->addDays(30),
            ['shortName' => $shortName, 'student' => $student->id]
        );
    }

    private function nextStepsUrl(Student $student, string $shortName): string
    {
        return URL::temporarySignedRoute(
            'public.application.next-steps',
            now()->addDays(30),
            ['shortName' => $shortName, 'student' => $student->id]
        );
    }

    private function paymentUrl(Student $student, string $shortName): string
    {
        return URL::temporarySignedRoute(
            'public.application.payment.show',
            now()->addDays(30),
            ['shortName' => $shortName, 'student' => $student->id]
        );
    }

    private function isFieldEnabled(array $formConfig, string $key): bool
    {
        return (bool) (($formConfig[$key]['enabled'] ?? false) && ($formConfig[$key]['section_enabled'] ?? true));
    }

    private function isFieldRequired(array $formConfig, string $key): bool
    {
        return $this->isFieldEnabled($formConfig, $key) && (bool) ($formConfig[$key]['required'] ?? false);
    }

    // ── Application form ─────────────────────────────────────────────────

    public function show(Request $request, string $shortName, Enquiry $enquiry)
    {
        $institute = $this->resolveInstitute($shortName);
        abort_if($enquiry->institute_id !== $institute->id, 404);

        if ($enquiry->converted_student_id) {
            return redirect($this->nextStepsUrl($enquiry->convertedStudent, $shortName));
        }

        $formConfig = AdmissionFormController::getActiveConfig($institute->id, 'online');
        $courses = Course::where('institute_id', $institute->id)
            ->where('status', true)
            ->with(['streams' => fn ($q) => $q->where('status', true)])
            ->orderBy('name')
            ->get();
        $studentTypes = StudentType::forInstitute($institute->id)->active()->orderBy('sort_order')->get();

        return view('public.admission.application', [
            'institute'    => $institute,
            'enquiry'      => $enquiry,
            'formConfig'   => $formConfig,
            'courses'      => $courses,
            'studentTypes' => $studentTypes,
        ]);
    }

    public function store(Request $request, string $shortName, Enquiry $enquiry)
    {
        $institute = $this->resolveInstitute($shortName);
        abort_if($enquiry->institute_id !== $institute->id, 404);
        abort_if($enquiry->converted_student_id, 409, 'This application has already been submitted.');

        $formConfig = AdmissionFormController::getActiveConfig($institute->id, 'online');
        $validated = $request->validate($this->buildRules($formConfig));

        $stream = CourseStream::where('id', $validated['course_stream_id'])
            ->whereHas('course', fn ($q) => $q->where('institute_id', $institute->id))
            ->firstOrFail();

        $activeSession = AcademicSession::where('institute_id', $institute->id)
            ->where('is_active', true)
            ->firstOrFail();

        $firstPart = CoursePart::where('course_id', $stream->course_id)
            ->orderBy('year_number')->orderBy('id')->first();

        // Prefer a plan configured specifically for this course; fall back to an
        // institute-wide "All Courses" plan (course_id null) — same matching the
        // staff admission form's fee-plan dropdown uses (FeePlanController::forCourse()).
        $feePlanId = FeePlan::where('institute_id', $institute->id)
            ->where('is_active', true)
            ->where('course_id', $stream->course_id)
            ->value('id')
            ?? FeePlan::where('institute_id', $institute->id)
                ->where('is_active', true)
                ->whereNull('course_id')
                ->value('id');

        $year = StudentIdService::getYearFromSession($activeSession->name);
        $studentData = $this->extractStudentData($request, $validated, $formConfig);
        $educationRows = $this->extractEducationRows($validated, $formConfig);

        $student = DB::transaction(function () use (
            $studentData, $educationRows, $institute, $enquiry, $stream, $activeSession, $firstPart, $feePlanId, $year
        ) {
            $studentUid = StudentIdService::generateStudentId($institute->id, $year);

            $student = Student::create(array_merge($studentData, [
                'institute_id'         => $institute->id,
                'academic_session_id'  => $activeSession->id,
                'student_uid'          => $studentUid,
                'course_stream_id'     => $stream->id,
                'course_type_id'       => $stream->course->course_type_id,
                'course_part_id'       => $firstPart?->id,
                'fee_plan_id'          => $feePlanId,
                'current_semester'     => 1,
                'admission_type'       => 'new',
                'admission_date'       => now()->toDateString(),
                'submitted_date'       => now()->toDateString(),
                'status'               => 'pending',
                'admission_source'     => 'online',
                'admitted_by_type'     => 'online',
                'admitted_by_staff_id' => null,
            ]));

            foreach ($educationRows as $edu) {
                $student->educationDetails()->create($edu);
            }

            WalletService::onAdmission($student);

            $student->load('educationDetails');
            StudentAcademicIdentity::firstOrCreate(
                ['student_id' => $student->id, 'academic_session_id' => $student->academic_session_id],
                [
                    'institute_id'              => $student->institute_id,
                    'course_id'                 => $stream->course_id,
                    'course_stream_id'          => $student->course_stream_id,
                    'course_part_id'            => $student->course_part_id,
                    'semester_at_time'          => $student->current_semester,
                    'subjects_json'             => [],
                    'form_no'                   => last(explode('/', $studentUid)),
                    'admission_source_snapshot' => 'online',
                    'source'                    => 'admission',
                    'admission_type'            => 'new',
                    'profile_snapshot'          => StudentSnapshotBuilder::build($student),
                ]
            );

            $enquiry->update(['converted_student_id' => $student->id, 'status' => 'interested']);

            return $student;
        });

        $nextStepsUrl = $this->nextStepsUrl($student, $shortName);
        if ($student->email) {
            InstituteMailer::send($institute->id, $student->email, new ApplicationDocumentsLinkMail($student, $nextStepsUrl));
        }

        return redirect($nextStepsUrl);
    }

    private function buildRules(array $formConfig): array
    {
        $rules = [
            'course_stream_id' => ['required', 'exists:course_streams,id'],
            'photo'            => ['nullable', 'image', 'max:2048'],
            'name'             => ['required', 'string', 'max:100', 'regex:/^[A-Za-z][A-Za-z\s\.\'\-]*$/'],
            'mobile'           => ['required', 'regex:/^\d{10}$/'],
            'email'            => ['required', 'email', 'max:100'],
        ];

        foreach (self::FIELD_RULES as $key => $base) {
            if (!$this->isFieldEnabled($formConfig, $key)) {
                continue;
            }
            $lead = $this->isFieldRequired($formConfig, $key) ? 'required' : 'nullable';
            $rules[$key] = array_merge([$lead], $base);
        }

        foreach (self::EDUCATION_FIELDS as $key => $label) {
            if (!$this->isFieldEnabled($formConfig, $key)) {
                continue;
            }
            $lead = $this->isFieldRequired($formConfig, $key) ? 'required' : 'nullable';
            $rules["education.{$key}.institute_name"]   = [$lead, 'string', 'max:150'];
            $rules["education.{$key}.board_university"] = [$lead, 'string', 'max:150'];
            $rules["education.{$key}.roll_number"]      = [$lead, 'string', 'max:50'];
            $rules["education.{$key}.passing_year"]     = [$lead, 'digits:4', 'integer', 'between:1900,' . now()->year];
            $rules["education.{$key}.district"]         = ['nullable', 'string', 'max:100'];
            $rules["education.{$key}.division"]         = ['nullable', 'string', 'max:20'];
            $rules["education.{$key}.obtained_marks"]   = [$lead, 'numeric', 'min:0'];
            $rules["education.{$key}.max_marks"]        = [$lead, 'numeric', 'gt:0'];
            $rules["education.{$key}.percentage"]       = ['nullable', 'numeric', 'min:0', 'max:100'];
        }

        return $rules;
    }

    private function extractStudentData(Request $request, array $validated, array $formConfig): array
    {
        $data = [
            'name'   => $validated['name'],
            'mobile' => $validated['mobile'],
            'email'  => $validated['email'],
        ];

        foreach (array_keys(self::FIELD_RULES) as $key) {
            if (!$this->isFieldEnabled($formConfig, $key)) {
                continue;
            }
            $value = $validated[$key] ?? null;
            // Leave the key out entirely when empty so the column's own DB default applies —
            // some columns (student_type, nationality, marital_status) are NOT NULL and MySQL
            // only falls back to the default when the column is omitted, not when it's given NULL.
            if ($value !== null && $value !== '') {
                $data[$key] = $value;
            }
        }

        if ($this->isFieldEnabled($formConfig, 'photo') && $request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('students/photos', 'public');
        }

        return $data;
    }

    private function extractEducationRows(array $validated, array $formConfig): array
    {
        $rows = [];
        foreach (self::EDUCATION_FIELDS as $key => $label) {
            if (!$this->isFieldEnabled($formConfig, $key)) {
                continue;
            }
            $row = $validated['education'][$key] ?? null;
            if (!$row || (empty($row['institute_name']) && empty($row['board_university']))) {
                continue;
            }
            $rows[] = array_merge($row, ['exam_name' => $label]);
        }

        return $rows;
    }

    private function ensureOnlinePendingStudent(Student $student, Institute $institute): void
    {
        abort_if($student->institute_id !== $institute->id, 404);
        abort_unless($student->admission_source === 'online' && $student->status === 'pending', 404);
    }

    // ── Next steps hub ───────────────────────────────────────────────────

    public function nextStepsShow(Request $request, string $shortName, Student $student)
    {
        $institute = $this->resolveInstitute($shortName);
        $this->ensureOnlinePendingStudent($student, $institute);

        $dueAmount = $student->feePlan?->dueNowAmount($student) ?? 0.0;
        $latestClaim = PaymentClaim::where('student_id', $student->id)->latest()->first();

        $student->load('stream.course');
        $courseId = $student->stream?->course_id;
        $required = $courseId
            ? AdmissionDocumentController::getRequiredDocumentTypes($courseId, 'online', $institute->id)
            : [];
        $uploaded = AdmissionDocument::where('student_id', $student->id)->get();
        $documentsComplete = collect($required)
            ->every(fn ($item) => $uploaded->firstWhere('document_type_id', $item['document_type']->id));

        return view('public.admission.next-steps', [
            'institute'          => $institute,
            'student'            => $student,
            'dueAmount'          => $dueAmount,
            'latestClaim'        => $latestClaim,
            'documentsUrl'       => $this->documentsUrl($student, $shortName),
            'paymentUrl'         => $dueAmount > 0 ? $this->paymentUrl($student, $shortName) : null,
            'documentsComplete'  => $documentsComplete,
            'requiredDocsCount'  => count($required),
            'uploadedDocsCount'  => $uploaded->count(),
        ]);
    }

    // ── Payment ───────────────────────────────────────────────────────────

    private function resolveOnlinePaymentAccount(Institute $institute): ?InstituteBankAccount
    {
        $preferred = InstituteBankAccount::where('institute_id', $institute->id)
            ->where('is_active', true)
            ->whereNotNull('upi_id')
            ->where('is_online_payment_account', true)
            ->first();

        if ($preferred) {
            return $preferred;
        }

        // No account explicitly designated yet — fall back to the first active one with a UPI ID.
        return InstituteBankAccount::where('institute_id', $institute->id)
            ->where('is_active', true)
            ->whereNotNull('upi_id')
            ->orderBy('sort_order')
            ->first();
    }

    private function buildUpiQrDataUri(InstituteBankAccount $account, float $amount, string $studentUid): string
    {
        $upiString = 'upi://pay?pa=' . urlencode($account->upi_id)
            . '&pn=' . urlencode($account->account_name ?? 'Institute')
            . '&am=' . number_format($amount, 2, '.', '')
            . '&cu=INR&tn=' . urlencode('Admission Fee - ' . $studentUid);

        $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(220)->margin(1)->generate($upiString);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private function validateProofFile($file): ?string
    {
        if (!$file) {
            return 'Please upload a payment screenshot.';
        }

        $sizeKb = (int) ceil($file->getSize() / 1024);
        if ($sizeKb > 5120) {
            return "File size {$sizeKb} KB exceeds the maximum allowed 5120 KB.";
        }

        $ext = strtolower($file->getClientOriginalExtension());
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        if (!in_array($ext, $allowed)) {
            return "Format .$ext is not allowed. Allowed formats: jpg, jpeg, png, webp, pdf.";
        }

        $permittedMimes = array_merge(...array_map(fn ($e) => self::DOCUMENT_MIME_MAP[$e], $allowed));
        if (!in_array($file->getMimeType(), $permittedMimes, true)) {
            return 'File content does not match the allowed formats. Upload was rejected.';
        }

        return null;
    }

    public function paymentShow(Request $request, string $shortName, Student $student)
    {
        $institute = $this->resolveInstitute($shortName);
        $this->ensureOnlinePendingStudent($student, $institute);

        $dueAmount = $student->feePlan?->dueNowAmount($student) ?? 0.0;
        if ($dueAmount <= 0) {
            return redirect($this->nextStepsUrl($student, $shortName));
        }

        $latestClaim = PaymentClaim::where('student_id', $student->id)->latest()->first();
        $bankAccount = $this->resolveOnlinePaymentAccount($institute);
        $qrDataUri = $bankAccount ? $this->buildUpiQrDataUri($bankAccount, $dueAmount, $student->student_uid) : null;

        return view('public.admission.payment', [
            'institute'   => $institute,
            'student'     => $student,
            'dueAmount'   => $dueAmount,
            'latestClaim' => $latestClaim,
            'bankAccount' => $bankAccount,
            'qrDataUri'   => $qrDataUri,
        ]);
    }

    public function paymentSubmit(Request $request, string $shortName, Student $student)
    {
        $institute = $this->resolveInstitute($shortName);
        $this->ensureOnlinePendingStudent($student, $institute);

        $dueAmount = $student->feePlan?->dueNowAmount($student) ?? 0.0;
        abort_if($dueAmount <= 0, 404);

        $blocked = PaymentClaim::where('student_id', $student->id)
            ->whereIn('verification_status', ['pending', 'approved'])
            ->exists();
        abort_if($blocked, 409, 'A payment claim has already been submitted for this admission.');

        $validated = $request->validate([
            'payment_mode'    => ['required', 'in:upi_neft,pay_at_institute'],
            'amount_claimed'  => ['required_if:payment_mode,upi_neft', 'nullable', 'numeric', 'min:1'],
            'transaction_ref' => ['required_if:payment_mode,upi_neft', 'nullable', 'string', 'max:100'],
            'screenshot'      => ['required_if:payment_mode,upi_neft', 'nullable', 'file'],
        ]);

        if ($validated['payment_mode'] === 'pay_at_institute') {
            PaymentClaim::create([
                'institute_id'         => $institute->id,
                'student_id'           => $student->id,
                'amount_due'           => $dueAmount,
                'amount_claimed'       => $dueAmount,
                'payment_mode'         => 'pay_at_institute',
                'verification_status'  => 'pending',
            ]);

            return redirect($this->nextStepsUrl($student, $shortName))
                ->with('success', 'Noted — please pay this amount at the institute. Staff will confirm it once received.');
        }

        $ref = trim($validated['transaction_ref']);
        $duplicate = PaymentClaim::where('institute_id', $institute->id)
            ->where('transaction_ref', $ref)
            ->whereIn('verification_status', ['pending', 'approved'])
            ->exists();
        if ($duplicate) {
            return back()->withErrors(['transaction_ref' => 'This transaction reference has already been submitted. Please check and try again.'])->withInput();
        }

        $proofError = $this->validateProofFile($request->file('screenshot'));
        if ($proofError) {
            return back()->withErrors(['screenshot' => $proofError])->withInput();
        }

        $bankAccount = $this->resolveOnlinePaymentAccount($institute);

        $path = $request->file('screenshot')->store("payment-proofs/{$institute->id}/{$student->id}", 'public');

        PaymentClaim::create([
            'institute_id'        => $institute->id,
            'student_id'          => $student->id,
            'amount_due'          => $dueAmount,
            'amount_claimed'      => $validated['amount_claimed'],
            'payment_mode'        => 'upi_neft',
            'transaction_ref'     => $ref,
            'screenshot_path'     => $path,
            'bank_account_id'     => $bankAccount?->id,
            'verification_status' => 'pending',
        ]);

        return redirect($this->nextStepsUrl($student, $shortName))
            ->with('success', 'Payment proof submitted. Staff will verify it shortly.');
    }

    // ── Document upload ──────────────────────────────────────────────────

    public function documentsShow(Request $request, string $shortName, Student $student)
    {
        $institute = $this->resolveInstitute($shortName);
        $this->ensureOnlinePendingStudent($student, $institute);

        $student->load('stream.course');
        $courseId = $student->stream?->course_id;

        $required = $courseId
            ? AdmissionDocumentController::getRequiredDocumentTypes($courseId, 'online', $institute->id)
            : [];

        $uploaded = AdmissionDocument::where('student_id', $student->id)->get()->keyBy('document_type_id');

        return view('public.admission.documents', [
            'institute' => $institute,
            'student'   => $student,
            'required'  => $required,
            'uploaded'  => $uploaded,
        ]);
    }

    public function documentsUpload(Request $request, string $shortName, Student $student)
    {
        $institute = $this->resolveInstitute($shortName);
        $this->ensureOnlinePendingStudent($student, $institute);

        $request->validate([
            'document_type_id' => 'required|exists:document_types,id',
            'file'              => 'required|file|max:10240',
        ]);

        $docType = DocumentType::where('id', $request->document_type_id)
            ->where('institute_id', $institute->id)
            ->where('status', true)
            ->firstOrFail();

        $fileKey = 'file_' . $docType->id;
        $fileSizeKb = (int) ceil($request->file('file')->getSize() / 1024);
        if ($fileSizeKb > $docType->max_size_kb) {
            return back()->withErrors([$fileKey => "File size {$fileSizeKb} KB exceeds the maximum allowed {$docType->max_size_kb} KB."]);
        }

        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        $allowed = array_map('trim', explode(',', $docType->allowed_formats));
        if (!in_array($ext, $allowed)) {
            return back()->withErrors([$fileKey => "Format .$ext is not allowed. Allowed formats: " . $docType->allowed_formats]);
        }

        $hasUnmappedExt = !empty(array_filter($allowed, fn ($e) => !array_key_exists($e, self::DOCUMENT_MIME_MAP)));
        if (!$hasUnmappedExt) {
            $permittedMimes = array_merge(...array_map(fn ($e) => self::DOCUMENT_MIME_MAP[$e], $allowed));
            $detectedMime = $request->file('file')->getMimeType();
            if (!in_array($detectedMime, $permittedMimes, true)) {
                return back()->withErrors([$fileKey => 'File content does not match the allowed formats. Upload was rejected.']);
            }
        }

        $existing = AdmissionDocument::where('student_id', $student->id)
            ->where('document_type_id', $docType->id)
            ->first();

        if ($existing) {
            if ($existing->isApproved()) {
                return back()->withErrors([$fileKey => 'This document is already approved and cannot be replaced here. Please contact the institute.']);
            }
            Storage::disk('public')->delete($existing->file_path);
            $existing->delete();
        }

        $path = $request->file('file')->store("admission-docs/{$institute->id}/{$student->id}", 'public');

        AdmissionDocument::create([
            'institute_id'        => $institute->id,
            'student_id'          => $student->id,
            'document_type_id'    => $docType->id,
            'file_path'           => $path,
            'original_name'       => $request->file('file')->getClientOriginalName(),
            'mime_type'           => $request->file('file')->getMimeType(),
            'file_size_kb'        => $fileSizeKb,
            'uploaded_by_type'    => 'web',
            'uploaded_by_id'      => null,
            'verification_status' => 'pending',
        ]);

        return back()->with('success', "'{$docType->name}' uploaded successfully.");
    }
}
